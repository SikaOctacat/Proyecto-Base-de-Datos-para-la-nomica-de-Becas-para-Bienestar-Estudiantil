<?php
session_start();
require 'db.php';

header('Content-Type: application/json');

// 0. GENERADOR DE ID MANUAL PARA TiDB
function generarIdTiDB() {
    return mt_rand(100000000, 999999999);
}

// 1. FUNCIÓN DE BITÁCORA (Forzando el ID manual)
function registrarMovimiento($pdo, $usuario_id, $accion, $tabla, $detalles = null) {
    if (!$usuario_id) return; 
    try {
        $id_bitacora = generarIdTiDB();
        $sql = 'INSERT INTO bitacora (id, usuario_id, accion, tabla_afectada, detalles) VALUES (?, ?, ?, ?, ?)';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id_bitacora, $usuario_id, $accion, $tabla, $detalles]);
    } catch (PDOException $e) {
        error_log("Error en bitácora: " . $e->getMessage());
    }
}

// 2. LECTURA DE DATOS
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
    $data = $_POST; 
}

if (empty($data)) {
    echo json_encode([
        'status' => 'error', 
        'error' => 'No se recibieron datos',
        'debug' => ['metodo' => $_SERVER['REQUEST_METHOD'], 'raw_body' => substr($raw, 0, 100)]
    ]);
    exit;
}

// --- LÓGICA DE PROCESAMIENTO ---

