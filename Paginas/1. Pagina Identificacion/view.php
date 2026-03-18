<?php if(session_status()==PHP_SESSION_NONE) session_start(); ?>

<div class="step-content">
    <h2>1. Identificación del Estudiante</h2>
    <div class="grid-container">
        <div>
            <label>Nombres</label>
            <input type="text" name="nombres" placeholder="Ej: Juan Alberto" required>
        </div>
        <div>
            <label>Apellidos</label>
            <input type="text" name="apellidos" placeholder="Ej: Pérez García" required>
        </div>

        <div>
            <label>C.I.</label>
            <input type="text" 
                   name="cedula" 
                   placeholder="Ej: 28123456" 
                   oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                   maxlength="9"
                   required>
        </div>

        <div>
            <label>Teléfono</label>
            <input type="tel" 
                   name="tel_estudiante" 
                   placeholder="Ej: 04121234567"
                   maxlength="11"
                   oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                   pattern="^(0414|0424|0412|0416|0426|0268)[0-9]{7}$"
                   title="Debe ser un formato válido (04XX o 02XX seguido de 7 números)">
        </div>

        <div>
            <label>Fecha de Nacimiento</label>
            <input type="date" name="f_nac" id="f_nac" required>
        </div>

        <div>
            <label>Edad</label>
            <input type="number" 
                id="edad" 
                name="edad" 
                placeholder="00" 
                readonly 
                tabindex="-1"
                style="background-color: #f0f0f0; cursor: not-allowed; font-weight: bold;">
        </div>
        <div>
            <label>Estado Civil</label>
            <select name="edo_civil">
                <option value="" disabled selected>Seleccione su estado civil...</option>
                <option value="soltero">Soltero/a</option>
                <option value="casado">Casado/a</option>
                <option value="divorciado">Divorciado/a</option>
                <option value="viudo">Viudo/a</option>
            </select>
        </div>

        <div>
            <label>Email</label>
            <input type="email" name="correo" placeholder="ejemplo@gmail.com">
        </div>

        <div class="full-width">
            <label>Código del Carnet de la Patria</label>
            <input type="text" 
                   name="C_Patria" 
                   placeholder="Ej: 0012345678 (10 dígitos)" 
                   maxlength="10"
                   oninput="this.value = this.value.replace(/[^0-9]/g, '')">
        </div>
    </div>

    <?php if(!isset($_SESSION['user']) || (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin')): ?>
    <div style="margin-top: 25px; padding-top: 20px; border-top: 2px dashed #eee;">
        <h3 style="color: #FF6600; font-size: 1rem; margin-bottom: 15px;">🔐 Seguridad de la Cuenta</h3>
        <p style="font-size: 0.85rem; color: #666; margin-bottom: 15px;">Defina una contraseña para consultar su estatus posteriormente. Su usuario será su número de cédula.</p>
        <div class="grid-container">
            <div>
                <label>Contraseña</label>
                <input type="password" name="password" id="reg_password" placeholder="Mínimo 4 caracteres" required minlength="4">
            </div>
            <div>
                <label>Confirmar Contraseña</label>
                <input type="password" id="reg_password_confirm" placeholder="Repita su contraseña" required minlength="4">
            </div>
        </div>
    </div>
    <script>
        // Validación básica de coincidencia de contraseñas
        document.getElementById('reg_password_confirm').addEventListener('input', function() {
            const pass = document.getElementById('reg_password').value;
            if (this.value !== pass) {
                this.setCustomValidity('Las contraseñas no coinciden');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
    <?php endif; ?>

    <div class="checkbox-group" style="display: flex; flex-direction: column; gap: 8px; margin-top: 15px;">
        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
            ¿Posees un trabajo?
            <input type="checkbox" name="trabaja" id="check_trabaja" style="width: auto;"> 
        </label>
        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
            ¿Viajas frecuentemente?
            <input type="checkbox" name="viaja" style="width: auto;"> 
        </label>
        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
            ¿Te encuentras activo en tus estudios?
            <input type="checkbox" name="activo" id="check_activo" style="width: auto;"> 
        </label>
    </div>
</div>