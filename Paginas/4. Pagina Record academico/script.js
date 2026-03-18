/**
 * Inicializa la lógica del récord académico (Paso 4 actual)
 */
function initRecord() {
    // Intentamos recuperar lo que ya esté en el storage para no borrarlo al recargar
    const savedIns = window.formDataStorage['m_ins'];
    const inputIns = document.getElementById('m_ins');

    if (inputIns) {
        // Si hay materias 'mat_' en el storage (por si acaso), las contamos
        const checkCount = Object.keys(window.formDataStorage)
            .filter(key => key.startsWith('mat_') && window.formDataStorage[key] === true)
            .length;

        // Si el conteo es > 0, lo usamos. Si no, respetamos lo que el usuario ya escribió
        // o dejamos que el campo sea editable.
        if (checkCount > 0) {
            inputIns.value = checkCount;
            window.formDataStorage['m_ins'] = checkCount;
        } else if (savedIns) {
            inputIns.value = savedIns;
        }
    }
}

/**
 * Función de validación corregida
 */
function validarRecord() {
    // 1. Verificamos que los elementos EXISTAN antes de pedir el .value
    // Esto evita que el botón "no reaccione" si un ID cambió de nombre
    const elIns = document.getElementById('m_ins');
    const elApr = document.getElementById('m_apr');
    const elIna = document.getElementById('m_ina');
    const elInd = document.getElementById('record_indice');

    if (!elIns || !elApr || !elIna || !elInd) {
        console.error("Error: Faltan campos de entrada en el HTML del Récord Académico.");
        return true; // Dejamos pasar para no "romper" el flujo si falta un ID
    }

    const ins = parseInt(elIns.value) || 0;
    const apr = parseInt(elApr.value) || 0;
    const ina = parseInt(elIna.value) || 0;
    const indice = parseFloat(elInd.value) || 0;

    // 2. Validación de campos vacíos (opcional pero recomendada)
    if (ins === 0) {
        alert("⚠️ Por favor, ingresa el número de materias inscritas.");
        return false;
    }

    // 3. Lógica aritmética
    if ((apr + ina) > ins) {
        alert(`⚠️ Error: La suma de aprobadas (${apr}) e inasistentes (${ina}) no puede ser mayor al total de inscritas (${ins}).`);
        return false;
    }

    if (indice > 20 || indice < 0) {
        alert("⚠️ El índice debe estar entre 0 y 20 puntos.");
        return false;
    }

    return true;
}