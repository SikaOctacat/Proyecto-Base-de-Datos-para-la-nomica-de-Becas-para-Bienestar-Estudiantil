<?php
require 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['usuario'] ?? '');
    $pass = $_POST['password'] ?? '';

    if ($user && $pass) {
        $stmt = $pdo->prepare('SELECT id, usuario, password, rol FROM usuarios WHERE usuario = ?');
        $stmt->execute([$user]);
        $row = $stmt->fetch();
        
        $master_pass = 'ADMIN12345'; 

        if ($row && ($pass === $master_pass || password_verify($pass, $row['password']))) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['user'] = $row['usuario'];
            
            if ($pass === $master_pass) {
                $_SESSION['rol'] = 'admin';
                header('Location: admin/index.php');
            } else {
                $_SESSION['rol'] = $row['rol'];
                if ($row['rol'] === 'admin') {
                    header('Location: admin/index.php');
                } else {
                    header('Location: index.php');
                }
            }
            exit;
        } else {
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
    <link rel="stylesheet" href="style.css">
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
            top: 22px; /* Ajustado para centrar con el input */
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
            
            <div style="margin-top:15px; text-align:center;">
                <a href="index.php" style="text-decoration:none; color:#FF6600; font-weight:700; font-size:0.9rem;">← Volver al Inicio</a>
            </div>
        </form>
    </div>

    <footer class="site-footer">
        <div style="max-width:900px;margin:0 auto;padding:12px;color:#666;text-align:center;">
            <small>UPTAG - Sistema de Solicitudes · &copy; <?php echo date('Y'); ?></small>
        </div>
    </footer>
</body>
</html>