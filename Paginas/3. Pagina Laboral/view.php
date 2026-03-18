<?php if(session_status()==PHP_SESSION_NONE) session_start(); ?>
<footer class="site-footer">
    <div style="max-width:900px;margin:0 auto;padding:12px;color:#666;text-align:center;">
        <small>UPTAG - Sistema de Solicitudes · &copy; <?php echo date('Y'); ?></small>
    </div>
</footer>
<div class="step-content">
    <h2>3. Información Laboral</h2>
    <div class="grid-container">
        <div>
            <label>¿Cuál es su trabajo?</label>
            <input type="text" name="cargo" placeholder="Ej: Asistente Administrativo">
        </div>
        <div>
            <label>¿En dónde trabaja?</label>
            <input type="text" name="empresa" placeholder="Ej: Alcaldía de Miranda / Independiente">
        </div>
        
        <div>
            <label>Ingresos</label>
            <div class="input-symbol-wrapper">
                <input type="number" name="ingresos" placeholder="0.00" step="0.01" oninput="this.value = this.value.replace(/[^0-9.]/g, '')">
                <span class="suffix">$</span>
            </div>
        </div>

        <div>
            <label>¿De quién recibe los aportes?</label>
            <input type="text" name="aportador" placeholder="Ej: Padres / Propio / Otro...">
        </div>

        <div>
            <label>Monto </label>
            <div class="input-symbol-wrapper">
                <input type="number" name="monto_bs" placeholder="0.00" step="0.01" oninput="this.value = this.value.replace(/[^0-9.]/g, '')">
                <span class="suffix">Bs</span>
            </div>
        </div>
    </div>
</div>