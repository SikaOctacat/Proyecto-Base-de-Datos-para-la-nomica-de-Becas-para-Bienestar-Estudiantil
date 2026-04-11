<?php
session_start();
require 'db.php';
header('Content-Type: application/json');

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data) {
    echo json_encode(['status' => 'error', 'error' => 'Información inválida']);
    exit;
}

// --- IDENTIFICAR ESTUDIANTE (RBAC) ---
$estudiante_id = null;
$pnf_id = null;
$is_admin = (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin');

if (isset($_SESSION['user']) && !$is_admin) {
    $stmt = $pdo->prepare('SELECT e.id, e.pnf_id FROM estudiantes e JOIN usuarios u ON e.usuario_id = u.id WHERE u.usuario = ?');
    $stmt->execute([$_SESSION['user']]);
    $row = $stmt->fetch();
    if ($row) {
        $estudiante_id = $row['id'];
        $pnf_id = $row['pnf_id'];
    }
}

if (!$estudiante_id && !empty($data['cedula'])) {
    $stmt = $pdo->prepare('SELECT id, pnf_id FROM estudiantes WHERE ci = ?');
    $stmt->execute([$data['cedula']]);
    $row = $stmt->fetch();
    if ($row) {
        $estudiante_id = $row['id'];
        $pnf_id = $row['pnf_id'];
    }
}

try {
    $pdo->beginTransaction();

    // --- REGISTRO DE USUARIO NUEVO (PASO 1) ---
    if (!$estudiante_id && !empty($data['cedula']) && !empty($data['password'])) {
        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE usuario = ?');
        $stmt->execute([$data['cedula']]);
        if ($stmt->fetch()) {
            throw new Exception("La cédula ya está registrada. Por favor, inicie sesión.");
        }

        $hash = hash('sha256', $data['password']);
        
        // CORRECCIÓN: Se eliminaron 'pregunta_seguridad' y 'respuesta_seguridad' de la consulta
        $stmt = $pdo->prepare('INSERT INTO usuarios (usuario, password, rol) VALUES (?, ?, "estudiante")');
        $stmt->execute([
            $data['cedula'], 
            $hash
        ]);
        $usuario_id = $pdo->lastInsertId();

        $nombres_full = trim(($data['nombre1'] ?? '') . ' ' . ($data['nombre2'] ?? ''));
        $apellidos_full = trim(($data['apellido_paterno'] ?? '') . ' ' . ($data['apellido_materno'] ?? ''));
        
        $stmt = $pdo->prepare('INSERT INTO estudiantes (usuario_id, ci, nombres, apellidos) VALUES (?, ?, ?, ?)');
        $stmt->execute([$usuario_id, $data['cedula'], $nombres_full, $apellidos_full]);
        $estudiante_id = $pdo->lastInsertId();

        $_SESSION['user'] = $data['cedula'];
        $_SESSION['rol'] = 'estudiante';
    }

    if (!$estudiante_id) {
        throw new Exception("No se pudo identificar al estudiante. Inicie sesión para continuar.");
    }

    // --- ACTUALIZAR DATOS PERSONALES (PASO 1) ---
    $nombres_full = trim(($data['nombre1'] ?? '') . ' ' . ($data['nombre2'] ?? ''));
    $apellidos_full = trim(($data['apellido_paterno'] ?? '') . ' ' . ($data['apellido_materno'] ?? ''));

    $stmt = $pdo->prepare('
        UPDATE estudiantes SET 
            nombres = ?, apellidos = ?, telefono = ?, fecha_nacimiento = ?, 
            edad = ?, estado_civil = ?, correo = ?, carnet_patria = ?, estatus_estudio = ?
        WHERE id = ?
    ');
    $stmt->execute([
        $nombres_full, $apellidos_full, $data['tel_estudiante'] ?? '',
        $data['f_nac'] ?? null, $data['edad'] ?? 0, $data['edo_civil'] ?? '',
        $data['correo'] ?? '', $data['C_Patria'] ?? '',
        ($data['estatus_estudio'] ?? '') === 'activo' ? 'activo' : 'inactivo',
        $estudiante_id
    ]);

    // --- PNF / CARRERA (PASO 3) ---
    if ($pnf_id) {
        $stmt = $pdo->prepare('UPDATE pnfs SET fecha_ingreso=?, trayecto=?, trimestre_actual=?, carrera=?, codigo_estudiante=? WHERE id=?');
        $stmt->execute([
            $data['f_ingreso'] ?? null, $data['trayecto'] ?? null, $data['trimestre'] ?? null,
            $data['carrera'] ?? null, $data['cod_est'] ?? null, $pnf_id
        ]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO pnfs (fecha_ingreso, trayecto, trimestre_actual, carrera, codigo_estudiante) VALUES (?,?,?,?,?)');
        $stmt->execute([
            $data['f_ingreso'] ?? null, $data['trayecto'] ?? null, $data['trimestre'] ?? null,
            $data['carrera'] ?? null, $data['cod_est'] ?? null
        ]);
        $pnf_id = $pdo->lastInsertId();
        $pdo->prepare('UPDATE estudiantes SET pnf_id = ? WHERE id = ?')->execute([$pnf_id, $estudiante_id]);
    }

    // --- LIMPIEZA DE TABLAS RELACIONADAS ---
    $tables = ['residencias', 'records_academicos', 'familiares', 'estudiante_becas'];
    foreach ($tables as $table) {
        $pdo->prepare("DELETE FROM $table WHERE estudiante_id = ?")->execute([$estudiante_id]);
    }

    // --- BECA ---
    $tipo_beca_form = $data['tipo_beneficio'] ?? 'Beca Estudio'; 
    $stmtBeca = $pdo->prepare('SELECT id FROM becas WHERE tipo LIKE ? LIMIT 1');
    $stmtBeca->execute(["%$tipo_beca_form%"]);
    $beca_row = $stmtBeca->fetch();
    $beca_id_final = ($beca_row) ? $beca_row['id'] : 1; 

    $pdo->prepare('INSERT INTO estudiante_becas (estudiante_id, beca_id, fecha_solicitud, estado) VALUES (?, ?, NOW(), "Pendiente")')
        ->execute([$estudiante_id, $beca_id_final]);

    // --- RESIDENCIA (PASO 2) ---
    $stmt = $pdo->prepare('INSERT INTO residencias (estudiante_id, tipo_vivienda, direccion, telefono, localidad, municipio, estado) VALUES (?,?,?,?,?,?,?)');
    $stmt->execute([
        $estudiante_id, 
        $data['t_viv'] ?? null, 
        $data['dir_local'] ?? null, 
        $data['tel_local'] ?? null,
        $data['t_loc'] ?? null, // Corregido el nombre de la clave para que coincida con main.js
        $data['municipio_res'] ?? null,
        $data['estado_res'] ?? null
    ]);

    // --- RÉCORD ACADÉMICO (PASO 4) ---
    $stmt = $pdo->prepare('INSERT INTO records_academicos (estudiante_id, indice_trimestre, ira) VALUES (?,?,?)');
    $stmt->execute([
        $estudiante_id, 
        $data['record_indice'] ?? 0, 
        $data['ira_anterior'] ?? 0 // CORRECCIÓN: Se cambió 'm_ira' por 'ira_anterior'
    ]);

    // --- FAMILIARES (PASO 5) ---
    if (empty($data['no_familiares']) || $data['no_familiares'] !== true) {
        $fam = [];
        foreach ($data as $k => $v) {
            if (preg_match('/^f_(nom|ape|par|eda|ins|ocu|ing)_(\d+)$/', $k, $m)) {
                $fam[$m[2]][$m[1]] = $v;
            }
        }
        foreach ($fam as $f) {
            if (!empty($f['nom'])) {
                $stmt = $pdo->prepare('INSERT INTO familiares (estudiante_id, nombres, apellidos, parentesco, edad, instruccion, ocupacion, ingreso) VALUES (?,?,?,?,?,?,?,?)');
                $stmt->execute([
                    $estudiante_id, $f['nom'], $f['ape'] ?? '', $f['par'] ?? '', 
                    $f['eda'] ?? 0, $f['ins'] ?? '', $f['ocu'] ?? '', $f['ing'] ?? 0
                ]);
            }
        }
    }

    // --- DATOS ADICIONALES (PASO 6) ---
    if (!empty($data['comentarios'])) {
        $stmt = $pdo->prepare('UPDATE estudiantes SET observaciones = ? WHERE id = ?');
        $stmt->execute([$data['comentarios'], $estudiante_id]);
    }

    $pdo->commit();
    echo json_encode(['status' => 'ok']);

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status' => 'error', 'error' => $e->getMessage()]);
}