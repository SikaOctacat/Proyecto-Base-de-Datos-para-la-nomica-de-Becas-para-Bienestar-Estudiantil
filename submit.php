<?php
session_start();
require 'db.php';

// 1. FUNCIÓN DE BITÁCORA ACTUALIZADA (Sin ID manual para que use AUTO_RANDOM)
function registrarMovimiento($pdo, $usuario_id, $accion, $tabla, $detalles = null) {
    $sql = 'INSERT INTO bitacora (usuario_id, accion, tabla_afectada, detalles) VALUES (?, ?, ?, ?)';
    $stmt = $pdo->prepare($sql);
    try {
        $stmt->execute([$usuario_id, $accion, $tabla, $detalles]);
    } catch (PDOException $e) {
        error_log("Error en bitácora: " . $e->getMessage());
    }
}

header('Content-Type: application/json');

// 1. Intentamos la lectura estándar
$raw = file_get_contents('php://input');

// 2. Si falla (vacío), intentamos forzar la lectura del stream 
// Esto soluciona problemas con proxies que no envían el content-length correctamente
if (empty($raw)) {
    $stream = fopen('php://input', 'r');
    $raw = stream_get_contents($stream);
    fclose($stream);
}

// 3. Diagnóstico real: si sigue vacío, enviamos los Headers para ver qué está pasando
if (empty($raw)) {
    echo json_encode([
        'status' => 'error', 
        'error' => 'Cuerpo totalmente vacío en el servidor',
        'debug_info' => [
            'method' => $_SERVER['REQUEST_METHOD'],
            'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'no definido',
            'content_length' => $_SERVER['CONTENT_LENGTH'] ?? 'no definido',
            'all_headers' => getallheaders() 
        ]
    ]);
    exit;
}

$data = json_decode($raw, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'status' => 'error', 
        'error' => 'Error al decodificar JSON: ' . json_last_error_msg(),
        'debug_raw' => $raw // Esto nos mostrará qué llegó exactamente
    ]);
    exit;
}

if (!$data) {
    echo json_encode(['status' => 'error', 'error' => 'No se recibieron datos validos']);
    exit;
}

