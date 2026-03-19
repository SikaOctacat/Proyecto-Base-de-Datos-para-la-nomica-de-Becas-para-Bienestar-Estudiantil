<?php
ob_start();
require '../db.php';

// Prevent caching
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// Simple session check
if (!isset($_SESSION['user']) || (isset($_SESSION['rol']) && $_SESSION['rol'] !== 'admin')) {
    header('Location: ../login.php');
    exit;
}

// Global variable initialization for scope safety
$edit_admin = null;
$edit_student = null;
$details = null;
$msg = $_GET['msg'] ?? null;

// --- LOGIC: DELETE STUDENT ---
if (isset($_GET['delete_student'])) {
    try {
        $id = (int)$_GET['delete_student'];
        
        // 1. Get pnf_id before deleting student
        $stmt = $pdo->prepare('SELECT pnf_id FROM estudiantes WHERE id = ?');
        $stmt->execute([$id]);
        $pnf_id = $stmt->fetchColumn();

        // 2. Manual cleanup of ALL related data (just in case CASCADE is restricted)
        $pdo->prepare('DELETE FROM estudiante_becas WHERE estudiante_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM estudiante_materias WHERE estudiante_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM familiares WHERE estudiante_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM records_academicos WHERE estudiante_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM residencias WHERE estudiante_id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM trabajos WHERE estudiante_id = ?')->execute([$id]);
        
        // 3. Delete the student
        $stmt_del = $pdo->prepare('DELETE FROM estudiantes WHERE id = ?');
        $stmt_del->execute([$id]);

        // 4. Delete the PNF record if it's orphaned and exists
        if ($pnf_id) {
            $pdo->prepare('DELETE FROM pnfs WHERE id = ?')->execute([$pnf_id]);
        }
        
        if ($stmt_del->rowCount() > 0) {
            header('Location: index.php?msg=Registro eliminado exitosamente');
        } else {
            header('Location: index.php?msg=No se encontró el registro para eliminar');
        }
    } catch (PDOException $e) {
        header('Location: index.php?msg=Error SQL: ' . urlencode($e->getMessage()));
    }
    exit;
}

// --- LOGIC: DELETE USER (SECONDARY ADMIN) ---
if (isset($_GET['delete_user'])) {
    try {
        $id = (int)$_GET['delete_user'];
        $stmt = $pdo->prepare('DELETE FROM usuarios WHERE id = ? AND usuario != "admin"');
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            header('Location: index.php?msg=Usuario eliminado');
        } else {
            header('Location: index.php?msg=Cuidado: No se pudo eliminar el usuario (no existe o es admin)');
        }
    } catch (PDOException $e) {
        header('Location: index.php?msg=Error SQL Usuario: ' . urlencode($e->getMessage()));
    }
    exit;
}

// --- LOGIC: SAVE/UPDATE USER (ADMIN) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_user'])) {
    $id = $_POST['id'] ?? null;
    $username = $_POST['usuario_form'] ?? '';
    $password = $_POST['password_form'] ?? '';
    
    if ($id) {
        // Prevent modifying the main 'admin' user
        $check = $pdo->prepare('SELECT usuario FROM usuarios WHERE id = ?');
        $check->execute([$id]);
        $current_user = $check->fetchColumn();

        if ($current_user === 'admin') {
            $msg = 'Error: No se permite modificar el usuario administrador principal.';
            header('Location: index.php?msg=' . urlencode($msg));
            exit;
        }

        if (!empty($password)) {
            $hash = hash('sha256', $password);
            $stmt = $pdo->prepare('UPDATE usuarios SET usuario = ?, password = ? WHERE id = ?');
            $stmt->execute([$username, $hash, $id]);
        } else {
            $stmt = $pdo->prepare('UPDATE usuarios SET usuario = ? WHERE id = ?');
            $stmt->execute([$username, $id]);
        }
        $msg = 'Administrador actualizado';
    } else {
        $hash = hash('sha256', $password);
        $stmt = $pdo->prepare('INSERT INTO usuarios (usuario, password, rol) VALUES (?, ?, "admin")');
        $stmt->execute([$username, $hash]);
        $msg = 'Nuevo administrador creado';
    }
    header('Location: index.php?msg=' . urlencode($msg));
    exit;
}

