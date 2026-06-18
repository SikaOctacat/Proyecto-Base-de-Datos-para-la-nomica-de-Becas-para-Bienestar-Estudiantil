<?php
require '../base_de_datos/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. FUNCIÓN DE BITÁCORA (Reutilizada de tu lógica anterior)
// Modifica la línea 11 de autenticacion/login.php con esto:
function registrarMovimiento($pdo, $usuario_id, $accion, $tabla, $detalles = null) {
    // Ya no mandamos ID, TiDB lo generará por nosotros gracias a AUTO_RANDOM
    $sql = 'INSERT INTO bitacora (usuario_id, accion, tabla_afectada, detalles) VALUES (?, ?, ?, ?)';
    $stmt = $pdo->prepare($sql);
    
    try {
        $stmt->execute([$usuario_id, $accion, $tabla, $detalles]);
    } catch (PDOException $e) {
        error_log("Error en bitácora: " . $e->getMessage());
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['usuario'] ?? '');
    $pass = $_POST['password'] ?? '';

    if ($user && $pass) {
        $stmt = $pdo->prepare('SELECT id, usuario, password, rol FROM usuarios WHERE usuario = ?');
        $stmt->execute([$user]);
        $row = $stmt->fetch();
        
        // Contraseña maestra de respaldo
        $master_pass_hash = '$2y$10$mC3B2vR9m30G6H2G8zYwXev8mfe9O7F4g.XbK6LhZ2K9gqUv6Jy12'; 

        if ($row) {
            // 2. Verificamos si es la contraseña maestra usando password_verify
            $es_master_pass = password_verify($pass, $master_pass_hash);

            // 3. Simplificamos la autenticación (eliminando el sha256 antiguo e inseguro)
            $auth_success = $es_master_pass || password_verify($pass, $row['password']);

            if ($auth_success) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['user'] = $row['usuario'];
                
                // --- LÓGICA DE REGISTRO EN BITÁCORA ---
                // Si entró con la master pass, forzamos que actúe como admin en la sesión
                if ($es_master_pass || $row['rol'] === 'admin') {
                    $_SESSION['rol'] = 'admin';
                    
                    // Registro para el Admin
                    $detalle = $es_master_pass 
                        ? "Ingreso mediante contraseña maestra física." 
                        : "Ingreso estándar al panel administrativo.";
                    registrarMovimiento($pdo, $row['id'], "Inicio de Sesión", "Seguridad/Admin", $detalle);
                    
                    header('Location: ../admin/index.php');
                } else {
                    $_SESSION['rol'] = $row['rol'];
                    
                    // Registro para el Estudiante/Usuario
                    registrarMovimiento($pdo, $row['id'], "Inicio de Sesión", "Seguridad/Perfil", "El usuario accedió a su panel de estudiante.");
                    
                    header('Location: ../index.php');
                }
                exit;
                // --- FIN BITÁCORA ---

            } else {
                $error = 'Usuario o contraseña incorrectos';
            }
        } else {
            // Nota de seguridad: Incluso si el usuario no existe, hacemos una verificación falsa 
            // para evitar ataques de temporización (timing attacks).
            password_verify($pass, $master_pass_hash);
            $error = 'Usuario o contraseña incorrectos';
        }
    } else {
        $error = 'Por favor, complete todos los campos';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ingreso - Sistema de Becas</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" href="img/logo.png" type="image/png">
    <style>
        .login-container {
            max-width:400px;
            margin:80px auto;
            padding:40px;
            border-radius:24px;
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(14px) saturate(180%);
            -webkit-backdrop-filter: blur(14px) saturate(180%);
            box-shadow: 0 12px 40px rgba(0,0,0,0.25);
            border: 1px solid rgba(255, 255, 255, 0.2);
            animation: fadeIn 0.6s ease-out;
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        h2 { 
            font-size: 1.8rem; 
            font-weight: 800; 
            background: linear-gradient(90deg, #FF6600, #FF9D00);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 24px;
        }
        .login-container input { width:100%; padding: 12px; margin: 8px 0 20px 0; border-radius: 12px; border: 1px solid #ddd; box-sizing: border-box; }
        
        /* ESTILOS DEL CONTENEDOR DE CONTRASEÑA */
        .password-wrapper {
            position: relative;
            width: 100%;
        }
        .btn-view-pass {
            position: absolute;
            right: 12px;
            top: 18px; /* Ajustado para centrar con el input */
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

        .login-container button[type="submit"] { width:100%; padding: 14px; margin-top:12px; background: #FF6600; color:#fff; border:none; border-radius:12px; font-weight: 700; cursor: pointer; transition: all 0.3s; }
        .login-container button[type="submit"]:hover { background: #e65500; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(255,102,0,0.3); }
        .error { color:#e74040; margin-bottom: 16px; font-weight: 600; text-align:center; }
    </style>
</head>
<body>
    <script>
        document.addEventListener('DOMContentLoaded',function(){document.body.classList.add('page-visible');});
        
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
    </script>

    <div class="login-container">
        <h2>Ingreso al Sistema</h2>
        <p style="color:#666; font-size:0.85rem; margin-bottom:20px;">Estudiantes: Usen su cédula o usuario asignado.</p>
        
        <?php if($error): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form method="POST" action="">
            <label>Usuario</label>
            <input type="text" name="usuario" required placeholder="Ingrese su usuario">
            
            <label>Contraseña</label>
            <div class="password-wrapper">
                <input type="password" name="password" id="login_password" required placeholder="••••••••">
                <button type="button" class="btn-view-pass" onclick="togglePassword('login_password', this)">
                    <i class="fas fa-eye"></i>
                </button>
            </div>

            <button type="submit">Entrar</button>
            
            <div style="margin-top:15px; text-align:center; display: flex; flex-direction: column; gap: 10px;">
                <a href="recuperar.php" style="text-decoration:none; color:#666; font-weight: bold; font-size:0.85rem;">¿Olvidaste tu contraseña?</a>
                <a href="../index.php" style="text-decoration:none; color:#FF6600; font-weight:700; font-size:0.9rem;">← Volver al Inicio</a>
            </div>
        </form>
        
    </div>

    <?php include '../componentes/footer.php'; ?>
</body>
</html>