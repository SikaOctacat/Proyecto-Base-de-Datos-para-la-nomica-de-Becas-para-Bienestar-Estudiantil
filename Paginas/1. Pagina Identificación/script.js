/**
 * Lógica Paso 1: Identificación
 * (Debe estar en el ámbito global, NO dentro del HTML inyectado)
 */
function initIdentificacion() {
    // El delay asegura que el navegador ya haya dibujado el HTML del fetch
    setTimeout(() => {
        const fNacInput = document.getElementById('f_nac');
        const edadInput = document.getElementById('edad');

        if (fNacInput && edadInput) {
            const actualizar = () => {
                const edadCalculada = calcularEdad(fNacInput.value);
                edadInput.value = edadCalculada;
                // Guardar inmediatamente en el storage global
                if(window.formDataStorage) {
                    window.formDataStorage['edad'] = edadCalculada;
                }
            };

            // Escuchar cambios en tiempo real
            fNacInput.addEventListener('input', actualizar);
            fNacInput.addEventListener('change', actualizar);

            // Cargar edad si ya existe un valor previo (al navegar atrás/adelante)
            if (fNacInput.value) actualizar();
        }
    }, 100);
}

function calcularEdad(fecha) {
    if (!fecha) return "00";
    const hoy = new Date();
    const cumpleanos = new Date(fecha);
    
    // Verificación de fecha válida
    if (isNaN(cumpleanos.getTime())) return "00";

    let edad = hoy.getFullYear() - cumpleanos.getFullYear();
    const mes = hoy.getMonth() - cumpleanos.getMonth();
    
    if (mes < 0 || (mes === 0 && hoy.getDate() < cumpleanos.getDate())) {
        edad--;
    }
    
    return edad >= 0 ? edad.toString().padStart(2, '0') : "00";
}

function validarPaso1() {
    const checkActivo = document.getElementById('check_activo');
    const edadCampo = document.getElementById('edad');
    
    // Convertimos a número para la comparación
    const edadVal = parseInt(edadCampo.value, 10) || 0;

    // 1. Validar Check de Activo
    if (!checkActivo || !checkActivo.checked) {
        alert("⚠️ No puedes continuar: Debes confirmar que te encuentras activo en tus estudios.");
        return false;
    }

    // 2. Validar Rango de Edad (17 a 39 años)
    if (edadVal < 17 || edadVal > 39) {
        alert(`⚠️ Edad no permitida (${edadVal} años): Debes tener entre 17 y 39 años para solicitar la beca.`);
        return false;
    }

    return true; 
}