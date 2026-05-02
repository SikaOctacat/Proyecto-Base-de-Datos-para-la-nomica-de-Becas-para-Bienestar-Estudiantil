<?php
ob_start();
session_start(); // Asegúrate de iniciar sesión antes de cualquier chequeo
require '../db.php';

// Prevenir el almacenamiento en caché para seguridad de datos administrativos
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");

// Verificación de sesión y rol de administrador
if (!isset($_SESSION['user']) || (isset($_SESSION['rol']) && $_SESSION['rol'] !== 'admin')) {
    header('Location: ../login.php');
    exit;
}

// Inicialización de variables de estado
$edit_admin = null;
$edit_student = null;
$details = null;
$msg = $_GET['msg'] ?? null;

// --- LÓGICA: ELIMINAR ESTUDIANTE Y SU CUENTA ---
if (isset($_GET['delete_student'])) {
    try {
        $ci = $_GET['delete_student']; 
        
        // 1. Obtenemos el usuario_id para no dejar la cuenta de acceso huérfana
        $stmt = $pdo->prepare('SELECT usuario_id FROM estudiante WHERE ci = ?');
        $stmt->execute([$ci]);
        $usuario_id = $stmt->fetchColumn();

        // 2. Borramos al estudiante 
        // (Esto borrará automáticamente familiar, residencia y record_academico por el CASCADE de la DB)
        $stmt_del = $pdo->prepare('DELETE FROM estudiante WHERE ci = ?');
        $stmt_del->execute([$ci]);

        // 3. Ahora borramos la cuenta de acceso en la tabla usuarios
        if ($usuario_id) {
            $pdo->prepare('DELETE FROM usuarios WHERE id = ?')->execute([$usuario_id]);
        }
        
        header('Location: index.php?msg=' . urlencode('Estudiante y cuenta de acceso eliminados correctamente'));
    } catch (PDOException $e) {
        header('Location: index.php?msg=Error SQL: ' . urlencode($e->getMessage()));
    }
    exit;
}

// --- LÓGICA: ELIMINAR USUARIO ADMINISTRADOR SECUNDARIO ---
if (isset($_GET['delete_user'])) {
    try {
        $id = (int)$_GET['delete_user'];
        // No permite borrar al 'admin' maestro por seguridad
        $stmt = $pdo->prepare('DELETE FROM usuarios WHERE id = ? AND usuario != "admin"');
        $stmt->execute([$id]);
        
        $mensaje = ($stmt->rowCount() > 0) ? 'Usuario eliminado' : 'Cuidado: No se pudo eliminar el usuario';
        header('Location: index.php?msg=' . urlencode($mensaje));
    } catch (PDOException $e) {
        header('Location: index.php?msg=Error SQL: ' . urlencode($e->getMessage()));
    }
    exit;
}

