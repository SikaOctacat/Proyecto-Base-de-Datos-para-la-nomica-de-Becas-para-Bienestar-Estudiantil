<?php if(session_status()==PHP_SESSION_NONE) session_start(); ?>
<div class="step-content">
    <h2>6. Datos Adicionales</h2>
    <p style="font-size: 0.85rem; color: #666; margin-bottom: 15px;">
        Use este espacio para añadir cualquier información relevante sobre su situación socioeconómica que no haya sido mencionada anteriormente.
    </p>
    
    <div style="position: relative;">
        <textarea 
            name="comentarios" 
            id="comentarios" 
            rows="6" 
            maxlength="500" 
            placeholder="Describa brevemente sus observaciones..."
            style="width: 100%; padding: 12px; border: 1px solid #ccc; border-radius: 8px; resize: none; font-family: inherit;"
        ></textarea>
        
        <div id="char-counter" style="text-align: right; font-size: 0.8rem; color: #888; margin-top: 5px;">
            <span id="chars-used">0</span> / 500 caracteres
        </div>
    </div>
</div>

<footer class="site-footer">
    <div style="max-width:900px;margin:0 auto;padding:12px;color:#666;text-align:center;">
        <small>UPTAG - Sistema de Solicitudes · &copy; <?php echo date('Y'); ?></small>
    </div>
</footer>