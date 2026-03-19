<?php if(session_status()==PHP_SESSION_NONE) session_start(); ?>
<footer class="site-footer">
    <div style="max-width:900px;margin:0 auto;padding:12px;color:#666;text-align:center;">
        <small>UPTAG - Sistema de Solicitudes · &copy; <?php echo date('Y'); ?></small>
    </div>
</footer>
<div class="step-content">
    <h2>5. Familiares del Estudiante</h2>
    <p style="font-size: 0.8rem; color: #666; margin-bottom: 10px;">Indique las personas que conviven con usted (Solo puedes añadir hasta 5 familiares).</p>
    
    <div style="background: rgba(255,102,0,0.05); padding: 12px; border-radius: 8px; margin-bottom: 15px; border: 1px dashed var(--primary);">
        <label class="radio-item" style="justify-content: flex-start; gap: 10px; cursor: pointer;">
            <input type="checkbox" id="no-familiares" name="no_familiares" style="width: 20px !important; height: 20px !important;">
            <span style="font-weight: 600; color: #444;">No convivo con familiares / Vivo solo</span>
        </label>
    </div>

    <div class="table-responsive">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background-color: #f4f4f4;">
                    <th>Nombre</th>
                    <th>Apellido</th>
                    <th>Parentesco</th>
                    <th style="width: 60px;">Edad</th>
                    <th>Instrucción</th>
                    <th>Ocupación</th>
                    <th>Ingreso (Bs)</th>
                </tr>
            </thead>
            <tbody id="cuerpo-tabla">
                </tbody>
        </table>
    </div>

    <div class="controles-tabla" style="margin-top: 15px; display: flex; align-items: center; gap: 15px;">
        <button type="button" id="btn-agregar" class="btn-add" 
                style="background: #00dc0b; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer;">
            + Añadir Familiar
        </button>
        <span id="aviso-estado" style="font-size: 0.85rem; font-weight: 600;"></span>
    </div>
</div>