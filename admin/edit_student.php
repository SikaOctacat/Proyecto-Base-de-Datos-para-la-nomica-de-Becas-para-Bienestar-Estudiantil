<?php
ob_start();
session_start();
require '../db.php'; 

if (!isset($_SESSION['user']) || (isset($_SESSION['rol']) && $_SESSION['rol'] !== 'admin')) {
    header('Location: ../login.php');
    exit;
}

$ci = $_GET['id'] ?? null;
if (!$ci) { header('Location: index.php'); exit; }

// --- LÓGICA DE GUARDADO ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_all'])) {
    try {
        $pdo->beginTransaction();
        
        // 1. Actualizar Datos del Estudiante
        $pdo->prepare('UPDATE residencia SET 
            t_res=?, t_viv=?, t_loc=?, r_prop=?, estado_res=?, municipio_res=?, dir_local=?, tel_local=? 
            WHERE ci_estudiante=?')
            ->execute([
                $_POST['t_res'], 
                $_POST['t_viv'], 
                $_POST['t_loc'], 
                $_POST['r_prop'], 
                $_POST['estado_res'], 
                $_POST['municipio_res'], 
                $_POST['direccion_res'], 
                $_POST['telefono_res'], 
                $ci
            ]);
        
        // 2. Residencia
        $pdo->prepare('UPDATE residencia SET t_viv=?, dir_local=?, tel_local=? WHERE ci_estudiante=?')
            ->execute([$_POST['tipo_vivienda'], $_POST['direccion_res'], $_POST['telefono_res'], $ci]);
        
        // 3. Record Académico
        $pdo->prepare('UPDATE record_academico SET ira_anterior=? WHERE ci_estudiante=?')
            ->execute([$_POST['indice'], $ci]);

        // 4. Familiares (Reemplazo total)
        $pdo->prepare('DELETE FROM familiar WHERE ci_estudiante = ?')->execute([$ci]);
        if (!empty($_POST['f_nom'])) {
            $stmt_fam = $pdo->prepare('INSERT INTO familiar (ci_estudiante, f_nom, f_ape, f_par, f_eda, f_ins, f_ocu, f_ing) VALUES (?,?,?,?,?,?,?,?)');
            foreach ($_POST['f_nom'] as $k => $nom) {
                if (!empty(trim($nom))) {
                    $stmt_fam->execute([$ci, $nom, $_POST['f_ape'][$k], $_POST['f_par'][$k], $_POST['f_eda'][$k], $_POST['f_ins'][$k], $_POST['f_ocu'][$k], $_POST['f_ing'][$k]]);
                }
            }
        }

        // 5. LÓGICA DE SEGURIDAD (Opcional en edición)
        // Solo se actualiza si el campo de password no está vacío
        if (!empty($_POST['reg_password'])) {
            $pass_hash = hash('sha256', $_POST['reg_password']);
            $pregunta = $_POST['pregunta_seguridad'] ?? '';
            $respuesta = hash('sha256', strtolower(trim($_POST['respuesta_seguridad'])));
            
            $stmt_user = $pdo->prepare('UPDATE usuarios SET password = ?, pregunta_seguridad = ?, respuesta_seguridad = ? WHERE usuario = ?');
            $stmt_user->execute([$pass_hash, $pregunta, $respuesta, $ci]);
        }
        
        $pdo->commit();
        header("Location: index.php?msg=success"); exit;
    } catch (Exception $e) { 
        if ($pdo->inTransaction()) $pdo->rollBack(); 
        $error = "Error: " . $e->getMessage(); 
    }
}

