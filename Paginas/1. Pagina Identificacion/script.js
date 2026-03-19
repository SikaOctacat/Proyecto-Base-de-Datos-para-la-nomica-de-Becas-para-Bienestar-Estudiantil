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
 * Función de validación (SIN ASYNC para evitar que el flujo continúe)
 */
function validarPaso1() {
    const radioActivo = document.querySelector('input[name="estatus_estudio"]:checked');
    const edadCampo = document.getElementById('edad');
    const cedulaCampo = document.getElementById('cedula');
    const codEstCampo = document.getElementById('cod_est');

    // Obtenemos valores
    const edad = parseInt(edadCampo?.value, 10) || 0;
    const cedula = cedulaCampo?.value || "";
    const codEst = codEstCampo?.value || "";

    // 1. Validaciones de longitud (Cédula: 7-8 dígitos)
    if (cedula.length < 7 || cedula.length > 8) {
        alert("⚠️ La cédula debe tener 7 u 8 dígitos.");
        return false;
    }

    // 2. Validación Código de Estudiante (Exactamente 10)
    if (codEst.length !== 10) {
        alert("⚠️ El código de estudiante debe tener exactamente 10 dígitos.");
        return false;
    }

    // 3. Validación de Estatus de estudio
    if (!radioActivo || radioActivo.value !== 'activo') {
        alert("⚠️ No puedes continuar: Debes confirmar que te encuentras activo en tus estudios.");
        return false;
    }

    // 4. Validación de Edad (17 a 39 años)
    if (edad < 17 || edad > 39) {
        alert(`⚠️ Edad no permitida (${edad} años): Debes tener entre 17 y 39 años.`);
        return false;
    }

    // Si todo está bien, permitimos el avance
    return true; 
}