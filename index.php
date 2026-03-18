<?php
// index.php: muestra una página de inicio pública si no hay sesión activa,
// y la aplicación (SPA) cuando el usuario está autenticado.
require 'db.php';
$logged = isset($_SESSION['user']);

// Obtener alertas para el inicio
$alertas = [];
try {
    $stmt = $pdo->query("SELECT * FROM alertas WHERE activo = 1 ORDER BY created_at DESC");
    if ($stmt) {
        $alertas = $stmt->fetchAll();
    }
} catch (PDOException $e) {
    // Silencio si la tabla no existe aún o hay error
}

if (!$logged) {
    // Página de inicio pública con menú fijo y enlace a login
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
            .top-nav{
                position:fixed;
                top:0;left:0;right:0;
                background:rgba(255,255,255,0.05);
                backdrop-filter: blur(12px) saturate(180%);
                -webkit-backdrop-filter: blur(12px) saturate(180%);
                border-bottom: 1px solid rgba(255,255,255,0.1);
                box-shadow:0 4px 12px rgba(0,0,0,.1);
                z-index:999;
                padding:15px 40px;
                display:flex;
                justify-content:space-between;
                align-items:center;
            }
            .top-nav .brand-logo{height:64px}
            .main-menu { list-style:none;margin:0;padding:0;display:flex;gap:22px;}
            .main-menu li a{color:#fff;text-decoration:none;font-weight:600;text-transform:uppercase;font-size:0.95rem;position:relative;}
            .main-menu li a:after{content:'';position:absolute;left:0;bottom:-4px;width:0;height:2px;background:#FF6600;transition:width .2s;}
            .main-menu li a:hover:after{width:100%;}
            .role-icons { display:flex; gap:12px; }
            .role-item {
                display:flex;align-items:center;justify-content:center;
                width:42px;height:42px;border-radius:50%;background:rgba(255,255,255,0.2);
                color:#fff;font-size:0.75rem;text-decoration:none;font-weight:700;
                transition:background 0.2s;
            }
            .role-item:hover { background:rgba(255,255,255,0.35); }
            .hero{padding:150px 20px 80px;text-align:center;color:#fff;}
            .hero h1{font-size:2.5rem;margin-bottom:6px;line-height:1.2;}
            .hero h1 .highlight{color:#FF6600;}
            .hero p{color:#f0f0f0;margin-bottom:22px;font-size:1.1rem;}
            .btn-cta{display:inline-block;padding:10px 18px;background:#0056b3;color:#fff;border-radius:6px;text-decoration:none;}
            .icon-login { width:16px; height:16px; vertical-align:middle; margin-right:6px; filter: invert(1); }

            /* Estilos Modal de Alertas UPTAG-style */
            .alert-modal-overlay {
                position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center;
                z-index: 2000; opacity: 0; visibility: hidden; transition: all 0.3s ease;
            }
            .alert-modal-overlay.active { opacity: 1; visibility: visible; }
            .alert-modal-box {
                background: white; width: 90%; max-width: 500px; border-radius: 12px;
                position: relative; box-shadow: 0 15px 50px rgba(0,0,0,0.6);
                overflow: hidden; animation: alertModalPop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                max-height: 90vh; display: flex; flex-direction: column;
            }
            @keyframes alertModalPop {
                from { transform: scale(0.8); opacity: 0; }
                to { transform: scale(1); opacity: 1; }
            }
            .alert-modal-close {
                position: absolute; top: 10px; right: 15px; font-size: 24px;
                cursor: pointer; color: #666; font-weight: bold; z-index: 10;
            }
            .alert-modal-close:hover { color: #000; }
            .alert-modal-img { width: 100%; display: block; max-height: 350px; object-fit: cover; border-bottom: 2px solid #eee; }
            .alert-modal-body { padding: 20px; text-align: center; overflow-y: auto; }
            .alert-modal-title { color: #2c3e50; font-size: 1.5rem; margin: 0 0 10px; font-weight: 800; border-bottom: 3px solid #FF6600; display: inline-block; padding-bottom: 5px; }
            .alert-modal-text { color: #555; font-size: 1rem; line-height: 1.6; margin-bottom: 0; }
        </style>
    </head>
    <body>
        <div id="loadingOverlay" class="loading-overlay"><div class="spinner"></div></div>
        <script>document.addEventListener('DOMContentLoaded',function(){document.body.classList.add('page-visible');});</script>
        <header class="top-nav">
            <img src="img/logo.png" alt="Sistema de Becas" class="brand-logo">
            <nav class="menu">
                <a href="#" id="openLogin"><img src="img/person-fill.svg" alt="" class="icon-login">Iniciar sesión</a>
            </nav>
        </header>
        <main class="hero">
            <div class="hero-content">
                <h1>SISTEMA <span class="highlight">INTEGRADO</span> DE BECAS</h1>
                <p>Postúlate hoy y asegura tu futuro académico con nosotros.</p>
                <div class="hero-actions">
                    <a class="btn-cta" href="login.php" title="Consultar estatus y datos personales">Entrar al Portal</a>
                    <a class="btn-sec" href="register.php" title="Completar formulario de nueva beca">Postularse Ahora</a>
                </div>
                <div style="margin-top:20px;">
                    <a href="login.php" style="color:rgba(255,255,255,0.6); font-size:0.85rem; text-decoration:none;">Acceso Administrativo</a>
                </div>
            </div>
        </main>

        <style>
            .hero { display: flex; align-items: center; justify-content: center; min-height: 60vh; }
            .hero-content { max-width: 700px; padding: 40px; border-radius: 24px; background: rgba(0,0,0,0.4); backdrop-filter: blur(8px); }
            .hero h1 { font-size: 3.5rem; font-weight: 800; letter-spacing: -1.5px; }
            .hero-actions { display: flex; gap: 16px; justify-content: center; margin-top: 24px; }
            .btn-cta { background: #FF6600; padding: 14px 28px; font-weight: 700; border-radius: 12px; transition: all 0.3s; }
            .btn-sec { border: 2px solid #fff; color: #fff; padding: 14px 28px; font-weight: 700; border-radius: 12px; transition: all 0.3s; }
            .btn-cta:hover { background: #e65500; transform: scale(1.05); }
            .btn-sec:hover { background: #fff; color: #000; transform: scale(1.05); }
        </style>

        <footer style="text-align:center;color:#ccc;padding:40px 10px;">
            <small>UPTAG - Sistema de Solicitudes &middot; &copy; <?php echo date('Y'); ?></small><br>
            <small style="opacity: 0.6; margin-top: 8px; display: block;">Bajo licencia <a href="https://creativecommons.org/licenses/by/4.0/" target="_blank" style="color: #FF6600;">Creative Commons BY 4.0</a></small>
        </footer>

        <!-- Login Modal -->
        <div id="loginModal" class="modal oculto" role="dialog" aria-modal="true" aria-labelledby="loginTitle">
            <div class="modal-content">
                <button class="modal-close" id="closeLogin" aria-label="Cerrar">×</button>
                <h3 id="loginTitle">Iniciar sesión</h3>
                <form method="POST" action="login.php">
                    <label for="usuario">Usuario</label>
                    <input id="usuario" name="usuario" type="text" required>
                    <label for="password">Contraseña</label>
                    <input id="password" name="password" type="password" required>
                    <div style="margin-top:12px;display:flex;gap:8px;justify-content:flex-end;align-items:center;">
                        <a href="recovery.php" style="font-size: 0.8rem; color: #666; margin-right: auto;">¿Olvidó su contraseña?</a>
                        <button type="submit" class="btn-next">Ingresar</button>
                        <a href="register.php" class="btn-prev" style="display:inline-block;padding:10px 12px;line-height:24px;text-decoration:none">Registrarse</a>
                    </div>
                </form>
            </div>
        </div>

        <script>
            (function(){
                // show loading overlay and fade body when navigating or logging out
                function prepareLeave(e){
                    var overlay = document.getElementById('loadingOverlay');
                    if(overlay) overlay.classList.add('active');
                    document.body.classList.add('fade-out');
                    // allow default navigation after short delay
                    setTimeout(function(){ window.location = e.target.href; },200);
                    e.preventDefault();
                }
                // attach to logout links
                var logoutLinks = document.querySelectorAll('a.logout-link');
                logoutLinks.forEach(function(link){ link.addEventListener('click', prepareLeave); });
                // attach to any internal navigation anchors except hashes
                var navLinks = document.querySelectorAll('a[href]:not([href^="#"])');
                navLinks.forEach(function(link){
                    if(!link.classList.contains('logout-link')){
                        link.addEventListener('click', function(e){
                            // only intercept same-host links
                            var url = new URL(link.href, window.location);
                            if(url.host === window.location.host){ prepareLeave(e); }
                        });
                    }
                });
                var open = document.getElementById('openLogin');
                var modal = document.getElementById('loginModal');
                var close = document.getElementById('closeLogin');
                if(open){ open.addEventListener('click', function(e){ e.preventDefault(); modal.classList.remove('oculto'); document.body.style.overflow='hidden'; document.getElementById('usuario').focus(); }); }
                if(close){ close.addEventListener('click', function(){ modal.classList.add('oculto'); document.body.style.overflow=''; }); }
                window.addEventListener('keydown', function(e){ if(e.key==='Escape') { modal.classList.add('oculto'); document.body.style.overflow=''; } });
                // click fuera del modal
                modal.addEventListener('click', function(e){ if(e.target===modal){ modal.classList.add('oculto'); document.body.style.overflow=''; } });

                // Ensure visibility on back/forward navigation
                window.addEventListener('pageshow', function(event) {
                    document.body.classList.remove('fade-out');
                    var overlay = document.getElementById('loadingOverlay');
                    if(overlay) overlay.classList.remove('active');
                });

                // no slide menu logic for landing
                // existing modal and other handlers remain unchanged
            })();
        </script>
        <!-- Alert Modal Structure UPTAG-Style -->
        <?php if (!empty($alertas)): 
            $main_alert = $alertas[0]; 
        ?>
        <div class="alert-modal-overlay active" id="alertModalOverlay">
            <div class="alert-modal-box">
                <span class="alert-modal-close" id="closeAlertModal">&times;</span>
                <?php if($main_alert['imagen_url']): ?>
                    <img src="<?= htmlspecialchars($main_alert['imagen_url']) ?>" class="alert-modal-img" alt="Anuncio">
                <?php endif; ?>
                <div class="alert-modal-body">
                    <h2 class="alert-modal-title"><?= htmlspecialchars($main_alert['titulo']) ?></h2>
                    <div class="alert-modal-text"><?= nl2br(htmlspecialchars($main_alert['mensaje'])) ?></div>
                    <div style="margin-top: 20px;">
                        <button id="closeAlertBtn" style="background: #FF6600; color: white; border: none; padding: 10px 20px; border-radius: 5px; font-weight: bold; cursor: pointer;">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
        <script>
            (function(){
                var modal = document.getElementById('alertModalOverlay');
                var closeX = document.getElementById('closeAlertModal');
                var closeBtn = document.getElementById('closeAlertBtn');

                function closeModal() {
                    modal.classList.remove('active');
                    setTimeout(function(){ modal.style.display = 'none'; }, 300);
                }

                if(closeX) closeX.onclick = closeModal;
                if(closeBtn) closeBtn.onclick = closeModal;
                
                modal.onclick = function(e) {
                    if (e.target === modal) closeModal();
                };
            })();
        </script>
        <?php endif; ?>
    </body>
    </html>
    <?php
    exit;
}

// Si está autenticado, mostrar la aplicación SPA original
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Sistema de Becas</title>
    <link rel="stylesheet" href="style.css">
    <style>
        /* Estilos Modal de Alertas UPTAG-style */
        .alert-modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.7); display: flex; align-items: center; justify-content: center;
            z-index: 2000; opacity: 0; visibility: hidden; transition: all 0.3s ease;
        }
        .alert-modal-overlay.active { opacity: 1; visibility: visible; }
        .alert-modal-box {
            background: white; width: 90%; max-width: 500px; border-radius: 12px;
            position: relative; box-shadow: 0 15px 50px rgba(0,0,0,0.6);
            overflow: hidden; animation: alertModalPop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            max-height: 90vh; display: flex; flex-direction: column;
        }
        @keyframes alertModalPop {
            from { transform: scale(0.8); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }
        .alert-modal-close {
            position: absolute; top: 10px; right: 15px; font-size: 24px;
            cursor: pointer; color: #666; font-weight: bold; z-index: 10;
        }
        .alert-modal-close:hover { color: #000; }
        .alert-modal-img { width: 100%; display: block; max-height: 350px; object-fit: cover; border-bottom: 2px solid #eee; }
        .alert-modal-body { padding: 20px; text-align: center; overflow-y: auto; }
        .alert-modal-title { color: #2c3e50; font-size: 1.5rem; margin: 0 0 10px; font-weight: 800; border-bottom: 3px solid #FF6600; display: inline-block; padding-bottom: 5px; }
        .alert-modal-text { color: #555; font-size: 1rem; line-height: 1.6; margin-bottom: 0; }
    </style>
</head>
<body data-rol="<?php echo $_SESSION['rol'] ?? 'publico'; ?>">
<?php
// If logged as student, fetch their info or create a basic profile
$studentInfo = null;
if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'estudiante') {
    // 1. Fetch User ID first to ensure we have context
    $stmtUser = $pdo->prepare('SELECT id FROM usuarios WHERE usuario = ?');
    $stmtUser->execute([$_SESSION['user']]);
    $userId = $stmtUser->fetchColumn();

    if ($userId) {
        // 2. Fetch Basic, PNF, Residence, Work and Academic Record starting from estudiantes
        $stmt = $pdo->prepare('
            SELECT e.*, 
                   p.carrera, p.trayecto, p.trimestre_actual, p.codigo_estudiante,
                   r.tipo_vivienda, r.direccion as dir_residencia, r.telefono as tel_residencia,
                   t.lugar as empresa, t.ingreso as cargo_trabajo, t.monto_bs as sueldo,
                   ra.indice_trimestre, ra.n_materias_inscritas, ra.n_materias_aprobadas,
                   eb.estado as estado_beca
            FROM estudiantes e 
            LEFT JOIN pnfs p ON e.pnf_id = p.id 
            LEFT JOIN residencias r ON e.id = r.estudiante_id
            LEFT JOIN trabajos t ON e.id = t.estudiante_id
            LEFT JOIN records_academicos ra ON e.id = ra.estudiante_id
            LEFT JOIN estudiante_becas eb ON e.id = eb.estudiante_id
            WHERE e.usuario_id = ?
        ');
        $stmt->execute([$userId]);
        $studentInfo = $stmt->fetch();

        // If no record in 'estudiantes' yet, we provide a placeholder with the username (CI)
        if (!$studentInfo) {
            $studentInfo = [
                'id' => null,
                'ci' => $_SESSION['user'],
                'nombres' => 'Estudiante',
                'apellidos' => '(Pendiente)',
                'usuario_id' => $userId
            ];
        } else {
            // 3. Fetch Familiares (if student record exists)
            $stmtFam = $pdo->prepare('SELECT * FROM familiares WHERE estudiante_id = ?');
            $stmtFam->execute([$studentInfo['id']]);
            $studentInfo['familiares'] = $stmtFam->fetchAll();

            // 4. Fetch Materias Inscritas
            $stmtMat = $pdo->prepare('
                SELECT m.nombre 
                FROM estudiante_materias em 
                JOIN materias m ON em.materia_id = m.id 
                WHERE em.estudiante_id = ?
            ');
            $stmtMat->execute([$studentInfo['id']]);
            $studentInfo['materias_inscritas'] = $stmtMat->fetchAll(PDO::FETCH_COLUMN);
        }
    }
}
?>

<script>
    window.studentProfile = <?php echo json_encode($studentInfo); ?>;
    document.addEventListener('DOMContentLoaded',function(){document.body.classList.add('page-visible');});
</script>
    <button id="menuToggleSPA" class="menu-icon" aria-label="Abrir menú">☰</button>
    <nav id="slideMenuSPA" class="slide-menu" aria-hidden="true">
        <ul>
            <?php if(isset($_SESSION['rol']) && $_SESSION['rol']==='admin'): ?>
                <li><a href="admin/index.php" class="admin-link">Administración</a></li>
            <?php endif; ?>
            <li><a href="#" data-step="1" onclick="if(window.loadStep)window.loadStep(1);return false;">1. Identificación</a></li>
            <li><a href="#" data-step="2" onclick="if(window.loadStep)window.loadStep(2);return false;">2. Residencia</a></li>
            <li><a href="#" data-step="3" onclick="if(window.loadStep)window.loadStep(3);return false;">3. Laboral</a></li>
            <li><a href="#" data-step="4" onclick="if(window.loadStep)window.loadStep(4);return false;">4. PNF</a></li>
            <li><a href="#" data-step="5" onclick="if(window.loadStep)window.loadStep(5);return false;">5. Materias</a></li>
            <li><a href="#" data-step="6" onclick="if(window.loadStep)window.loadStep(6);return false;">6. Record</a></li>
            <li><a href="#" data-step="7" onclick="if(window.loadStep)window.loadStep(7);return false;">7. Familiares</a></li>
            <li><a href="#" data-step="8" onclick="if(window.loadStep)window.loadStep(8);return false;">8. Datos extra</a></li>
            <li><a href="#" data-step="9" onclick="if(window.loadStep)window.loadStep(9);return false;">9. Verificación</a></li>
            <li><a href="#" data-step="10" onclick="if(window.loadStep)window.loadStep(10);return false;">10. Final</a></li>
            <li class="logout-item"><a href="logout.php" class="logout-link" onclick="if(typeof closeMenuSPA==='function'){closeMenuSPA();}">Cerrar sesión</a></li>
        </ul>
        <div class="menu-footer">
            <!-- mantenimiento del enlace de logout en el pie opcional -->
            <a href="logout.php" class="logout-link" onclick="if(typeof closeMenuSPA==='function'){closeMenuSPA();}">Cerrar sesión</a>
        </div>
    </nav>
    <div id="main-container">
        <div class="page-header">
            <h2>SISTEMA DE SOLICITUD DE BECAS</h2>
            <div style="color: #666; font-size: 0.9rem; margin-top: 5px;">
                Bienvenido, <strong><?php echo htmlspecialchars($_SESSION['user']); ?></strong> 
                (<?php echo ucfirst($_SESSION['rol']); ?>)
            </div>
        </div>
        
        <div class="progress-wrapper">
            <div id="progressBar" class="progress-bar"></div>
        </div>

        <form id="becaForm">
            <div id="dynamic-content"></div>

            <div class="nav-buttons">
                <button type="button" id="prevBtn" class="btn-prev">← Anterior</button>
                <button type="button" id="nextBtn" class="btn-next">Siguiente</button>
            </div>
        </form>
    </div>

    <script src="main.js?v=<?php echo time(); ?>"></script>

    <script src="Paginas/7. Pagina Familiares/script.js"></script>

    <script>
        (function(){
            var toggle = document.getElementById('menuToggleSPA');
            var slide = document.getElementById('slideMenuSPA');
            function openMenu(){ if(slide){ slide.classList.add('open'); slide.setAttribute('aria-hidden','false'); } }
            function closeMenu(){ if(slide){ slide.classList.remove('open'); slide.setAttribute('aria-hidden','true'); } }
            // exponer la función para uso externo (logout)
            window.closeMenuSPA = closeMenu;
            if(toggle){ toggle.addEventListener('click', function(e){ e.stopPropagation(); if(slide.classList.contains('open')) closeMenu(); else openMenu(); }); }
            document.addEventListener('click', function(e){ if(slide && !slide.contains(e.target) && e.target !== toggle) closeMenu(); });
            window.addEventListener('keydown', function(e){ if(e.key==='Escape') closeMenu(); });
            // global navigation fading/overlay
            function prepareLeave(e){
                var overlay = document.getElementById('loadingOverlay');
                if(overlay) overlay.classList.add('active');
                document.body.classList.add('fade-out');
                setTimeout(function(){ window.location = e.target.href; },200);
                e.preventDefault();
            }
            var logoutLinks = document.querySelectorAll('a.logout-link');
            logoutLinks.forEach(function(link){ link.addEventListener('click', prepareLeave); });
            var navLinks = document.querySelectorAll('a[href]:not([href^="#"])');
            navLinks.forEach(function(link){
                if(!link.classList.contains('logout-link')){
                    link.addEventListener('click', function(e){
                        var url = new URL(link.href, window.location);
                        if(url.host === window.location.host){ prepareLeave(e); }
                    });
                }
            });
        })();
    </script>

    <footer class="site-footer">
        <div style="max-width:900px;margin:0 auto;padding:18px 12px;color:#666;text-align:center;">
            <small>UPTAG - Sistema de Solicitudes · &copy; <?php echo date('Y'); ?></small>
        </div>
    </footer>

    <!-- Alert Modal Structure UPTAG-Style -->
    <?php if (!empty($alertas)): 
        $main_alert = $alertas[0]; 
    ?>
    <div class="alert-modal-overlay active" id="alertModalOverlay">
        <div class="alert-modal-box">
            <span class="alert-modal-close" id="closeAlertModal">&times;</span>
            <?php if($main_alert['imagen_url']): ?>
                <img src="<?= htmlspecialchars($main_alert['imagen_url']) ?>" class="alert-modal-img" alt="Anuncio">
            <?php endif; ?>
            <div class="alert-modal-body">
                <h2 class="alert-modal-title"><?= htmlspecialchars($main_alert['titulo']) ?></h2>
                <div class="alert-modal-text"><?= nl2br(htmlspecialchars($main_alert['mensaje'])) ?></div>
                <div style="margin-top: 20px;">
                    <button id="closeAlertBtn" style="background: #FF6600; color: white; border: none; padding: 10px 20px; border-radius: 5px; font-weight: bold; cursor: pointer;">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        (function(){
            var modal = document.getElementById('alertModalOverlay');
            var closeX = document.getElementById('closeAlertModal');
            var closeBtn = document.getElementById('closeAlertBtn');

            function closeModal() {
                modal.classList.remove('active');
                setTimeout(function(){ modal.style.display = 'none'; }, 300);
            }

            if(closeX) closeX.onclick = closeModal;
            if(closeBtn) closeBtn.onclick = closeModal;
            
            modal.onclick = function(e) {
                if (e.target === modal) closeModal();
            };
        })();
    </script>
    <?php endif; ?>
</body>
</html>