// --- LOGIC: REGISTER / UPDATE STUDENT WITH LOGIN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reg_student'])) {
    try {
        $pdo->beginTransaction();
        
        $usuario = $_POST['std_usuario'];
        $password = $_POST['std_password'];
        $ci = $_POST['std_ci'];
        $nombres = $_POST['std_nombres'];
        $apellidos = $_POST['std_apellidos'];
        $id = $_POST['std_id'] ?? null;

        // If ID is not provided, try to find student by CI to prevent duplicates
        if (!$id && !empty($ci)) {
            $stmt_find = $pdo->prepare('SELECT id FROM estudiantes WHERE ci = ?');
            $stmt_find->execute([$ci]);
            $id = $stmt_find->fetchColumn();
        }

        if ($id) {
            // Check if student already has a user account
            $stmt_u = $pdo->prepare('SELECT usuario_id FROM estudiantes WHERE id = ?');
            $stmt_u->execute([$id]);
            $usuario_id = $stmt_u->fetchColumn();

            if ($usuario_id) {
                // Update Existing User Account
                if (!empty($password)) {
                    $hash = hash('sha256', $password);
                    $pdo->prepare('UPDATE usuarios SET usuario = ?, password = ? WHERE id = ?')
                        ->execute([$usuario, $hash, $usuario_id]);
                } else {
                    $pdo->prepare('UPDATE usuarios SET usuario = ? WHERE id = ?')
                        ->execute([$usuario, $usuario_id]);
                }
            } else {
                // Create New User Account for existing student
                $hash = hash('sha256', $password);
                $stmt1 = $pdo->prepare('INSERT INTO usuarios (usuario, password, rol) VALUES (?, ?, "estudiante")');
                $stmt1->execute([$usuario, $hash]);
                $usuario_id = $pdo->lastInsertId();
                
                // Link it
                $pdo->prepare('UPDATE estudiantes SET usuario_id = ? WHERE id = ?')
                    ->execute([$usuario_id, $id]);
            }

            // Update Student Profile Details
            $stmt2 = $pdo->prepare('UPDATE estudiantes SET ci = ?, nombres = ?, apellidos = ? WHERE id = ?');
            $stmt2->execute([$ci, $nombres, $apellidos, $id]);
            $msg = 'Acceso actualizado para el estudiante existente';
        } else {
            // Completely New Student
            $hash = hash('sha256', $password);
            // 1. Create User Account
            $stmt1 = $pdo->prepare('INSERT INTO usuarios (usuario, password, rol) VALUES (?, ?, "estudiante")');
            $stmt1->execute([$usuario, $hash]);
            $usuario_id = $pdo->lastInsertId();

            // 2. Create Student Profile
            $stmt2 = $pdo->prepare('INSERT INTO estudiantes (ci, nombres, apellidos, usuario_id) VALUES (?, ?, ?, ?)');
            $stmt2->execute([$ci, $nombres, $apellidos, $usuario_id]);
            $msg = 'Nuevo estudiante registrado con éxito';
        }

        $pdo->commit();
        header('Location: index.php?msg=' . urlencode($msg));
    } catch (Exception $e) {
        $pdo->rollBack();
        header('Location: index.php?msg=Error: ' . urlencode($e->getMessage()));
    }
    exit;
}

