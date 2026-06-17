<?php
require '../base_de_datos/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. FUNCIÓN DE BITÁCORA
function registrarMovimiento($pdo, $usuario_id, $accion, $tabla, $detalles = null) {
    $stmt = $pdo->prepare('INSERT INTO bitacora (usuario_id, accion, tabla_afectada, detalles) VALUES (?, ?, ?, ?)');
    $stmt->execute([$usuario_id, $accion, $tabla, $detalles]);
}

$error = '';
$success = '';
$pregunta = ''; 
$step = isset($_POST['step']) ? (int)$_POST['step'] : 1; 

// Lógica para el botón "Regresar"
if (isset($_POST['go_back'])) {
    if ($step > 1) {
        $step--;
        if ($step == 1) {
            unset($_SESSION['reset_user_id'], $_SESSION['reset_user_name'], $_SESSION['db_answer']);
        }
    } else {
        header("Location: login.php");
        exit;
    }
}

// PROCESAMIENTO DEL FORMULARIO (Solo si no se presionó "Regresar")
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['go_back'])) {
    
    // PASO 1: Buscar usuario
    if (isset($_POST['check_user'])) {
        $usuario = trim($_POST['usuario']);
        $stmt = $pdo->prepare("SELECT id, usuario, pregunta_seguridad, respuesta_seguridad FROM usuarios WHERE usuario = ?");
        $stmt->execute([$usuario]);
        $user_data = $stmt->fetch();

        if (!$user_data) {
            $error = "El nombre de usuario o cédula no está registrado.";
            $step = 1;
        } elseif (empty($user_data['pregunta_seguridad'])) {
            $error = "Este usuario no tiene configurada una pregunta de seguridad.";
            $step = 1;
        } else {
            $_SESSION['reset_user_id'] = $user_data['id'];
            $_SESSION['reset_user_name'] = $user_data['usuario']; 
            // Guardamos la respuesta esperada en minúsculas y sin espacios de los lados
            $_SESSION['db_answer'] = strtolower(trim($user_data['respuesta_seguridad'])); 
            $pregunta = $user_data['pregunta_seguridad'];
            $step = 2;
        }
    }

    // PASO 2: Verificar respuesta
    if (isset($_POST['verify_answer'])) {
        $respuesta_user = strtolower(trim($_POST['respuesta']));
        $respuesta_correcta = $_SESSION['db_answer'] ?? '';

        // COMPARACIÓN DIRECTA (Ya que en el registro se guarda en texto plano)
        if (!empty($respuesta_correcta) && $respuesta_user === $respuesta_correcta) {
            $step = 3;
        } else {
            $error = "La respuesta de seguridad es incorrecta.";
            $step = 2;
        }
    }

    // PASO 3: Cambio de contraseña
    if (isset($_POST['change_password'])) {
        $pass1 = $_POST['pass1'];
        $pass2 = $_POST['pass2'];

        if (empty($pass1)) {
            $error = "La contraseña no puede estar vacía.";
            $step = 3;
        } elseif ($pass1 === $pass2) {
            $new_hash = password_hash($pass1, PASSWORD_BCRYPT); // Usando BCRYPT nativo
            
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
                $stmt->execute([$new_hash, $_SESSION['reset_user_id']]);
                
                registrarMovimiento(
                    $pdo, 
                    $_SESSION['reset_user_id'], 
                    "Recuperación de Cuenta", 
                    "usuarios", 
                    "El usuario " . $_SESSION['reset_user_name'] . " (" . $_SESSION['reset_user_id'] . ") restableció su contraseña exitosamente."
                );

                $pdo->commit();

                // Destruimos variables de recuperación por seguridad
                unset($_SESSION['reset_user_id'], $_SESSION['reset_user_name'], $_SESSION['db_answer']);
                $success = "Contraseña actualizada exitosamente.";
                $step = 4;

            } catch (Exception $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $error = "Error al procesar la solicitud en el servidor.";
                $step = 3;
            }
        } else {
            $error = "Las contraseñas no coinciden.";
            $step = 3;
        }
    }
}

