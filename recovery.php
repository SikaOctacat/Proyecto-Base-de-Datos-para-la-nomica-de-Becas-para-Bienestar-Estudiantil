<?php
require 'db.php';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['usuario'] ?? '';
    $new_pass = $_POST['password'] ?? '';
    $pin = $_POST['pin'] ?? '';

    // Master PIN for demo purposes: 1234
    if ($pin === '1234') {
        $stmt = $pdo->prepare('SELECT id FROM usuarios WHERE usuario = ?');
        $stmt->execute([$user]);
        if ($stmt->fetch()) {
            if ($user === 'admin') {
                $error = 'No se puede cambiar la contraseña del administrador principal por este medio.';
            } else {
                $hash = hash('sha256', $new_pass);
                $pdo->prepare('UPDATE usuarios SET password = ? WHERE usuario = ?')->execute([$hash, $user]);
                $success = 'Contraseña actualizada correctamente.';
            }
        } else {
            $error = 'El usuario no existe.';
        }
    } else {
        $error = 'PIN de recuperación incorrecto.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Contraseña</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .recovery-box { 
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(14px) saturate(180%);
            -webkit-backdrop-filter: blur(14px) saturate(180%);
            padding: 40px; 
            border-radius: 24px; 
            box-shadow: 0 12px 40px rgba(0,0,0,0.25);
            width: 100%; 
            max-width: 420px; 
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
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
<script>document.addEventListener('DOMContentLoaded',function(){document.body.classList.add('page-visible');});</script>
    <div class="recovery-box">
        <h2>Recuperar Acceso</h2>
        <p style="font-size: 0.85rem; color: #666; margin-bottom: 20px;">Use su PIN de seguridad para restablecer su contraseña.</p>
        
        <?php if($error): ?> <p class="form-message error"><?php echo $error; ?></p> <?php endif; ?>
        <?php if($success): ?> <p class="form-message success"><?php echo $success; ?></p> <?php endif; ?>

        <form method="POST">
            <label>Usuario</label>
            <input type="text" name="usuario" required>
            <label>Nueva Contraseña</label>
            <input type="password" name="password" required>
            <label>PIN de Recuperación (Demo: 1234)</label>
            <input type="password" name="pin" required>
            <div style="margin-top: 20px; display: flex; gap: 10px;">
                <button type="submit" class="btn-primary">Actualizar</button>
                <a href="index.php" class="btn-secondary" style="text-decoration: none; padding: 10px;">← Volver</a>
            </div>
        </form>
    </div>
</body>
</html>