// IDENTIFICAR AL EJECUTOR Y AL ESTUDIANTE
$ejecutor_id = $_SESSION['user_id'] ?? null; 
$es_admin = (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin');
$nombre_ejecutor = $_SESSION['user'] ?? 'Desconocido'; 

$estudiante_ci = null;
if (!empty($data['cedula'])) {
    $estudiante_ci = $data['cedula'];
} elseif (isset($_SESSION['user']) && $_SESSION['rol'] === 'estudiante') {
    $estudiante_ci = $_SESSION['user'];
}

if (!$estudiante_ci) {
    echo json_encode(['status' => 'error', 'error' => 'Cédula no identificada']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 2. VERIFICAR O CREAR USUARIO
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

        // ELIMINAMOS 'id' DEL INSERT DE USUARIOS (TiDB lo genera solo)
        $stmt = $pdo->prepare('INSERT INTO usuarios (usuario, password, pregunta_seguridad, respuesta_seguridad, rol) VALUES (?, ?, ?, ?, "estudiante")');
        $stmt->execute([$estudiante_ci, $hash_pass, $pregunta, $hash_respuesta]);
        
        $usuario_id_estudiante = $pdo->lastInsertId();

        if (!$existe) {
            // Le pasamos un string vacío a nombre1, apellido_paterno y carrera 
            // para que TiDB nos deje crear el "esqueleto" inicial.
            $stmt = $pdo->prepare('INSERT INTO estudiante (ci, usuario_id, nombre1, apellido_paterno, carrera) VALUES (?, ?, "", "", "")');
            $stmt->execute([$estudiante_ci, $usuario_id_estudiante]);
        } else {
            $stmt = $pdo->prepare('UPDATE estudiante SET usuario_id = ? WHERE ci = ?');
            $stmt->execute([$usuario_id_estudiante, $estudiante_ci]);
        }
    } else {
        $usuario_id_estudiante = $existe['usuario_id'];
    }

    // 3. ACTUALIZAR TABLA 'estudiante'
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

    // 4. LIMPIEZA E INSERCIÓN (Quitando 'id' de los INSERT para que TiDB use AUTO_RANDOM/INCREMENT)
    $pdo->prepare("DELETE FROM residencia WHERE ci_estudiante = ?")->execute([$estudiante_ci]);
    $pdo->prepare("DELETE FROM record_academico WHERE ci_estudiante = ?")->execute([$estudiante_ci]);
    $pdo->prepare("DELETE FROM familiar WHERE ci_estudiante = ?")->execute([$estudiante_ci]);

    // Inserción en Residencia (Sin columna ID)
    $stmt = $pdo->prepare('INSERT INTO residencia (ci_estudiante, t_res, t_viv, t_loc, r_prop, estado_res, municipio_res, dir_local, tel_local) VALUES (?,?,?,?,?,?,?,?,?)');
    $stmt->execute([$estudiante_ci, $data['t_res'] ?? null, $data['t_viv'] ?? null, $data['t_loc'] ?? null, $data['r_prop'] ?? null, $data['estado_res'] ?? null, $data['municipio_res'] ?? null, $data['dir_local'] ?? null, $data['tel_local'] ?? null]);

    // Inserción en Record (Sin columna ID)
    $stmt = $pdo->prepare('INSERT INTO record_academico (ci_estudiante, ira_anterior) VALUES (?,?)');
    $stmt->execute([$estudiante_ci, $data['ira_anterior'] ?? 0]);

    $vive_solo = (isset($data['no_familiares']) && ($data['no_familiares'] === true || $data['no_familiares'] === "on"));
    if (!$vive_solo) {
        $idsFam = [];
        foreach ($data as $key => $val) { if (strpos($key, 'f_nom_') === 0) { $idsFam[] = substr($key, 6); } }
        foreach ($idsFam as $id) {
            // Inserción en Familiar (Sin columna ID)
            $stmt = $pdo->prepare('INSERT INTO familiar (ci_estudiante, f_nom, f_ape, f_par, f_eda, f_ins, f_ocu, f_ing) VALUES (?,?,?,?,?,?,?,?)');
            $stmt->execute([$estudiante_ci, $data['f_nom_'.$id], $data['f_ape_'.$id] ?? '', $data['f_par_'.$id] ?? '', (int)($data['f_eda_'.$id] ?? 0), $data['f_ins_'.$id] ?? '', $data['f_ocu_'.$id] ?? '', (float)($data['f_ing_'.$id] ?? 0)]);
        }
    }

    // LÓGICA DE BITÁCORA
    $nombre_est_log = trim(($data['nombre1'] ?? '') . ' ' . ($data['apellido_paterno'] ?? ''));

    if ($nuevo_registro) {
        if ($es_admin) {
            $detalle_admin = "El administrador $nombre_ejecutor registró al usuario $estudiante_ci ($nombre_est_log)";
            registrarMovimiento($pdo, $ejecutor_id, "Registro de Usuario", "Sistema/Multitabla", $detalle_admin);

            $detalle_estudiante = "Cuenta creada por el administrador $nombre_ejecutor";
            registrarMovimiento($pdo, $usuario_id_estudiante, "Cuenta Creada", "Sistema", $detalle_estudiante);
        } else {
            $detalle_self = "El usuario $nombre_est_log ($estudiante_ci) se registró exitosamente";
            registrarMovimiento($pdo, $usuario_id_estudiante, "Registro de Usuario", "Sistema/Multitabla", $detalle_self);
        }
    }

    $pdo->commit();
    echo json_encode(['status' => 'ok', 'message' => '¡Registro procesado con éxito!']);

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status' => 'error', 'error' => $e->getMessage()]);
}