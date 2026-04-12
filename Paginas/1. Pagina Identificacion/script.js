/**
 * FUNCIONES DEL PASO 1 (Ámbito global)
 * Ubicación: Paginas/1. Pagina Identificacion/script.js
 */

/**
 * Inicializa los eventos de la página.
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
        // Si el pass principal ya tiene valor (recuperado del storage), lo copiamos al confirm
        if (passInput.value && !passConfirmInput.value) {
            passConfirmInput.value = passInput.value;
        }

        // 2. LÓGICA DE VALIDACIÓN EN TIEMPO REAL
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

            // IMPORTANTE: Llamamos a la validación global para que el botón Siguiente se active
            if (typeof window.validarFormularioActual === 'function') {
                window.validarFormularioActual();
            }
        };

        passInput.addEventListener('input', validarMatch);
        passConfirmInput.addEventListener('input', validarMatch);
    }

    // Lógica de edad (se mantiene)
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
 * Evita que el usuario borre el prefijo 'INT'
 */
function prevenirBorradoPrefijo(e, input) {
    // Si el cursor está en las primeras 3 posiciones y presiona borrar
    if (input.selectionStart <= 3 && (e.key === 'Backspace' || e.key === 'Delete')) {
        e.preventDefault();
    }
}

/**
 * Mantiene el prefijo y solo permite números después de él
 */
function validarSoloNumerosPostPrefijo(input) {
    const prefijo = "INT";
    const guia = document.getElementById('placeholder-guia');
    let valorActual = input.value.toUpperCase();

    // 1. Restaurar prefijo si se intenta borrar
    if (valorActual.length < 3 || !valorActual.startsWith(prefijo)) {
        input.value = prefijo;
    } else {
        const parteNumerica = valorActual.substring(3).replace(/[^\d]/g, '');
        input.value = prefijo + parteNumerica;
    }

    // 2. Lógica del Placeholder simulado
    if (guia) {
        // Si el valor es solo "INT", mostramos la guía. Si hay más, la ocultamos.
        guia.style.display = (input.value === prefijo) ? 'block' : 'none';
    }

    if (typeof window.validarFormularioActual === 'function') {
        window.validarFormularioActual();
    }
}

/**
 * Función de validación (Paso 1)
 */
/**
 * Función de validación (Paso 1) mejorada con RegEx para Email y Código UPTAG
 */
function validarPaso1() {
    // --- 1. CAPTURA DE DATOS ---
    const cedula = document.getElementById('cedula')?.value || "";
    const codEst = document.getElementById('cod_est')?.value || "";
    const telf = document.querySelector('input[name="tel_estudiante"]')?.value || "";
    const correo = document.querySelector('input[name="correo"]')?.value || ""; // Captura el correo
    const pass = document.getElementById('reg_password')?.value || "";
    const passConfirm = document.getElementById('reg_password_confirm')?.value || "";
    const edadValor = parseInt(document.getElementById('edad')?.value) || 0; // Capturamos la edad calculada
    const trabaja = document.querySelector('input[name="trabaja"]:checked')?.value;
    const estatus = document.querySelector('input[name="estatus_estudio"]:checked')?.value;
    
    // --- 2. PRIORIDAD ALTA: CRITERIOS DE EXCLUSIÓN ---

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

    // --- 3. PRIORIDAD MEDIA: IDENTIDAD, EMAIL Y CONTRASEÑA ---
    
    // Validación de Cédula
    if (cedula.length < 6 || cedula.length > 8) {
        alert("⚠️ La cédula debe tener entre 6 y 8 dígitos.");
        return false;
    }
    
    // Validación de Teléfono
    if (telf.trim() !== "") {
        const regexTel = /^(0414|0424|0412|0416|0426|0268)[0-9]{7}$/;
        if (!regexTel.test(telf)) {
            alert("⚠️ El formato del teléfono es inválido (Ej: 04121234567).");
            return false;
        }
    }

    // NUEVA: Validación Rigurosa de Email
    const regexEmail = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    if (!regexEmail.test(correo)) {
        alert("⚠️ Por favor, ingresa un correo electrónico válido (Ej: usuario@gmail.com).");
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

    // --- VALIDACIÓN DE CÓDIGO DE ESTUDIANTE ---
    // Ahora solo verificamos que tenga el largo exacto de 10 (INT + 7 números)
    if (codEst.length !== 10) {
        alert("⚠️ El código de estudiante debe tener el prefijo INT seguido de 7 números (Total 10 caracteres).");
        return false;
    }

    // --- 4. PRIORIDAD BAJA: VALIDACIÓN CONDICIONAL ---

    const carnetPatria = document.querySelector('input[name="C_Patria"]')?.value || "";

    // Validación de Carnet de la Patria (Si no está vacío)
    if (carnetPatria.trim() !== "") {
        if (carnetPatria.length !== 10) {
            alert("⚠️ El serial del Carnet de la Patria debe tener exactamente 10 dígitos.");
            return false;
        }
    }

    return true;

}