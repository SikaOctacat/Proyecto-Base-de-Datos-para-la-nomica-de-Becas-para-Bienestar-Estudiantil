/**
 * FUNCIONES DEL PASO 1 (Ámbito global)
 * Ubicación: Paginas/1. Pagina Identificacion/script.js
 */

/**
 * Inicializa los eventos de la página.
 */
function initIdentificacion() {
    // 1. ACTIVAR VALIDACIÓN EN TIEMPO REAL AL SALIR DEL ENFOQUE Y AL ESCRIBIR
    // Se pasa el ID del contenedor del Paso 1 para aislar los eventos.
    aplicarValidacionEnTiempoReal('paso-1-contenedor');

    const fNacInput = document.getElementById('f_nac');
    const edadInput = document.getElementById('edad');
    const passInput = document.getElementById('reg_password');
    const passConfirmInput = document.getElementById('reg_password_confirm');
    const errorText = document.getElementById('error-pass');

    if (passInput && passConfirmInput) {
        // Sincronización inmediata al cargar
        if (passInput.value && !passConfirmInput.value) {
            passConfirmInput.value = passInput.value;
        }

        // Lógica de validación de contraseñas en tiempo real
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
            
            // Forzar la validación inline del campo edad al recalcularse dinámicamente
            validarCampoIndividual(edadInput);

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
 * Escucha global de eventos de validación mediante delegación.
 * Maneja 'blur' para evaluar al salir e 'input' para limpiar errores al escribir.
 */
function aplicarValidacionEnTiempoReal(formContainerId) {
    const contenedor = document.getElementById(formContainerId);
    if (!contenedor) return;

    // 1. Fase de captura para el 'blur' (cuando el usuario sale del input)
    contenedor.addEventListener('blur', function(evento) {
        const campo = evento.target;
        if (['INPUT', 'SELECT', 'TEXTAREA'].includes(campo.tagName) && campo.name) {
            validarCampoIndividual(campo);
        }
    }, true);

    // 2. Evento 'input' para re-evaluar inmediatamente MIENTRAS escribe
    contenedor.addEventListener('input', function(evento) {
        const campo = evento.target;
        if (['INPUT', 'SELECT', 'TEXTAREA'].includes(campo.tagName) && campo.name) {
            // Si el campo ya tiene un error visual previo, re-validamos en tiempo real para quitarlo apenas cumpla
            const contenedorPadre = campo.id === 'cod_est' ? campo.parentElement.parentElement : campo.parentElement;
            if (contenedorPadre.querySelector('.error-feedback-inline') || campo.style.borderColor === 'rgb(211, 47, 47)') {
                validarCampoIndividual(campo);
            }
        }
    });
}

/**
 * Centraliza las reglas de validación en tiempo real por campo.
 */
function validarCampoIndividual(campo) {
    let mensajeError = "";
    const valor = campo.value.trim();

    // 1. Validación de campo obligatorio (Required)
    if (campo.hasAttribute('required') && valor === "") {
        if (campo.type !== 'radio') {
            mensajeError = "Este campo es obligatorio.";
        }
    } 
    // 2. Evaluaciones de formato y lógica de negocio si el campo no está vacío
    else if (valor !== "") {
        switch (campo.name) {
            case 'cedula':
                if (valor.length < 6 || valor.length > 8) {
                    mensajeError = "La cédula debe tener entre 6 y 8 dígitos.";
                }
                break;
                
            case 'tel_estudiante':
                const regexTel = /^(0414|0424|0412|0416|0426|0422|0268)[0-9]{7}$/;
                if (!regexTel.test(valor)) {
                    mensajeError = "Formato inválido. Use 11 dígitos y numeros iniciales permitidos.";
                }
                break;

            case 'correo':
                const regexEmail = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                if (!regexEmail.test(valor)) {
                    mensajeError = "Por favor, ingresa un correo electrónico válido.";
                }
                break;

            case 'cod_est':
                if (valor.length !== 10) {
                    mensajeError = "Debe tener exactamente 10 caracteres (Prefijo + 7 números).";
                }
                break;

            case 'C_Patria':
                if (valor.length !== 10) {
                    mensajeError = "El serial debe tener exactamente 10 dígitos.";
                }
                break;
                
            case 'edad':
                const edadValor = parseInt(valor) || 0;
                if (edadValor < 17 || edadValor > 39) {
                    mensajeError = "El programa de becas está dirigido a personas entre 17 y 39 años.";
                }
                break;

            case 'trabaja':
                const trabajaChecked = document.querySelector('input[name="trabaja"]:checked')?.value;
                if (trabajaChecked === "si") {
                    mensajeError = "No puedes solicitar el beneficio si posees un empleo actualmente.";
                }
                break;

            case 'estatus_estudio':
                const estatusChecked = document.querySelector('input[name="estatus_estudio"]:checked')?.value;
                if (estatusChecked === "inactivo") {
                    mensajeError = "Debes estar activo en tus estudios para solicitar el beneficio.";
                }
                break;
        }
    }

    // Gestionar la respuesta en la interfaz
    gestionarMensajeErrorVisual(campo, mensajeError);
}

/**
 * Controla la inyección y destrucción de los mensajes de alerta en el DOM.
 */
function gestionarMensajeErrorVisual(campo, mensaje) {
    // Ubicar contenedor nativo del input
    let contenedorPadre = campo.parentElement;
    
    // Si el campo está dentro del nuevo wrapper de contraseña, el papá real donde va el error es el contenedor superior
    if (campo.type === 'password' && contenedorPadre.classList.contains('input-wrapper')) {
        contenedorPadre = campo.parentElement.parentElement;
    }
    
    // Tratamiento especial de jerarquía para el input decorado de código de estudiante
    if (campo.id === 'cod_est') {
        contenedorPadre = campo.parentElement.parentElement;
    }

    let errorSpan = contenedorPadre.querySelector('.error-feedback-inline');

    if (mensaje) {
        // Estilización de error
        campo.style.borderColor = '#d32f2f';
        campo.setCustomValidity(mensaje); // Previene envíos accidentales del formulario

        if (!errorSpan) {
            errorSpan = document.createElement('div');
            errorSpan.className = 'error-feedback-inline';
            errorSpan.style.color = '#d32f2f';
            errorSpan.style.fontSize = '0.75rem';
            errorSpan.style.marginTop = '5px';
            errorSpan.style.fontWeight = 'bold';
            contenedorPadre.appendChild(errorSpan);
        }
        errorSpan.textContent = `⚠️ ${mensaje}`;
    } else {
        // Restauración a estados normales
        if (campo.id === 'cedula' && campo.validationMessage === "Esta cédula ya está registrada.") {
            return; 
        }

        campo.style.borderColor = '#ddd';
        campo.setCustomValidity("");
        
        if (errorSpan) {
            errorSpan.remove();
        }
    }
}

/**
 * Función de validación (Paso 1)
 * Se ejecuta al hacer clic en "Siguiente" como doble capa de seguridad estricta
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
    
    if (telf.trim() !== "") {
        const regexTel = /^(0414|0424|0412|0416|0426|0422|0268)[0-9]{7}$/;
        if (!regexTel.test(telf)) {
            alert("⚠️ El número de teléfono debe cumplir con los formatos: 0414, 0424, 0412, 0416, 0426, 0422, 0268 y debe tener 11 dígitos");
            return false;
        }
    }

    const regexEmail = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    if (!regexEmail.test(correo)) {
        alert("⚠️ Por favor, ingresa un correo electrónico válido.");
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

    // --- 4. VALIDACIÓN DE CÓDIGO DE ESTUDIANTE ---
    if (codEst.length !== 10) {
        alert("⚠️ El código de estudiante es inválido. Debe tener exactamente 10 caracteres (Prefijo + 7 números).");
        return false;
    }

    const carnetPatria = document.querySelector('input[name="C_Patria"]')?.value || "";
    if (carnetPatria.trim() !== "" && carnetPatria.length !== 10) {
        alert("⚠️ El serial del Carnet de la Patria debe tener exactamente 10 dígitos.");
        return false;
    }

    return true;
}