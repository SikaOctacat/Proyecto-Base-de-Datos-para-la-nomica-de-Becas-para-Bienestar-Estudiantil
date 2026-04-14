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
    const passInput = document.getElementById('reg_password');
    const passConfirmInput = document.getElementById('reg_password_confirm');
    const errorText = document.getElementById('error-pass');

    if (passInput && passConfirmInput) {
        // 1. SINCRONIZACIÓN INMEDIATA AL CARGAR
        if (passInput.value && !passConfirmInput.value) {
            passConfirmInput.value = passInput.value;
        }

        // 2. LÓGICA DE VALIDACIÓN DE CONTRASEÑAS EN TIEMPO REAL
        const validarMatch = () => {
            if (passConfirmInput.value === "") {
                errorText.style.display = 'none';
                passConfirmInput.style.borderColor = '#ddd';
            } else if (passInput.value !== passConfirmInput.value) {
                errorText.style.display = 'block';
                passConfirmInput.style.borderColor = '#d32f2f';
            } else {
                errorText.style.display = 'none';
                passConfirmInput.style.borderColor = '#ddd';
            }

            if (typeof window.validarFormularioActual === 'function') {
                window.validarFormularioActual();
            }
        };

        passInput.addEventListener('input', validarMatch);
        passConfirmInput.addEventListener('input', validarMatch);
    }

    // Lógica de cálculo de edad automática
    if (fNacInput && edadInput) {
        const actualizar = () => {
            const edadCalculada = calcularEdad(fNacInput.value);
            edadInput.value = edadCalculada;
            if(window.formDataStorage) window.formDataStorage['edad'] = edadCalculada;
            if (typeof window.validarFormularioActual === 'function') window.validarFormularioActual();
        };
        fNacInput.addEventListener('input', actualizar);
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
 * Se ejecuta al hacer clic en "Siguiente"
 */
function validarPaso1() {
    // --- 1. CAPTURA DE DATOS ---
    const cedula = document.getElementById('cedula')?.value || "";
    const codEst = document.getElementById('cod_est')?.value || "";
    const telf = document.querySelector('input[name="tel_estudiante"]')?.value || "";
    const correo = document.querySelector('input[name="correo"]')?.value || "";
    const pass = document.getElementById('reg_password')?.value || "";
    const passConfirm = document.getElementById('reg_password_confirm')?.value || "";
    const edadValor = parseInt(document.getElementById('edad')?.value) || 0;
    const trabaja = document.querySelector('input[name="trabaja"]:checked')?.value;
    const estatus = document.querySelector('input[name="estatus_estudio"]:checked')?.value;
    
    // --- 2. CRITERIOS DE EXCLUSIÓN ---
    if (edadValor < 17 || edadValor > 39) {
        alert("⚠️ Lo sentimos, el programa de becas está dirigido a estudiantes entre 17 y 39 años.");
        return false;
    }
    if (trabaja === "si") {
        alert("⚠️ No puedes solicitar la beca si posees un empleo actualmente.");
        return false;
    }
    if (estatus === "inactivo") {
        alert("⚠️ Debes estar activo en tus estudios para solicitar el beneficio.");
        return false;
    }

    // --- 3. IDENTIDAD, EMAIL Y CONTRASEÑA ---
    if (cedula.length < 6 || cedula.length > 8) {
        alert("⚠️ La cédula debe tener entre 6 y 8 dígitos.");
        return false;
    }
    
    // Validación de Teléfono (Formatos venezolanos comunes)
    if (telf.trim() !== "") {
        const regexTel = /^(0414|0424|0412|0416|0426|0268)[0-9]{7}$/;
        if (!regexTel.test(telf)) {
            alert("⚠️ El formato del teléfono es inválido (Ej: 04121234567).");
            return false;
        }
    }

    // Validación de Email
    const regexEmail = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    if (!regexEmail.test(correo)) {
        alert("⚠️ Por favor, ingresa un correo electrónico válido.");
        return false;
    }

    // Validación de Contraseña
    if (pass.length < 4) {
        alert("⚠️ La contraseña debe tener al menos 4 caracteres.");
        return false;
    }
    if (pass !== passConfirm) {
        alert("⚠️ Las contraseñas no coinciden.");
        return false;
    }

    // --- 4. VALIDACIÓN DE CÓDIGO DE ESTUDIANTE (Solo Largo) ---
    // Verificamos que tenga exactamente 10 caracteres (Ej: INT1234567)
    if (codEst.length !== 10) {
        alert("⚠️ El código de estudiante es inválido. Debe tener exactamente 10 caracteres (Prefijo INT + 7 números).");
        return false;
    }

    // Validación opcional de Carnet de la Patria
    const carnetPatria = document.querySelector('input[name="C_Patria"]')?.value || "";
    if (carnetPatria.trim() !== "" && carnetPatria.length !== 10) {
        alert("⚠️ El serial del Carnet de la Patria debe tener exactamente 10 dígitos.");
        return false;
    }

    return true;
}