<?php
session_start();
require 'db.php';
header('Content-Type: application/json');

function generarIdManual() {
    return mt_rand(100000, 99999999);
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;

if (empty($data)) {
    echo json_encode(['status' => 'error', 'error' => 'No se recibieron datos']);
    exit;
}

$estudiante_ci = $data['cedula'] ?? null;
if (!$estudiante_ci) {
    echo json_encode(['status' => 'error', 'error' => 'Cédula requerida']);
    exit;
}

try {
    $pdo->beginTransaction();

    // 1. USUARIO (Tabla: usuarios)
    $stmt = $pdo->prepare('SELECT usuario_id FROM estudiante WHERE ci = ?');
    $stmt->execute([$estudiante_ci]);
    $existe = $stmt->fetch();
    $user_id = $existe['usuario_id'] ?? null;

    if (!$user_id) {
        $user_id = generarIdManual();
        $pass = password_hash($data['password'] ?? $estudiante_ci, PASSWORD_BCRYPT);
        $sql_u = "INSERT INTO usuarios (id, usuario, password, pregunta_seguridad, respuesta_seguridad, rol) VALUES (?, ?, ?, ?, ?, 'estudiante')";
        $pdo->prepare($sql_u)->execute([
            $user_id, 
            $estudiante_ci, 
            $pass, 
            $data['pregunta_seguridad'] ?? null, 
            $data['respuesta_seguridad'] ?? null
        ]);

        $pdo->prepare('INSERT INTO estudiante (ci, usuario_id) VALUES (?, ?)')->execute([$estudiante_ci, $user_id]);
    }

    // 2. ESTUDIANTE (Tabla: estudiante)
    $sql_e = "UPDATE estudiante SET 
        nombre1=?, nombre2=?, apellido_paterno=?, apellido_materno=?, 
        f_nac=?, edad=?, tel_estudiante=?, correo=?, edo_civil=?, 
        tipo_beneficio=?, C_Patria=?, viaja=?, estatus_estudio=?, 
        carrera=?, cod_est=?, f_ingreso=?, trayecto=?, trimestre=?, 
        ira_anterior=?, observaciones=? 
        WHERE ci=?";
    $pdo->prepare($sql_e)->execute([
        $data['nombre1']??'', $data['nombre2']??'', $data['apellido_paterno']??'', $data['apellido_materno']??'',
        $data['f_nac']??null, $data['edad']??0, $data['tel_estudiante']??'', $data['correo']??'', $data['edo_civil']??'',
        $data['tipo_beneficio']??'', $data['C_Patria']??'', $data['viaja']??'no', $data['estatus_estudio']??'activo',
        $data['carrera']??'', $data['cod_est']??'', $data['f_ingreso']??null, $data['trayecto']??'', $data['trimestre']??'',
        $data['ira_anterior']??0.00, $data['observaciones']??($data['comentarios']??''),
        $estudiante_ci
    ]);

    // 3. RESIDENCIA (Tabla: residencia)
    $pdo->prepare("DELETE FROM residencia WHERE ci_estudiante = ?")->execute([$estudiante_ci]);
    $sql_r = "INSERT INTO residencia (ci_estudiante, t_res, t_viv, t_loc, r_prop, estado_res, municipio_res, dir_local, tel_local) VALUES (?,?,?,?,?,?,?,?,?)";
    $pdo->prepare($sql_r)->execute([
        $estudiante_ci, $data['t_res']??'', $data['t_viv']??'', $data['t_loc']??'', $data['r_prop']??'',
        $data['estado_res']??'', $data['municipio_res']??'', $data['dir_local']??'', $data['tel_local']??''
    ]);

    // 4. RECORD ACADÉMICO (Tabla: record_academico)
    $pdo->prepare("DELETE FROM record_academico WHERE ci_estudiante = ?")->execute([$estudiante_ci]);
    $sql_a = "INSERT INTO record_academico (ci_estudiante, indice_trimestre, ira_anterior) VALUES (?, ?, ?)";
    $pdo->prepare($sql_a)->execute([$estudiante_ci, $data['indice_trimestre']??null, $data['ira_anterior']??0.00]);

    // 5. FAMILIARES (Tabla: familiar)
    $pdo->prepare("DELETE FROM familiar WHERE ci_estudiante = ?")->execute([$estudiante_ci]);
    if (!isset($data['no_familiares']) || $data['no_familiares'] !== true) {
        foreach ($data as $key => $val) {
            if (strpos($key, 'f_nom_') === 0 && !empty($val)) {
                $suffix = substr($key, 6);
                $id_fam = generarIdManual();
                $sql_f = "INSERT INTO familiar (id, ci_estudiante, f_nom, f_ape, f_par, f_eda, f_ins, f_ocu, f_ing) VALUES (?,?,?,?,?,?,?,?,?)";
                $pdo->prepare($sql_f)->execute([
                    $id_fam, $estudiante_ci, $val, $data['f_ape_'.$suffix]??'', $data['f_par_'.$suffix]??'',
                    $data['f_eda_'.$suffix]??0, $data['f_ins_'.$suffix]??'', $data['f_ocu_'.$suffix]??'', $data['f_ing_'.$suffix]??0.00
                ]);
            }
        }
    }

    // 6. BITÁCORA
    $sql_b = "INSERT INTO bitacora (usuario_id, accion, tabla_afectada, detalles) VALUES (?, 'Registro Actualizado', 'Múltiples', ?)";
    $pdo->prepare($sql_b)->execute([$user_id, "CI Estudiante: $estudiante_ci"]);

    $pdo->commit();
    echo json_encode(['status' => 'ok', 'message' => '¡Proceso completado exitosamente!']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['status' => 'error', 'error' => $e->getMessage()]);
}