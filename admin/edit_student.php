<?php
ob_start();
session_start();
require '../db.php'; 

// Verificación de sesión y rol
if (!isset($_SESSION['user']) || (isset($_SESSION['rol']) && $_SESSION['rol'] !== 'admin')) {
    header('Location: ../login.php');
    exit;
}

$ci = $_GET['id'] ?? null;
if (!$ci) { header('Location: index.php'); exit; }

/**
 * Función para generar IDs manuales en tablas donde TiDB no lo hace automáticamente
 */
function generarIdManual() {
    return mt_rand(100000, 99999999);
}

// --- LÓGICA DE GUARDADO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_all'])) {
    try {
        $pdo->beginTransaction();
        
        // 1. Preparación de datos (Observaciones e IRA)
        $obs = !empty($_POST['observaciones']) ? $_POST['observaciones'] : "Sin observaciones adicionales.";
        $ira = !empty($_POST['indice']) ? $_POST['indice'] : 0.00;

        // 2. Actualización de Estudiante (Incluye todos los campos e IRA/Observaciones)
        $stmt = $pdo->prepare('UPDATE estudiante SET 
            nombre1=?, nombre2=?, apellido_paterno=?, apellido_materno=?, 
            f_nac=?, carrera=?, trayecto=?, trimestre=?, cod_est=?, 
            tel_estudiante=?, correo=?, edo_civil=?, C_Patria=?, 
            viaja=?, estatus_estudio=?, observaciones=?, f_ingreso=?, 
            ira_anterior=? 
            WHERE ci=?');
        
        $stmt->execute([
            $_POST['nombre1'], $_POST['nombre2'], $_POST['apellido_paterno'], $_POST['apellido_materno'],
            $_POST['f_nac'], $_POST['carrera'], $_POST['trayecto'], $_POST['trimestre'], $_POST['codigo_estudiante'],
            $_POST['telefono'], $_POST['correo'], $_POST['estado_civil'], $_POST['carnet_patria'],
            $_POST['viaja'], $_POST['estatus_estudio'], $obs, $_POST['f_ingreso'], 
            $ira, $ci
        ]);
        
        // 3. Actualización de Residencia (Se añade dir_procedencia)
        $pdo->prepare('UPDATE residencia SET 
            t_res=?, t_viv=?, t_loc=?, r_prop=?, estado_res=?, municipio_res=?, dir_local=?, dir_procedencia=?, tel_local=? 
            WHERE ci_estudiante=?')
            ->execute([
                $_POST['t_res'], $_POST['t_viv'], $_POST['t_loc'], $_POST['r_prop'], 
                $_POST['estado_res'], $_POST['municipio_res'], $_POST['direccion_res'], 
                $_POST['dir_procedencia'] ?? null, $_POST['telefono_res'], $ci
            ]);
        
        // 4. Familiares (Estructura Dinámica idéntica a la vista del Estudiante)
        $pdo->prepare('DELETE FROM familiar WHERE ci_estudiante = ?')->execute([$ci]);
        
        $estructuraTable = $_POST['estructura_tabla'] ?? null;

        if (!empty($estructuraTable) && is_array($estructuraTable)) {
            $grupoClasificacionActual = 'otros'; // Por defecto

            $stmt_fam = $pdo->prepare('INSERT INTO familiar (id, ci_estudiante, f_nom, f_ape, f_par, f_eda, f_ins, f_ocu, f_ing, f_clasificacion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');

            foreach ($estructuraTable as $item) {
                $partes = explode(':', $item);
                if (count($partes) !== 2) continue;

                list($tipo, $identificador) = $partes;

                if ($tipo === 'separador') {
                    $grupoClasificacionActual = $identificador; 
                } 
                elseif ($tipo === 'fila') {
                    $id = $identificador;
                    $nombreFamiliar = $_POST['f_nom_' . $id] ?? null;

                    if (!empty(trim($nombreFamiliar))) {
                        $stmt_fam->execute([
                            generarIdManual(), 
                            $ci, 
                            trim($nombreFamiliar), 
                            $_POST['f_ape_' . $id] ?? '', 
                            $_POST['f_par_' . $id] ?? '', 
                            (int)($_POST['f_eda_' . $id] ?? 0), 
                            $_POST['f_ins_' . $id] ?? '', 
                            $_POST['f_ocu_' . $id] ?? '', 
                            (float)($_POST['f_ing_' . $id] ?? 0.00),
                            $grupoClasificacionActual
                        ]);
                    }
                }
            }
        }

        // 5. Lógica de Seguridad (Actualización de credenciales de acceso)
        $pregunta = $_POST['pregunta_seguridad'] ?? '';
        $respuesta = $_POST['respuesta_seguridad'] ?? '';
        
        if (!empty($_POST['reg_password'])) {
            // Si el administrador cambió la contraseña, se actualiza todo (incluyendo clave)
            $pass_hash = password_hash($_POST['reg_password'], PASSWORD_BCRYPT);
            $stmt_user = $pdo->prepare('UPDATE usuarios SET password = ?, pregunta_seguridad = ?, respuesta_seguridad = ? WHERE usuario = ?');
            $stmt_user->execute([$pass_hash, $pregunta, $respuesta, $ci]);
        } elseif (!empty($pregunta)) {
            // Si no cambió la contraseña pero sí eligió una pregunta de seguridad
            $stmt_user = $pdo->prepare('UPDATE usuarios SET pregunta_seguridad = ?, respuesta_seguridad = ? WHERE usuario = ?');
            $stmt_user->execute([$pregunta, $respuesta, $ci]);
        }
        
        // 6. Registro en Bitácora (Omitimos 'id' para que TiDB use AUTO_RANDOM)
        $admin_id = $_SESSION['user_id'] ?? null; 
        if ($admin_id) {
            $detalles = "Se editó el expediente del estudiante C.I. $ci. Se actualizaron datos personales, familiares (con clasificación) y dirección de procedencia.";
            $stmt_bit = $pdo->prepare('INSERT INTO bitacora (usuario_id, accion, tabla_afectada, detalles) VALUES (?, ?, ?, ?)');
            $stmt_bit->execute([$admin_id, 'Actualización de Expediente', 'Múltiples Tablas', $detalles]);
        }

        $pdo->commit();
        header("Location: index.php?msg=success"); 
        exit;

    } catch (Exception $e) { 
        if ($pdo->inTransaction()) $pdo->rollBack(); 
        $error = "Error: " . $e->getMessage(); 
    }
}

// --- CARGA DE DATOS PARA EL FORMULARIO ---
// Se añade r.dir_procedencia a la consulta de selección inicial
$stmt = $pdo->prepare('SELECT e.*, r.t_res, r.t_viv, r.t_loc, r.r_prop, r.estado_res, r.municipio_res, r.dir_local, r.dir_procedencia, r.tel_local 
                       FROM estudiante e 
                       LEFT JOIN residencia r ON e.ci = r.ci_estudiante 
                       WHERE e.ci = ?');
$stmt->execute([$ci]);
$std = $stmt->fetch();

// Mapeamos para compatibilidad con tus campos del HTML
if ($std) {
    $std['ra_indice'] = $std['ira_anterior'];
}

$fams = $pdo->prepare('SELECT * FROM familiar WHERE ci_estudiante = ?');
$fams->execute([$ci]);
$lista_familiares = $fams->fetchAll();

// Fechas para restricciones del input date
$fecha_hoy = new DateTime();
$max_date = (clone $fecha_hoy)->modify('-5 years')->format('Y-m-d');
$min_date = (clone $fecha_hoy)->modify('-50 years')->format('Y-m-d');
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Expediente - <?php echo htmlspecialchars($std['nombre1']); ?></title>
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="TablaDinamica.js"></script>
    <style>
        .seccion-titulo { grid-column: 1 / -1; border-bottom: 2px solid rgba(255,102,0,0.2); padding-bottom: 5px; margin-top: 25px; color: #FF6600; font-size: 1.1rem; font-weight: 700; }
        .opcional-hint { font-size: 0.75rem; color: #888; font-weight: normal; margin-left: 5px; }
        .table-responsive { overflow-x: auto; margin-top: 10px; background: rgba(255,255,255,0.5); padding: 10px; border-radius: 8px; }
        table { border-collapse: collapse; width: 100%; min-width: 800px; }
        .readonly-input { background-color: #e9ecef !important; color: #6c757d !important; cursor: not-allowed; user-select: none; }
        .radio-group { display: flex; gap: 20px; align-items: center; padding: 10px; background: #fff; border-radius: 8px; border: 1px solid #ddd; }
        
        /* Estilos específicos para el toggle de contraseña */
        .password-wrapper {
            position: relative;
            width: 100%;
        }
        .btn-view-pass {
            position: absolute;
            right: 12px;
            top: 32px; /* Ajustado para que quede sobre el input bajo el label */
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            padding: 0;
            font-size: 1.1rem;
            transition: color 0.3s;
        }
        .btn-view-pass:hover {
            color: #FF6600 !important;
        }
        /* Estilo para los inputs de seguridad */
        .grid-container input[type="password"], 
        .grid-container input[type="text"] {
            width: 100%;
        }
    </style>
</head>
<body>

<div id="main-container">
    <div class="page-header">
        <h2>Editar Expediente Estudiantil</h2>
        <a href="index.php">← Volver al Listado</a>
    </div>

    <?php if(isset($error)): ?> <div class="alert error"><?php echo $error; ?></div> <?php endif; ?>

    <form method="POST" id="editForm">
        <div class="grid-container">
            <div class="seccion-titulo">👤 Datos Personales</div>
            
            <div>
                <label>Cédula</label>
                <input type="text" value="<?php echo $ci; ?>" disabled class="readonly-input" required>
            </div>
            <div>
                <label>Primer Nombre</label>
                <input type="text" name="nombre1" value="<?php echo htmlspecialchars($std['nombre1']); ?>" required>
            </div>
            <div>
                <label>Segundo Nombre</label>
                <input type="text" name="nombre2" value="<?php echo htmlspecialchars($std['nombre2']); ?>">
            </div>
            <div>
                <label>Primer Apellido</label>
                <input type="text" name="apellido_paterno" value="<?php echo htmlspecialchars($std['apellido_paterno']); ?>" required>
            </div>
            <div>
                <label>Segundo Apellido</label>
                <input type="text" name="apellido_materno" value="<?php echo htmlspecialchars($std['apellido_materno']); ?>">
            </div>
            <div>
                <label>Fecha de Nacimiento</label>
                <input type="date" name="f_nac" id="f_nac" value="<?php echo $std['f_nac']; ?>" min="<?php echo $min_date; ?>" max="<?php echo $max_date; ?>" required>
            </div>
            <div>
                <label>Edad</label>
                <input type="number" id="edad" value="<?php echo $std['edad']; ?>" readonly class="readonly-input" required>
            </div>
            <div>
                <label>Teléfono</label>
                <input type="text" name="telefono" value="<?php echo htmlspecialchars($std['tel_estudiante']); ?>" required>
            </div>
            <div>
                <label>Correo Electrónico</label>
                <input type="email" name="correo" value="<?php echo htmlspecialchars($std['correo']); ?>" required>
            </div>
            <div>
                <label>Estado Civil</label>
                <select name="estado_civil" required>
                    <option value="soltero" <?php if($std['edo_civil']=='soltero') echo 'selected'; ?>>Soltero/a</option>
                    <option value="casado" <?php if($std['edo_civil']=='casado') echo 'selected'; ?>>Casado/a</option>
                    <option value="divorciado" <?php if($std['edo_civil']=='divorciado') echo 'selected'; ?>>Divorciado/a</option>
                    <option value="viudo" <?php if($std['edo_civil']=='viudo') echo 'selected'; ?>>Viudo/a</option>
                </select>
            </div>

            <div class="seccion-titulo">🔐 Seguridad de la Cuenta <span class="opcional-hint">(Llenar solo si desea cambiar la contraseña)</span></div>

            <div class="password-wrapper">
                <label>Nueva Contraseña</label>
                <input type="password" name="reg_password" id="reg_password" placeholder="Mínimo 4 caracteres">
                <button type="button" class="btn-view-pass" onclick="togglePassword('reg_password', this)">
                    <i class="fas fa-eye"></i>
                </button>
            </div>

            <div class="password-wrapper">
                <label>Confirmar Contraseña</label>
                <input type="password" id="reg_password_confirm" placeholder="Repita la nueva contraseña">
                <button type="button" class="btn-view-pass" onclick="togglePassword('reg_password_confirm', this)">
                    <i class="fas fa-eye"></i>
                </button>
            </div>

            <div>
                <label>Pregunta de Seguridad</label>
                <?php
                // Consultamos la pregunta guardada actualmente en la tabla usuarios
                $stmt_p = $pdo->prepare("SELECT pregunta_seguridad FROM usuarios WHERE usuario = ?");
                $stmt_p->execute([$ci]);
                $preg_actual = $stmt_p->fetchColumn() ?: '';
                ?>
                <select name="pregunta_seguridad" id="pregunta_seguridad" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd;">
                    <option value="" <?php echo empty($preg_actual) ? 'selected' : ''; ?> disabled>Seleccione una pregunta...</option>
                    <option value="Nombre de tu primera mascota" <?php echo ($preg_actual == "Nombre de tu primera mascota") ? 'selected' : ''; ?>>¿Nombre de tu primera mascota?</option>
                    <option value="Ciudad donde naciste" <?php echo ($preg_actual == "Ciudad donde naciste") ? 'selected' : ''; ?>>¿Ciudad donde naciste?</option>
                    <option value="Nombre de tu escuela primaria" <?php echo ($preg_actual == "Nombre de tu escuela primaria") ? 'selected' : ''; ?>>¿Nombre de tu escuela primaria?</option>
                    <option value="Personaje de ficción favorito" <?php echo ($preg_actual == "Personaje de ficción favorito") ? 'selected' : ''; ?>>¿Personaje de ficción favorito?</option>
                </select>
            </div>

            <div>
                <label>Respuesta de Seguridad</label>
                <input type="text" name="respuesta_seguridad" id="respuesta_seguridad" placeholder="Nueva respuesta secreta">
            </div>

            <div class="seccion-titulo">🏠 Residencia</div>

            <div>
                <label>Tipo de residencia</label>
                <select name="t_res" id="t_res" required>
                    <?php $tr = $std['t_res']; ?>
                    <option value="familiar" <?php if($tr=='familiar') echo 'selected'; ?>>Familiar</option>
                    <option value="particular" <?php if($tr=='particular') echo 'selected'; ?>>Particular</option>
                    <option value="universitaria" <?php if($tr=='universitaria') echo 'selected'; ?>>Universitaria</option>
                    <option value="otro" <?php if($tr=='otro') echo 'selected'; ?>>Otro...</option>
                </select>
            </div>

            <div>
                <label>Tipo de vivienda</label>
                <select name="t_viv" id="t_viv" required>
                    <?php $tv = $std['t_viv']; ?>
                    <option value="casa" <?php if($tv=='casa') echo 'selected'; ?>>Casa</option>
                    <option value="apartamento" <?php if($tv=='apartamento') echo 'selected'; ?>>Apartamento</option>
                    <option value="vivienda_social" <?php if($tv=='vivienda_social') echo 'selected'; ?>>Vivienda de interés social</option>
                    <option value="habitacion" <?php if($tv=='habitacion') echo 'selected'; ?>>Habitación</option>
                    <option value="otro" <?php if($tv=='otro') echo 'selected'; ?>>Otro</option>
                </select>
            </div>

            <div>
                <label>Localidad</label> 
                <select name="t_loc" id="t_loc" required>
                    <option value="urbano" <?php if($std['t_loc']=='urbano') echo 'selected'; ?>>Urbano</option>
                    <option value="rural" <?php if($std['t_loc']=='rural') echo 'selected'; ?>>Rural</option>
                </select>
            </div>

            <div>
                <label>Régimen de propiedad</label>
                <select name="r_prop" id="r_prop" required>
                    <?php $rp = $std['r_prop']; ?>
                    <option value="propia" <?php if($rp=='propia') echo 'selected'; ?>>Propia</option>
                    <option value="alquilada" <?php if($rp=='alquilada') echo 'selected'; ?>>Alquilada</option>
                    <option value="cedida" <?php if($rp=='cedida') echo 'selected'; ?>>Cedida</option>
                    <option value="comodato" <?php if($rp=='comodato') echo 'selected'; ?>>Comodato</option>
                    <option value="pagandola" <?php if($rp=='pagandola') echo 'selected'; ?>>Pagándola (Crédito)</option> 
                </select>
            </div>

            <div>
                <label>Estado</label>
                <select name="estado_res" id="estado_res" required>
                    <option value="<?php echo $std['estado_res']; ?>" selected><?php echo $std['estado_res']; ?></option>
                </select>
            </div>

            <div>
                <label>Municipio</label>
                <select name="municipio_res" id="municipio_res" required>
                    <option value="<?php echo $std['municipio_res']; ?>" selected><?php echo $std['municipio_res']; ?></option>
                </select>
            </div>

            <div>
                <label>Teléfono Local</label>
                <input type="text" name="telefono_res" value="<?php echo htmlspecialchars($std['tel_local']); ?>" maxlength="11" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
            </div>

            <div style="grid-column: span 2;">
                <label>Dirección Local Exacta</label>
                <input type="text" name="direccion_res" value="<?php echo htmlspecialchars($std['dir_local']); ?>" required>
            </div>

            <div style="grid-column: span 2;">
                <label>Dirección de Procedencia</label>
                <input type="text" name="dir_procedencia" value="<?php echo htmlspecialchars($std['dir_procedencia'] ?? ''); ?>" placeholder="Ej: Calle Principal Sector Centro, Coro, Falcón">
            </div>

            <div class="seccion-titulo">🎓 Información Académica</div>

            <div>
                <label>Carrera (PNF)</label>
                <select name="carrera" id="carreraSelect" required>
                    <option value="<?php echo $std['carrera']; ?>" selected><?php echo htmlspecialchars($std['carrera']); ?></option>
                </select>
            </div>

            <div>
                <label>Fecha Ingreso a la UPTAG</label>
                <input type="date" name="f_ingreso" id="f_ingreso" value="<?php echo $std['f_ingreso']; ?>" required>
            </div>

            <div>
                <label>Trayecto actual</label>
                <select name="trayecto" id="trayectoSelect" required>
                    <option value="inicial" <?php if($std['trayecto']=='inicial') echo 'selected'; ?>>Trayecto Inicial</option>
                    <option value="1" <?php if($std['trayecto']=='1') echo 'selected'; ?>>Trayecto I</option>
                    <option value="2" <?php if($std['trayecto']=='2') echo 'selected'; ?>>Trayecto II</option>
                    <option value="3" <?php if($std['trayecto']=='3') echo 'selected'; ?>>Trayecto III</option>
                    <option value="4" <?php if($std['trayecto']=='4') echo 'selected'; ?>>Trayecto IV</option>
                </select>
            </div>

            <div>
                <label>Trimestre actual</label>
                <select name="trimestre" id="trimestreSelect" required>
                    <option value="1" <?php if($std['trimestre']=='1') echo 'selected'; ?>>1er Trimestre</option>
                    <option value="2" <?php if($std['trimestre']=='2') echo 'selected'; ?>>2do Trimestre</option>
                    <option value="3" <?php if($std['trimestre']=='3') echo 'selected'; ?>>3er Trimestre</option>
                </select>
            </div>

            <div>
                <label>Índice Académico (IRA)</label>
                <div style="display: flex; align-items: center; gap: 5px;">
                    <input type="number" name="indice" id="ira_anterior" step="0.01" value="<?php echo $std['ra_indice']; ?>" required style="width: 100%;">
                    <span style="font-weight: bold; color: #666;">/20</span>
                </div>
            </div>

            <div>
                <label>Código Estudiante</label>
                <input type="text" name="codigo_estudiante" value="<?php echo htmlspecialchars($std['cod_est']); ?>" required maxlength="10">
            </div>

            
            <div class="seccion-titulo">
                <div style="margin-bottom: 15px;">
                    <span style="display: block; font-size: 1.1rem; font-weight: bold; color: #2d3748; margin-bottom: 12px;">
                        👨‍👩‍👧‍👦 Carga Familiar Regente
                    </span>
                    
                    <div style="background: rgba(255,102,0,0.03); padding: 12px; border-radius: 8px; margin-bottom: 15px; border: 1px dashed #FF6600;">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-weight: 600; color: #444;">
                            <input type="checkbox" id="no-familiares" name="no_familiares" style="width: 18px; height: 18px;">
                            <span>El estudiante no convive con familiares / Vive solo</span>
                        </label>
                    </div>

                    <div id="aviso-estado" style="margin-bottom: 10px; font-weight: 600; font-size: 0.85rem;"></div>

                    <div class="table-responsive" style="border: 1px solid #ddd; border-radius: 8px; background: white; padding: 5px; overflow-x: auto; margin-bottom: 15px;">
                        <table style="width: 100%; border-collapse: collapse; min-width: 800px;">
                            <thead>
                                <tr style="background: #f8f9fa;">
                                    <th style="padding: 10px; border-bottom: 2px solid #FF6600; text-align: left; font-size:0.9rem;">Nombre</th>
                                    <th style="padding: 10px; border-bottom: 2px solid #FF6600; text-align: left; font-size:0.9rem;">Apellido</th>
                                    <th style="padding: 10px; border-bottom: 2px solid #FF6600; text-align: left; font-size:0.9rem;">Vínculo</th>
                                    <th style="padding: 10px; border-bottom: 2px solid #FF6600; text-align: left; font-size:0.9rem; width: 80px;">Edad</th>
                                    <th style="padding: 10px; border-bottom: 2px solid #FF6600; text-align: left; font-size:0.9rem;">Instrucción</th>
                                    <th style="padding: 10px; border-bottom: 2px solid #FF6600; text-align: left; font-size:0.9rem;">Ocupación</th>
                                    <th style="padding: 10px; border-bottom: 2px solid #FF6600; text-align: left; font-size:0.9rem; width: 120px;">Ingreso</th>
                                    <th style="padding: 10px; border-bottom: 2px solid #FF6600; width: 50px;"></th>
                                </tr>
                            </thead>
                            <tbody id="cuerpo-tabla">
                                <?php 
                                if (!empty($lista_familiares)) {
                                    // Ordenar de forma que respete la prioridad posicional visual
                                    usort($lista_familiares, function($a, $b) {
                                        $p = ['primaria' => 1, 'secundaria' => 2, 'otros' => 3];
                                        return ($p[$a['f_clasificacion']??'otros']??3) <=> ($p[$b['f_clasificacion']??'otros']??3);
                                    });

                                    $grupo_actual = null;
                                    $titulos = ['primaria' => '👨‍👩‍👧‍👦 Familia Primaria', 'secundaria' => '🏡 Carga Secundaria', 'otros' => '🔗 Otros Parientes'];
                                    $colores = [
                                        'primaria' => ['bg'=>'#c6f6d5','txt'=>'#22543d','fila'=>'#f0fff4'],
                                        'secundaria' => ['bg'=>'#feebc8','txt'=>'#744210','fila'=>'#fffaf0'],
                                        'otros' => ['bg'=>'#edf2f7','txt'=>'#2d3748','fila'=>'#f7fafc']
                                    ];

                                    foreach ($lista_familiares as $k => $f) {
                                        $clasif = !empty($f['f_clasificacion']) ? $f['f_clasificacion'] : 'otros';
                                        
                                        if ($grupo_actual !== $clasif) {
                                            $grupo_actual = $clasif;
                                            echo "<tr class='fila-separador' data-grupo='{$grupo_actual}' style='background:{$colores[$grupo_actual]['bg']}; color:{$colores[$grupo_actual]['txt']}; font-weight:bold;'>";
                                            echo "  <td colspan='7' style='padding:10px 12px;'>{$titulos[$grupo_actual]}<input type='hidden' name='estructura_tabla[]' value='separador:{$grupo_actual}'></td>";
                                            echo "  <td style='text-align:center;'><button type='button' class='btn-remove-separador' data-valor='{$grupo_actual}' title='Remover Clasificación' style='background:#fff; border:1px solid #cbd5e0; color:#e53e3e; font-size:1.1rem; font-weight:bold; width:28px; height:28px; border-radius:50%; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; transition: all 0.2s;'>&times;</button></td>";
                                            echo "</tr>";
                                        }

                                        $id_f = $f['id'] ?? $k;
                                        ?>
                                        <tr class="fila-datos" data-id="<?php echo $id_f; ?>" style="background: <?php echo $colores[$grupo_actual]['fila']; ?>;">
                                            <input type="hidden" name="estructura_tabla[]" value="fila:<?php echo $id_f; ?>">
                                            <td><input type="text" name="f_nom_<?php echo $id_f; ?>" value="<?php echo htmlspecialchars($f['f_nom']); ?>" required style="width:100%; padding:5px; border:1px solid #ccc; border-radius:4px;"></td>
                                            <td><input type="text" name="f_ape_<?php echo $id_f; ?>" value="<?php echo htmlspecialchars($f['f_ape']); ?>" required style="width:100%; padding:5px; border:1px solid #ccc; border-radius:4px;"></td>
                                            <td><input type="text" name="f_par_<?php echo $id_f; ?>" value="<?php echo htmlspecialchars($f['f_par']); ?>" required style="width:100%; padding:5px; border:1px solid #ccc; border-radius:4px;"></td>
                                            <td><input type="number" name="f_eda_<?php echo $id_f; ?>" value="<?php echo $f['f_eda']; ?>" min="0" max="120" required style="width:100%; padding:5px; border:1px solid #ccc; border-radius:4px;"></td>
                                            <td><input type="text" name="f_ins_<?php echo $id_f; ?>" value="<?php echo htmlspecialchars($f['f_ins']); ?>" style="width:100%; padding:5px; border:1px solid #ccc; border-radius:4px;"></td>
                                            <td><input type="text" name="f_ocu_<?php echo $id_f; ?>" value="<?php echo htmlspecialchars($f['f_ocu']); ?>" style="width:100%; padding:5px; border:1px solid #ccc; border-radius:4px;"></td>
                                            <td><input type="number" name="f_ing_<?php echo $id_f; ?>" value="<?php echo $f['f_ing']; ?>" step="0.01" min="0" style="width:100%; padding:5px; border:1px solid #ccc; border-radius:4px;"></td>
                                            <td style="text-align:center;"><button type="button" class="btn-remove" title="Remover fila" style="background:#fff; border:1px solid #cbd5e0; color:#e53e3e; font-size:1.1rem; font-weight:bold; width:28px; height:28px; border-radius:50%; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; transition: all 0.2s;">&times;</button></td>
                                        </tr>
                                        <?php
                                    }
                                } else { ?>
                                    <tr class="fila-separador" data-grupo="primaria" style="background: #c6f6d5; color: #22543d; font-weight: bold;">
                                        <td colspan="7" style="padding: 10px 12px;">👨‍👩‍👧‍👦 Familia Primaria<input type="hidden" name="estructura_tabla[]" value="separador:primaria"></td>
                                        <td style="text-align:center;"><button type="button" class="btn-remove-separador" data-valor="primaria" style="background:#fff; border:1px solid #cbd5e0; color:#e53e3e; font-size:1.1rem; font-weight:bold; width:28px; height:28px; border-radius:50%; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; transition: all 0.2s;">&times;</button></td>
                                    </tr>
                                    <tr class="fila-datos" data-id="101" style="background: #f0fff4;">
                                        <input type="hidden" name="estructura_tabla[]" value="fila:101">
                                        <td><input type="text" name="f_nom_101" required style="width:100%; padding:5px; border:1px solid #ccc; border-radius:4px;"></td>
                                        <td><input type="text" name="f_ape_101" required style="width:100%; padding:5px; border:1px solid #ccc; border-radius:4px;"></td>
                                        <td><input type="text" name="f_par_101" required style="width:100%; padding:5px; border:1px solid #ccc; border-radius:4px;"></td>
                                        <td><input type="number" name="f_eda_101" min="0" max="120" required style="width:100%; padding:5px; border:1px solid #ccc; border-radius:4px;"></td>
                                        <td><input type="text" name="f_ins_101" style="width:100%; padding:5px; border:1px solid #ccc; border-radius:4px;"></td>
                                        <td><input type="text" name="f_ocu_101" style="width:100%; padding:5px; border:1px solid #ccc; border-radius:4px;"></td>
                                        <td><input type="number" name="f_ing_101" step="0.01" min="0" value="0.00" style="width:100%; padding:5px; border:1px solid #ccc; border-radius:4px;"></td>
                                        <td style="text-align:center;"><button type="button" class="btn-remove" style="background:#fff; border:1px solid #cbd5e0; color:#e53e3e; font-size:1.1rem; font-weight:bold; width:28px; height:28px; border-radius:50%; cursor:pointer; display:inline-flex; align-items:center; justify-content:center; transition: all 0.2s;">&times;</button></td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>

                    <div style="display: flex; justify-content: flex-end; align-items: center; gap: 10px;">
                        <div style="position: relative; display: inline-block;">
                            <button type="button" id="btn-dropdown-grupo" class="btn" style="background: #edf2f7; color: #2d3748; font-weight: 600; padding: 8px 12px; border: 1px solid #cbd5e0; border-radius: 6px; cursor: pointer;">
                                ➕ Agregar Clasificación ▾
                            </button>
                            <div id="menu-dropdown-grupo" style="display: none; position: absolute; right: 0; bottom: 110%; background: white; min-width: 200px; border: 1px solid #cbd5e0; border-radius: 6px; box-shadow: 0 -4px 12px rgba(0,0,0,0.1); z-index: 100; padding: 6px 0; margin-bottom: 5px;">
                                <a href="#" data-valor="primaria" style="display: block; padding: 10px 14px; color: #2d3748; text-decoration: none; font-size: 0.85rem; font-weight: 600; border-left: 4px solid #48bb78;">👨‍👩‍👧‍👦 Familia Primaria</a>
                                <a href="#" data-valor="secundaria" style="display: block; padding: 10px 14px; color: #2d3748; text-decoration: none; font-size: 0.85rem; font-weight: 600; border-left: 4px solid #ed8936;">🏡 Carga Secundaria</a>
                                <a href="#" data-valor="otros" style="display: block; padding: 10px 14px; color: #2d3748; text-decoration: none; font-size: 0.85rem; font-weight: 600; border-left: 4px solid #a0aec0;">🔗 Otros Parientes</a>
                            </div>
                        </div>

                        <button type="button" id="btn-agregar" class="btn" style="background: var(--primary, #FF6600); color: white; padding: 8px 14px; border-radius: 6px; border: none; font-weight: bold; cursor: pointer;">➕ Añadir Familiar</button>
                    </div>
                </div>

                <style>
                    .btn-remove:hover, .btn-remove-separador:hover {
                        background-color: #e53e3e !important;
                        color: white !important;
                        border-color: #e53e3e !important;
                    }
                </style>
            
        <div class="grid-container" style="margin-top:20px;">
            <div class="seccion-titulo">📝 Observaciones y Estatus</div>
            <div style="grid-column: span 2;">
                <textarea name="observaciones" rows="4" style="width: 100%; border-radius: 8px; border: 1px solid #ddd; padding: 10px;"><?php echo htmlspecialchars($std['observaciones']); ?></textarea>
            </div>
            
            <div>
                <label>¿Viaja?</label>
                <div class="radio-group">
                    <label><input type="radio" name="viaja" value="si" <?php if($std['viaja']=='si') echo 'checked'; ?>> Sí</label>
                    <label><input type="radio" name="viaja" value="no" <?php if($std['viaja']=='no') echo 'checked'; ?>> No</label>
                </div>
            </div>
            <div>
                <label>Estatus Académico</label>
                <div class="radio-group">
                    <label><input type="radio" name="estatus_estudio" value="activo" <?php if($std['estatus_estudio']=='activo') echo 'checked'; ?>> Activo</label>
                    <label><input type="radio" name="estatus_estudio" value="inactivo" <?php if($std['estatus_estudio']=='inactivo') echo 'checked'; ?>> Inactivo</label>
                </div>
            </div>
        </div>

        <div style="display: flex; justify-content: space-around; align-items: center; margin-top: 30px; padding-bottom: 50px;">
    
            <!-- Botón Cancelar (Rojo) -->
            <a href="index.php" style="text-decoration: none; background: #D32F2F; color: white; padding: 14px 32px; border-radius: 12px; cursor: pointer; font-weight: bold; display: inline-flex; align-items: center; justify-content: center; font-size: 14px; border: none; box-sizing: border-box; min-width: 220px;">
                <span style="margin-right: 8px; font-size: 16px;">✖</span> Cancelar y volver
            </a>

            <!-- Botón Guardar (Naranja) -->
            <button type="submit" name="save_all" class="btn-next" style="background: #FF6600; color: white; padding: 12px 30px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold;">
                💾 GUARDAR CAMBIOS DEFINITIVOS
            </button>

        </div>

        </div>
    </form>
</div>

<script>
window.formDataStorage = {};
window.familiaresExistentes = <?php echo json_encode($lista_familiares ?: []); ?>;
const carreraGuardada = "<?php echo $std['carrera'] ?? ''; ?>";
const currentEstado = "<?php echo $std['estado_res'] ?? ''; ?>";
const currentMunicipio = "<?php echo $std['municipio_res'] ?? ''; ?>";

document.addEventListener('DOMContentLoaded', () => {
    // Inicialización de módulos del Administrador
    initFamiliaresAdmin();
    initAcademicoEdicion();
    initResidencia();
    initSeguridadEventos();
});

/**
 * LÓGICA DE CARGA FAMILIAR INTERACTIVA (Idéntica a la vista pública)
 */
function initFamiliaresAdmin() {
    const cuerpoTabla = document.getElementById('cuerpo-tabla');
    const btnAgregar = document.getElementById('btn-agregar');
    const avisoEstado = document.getElementById('aviso-estado');
    const checkNoFamiliares = document.getElementById('no-familiares');
    const btnDropdown = document.getElementById('btn-dropdown-grupo');
    const menuDropdown = document.getElementById('menu-dropdown-grupo');

    if (!cuerpoTabla || !btnAgregar || !checkNoFamiliares || !btnDropdown || !menuDropdown) return;

    const coloresConfig = {
        'primaria': { separador: '#c6f6d5', texto: '#22543d', filas: '#f0fff4' },
        'secundaria': { separador: '#feebc8', texto: '#744210', filas: '#fffaf0' },
        'otros': { separador: '#edf2f7', texto: '#2d3748', filas: '#f7fafc' }
    };

    const titulosConfig = {
        'primaria': '👨‍👩‍👧‍👦 Familia Primaria',
        'secundaria': '🏡 Carga Secundaria',
        'otros': '🔗 Otros Parientes'
    };

    // Control de visibilidad del Dropdown de Clasificaciones
    btnDropdown.onclick = (e) => {
        e.stopPropagation();
        menuDropdown.style.display = menuDropdown.style.display === 'block' ? 'none' : 'block';
    };

    document.addEventListener('click', () => { menuDropdown.style.display = 'none'; });

    // Deshabilita del menú contextual las clasificaciones ya impresas
    function actualizarOpcionesDropdown() {
        const existentes = Array.from(cuerpoTabla.querySelectorAll('.fila-separador')).map(tr => tr.getAttribute('data-grupo'));
        menuDropdown.querySelectorAll('a').forEach(enlace => {
            const valor = enlace.getAttribute('data-valor');
            if (existentes.includes(valor)) {
                enlace.style.pointerEvents = 'none';
                enlace.style.opacity = '0.4';
                enlace.classList.add('disabled');
            } else {
                enlace.style.pointerEvents = 'auto';
                enlace.style.opacity = '1';
                enlace.classList.remove('disabled');
            }
        });
    }

    // Pinta las filas de datos con el color del grupo correspondiente según herencia posicional
    function actualizarPertenenciaVisual() {
        let grupoActive = 'primaria';
        Array.from(cuerpoTabla.children).forEach(tr => {
            if (tr.classList.contains('fila-separador')) {
                grupoActive = tr.getAttribute('data-grupo');
            } else if (tr.classList.contains('fila-datos')) {
                tr.style.backgroundColor = coloresConfig[grupoActive] ? coloresConfig[grupoActive].filas : '#ffffff';
            }
        });
        actualizarOpcionesDropdown();
    }

    // Inyección de una nueva Fila de Datos
    function crearFilaHTML(id) {
        const tr = document.createElement('tr');
        tr.className = 'fila-datos';
        tr.setAttribute('data-id', id);
        tr.innerHTML = `
            <input type="hidden" name="estructura_tabla[]" value="fila:${id}">
            <td><input type="text" name="f_nom_${id}" required style="width:100%; padding:5px; border:1px solid #ccc; border-radius:4px;"></td>
            <td><input type="text" name="f_ape_${id}" required style="width:100%; padding:5px; border:1px solid #ccc; border-radius:4px;"></td>
            <td><input type="text" name="f_par_${id}" required style="width:100%; padding:5px; border:1px solid #ccc; border-radius:4px;"></td>
            <td><input type="number" name="f_eda_${id}" min="0" max="120" required style="width:100%; padding:5px; border:1px solid #ccc; border-radius:4px;"></td>
            <td><input type="text" name="f_ins_${id}" style="width:100%; padding:5px; border:1px solid #ccc; border-radius:4px;"></td>
            <td><input type="text" name="f_ocu_${id}" style="width:100%; padding:5px; border:1px solid #ccc; border-radius:4px;"></td>
            <td><input type="number" name="f_ing_${id}" step="0.01" min="0" value="0.00" style="width:100%; padding:5px; border:1px solid #ccc; border-radius:4px;"></td>
            <td style="text-align:center;"><button type="button" class="btn-remove" style="background:none; border:none; cursor:pointer;">❌</button></td>
        `;
        cuerpoTabla.appendChild(tr);
        return tr;
    }

    // Evento de inserción manual de clasificaciones
    menuDropdown.querySelectorAll('a').forEach(enlace => {
        enlace.onclick = (e) => {
            e.preventDefault();
            const valor = e.target.closest('a').getAttribute('data-valor');
            
            const trSep = document.createElement('tr');
            trSep.className = 'fila-separador';
            trSep.setAttribute('data-grupo', valor);
            trSep.style.backgroundColor = coloresConfig[valor].separador;
            trSep.style.color = coloresConfig[valor].texto;
            trSep.style.fontWeight = 'bold';
            trSep.innerHTML = `
                <td colspan="7" style="padding: 10px 12px;">${titulosConfig[valor]}<input type="hidden" name="estructura_tabla[]" value="separador:${valor}"></td>
                <td style="text-align:center;"><button type="button" class="btn-remove-separador" data-valor="${valor}" style="background:none; border:none; cursor:pointer;">❌</button></td>
            `;
            cuerpoTabla.appendChild(trSep);
            crearFilaHTML(Date.now());
            actualizarPertenenciaVisual();
        };
    });

    btnAgregar.onclick = (e) => {
        e.preventDefault();
        crearFilaHTML(Date.now());
        actualizarPertenenciaVisual();
    };

    // Delegación de eventos para Remover filas y separadores
    cuerpoTabla.onclick = (e) => {
        const btnRemove = e.target.closest('.btn-remove');
        if (btnRemove) {
            if (cuerpoTabla.querySelectorAll('.fila-datos').length > 1) {
                if (confirm('¿Desea remover este familiar de la lista?')) {
                    btnRemove.closest('tr').remove();
                    actualizarPertenenciaVisual();
                }
            } else {
                alert('⚠️ Debe haber al menos un familiar en tabla, o marcar la opción de que el estudiante vive solo.');
            }
            return;
        }

        const btnRemoveSep = e.target.closest('.btn-remove-separador');
        if (btnRemoveSep) {
            if (confirm('¿Remover este grupo clasificado por completo?')) {
                btnRemoveSep.closest('tr').remove();
                actualizarPertenenciaVisual();
            }
        }
    };

    // Comportamiento del interruptor "El estudiante vive solo"
    function gestionarEstadoNoFamiliares() {
        const inactivo = checkNoFamiliares.checked;
        cuerpoTabla.querySelectorAll('input, button').forEach(el => { el.disabled = inactivo; });
        btnAgregar.disabled = inactivo;
        btnDropdown.disabled = inactivo;

        if (inactivo) {
            cuerpoTabla.style.opacity = '0.5';
            avisoEstado.textContent = '🚫 Carga familiar omitida (El estudiante vive solo / Sin familiares registrados).';
            avisoEstado.style.color = '#e53e3e';
            cuerpoTabla.querySelectorAll('input[required]').forEach(input => input.removeAttribute('required'));
        } else {
            cuerpoTabla.style.opacity = '1';
            avisoEstado.textContent = '✅ Listado familiar activo y estructurado posicionalmente.';
            avisoEstado.style.color = '#38a169';
            cuerpoTabla.querySelectorAll('.fila-datos input:not([name*="f_ins"]):not([name*="f_ocu"]):not([name*="f_ing"])').forEach(input => input.setAttribute('required', 'required'));
        }
    }

    checkNoFamiliares.addEventListener('change', gestionarEstadoNoFamiliares);

    // Carga de datos iniciales provenientes de Base de Datos
    if (window.familiaresExistentes && window.familiaresExistentes.length > 0) {
        checkNoFamiliares.checked = false;
        actualizarPertenenciaVisual();
        gestionarEstadoNoFamiliares();
    } else {
        // Si la base de datos está vacía, marcamos el check como "vive solo" automáticamente para no forzar inputs vacíos
        checkNoFamiliares.checked = true;
        gestionarEstadoNoFamiliares();
    }
}

/**
 * GESTIÓN ACADÉMICA (Carreras PNF e Incidencias del Trayecto Inicial)
 */
async function initAcademicoEdicion() {
    const carreraSelect = document.getElementById('carreraSelect');
    const trayectoSelect = document.getElementById('trayectoSelect');
    const trimestreSelect = document.getElementById('trimestreSelect');
    const iraInput = document.getElementById('ira_anterior');

    if (carreraSelect) {
        try {
            const response = await fetch('../carreras.json');
            const carreras = await response.json();
            
            carreraSelect.innerHTML = '<option value="" disabled>Seleccione PNF</option>';
            carreras.forEach(c => {
                const opt = document.createElement('option');
                opt.value = c.id; 
                opt.textContent = c.nombre;
                if(c.id === carreraGuardada || c.nombre === carreraGuardada) opt.selected = true;
                carreraSelect.appendChild(opt);
            });
        } catch (e) { console.error("Error cargando carreras.json:", e); }
    }

    if (trayectoSelect && trimestreSelect) {
        const gestionarTrayecto = () => {
            if (trayectoSelect.value === 'inicial') {
                trimestreSelect.disabled = true;
                trimestreSelect.style.backgroundColor = "#f0f0f0";
                trimestreSelect.value = ""; 
            } else {
                trimestreSelect.disabled = false;
                trimestreSelect.style.backgroundColor = "";
            }
        };
        trayectoSelect.addEventListener('change', gestionarTrayecto);
        gestionarTrayecto();
    }

    if (iraInput) {
        iraInput.addEventListener('input', () => {
            let v = parseFloat(iraInput.value);
            if (v > 20) iraInput.value = 20;
            if (v < 0) iraInput.value = 0;
        });
    }
}

/**
 * CONTROL DE RESIDENCIA (Estados y Municipios Dinámicos de Venezuela)
 */
async function initResidencia() {
    const estadoSelect = document.getElementById('estado_res');
    const municipioSelect = document.getElementById('municipio_res');

    if (!estadoSelect || !municipioSelect) return;

    try {
        const response = await fetch('../venezuela.json');
        const datos = await response.json();

        estadoSelect.innerHTML = '<option value="" disabled>Seleccione Estado</option>';
        datos.forEach(item => {
            const opt = document.createElement('option');
            opt.value = item.estado;
            opt.textContent = item.estado;
            if(item.estado === currentEstado) opt.selected = true;
            estadoSelect.appendChild(opt);
        });

        if (currentEstado) {
            actualizarMunicipios(currentEstado, datos, currentMunicipio);
        }

        estadoSelect.addEventListener('change', () => {
            actualizarMunicipios(estadoSelect.value, datos, null);
        });

    } catch (e) { console.error("Error cargando venezuela.json:", e); }
}

function actualizarMunicipios(nombreEstado, datos, seleccionado) {
    const municipioSelect = document.getElementById('municipio_res');
    const info = datos.find(e => e.estado === nombreEstado);
    municipioSelect.innerHTML = '<option value="" disabled selected>Seleccione Municipio</option>';
    
    if (info) {
        info.municipios.sort().forEach(m => {
            const opt = document.createElement('option');
            opt.value = m;
            opt.textContent = m;
            if(m === seleccionado) opt.selected = true;
            municipioSelect.appendChild(opt);
        });
        municipioSelect.disabled = false;
    }
}

/**
 * CÁLCULO DE EDAD AUTOMÁTICO Y SEGURIDAD CREDECIALES
 */
function initSeguridadEventos() {
    const fNacInput = document.getElementById('f_nac');
    const passInput = document.getElementById('reg_password');
    const passConfirm = document.getElementById('reg_password_confirm');
    const pregunta = document.getElementById('pregunta_seguridad');
    const respuesta = document.getElementById('respuesta_seguridad');

    if (fNacInput) {
        fNacInput.addEventListener('input', function() {
            const hoy = new Date();
            const fechaNac = new Date(this.value);
            if(!isNaN(fechaNac.getTime())){
                let edad = hoy.getFullYear() - fechaNac.getFullYear();
                const m = hoy.getMonth() - fechaNac.getMonth();
                if (m < 0 || (m === 0 && hoy.getDate() < fechaNac.getDate())) edad--;
                const campoEdad = document.getElementById('edad');
                if (campoEdad) campoEdad.value = edad;
            }
        });
    }

    if (passInput && passConfirm) {
        function validarCamposSeguridad() {
            const hasValue = passInput.value.length > 0;
            passConfirm.required = hasValue;
            if (pregunta) pregunta.required = hasValue;
            if (respuesta) respuesta.required = hasValue;

            if (hasValue && passInput.value !== passConfirm.value) {
                passConfirm.setCustomValidity("Las contraseñas no coinciden");
                passConfirm.style.borderColor = "#d32f2f";
            } else {
                passConfirm.setCustomValidity("");
                passConfirm.style.borderColor = hasValue ? "#4CAF50" : "#ddd";
            }
        }
        passInput.addEventListener('input', validarCamposSeguridad);
        passConfirm.addEventListener('input', validarCamposSeguridad);
    }
}

// Alternar visualización de contraseñas con cambio dinámico de icono FontAwesome
function togglePassword(idInput, btn) {
    const input = document.getElementById(idInput);
    const icon = btn.querySelector('i');
    if (!input || !icon) return;
    
    if (input.type === "password") {
        input.type = "text";
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = "password";
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>

</body>
</html>