// Recuperación de la pregunta si ocurre una recarga de página en el Paso 2
if ($step == 2 && isset($_SESSION['reset_user_id']) && empty($pregunta)) {
    $stmt = $pdo->prepare("SELECT pregunta_seguridad FROM usuarios WHERE id = ?");
    $stmt->execute([$_SESSION['reset_user_id']]);
    $pregunta = $stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Acceso - Sistema de Becas</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .recover-box { 
            max-width: 400px; margin: 80px auto; padding: 40px; 
            background: rgba(255,255,255,0.9); border-radius: 24px; 
            box-shadow: 0 10px 30px rgba(0,0,0,0.1); 
            backdrop-filter: blur(10px);
            font-family: sans-serif;
        }
        .step-title { 
            background: linear-gradient(90deg, #FF6600, #FF9D00);
            -webkit-background-clip: text; background-clip: text;
            -webkit-text-fill-color: transparent;
            font-weight: 800; margin-bottom: 20px; text-align: center;
        }
        input { width: 100%; padding: 12px; margin: 10px 0; border-radius: 10px; border: 1px solid #ddd; box-sizing: border-box; }
        
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
        
        .btn-back {
            background: none; border: none; color: #FF6600; 
            font-weight: 700; font-size: 0.9rem; cursor: pointer;
            margin-top: 20px; width: 100%; display: flex; justify-content: center;
        }
        .btn-back:hover { text-decoration: underline; }

        .msg-err { color: #d32f2f; background: #ffcdd2; padding: 12px; border-radius: 8px; margin-bottom: 15px; font-weight: 600; text-align: center; }
        .msg-ok { color: #2e7d32; background: #c8e6c9; padding: 12px; border-radius: 8px; margin-bottom: 15px; text-align: center; font-weight: 600; }
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
        <h2 class="step-title">Recuperar acceso</h2>
        
        <?php if($error): ?> <div class="msg-err"><?php echo htmlspecialchars($error); ?></div> <?php endif; ?>
        <?php if($success): ?> <div class="msg-ok"><?php echo htmlspecialchars($success); ?></div> <?php endif; ?>

        <form method="POST">
            <!-- Forzamos el paso calculado por el Backend -->
            <input type="hidden" name="step" value="<?php echo $step; ?>">

            <?php if($step == 1): ?>
                <label>Usuario o cédula</label>
                <input type="text" name="usuario" required placeholder="Ej: 29888777" autofocus>
                <button type="submit" name="check_user" class="btn-main">Continuar</button>
            <?php endif; ?>

            <?php if($step == 2): ?>
                <p style="margin-bottom: 5px;"><strong>Pregunta de seguridad:</strong></p>
                <p style="color: #555; background: #f9f9f9; padding: 10px; border-radius: 8px; border-left: 4px solid #FF6600;">
                    <?php echo htmlspecialchars($pregunta); ?>
                </p>
                <input type="text" name="respuesta" required placeholder="Escriba su respuesta..." autofocus autocomplete="off">
                <button type="submit" name="verify_answer" class="btn-main">Verificar</button>
            <?php endif; ?>

            <?php if($step == 3): ?>
                <label>Nueva contraseña</label>
                <div class="password-wrapper">
                    <input type="password" name="pass1" id="pass1" required minlength="6" placeholder="••••••••">
                    <button type="button" class="btn-view-pass" onclick="togglePassword('pass1', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>

                <label>Confirmar contraseña</label>
                <div class="password-wrapper">
                    <input type="password" name="pass2" id="pass2" required minlength="6" placeholder="••••••••">
                    <button type="button" class="btn-view-pass" onclick="togglePassword('pass2', this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <button type="submit" name="change_password" class="btn-main">Restablecer Contraseña</button>
            <?php endif; ?>

            <?php if($step == 4): ?>
                <p style="text-align: center; color: #666; margin-bottom: 15px;">Tu cuenta ha sido recuperada de forma segura. Ya puedes iniciar sesión.</p>
                <a href="login.php" class="btn-main" style="text-decoration:none; display:block; text-align:center;">Ir al Login</a>
            <?php else: ?>
                <button type="submit" name="go_back" class="btn-back" formnovalidate>← Regresar</button>
            <?php endif; ?>
        </form>
    </div>
</body>
</html>