// --- CARGA DE DATOS PARA EL FORMULARIO ---
$stmt = $pdo->prepare('SELECT e.*, r.*, ra.ira_anterior as ra_indice 
                       FROM estudiante e 
                       LEFT JOIN residencia r ON e.ci = r.ci_estudiante 
                       LEFT JOIN record_academico ra ON e.ci = ra.ci_estudiante 
                       WHERE e.ci = ?');
$stmt->execute([$ci]);
$std = $stmt->fetch();

$fams = $pdo->prepare('SELECT * FROM familiar WHERE ci_estudiante = ?');
$fams->execute([$ci]);
$lista_familiares = $fams->fetchAll();

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
                <input type="text" value="<?php echo $ci; ?>" disabled class="readonly-input">
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
                <label>Apellido Paterno</label>
                <input type="text" name="apellido_paterno" value="<?php echo htmlspecialchars($std['apellido_paterno']); ?>" required>
            </div>
            <div>
                <label>Apellido Materno</label>
                <input type="text" name="apellido_materno" value="<?php echo htmlspecialchars($std['apellido_materno']); ?>" required>
            </div>
            <div>
                <label>Fecha de Nacimiento</label>
                <input type="date" name="f_nac" id="f_nac" value="<?php echo $std['f_nac']; ?>" min="<?php echo $min_date; ?>" max="<?php echo $max_date; ?>" required>
            </div>
            <div>
                <label>Edad</label>
                <input type="number" id="edad" value="<?php echo $std['edad']; ?>" readonly class="readonly-input">
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
                <input type="password" name="reg_password" id="reg_password" placeholder="Mínimo 4 caracteres" required>
                <button type="button" class="btn-view-pass" onclick="togglePassword('reg_password', this)">
                    <i class="fas fa-eye"></i>
                </button>
            </div>

            <div class="password-wrapper">
                <label>Confirmar Contraseña</label>
                <input type="password" id="reg_password_confirm" placeholder="Repita la nueva contraseña" required>
                <button type="button" class="btn-view-pass" onclick="togglePassword('reg_password_confirm', this)">
                    <i class="fas fa-eye"></i>
                </button>
            </div>

            <div>
                <label>Pregunta de Seguridad</label>
                <select name="pregunta_seguridad" id="pregunta_seguridad" style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd;" required>
                    <option value="" selected disabled>Seleccione una pregunta...</option>
                    <option value="Nombre de tu primera mascota">¿Nombre de tu primera mascota?</option>
                    <option value="Ciudad donde naciste">¿Ciudad donde naciste?</option>
                    <option value="Nombre de tu escuela primaria">¿Nombre de tu escuela primaria?</option>
                    <option value="Personaje de ficción favorito">¿Personaje de ficción favorito?</option>
                </select>
            </div>

            <div>
                <label>Respuesta de Seguridad</label>
                <input type="text" name="respuesta_seguridad" id="respuesta_seguridad" placeholder="Nueva respuesta secreta" required>
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

            <div class="seccion-titulo">🎓 Información Académica</div>
            
            <div>
                <label>Carrera</label>
                <input type="text" name="carrera" value="<?php echo htmlspecialchars($std['carrera']); ?>" required>
            </div>
            <div>
                <label>Código Estudiante</label>
                <input type="text" name="codigo_estudiante" value="<?php echo htmlspecialchars($std['cod_est']); ?>" required maxlength="10">
            </div>
            <div>
                <label>Trayecto</label>
                <input type="text" name="trayecto" value="<?php echo htmlspecialchars($std['trayecto']); ?>" required>
            </div>
            <div>
                <label>Trimestre</label>
                <input type="text" name="trimestre" value="<?php echo htmlspecialchars($std['trimestre']); ?>" required>
            </div>

            <div class="seccion-titulo">👨‍👩‍👧‍👦 Carga Familiar</div>
        </div>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Apellido</th>
                        <th>Parentesco</th>
                        <th style="width: 60px;">Edad</th>
                        <th>Instrucción</th>
                        <th>Ocupación</th>
                        <th style="width: 100px;">Ingreso</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    if(empty($lista_familiares)) $lista_familiares = [array_fill_keys(['f_nom','f_ape','f_par','f_eda','f_ins','f_ocu','f_ing'], '')];
                    foreach($lista_familiares as $f): 
                    ?>
                    <tr>
                        <td><input type="text" name="f_nom[]" style="width:100%" value="<?php echo htmlspecialchars($f['f_nom']); ?>"></td>
                        <td><input type="text" name="f_ape[]" style="width:100%" value="<?php echo htmlspecialchars($f['f_ape']); ?>"></td>
                        <td><input type="text" name="f_par[]" style="width:100%" value="<?php echo htmlspecialchars($f['f_par']); ?>"></td>
                        <td><input type="number" name="f_eda[]" style="width:100%" value="<?php echo $f['f_eda']; ?>"></td>
                        <td><input type="text" name="f_ins[]" style="width:100%" value="<?php echo htmlspecialchars($f['f_ins']); ?>"></td>
                        <td><input type="text" name="f_ocu[]" style="width:100%" value="<?php echo htmlspecialchars($f['f_ocu']); ?>"></td>
                        <td><input type="text" name="f_ing[]" style="width:100%" value="<?php echo htmlspecialchars($f['f_ing']); ?>"></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

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

        <div style="margin-top: 30px; text-align: right; padding-bottom: 50px;">
            <button type="submit" name="save_all" class="btn-next" style="background: #FF6600; color: white; padding: 12px 30px; border: none; border-radius: 8px; cursor: pointer; font-weight: bold;">
                💾 GUARDAR CAMBIOS DEFINITIVOS
            </button>
        </div>
    </form>
</div>

<script>
// 1. Visibilidad de Contraseña
function togglePassword(id) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
}

// 2. Cálculo de Edad Automático
document.getElementById('f_nac').addEventListener('input', function() {
    const hoy = new Date();
    const fechaNac = new Date(this.value);
    if(!isNaN(fechaNac.getTime())){
        let edad = hoy.getFullYear() - fechaNac.getFullYear();
        const m = hoy.getMonth() - fechaNac.getMonth();
        if (m < 0 || (m === 0 && hoy.getDate() < fechaNac.getDate())) edad--;
        document.getElementById('edad').value = edad;
    }
});

// 3. Validación Condicional de Seguridad
const passInput = document.getElementById('reg_password');
const passConfirm = document.getElementById('reg_password_confirm');
const pregunta = document.getElementById('pregunta_seguridad');
const respuesta = document.getElementById('respuesta_seguridad');

function validarCamposSeguridad() {
    const hasValue = passInput.value.length > 0;
    
    // Si hay texto en password, los demás campos de seguridad son obligatorios
    passConfirm.required = hasValue;
    pregunta.required = hasValue;
    respuesta.required = hasValue;

    // Validación de coincidencia
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
</script>

</body>
</html>