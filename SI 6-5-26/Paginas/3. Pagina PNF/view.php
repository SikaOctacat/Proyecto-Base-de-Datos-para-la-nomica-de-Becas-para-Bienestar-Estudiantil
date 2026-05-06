<?php 
if(session_status() == PHP_SESSION_NONE) session_start(); 

// Leer el archivo JSON
$json_path = '../../carreras.json';
$carreras = [];

if (file_exists($json_path)) {
    $json_content = file_get_contents($json_path);
    $carreras = json_decode($json_content, true);
}
?>
<div class="step-content">
    <h2>3. Información del PNF</h2>
    <div class="grid-container">
        <div class="full-width">
            <label>Carrera (PNF)</label>
            <select name="carrera" id="carreraSelect" required>
                <option value="" disabled selected>Seleccione su PNF...</option>
                <?php if (!empty($carreras)): ?>
                    <?php foreach ($carreras as $carrera): ?>
                        <option value="<?php echo htmlspecialchars($carrera['id']); ?>">
                            <?php echo htmlspecialchars($carrera['nombre']); ?>
                        </option>
                    <?php endforeach; ?>
                <?php else: ?>
                    <option value="">Error al cargar carreras</option>
                <?php endif; ?>
            </select>
        </div>

        <div class="full-width">
            <label>Fecha Ingreso a la UPTAG</label>
            <input type="date" name="f_ingreso" id="f_ingreso" required>
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

        <div class="full-width">
            <label for="ira_anterior">Índice de Rendimiento Académico (IRA)</label>
            <div class="input-group-ira">
                <input type="number" 
                    name="ira_anterior" 
                    id="ira_anterior" 
                    step="0.01" 
                    placeholder="0.00" 
                    required>
                <span class="input-suffix">/20</span>
            </div>
        </div>
    </div>

    <div id="warningTrayecto" style="display:none; margin-top:15px; padding:15px; background:#fff3cd; color:#856404; border-radius:10px; border:1px solid #ffeeba; font-size:0.9rem;">
        ⚠️ Los estudiantes de Trayecto Inicial no son elegibles para la beca hasta poseer un récord académico aprobado en Trayecto I.
    </div>
</div>