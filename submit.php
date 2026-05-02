<?php
session_start();
require 'db.php';

// Función para registrar movimientos en la bitácora
function registrarMovimiento($pdo, $usuario_id, $accion, $tabla, $detalles = null) {
    $stmt = $pdo->prepare('INSERT INTO bitacora (usuario_id, accion, tabla_afectada, detalles) VALUES (?, ?, ?, ?)');
    $stmt->execute([$usuario_id, $accion, $tabla, $detalles]);
}

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

    // 2. VERIFICAR O CREAR USUARIO Y OBTENER EL ID
    // Buscamos el usuario_id en la tabla estudiante para saber si ya tiene cuenta vinculada
    $stmt = $pdo->prepare('SELECT usuario_id FROM estudiante WHERE ci = ?');
    $stmt->execute([$estudiante_ci]);
    $existe = $stmt->fetch();
    $usuario_id = null;

    if (!$existe || empty($existe['usuario_id'])) {
        // Si no existe, creamos el usuario con su pregunta de seguridad
        $password = !empty($data['password']) ? $data['password'] : $estudiante_ci; 
        $hash_pass = password_hash($password, PASSWORD_BCRYPT);
        
        $pregunta = $data['pregunta_seguridad'] ?? null;
        $respuesta = !empty($data['respuesta_seguridad']) ? strtolower(trim($data['respuesta_seguridad'])) : null;
        $hash_respuesta = $respuesta ? password_hash($respuesta, PASSWORD_BCRYPT) : null;

        $stmt = $pdo->prepare('INSERT INTO usuarios (usuario, password, pregunta_seguridad, respuesta_seguridad, rol) VALUES (?, ?, ?, ?, "estudiante")');
        $stmt->execute([
            $estudiante_ci, 
            $hash_pass, 
            $pregunta, 
            $hash_respuesta
        ]);
        
        $usuario_id = $pdo->lastInsertId();

        if (!$existe) {
            // Creamos el registro en estudiante vinculado al usuario_id
            $stmt = $pdo->prepare('INSERT INTO estudiante (ci, usuario_id) VALUES (?, ?)');
            $stmt->execute([$estudiante_ci, $usuario_id]);
        } else {
            // Si el estudiante existía pero no tenía usuario vinculado (caso raro, pero previene errores)
            $stmt = $pdo->prepare('UPDATE estudiante SET usuario_id = ? WHERE ci = ?');
            $stmt->execute([$usuario_id, $estudiante_ci]);
        }

        // Registrar en bitácora
        registrarMovimiento($pdo, $usuario_id, 'Registro Inicial', 'usuarios/estudiante', 'Creación de cuenta y vinculación de cédula');
    } else {
        // Si ya existía, simplemente capturamos su ID para los logs siguientes
        $usuario_id = $existe['usuario_id'];
    }

    // 3. ACTUALIZAR TABLA 'estudiante' (Datos personales, PNF y académicos)
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
        $data['comentarios'] ?? '', 
        $estudiante_ci
    ]);

    // Registrar actualización en bitácora
    registrarMovimiento($pdo, $usuario_id, 'Actualización de Perfil', 'estudiante', 'Actualización de datos personales y/o académicos');

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

    // 7. INSERTAR FAMILIARES
    $vive_solo = (isset($data['no_familiares']) && ($data['no_familiares'] === true || $data['no_familiares'] === "on"));
    
    if (!$vive_solo) {
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

        // Registrar familiares en bitácora
        if (count($idsFam) > 0) {
            registrarMovimiento($pdo, $usuario_id, 'Carga de Familiares', 'familiar', 'Se registraron ' . count($idsFam) . ' familiares en el sistema');
        }
    } else {
        // Opcional: Registrar que vive solo si marcó la casilla
        registrarMovimiento($pdo, $usuario_id, 'Actualización de Familiares', 'familiar', 'Indicó vivir solo/sin carga familiar');
    }

    $pdo->commit();
    echo json_encode(['status' => 'ok', 'message' => '¡Registro procesado con éxito!']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status' => 'error', 'error' => $e->getMessage()]);
}