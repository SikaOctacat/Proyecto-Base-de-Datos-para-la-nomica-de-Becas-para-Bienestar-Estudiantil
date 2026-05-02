<?php
require 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error = '';
$success = '';
$step = 1; 
$user_data = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['check_user'])) {
        $usuario = trim($_POST['usuario']);
        $stmt = $pdo->prepare("SELECT id, usuario, pregunta_seguridad, respuesta_seguridad FROM usuarios WHERE usuario = ?");
        $stmt->execute([$usuario]);
        $user_data = $stmt->fetch();

        if (!$user_data) {
            $error = "El nombre de usuario o cédula no está registrado.";
        } elseif (empty($user_data['pregunta_seguridad'])) {
            $error = "Este usuario no tiene configurada una pregunta de seguridad. Contacte al administrador.";
        } else {
            $_SESSION['reset_user_id'] = $user_data['id'];
            $_SESSION['db_answer'] = $user_data['respuesta_seguridad'];
            $pregunta = $user_data['pregunta_seguridad'];
            $step = 2;
        }
    }

    if (isset($_POST['verify_answer'])) {
        $respuesta_user = trim($_POST['respuesta']);
        if (strcasecmp($respuesta_user, $_SESSION['db_answer']) === 0) {
            $step = 3;
        } else {
            $error = "La respuesta es incorrecta.";
            $step = 2;
            $stmt = $pdo->prepare("SELECT pregunta_seguridad FROM usuarios WHERE id = ?");
            $stmt->execute([$_SESSION['reset_user_id']]);
            $pregunta = $stmt->fetchColumn();
        }
    }

    if (isset($_POST['change_password'])) {
        $pass1 = $_POST['pass1'];
        $pass2 = $_POST['pass2'];

        if ($pass1 === $pass2 && !empty($pass1)) {
            $new_hash = password_hash($pass1, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
            $stmt->execute([$new_hash, $_SESSION['reset_user_id']]);
            
            unset($_SESSION['reset_user_id'], $_SESSION['db_answer']);
            $success = "Contraseña actualizada exitosamente.";
            $step = 4;
        } else {
            $error = "Las contraseñas no coinciden.";
            $step = 3;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Acceso - Sistema de Becas</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .recover-box { 
            max-width: 400px; margin: 80px auto; padding: 40px; 
            background: rgba(255,255,255,0.9); border-radius: 24px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
            backdrop-filter: blur(10px);
        }
        .step-title { 
            background: linear-gradient(90deg, #FF6600, #FF9D00);
            -webkit-background-clip: text; background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 800; margin-bottom: 20px; 
        }
        input { width: 100%; padding: 12px; margin: 10px 0; border-radius: 10px; border: 1px solid #ddd; box-sizing: border-box; }
        
        /* Contenedor para el ojo */
        .password-wrapper { position: relative; width: 100%; }
        .btn-view-pass {
            position: absolute; right: 12px; top: 22px;
            background: none; border: none; color: #666; cursor: pointer;
        }

        .btn-main { 
            width: 100%; padding: 14px; background: #FF6600; color: white; 
            border: none; border-radius: 12px; cursor: pointer; font-weight: 700; 
            transition: all 0.3s; margin-top: 10px;
        }
        .btn-main:hover { background: #e65500; transform: translateY(-2px); }
        
        /* Botón de retorno mejorado */
        .btn-secondary {
            display: block; width: 100%; padding: 12px; margin-top: 20px;
            background: #f0f0f0; color: #444; text-align: center;
            text-decoration: none; border-radius: 12px; font-weight: 600;
            transition: background 0.3s; border: 1px solid #ddd;
        }
        .btn-secondary:hover { background: #e5e5e5; color: #000; }

        .msg-err { color: #d32f2f; background: #ffcdd2; padding: 12px; border-radius: 8px; margin-bottom: 15px; font-weight: 600; text-align: center; }
        .msg-ok { color: #2e7d32; background: #c8e6c9; padding: 12px; border-radius: 8px; margin-bottom: 15px; text-align: center; }
    </style>
</head>
<body>
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
    </script>

    <div class="recover-box">
        <h2 class="step-title">Recuperar Acceso</h2>
        
        <?php if($error): ?> <div class="msg-err"><?php echo $error; ?></div> <?php endif; ?>
        <?php if($success): ?> <div class="msg-ok"><?php echo $success; ?></div> <?php endif; ?>

        <?php if($step == 1): ?>
            <form method="POST">
                <label>Usuario o Cédula</label>
                <input type="text" name="usuario" required placeholder="Ej: 29888777" autofocus>
                <button type="submit" name="check_user" class="btn-main">Continuar</button>
            </form>
        <?php endif; ?>

        <?php if($step == 2): ?>
            <form method="POST">
                <p style="margin-bottom: 5px;"><strong>Pregunta de Seguridad:</strong></p>
                <p style="color: #555; background: #f9f9f9; padding: 10px; border-radius: 8px; border-left: 4px solid #FF6600;">
                    <?php echo htmlspecialchars($pregunta); ?>
                </p>
                <input type="text" name="respuesta" required placeholder="Escriba su respuesta..." autofocus>
                <button type="submit" name="verify_answer" class="btn-main">Verificar</button>
            </form>
        <?php endif; ?>

        <?php if($step == 3): ?>
            <form method="POST">
                <label>Nueva Contraseña</label>
                <div class="password-wrapper">
                    <input type="password" name="pass1" id="pass1" required minlength="6" placeholder="••••••••">
                    <button type="button" class="btn-view-pass" onclick="togglePassword('pass1', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>

                <label>Confirmar Contraseña</label>
                <div class="password-wrapper">
                    <input type="password" name="pass2" id="pass2" required minlength="6" placeholder="••••••••">
                    <button type="button" class="btn-view-pass" onclick="togglePassword('pass2', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <button type="submit" name="change_password" class="btn-main">Restablecer Contraseña</button>
            </form>
        <?php endif; ?>

        <?php if($step == 4): ?>
            <p style="text-align: center; color: #666;">Tu cuenta ha sido recuperada. Ahora puedes volver al sistema con tu nueva clave.</p>
        <?php endif; ?>

        <a href="login.php" class="btn-secondary">Volver al Login</a>
    </div>
</body>
</html>