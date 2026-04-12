window.initPNF = () => {
    const trayectoSelect = document.getElementById('trayectoSelect');
    const trimestreSelect = document.getElementById('trimestreSelect');
    const warning = document.getElementById('warningTrayecto');
    const nextBtn = document.getElementById('nextBtn');
    const iraInput = document.getElementById('ira_anterior');

    if (iraInput && nextBtn) {
        iraInput.addEventListener('input', (e) => {
            let val = e.target.value;
            // Limpieza básica
            if (val.length > 1 && val.startsWith('0') && val[1] !== '.') {
                val = val.replace(/^0+/, '');
                e.target.value = val;
            }
            let numericValue = parseFloat(val);
            if (numericValue > 20) e.target.value = 20;
            if (numericValue < 0) e.target.value = 0;

            // Feedback visual: Si es menor a 16 se ve "apagado" pero sigue activo para el alert
            if (numericValue < 16 || isNaN(numericValue)) {
                nextBtn.style.opacity = "0.5";
            } else {
                nextBtn.style.opacity = "1";
            }
        });
    }
    
    // --- LA SOLUCIÓN AL BLOQUEO ---
    if (nextBtn) {
        // Usamos capture: true para interceptar el evento ANTES de que el script de navegación actúe
        nextBtn.addEventListener('click', function(e) {
            // 1. Si el trayecto es inicial, bloqueo total
            if (nextBtn.dataset.locked === "true") {
                e.preventDefault();
                e.stopImmediatePropagation();
                return false;
            }

            // 2. Ejecutamos la validación del IRA
            if (!validarPaso3()) {
                // Si el alert sale (porque es < 16), detenemos el avance
                e.preventDefault(); 
                e.stopImmediatePropagation(); 
                return false;
            }

            // 3. SI LLEGA AQUÍ: No hacemos nada. 
            // Al no llamar a preventDefault(), el navegador permite que 
            // los otros scripts de "Siguiente" se ejecuten normalmente.
        }, { capture: true }); 
    }

    const gestionarBloqueoInicial = () => {
        if (!trayectoSelect || !nextBtn) return;
        if (trayectoSelect.value === 'inicial') {
            if (warning) warning.style.display = 'block';
            nextBtn.style.opacity = "0.5";
            nextBtn.dataset.locked = "true";
            nextBtn.disabled = true; // Bloqueo físico en inicial
        } else {
            if (warning) warning.style.display = 'none';
            nextBtn.dataset.locked = "false";
            nextBtn.disabled = false;
            if (iraInput) iraInput.dispatchEvent(new Event('input'));
        }
    };

    if (trayectoSelect) {
        trayectoSelect.addEventListener('change', gestionarBloqueoInicial);
        gestionarBloqueoInicial();
    }
};

function validarPaso3() {
    const iraInput = document.getElementById('ira_anterior');
    if (!iraInput) return true;
    const valor = parseFloat(iraInput.value);

    if (isNaN(valor) || valor < 16) {
        alert("⚠️ El IRA mínimo es de 16.00 puntos.");
        iraInput.focus();
        return false; 
    }
    return true; 
}