$ejecutor_id = $_SESSION['user_id'] ?? null; 
$es_admin = (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin');
$nombre_ejecutor = $_SESSION['user'] ?? 'Desconocido'; 
$estudiante_ci = $data['cedula'] ?? ($_SESSION['rol'] === 'estudiante' ? $_SESSION['user'] : null);

if (!$estudiante_ci) {
    echo json_encode(['status' => 'error', 'error' => 'Cédula no identificada']);
    exit;
}

try {
    $pdo->beginTransaction();

    // VERIFICAR O CREAR USUARIO
    $stmt = $pdo->prepare('SELECT usuario_id FROM estudiante WHERE ci = ?');
    $stmt->execute([$estudiante_ci]);
    $existe = $stmt->fetch();
    
    $usuario_id_estudiante = null;
    $nuevo_registro = false; 

    if (!$existe || empty($existe['usuario_id'])) {
        $nuevo_registro = true;
        $password = !empty($data['password']) ? $data['password'] : $estudiante_ci; 
        $hash_pass = password_hash($password, PASSWORD_BCRYPT);
        
        $pregunta = $data['pregunta_seguridad'] ?? null;
        $respuesta = !empty($data['respuesta_seguridad']) ? strtolower(trim($data['respuesta_seguridad'])) : null;
        $hash_respuesta = $respuesta ? password_hash($respuesta, PASSWORD_BCRYPT) : null;

        // INSERT EN USUARIOS: Inyectamos el ID manual para que TiDB no dé el error 1364
        $nuevo_id_usuario = generarIdTiDB();
        $stmt = $pdo->prepare('INSERT INTO usuarios (id, usuario, password, pregunta_seguridad, respuesta_seguridad, rol) VALUES (?, ?, ?, ?, ?, "estudiante")');
        $stmt->execute([$nuevo_id_usuario, $estudiante_ci, $hash_pass, $pregunta, $hash_respuesta]);
        
        // Usamos el ID seguro que acabamos de generar
        $usuario_id_estudiante = $nuevo_id_usuario;

        if (!$existe) {
            $stmt = $pdo->prepare('INSERT INTO estudiante (ci, usuario_id, nombre1, apellido_paterno, carrera) VALUES (?, ?, "", "", "")');
            $stmt->execute([$estudiante_ci, $usuario_id_estudiante]);
        } else {
            $stmt = $pdo->prepare('UPDATE estudiante SET usuario_id = ? WHERE ci = ?');
            $stmt->execute([$usuario_id_estudiante, $estudiante_ci]);
        }
    } else {
        $usuario_id_estudiante = $existe['usuario_id'];
    }

    // ACTUALIZAR DATOS DEL ESTUDIANTE
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
        $data['nombre1'] ?? '', $data['nombre2'] ?? '', $data['apellido_paterno'] ?? '', $data['apellido_materno'] ?? '',
        $data['f_nac'] ?? null, $data['tel_estudiante'] ?? '', $data['correo'] ?? '', $data['edo_civil'] ?? '', 
        $data['tipo_beneficio'] ?? '', $data['C_Patria'] ?? '', $data['viaja'] ?? '', $data['estatus_estudio'] ?? '', 
        $data['carrera'] ?? '', $data['cod_est'] ?? '', $data['f_ingreso'] ?? null, $data['trayecto'] ?? '', $data['trimestre'] ?? '',
        $data['ira_anterior'] ?? 0, $data['comentarios'] ?? '', $estudiante_ci
    ]);

    // TABLAS RELACIONADAS (Limpieza y Carga)
    $pdo->prepare("DELETE FROM residencia WHERE ci_estudiante = ?")->execute([$estudiante_ci]);
    $pdo->prepare("DELETE FROM record_academico WHERE ci_estudiante = ?")->execute([$estudiante_ci]);
    $pdo->prepare("DELETE FROM familiar WHERE ci_estudiante = ?")->execute([$estudiante_ci]);

    // Residencia
    $stmt = $pdo->prepare('INSERT INTO residencia (ci_estudiante, t_res, t_viv, t_loc, r_prop, estado_res, municipio_res, dir_local, tel_local) VALUES (?,?,?,?,?,?,?,?,?)');
    $stmt->execute([$estudiante_ci, $data['t_res'] ?? null, $data['t_viv'] ?? null, $data['t_loc'] ?? null, $data['r_prop'] ?? null, $data['estado_res'] ?? null, $data['municipio_res'] ?? null, $data['dir_local'] ?? null, $data['tel_local'] ?? null]);

    // Record Académico
    $stmt = $pdo->prepare('INSERT INTO record_academico (ci_estudiante, ira_anterior) VALUES (?,?)');
    $stmt->execute([$estudiante_ci, $data['ira_anterior'] ?? 0]);

    // Familiares
    $vive_solo = (isset($data['no_familiares']) && ($data['no_familiares'] === true || $data['no_familiares'] === "on"));
    if (!$vive_solo) {
        foreach ($data as $key => $val) { 
            if (strpos($key, 'f_nom_') === 0 && !empty($val)) { 
                $id_suffix = substr($key, 6);
                $stmt = $pdo->prepare('INSERT INTO familiar (ci_estudiante, f_nom, f_ape, f_par, f_eda, f_ins, f_ocu, f_ing) VALUES (?,?,?,?,?,?,?,?)');
                $stmt->execute([
                    $estudiante_ci, 
                    $val, 
                    $data['f_ape_'.$id_suffix] ?? '', 
                    $data['f_par_'.$id_suffix] ?? '', 
                    (int)($data['f_eda_'.$id_suffix] ?? 0), 
                    $data['f_ins_'.$id_suffix] ?? '', 
                    $data['f_ocu_'.$id_suffix] ?? '', 
                    (float)($data['f_ing_'.$id_suffix] ?? 0)
                ]);
            } 
        }
    }

    // BITÁCORA
    if ($nuevo_registro) {
        $detalle = $es_admin ? "Admin $nombre_ejecutor registró a $estudiante_ci" : "Auto-registro de $estudiante_ci";
        registrarMovimiento($pdo, $ejecutor_id ?? $usuario_id_estudiante, "Registro Completo", "Sistema", $detalle);
    }

    $pdo->commit();
    echo json_encode(['status' => 'ok', 'message' => '¡Proceso completado!']);

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status' => 'error', 'error' => $e->getMessage()]);
}