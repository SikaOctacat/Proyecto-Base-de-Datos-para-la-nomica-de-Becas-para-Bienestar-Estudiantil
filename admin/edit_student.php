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
        
        // 1. Estudiante
        $stmt = $pdo->prepare('UPDATE estudiante SET nombre1=?, apellido_paterno=?, carrera=?, trayecto=?, trimestre=?, cod_est=?, edad=?, tel_estudiante=?, correo=?, edo_civil=?, C_Patria=?, observaciones=? WHERE ci=?');
        $stmt->execute([$_POST['nombres'], $_POST['apellidos'], $_POST['carrera'], $_POST['trayecto'], $_POST['trimestre'], $_POST['codigo_estudiante'], $_POST['edad'], $_POST['telefono'], $_POST['correo'], $_POST['estado_civil'], $_POST['carnet_patria'], $_POST['observaciones'], $ci]);
        
        // 2. Residencia
        $pdo->prepare('UPDATE residencia SET t_viv=?, dir_local=?, tel_local=? WHERE ci_estudiante=?')->execute([$_POST['tipo_vivienda'], $_POST['direccion_res'], $_POST['telefono_res'], $ci]);
        
        // 3. Record
        $pdo->prepare('UPDATE record_academico SET ira_anterior=? WHERE ci_estudiante=?')->execute([$_POST['indice'], $ci]);

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
        
        $pdo->commit();
        header("Location: index.php?msg=success"); exit;
    } catch (Exception $e) { 
        $pdo->rollBack(); 
        $error = "Error: " . $e->getMessage(); 
    }
}

// --- CARGA DE DATOS ---
$stmt = $pdo->prepare('SELECT e.*, r.t_viv, r.dir_local, r.tel_local, ra.ira_anterior as ra_indice FROM estudiante e LEFT JOIN residencia r ON e.ci = r.ci_estudiante LEFT JOIN record_academico ra ON e.ci = ra.ci_estudiante WHERE e.ci = ?');
$stmt->execute([$ci]);
$std = $stmt->fetch();

