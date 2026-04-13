<?php 
if(session_status()==PHP_SESSION_NONE) session_start(); 

// Calculamos las fechas en PHP para que el HTML ya venga con los límites
$fecha_hoy = new DateTime();
$max_date = (clone $fecha_hoy)->modify('-5 years')->format('Y-m-d');
$min_date = (clone $fecha_hoy)->modify('-50 years')->format('Y-m-d');
?>

<style>
    .password-container { position: relative; }
    .btn-view-pass {
        position: absolute;
        right: 10px;
        top: 32px;
        background: none;
        border: none;
        cursor: pointer;
        font-size: 1.2rem;
        padding: 0;
    }
</style>

<div class="step-content">
    <h2>1. Identificación del Estudiante</h2>
    <p style="font-size: 0.85rem; color: #666; margin-bottom: 20px;">Por favor, complete sus datos personales con precisión.</p>
    
    <div class="grid-container">
        <div>
            <label>Primer Nombre</label>
            <input type="text" name="nombre1" placeholder="Ej: Juan" required>
        </div>
        <div>
            <label>Segundo Nombre</label>
            <input type="text" name="nombre2" placeholder="Ej: Alberto">
        </div>
        <div>
            <label>Apellido Paterno</label>
            <input type="text" name="apellido_paterno" placeholder="Ej: Pérez" required>
        </div>
        <div>
            <label>Apellido Materno</label>
            <input type="text" name="apellido_materno" placeholder="Ej: García" required>
        </div>

        <div>
            <label>C.I. (Máx. 8 dígitos)</label>
            <input type="text" name="cedula" id="cedula" placeholder="Ej: 28123456" 
                   oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 8)" 
                   maxlength="8" required>
        </div>

        <div>
            <label>Teléfono</label>
            <input type="tel" name="tel_estudiante" placeholder="Ej: 04121234567" maxlength="11" oninput="this.value = this.value.replace(/[^0-9]/g, '')" pattern="^(0414|0424|0412|0416|0426|0268)[0-9]{7}$" required>
        </div>

        <div>
            <label>Email</label>
            <input type="email" name="correo" placeholder="ejemplo@gmail.com" required>
        </div>

        <div>
            <label>Estado Civil</label>
            <select name="edo_civil" required>
                <option value="" disabled selected>Seleccione...</option>
                <option value="soltero">Soltero/a</option>
                <option value="casado">Casado/a</option>
                <option value="divorciado">Divorciado/a</option>
                <option value="viudo">Viudo/a</option>
            </select>
        </div>

        <div>
            <label>Fecha de Nacimiento</label>
            <input type="date" 
                   name="f_nac" 
                   id="f_nac" 
                   min="<?php echo $min_date; ?>" 
                   max="<?php echo $max_date; ?>" 
                   required>
        </div>
        
        <div>
            <label>Edad</label>
            <input  type="number" id="edad" name="edad" placeholder="00" readonly tabindex="-1"
                    style="background-color: #f0f0f0; cursor: not-allowed; font-weight: bold;" required>
        </div>

        <div>
            <label>Beneficio al cual aspira</label>
            <select name="tipo_beneficio" required>
                <option value="" disabled selected>Seleccione una opción...</option>
                <option value="Beca_Estudiantil/Universitaria">Beca Estudiantil/Universitaria</option>
                <option value="ayudantia">Ayudantía</option>
                <option value="preparaduria">Preparaduría</option>
            </select>
        </div>

        <div style="display: flex; flex-direction: column;">
        <label>Código de Estudiante</label>
        <div style="position: relative; width: 100%; background: #fff; border-radius: 8px; border: 1px solid #ddd; overflow: hidden;">
            <input type="text" 
                name="cod_est" 
                id="cod_est" 
                value="INT" 
                maxlength="10" 
                style="background: transparent; border: none; width: 100%; padding: 10px; position: relative; z-index: 2; outline: none; font-size: 1rem;"
                onkeydown="prevenirBorradoPrefijo(event, this)"
                oninput="validarSoloNumerosPostPrefijo(this)"
                required>
            
            <span id="placeholder-guia" 
                style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: #999; z-index: 1; pointer-events: none; font-size: 1rem; letter-spacing: 0.5px;">
                <span style="visibility: hidden;">INT</span>1234567
            </span>
        </div>
    </div>

        <div style="grid-column: span 1;">
            <label>Código del Carnet de la Patria</label>
            <input type="text" name="C_Patria" placeholder="Ej: 0012345678" maxlength="10" oninput="this.value = this.value.replace(/[^0-9]/g, '')">
        </div>
    </div>

    <?php if(!isset($_SESSION['user']) || (isset($_SESSION['rol']) && $_SESSION['rol'] === 'admin')): ?>
    <div style="margin-top: 25px; padding: 20px; border: 1px dashed #ccc; border-radius: 8px; background-color: #f9f9f9;">
        <h3 style="color: #FF6600; font-size: 1rem; margin-bottom: 10px;">🔐 Seguridad de la Cuenta</h3>
        <div class="grid-container">
            
            <div class="password-container">
                <label>Contraseña</label>
                <input type="password" name="password" id="reg_password" placeholder="Mínimo 4 caracteres" required minlength="4">
                <button type="button" class="btn-view-pass" onclick="togglePassword('reg_password')">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            
            <div class="password-container">
                <label>Confirmar Contraseña</label>
                <input type="password" id="reg_password_confirm" placeholder="Repita su contraseña" required minlength="4">
                <button type="button" class="btn-view-pass" onclick="togglePassword('reg_password_confirm')">
                    <i class="fas fa-eye"></i>
                </button>
            </div>

            <div>
                <label>Pregunta de Seguridad</label>
                <select name="pregunta_seguridad" id="pregunta_seguridad" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid #ddd; margin-bottom: 15px;">
                    <option value="" disabled selected>Seleccione una pregunta...</option>
                    <option value="Nombre de tu primera mascota">¿Nombre de tu primera mascota?</option>
                    <option value="Ciudad donde naciste">¿Ciudad donde naciste?</option>
                    <option value="Nombre de tu escuela primaria">¿Nombre de tu escuela primaria?</option>
                    <option value="Personaje de ficción favorito">¿Personaje de ficción favorito?</option>
                </select>
            </div>
            
            <div>
                <label>Respuesta de Seguridad</label>
                <input type="text" name="respuesta_seguridad" id="respuesta_seguridad" placeholder="Tu respuesta secreta" required>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="checkbox-group" style="margin-top: 20px; padding: 15px; background: #fff; border-radius: 8px; border: 1px solid #eee;">
        <div class="pregunta-row">
            <span>¿Viajas frecuentemente?</span>
            <div style="display: flex; gap: 15px;">
                <label class="radio-item"><input type="radio" name="viaja" value="si"> Sí</label>
                <label class="radio-item"><input type="radio" name="viaja" value="no"> No</label>
            </div>
        </div>

        <div class="pregunta-row" style="border-top: 1px solid #f0f0f0; padding-top: 15px; margin-top: 15px;">
            <span>¿Posees empleo actualmente?</span>
            <div style="display: flex; gap: 15px;">
                <label class="radio-item"><input type="radio" name="trabaja" value="si" required> Sí</label>
                <label class="radio-item"><input type="radio" name="trabaja" value="no"> No</label>
            </div>
        </div>

        <div class="pregunta-row" style="border-top: 1px solid #f0f0f0; padding-top: 15px; margin-top: 15px;">
            <span style="font-weight: bold; color: #d32f2f;">¿Te encuentras activo en tus estudios?</span>
            <div style="display: flex; gap: 15px;">
                <label class="radio-item"><input type="radio" name="estatus_estudio" value="activo" required> Sí</label>
                <label class="radio-item"><input type="radio" name="estatus_estudio" value="inactivo"> No</label>
            </div>
        </div>
    </div>
</div>

<script>
// Función para mostrar/ocultar contraseña
function togglePass(id) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
}

document.getElementById('f_nac').addEventListener('change', function() {
    const input = this;
    const hoy = new Date();
    const fechaNac = new Date(input.value);

    if (input.value > input.max || (input.value < input.min && input.value !== "")) {
        alert("La fecha no es válida. Debes tener entre 5 y 50 años.");
        input.value = "";
        document.getElementById('edad').value = "00";
        return;
    }

    if(!isNaN(fechaNac.getTime())){
        let edad = hoy.getFullYear() - fechaNac.getFullYear();
        const m = hoy.getMonth() - fechaNac.getMonth();
        if (m < 0 || (m === 0 && hoy.getDate() < fechaNac.getDate())) {
            edad--;
        }
        document.getElementById('edad').value = edad.toString().padStart(2, '0');
    }
});

const passConfirm = document.getElementById('reg_password_confirm');
if(passConfirm){
    passConfirm.addEventListener('input', function() {
        const pass = document.getElementById('reg_password').value;
        this.setCustomValidity(this.value !== pass ? 'Las contraseñas no coinciden' : '');
    });
}
</script>