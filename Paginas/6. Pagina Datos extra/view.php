<?php if(session_status()==PHP_SESSION_NONE) session_start(); ?>
<footer class="site-footer">
    <div style="max-width:900px;margin:0 auto;padding:12px;color:#666;text-align:center;">
        <small>UPTAG - Sistema de Solicitudes · &copy; <?php echo date('Y'); ?></small>
    </div>
</footer>
<div class="step">
        <h2>6. Datos Adicionales</h2>
        <textarea name="comentarios" rows="4" placeholder="Observaciones máximas 500 caracteres..."></textarea>
</div>