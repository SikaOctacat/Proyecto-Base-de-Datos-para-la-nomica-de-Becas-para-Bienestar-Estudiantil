<?php
// index.php unificado
require 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Lógica para cerrar sesión manualmente
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit;
}

$logged = isset($_SESSION['user']);
$studentInfo = null;

// --- LÓGICA DE BÚSQUEDA ---
if ($logged && $_SESSION['rol'] === 'estudiante' && isset($_SESSION['user_id'])) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM estudiante WHERE usuario_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $studentInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($studentInfo) {
            $ci = $studentInfo['ci'];

            // Familiares
            $stmtFam = $pdo->prepare("SELECT * FROM familiar WHERE ci_estudiante = ?");
            $stmtFam->execute([$ci]);
            $studentInfo['familiares'] = $stmtFam->fetchAll(PDO::FETCH_ASSOC);

            // Residencia
            $stmtRes = $pdo->prepare("SELECT * FROM residencia WHERE ci_estudiante = ?"); // Corregido ci_student por ci_estudiante
            $stmtRes->execute([$ci]);
            $res = $stmtRes->fetch(PDO::FETCH_ASSOC);
            
            if ($res) {
                $studentInfo['t_viv'] = $res['t_viv'];
                $studentInfo['estado_res'] = $res['estado_res'];
                $studentInfo['municipio_res'] = $res['municipio_res'];
                $studentInfo['t_loc'] = $res['t_loc'];
                $studentInfo['tel_local'] = $res['tel_local'];
                $studentInfo['dir_local'] = $res['dir_local'];
            }

            // Info Académica
            $stmtAcad = $pdo->prepare("SELECT * FROM record_academico WHERE ci_estudiante = ?");
            $stmtAcad->execute([$ci]);
            $ac = $stmtAcad->fetch(PDO::FETCH_ASSOC);
            
            if ($ac) {
                $studentInfo['cod_est'] = $ac['cod_est'] ?? 'N/A';
                $studentInfo['trayecto'] = $ac['trayecto'] ?? 'N/A';
                $studentInfo['trimestre'] = $ac['trimestre'] ?? 'N/A';
                $studentInfo['record_indice'] = $ac['indice_trimestre'] ?? '0.0';
                $studentInfo['m_ira'] = $ac['ira_anterior'] ?? '0.0';
            }

            if (!empty($studentInfo['f_nac'])) {
                $cumple = new DateTime($studentInfo['f_nac']);
                $hoy = new DateTime();
                $studentInfo['edad'] = $hoy->diff($cumple)->y;
            }
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
    }
}

// --- VISTA PARA NO LOGUEADOS ---
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
            
        </header>
        <main class="hero">
            <div class="hero-content">
                <h1>SISTEMA <span class="highlight">INTEGRADO</span> DE BECAS</h1>
                <p>Postúlate hoy y asegura tu futuro académico.</p>
                <div class="hero-actions">
                    <a class="btn-cta" href="login.php">Inicar sesion</a>
                    <a class="btn-sec" href="register.php">Postularse Ahora</a>
                </div>
            </div>
        </main>
        </div>
        <script>
            const modal = document.getElementById('loginModal');
            document.getElementById('openLogin').onclick = (e) => { e.preventDefault(); modal.classList.remove('oculto'); };
            document.getElementById('closeLogin').onclick = () => modal.classList.add('oculto');
            window.onclick = (event) => { if (event.target == modal) modal.classList.add('oculto'); }
        </script>
    </body>
    </html>
<?php
    exit;
}
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

    <div id="main-container">
        <div class="page-header" style="display:flex; justify-content:space-between; align-items:center;">
            <div>
                <h2>PERFIL DE USUARIO<h2>
            </div>
            <a href="?logout=1" 
               onclick="return confirm('¿Estás seguro de que deseas cerrar tu sesión actual?');"
               style="background:#e74040; color:#fff; padding:8px 15px; text-decoration:none; border-radius:8px; font-weight:bold; font-size:0.8rem;">
               Cerrar Sesión
            </a>
        </div>
        
        <div class="progress-wrapper" id="progress-wrapper" <?php echo $studentInfo ? 'style="display:none;"' : ''; ?>>
            <div id="progressBar" class="progress-bar"></div>
        </div>

        <form id="becaForm">
            <div id="dynamic-content">
                <?php if($studentInfo): ?>
                    <div id="loading-profile" style="text-align:center; padding:40px;">
                        <p>Cargando información de perfil...</p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="nav-buttons" id="nav-buttons" <?php echo $studentInfo ? 'style="display:none;"' : ''; ?>>
                <button type="button" id="prevBtn" class="btn-prev">Anterior</button>
                <button type="button" id="nextBtn" class="btn-next">Siguiente</button>
            </div>
        </form>
    </div>

    <script>
        window.studentProfile = <?php echo json_encode($studentInfo); ?>;
        
        document.addEventListener('DOMContentLoaded', function() {
            if (window.studentProfile) {
                fetch('perfil_view.php')
                    .then(response => response.text())
                    .then(html => {
                        document.getElementById('dynamic-content').innerHTML = html;
                    })
                    .catch(err => console.error("Error al cargar la vista de perfil:", err));
            }
        });
    </script>
    <script src="main.js"></script>
    <script src="script.js"></script>
</body>
</html>