<?php
require 'db.php';
// register.php ahora funciona como el formulario de postulación abierta
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Postulación de Beca - Sistema de Becas</title>
    <link rel="stylesheet" href="style.css">
    <link rel="icon" href="img/logo.png" type="image/png">
    <style>
        #main-container { margin-top: 60px; }
        .page-header h2 { font-size: 1.8rem; }
    </style>
</head>
<body data-rol="publico">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <div id="loadingOverlay" class="loading-overlay"><div class="spinner"></div></div>
    <script>
        window.studentProfile = null; // No hay perfil previo para nuevos postulantes
        document.addEventListener('DOMContentLoaded',function(){document.body.classList.add('page-visible');});
    </script>

    <div id="main-container">
        <div class="page-header">
            <h2>FORMULARIO DE POSTULACIÓN</h2>
            <div style="color: #666; font-size: 0.9rem; margin-top: 5px;">
                Complete todos los pasos para registrar su solicitud de beca.
            </div>
            <button type="button" id="btnLimpiarRegistro" class="btn-clear-data" 
                    style="position: absolute; top: 25px; right: 25px; background: #d32f2f; color: white; border: none; padding: 8px 15px; border-radius: 6px; font-size: 0.75rem; cursor: pointer; font-weight: bold; transition: all 0.3s; box-shadow: 0 2px 5px rgba(0,0,0,0.1);">
                Borrar Formulario
            </button>

        </div>
        
        <div class="progress-wrapper">
            <div id="progressBar" class="progress-bar"></div>
        </div>

        <form id="becaForm">
            <div id="dynamic-content" style="min-height: 200px;">
                <div id="form-placeholder" style="text-align:center; padding:60px; color:#888; font-style:italic;">
                    Cargando componentes del sistema...
                </div>
            </div>

            <div class="nav-buttons">
                <button type="button" id="prevBtn" class="btn-prev">← Anterior</button>
                <button type="button" id="nextBtn" class="btn-next">Siguiente</button>
            </div>
        </form>
    </div>

    <script src="main.js?v=<?php echo time(); ?>"></script>
    <script>
        // Asegurar que las flechas de volver funcionen y se limpie la navegación
        window.addEventListener('pageshow', function(event) {
            document.body.classList.remove('fade-out');
            var overlay = document.getElementById('loadingOverlay');
            if(overlay) overlay.classList.remove('active');
        });
    </script>
    <?php include 'footer.php'; ?>
    <footer class="site-footer">
        <div style="max-width:900px;margin:0 auto;padding:18px 12px;color:#666;text-align:center;">
            <small>UPTAG - Sistema de Solicitudes · &copy; <?php echo date('Y'); ?></small>
        </div>
    </footer>
</body>
</html>
