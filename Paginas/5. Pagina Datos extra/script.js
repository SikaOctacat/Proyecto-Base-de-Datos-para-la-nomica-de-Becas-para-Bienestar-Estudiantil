/**
 * ARCHIVO: Paginas/6. Datos Adicionales/script.js
 */
function initDatosExtra() {
    const textarea = document.getElementById('comentarios');
    const display = document.getElementById('chars-used');
    const counterContainer = document.getElementById('char-counter');

    if (!textarea || !display) return;

    const actualizarContador = () => {
        const longitud = textarea.value.length;
        display.textContent = longitud;

        // Feedback visual: se pone naranja al llegar a 450 y rojo al llegar a 500
        if (longitud >= 500) {
            counterContainer.style.color = "#d9534f";
            counterContainer.style.fontWeight = "bold";
        } else if (longitud >= 450) {
            counterContainer.style.color = "#ff6600";
            counterContainer.style.fontWeight = "normal";
        } else {
            counterContainer.style.color = "#888";
            counterContainer.style.fontWeight = "normal";
        }
    };

    // Escuchar la escritura
    textarea.addEventListener('input', actualizarContador);

    // Cargar datos si ya existen en el storage
    if (window.formDataStorage && window.formDataStorage['comentarios']) {
        textarea.value = window.formDataStorage['comentarios'];
        actualizarContador();
    }
}