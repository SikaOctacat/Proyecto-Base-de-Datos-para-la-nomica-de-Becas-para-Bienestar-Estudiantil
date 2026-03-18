<?php
// index.php unificado
require 'db.php';

// SOLUCIÓN AL ERROR: Verificar si la sesión ya existe antes de iniciarla
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$logged = isset($_SESSION['user']);

// Obtener alertas para el inicio
$alertas = [];
try {
    $stmt = $pdo->query("SELECT * FROM alertas WHERE activo = 1 ORDER BY created_at DESC");
    if ($stmt) {
        $alertas = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    // Silencio si la tabla no existe aún
}

// --- CASO 1: USUARIO NO AUTENTICADO (Landing Page) ---
if (!$logged) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <title>Inicio - Sistema de Becas</title>
        <link rel="stylesheet" href="style.css">
        <link rel="icon" href="img/logo.png" type="image/png">
        <meta name="viewport" content="width=device-width,initial-scale=1">
        <style>
            /* Estilos de la Landing Page */
            .top-nav{ position:fixed; top:0;left:0;right:0; background:rgba(255,255,255,0.05); backdrop-filter: blur(12px); border-bottom: 1px solid rgba(255,255,255,0.1); z-index:999; padding:15px 40px; display:flex; justify-content:space-between; align-items:center; }
            .hero{ display: flex; align-items: center; justify-content: center; min-height: 80vh; padding-top: 100px; text-align:center; color:#fff;}
            .hero-content { max-width: 700px; padding: 40px; border-radius: 24px; background: rgba(0,0,0,0.4); backdrop-filter: blur(8px); }
            .highlight{color:#FF6600;}
            .btn-cta { background: #FF6600; padding: 14px 28px; color:#fff; text-decoration:none; font-weight: 700; border-radius: 12px; transition: all 0.3s; margin: 10px; display:inline-block; border:none; cursor:pointer;}
            .btn-sec { border: 2px solid #fff; color: #fff; padding: 14px 28px; text-decoration:none; font-weight: 700; border-radius: 12px; transition: all 0.3s; margin: 10px; display:inline-block; }
            .modal.oculto { display: none !important; }
        </style>
    </head>
    <body>
        <header class="top-nav">
            <img src="img/logo.png" alt="Logo" style="height:64px">
            <nav><a href="#" id="openLogin" style="color:#fff; text-decoration:none;">Iniciar sesión</a></nav>
        </header>

        <main class="hero">
            <div class="hero-content">
                <h1>SISTEMA <span class="highlight">INTEGRADO</span> DE BECAS</h1>
                <p>Postúlate hoy y asegura tu futuro académico.</p>
                <div class="hero-actions">
                    <a class="btn-cta" href="login.php">Entrar al Portal</a>
                    <a class="btn-sec" href="register.php">Postularse Ahora</a>
                </div>
            </div>
        </main>

        <div id="loginModal" class="modal oculto" style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:1000; display:flex; align-items:center; justify-content:center;">
            <div style="background:#fff; padding:30px; border-radius:12px; position:relative; width:90%; max-width:400px;">
                <button id="closeLogin" style="position:absolute; top:10px; right:15px; border:none; background:none; font-size:24px; cursor:pointer;">&times;</button>
                <h3 style="color:#333">Iniciar sesión</h3>
                <form method="POST" action="login.php">
                    <input name="usuario" type="text" placeholder="Usuario" required style="width:100%; margin-bottom:10px; padding:10px; border:1px solid #ccc; border-radius:5px;">
                    <input name="password" type="password" placeholder="Contraseña" required style="width:100%; margin-bottom:10px; padding:10px; border:1px solid #ccc; border-radius:5px;">
                    <button type="submit" class="btn-cta" style="width:100%;">Ingresar</button>
                </form>
            </div>
        </div>

        <script>
            const modal = document.getElementById('loginModal');
            document.getElementById('openLogin').onclick = (e) => {
                e.preventDefault();
                modal.classList.remove('oculto');
            };
            document.getElementById('closeLogin').onclick = () => modal.classList.add('oculto');
            
            // Cerrar al hacer clic fuera del blanco
            window.onclick = (event) => {
                if (event.target == modal) modal.classList.add('oculto');
            }
        </script>
    </body>
    </html>
    <?php
    exit;
}

// --- CASO 2: USUARIO AUTENTICADO ---
// Aquí puedes incluir tus consultas SQL para $studentInfo si el rol es 'estudiante'
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sistema de Becas - Panel</title>
    <link rel="stylesheet" href="style.css">
    <meta name="viewport" content="width=device-width,initial-scale=1">
</head>
<body data-rol="<?php echo $_SESSION['rol'] ?? 'publico'; ?>">

    <label for="btn-menu" class="btn-abrir-menu"> ☰ </label>
    <div id="sidebar-placeholder"></div>

    <div id="main-container">
        <div class="page-header">
            <h2>SISTEMA DE SOLICITUD DE BECAS</h2>
            <div style="color: #666; font-size: 0.9rem;">
                Bienvenido, <strong><?php echo htmlspecialchars($_SESSION['user']); ?></strong> 
            </div>
        </div>
        
        <div class="progress-wrapper">
            <div id="progressBar" class="progress-bar"></div>
        </div>

        <form id="becaForm">
            <div id="dynamic-content"></div>

            <div class="nav-buttons">
                <button type="button" id="prevBtn" class="btn-prev">Anterior</button>
                <button type="button" id="nextBtn" class="btn-next">Siguiente</button>
            </div>
        </form>
    </div>

    <script>
        // Cargar Sidebar
        fetch('./sidebar.html')
            .then(res => res.text())
            .then(data => {
                document.getElementById('sidebar-placeholder').innerHTML = data;
            })
            .catch(err => console.error("Error al cargar la barra lateral:", err));

        // Pasar datos de PHP a JS de forma segura
        window.studentProfile = <?php echo json_encode($studentInfo ?? []); ?>;
    </script>

    <script src="main.js"></script>
    <script src="script.js"></script>
</body>
</html>