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
    // Referencias a los campos de fecha de nacimiento y el input (probablemente readonly) de edad
    const fNacInput = document.getElementById('f_nac');
    const edadInput = document.getElementById('edad');

    // Verifica que ambos elementos existan en el DOM antes de asignar eventos
    if (fNacInput && edadInput) {
        /**
         * Función interna para actualizar el campo de edad y el almacenamiento global.
         */
        const actualizar = () => {
            // Obtiene el cálculo basado en el valor actual del input tipo date
            const edadCalculada = calcularEdad(fNacInput.value);
            
            // Refleja el resultado en la interfaz de usuario
            edadInput.value = edadCalculada;
            
            // Sincroniza el dato con el objeto de persistencia global definido en main.js
            if(window.formDataStorage) window.formDataStorage['edad'] = edadCalculada;
        };

        // Escucha cambios tanto al escribir como al seleccionar en el calendario del navegador
        fNacInput.addEventListener('input', actualizar);
        fNacInput.addEventListener('change', actualizar);

        // Si el usuario regresa a esta página (Atrás), dispara el cálculo si ya había una fecha
        if (fNacInput.value) actualizar();
    }
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
    const checkActivo = document.getElementById('check_activo');
    const edadCampo = document.getElementById('edad');
    
    // Convierte el valor del campo edad a número entero para la comparación
    const edad = parseInt(edadCampo.value, 10) || 0;

    // Validación 1: El usuario debe marcar obligatoriamente el checkbox de "activo"
    if (!checkActivo || !checkActivo.checked) {
        alert("⚠️ No puedes continuar: Debes confirmar que te encuentras activo en tus estudios.");
        return false;
    }

    // Validación 2: Rango de edad permitido (17 a 39 años inclusive)
    if (edad < 17 || edad > 39) {
        alert(`⚠️ Edad no permitida (${edad} años): Debes tener entre 17 y 39 años para solicitar la beca.`);
        return false;
    }

    // Si pasa todas las pruebas, retorna true para permitir el cambio de paso
    return true; 
}