$fams = $pdo->prepare('SELECT * FROM familiar WHERE ci_estudiante = ?');
$fams->execute([$ci]);
$lista_familiares = $fams->fetchAll();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Expediente - <?php echo $std['nombre1']; ?></title>
    <link rel="stylesheet" href="../style.css"> 
    <style>
        /* Ajustes para que la tabla no se desborde y use tu estética */
        .seccion-titulo { 
            grid-column: 1 / -1; 
            border-bottom: 2px solid rgba(255,102,0,0.2); 
            padding-bottom: 5px; 
            margin-top: 25px;
            color: var(--primary);
            font-size: 1.1rem;
            font-weight: 700;
        }
        .fam-input { 
            width: 100% !important; 
            padding: 6px !important; 
            font-size: 0.85rem !important; 
        }
        .table-responsive { 
            overflow-x: auto; 
            margin-top: 10px; 
            background: rgba(255,255,255,0.5); 
            padding: 10px; 
            border-radius: 8px;
        }
        table { border-collapse: collapse; width: 100%; min-width: 800px; }
        th { text-align: left; font-size: 0.75rem; color: #666; padding: 5px; }
    </style>
</head>
<body>

<div id="main-container">
    <div class="page-header">
        <h2>Editar Expediente Estudiantil</h2>
        <a href="index.php">← Volver al Listado</a>
    </div>

    <form method="POST">
        <div class="grid-container">
            <div class="seccion-titulo">👤 Datos Personales</div>
            
            <div>
                <label>Cédula</label>
                <input type="text" value="<?php echo $ci; ?>" disabled style="background: rgba(0,0,0,0.05); color: #666;">
            </div>
            <div>
                <label>Primer Nombre</label>
                <input type="text" name="nombres" value="<?php echo $std['nombre1']; ?>" required>
            </div>
            <div>
                <label>Apellido Paterno</label>
                <input type="text" name="apellidos" value="<?php echo $std['apellido_paterno']; ?>" required>
            </div>
            <div>
                <label>Edad</label>
                <input type="number" name="edad" value="<?php echo $std['edad']; ?>" required>
            </div>
            <div>
                <label>Teléfono</label>
                <input type="text" name="telefono" value="<?php echo $std['tel_estudiante']; ?>" required>
            </div>
            <div>
                <label>Correo Electrónico</label>
                <input type="email" name="correo" value="<?php echo $std['correo']; ?>" required>
            </div>
            <div>
                <label>Estado Civil</label>
                <select name="estado_civil" required>
                    <option value="soltero" <?php echo ($std['edo_civil']=='soltero')?'selected':''; ?>>Soltero/a</option>
                    <option value="casado" <?php echo ($std['edo_civil']=='casado')?'selected':''; ?>>Casado/a</option>
                    <option value="concubino" <?php echo ($std['edo_civil']=='concubino')?'selected':''; ?>>Concubino/a</option>
                </select>
            </div>
            <div>
                <label>Carnet de la Patria</label>
                <input type="text" name="carnet_patria" value="<?php echo $std['C_Patria']; ?>">
            </div>

            <div class="seccion-titulo">🎓 Información Académica</div>
            
            <div>
                <label>Carrera</label>
                <input type="text" name="carrera" value="<?php echo $std['carrera']; ?>" required>
            </div>
            <div>
                <label>Código Estudiante</label>
                <input type="text" name="codigo_estudiante" value="<?php echo $std['cod_est']; ?>" required>
            </div>
            <div>
                <label>Trayecto</label>
                <input type="text" name="trayecto" value="<?php echo $std['trayecto']; ?>" required>
            </div>
            <div>
                <label>Trimestre</label>
                <input type="text" name="trimestre" value="<?php echo $std['trimestre']; ?>" required>
            </div>
            <div class="input-group-ira">
                <label>IRA Anterior</label>
                <input type="text" name="indice" value="<?php echo $std['ra_indice']; ?>"required>
                <span class="input-suffix">/20</span>
            </div>

            <div class="seccion-titulo">🏠 Residencia</div>
            
            <div>
                <label>Tipo de Vivienda</label>
                <input type="text" name="tipo_vivienda" value="<?php echo $std['t_viv']; ?>"required>
            </div>
            <div>
                <label>Teléfono Local</label>
                <input type="text" name="telefono_res" value="<?php echo $std['tel_local']; ?>"required>
            </div>
            <div class="full-width">
                <label>Dirección Local Detallada</label>
                <input type="text" name="direccion_res" value="<?php echo $std['dir_local']; ?>"required>
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
                    <?php if(empty($lista_familiares)): ?>
                        <tr>
                            <td><input type="text" name="f_nom[]" class="fam-input"></td>
                            <td><input type="text" name="f_ape[]" class="fam-input"></td>
                            <td><input type="text" name="f_par[]" class="fam-input"></td>
                            <td><input type="number" name="f_eda[]" class="fam-input"></td>
                            <td><input type="text" name="f_ins[]" class="fam-input"></td>
                            <td><input type="text" name="f_ocu[]" class="fam-input"></td>
                            <td><input type="text" name="f_ing[]" class="fam-input"></td>
                        </tr>
                    <?php else: ?>
                        <?php foreach($lista_familiares as $f): ?>
                        <tr>
                            <td><input type="text" name="f_nom[]" class="fam-input" value="<?php echo $f['f_nom']; ?>"></td>
                            <td><input type="text" name="f_ape[]" class="fam-input" value="<?php echo $f['f_ape']; ?>"></td>
                            <td><input type="text" name="f_par[]" class="fam-input" value="<?php echo $f['f_par']; ?>"></td>
                            <td><input type="number" name="f_eda[]" class="fam-input" value="<?php echo $f['f_eda']; ?>"></td>
                            <td><input type="text" name="f_ins[]" class="fam-input" value="<?php echo $f['f_ins']; ?>"></td>
                            <td><input type="text" name="f_ocu[]" class="fam-input" value="<?php echo $f['f_ocu']; ?>"></td>
                            <td><input type="text" name="f_ing[]" class="fam-input" value="<?php echo $f['f_ing']; ?>"></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="grid-container" style="margin-top:20px;">
            <div class="seccion-titulo">📝 Observaciones</div>
            <div class="full-width">
                <textarea name="observaciones" placeholder="Escriba aquí notas adicionales..."><?php echo $std['observaciones']; ?></textarea>
            </div>
        </div>

        <div class="nav-buttons">
            <button type="submit" name="save_all" class="btn-next">ACTUALIZAR EXPEDIENTE</button>
        </div>
    </form>
</div>

</body>
</html>