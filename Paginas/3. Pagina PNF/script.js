/**
 * ARCHIVO: Paginas/3. Pagina PNF/script.js
 */
window.initPNF = () => {
    const trayectoSelect = document.getElementById('trayectoSelect');
    const trimestreSelect = document.getElementById('trimestreSelect');
    const warning = document.getElementById('warningTrayecto');
    const nextBtn = document.getElementById('nextBtn');
    const iraInput = document.getElementById('ira_anterior');

    // --- Lógica de validación para el IRA ---
    if (iraInput) {
        iraInput.addEventListener('input', (e) => {
            let val = e.target.value;

            // 1. Evitar múltiples ceros a la izquierda (ej: "0005" -> "5")
            // Pero permitimos "0." para decimales
            if (val.length > 1 && val.startsWith('0') && val[1] !== '.') {
                val = val.replace(/^0+/, '');
                e.target.value = val;
            }

            let numericValue = parseFloat(val);

            // 2. Controlar el rango 0 - 20
            if (numericValue > 20) {
                e.target.value = 20;
            } else if (numericValue < 0) {
                e.target.value = 0;
            }

            // 3. Limitar longitud total para evitar que "rompan" el diseño con mil números
            if (val.length > 5) { // Ejemplo: "19.99" son 5 caracteres
                e.target.value = val.slice(0, 5);
            }
        });

        // Bloqueo de teclas problemáticas
        iraInput.addEventListener('keydown', (e) => {
            const invalidChars = ["-", "+", "e"];
            if (invalidChars.includes(e.key)) {
                e.preventDefault();
            }
        });
    }
    

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