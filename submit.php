<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

// Función para las tablas que NO tienen autogenerado (usuarios y familiar)
function generarIdManual() {
    return mt_rand(100000, 99999999);
}

// 1. LECTURA DE DATOS
$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;

if (empty($data)) {
    echo json_encode(['status' => 'error', 'error' => 'No se recibieron datos']);
    exit;
}

// 2. BITÁCORA (Aquí TiDB SI genera el ID solo por AUTO_RANDOM)
function registrarMovimiento($pdo, $usuario_id, $accion, $tabla, $detalles = null) {
    if (!$usuario_id) return; 
    try {
        $sql = 'INSERT INTO bitacora (usuario_id, accion, tabla_afectada, detalles) VALUES (?, ?, ?, ?)';
        $pdo->prepare($sql)->execute([$usuario_id, $accion, $tabla, $detalles]);
    } catch (PDOException $e) {
        error_log("Error en bitácora: " . $e->getMessage());
    }
}

$estudiante_ci = $data['cedula'] ?? null;
if (!$estudiante_ci) {
    echo json_encode(['status' => 'error', 'error' => 'Falta la cédula']);
    exit;
}

try {
    $pdo->beginTransaction();

    // --- SECCIÓN USUARIOS (Requiere ID MANUAL) ---
    $stmt = $pdo->prepare('SELECT usuario_id FROM estudiante WHERE ci = ?');
    $stmt->execute([$estudiante_ci]);
    $existe = $stmt->fetch();
    
    $user_id = $existe['usuario_id'] ?? null;

    if (!$user_id) {
        $user_id = generarIdManual(); // Obligatorio porque tu tabla no tiene Extra: auto_increment
        $pass = password_hash($data['password'] ?? $estudiante_ci, PASSWORD_BCRYPT);
        
        $stmt = $pdo->prepare('INSERT INTO usuarios (id, usuario, password, rol) VALUES (?, ?, ?, "estudiante")');
        $stmt->execute([$user_id, $estudiante_ci, $pass]);

        $stmt = $pdo->prepare('INSERT INTO estudiante (ci, usuario_id) VALUES (?, ?)');
        $stmt->execute([$estudiante_ci, $user_id]);
    }

    // --- SECCIÓN ESTUDIANTE (Update) ---
    $sql_est = "UPDATE estudiante SET 
                nombre1=?, nombre2=?, apellido_paterno=?, apellido_materno=?, 
                f_nac=?, tel_estudiante=?, correo=?, edo_civil=?, carrera=?, 
                trayecto=?, trimestre=? WHERE ci=?";
    $pdo->prepare($sql_est)->execute([
        $data['nombre1']??'', $data['nombre2']??'', $data['apellido_paterno']??'', $data['apellido_materno']??'',
        $data['f_nac']??null, $data['tel_estudiante']??'', $data['correo']??'', $data['edo_civil']??'',
        $data['carrera']??'', $data['trayecto']??'', $data['trimestre']??'', $estudiante_ci
    ]);

    // --- SECCIÓN RESIDENCIA (TiDB SI genera ID solo por AUTO_INCREMENT) ---
    $pdo->prepare("DELETE FROM residencia WHERE ci_estudiante = ?")->execute([$estudiante_ci]);
    $stmt = $pdo->prepare('INSERT INTO residencia (ci_estudiante, estado_res, municipio_res, dir_local) VALUES (?, ?, ?, ?)');
    $stmt->execute([$estudiante_ci, $data['estado_res']??'', $data['municipio_res']??'', $data['dir_local']??'']);

    // --- SECCIÓN FAMILIAR (Requiere ID MANUAL por cada familiar) ---
    $pdo->prepare("DELETE FROM familiar WHERE ci_estudiante = ?")->execute([$estudiante_ci]);
    if (!isset($data['no_familiares'])) {
        foreach ($data as $key => $val) {
            if (strpos($key, 'f_nom_') === 0 && !empty($val)) {
                $id_fam = generarIdManual(); // Obligatorio para esta tabla
                $suffix = substr($key, 6);
                $stmt = $pdo->prepare('INSERT INTO familiar (id, ci_estudiante, f_nom, f_ape, f_par) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$id_fam, $estudiante_ci, $val, $data['f_ape_'.$suffix]??'', $data['f_par_'.$suffix]??'']);
            }
        }
    }

    registrarMovimiento($pdo, $user_id, "Registro Actualizado", "Sistema", "CI: $estudiante_ci");

    $pdo->commit();
    echo json_encode(['status' => 'ok', 'message' => '¡Logrado! Todo guardado.']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status' => 'error', 'error' => "Error en DB: " . $e->getMessage()]);
}