$estudiantes = $pdo->query('
    SELECT e.*, p.carrera, r.indice_trimestre 
    FROM estudiantes e 
    LEFT JOIN pnfs p ON e.pnf_id = p.id 
    LEFT JOIN records_academicos r ON e.id = r.estudiante_id
    ORDER BY e.created_at DESC
')->fetchAll();

$administradores = $pdo->query('SELECT id, usuario FROM usuarios WHERE rol = "admin" ORDER BY id ASC')->fetchAll();

// --- LOGIC: FETCH FULL STUDENT DETAILS FOR MODAL ---
if (isset($_GET['view_details'])) {
    $sid = (int)$_GET['view_details'];
    $details['base'] = $pdo->prepare("SELECT * FROM estudiantes WHERE id = ?");
    $details['base']->execute([$sid]);
    $details['base'] = $details['base']->fetch();

    if ($details['base']) {
        $details['pnf'] = $pdo->prepare("SELECT * FROM pnfs WHERE id = ?");
        $details['pnf']->execute([$details['base']['pnf_id']]);
        $details['pnf'] = $details['pnf']->fetch();

        $details['residencia'] = $pdo->prepare("SELECT * FROM residencias WHERE estudiante_id = ?");
        $details['residencia']->execute([$sid]);
        $details['residencia'] = $details['residencia']->fetch();

        $details['trabajo'] = $pdo->prepare("SELECT * FROM trabajos WHERE estudiante_id = ?");
        $details['trabajo']->execute([$sid]);
        $details['trabajo'] = $details['trabajo']->fetch();

        $details['academico'] = $pdo->prepare("SELECT * FROM records_academicos WHERE estudiante_id = ?");
        $details['academico']->execute([$sid]);
        $details['academico'] = $details['academico']->fetch();

        $details['materias'] = $pdo->prepare("SELECT m.nombre, em.trimestre FROM estudiante_materias em JOIN materias m ON em.materia_id = m.id WHERE em.estudiante_id = ?");
        $details['materias']->execute([$sid]);
        $details['materias'] = $details['materias']->fetchAll();

        $details['familiares'] = $pdo->prepare("SELECT * FROM familiares WHERE estudiante_id = ?");
        $details['familiares']->execute([$sid]);
        $details['familiares'] = $details['familiares']->fetchAll();
    }
}

// --- LOGIC: GET DATA FOR EDITING STUDENT ---
if (isset($_GET['edit_student'])) {
    $stmt = $pdo->prepare('SELECT e.*, u.usuario FROM estudiantes e JOIN usuarios u ON e.usuario_id = u.id WHERE e.id = ?');
    $stmt->execute([$_GET['edit_student']]);
    $edit_student = $stmt->fetch();
}

// --- LOGIC: GET DATA FOR EDITING ADMIN ---
if (isset($_GET['edit_user'])) {
    $stmt = $pdo->prepare('SELECT id, usuario FROM usuarios WHERE id = ? AND usuario != "admin" AND rol = "admin"');
    $stmt->execute([$_GET['edit_user']]);
    $edit_admin = $stmt->fetch();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Administrativo</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        body { 
            background: linear-gradient(135deg, rgba(0,0,0,0.95), rgba(20,20,20,0.8)), url('../img/fondo.jpg'); 
            background-size: cover; background-position: center; background-attachment: fixed;
            min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: flex-start; padding: 40px 0;
        }
        .dashboard { 
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            width: 95%; max-width: 1200px; padding: 40px; border-radius: 28px; 
            box-shadow: 0 16px 48px rgba(0,0,0,0.4); border: 1px solid rgba(255, 255, 255, 0.3);
            margin-bottom: 40px;
        }
        h2 { 
            font-size: 2.2rem; font-weight: 800; 
            background: linear-gradient(90deg, #FF6600, #FF9D00);
            -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
            margin: 0; display: flex; align-items: center; gap: 15px;
        }
        h3 { color: #555; margin-bottom: 20px; font-weight: 700; border-bottom: 2px solid #FF6600; display: inline-block; padding-bottom: 5px; }
        
        table { width: 100%; border-collapse: separate; border-spacing: 0 12px; margin-bottom: 40px; }
        th { padding: 15px; color: #777; font-weight: 700; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1.5px; text-align: left; }
        td { padding: 20px 15px; background: rgba(255,255,255,0.6); transition: all 0.3s; }
        tr:hover td { background: #fff; transform: scale(1.01); box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
        td:first-child { border-radius: 16px 0 0 16px; }
        td:last-child { border-radius: 0 16px 16px 0; }
        
        .form-section { background: rgba(0,0,0,0.03); padding: 30px; border-radius: 20px; margin-bottom: 40px; border: 1px solid rgba(0,0,0,0.05); }
        .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; align-items: flex-end; }
        
        .badge { padding: 8px 14px; border-radius: 25px; font-size: 0.85rem; font-weight: 800; background: #e3f2fd; color: #1976d2; }
        .btn-action { padding: 8px 16px; border-radius: 10px; font-weight: 700; text-decoration: none; transition: all 0.2s; font-size: 0.85rem; }
        .btn-edit { background: #fff3e0; color: #FF6600; margin-right: 8px; }
        .btn-edit:hover { background: #FF6600; color: #fff; }
        .btn-del { background: #ffebee; color: #dc3545; }
        .btn-del:hover { background: #dc3545; color: #fff; }
        
        .btn-logout { background: #e74040; color: #fff; text-decoration:none; padding: 12px 26px; border-radius: 14px; font-weight: 700; transition: all 0.3s; box-shadow: 0 4px 15px rgba(231,64,64,0.3); }
        .btn-logout:hover { background: #c33030; transform: translateY(-2px); }
        
        .msg { background: #d4edda; color: #155724; padding: 12px; border-radius: 10px; margin-bottom: 20px; font-weight: 600; border-left: 5px solid #28a745; }

        /* Modal Styles */
        .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.6); backdrop-filter: blur(8px); z-index: 2000; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .modal-box { background: rgba(255,255,255,0.95); width: 100%; max-width: 900px; max-height: 90vh; overflow-y: auto; border-radius: 24px; padding: 40px; box-shadow: 0 20px 60px rgba(0,0,0,0.5); position: relative; }
        .modal-close { position: absolute; top: 20px; right: 20px; font-size: 2rem; cursor: pointer; color: #999; line-height: 1; }
        .details-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 20px; }
        .details-group { background: #f9f9f9; padding: 15px; border-radius: 15px; border: 1px solid #eee; }
        .details-group h4 { margin: 0 0 10px 0; font-size: 0.9rem; color: #FF6600; text-transform: uppercase; letter-spacing: 1px; }
        .details-group p { margin: 5px 0; font-size: 0.85rem; color: #444; }
        .details-group strong { color: #000; }
        .modal-box h2 { border-bottom: 2px solid #FF6600; padding-bottom: 10px; margin-bottom: 20px; }

        .btn-exp { background: #28a745; color: white !important; padding: 12px 20px; border-radius: 10px; text-decoration: none; font-weight: 700; margin-left: 10px; transition: 0.3s; }
        .btn-exp:hover { background: #218838; transform: translateY(-2px); }
        .btn-view { background: #e3f2fd; color: #1976d2; margin-right: 8px; }
        .btn-view:hover { background: #1976d2; color: #fff; }
    </style>
</head>
<body>
<script>document.addEventListener('DOMContentLoaded',function(){document.body.classList.add('page-visible');});</script>
    
    <div class="dashboard">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h2>🎓 <span>Gestión del Sistema</span></h2>
            <div>
                <a href="manage_alerts.php" class="btn-exp" style="background: #FF6600;">🔔 Gestionar Alertas</a>
                <a href="export_excel.php" class="btn-exp">📥 Exportar a Excel</a>
                <a href="../logout.php" class="btn-logout">Cerrar Sesión</a>
            </div>
        </div>

        <?php if(isset($_GET['msg'])): ?>
            <div class="msg">✨ <?php echo htmlspecialchars($_GET['msg']); ?></div>
        <?php endif; ?>

        <!-- SECCIÓN: GESTIÓN DE ADMINISTRADORES -->
        <div class="form-section">
            <h3 style="border-color: #0056b3;">👥 <?php echo $edit_admin ? 'Editar Administrador' : 'Registrar Administrador del Sistema'; ?></h3>
            <form method="POST">
                <input type="hidden" name="id" value="<?php echo $edit_admin['id'] ?? ''; ?>">
                <div class="grid-3">
                    <div>
                        <label>Nombre de Usuario</label>
                        <input type="text" name="usuario_form" value="<?php echo $edit_admin['usuario'] ?? ''; ?>" required>
                    </div>
                    <div>
                        <label>Contraseña <?php echo $edit_admin ? '(en blanco para no cambiar)' : ''; ?></label>
                        <input type="password" name="password_form" <?php echo $edit_admin ? '' : 'required'; ?>>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" name="save_user" class="btn-primary" style="padding: 12px 30px; width: 100%; background: #0056b3;">
                            <?php echo $edit_admin ? 'Actualizar' : 'Guardar Administrador'; ?>
                        </button>
                        <?php if($edit_admin): ?>
                            <a href="index.php" class="btn-secondary" style="padding: 12px 20px; text-decoration: none; align-self: center;">Cancelar</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <!-- SECCIÓN: REGISTRO / EDICIÓN DE ESTUDIANTE -->
        <div class="form-section" style="border-left: 5px solid #FF6600;">
            <h3>🎓 <?php echo $edit_student ? 'Editar Estudiante' : 'Registro de Estudiante con Acceso'; ?></h3>
            <form method="POST">
                <input type="hidden" name="std_id" value="<?php echo $edit_student['id'] ?? ''; ?>">
                <div class="grid-3">
                    <div>
                        <label>Cédula de Identidad</label>
                        <input type="text" name="std_ci" value="<?php echo $edit_student['ci'] ?? ''; ?>" placeholder="Ej: 25123456" required>
                    </div>
                    <div>
                        <label>Nombres</label>
                        <input type="text" name="std_nombres" value="<?php echo $edit_student['nombres'] ?? ''; ?>" required>
                    </div>
                    <div>
                        <label>Apellidos</label>
                        <input type="text" name="std_apellidos" value="<?php echo $edit_student['apellidos'] ?? ''; ?>" required>
                    </div>
                </div>
                <div class="grid-3" style="margin-top: 15px;">
                    <div>
                        <label>Usuario (Para login)</label>
                        <input type="text" name="std_usuario" value="<?php echo $edit_student['usuario'] ?? ''; ?>" required>
                    </div>
                    <div>
                        <label>Contraseña <?php echo $edit_student ? '(en blanco para mantener)' : ''; ?></label>
                        <input type="password" name="std_password" <?php echo $edit_student ? '' : 'required'; ?>>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" name="reg_student" class="btn-primary" style="padding: 12px 30px; width: 100%;">
                            <?php echo $edit_student ? 'Actualizar Estudiante' : 'Registrar Estudiante y Crear Cuenta'; ?>
                        </button>
                        <?php if($edit_student): ?>
                            <a href="index.php" class="btn-secondary" style="padding: 12px 20px; text-decoration: none; align-self: center;">Cancelar</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <!-- SECCIÓN: TABLA DE SOLICITUDES -->
        <h3>📋 Solicitudes de Becas</h3>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Estudiante</th>
                    <th>CI</th>
                    <th>Carrera</th>
                    <th>Indice</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($estudiantes as $e): ?>
                <tr>
                    <td><?php echo $e['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($e['nombres'] . ' ' . $e['apellidos']); ?></strong></td>
                    <td><?php echo htmlspecialchars($e['ci']); ?></td>
                    <td><?php echo htmlspecialchars($e['carrera'] ?? 'Sin asignar'); ?></td>
                    <td><span class="badge"><?php echo $e['indice_trimestre'] ?? '0.0'; ?></span></td>
                    <td style="white-space: nowrap;">
                        <a href="?view_details=<?php echo $e['id']; ?>" class="btn-action btn-view">Detalles</a>
                        <a href="edit_student.php?id=<?php echo $e['id']; ?>#acceso" class="btn-action btn-edit" style="background: #e1f5fe; color: #0288d1; border: 1px solid #b3e5fc;">Acceso</a>
                        <a href="edit_student.php?id=<?php echo $e['id']; ?>" class="btn-action btn-edit">Editar</a>
                        <a href="?delete_student=<?php echo $e['id']; ?>" class="btn-action btn-del" onclick="return confirm('¿Eliminar registro?')">Borrar</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- SECCIÓN: TABLA DE ADMINISTRADORES -->
        <h3>💻 Administradores del Sistema</h3>
        <table style="max-width: 600px;">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Usuario</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($administradores as $u): ?>
                <tr>
                    <td><?php echo $u['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($u['usuario']); ?></strong></td>
                    <td>
                        <?php if($u['usuario'] !== 'admin'): ?>
                            <a href="?edit_user=<?php echo $u['id']; ?>" class="btn-action btn-edit">Editar</a>
                            <a href="?delete_user=<?php echo $u['id']; ?>" class="btn-action btn-del" onclick="return confirm('¿Eliminar acceso a este administrador?')">Eliminar</a>
                        <?php else: ?>
                            <span style="color:#999; font-size: 0.8rem;">(Intocable)</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div style="margin-top: 40px; text-align: center;">
            <a href="../index.php" style="color: #666; text-decoration: none; font-weight: 700; font-size: 0.9rem;">← Volver al Portal de Inicio</a>
        </div>
    </div>

    <!-- MODAL DE DETALLES -->
    <?php if ($details): ?>
    <div class="modal-overlay" id="detailsModal">
        <div class="modal-box">
            <span class="modal-close" onclick="window.location.href='index.php'">&times;</span>
            <h2>Perfil Detallado: <?php echo htmlspecialchars($details['base']['nombres'].' '.$details['base']['apellidos']); ?></h2>
            
            <div class="details-grid">
                <!-- INF. BÁSICA -->
                <div class="details-group">
                    <h4>Información Personal</h4>
                    <p><strong>Cédula:</strong> <?php echo htmlspecialchars($details['base']['ci']); ?></p>
                    <p><strong>Nacimiento:</strong> <?php echo htmlspecialchars($details['base']['fecha_nacimiento'] ?? '-'); ?></p>
                    <p><strong>Estado Civil:</strong> <?php echo htmlspecialchars($details['base']['estado_civil'] ?? '-'); ?></p>
                    <p><strong>Teléfono:</strong> <?php echo htmlspecialchars($details['base']['telefono'] ?? '-'); ?></p>
                    <p><strong>Correo:</strong> <?php echo htmlspecialchars($details['base']['correo'] ?? '-'); ?></p>
                </div>

                <!-- PNF -->
                <div class="details-group">
                    <h4>Carrera y PNF</h4>
                    <?php if ($details['pnf']): ?>
                        <p><strong>Carrera:</strong> <?php echo htmlspecialchars($details['pnf']['carrera']); ?></p>
                        <p><strong>Trayecto/Trim:</strong> <?php echo htmlspecialchars($details['pnf']['trayecto'].' / '.$details['pnf']['trimestre_actual']); ?></p>
                        <p><strong>Código:</strong> <?php echo htmlspecialchars($details['pnf']['codigo_estudiante']); ?></p>
                    <?php else: ?>
                        <p>No hay datos registrados.</p>
                    <?php endif; ?>
                </div>

                <!-- RESIDENCIA -->
                <div class="details-group">
                    <h4>Residencia</h4>
                    <?php if ($details['residencia']): ?>
                        <p><strong>Tipo:</strong> <?php echo htmlspecialchars($details['residencia']['tipo_vivienda']); ?></p>
                        <p><strong>Dirección:</strong> <?php echo htmlspecialchars($details['residencia']['direccion']); ?></p>
                        <p><strong>Tel. Local:</strong> <?php echo htmlspecialchars($details['residencia']['telefono']); ?></p>
                    <?php else: ?>
                        <p>Sin información.</p>
                    <?php endif; ?>
                </div>

                <!-- TRABAJO -->
                <div class="details-group">
                    <h4>Situación Laboral</h4>
                    <?php if ($details['trabajo']): ?>
                        <p><strong>Lugar:</strong> <?php echo htmlspecialchars($details['trabajo']['lugar']); ?></p>
                        <p><strong>Ingreso:</strong> <?php echo htmlspecialchars($details['trabajo']['ingreso']); ?></p>
                        <p><strong>Aportador:</strong> <?php echo htmlspecialchars($details['trabajo']['aportador']); ?></p>
                    <?php else: ?>
                        <p>El estudiante no trabaja.</p>
                    <?php endif; ?>
                </div>

                <!-- ACADÉMICO -->
                <div class="details-group">
                    <h4>Record Académico</h4>
                    <?php if ($details['academico']): ?>
                        <p><strong>Índice:</strong> <span class="badge"><?php echo htmlspecialchars($details['academico']['indice_trimestre']); ?></span></p>
                        <p><strong>Mat. Inscritas:</strong> <?php echo htmlspecialchars($details['academico']['n_materias_inscritas']); ?></p>
                        <p><strong>Mat. Aprobadas:</strong> <?php echo htmlspecialchars($details['academico']['n_materias_aprobadas']); ?></p>
                    <?php else: ?>
                        <p>Sin récord.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- TABLA FAMILIARES -->
            <h3 style="margin-top:30px">Familiares</h3>
            <table style="font-size: 0.8rem;">
                <thead>
                    <tr><th>Nombre</th><th>Parentesco</th><th>Ocupación</th><th>Ingreso</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($details['familiares'] as $f): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($f['nombres'].' '.$f['apellidos']); ?></td>
                        <td><?php echo htmlspecialchars($f['parentesco']); ?></td>
                        <td><?php echo htmlspecialchars($f['ocupacion']); ?></td>
                        <td><strong><?php echo number_format($f['ingreso'], 2); ?> Bs</strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <!-- TABLA MATERIAS -->
            <h3>Materias Inscritas</h3>
            <div style="display:flex; flex-wrap: wrap; gap:10px;">
                <?php foreach ($details['materias'] as $m): ?>
                    <span class="badge" style="background:#f0f0f0; color:#444;">
                        <?php echo htmlspecialchars($m['nombre']); ?> (T<?php echo $m['trimestre']; ?>)
                    </span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // --- AUTO-FILL STUDENT DATA BY CI ---
        document.addEventListener('DOMContentLoaded', function() {
            const ciInput = document.querySelector('input[name="std_ci"]');
            const nombresInput = document.querySelector('input[name="std_nombres"]');
            const apellidosInput = document.querySelector('input[name="std_apellidos"]');
            const idInput = document.querySelector('input[name="std_id"]');

            if (ciInput) {
                ciInput.addEventListener('input', function() {
                    const ci = this.value.trim();
                    
                    // Only search if CI has 7 or more digits (standard range for Venezuelan CI)
                    if (ci.length >= 7) {
                        fetch(`ajax_get_student.php?ci=${ci}`)
                            .then(response => response.json())
                            .then(data => {
                                if (data.found) {
                                    if(idInput) idInput.value = data.id;
                                    nombresInput.value = data.nombres;
                                    apellidosInput.value = data.apellidos;
                                    // Optional: maybe highlight the fields briefly to show they were auto-filled
                                    nombresInput.style.backgroundColor = '#fff9c4';
                                    apellidosInput.style.backgroundColor = '#fff9c4';
                                    setTimeout(() => {
                                        nombresInput.style.backgroundColor = '';
                                        apellidosInput.style.backgroundColor = '';
                                    }, 2000);
                                }
                            })
                            .catch(error => console.error('Error fetching student data:', error));
                    }
                });
            }
        });
    </script>

    <?php include 'footer.php'; ?>
</body>
</html>
