<?php if(session_status()==PHP_SESSION_NONE) session_start(); ?>
<div class="step-content">
    <h2>9. Verificación de Datos</h2>
    <p style="margin-bottom: 20px;">Revisa tu información antes de finalizar. Si algo está mal, puedes volver atrás y corregirlo.</p>
    
    <div id="resumen" class="resumen-box" style="border: 1px solid #ddd; border-radius: 8px; background: #fff; padding: 20px;">
        </div>

    <div style="margin-top: 20px; text-align: center;">
        <button type="button" onclick="resetFormulario()" 
                style="background: #e74040; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer;">
            Reiniciar Formulario
        </button>
    </div>
</div>
<footer class="site-footer">
    <div style="max-width:900px;margin:0 auto;padding:12px;color:#666;text-align:center;">
        <small>UPTAG - Sistema de Solicitudes · &copy; <?php echo date('Y'); ?></small>
    </div>
</footer>