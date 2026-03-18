<?php
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
    $stmt = $pdo->prepare('SELECT id, pnf_id FROM estudiantes WHERE usuario_id = (SELECT id FROM usuarios WHERE usuario = ?)');
    $stmt->execute([$_SESSION['user']]);
    $row = $stmt->fetch();
    if ($row) {
        $estudiante_id = $row['id'];
        $pnf_id = $row['pnf_id'];
    }
}

// Si no se identificó por sesión (o es admin), buscamos por cédula enviada
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

    // 2. Si es una postulación abierta (no logueado) y trae contraseña, creamos cuenta
    if (!$estudiante_id && !empty($data['cedula']) && !empty($data['password'])) {
        // Verificar duplicados
        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE usuario = ?');
        $stmt->execute([$data['cedula']]);
        if ($stmt->fetch()) {
            throw new Exception("Ya existe una cuenta con esta cédula. Use el botón de 'Entrar al Portal'.");
        }

        // Crear Usuario
        $hash = hash('sha256', $data['password']);
        $stmt = $pdo->prepare('INSERT INTO usuarios (usuario, password, rol) VALUES (?, ?, "estudiante")');
        $stmt->execute([$data['cedula'], $hash]);
        $usuario_id = $pdo->lastInsertId();

        // Crear Estudiante
        $stmt = $pdo->prepare('INSERT INTO estudiantes (usuario_id, ci, nombres, apellidos) VALUES (?, ?, ?, ?)');
        $stmt->execute([$usuario_id, $data['cedula'], $data['nombres'] ?? 'Estudiante', $data['apellidos'] ?? 'Nuevo']);
        $estudiante_id = $pdo->lastInsertId();
    }

    if (!$estudiante_id) {
        throw new Exception("No se pudo identificar al estudiante. Inicie sesión o complete los campos de seguridad.");
    }

    // 3. ACTUALIZAR DATOS BÁSICOS
    $stmt = $pdo->prepare('
        UPDATE estudiantes SET 
            nombres = ?, 
            apellidos = ?, 
            telefono = ?, 
            fecha_nacimiento = ?, 
            edad = ?, 
            estado_civil = ?, 
            correo = ?, 
            carnet_patria = ?,
            estatus_estudio = ?
        WHERE id = ?
    ');
    $stmt->execute([
        $data['nombres'] ?? '',
        $data['apellidos'] ?? '',
        $data['tel_estudiante'] ?? '',
        $data['f_nac'] ?? null,
        $data['edad'] ?? 0,
        $data['edo_civil'] ?? '',
        $data['correo'] ?? '',
        $data['C_Patria'] ?? '',
        !empty($data['activo']) ? 'activo' : 'inactivo',
        $estudiante_id
    ]);

    //-------------- PNF (INSERT o UPDATE) -------------------------
    if ($pnf_id) {
        $stmt = $pdo->prepare(
            'UPDATE pnfs SET fecha_ingreso=?, trayecto=?, trimestre_actual=?, carrera=?, codigo_estudiante=? WHERE id=?'
        );
        $stmt->execute([
            $data['f_ingreso'] ?? null,
            $data['trayecto'] ?? null,
            $data['trimestre'] ?? null,
            $data['carrera'] ?? null,
            $data['cod_est'] ?? null,
            $pnf_id
        ]);
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO pnfs (fecha_ingreso,trayecto,trimestre_actual,carrera,codigo_estudiante) VALUES (?,?,?,?,?)'
        );
        $stmt->execute([
            $data['f_ingreso'] ?? null,
            $data['trayecto'] ?? null,
            $data['trimestre'] ?? null,
            $data['carrera'] ?? null,
            $data['cod_est'] ?? null,
        ]);
        $pnf_id = $pdo->lastInsertId();
        $pdo->prepare('UPDATE estudiantes SET pnf_id = ? WHERE id = ?')->execute([$pnf_id, $estudiante_id]);
    }

    // Limpieza previa de datos relacionados para evitar duplicados en el UPDATE
    $pdo->prepare('DELETE FROM residencias WHERE estudiante_id = ?')->execute([$estudiante_id]);
    $pdo->prepare('DELETE FROM trabajos WHERE estudiante_id = ?')->execute([$estudiante_id]);
    $pdo->prepare('DELETE FROM records_academicos WHERE estudiante_id = ?')->execute([$estudiante_id]);
    $pdo->prepare('DELETE FROM estudiante_materias WHERE estudiante_id = ?')->execute([$estudiante_id]);
    $pdo->prepare('DELETE FROM familiares WHERE estudiante_id = ?')->execute([$estudiante_id]);
    $pdo->prepare('DELETE FROM estudiante_becas WHERE estudiante_id = ?')->execute([$estudiante_id]);

    // --- ASEGURAR QUE EXISTE AL MENOS UNA BECA (FK CONSTRAINT) ---
    $checkBeca = $pdo->query('SELECT id FROM becas WHERE id = 1')->fetch();
    if (!$checkBeca) {
        // Ignoramos el error si falla por concurrencia o similar, pero intentamos crear la beca por defecto
        try {
            $pdo->prepare('INSERT INTO becas (id, tipo, descripcion) VALUES (1, "Beca General", "Bequita asignada por defecto")')
                ->execute();
        } catch (Exception $e) { /* Ya existe o error menor */ }
    }

    // Registrar solicitud de beca básica
    $pdo->prepare('INSERT INTO estudiante_becas (estudiante_id, beca_id, fecha_solicitud, estado) VALUES (?, 1, NOW(), "Pendiente")')
        ->execute([$estudiante_id]);

    //-------------- RESIDENCIA ------------------
    $stmt = $pdo->prepare(
        'INSERT INTO residencias (numero,tipo_vivienda,tipo_estructura,tipo_localidad,regimen_propiedad,direccion,telefono,monto_bs,estudiante_id) VALUES (?,?,?,?,?,?,?,?,?)'
    );
    $stmt->execute([
        $data['t_res'] ?? null,
        $data['t_viv'] ?? null,
        $data['t_loc'] ?? null,
        $data['t_loc'] ?? null,
        $data['r_prop'] ?? null,
        $data['dir_local'] ?? null,
        $data['tel_local'] ?? null,
        null,
        $estudiante_id,
    ]);

    //------------- TRABAJO ----------------------
    if (!empty($data['trabaja'])) {
        $stmt = $pdo->prepare(
            'INSERT INTO trabajos (estudiante_id,lugar,ingreso,monto_bs,aportador) VALUES (?,?,?,?,?)'
        );
        $stmt->execute([
            $estudiante_id,
            $data['empresa'] ?? null,
            $data['cargo'] ?? null,
            $data['monto_bs'] ?? null,
            $data['aportador'] ?? null,
        ]);
    }

    //----------- RECORD ACADEMICO ----------------
    $stmt = $pdo->prepare(
        'INSERT INTO records_academicos (estudiante_id,codigo_estudiante,n_materias_inscritas,n_materias_aprobadas,n_materias_aplazadas,n_materias_inasistentes,indice_trimestre) VALUES (?,?,?,?,?,?,?)'
    );
    $stmt->execute([
        $estudiante_id,
        $data['cod_est'] ?? null,
        $data['m_ins'] ?? null,
        $data['m_apr'] ?? null,
        $data['m_ina'] ?? null,
        null,
        $data['record_indice'] ?? null,
    ]);

    //----------- MATERIAS -----------------------
    foreach ($data as $k => $v) {
        if (strpos($k, 'mat_') === 0 && $v) {
            $mat = substr($k, 4);
            $nombre = ucwords(str_replace('_', ' ', $mat));

            $stmt = $pdo->prepare('SELECT id FROM materias WHERE nombre = ?');
            $stmt->execute([$nombre]);
            $row = $stmt->fetch();
            if (!$row) {
                $pdo->prepare('INSERT INTO materias (nombre) VALUES (?)')->execute([$nombre]);
                $matId = $pdo->lastInsertId();
            } else {
                $matId = $row['id'];
            }
            $pdo->prepare('INSERT INTO estudiante_materias (estudiante_id,materia_id,trimestre) VALUES (?,?,?)')
                ->execute([$estudiante_id, $matId, $data['trimestre'] ?? null]);
        }
    }

    //----------- FAMILIARES ---------------------
    $fam = [];
    foreach ($data as $k => $v) {
        if (preg_match('/^f_(nom|ape|par|eda|ins|ocu|ing)_(\d+)$/', $k, $m)) {
            $field = $m[1];
            $id = $m[2];
            $fam[$id][$field] = $v;
        }
    }
    foreach ($fam as $f) {
        $pdo->prepare(
            'INSERT INTO familiares (estudiante_id,nombres,apellidos,parentesco,edad,instruccion,ocupacion,ingreso) VALUES (?,?,?,?,?,?,?,?)'
        )->execute([
            $estudiante_id,
            $f['nom'] ?? null,
            $f['ape'] ?? null,
            $f['par'] ?? null,
            $f['eda'] ?? null,
            $f['ins'] ?? null,
            $f['ocu'] ?? null,
            $f['ing'] ?? null,
        ]);
    }

    $pdo->commit();
    echo json_encode(['status' => 'ok']);
    exit;
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['status' => 'error', 'error' => $e->getMessage()]);
    exit;
}
