<?php if(session_status()==PHP_SESSION_NONE) session_start(); ?>

<div class="step-content">
    <h2>2. Residencia / Vivienda</h2>
    <div class="grid-container">
        
        <div>
            <label>Tipo de residencia
                <select name="t_res" id="t_res" required>
                    <option value="" disabled selected>Seleccione el tipo...</option>
                    <option value="familiar">Familiar</option>
                    <option value="particular">Particular</option>
                    <option value="universitaria">Universitaria</option>
                    <option value="otro">Otro...</option>
                </select>
            </label>
        </div>

        <div>
            <label>Tipo de vivienda
                <select name="t_viv" id="t_viv" required>
                    <option value="" disabled selected>Seleccione el tipo...</option>
                    <option value="casa">Casa</option>
                    <option value="apartamento">Apartamento</option>
                    <option value="vivienda_social">Vivienda de interés social</option>
                    <option value="habitacion">Habitación</option>
                    <option value="otro">Otro</option>
                </select>
            </label>
        </div>

        <div>
            <label>Tipo de localidad
                <select name="t_loc" id="t_loc" required>
                    <option value="" disabled selected>Seleccione localidad...</option>
                    <option value="urbano">Urbano</option>
                    <option value="rural">Rural</option>
                </select>
            </label>
        </div>

        <div>
            <label>Régimen de propiedad
                <select name="r_prop" id="r_prop" required>
                    <option value="" disabled selected>Seleccione régimen...</option>
                    <option value="propia">Propia</option>
                    <option value="alquilada">Alquilada</option>
                    <option value="cedida">Cedida</option>
                    <option value="comodato">Comodato</option>
                    <option value="pagando">Pagándola (Crédito)</option>
                </select>
            </label>
        </div>

        <div>
            <label>Estado (Donde reside actualmente)
                <input type="text" name="estado_res" placeholder="Ej: Falcón" required>
            </label>
        </div>

        <div>
            <label>Municipio (Donde reside actualmente)
                <input type="text" name="municipio_res" placeholder="Ej: Miranda" required>
            </label>
        </div>

        <div class="full-width">
            <label>Dirección Local Exacta
                <input type="text" name="dir_local" placeholder="Ej: Avenida X, Calle X, Casa Nro X" required>
            </label>
        </div>

        <div>
            <label>Teléfono Local
                <input type="tel" 
                       name="tel_local" 
                       placeholder="Ej: 02681234567"
                       maxlength="11"
                       oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                       pattern="^(02)[0-9]{9}$"
                       title="Solo números. Debe empezar por 02. Ejemplo: 02681234567"
                       required>
            </label>
        </div>

    </div>

    <div class="pregunta-row" style="border-top: 1px solid #f0f0f0; padding-top: 15px;">
        <span>¿Paga usted la vivienda?</span>
        <div style="display: flex; gap: 15px;">
            <label class="radio-item"><input type="radio" name="estatus_estudio" value="activo" required> Sí</label>
            <label class="radio-item"><input type="radio" name="estatus_estudio" value="inactivo"> No</label>
        </div>
    </div>
</div>