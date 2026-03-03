/**
 * FUNCIONES DEL PASO 1 (Ámbito global)
 * Ubicación: Paginas/1. Pagina Identificacion/script.js
 * * Este script maneja la lógica específica de la primera pantalla, incluyendo
 * el cálculo automático de la edad y las validaciones de elegibilidad.
 */

/**
 * Inicializa los eventos de la página de identificación.
 * Se ejecuta automáticamente cuando loadStep(1) carga este script.
 */
function initIdentificacion() {
    const fNacInput = document.getElementById('f_nac');
    const edadInput = document.getElementById('edad');

    // 1. Configurar límites de fecha (lo que ya tenías)
    if (fNacInput) {
        const hoy = new Date();
        fNacInput.max = new Date(hoy.getFullYear() - 5, hoy.getMonth(), hoy.getDate()).toISOString().split('T')[0];
        fNacInput.min = (hoy.getFullYear() - 100) + "-01-01";
    }

    // 2. Función para guardar Radio Buttons en el storage global
    const guardarRadios = (name) => {
        const seleccionado = document.querySelector(`input[name="${name}"]:checked`);
        if (window.formDataStorage) {
            window.formDataStorage[name] = seleccionado ? seleccionado.value : null;
        }
    };

    // 3. Cargar datos previos si existen (Persistencia)
    if (window.formDataStorage) {
        ['trabaja', 'viaja', 'activo'].forEach(name => {
            const valorPrevio = window.formDataStorage[name];
            if (valorPrevio) {
                const radio = document.querySelector(`input[name="${name}"][value="${valorPrevio}"]`);
                if (radio) radio.checked = true;
            }
        });
    }

    // 4. Listeners para cambios
    if (fNacInput && edadInput) {
        const actualizarEdad = () => {
            const edadCalculada = calcularEdad(fNacInput.value);
            edadInput.value = edadCalculada;
            if(window.formDataStorage) window.formDataStorage['edad'] = edadCalculada;
        };
        fNacInput.addEventListener('change', actualizarEdad);
        if (fNacInput.value) actualizarEdad();
    }

    // Asignar guardado a los radios
    document.querySelectorAll('input[type="radio"]').forEach(radio => {
        radio.addEventListener('change', () => guardarRadios(radio.name));
    });
}

// Validación corregida para Radio Buttons
function validarPaso1() {
    const activoSi = document.getElementById('radio_activo_si');
    const edadCampo = document.getElementById('edad');
    const edad = parseInt(edadCampo.value, 10) || 0;

    if (!activoSi || !activoSi.checked) {
        alert("⚠️ No puedes continuar: Debes confirmar que te encuentras activo en tus estudios (Selecciona 'Sí').");
        return false;
    }

    if (edad < 17 || edad > 39) {
        alert(`⚠️ Edad no permitida (${edad} años): Debes tener entre 17 y 39 años.`);
        return false;
    }

    return true; 
}

/**
 * Lógica matemática para determinar la edad exacta basándose en la fecha actual
 * @param {string} fecha - Formato YYYY-MM-DD proveniente del input date
 * @returns {string} Edad formateada a dos dígitos (ej: "05", "25")
 */
function calcularEdad(fecha) {
    // Si no hay fecha seleccionada, retorna el valor por defecto
    if (!fecha) return "00";
    
    const hoy = new Date();
    const cumpleanos = new Date(fecha);
    
    // Cálculo inicial basado solo en el año
    let edad = hoy.getFullYear() - cumpleanos.getFullYear();
    
    // Ajuste fino: resta un año si aún no ha pasado el mes o el día del cumpleaños
    const mes = hoy.getMonth() - cumpleanos.getMonth();
    
    if (mes < 0 || (mes === 0 && hoy.getDate() < cumpleanos.getDate())) {
        edad--;
    }
    
    // Retorna la edad asegurando que sea positiva y con un cero a la izquierda si es menor a 10
    return edad >= 0 ? edad.toString().padStart(2, '0') : "00";
}

/**
 * Función de validación requerida por el flujo principal (main.js)
 * Verifica que el usuario cumpla con los requisitos mínimos de estado y edad.
 * @returns {boolean} True si permite avanzar, False si bloquea el flujo.
 */
function validarPaso1() {
    const activoSi = document.getElementById('radio_activo_si');
    const edadCampo = document.getElementById('edad');
    const edad = parseInt(edadCampo.value, 10) || 0;

    if (!activoSi || !activoSi.checked) {
        alert("⚠️ No puedes continuar: Debes confirmar que te encuentras activo en tus estudios (Selecciona 'Sí').");
        return false;
    }

    if (edad < 17 || edad > 39) {
        alert(`⚠️ Edad no permitida (${edad} años): Debes tener entre 17 y 39 años.`);
        return false;
    }

    return true; 
}