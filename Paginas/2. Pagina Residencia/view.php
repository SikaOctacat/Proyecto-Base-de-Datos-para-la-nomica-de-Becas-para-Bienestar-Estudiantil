<?php if(session_status()==PHP_SESSION_NONE) session_start(); ?>

<div class="step-content">
    <h2>2. Residencia / Vivienda</h2>
    <p style="font-size: 0.85rem; color: #666; margin-bottom: 20px;">Indique la ubicación y condiciones de su vivienda actual.</p>
    
    <div class="grid-container">
        
        <div>
            <label>Tipo de residencia</label>
            <select name="t_res" id="t_res" required>
                <option value="" disabled selected>Seleccione el tipo...</option>
                <option value="familiar">Familiar</option>
                <option value="particular">Particular</option>
                <option value="universitaria">Universitaria</option>
                <option value="otro">Otro...</option>
            </select>
        </div>

        <div>
            <label>Tipo de vivienda</label>
            <select name="t_viv" id="t_viv" required>
                <option value="" disabled selected>Seleccione el tipo...</option>
                <option value="casa">Casa</option>
                <option value="apartamento">Apartamento</option>
                <option value="vivienda_social">Vivienda de interés social</option>
                <option value="habitacion">Habitación</option>
                <option value="otro">Otro</option>
            </select>
        </div>

        <div>
            <label>Localidad</label> <select name="t_loc" id="t_loc" required>
                <option value="" disabled selected>Seleccione localidad...</option>
                <option value="urbano">Urbano</option>
                <option value="rural">Rural</option>
            </select>
        </div>

        <div>
            <label>Régimen de propiedad</label>
            <select name="r_prop" id="r_prop" required>
                <option value="" disabled selected>Seleccione régimen...</option>
                <option value="propia">Propia</option>
                <option value="alquilada">Alquilada</option>
                <option value="cedida">Cedida</option>
                <option value="comodato">Comodato</option>
                <option value="pagandola">Pagándola (Crédito)</option> </select>
        </div>

        <div>
            <label>Estado</label>
            <select name="estado_res" id="estado_res" required>
                <option value="" disabled selected>Cargando estados...</option>
            </select>
        </div>

        <div>
            <label>Municipio</label>
            <select name="municipio_res" id="municipio_res" required disabled>
                <option value="" disabled selected>Seleccione un estado primero</option>
            </select>
        </div>

        <div class="full-width">
            <label>Teléfono Local </label>
            <input type="tel" 
                   name="tel_local" 
                   id="tel_local"
                   placeholder="04121234567"
                   maxlength="11"
                   oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                   pattern="^(02)[0-9]{9}$"
                   style="width: 100%;">
        </div>

        <div class="full-width">
            <label>Dirección Local Exacta</label>
            <input type="text" name="dir_local" placeholder="Ej: Avenida X, Calle X, Casa Nro X" required>
        </div>

    </div>
    </div>