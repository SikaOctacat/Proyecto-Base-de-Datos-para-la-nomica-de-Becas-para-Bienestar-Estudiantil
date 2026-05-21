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
        
        // 4. Familiares (Corrección error 1364 + Clasificación Inteligente Automática)
        $pdo->prepare('DELETE FROM familiar WHERE ci_estudiante = ?')->execute([$ci]);
        if (!empty($_POST['f_nom'])) {
            $stmt_fam = $pdo->prepare('INSERT INTO familiar (id, ci_estudiante, f_nom, f_ape, f_par, f_eda, f_ins, f_ocu, f_ing, f_clasificacion) VALUES (?,?,?,?,?,?,?,?,?,?)');
            foreach ($_POST['f_nom'] as $k => $nom) {
                if (!empty(trim($nom))) {
                    $id_fam = generarIdManual();
                    
                    // Deducción automática de la clasificación según el parentesco (f_par)
                    $parentesco = mb_strtolower(trim($_POST['f_par'][$k] ?? ''), 'UTF-8');
                    if (in_array($parentesco, ['madre', 'padre', 'mamá', 'papá', 'hermano', 'hermana', 'hijo', 'hija'])) {
                        $clasificacion = 'primaria';
                    } elseif (in_array($parentesco, ['abuelo', 'abuela', 'tío', 'tía', 'primo', 'prima', 'sobrino', 'sobrina'])) {
                        $clasificacion = 'secundaria';
                    } else {
                        $clasificacion = 'otros';
                    }

                    $stmt_fam->execute([
                        $id_fam,
                        $ci, 
                        $nom, 
                        $_POST['f_ape'][$k] ?? '', 
                        $_POST['f_par'][$k] ?? '', 
                        (int)($_POST['f_eda'][$k] ?? 0), 
                        $_POST['f_ins'][$k] ?? '', 
                        $_POST['f_ocu'][$k] ?? '', 
                        (float)($_POST['f_ing'][$k] ?? 0),
                        $clasificacion // Nueva columna guardada con éxito
                    ]);
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
                    <span>👨‍👩‍👧‍👦 Carga Familiar</span>
                    <div id="aviso-estado" style="margin: 10px 0; font-weight: bold; font-size: 0.9rem;"></div>

                    <div class="table-responsive" style="border: 1px solid #ddd; border-radius: 8px; background: white; padding: 5px;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <thead>
                            <tr style="background: #f8f9fa;">
                                <th style="padding: 10px; border-bottom: 2px solid #FF6600;">Nombre</th>
                                <th style="padding: 10px; border-bottom: 2px solid #FF6600;">Apellido</th>
                                <th style="padding: 10px; border-bottom: 2px solid #FF6600;">Vínculo</th>
                                <th style="padding: 10px; border-bottom: 2px solid #FF6600;">Edad</th>
                                <th style="padding: 10px; border-bottom: 2px solid #FF6600;">Instrucción</th>
                                <th style="padding: 10px; border-bottom: 2px solid #FF6600;">Ocupación</th>
                                <th style="padding: 10px; border-bottom: 2px solid #FF6600;">Ingreso</th>
                                <th style="padding: 10px; border-bottom: 2px solid #FF6600;"></th> </tr>
                        </thead>
                        <tbody id="cuerpo-tabla"></tbody>
                    </table>
                </div>

                <label style="font-size: 0.8rem; color: #666; cursor: pointer;">
                    <input type="checkbox" id="no-familiares" style="margin-top: 15px;"> No tengo familiares
                </label>
                <div style="margin-top: 15px;">
                    <button type="button" id="btn-agregar" class="btn" style="background: #00dc0b; color: white; padding: 10px 20px; border: none; border-radius: 8px; font-weight: bold;">
                        + Añadir Familiar
                    </button>
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

// Declaramos la variable de la tabla en el scope global para acceder a ella
let tablaFamiliares;

document.addEventListener('DOMContentLoaded', () => {
    // 2. Instancia ÚNICA de la tabla
    tablaFamiliares = new TablaDinamica({
        cuerpoId: 'cuerpo-tabla',
        btnAgregarId: 'btn-agregar',
        checkOmitirId: 'no-familiares',
        contenedorClass: '.table-responsive',
        avisoId: 'aviso-estado',
        prefijoKey: 'f',
        maxFilas: 5,
        columnas: [
            { name: 'nom', placeholder: 'Nombre' },
            { name: 'ape', placeholder: 'Apellido' },
            { name: 'par', placeholder: 'Vínculo' },
            { name: 'eda', placeholder: '0', type: 'number', style: 'width:60px' },
            { name: 'ins', placeholder: 'Nivel educativo' },
            { name: 'ocu', placeholder: 'Trabajo' },
            { name: 'ing', placeholder: '0.00', type: 'number', step: '0.01', style: 'width:90px' }
        ]
    });

    // 3. CARGA DE DATOS REALES (Inyección de BD a la Tabla)
    if (window.familiaresExistentes && window.familiaresExistentes.length > 0) {
        // Limpiamos la fila vacía que la tabla crea por defecto al iniciar
        const cuerpo = document.getElementById('cuerpo-tabla');
        cuerpo.innerHTML = "";

        window.familiaresExistentes.forEach(fam => {
            // Usamos un método para agregar fila con datos pre-cargados
            // Si tu clase no tiene "agregarFila", usamos "crearFila" y luego asignamos valores
            const idUnico = Date.now() + Math.random();
            tablaFamiliares.crearFila(idUnico);
            
            // Buscamos la última fila insertada para llenarla
            const filas = cuerpo.querySelectorAll('tr');
            const ultimaFila = filas[filas.length - 1];
            
            // Llenamos los inputs de esa fila
            const inputs = ultimaFila.querySelectorAll('input');
            inputs[0].value = fam.f_nom || "";
            inputs[1].value = fam.f_ape || "";
            inputs[2].value = fam.f_par || "";
            inputs[3].value = fam.f_eda || 0;
            inputs[4].value = fam.f_ins || "";
            inputs[5].value = fam.f_ocu || "";
            inputs[6].value = fam.f_ing || 0.00;
        });
        
        tablaFamiliares.actualizar();
    } else {
        // Si no hay datos, mostramos el checkbox de "No tengo familiares" desactivado
        // o dejamos la fila vacía inicial.
    }
});

const carreraGuardada = "<?php echo $std['carrera']; ?>";

async function initAcademicoEdicion() {
    const carreraSelect = document.getElementById('carreraSelect');
    const trayectoSelect = document.getElementById('trayectoSelect');
    const trimestreSelect = document.getElementById('trimestreSelect');

    // 1. Cargar Carreras desde JSON
    try {
        const response = await fetch('../carreras.json'); // Ajusta la ruta si es necesario
        const carreras = await response.json();
        
        carreraSelect.innerHTML = '<option value="" disabled>Seleccione PNF</option>';
        carreras.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.id; // o c.nombre, según cómo guardes en BD
            opt.textContent = c.nombre;
            if(c.id === carreraGuardada || c.nombre === carreraGuardada) opt.selected = true;
            carreraSelect.appendChild(opt);
        });
    } catch (e) { console.error("Error cargando carreras:", e); }

    // 2. Lógica de Trayecto Inicial
    const gestionarTrayecto = () => {
        if (trayectoSelect.value === 'inicial') {
            trimestreSelect.disabled = true;
            trimestreSelect.style.backgroundColor = "#f0f0f0";
            // Nota: En edición admin no bloqueamos el botón de guardar, 
            // pero podrías mostrar un alert si lo deseas.
        } else {
            trimestreSelect.disabled = false;
            trimestreSelect.style.backgroundColor = "";
        }
    };

    trayectoSelect.addEventListener('change', gestionarTrayecto);
    gestionarTrayecto(); // Ejecutar al cargar
    
    // 3. Validación de rango del IRA (0-20)
    const iraInput = document.getElementById('ira_anterior');
    iraInput.addEventListener('input', () => {
        let v = parseFloat(iraInput.value);
        if (v > 20) iraInput.value = 20;
        if (v < 0) iraInput.value = 0;
    });
}

// Agregar a tu DOMContentLoaded existente
document.addEventListener('DOMContentLoaded', () => {
    initResidencia(); // La de la respuesta anterior
    initAcademicoEdicion(); 
});

// --- LÓGICA DE UBICACIÓN DINÁMICA ---
const currentEstado = "<?php echo $std['estado_res']; ?>";
const currentMunicipio = "<?php echo $std['municipio_res']; ?>";

async function initResidencia() {
    const estadoSelect = document.getElementById('estado_res');
    const municipioSelect = document.getElementById('municipio_res');

    try {
        const response = await fetch('../venezuela.json');
        const datos = await response.json();

        // Llenar estados
        estadoSelect.innerHTML = '<option value="" disabled>Seleccione Estado</option>';
        datos.forEach(item => {
            const opt = document.createElement('option');
            opt.value = item.estado;
            opt.textContent = item.estado;
            if(item.estado === currentEstado) opt.selected = true;
            estadoSelect.appendChild(opt);
        });

        // Llenar municipios si ya hay un estado cargado
        if (currentEstado) {
            actualizarMunicipios(currentEstado, datos, currentMunicipio);
        }

        // Cambio de estado
        estadoSelect.addEventListener('change', () => {
            actualizarMunicipios(estadoSelect.value, datos, null);
        });

    } catch (e) { console.error("Error cargando JSON", e); }
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

// Iniciar al cargar
document.addEventListener('DOMContentLoaded', () => {
    initResidencia();
    // También asegúrate de que la función togglePassword maneje el icono:
});

// Actualiza tu función togglePassword para que sea igual a la del login:
function togglePassword(idInput, btn) {
    const input = document.getElementById(idInput);
    const icon = btn.querySelector('i');
    if (input.type === "password") {
        input.type = "text";
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = "password";
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

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