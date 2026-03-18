<?php
ob_start();
require '../db.php';

// Simple session check
if (!isset($_SESSION['user']) || (isset($_SESSION['rol']) && $_SESSION['rol'] !== 'admin')) {
    header('Location: ../login.php');
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: index.php');
    exit;
}

// --- LOGIC: SAVE DATA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_all'])) {
    try {
        $pdo->beginTransaction();

        // 1. Update Estudiantes
        $stmt = $pdo->prepare('UPDATE estudiantes SET ci=?, nombres=?, apellidos=?, fecha_nacimiento=?, edad=?, estado_civil=?, telefono=?, correo=?, carnet_patria=? WHERE id=?');
        $stmt->execute([
            $_POST['ci'], $_POST['nombres'], $_POST['apellidos'], $_POST['fecha_nacimiento'],
            $_POST['edad'], $_POST['estado_civil'], $_POST['telefono'], $_POST['correo'],
            $_POST['carnet_patria'], $id
        ]);

        // 1.1 Update Credentials (Login)
        $usuario = $_POST['std_usuario'];
        $pass = $_POST['std_pass'];
        $stmt_u = $pdo->prepare('SELECT usuario_id FROM estudiantes WHERE id = ?');
        $stmt_u->execute([$id]);
        $uid = $stmt_u->fetchColumn();

        if ($uid) {
            if (!empty($pass)) {
                $hash = hash('sha256', $pass);
                $pdo->prepare('UPDATE usuarios SET usuario = ?, password = ? WHERE id = ?')->execute([$usuario, $hash, $uid]);
            } else {
                $pdo->prepare('UPDATE usuarios SET usuario = ? WHERE id = ?')->execute([$usuario, $uid]);
            }
        } else if (!empty($usuario) && !empty($pass)) {
            $hash = hash('sha256', $pass);
            $pdo->prepare('INSERT INTO usuarios (usuario, password, rol) VALUES (?, ?, "estudiante")')->execute([$usuario, $hash]);
            $new_uid = $pdo->lastInsertId();
            $pdo->prepare('UPDATE estudiantes SET usuario_id = ? WHERE id = ?')->execute([$new_uid, $id]);
        }

        // 2. Update PNF
        $stmt_pnf_id = $pdo->prepare('SELECT pnf_id FROM estudiantes WHERE id = ?');
        $stmt_pnf_id->execute([$id]);
        $pnf_id = $stmt_pnf_id->fetchColumn();
        if ($pnf_id) {
            $stmt = $pdo->prepare('UPDATE pnfs SET carrera=?, trayecto=?, trimestre_actual=?, codigo_estudiante=? WHERE id=?');
            $stmt->execute([$_POST['carrera'], $_POST['trayecto'], $_POST['trimestre'], $_POST['codigo_estudiante'], $pnf_id]);
        }

        // 3. Update Residencia
        $stmt = $pdo->prepare('UPDATE residencias SET tipo_vivienda=?, direccion=?, telefono=? WHERE estudiante_id=?');
        $stmt->execute([$_POST['tipo_vivienda'], $_POST['direccion_res'], $_POST['telefono_res'], $id]);

        // 4. Update Trabajo
        $stmt_check_trabajo = $pdo->prepare('SELECT id FROM trabajos WHERE estudiante_id = ?');
        $stmt_check_trabajo->execute([$id]);
        if ($stmt_check_trabajo->fetch()) {
            $stmt = $pdo->prepare('UPDATE trabajos SET lugar=?, ingreso=?, aportador=? WHERE estudiante_id=?');
            $stmt->execute([$_POST['trabajo_lugar'], $_POST['trabajo_ingreso'], $_POST['trabajo_aportador'], $id]);
        } else if (!empty($_POST['trabajo_lugar'])) {
            $stmt = $pdo->prepare('INSERT INTO trabajos (estudiante_id, lugar, ingreso, aportador) VALUES (?, ?, ?, ?)');
            $stmt->execute([$id, $_POST['trabajo_lugar'], $_POST['trabajo_ingreso'], $_POST['trabajo_aportador']]);
        }

        // 5. Update Record Academico
        $stmt = $pdo->prepare('UPDATE records_academicos SET indice_trimestre=?, n_materias_inscritas=?, n_materias_aprobadas=? WHERE estudiante_id=?');
        $stmt->execute([$_POST['indice'], $_POST['m_inscritas'], $_POST['m_aprobadas'], $id]);

        // 6. Update Familiares (Delete and Re-insert)
        $pdo->prepare('DELETE FROM familiares WHERE estudiante_id = ?')->execute([$id]);
        if (!empty($_POST['f_nom'])) {
            $stmt_fam = $pdo->prepare('INSERT INTO familiares (estudiante_id, nombres, apellidos, parentesco, edad, instruccion, ocupacion, ingreso) VALUES (?,?,?,?,?,?,?,?)');
            foreach ($_POST['f_nom'] as $key => $nom) {
                if (!empty($nom)) {
                    $stmt_fam->execute([
                        $id, $nom, $_POST['f_ape'][$key], $_POST['f_par'][$key], 
                        $_POST['f_eda'][$key], $_POST['f_ins'][$key], $_POST['f_ocu'][$key], $_POST['f_ing'][$key]
                    ]);
                }
            }
        }

        $pdo->commit();
        header("Location: index.php?msg=Datos actualizados correctamente");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error al actualizar: " . $e->getMessage();
    }
}

