<?php if(session_status()==PHP_SESSION_NONE) session_start(); ?>
<div class="step-content">
    <h2>3. Información del PNF</h2>
    <div class="grid-container">
        <div class="full-width">
            <label>Carrera (PNF)</label>
            <select name="carrera" id="carreraSelect" required>
                <option value="" disabled selected>Seleccione su PNF...</option>
                <option value="administracion">Administración de Empresas</option>
                <option value="agroalimentacion">Agroalimentación</option>
                <option value="automotriz">Mantenimiento de Sistemas Automotrices</option>
                <option value="construccion_civil">Construcción Civil</option>
                <option value="contaduria">Contaduría Pública</option>
                <option value="electricidad">Electricidad</option>
                <option value="electronica">Electrónica</option>
                <option value="informatica">Informática</option>
                <option value="instrumentacion">Instrumentación y Control</option>
                <option value="mecanica">Mecánica</option>
                <option value="procesos_quimicos">Procesos Químicos</option>
                <option value="quimica">Química</option>
            </select>
        </div>

        <div class="full-width">
            <label>Fecha Ingreso a la UPTAG</label>
            <input type="date" name="f_ingreso" required>
        </div>

        <div>
            <label>Trayecto actual</label>
            <select name="trayecto" id="trayectoSelect" required>
                <option value="" disabled selected>Seleccione su trayecto...</option>
                <option value="inicial">Trayecto Inicial</option>
                <option value="1">Trayecto I</option>
                <option value="2">Trayecto II</option>
                <option value="3">Trayecto III</option>
                <option value="4">Trayecto IV</option>
            </select>
        </div>
        <div>
            <label>Trimestre actual</label>
            <select name="trimestre" required id="trimestreSelect">
                <option value="" disabled selected>Seleccione su trimestre...</option>
                <option value="1">1er Trimestre</option>
                <option value="2">2do Trimestre</option>
                <option value="3">3er Trimestre</option>
            </select>
        </div>
    </div>

    <div id="warningTrayecto" style="display:none; margin-top:15px; padding:15px; background:#fff3cd; color:#856404; border-radius:10px; border:1px solid #ffeeba; font-size:0.9rem;">
        ⚠️ Los estudiantes de Trayecto Inicial no son elegibles para la beca hasta poseer un récord académico aprobado en Trayecto I.
    </div>
</div>