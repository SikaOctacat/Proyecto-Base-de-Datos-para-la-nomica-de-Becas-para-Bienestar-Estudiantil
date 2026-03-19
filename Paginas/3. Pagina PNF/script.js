/**
 * ARCHIVO: Paginas/3. Pagina PNF/script.js
 */
window.initPNF = () => {
    const trayectoSelect = document.getElementById('trayectoSelect');
    const warning = document.getElementById('warningTrayecto');
    const nextBtn = document.getElementById('nextBtn');

    const validarElegibilidad = () => {
        if (trayectoSelect.value === 'inicial') {
            warning.style.display = 'block';
            // Bloqueamos el botón independientemente de si los campos están llenos
            nextBtn.disabled = true;
            nextBtn.style.opacity = "0.5";
            nextBtn.style.cursor = "not-allowed";
        } else {
            warning.style.display = 'none';
            // Dejamos que la validación general de main.js tome el control
            if (typeof validarFormularioActual === 'function') {
                validarFormularioActual();
            }
        }
    };

    if (trayectoSelect) {
        trayectoSelect.addEventListener('change', validarElegibilidad);
        // Ejecución inicial por si el dato ya estaba cargado en el storage
        validarElegibilidad();
    }
};