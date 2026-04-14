<?php
session_start();
require 'db.php';
header('Content-Type: application/json');

// Recibir el JSON enviado desde el frontend
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    echo json_encode(['status' => 'error', 'error' => 'No se recibieron datos válidos']);
    exit;
}

// 1. IDENTIFICAR AL ESTUDIANTE (Usamos la cédula como ID principal según tu DB)
$estudiante_ci = null;

if (isset($_SESSION['user'])) {
    $estudiante_ci = $_SESSION['user'];
} elseif (!empty($data['cedula'])) {
    $estudiante_ci = $data['cedula'];
}

if (!$estudiante_ci) {
    echo json_encode(['status' => 'error', 'error' => 'Cédula no identificada']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 2. VERIFICAR O CREAR USUARIO (Si es registro nuevo)
    $stmt = $pdo->prepare('SELECT ci FROM estudiante WHERE ci = ?');
    $stmt->execute([$estudiante_ci]);
    $existe = $stmt->fetch();

    if (!$existe) {
        // Si no existe, creamos el usuario primero
        $password = !empty($data['password']) ? $data['password'] : $estudiante_ci; // Password por defecto es la CI
        $hash = password_hash($password, PASSWORD_BCRYPT);
        
        $stmt = $pdo->prepare('INSERT INTO usuarios (usuario, password, rol) VALUES (?, ?, "estudiante")');
        $stmt->execute([$estudiante_ci, $hash]);
        $usuario_id = $pdo->lastInsertId();

        // Creamos el registro en estudiante
        $stmt = $pdo->prepare('INSERT INTO estudiante (ci, usuario_id) VALUES (?, ?)');
        $stmt->execute([$estudiante_ci, $usuario_id]);
    }

    // 3. ACTUALIZAR TABLA 'estudiante' (Datos personales, PNF y académicos)
    // Según tu script JS, estos son los nombres de las llaves en window.formDataStorage
    $stmt = $pdo->prepare('
        UPDATE estudiante SET 
            nombre1 = ?, nombre2 = ?, apellido_paterno = ?, apellido_materno = ?,
            f_nac = ?, tel_estudiante = ?, correo = ?, edo_civil = ?, 
            tipo_beneficio = ?, C_Patria = ?, viaja = ?, estatus_estudio = ?, 
            carrera = ?, cod_est = ?, f_ingreso = ?, trayecto = ?, trimestre = ?,
            ira_anterior = ?, observaciones = ?
        WHERE ci = ?
    ');
    $stmt->execute([
        $data['nombre1'] ?? '', 
        $data['nombre2'] ?? '', 
        $data['apellido_paterno'] ?? '', 
        $data['apellido_materno'] ?? '',
        $data['f_nac'] ?? null,
        $data['tel_estudiante'] ?? '', 
        $data['correo'] ?? '', 
        $data['edo_civil'] ?? '', 
        $data['tipo_beneficio'] ?? '', 
        $data['C_Patria'] ?? '', 
        $data['viaja'] ?? '', 
        $data['estatus_estudio'] ?? '', 
        $data['carrera'] ?? '', 
        $data['cod_est'] ?? '', 
        $data['f_ingreso'] ?? null, 
        $data['trayecto'] ?? '', 
        $data['trimestre'] ?? '',
        $data['ira_anterior'] ?? 0, 
        $data['comentarios'] ?? '', // Se mapea a 'Observaciones' según tu JS
        $estudiante_ci
    ]);

    // 4. LIMPIEZA DE TABLAS RELACIONADAS (Para evitar duplicados al re-enviar)
    $pdo->prepare("DELETE FROM residencia WHERE ci_estudiante = ?")->execute([$estudiante_ci]);
    $pdo->prepare("DELETE FROM record_academico WHERE ci_estudiante = ?")->execute([$estudiante_ci]);
    $pdo->prepare("DELETE FROM familiar WHERE ci_estudiante = ?")->execute([$estudiante_ci]);

    // 5. INSERTAR RESIDENCIA
    $stmt = $pdo->prepare('INSERT INTO residencia (ci_estudiante, t_res, t_viv, t_loc, r_prop, estado_res, municipio_res, dir_local, tel_local) VALUES (?,?,?,?,?,?,?,?,?)');
    $stmt->execute([
        $estudiante_ci, 
        $data['t_res'] ?? null, 
        $data['t_viv'] ?? null, 
        $data['t_loc'] ?? null,
        $data['r_prop'] ?? null, 
        $data['estado_res'] ?? null, 
        $data['municipio_res'] ?? null, 
        $data['dir_local'] ?? null, 
        $data['tel_local'] ?? null
    ]);

    // 6. INSERTAR RÉCORD ACADÉMICO
    $stmt = $pdo->prepare('INSERT INTO record_academico (ci_estudiante, ira_anterior) VALUES (?,?)');
    $stmt->execute([$estudiante_ci, $data['ira_anterior'] ?? 0]);

    // 7. INSERTAR FAMILIARES (Lógica dinámica de f_nom_X)
    $vive_solo = ($data['no_familiares'] === true || $data['no_familiares'] === "on");
    
    if (!$vive_solo) {
        // Extraer los IDs únicos de los familiares enviados (ej: de f_nom_1 extrae "1")
        $idsFam = [];
        foreach ($data as $key => $val) {
            if (strpos($key, 'f_nom_') === 0) {
                $idsFam[] = substr($key, 6);
            }
        }

        foreach ($idsFam as $id) {
            $stmt = $pdo->prepare('INSERT INTO familiar (ci_estudiante, f_nom, f_ape, f_par, f_eda, f_ins, f_ocu, f_ing) VALUES (?,?,?,?,?,?,?,?)');
            $stmt->execute([
                $estudiante_ci,
                $data['f_nom_'.$id],
                $data['f_ape_'.$id] ?? '',
                $data['f_par_'.$id] ?? '',
                (int)($data['f_eda_'.$id] ?? 0),
                $data['f_ins_'.$id] ?? '',
                $data['f_ocu_'.$id] ?? '',
                (float)($data['f_ing_'.$id] ?? 0)
            ]);
        }
    }

    $pdo->commit();
    echo json_encode(['status' => 'ok', 'message' => '¡Registro procesado con éxito!']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status' => 'error', 'error' => $e->getMessage()]);
}