// --- FETCH DATA ---
$stmt = $pdo->prepare('
    SELECT e.*, p.carrera, p.trayecto, p.trimestre_actual, p.codigo_estudiante as pnf_cod,
           r.tipo_vivienda, r.direccion as res_dir, r.telefono as res_tel,
           t.lugar as trab_lugar, t.ingreso as trab_ing, t.aportador as trab_apor,
           ra.indice_trimestre as ra_indice, ra.n_materias_inscritas as ra_ins, ra.n_materias_aprobadas as ra_apr
    FROM estudiantes e
    LEFT JOIN pnfs p ON e.pnf_id = p.id
    LEFT JOIN residencias r ON e.id = r.estudiante_id
    LEFT JOIN trabajos t ON e.id = t.estudiante_id
    LEFT JOIN records_academicos ra ON e.id = ra.estudiante_id
    LEFT JOIN usuarios u ON e.usuario_id = u.id
    WHERE e.id = ?
');
$stmt->execute([$id]);
$std = $stmt->fetch();

// --- FETCH FAMILIARES ---
$stmt_fam = $pdo->prepare('SELECT * FROM familiares WHERE estudiante_id = ?');
$stmt_fam->execute([$id]);
$familiares = $stmt_fam->fetchAll();

// --- FETCH MATERIAS ---
$stmt_mat = $pdo->prepare('SELECT m.nombre, em.trimestre FROM estudiante_materias em JOIN materias m ON em.materia_id = m.id WHERE em.estudiante_id = ?');
$stmt_mat->execute([$id]);
$materias = $stmt_mat->fetchAll();

if (!$std) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Solicitud - Admin</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        body { 
            background: linear-gradient(135deg, rgba(0,0,0,0.92), rgba(20,20,20,0.85)), url('../img/fondo.jpg'); 
            background-size: cover; background-attachment: fixed;
            min-height: 100vh; display: flex; flex-direction: column; align-items: center; padding: 40px 0;
        }
        .dashboard { 
            background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(20px);
            width: 90%; max-width: 1000px; padding: 40px; border-radius: 30px; 
            box-shadow: 0 20px 60px rgba(0,0,0,0.5); border: 1px solid rgba(255,255,255,0.4);
        }
        h2 { color: #FF6600; font-weight: 800; border-bottom: 3px solid #FF6600; padding-bottom: 10px; margin-bottom: 30px; }
        .section-title { background: #f0f0f0; padding: 10px 20px; border-radius: 12px; margin: 30px 0 20px 0; font-weight: 700; color: #555; display: flex; align-items: center; gap: 10px; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 0.9rem; color: #444; }
        input, select, textarea { 
            width: 100%; padding: 12px; border-radius: 12px; border: 1px solid #ddd; background: #fff;
            transition: 0.3s; font-size: 0.95rem; box-sizing: border-box;
        }
        input:focus { border-color: #FF6600; outline: none; box-shadow: 0 0 10px rgba(255,102,0,0.2); }
        .btn-save { background: #28a745; color: white; border: none; padding: 15px 40px; border-radius: 15px; font-weight: 700; cursor: pointer; transition: 0.3s; font-size: 1.1rem; }
        .btn-save:hover { background: #218838; transform: translateY(-3px); box-shadow: 0 8px 25px rgba(40,167,69,0.3); }
        .btn-back { display: inline-block; margin-bottom: 20px; text-decoration: none; color: #666; font-weight: 600; transition: 0.3s; }
        .btn-back:hover { color: #FF6600; }
    </style>
</head>
<body>
<script>document.addEventListener('DOMContentLoaded',function(){document.body.classList.add('page-visible');});</script>
    <div class="dashboard">
        <a href="index.php" class="btn-back">← Volver al Panel</a>
        <h2>🛠️ Editar Solicitud de Beca</h2>
        
        <?php if(isset($error)): ?>
            <div style="background:#f8d7da; color:#721c24; padding:15px; border-radius:12px; margin-bottom:20px;"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <!-- CREDENCIALES (NUEVO) -->
            <div id="acceso" class="section-title" style="background: #fff3e0; border: 1px solid #ffe0b2;">🔐 Credenciales de Acceso</div>
            <div class="grid-2">
                <div>
                    <label>Usuario (Cédula de Identidad)</label>
                    <input type="text" name="std_usuario" value="<?php echo htmlspecialchars($std['usuario'] ?? ''); ?>" placeholder="Ej: 25123456" pattern="[0-9]+" title="Ingrese solo números para la cédula" required>
                </div>
                <div>
                    <label>Nueva Contraseña (dejar en blanco para no cambiar)</label>
                    <input type="password" name="std_pass" placeholder="••••••••">
                </div>
            </div>
            <!-- DATOS PERSONALES -->
            <div class="section-title">👤 Datos Personales</div>
            <div class="grid-3">
                <div>
                    <label>Cédula</label>
                    <input type="text" name="ci" value="<?php echo htmlspecialchars($std['ci']); ?>" required>
                </div>
                <div>
                    <label>Nombres</label>
                    <input type="text" name="nombres" value="<?php echo htmlspecialchars($std['nombres']); ?>" required>
                </div>
                <div>
                    <label>Apellidos</label>
                    <input type="text" name="apellidos" value="<?php echo htmlspecialchars($std['apellidos']); ?>" required>
                </div>
            </div>
            <div class="grid-3" style="margin-top:15px;">
                <div>
                    <label>Fecha de Nacimiento</label>
                    <input type="date" name="fecha_nacimiento" value="<?php echo $std['fecha_nacimiento']; ?>">
                </div>
                <div>
                    <label>Edad</label>
                    <input type="number" name="edad" value="<?php echo $std['edad']; ?>">
                </div>
                <div>
                    <label>Estado Civil</label>
                    <select name="estado_civil">
                        <option value="Soltero/a" <?php echo $std['estado_civil']=='Soltero/a'?'selected':''; ?>>Soltero/a</option>
                        <option value="Casado/a" <?php echo $std['estado_civil']=='Casado/a'?'selected':''; ?>>Casado/a</option>
                        <option value="Divorciado/a" <?php echo $std['estado_civil']=='Divorciado/a'?'selected':''; ?>>Divorciado/a</option>
                        <option value="Viudo/a" <?php echo $std['estado_civil']=='Viudo/a'?'selected':''; ?>>Viudo/a</option>
                    </select>
                </div>
            </div>
            <div class="grid-3" style="margin-top:15px;">
                <div>
                    <label>Teléfono</label>
                    <input type="text" name="telefono" value="<?php echo htmlspecialchars($std['telefono']); ?>">
                </div>
                <div>
                    <label>Correo</label>
                    <input type="email" name="correo" value="<?php echo htmlspecialchars($std['correo']); ?>">
                </div>
                <div>
                    <label>Carnet de la Patria</label>
                    <input type="text" name="carnet_patria" value="<?php echo htmlspecialchars($std['carnet_patria']); ?>">
                </div>
            </div>

            <!-- ACADÉMICO / PNF -->
            <div class="section-title">🎓 Carrera y Situación Académica</div>
            <div class="grid-2">
                <div>
                    <label>Carrera (PNF)</label>
                    <input type="text" name="carrera" value="<?php echo htmlspecialchars($std['carrera'] ?? ''); ?>">
                </div>
                <div>
                    <label>Código Estudiante (OPSU/Univ)</label>
                    <input type="text" name="codigo_estudiante" value="<?php echo htmlspecialchars($std['pnf_cod'] ?? ''); ?>">
                </div>
            </div>
            <div class="grid-3" style="margin-top:15px;">
                <div>
                    <label>Trayecto</label>
                    <input type="text" name="trayecto" value="<?php echo htmlspecialchars($std['trayecto'] ?? ''); ?>">
                </div>
                <div>
                    <label>Trimestre Actual</label>
                    <input type="text" name="trimestre" value="<?php echo htmlspecialchars($std['trimestre_actual'] ?? ''); ?>">
                </div>
                <div>
                    <label>Índice Académico</label>
                    <input type="number" step="0.01" name="indice" value="<?php echo $std['ra_indice'] ?? ''; ?>">
                </div>
            </div>
            <div class="grid-2" style="margin-top:15px;">
                <div>
                    <label>Materias Inscritas</label>
                    <input type="number" name="m_inscritas" value="<?php echo $std['ra_ins'] ?? ''; ?>">
                </div>
                <div>
                    <label>Materias Aprobadas</label>
                    <input type="number" name="m_aprobadas" value="<?php echo $std['ra_apr'] ?? ''; ?>">
                </div>
            </div>

            <!-- RESIDENCIA -->
            <div class="section-title">🏠 Datos de Residencia</div>
            <div class="grid-2">
                <div>
                    <label>Tipo de Vivienda</label>
                    <input type="text" name="tipo_vivienda" value="<?php echo htmlspecialchars($std['tipo_vivienda'] ?? ''); ?>">
                </div>
                <div>
                    <label>Teléfono de Residencia</label>
                    <input type="text" name="telefono_res" value="<?php echo htmlspecialchars($std['res_tel'] ?? ''); ?>">
                </div>
            </div>
            <div style="margin-top:15px;">
                <label>Dirección Detallada</label>
                <textarea name="direccion_res" rows="3"><?php echo htmlspecialchars($std['res_dir'] ?? ''); ?></textarea>
            </div>

            <!-- LABORAL -->
            <div class="section-title">💼 Información Laboral</div>
            <div class="grid-3">
                <div>
                    <label>Lugar de Trabajo</label>
                    <input type="text" name="trabajo_lugar" value="<?php echo htmlspecialchars($std['trab_lugar'] ?? ''); ?>">
                </div>
                <div>
                    <label>Ingreso Estimado (Bs)</label>
                    <input type="text" name="trabajo_ingreso" value="<?php echo htmlspecialchars($std['trab_ing'] ?? ''); ?>">
                </div>
                <div>
                    <label>Aportador Económico</label>
                    <input type="text" name="trabajo_aportador" value="<?php echo htmlspecialchars($std['trab_apor'] ?? ''); ?>">
                </div>
            </div>

            <!-- FAMILIARES -->
            <div class="section-title">👨‍👩‍👧‍👦 Grupo Familiar</div>
            <div id="familiares-container">
                <?php if ($familiares): foreach($familiares as $f): ?>
                    <div class="grid-3" style="margin-bottom: 15px; background: rgba(0,0,0,0.02); padding: 15px; border-radius: 15px; border: 1px solid #eee;">
                        <div>
                            <label>Nombre</label>
                            <input type="text" name="f_nom[]" value="<?php echo htmlspecialchars($f['nombres']); ?>">
                        </div>
                        <div>
                            <label>Apellido</label>
                            <input type="text" name="f_ape[]" value="<?php echo htmlspecialchars($f['apellidos']); ?>">
                        </div>
                        <div>
                            <label>Parentesco</label>
                            <input type="text" name="f_par[]" value="<?php echo htmlspecialchars($f['parentesco']); ?>">
                        </div>
                        <div style="margin-top:10px;">
                            <label>Edad</label>
                            <input type="number" name="f_eda[]" value="<?php echo $f['edad']; ?>">
                        </div>
                        <div style="margin-top:10px;">
                            <label>Ocupación</label>
                            <input type="text" name="f_ocu[]" value="<?php echo htmlspecialchars($f['ocupacion']); ?>">
                        </div>
                        <div style="margin-top:10px;">
                            <label>Ingreso (Bs)</label>
                            <input type="text" name="f_ing[]" value="<?php echo htmlspecialchars($f['ingreso']); ?>">
                        </div>
                        <input type="hidden" name="f_ins[]" value="<?php echo htmlspecialchars($f['instruccion']); ?>">
                    </div>
                <?php endforeach; endif; ?>
                <!-- Always show 2 empty rows for adding new members -->
                <?php for($i=0; $i<2; $i++): ?>
                    <div class="grid-3" style="margin-bottom: 15px; background: rgba(0,0,0,0.01); padding: 15px; border-radius: 15px; border: 1px dashed #ccc;">
                        <div>
                            <label>Nombre</label>
                            <input type="text" name="f_nom[]" value="" placeholder="Nuevo familiar...">
                        </div>
                        <div>
                            <label>Apellido</label>
                            <input type="text" name="f_ape[]" value="">
                        </div>
                        <div>
                            <label>Parentesco</label>
                            <input type="text" name="f_par[]" value="">
                        </div>
                        <div style="margin-top:10px;">
                            <label>Edad</label>
                            <input type="number" name="f_eda[]" value="">
                        </div>
                        <div style="margin-top:10px;">
                            <label>Ocupación</label>
                            <input type="text" name="f_ocu[]" value="">
                        </div>
                        <div style="margin-top:10px;">
                            <label>Ingreso (Bs)</label>
                            <input type="text" name="f_ing[]" value="">
                        </div>
                        <input type="hidden" name="f_ins[]" value="">
                    </div>
                <?php endfor; ?>
            </div>

            <!-- MATERIAS (Solo vista por ahora para simplicidad) -->
            <div class="section-title">📚 Materias Inscritas</div>
            <div style="display:flex; flex-wrap: wrap; gap:10px; padding: 15px; background: #fff; border-radius: 12px; border: 1px solid #eee;">
                <?php if ($materias): foreach($materias as $m): ?>
                    <span class="badge" style="background:#e3f2fd; color:#1976d2; padding: 10px 15px; border-radius: 20px; font-weight: 700;">
                        <?php echo htmlspecialchars($m['nombre']); ?> (T<?php echo $m['trimestre']; ?>)
                    </span>
                <?php endforeach; else: ?>
                    <p style="color:#999; font-style:italic;">No hay materias vinculadas.</p>
                <?php endif; ?>
                <p style="width:100%; font-size: 0.8rem; color: #888; margin-top: 10px;">* Las materias se actualizan automáticamente según el trimestre cargado en la sección académica.</p>
            </div>

            <div style="margin-top: 50px; text-align: center;">
                <button type="submit" name="save_all" class="btn-save">💾 Guardar Todos los Cambios</button>
            </div>
        </form>
    </div>
    <div style="height: 50px;"></div>
</body>
</html>