// --- LÓGICA: GUARDAR/ACTUALIZAR ADMINISTRADOR ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_user'])) {
    $id = $_POST['id'] ?? null;
    $username = $_POST['usuario_form'] ?? '';
    $password = $_POST['password_form'] ?? '';
    
    if ($id) {
        // Validación para no editar el usuario maestro 'admin'
        $check = $pdo->prepare('SELECT usuario FROM usuarios WHERE id = ?');
        $check->execute([$id]);
        if ($check->fetchColumn() === 'admin') {
            header('Location: index.php?msg=' . urlencode('Error: El admin principal no es editable.'));
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


$query = "SELECT e.ci, e.usuario_id, e.nombre1, e.apellido_paterno, e.carrera,e.cod_est, r.ira_anterior
          FROM estudiante e 
          LEFT JOIN record_academico r ON e.ci = r.ci_estudiante";
$stmt = $pdo->query($query);
$estudiantes = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Obtener lista de administradores
$query_admins = "SELECT id, usuario FROM usuarios WHERE rol = 'admin'";
$stmt_admins = $pdo->query($query_admins);
$administradores = $stmt_admins->fetchAll(PDO::FETCH_ASSOC);

// Obtener historial de registros
$query_bitacora = "SELECT b.id, b.usuario_id, u.usuario, b.accion, b.tabla_afectada, b.detalles, b.fecha 
                    FROM bitacora b 
                    LEFT JOIN usuarios u ON b.usuario_id = u.id 
                    ORDER BY b.fecha DESC LIMIT 100";
$stmt_bitacora = $pdo->query($query_bitacora);
$registros_bitacora = $stmt_bitacora->fetchAll(PDO::FETCH_ASSOC);

// LÓGICA DE DETALLES (MODAL) Y EDICIÓN (FORMULARIOS)
if (isset($_GET['view_details'])) {
    $ci = $_GET['view_details'];
    
    // Datos básicos de la tabla 'estudiante'
    $stmt = $pdo->prepare("SELECT * FROM estudiante WHERE ci = ?");
    $stmt->execute([$ci]);
    $base = $stmt->fetch(PDO::FETCH_ASSOC);

    // Datos de residencia
    $stmt = $pdo->prepare("SELECT * FROM residencia WHERE ci_estudiante = ?");
    $stmt->execute([$ci]);
    $residencia = $stmt->fetch(PDO::FETCH_ASSOC);

    // Carga familiar - Verifica si es 'familiares' o 'familiar' en tu DB
    $stmt = $pdo->prepare("SELECT * FROM familiar WHERE ci_estudiante = ?");
    $stmt->execute([$ci]);
    $familiares = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $details = ['base' => $base, 'residencia' => $residencia, 'familiares' => $familiares];
}
// (Aquí terminamos la lógica de carga para edición de admin/estudiante)
if (isset($_GET['edit_student'])) {
    $ci_edit = $_GET['edit_student'];
    // Cambiamos "estudiantes" por "estudiante" y buscamos por "ci"
    $stmt = $pdo->prepare("SELECT * FROM estudiante WHERE ci = ?");
    $stmt->execute([$ci_edit]);
    $edit_student = $stmt->fetch(PDO::FETCH_ASSOC);
}
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Administrativo - Sistema de Becas</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        :root {
            --primary: #FF6600;
            --primary-dark: #e65c00;
            --secondary: #0056b3;
            --success: #28a745;
            --danger: #dc3545;
            --glass: rgba(255, 255, 255, 0.88);
            --border: rgba(255, 255, 255, 0.3);
        }

        body { 
            background: linear-gradient(135deg, rgba(0,0,0,0.92), rgba(20,20,20,0.85)), url('../img/fondo.jpg'); 
            background-size: cover; 
            background-position: center; 
            background-attachment: fixed;
            min-height: 100vh; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            padding: 40px 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .dashboard { 
            background: var(--glass);
            backdrop-filter: blur(16px) saturate(180%);
            -webkit-backdrop-filter: blur(16px) saturate(180%);
            width: 100%; 
            max-width: 1200px; 
            padding: 40px; 
            border-radius: 28px; 
            box-shadow: 0 16px 48px rgba(0,0,0,0.4); 
            border: 1px solid var(--border);
            margin-bottom: 40px;
        }

        
        .password-wrapper {
            position: relative;
            width: 100%;
        }
        .btn-view-pass {
            position: absolute;
            right: 12px;
            top: 15px; /* Ajustado para centrar con el input */
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
            transform: none !important; /* Evitamos que herede el efecto de los botones normales */
            box-shadow: none !important;
        }

        /* Tipografía y Títulos */
        h2 { 
            font-size: 2.2rem; font-weight: 800; 
            background: linear-gradient(90deg, var(--primary), #FF9D00);
            -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent;
            margin: 0; display: flex; align-items: center; gap: 15px;
        }
        
        h3 { 
            color: #444; margin: 30px 0 20px; font-weight: 700; 
            border-bottom: 3px solid var(--primary); 
            display: inline-block; padding-bottom: 5px; 
        }

        /* Tablas Estilizadas */
        table { width: 100%; border-collapse: separate; border-spacing: 0 10px; margin-top: 10px; }
        th { 
            padding: 15px; color: #666; font-weight: 700; 
            text-transform: uppercase; font-size: 0.75rem; 
            letter-spacing: 1px; text-align: left; 
        }
        td { padding: 18px 15px; background: rgba(255,255,255,0.5); transition: 0.3s; border: none; }
        tr:hover td { background: #fff; transform: translateY(-2px); box-shadow: 0 6px 15px rgba(0,0,0,0.05); }
        td:first-child { border-radius: 12px 0 0 12px; }
        td:last-child { border-radius: 0 12px 12px 0; }

        /* Botones y Acciones */
        .btn-action { 
            padding: 8px 14px; border-radius: 8px; font-weight: 700; 
            text-decoration: none; font-size: 0.8rem; transition: 0.2s;
            display: inline-block;
        }
        .btn-view { background: #e3f2fd; color: #1976d2; }
        .btn-view:hover { background: #1976d2; color: #fff; }
        .btn-edit { background: #fff3e0; color: var(--primary); }
        .btn-edit:hover { background: var(--primary); color: #fff; }
        .btn-del { background: #ffebee; color: var(--danger); }
        .btn-del:hover { background: var(--danger); color: #fff; }

        .btn-logout { 
            background: var(--danger); color: #fff; padding: 12px 24px; 
            border-radius: 12px; font-weight: 700; text-decoration: none;
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3); transition: 0.3s;
        }
        .btn-logout:hover { background: #bd2130; transform: translateY(-2px); }

        /* Formularios */
        .form-section { 
            background: rgba(0,0,0,0.04); padding: 25px; 
            border-radius: 20px; margin-bottom: 35px; border: 1px solid rgba(0,0,0,0.05); 
        }
        .grid-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; }
        
        input { 
            width: 100%; padding: 12px; border-radius: 10px; border: 1px solid #ccc;
            margin-top: 5px; font-size: 0.95rem;
        }
        label { font-weight: 600; font-size: 0.85rem; color: #555; }

        /* Modales */
        .modal-overlay { 
            position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
            background: rgba(0,0,0,0.7); backdrop-filter: blur(6px); 
            z-index: 9999; display: flex; justify-content: center; align-items: center; padding: 20px;
        }
        .modal-box { 
            background: #fff; width: 100%; max-width: 900px; max-height: 90vh; 
            border-radius: 24px; padding: 35px; overflow-y: auto; position: relative;
        }
        
        .msg { 
            background: #d4edda; color: #155724; padding: 15px; border-radius: 12px; 
            margin-bottom: 25px; border-left: 6px solid var(--success); font-weight: 600; 
        }

        .badge { 
            padding: 5px 12px; border-radius: 20px; font-weight: 800; font-size: 0.75rem;
            background: #e1f5fe; color: #0288d1;
        }
        /* Pestañas (Tabs) */
        .tabs { 
            display: flex; gap: 10px; margin-top: 30px; margin-bottom: 20px; 
            border-bottom: 2px solid var(--border); padding-bottom: 0;
        }
        .tab-btn {
            background: rgba(255,255,255,0.3); border: none; padding: 12px 25px; 
            font-size: 1.05rem; font-weight: 700; color: #666; cursor: pointer; 
            transition: 0.3s; border-radius: 12px 12px 0 0;
        }
        .tab-btn.active { 
            background: #fff; color: var(--primary); 
            box-shadow: 0 -4px 10px rgba(0,0,0,0.05);
            border-bottom: 3px solid var(--primary);
        }
        .tab-btn:hover:not(.active) { color: var(--primary-dark); background: rgba(255,255,255,0.6); }
        
        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeIn 0.4s ease; }
        
        @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }

    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="dashboard">
        <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <h2>🎓 <span>Panel de Control</span></h2>
            <div style="display: flex; gap: 12px;">
                <a href="export_excel.php" class="btn-action" style="background: var(--success); color:#fff; padding: 12px 20px;">📥 Excel</a>
                <a href="../logout.php" class="btn-logout" onclick="return confirm('¿Cerrar sesión?')">Cerrar Sesión</a>
            </div>
        </header>

        <?php if($msg): ?>
            <div class="msg">✨ <?php echo htmlspecialchars($msg); ?></div>
        <?php endif; ?>


        <!-- Controles de Pestañas -->
        <div class="tabs">
            <button class="tab-btn active" onclick="openTab('tab-estudiantes', this)">📋 Listado de Solicitudes</button>
            <button class="tab-btn" onclick="openTab('tab-admins', this)">🛡️ Administradores</button>
            <button class="tab-btn" onclick="openTab('tab-registros', this)">🕒 Bitacora</button>
        </div>

        <!-- PESTAÑA: ESTUDIANTES -->
        <div id="tab-estudiantes" class="tab-content active">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin:0; border:none;">Registros Actuales</h3>
                <a href="../register.php" class="btn-action" style="background: var(--primary); color:white; padding: 10px 20px; border-radius: 12px;">
                    <i class="fas fa-user-plus"></i> Nuevo Estudiante
                </a>
            </div>
            
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Estudiante</th>
                            <th>Cédula</th>
                            <th>Carrera</th>
                            <th>IRA</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($estudiantes as $e): ?>
                        <tr>
                            <td><?php echo $e['usuario_id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($e['nombre1'] . ' ' . $e['apellido_paterno']); ?></strong></td>
                            <td><?php echo htmlspecialchars($e['ci']); ?></td>
                            <td><?php echo htmlspecialchars($e['carrera'] ?? 'No asignada'); ?></td>
                            <td><span class="badge"><?php echo htmlspecialchars($e['ira_anterior'] ?? '0.0'); ?></span></td>
                            <td style="white-space: nowrap;">
                                <a href="?view_details=<?php echo $e['ci']; ?>" class="btn-action btn-view">Detalles</a>
                                <a href="edit_student.php?id=<?php echo $e['ci']; ?>" class="btn-action btn-edit">Editar</a>
                                <a href="?delete_student=<?php echo $e['ci']; ?>" class="btn-action btn-del" onclick="return confirm('¿Eliminar?')">Borrar</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- PESTAÑA: ADMINISTRADORES -->
        <div id="tab-admins" class="tab-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin:0; border:none;">Gestión de Staff</h3>
                <button onclick="toggleAdminForm()" class="btn-action" style="background: var(--secondary); color:white; padding: 10px 20px; border-radius: 12px; border:none; cursor:pointer;">
                    <i class="fas fa-shield-alt"></i> <?php echo $edit_admin ? 'Editando...' : 'Nuevo Administrador'; ?>
                </button>
            </div>

            <!-- Formulario de Admin (Oculto por defecto a menos que se esté editando) -->
            <div id="admin-form-container" class="form-section" style="display: <?php echo $edit_admin ? 'block' : 'none'; ?>; margin-bottom: 20px; background: rgba(0,0,0,0.02);">
                <form method="POST">
                    <input type="hidden" name="id" value="<?php echo $edit_admin['id'] ?? ''; ?>">
                    <div class="grid-3">
                        <div>
                            <label>Usuario</label>
                            <input type="text" name="usuario_form" value="<?php echo $edit_admin['usuario'] ?? ''; ?>" required>
                        </div>
                        <div>
                            <label>Contraseña (Dejar vacia para no cambiar)</label>
                            <div class="password-wrapper">
                                <input type="password" name="password_form" id="pass_admin" <?php echo $edit_admin ? '' : 'required'; ?>>
                                <button type="button" class="btn-view-pass" onclick="togglePassword('pass_admin', this)"><i class="fas fa-eye"></i></button>
                            </div>
                        </div>
                        <div style="display: flex; gap: 10px; align-items: flex-end;">
                            <button type="submit" name="save_user" class="btn-action" style="background: var(--success); color:white; border:none; width:100%; height: 45px; cursor:pointer;">
                                Guardar
                            </button>
                            <a href="index.php" class="btn-action" style="background: var(--danger); color:white;display:flex; align-items:center; justify-content:center; width: 50%; height: 45%; box-shadow: 0 4px 12px rgba(220, 53, 69, 0.2);" onclick="localStorage.setItem('activeTab', 'tab-admins')">
                                Cancelar
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario</th>
                            <th>Rol</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($administradores as $admin): ?>
                        <tr>
                            <td><?php echo $admin['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($admin['usuario']); ?></strong></td>
                            <td><span class="badge" style="background: #e8f5e9; color: #2e7d32;">Admin</span></td>
                            <td style="white-space: nowrap;">
                                <a href="?edit_user=<?php echo $admin['id']; ?>" class="btn-action btn-edit" onclick="localStorage.setItem('activeTab', 'tab-admins')">Editar</a>
                                <?php if($admin['usuario'] !== 'admin'): ?>
                                    <a href="?delete_user=<?php echo $admin['id']; ?>" class="btn-action btn-del" onclick="return confirm('¿Eliminar?')">Borrar</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- PESTAÑA: REGISTROS (Añadir justo antes del cierre de div class="dashboard") -->
        <div id="tab-registros" class="tab-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                <h3 style="margin:0; border:none;">Registro de Movimientos</h3>
            </div>
            
            <div style="overflow-x: auto;">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Usuario (ID)</th>
                            <th>Acción</th>
                            <th>Tabla</th>
                            <th>Detalles</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($registros_bitacora as $reg): ?>
                        <tr>
                            <td><?php echo $reg['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($reg['usuario'] ?? 'Sistema/Desconocido'); ?></strong> <span style="color:#888; font-size:0.8em;">(#<?php echo $reg['usuario_id']; ?>)</span></td>
                            <td><span class="badge" style="background: #e3f2fd; color: #1976d2;"><?php echo htmlspecialchars($reg['accion']); ?></span></td>
                            <td><?php echo htmlspecialchars($reg['tabla_afectada']); ?></td>
                            <td style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($reg['detalles']); ?>">
                                <?php echo htmlspecialchars($reg['detalles']); ?>
                            </td>
                            <td><?php echo htmlspecialchars($reg['fecha']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    
    </div> <?php if ($details): ?>
    <div class="modal-overlay">
        <div class="modal-box">
            <a href="index.php" style="position:absolute; top:20px; right:20px; font-size:24px; color:#999; text-decoration:none;">&times;</a>
            <h2 style="margin-bottom:25px; color: #333;">
                Perfil: <?php 
                    $nombre_completo = htmlspecialchars($details['base']['nombre1']);
                    if (!empty($details['base']['nombre2'])) {
                        $nombre_completo .= ' ' . htmlspecialchars($details['base']['nombre2']);
                    }
                    $nombre_completo .= ' ' . htmlspecialchars($details['base']['apellido_paterno'] . ' ' . $details['base']['apellido_materno']);
                    echo $nombre_completo;
                ?>
            </h2>

            <div class="grid-3">
                <div style="background:#f8f9fa; padding:15px; border-radius:12px; border: 1px solid #eee;">
                    <h4 style="color:var(--primary); margin-top:0; border-bottom: 1px solid #ddd;">📍 Personal</h4>
                    <p><strong>CI:</strong> <?php echo $details['base']['ci']; ?></p>
                    <p><strong>Email:</strong> <?php echo $details['base']['correo'] ?? 'N/P'; ?></p>
                    <p><strong>Teléfono:</strong> <?php echo $details['base']['tel_estudiante'] ?? 'N/P'; ?></p>
                    <p><strong>Estado Civil:</strong> <?php echo ucfirst($details['base']['edo_civil'] ?? 'N/P'); ?></p>
                    <p><strong>Edad:</strong> <?php echo $details['base']['edad'] ?? 'N/P'; ?> años</p>
                    <p><strong>F. Nacimiento:</strong> <?php echo $details['base']['f_nac'] ?? 'N/P'; ?></p>
                </div>

                <div style="background:#f8f9fa; padding:15px; border-radius:12px; border: 1px solid #eee;">
                    <h4 style="color:var(--primary); margin-top:0; border-bottom: 1px solid #ddd;">🎓 Académico</h4>
                    <p><strong>Carrera:</strong> <?php echo ucfirst($details['base']['carrera'] ?? 'Informatica'); ?></p>
                    <p><strong>Trayecto:</strong> <?php echo $details['base']['trayecto'] ?? '-'; ?></p>
                    <p><strong>Trimestre:</strong> <?php echo $details['base']['trimestre'] ?? '-'; ?></p>
                    <p><strong>IRA Anterior:</strong> <span class="badge"><?php echo $details['base']['ira_anterior'] ?? '0.0'; ?></span></p>
                    <p><strong>Codigo estudiante:</strong> <?php echo ucfirst($details['base']['cod_est'] ?? '-'); ?></p>
                    <p><strong>Estatus:</strong> <?php echo ucfirst($details['base']['estatus_estudio'] ?? '-'); ?></p>
                </div>

                <div style="background:#f8f9fa; padding:15px; border-radius:12px; border: 1px solid #eee;">
                    <h4 style="color:var(--primary); margin-top:0; border-bottom: 1px solid #ddd;">🏠 Vivienda</h4>
                    <p><strong>Tipo:</strong> <?php echo ucfirst($details['residencia']['t_viv'] ?? 'N/P'); ?></p>
                    <p><strong>Ubicación:</strong> <?php echo ucfirst($details['residencia']['t_loc'] ?? 'N/P'); ?></p>
                    <p><strong>Municipio:</strong> <?php echo $details['residencia']['municipio_res'] ?? 'N/P'; ?></p>
                    <p><strong>Dirección:</strong> <?php echo $details['residencia']['dir_local'] ?? 'N/P'; ?></p>
                </div>
            </div>

            <h3 style="margin-top:30px; border-bottom: 2px solid var(--primary); padding-bottom: 5px;">👥 Carga Familiar Detallada</h3>
            <div style="overflow-x: auto;">
                <table style="font-size:0.85rem; width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background:#eee; text-align: left;">
                            <th style="padding: 10px;">Nombre y Apellido</th>
                            <th>Parentesco</th>
                            <th>Edad</th>
                            <th>Instrucción</th>
                            <th>Ocupación</th>
                            <th>Ingresos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($details['familiares'] as $f): ?>
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 10px;"><?php echo htmlspecialchars($f['f_nom'] . ' ' . $f['f_ape']); ?></td>
                            <td><?php echo htmlspecialchars($f['f_par']); ?></td>
                            <td><?php echo htmlspecialchars($f['f_eda']); ?> años</td>
                            <td><?php echo htmlspecialchars($f['f_ins']); ?></td>
                            <td><?php echo htmlspecialchars($f['f_ocu']); ?></td>
                            <td style="font-weight: bold; color: #2e7d32;"><?php echo number_format($f['f_ing'], 2); ?> Bs</td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div style="margin-top: 25px; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div style="background:#fff3e0; padding:15px; border-radius:12px;">
                    <h4 style="margin-top:0; color:#e65100;">🆔 Beneficios y Otros</h4>
                    <p><strong>Tipo Beneficio:</strong> <?php echo $details['base']['tipo_beneficio'] ?? 'N/P'; ?></p>
                    <p><strong>Carnet Patria:</strong> <?php echo $details['base']['C_Patria'] ?? 'No posee'; ?></p>
                    <p><strong>Viaja:</strong> <?php echo ucfirst($details['base']['viaja'] ?? 'No'); ?></p>
                </div>
                <div style="background:#f1f8e9; padding:15px; border-radius:12px;">
                    <h4 style="margin-top:0; color:#2e7d32;">📝 Observaciones</h4>
                    <p><?php echo !empty($details['base']['observaciones']) ? $details['base']['observaciones'] : 'Sin observaciones adicionales.'; ?></p>
                </div>
            </div>
            
            <div style="text-align:right; margin-top:20px;">
                <a href="index.php" class="btn-logout" style="background:#666;">Cerrar Detalles</a>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>

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

        // Función para cambiar entre pestañas
        function openTab(tabId, btn) {
            document.querySelectorAll('.tab-content').forEach(el => el.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(el => el.classList.remove('active'));
            
            document.getElementById(tabId).classList.add('active');
            if(btn) btn.classList.add('active');
            
            // Guardamos en el navegador cuál está abierta
            localStorage.setItem('activeTab', tabId);
        }

        // Al cargar la página, restauramos la pestaña guardada
        document.addEventListener("DOMContentLoaded", function() {
            const savedTab = localStorage.getItem('activeTab');
            
            // Si hay una pestaña guardada (y no estamos viendo detalles de un estudiante)
            // la abrimos automáticamente.
            if (savedTab && !window.location.search.includes('view_details')) {
                const targetBtn = document.querySelector(`button[onclick*="${savedTab}"]`);
                if (targetBtn) {
                    openTab(savedTab, targetBtn);
                }
            }
        });

        function toggleAdminForm() {
            const form = document.getElementById('admin-form-container');
            form.style.display = (form.style.display === 'none') ? 'block' : 'none';
        }
    </script>
    <?php include '../footer.php'; ?>
</body>
</html>