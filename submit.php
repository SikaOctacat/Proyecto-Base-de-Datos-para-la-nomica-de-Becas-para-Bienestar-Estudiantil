<?php
session_start();
require 'base_de_datos/db.php';

header('Content-Type: application/json');

/**
 * Función para generar IDs manuales en tablas donde TiDB no lo hace automáticamente
 * (usuarios y familiar)
 */
function generarIdManual() {
    return mt_rand(100000, 99999999);
}

// 1. RECEPCIÓN DE DATOS
$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: $_POST;

if (empty($data)) {
    echo json_encode(['status' => 'error', 'error' => 'No se recibieron datos en el servidor']);
    exit;
}

$estudiante_ci = $data['cedula'] ?? null;
if (!$estudiante_ci) {
    echo json_encode(['status' => 'error', 'error' => 'La Cédula (CI) es obligatoria']);
    exit;
}

try {
    $pdo->beginTransaction();

    // --- A. GESTIÓN DE USUARIO (ID MANUAL) ---
    $stmt = $pdo->prepare('SELECT usuario_id FROM estudiante WHERE ci = ?');
    $stmt->execute([$estudiante_ci]);
    $user_reg = $stmt->fetch();
    
    $user_id = $user_reg['usuario_id'] ?? null;

    if (!$user_id) {
        $user_id = generarIdManual(); 
        $pass_hash = password_hash($data['password'] ?? $estudiante_ci, PASSWORD_BCRYPT);
        
        $stmt = $pdo->prepare('INSERT INTO usuarios (id, usuario, password, pregunta_seguridad, respuesta_seguridad, rol) VALUES (?, ?, ?, ?, ?, "estudiante")');
        $stmt->execute([
            $user_id, 
            $estudiante_ci, 
            $pass_hash, 
            $data['pregunta_seguridad'] ?? null, 
            $data['respuesta_seguridad'] ?? null
        ]);

        // Insert inicial en estudiante para vincular la PK
        $stmt = $pdo->prepare('INSERT INTO estudiante (ci, usuario_id) VALUES (?, ?)');
        $stmt->execute([$estudiante_ci, $user_id]);
    }

    // --- B. ACTUALIZACIÓN DEL ESTUDIANTE ---
    $carnet = !empty($data['C_Patria']) ? $data['C_Patria'] : "N/A";
    
    // Cambiamos 'observaciones' por 'comentarios' para capturar el valor real del formulario
    $observacionesRaw = isset($data['comentarios']) ? trim($data['comentarios']) : '';
    $obs = ($observacionesRaw !== '') ? $observacionesRaw : "Sin observaciones adicionales.";

    $sql_est = "UPDATE estudiante SET 
                nombre1=?, nombre2=?, apellido_paterno=?, apellido_materno=?, 
                f_nac=?, edad=?, tel_estudiante=?, correo=?, edo_civil=?, 
                tipo_beneficio=?, C_Patria=?, viaja=?, estatus_estudio=?, 
                carrera=?, cod_est=?, f_ingreso=?, trayecto=?, trimestre=?, 
                ira_anterior=?, observaciones=? WHERE ci=?";
    
    $pdo->prepare($sql_est)->execute([
        $data['nombre1'] ?? null, 
        $data['nombre2'] ?? null, 
        $data['apellido_paterno'] ?? null, 
        $data['apellido_materno'] ?? null,
        $data['f_nac'] ?? null, 
        (int)($data['edad'] ?? 0), 
        $data['tel_estudiante'] ?? null, 
        $data['correo'] ?? null, 
        $data['edo_civil'] ?? null,
        $data['tipo_beneficio'] ?? null, 
        $carnet, 
        $data['viaja'] ?? 'no', 
        $data['estatus_estudio'] ?? 'activo',
        $data['carrera'] ?? null, 
        $data['cod_est'] ?? null, 
        $data['f_ingreso'] ?? null, 
        $data['trayecto'] ?? null, 
        $data['trimestre'] ?? null,
        $data['ira_anterior'] ?? 0.00, 
        $obs, 
        $estudiante_ci
    ]);

    // --- C. RESIDENCIA (INCLUYENDO DIRECCIÓN DE PROCEDENCIA) ---
    $tel_local = !empty($data['tel_local']) ? $data['tel_local'] : "No posee";

    $pdo->prepare("DELETE FROM residencia WHERE ci_estudiante = ?")->execute([$estudiante_ci]);
    
    // Se añade dir_procedencia a la consulta
    $sql_res = "INSERT INTO residencia (ci_estudiante, t_res, t_viv, t_loc, r_prop, estado_res, municipio_res, dir_local, dir_procedencia, tel_local) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $pdo->prepare($sql_res)->execute([
        $estudiante_ci, 
        $data['t_res'] ?? null, 
        $data['t_viv'] ?? null, 
        $data['t_loc'] ?? null, 
        $data['r_prop'] ?? null, 
        $data['estado_res'] ?? null, 
        $data['municipio_res'] ?? null, 
        $data['dir_local'] ?? null, 
        $data['dir_procedencia'] ?? null, // Nuevo parámetro
        $tel_local
    ]);

    // --- D. FAMILIARES (CON ATRIBUTO DE CLASIFICACIÓN DINÁMICA CORREGIDO) ---
    $pdo->prepare("DELETE FROM familiar WHERE ci_estudiante = ?")->execute([$estudiante_ci]);
    
    $estructuraTable = $data['estructura_tabla'] ?? null;

    if (!empty($estructuraTable) && is_array($estructuraTable)) {
        // Inicializamos en 'otros' por prevención si hay filas antes del primer separador
        $grupoClasificacionActual = 'otros'; 

        foreach ($estructuraTable as $item) {
            $partes = explode(':', $item);
            if (count($partes) !== 2) continue;

            list($tipo, $identificador) = $partes;

            if ($tipo === 'separador') {
                // Captura: primaria, secundaria o otros
                $grupoClasificacionActual = !empty($identificador) ? $identificador : 'otros'; 
            } 
            elseif ($tipo === 'fila') {
                $id = $identificador;
                $nombreFamiliar = $data['f_nom_' . $id] ?? null;

                if (!empty($nombreFamiliar)) {
                    $stmt = $pdo->prepare('INSERT INTO familiar (id, ci_estudiante, f_nom, f_ape, f_par, f_eda, f_ins, f_ocu, f_ing, f_clasificacion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                    $stmt->execute([
                        generarIdManual(), 
                        $estudiante_ci, 
                        $nombreFamiliar, 
                        $data['f_ape_' . $id] ?? null, 
                        $data['f_par_' . $id] ?? null, 
                        (int)($data['f_eda_' . $id] ?? 0), 
                        $data['f_ins_' . $id] ?? null, 
                        $data['f_ocu_' . $id] ?? null, 
                        (float)($data['f_ing_' . $id] ?? 0.00),
                        $grupoClasificacionActual // Guarda de forma segura la clasificación actual
                    ]);
                }
            }
        }
    } else {
        // Fallback preventivo: Si no viene el mapa estructurado, deduce inteligentemente en vez de clavar NULL
        foreach ($data as $key => $val) {
            if (strpos($key, 'f_nom_') === 0 && !empty($val)) {
                $suffix = substr($key, 6);
                $parentesco = strtolower(trim($data['f_par_'.$suffix] ?? ''));
                
                // Deducción básica basada en palabras clave comunes
                if (in_array($parentesco, ['madre', 'padre', 'mamá', 'papá', 'hermano', 'hermana', 'hijo', 'hija'])) {
                    $clasificacionDeducida = 'primaria';
                } elseif (in_array($parentesco, ['abuelo', 'abuela', 'tío', 'tía', 'primo', 'prima', 'sobrino', 'sobrina'])) {
                    $clasificacionDeducida = 'secundaria';
                } else {
                    $clasificacionDeducida = 'otros';
                }

                $stmt = $pdo->prepare('INSERT INTO familiar (id, ci_estudiante, f_nom, f_ape, f_par, f_eda, f_ins, f_ocu, f_ing, f_clasificacion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
                $stmt->execute([
                    generarIdManual(), 
                    $estudiante_ci, 
                    $val, 
                    $data['f_ape_'.$suffix] ?? null, 
                    $data['f_par_'.$suffix] ?? null, 
                    (int)($data['f_eda_'.$suffix] ?? 0), 
                    $data['f_ins_'.$suffix] ?? null, 
                    $data['f_ocu_'.$suffix] ?? null, 
                    (float)($data['f_ing_'.$suffix] ?? 0.00),
                    $clasificacionDeducida // Inserción segura de clasificación deducida
                ]);
            }
        }
    }

    // --- E. BITÁCORA (ID AUTOMÁTICO) ---
    $sql_bit = "INSERT INTO bitacora (usuario_id, accion, tabla_afectada, detalles) VALUES (?, ?, ?, ?)";
    $pdo->prepare($sql_bit)->execute([
        $user_id, 
        "Registro/Actualización Completa", 
        "Múltiples", 
        "CI: $estudiante_ci - IP: " . $_SERVER['REMOTE_ADDR']
    ]);

    $pdo->commit();
    echo json_encode(['status' => 'ok', 'message' => 'Datos procesados correctamente']);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("Error en submit.php: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'error' => $e->getMessage()]);
}