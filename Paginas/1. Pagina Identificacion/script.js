/**
 * FUNCIONES DEL PASO 1 (Ámbito global)
 * Ubicación: Paginas/1. Pagina Identificacion/script.js
 */

/**
 * Inicializa los eventos de la página.
 */
function initIdentificacion() {
    const fNacInput = document.getElementById('f_nac');
    const edadInput = document.getElementById('edad');

    if (fNacInput && edadInput) {
        const actualizar = () => {
            const edadCalculada = calcularEdad(fNacInput.value);
            edadInput.value = edadCalculada;
            if(window.formDataStorage) window.formDataStorage['edad'] = edadCalculada;
        };

        fNacInput.addEventListener('input', actualizar);
        fNacInput.addEventListener('change', actualizar);

        if (fNacInput.value) actualizar();
    }
}

/**
 * Calcula la edad exacta.
 */
function calcularEdad(fecha) {
    if (!fecha) return "00";
    const hoy = new Date();
    const cumpleanos = new Date(fecha);
    let edad = hoy.getFullYear() - cumpleanos.getFullYear();
    const mes = hoy.getMonth() - cumpleanos.getMonth();
    
    if (mes < 0 || (mes === 0 && hoy.getDate() < cumpleanos.getDate())) {
        edad--;
    }
    return edad >= 0 ? edad.toString().padStart(2, '0') : "00";
}

/**
 * Alterna la visibilidad de la contraseña
 */
function togglePassword(idInput) {
    const input = document.getElementById(idInput);
    if (input) {
        input.type = input.type === "password" ? "text" : "password";
    }
}

/**
 * Función de validación (Paso 1)
 */
function validarPaso1() {
    // --- 1. CAPTURA DE DATOS ---
    const cedula = document.getElementById('cedula')?.value || "";
    const codEst = document.getElementById('cod_est')?.value || "";
    const telf = document.querySelector('input[name="tel_estudiante"]')?.value || "";
    const pass = document.getElementById('reg_password')?.value || "";
    const passConfirm = document.getElementById('reg_password_confirm')?.value || "";
    
    const trabaja = document.querySelector('input[name="trabaja"]:checked')?.value;
    const estatus = document.querySelector('input[name="estatus_estudio"]:checked')?.value;

    // --- 2. PRIORIDAD ALTA: CRITERIOS DE EXCLUSIÓN ---
    // Si no cumple esto, no importa lo demás.
    if (trabaja === "si") {
        alert("⚠️ No puedes solicitar la beca si posees un empleo actualmente.");
        return false;
    }

    if (estatus === "inactivo") {
        alert("⚠️ Debes estar activo en tus estudios para solicitar el beneficio.");
        return false;
    }

    // --- 3. PRIORIDAD MEDIA: IDENTIDAD Y OBLIGATORIOS ---
    if (cedula.length < 6 || cedula.length > 8) {
        alert("⚠️ La cédula debe tener entre 6 y 8 dígitos.");
        return false;
    }

    if (pass.length < 4) {
        alert("⚠️ La contraseña debe tener al menos 4 caracteres.");
        return false;
    }

    if (pass !== passConfirm) {
        alert("⚠️ Las contraseñas no coinciden.");
        return false;
    }

    // --- 4. PRIORIDAD BAJA: CAMPOS OPCIONALES (Validación Condicional) ---
    // Solo validamos si el usuario empezó a escribir algo.
    
    // Validación de Teléfono (si no está vacío)
    if (telf.trim() !== "") {
        const regexTel = /^(0414|0424|0412|0416|0426|0268)[0-9]{7}$/;
        if (!regexTel.test(telf)) {
            alert("⚠️ El formato del teléfono es inválido (Ej: 04121234567).");
            return false;
        }
    }

    // Validación de Código de Estudiante (si no está vacío)
    if (codEst.trim() !== "") {
        if (codEst.length !== 10) {
            alert("⚠️ El código de estudiante debe tener exactamente 10 dígitos.");
            return false;
        }
    }

    return true; // Todo correcto
}