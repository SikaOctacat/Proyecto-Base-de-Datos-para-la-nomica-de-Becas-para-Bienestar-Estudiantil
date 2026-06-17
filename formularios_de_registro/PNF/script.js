window.initPNF = () => {
    const carreraSelect = document.getElementById('carreraSelect');
    const trayectoSelect = document.getElementById('trayectoSelect');
    const trimestreSelect = document.getElementById('trimestreSelect');
    const warning = document.getElementById('warningTrayecto');
    const nextBtn = document.getElementById('nextBtn');
    const iraInput = document.getElementById('ira_anterior');

    // --- LÓGICA PARA EL TRAYECTO V (ELECTRÓNICA) ---
    const gestionarTrayectosEspeciales = () => {
        // Obtenemos el texto de la opción seleccionada para mayor seguridad
        const carreraNombre = carreraSelect.options[carreraSelect.selectedIndex].text.toLowerCase();
        
        // Verificamos si existe la opción 5
        let opcion5 = trayectoSelect.querySelector('option[value="5"]');

        if (carreraNombre.includes('electrónica')) {
            if (!opcion5) {
                // Si es electrónica y no existe la opción, la creamos
                const nuevaOpcion = document.createElement('option');
                nuevaOpcion.value = "5";
                nuevaOpcion.textContent = "Trayecto V";
                trayectoSelect.appendChild(nuevaOpcion);
            }
        } else {
            // Si no es electrónica, quitamos la opción 5 si existe
            if (opcion5) {
                if (trayectoSelect.value === "5") trayectoSelect.value = ""; 
                opcion5.remove();
            }
        }
    };

    if (carreraSelect) {
        carreraSelect.addEventListener('change', gestionarTrayectosEspeciales);
    }
    // -----------------------------------------------

    // Tu lógica existente del IRA
    if (iraInput && nextBtn) {
        iraInput.addEventListener('input', (e) => {
            let val = e.target.value;
            // Limpieza de ceros a la izquierda
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