<?php if(session_status()==PHP_SESSION_NONE) session_start(); ?>
<footer class="site-footer">
    <div style="max-width:900px;margin:0 auto;padding:12px;color:#666;text-align:center;">
        <small>UPTAG - Sistema de Solicitudes · &copy; <?php echo date('Y'); ?></small>
    </div>
</footer>
<div class="step-content">
    <h2>6. Récord Académico</h2>
    <p>Complete la información sobre su desempeño en el trayecto anterior.</p>

    <div class="grid-container">
        <div>
            <label>Materias Inscritas:</label>
            <input type="number" id="m_ins" name="m_ins" readonly>
        </div>

        <div>
            <label>Materias Aprobadas:</label>
            <input type="number" id="m_apr" name="m_apr" min="0" placeholder="0" required>
        </div>

        <div>
            <label>Materias Inasistentes:</label>
            <input type="number" id="m_ina" name="m_ina" min="0" placeholder="0" required>
        </div>

        <div>
            <label>Índice del Trimestre:</label>
            <input type="number" id="record_indice" name="record_indice" min="0" max="20" step="0.01" placeholder="0.00" required>
        </div>

        <div class="full-width">
            <label>Indice de Rendimiento Académico (IRA):</label>
            <input type="number" id="m_ira" name="m_ira" min="0" max="100" step="0.01" placeholder="0.00" required>
        </div>
    </div>
</div>