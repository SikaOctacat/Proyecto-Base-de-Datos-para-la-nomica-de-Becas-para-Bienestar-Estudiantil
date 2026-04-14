window.initPNF = () => {
    const trayectoSelect = document.getElementById('trayectoSelect');
    const trimestreSelect = document.getElementById('trimestreSelect');
    const warning = document.getElementById('warningTrayecto');
    const nextBtn = document.getElementById('nextBtn');
    const iraInput = document.getElementById('ira_anterior');

    if (iraInput && nextBtn) {
        iraInput.addEventListener('input', (e) => {
            let val = e.target.value;
            if (val.length > 1 && val.startsWith('0') && val[1] !== '.') {
                val = val.replace(/^0+/, '');
                e.target.value = val;
            }
            let numericValue = parseFloat(val);
            if (numericValue > 20) e.target.value = 20;
            if (numericValue < 0) e.target.value = 0;

            nextBtn.style.opacity = (numericValue < 16 || isNaN(numericValue)) ? "0.5" : "1";
        });
    }
    
    if (nextBtn) {
        nextBtn.addEventListener('click', function(e) {
            if (window.adminMode) return true;

            if (nextBtn.dataset.locked === "true") {
                e.preventDefault();
                e.stopImmediatePropagation();
                return false;
            }

            if (!validarPaso3()) {
                e.preventDefault(); 
                e.stopImmediatePropagation(); 
                return false;
            }
        }, { capture: true }); 
    }

    const gestionarBloqueoInicial = () => {
        if (!trayectoSelect || !nextBtn) return;

        if (trayectoSelect.value === 'inicial') {
            if (trimestreSelect) {
                trimestreSelect.selectedIndex = 0; 
                trimestreSelect.disabled = true;
                trimestreSelect.style.backgroundColor = "#f0f0f0";
            }
            if (warning) warning.style.display = 'block';
            nextBtn.style.opacity = "0.5";
            nextBtn.dataset.locked = "true";
            nextBtn.disabled = true;
        } else {
            if (trimestreSelect) {
                trimestreSelect.disabled = false;
                trimestreSelect.style.backgroundColor = "#fff";
                // Si está en el "Seleccione...", lo movemos al 1er trimestre automáticamente
                if (trimestreSelect.selectedIndex <= 0) {
                    trimestreSelect.selectedIndex = 1;
                }
            }
            if (warning) warning.style.display = 'none';
            nextBtn.dataset.locked = "false";
            nextBtn.disabled = false;
        }
    };

    if (trayectoSelect) {
        trayectoSelect.addEventListener('change', gestionarBloqueoInicial);
        gestionarBloqueoInicial();
    }
};

function validarPaso3() {
    const iraInput = document.getElementById('ira_anterior');
    const fIngresoInput = document.getElementById('f_ingreso'); 
    
    if (fIngresoInput && fIngresoInput.value) {
        // Convertimos el valor a un objeto Date
        const fechaIngreso = new Date(fIngresoInput.value + 'T00:00:00'); // Forzamos formato local
        const añoIngreso = fechaIngreso.getFullYear();
        const hoy = new Date();
        const añoActual = hoy.getFullYear();

        // 1. Validar año mínimo (2010) y que no sea futuro
        if (añoIngreso < (añoActual-30) || añoIngreso > añoActual) {
            alert("⚠️ Ingrese una año de entrada valido");
            fIngresoInput.focus();
            return false;
        }

        // 2. Validación extra por si el input date falla en navegadores viejos
        if (fechaIngreso > hoy) {
            alert("⚠️ La fecha de ingreso no puede ser una fecha futura.");
            fIngresoInput.focus();
            return false;
        }
    }

    // Validación del IRA (Mínimo 16)
    if (iraInput) {
        const valor = parseFloat(iraInput.value);
        if (isNaN(valor) || valor < 16) {
            alert("⚠️ El promedio del IRA debe ser mínimo de 16 para poder continuar.");
            iraInput.focus();
            return false; 
        }
    }
    return true; 
}