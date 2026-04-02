<?php
// index.php unificado
require 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$logged = isset($_SESSION['user']);

// --- LÓGICA DE BÚSQUEDA ---
$studentInfo = null;

// Agregamos verificación de que 'user_id' existe en la sesión
if ($logged && $_SESSION['rol'] === 'estudiante' && isset($_SESSION['user_id'])) {
    try {
        // 1. Datos básicos del estudiante
        $stmt = $pdo->prepare("SELECT * FROM estudiantes WHERE usuario_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $studentInfo = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($studentInfo) {
            $e_id = $studentInfo['id'];

            // --- CORRECCIÓN DE NOMBRES PARA CABECERA ---
            $studentInfo['nombre1'] = $studentInfo['nombre1'] ?? $studentInfo['nombres'] ?? 'Usuario';
            $studentInfo['apellido_paterno'] = $studentInfo['apellido_paterno'] ?? $studentInfo['apellidos'] ?? '';
            
            // --- DATOS PERSONALES (Mapeo para etiquetas {{...}}) ---
            $studentInfo['tel_estudiante'] = $studentInfo['telefono'] ?? $studentInfo['celular'] ?? 'N/A';
            $studentInfo['C_Patria'] = $studentInfo['codigo_patria'] ?? 'N/A';
            $studentInfo['edo_civil'] = $studentInfo['estado_civil'] ?? 'N/A';
            $studentInfo['observaciones'] = $studentInfo['notas'] ?? 'Sin observaciones adicionales.';

            // 2. Familiares (Carga Familiar)
            $stmtFam = $pdo->prepare("SELECT * FROM familiares WHERE estudiante_id = ?");
            $stmtFam->execute([$e_id]);
            $studentInfo['familiares'] = $stmtFam->fetchAll(PDO::FETCH_ASSOC);

            // 3. Residencia / Ubicación
            $stmtRes = $pdo->prepare("SELECT * FROM residencias WHERE estudiante_id = ?");
            $stmtRes->execute([$e_id]);
            $res = $stmtRes->fetch(PDO::FETCH_ASSOC);
            
            if ($res) {
                $studentInfo['t_viv'] = $res['tipo_vivienda'] ?? 'N/A';
                $studentInfo['estado_res'] = $res['estado'] ?? 'Falcón';
                $studentInfo['municipio_res'] = $res['municipio'] ?? 'N/A';
                $studentInfo['localidad'] = $res['localidad'] ?? 'N/A';
                $studentInfo['tel_local'] = $res['telefono_local'] ?? 'N/A';
                $studentInfo['dir_local'] = $res['direccion_exacta'] ?? 'No especificada';
            } else {
                $studentInfo['t_viv'] = $studentInfo['localidad'] = $studentInfo['municipio_res'] = 'N/A';
                $studentInfo['estado_res'] = 'Falcón';
                $studentInfo['dir_local'] = 'No especificada';
            }

            // 4. Info Académica
            $stmtAcad = $pdo->prepare("SELECT * FROM datos_academicos WHERE estudiante_id = ?");
            $stmtAcad->execute([$e_id]);
            $ac = $stmtAcad->fetch(PDO::FETCH_ASSOC);
            
            if ($ac) {
                $studentInfo['cod_est'] = $ac['codigo_estudiante'] ?? 'N/A';
                $studentInfo['f_ingreso'] = $ac['fecha_ingreso'] ?? 'N/A';
                $studentInfo['record_indice'] = $ac['indice_academico'] ?? '0.0';
                $studentInfo['m_ira'] = $ac['ira'] ?? '0.0';
                $studentInfo['trayecto'] = $ac['trayecto'] ?? 'N/A';
                $studentInfo['trimestre'] = $ac['trimestre'] ?? 'N/A';
            } else {
                $studentInfo['cod_est'] = $studentInfo['f_ingreso'] = 'N/A';
                $studentInfo['record_indice'] = $studentInfo['m_ira'] = '0.0';
            }

            // 5. Cálculo de Edad
            if (!empty($studentInfo['f_nac'])) {
                $cumple = new DateTime($studentInfo['f_nac']);
                $hoy = new DateTime();
                $studentInfo['edad'] = $hoy->diff($cumple)->y;
                $studentInfo['f_nac_format'] = $cumple->format('d/m/Y'); 
            } else {
                $studentInfo['edad'] = 'N/A';
            }
        }
    } catch (PDOException $e) {
        error_log($e->getMessage());
    }
}

// Obtener alertas
$alertas = [];
try {
    $stmt = $pdo->query("SELECT * FROM alertas WHERE activo = 1 ORDER BY created_at DESC");
    if ($stmt) { $alertas = $stmt->fetchAll(); }
} catch (PDOException $e) { }

// --- VISTA ---
if (!$logged) {
    // ... (Tu código de Hero/Login se mantiene igual) ...
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
            document.getElementById('openLogin').onclick = (e) => { e.preventDefault(); modal.classList.remove('oculto'); };
            document.getElementById('closeLogin').onclick = () => modal.classList.add('oculto');
            window.onclick = (event) => { if (event.target == modal) modal.classList.add('oculto'); }
        </script>
        <?php include 'footer.php'; ?>
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

    <div id="sidebar-placeholder"></div>

    <div id="main-container">
        <div class="page-header">
            <h2>SISTEMA DE SOLICITUD DE BECAS</h2>
            <div style="color: #666; font-size: 0.9rem;">
                Bienvenido, <strong><?php echo htmlspecialchars($_SESSION['user']); ?></strong> 
            </div>
        </div>
        
        <div class="progress-wrapper" id="progress-wrapper">
            <div id="progressBar" class="progress-bar"></div>
        </div>

        <form id="becaForm">
            <div id="dynamic-content"></div>

            <div class="nav-buttons" id="nav-buttons">
                <button type="button" id="prevBtn" class="btn-prev">Anterior</button>
                <button type="button" id="nextBtn" class="btn-next">Siguiente</button>
            </div>
        </form>
    </div>

    <script>
        // Cargar Sidebar
        const sidebarBox = document.getElementById('sidebar-placeholder');
        if(sidebarBox) {
            fetch('./sidebar.html')
                .then(res => res.ok ? res.text() : "")
                .then(data => { sidebarBox.innerHTML = data; })
                .catch(err => console.error("Error al cargar la barra lateral:", err));
        }

        // Datos para main.js
        window.studentProfile = <?php echo json_encode($studentInfo); ?>;
        console.log("Datos enviados al JS:", window.studentProfile);
    </script>

    <script src="main.js"></script>
    <script src="script.js"></script>
</body>
</html>