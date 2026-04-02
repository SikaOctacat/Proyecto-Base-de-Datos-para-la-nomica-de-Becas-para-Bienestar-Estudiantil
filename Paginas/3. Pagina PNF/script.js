/**
 * ARCHIVO: Paginas/3. Pagina PNF/script.js
 */
window.initPNF = () => {
    const trayectoSelect = document.getElementById('trayectoSelect');
    const trimestreSelect = document.getElementById('trimestreSelect');
    const warning = document.getElementById('warningTrayecto');
    const nextBtn = document.getElementById('nextBtn');

    const gestionarBloqueoInicial = () => {
        if (!trayectoSelect || !trimestreSelect || !nextBtn) return;

        if (trayectoSelect.value === 'inicial') {
            // 1. Mostrar advertencia
            if (warning) warning.style.display = 'block';

            // 2. Forzar Trimestre 1 y bloquear el campo
            trimestreSelect.value = "1";
            trimestreSelect.disabled = true;
            trimestreSelect.classList.add('input-disabled');

            // 3. Bloqueo absoluto del botón Siguiente
            nextBtn.disabled = true;
            nextBtn.style.opacity = "0.5";
            nextBtn.style.cursor = "not-allowed";
            
            // Evitamos que cualquier otra validación lo active
            nextBtn.dataset.locked = "true";
        } else {
            // Restaurar estado normal
            if (warning) warning.style.display = 'none';
            trimestreSelect.disabled = false;
            trimestreSelect.classList.remove('input-disabled');
            nextBtn.dataset.locked = "false";

            // Llamar a la validación general para ver si otros campos están listos
            if (typeof window.validarFormularioActual === 'function') {
                window.validarFormularioActual();
            }
        }
    };

    if (trayectoSelect) {
        trayectoSelect.addEventListener('change', gestionarBloqueoInicial);
        gestionarBloqueoInicial(); // Ejecución al cargar
    }
};