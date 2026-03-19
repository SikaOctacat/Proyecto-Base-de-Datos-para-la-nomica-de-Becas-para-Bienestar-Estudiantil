<?php
require 'db.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['usuario'] ?? '';
    $pass = $_POST['password'] ?? '';

    if ($user && $pass) {
        $stmt = $pdo->prepare('SELECT id, usuario, password, rol FROM usuarios WHERE usuario = ?');
        $stmt->execute([$user]);
        $row = $stmt->fetch();
        if ($row && hash('sha256', $pass) === $row['password']) {
            $_SESSION['user'] = $row['usuario'];
            $_SESSION['rol'] = $row['rol'];
            
            if ($row['rol'] === 'admin') {
                header('Location: admin/index.php');
            } else {
                header('Location: index.php');
            }
            exit;
        } else {
            $error = 'Usuario o contraseña incorrectos';
        }
    } else {
        $error = 'Por favor, ingrese usuario y contraseña';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ingreso - Sistema de Becas</title>
    <link rel="stylesheet" href="style.css">
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
        .login-container input { width:100%; padding: 12px; margin: 8px 0 20px 0; border-radius: 12px; border: 1px solid #ddd; }
        .login-container button { width:100%; padding: 14px; margin-top:12px; background: #FF6600; color:#fff; border:none; border-radius:12px; font-weight: 700; cursor: pointer; transition: all 0.3s; }
        .login-container button:hover { background: #e65500; transform: translateY(-2px); box-shadow: 0 4px 12px rgba(255,102,0,0.3); }
        .error { color:#e74040; margin-bottom: 16px; font-weight: 600; }
    </style>
</head>
<body>
<script>document.addEventListener('DOMContentLoaded',function(){document.body.classList.add('page-visible');});</script>
    <div class="login-container">
        <h2>Ingreso al Sistema</h2>
        <p style="color:#666; font-size:0.85rem; margin-bottom:20px;">Estudiantes: Usen su cédula o usuario asignado.</p>
        <?php if($error): ?>
            <p class="error"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <form method="POST" action="">
            <label>Usuario</label>
            <input type="text" name="usuario" required>
            <label>Contraseña</label>
            <input type="password" name="password" required>
            <button type="submit">Entrar</button>
            <div style="margin-top:15px; text-align:center;">
                <a href="index.php" style="text-decoration:none; color:#FF6600; font-weight:700; font-size:0.9rem;">← Volver al Inicio</a>
            </div>
        </form>
    </div>

    <?php include 'footer.php'; ?>
    <footer class="site-footer">
        <div style="max-width:900px;margin:0 auto;padding:12px;color:#666;text-align:center;">
            <small>UPTAG - Sistema de Solicitudes · &copy; <?php echo date('Y'); ?></small>
        </div>
    </footer>
</body>
</html>