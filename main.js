/**
 * FUNCIONES DEL PASO 1 (Ámbito global)
 */
function initIdentificacion() {
    const fNacInput = document.getElementById('f_nac');
    const edadInput = document.getElementById('edad');

    if (fNacInput && edadInput) {
        const actualizar = () => {
            const edadCalculada = calcularEdad(fNacInput.value);
            edadInput.value = edadCalculada;
            if(window.formDataStorage) window.formDataStorage['edad'] = edadCalculada;
        };

        fNacInput.addEventListener('input', actualizar);
        fNacInput.addEventListener('change', actualizar);

        if (fNacInput.value) actualizar();
    }
}

function calcularEdad(fecha) {
    if (!fecha) return "00";
    const hoy = new Date();
    const cumpleanos = new Date(fecha);
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
    const edad = parseInt(edadCampo.value, 10) || 0;

    if (!checkActivo || !checkActivo.checked) {
        alert("⚠️ No puedes continuar: Debes confirmar que te encuentras activo en tus estudios.");
        return false;
    }

    if (edad < 17 || edad > 39) {
        alert(`⚠️ Edad no permitida (${edad} años): Debes tener entre 17 y 39 años para solicitar la beca.`);
        return false;
    }

    return true; 
}

/**
 * ARCHIVO: main.js (Raíz) - Lógica Principal Integrada
 */
document.addEventListener('DOMContentLoaded', () => {
    let currentStep = 1; 
    const totalSteps = 10;
    const viewPort = document.getElementById('dynamic-content');
    const progressBar = document.getElementById('progressBar');
    const mainContainer = document.getElementById('main-container');
    window.formDataStorage = {}; 

    // --- INTEGRACIÓN DEL BOTÓN DE LIMPIEZA ---
    const btnLimpiar = document.createElement('button');
    btnLimpiar.className = 'btn-clear-data'; // Usa el estilo de tu CSS
    btnLimpiar.innerHTML = 'Borrar Campos';
    mainContainer.appendChild(btnLimpiar);

    btnLimpiar.onclick = () => {
        if (confirm("¿Estás seguro de que deseas borrar los datos de esta página?")) {
            const inputs = viewPort.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                if (input.type === 'checkbox') input.checked = false;
                else input.value = '';
                if (input.name) delete window.formDataStorage[input.name];
            });
            // Reset manual para campos calculados
            if (currentStep === 1 && document.getElementById('edad')) {
                document.getElementById('edad').value = '00';
            }
        }
    };

    const folderMap = {
        1: "1. Pagina Identificación",
        2: "2. Pagina Residencia",
        3: "3. Pagina Laboral",
        4: "4. Pagina PNF",
        5: "5. Pagina Materias",
        6: "6. Pagina Record academico",
        7: "7. Pagina Familiares",
        8: "8. Pagina Datos extra",
        9: "9. Verificación",
        10: "10. Pantalla final"
    };

    window.saveCurrentData = () => {
        const inputs = viewPort.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            if (input.name) {
                window.formDataStorage[input.name] = (input.type === 'checkbox') ? input.checked : input.value;
            }
        });
    };

    window.restoreDataGlobal = () => {
        const inputs = viewPort.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            if (window.formDataStorage[input.name] !== undefined) {
                if (input.type === 'checkbox') input.checked = window.formDataStorage[input.name];
                else input.value = window.formDataStorage[input.name];
            }
        });
    };

    async function loadStep(stepNumber) {
        const folder = folderMap[stepNumber];
        const htmlPath = `Paginas/${encodeURIComponent(folder)}/view.html`;
        const scriptPath = `Paginas/${encodeURIComponent(folder)}/script.js`;

        try {
            const response = await fetch(htmlPath);
            viewPort.innerHTML = await response.text();
            window.restoreDataGlobal();

            const oldScript = document.getElementById('step-script');
            if (oldScript) oldScript.remove();

            const script = document.createElement('script');
            script.src = scriptPath;
            script.id = 'step-script';
            script.onload = () => {
                if (stepNumber === 1 && typeof initIdentificacion === 'function') initIdentificacion();
                if (stepNumber === 5 && typeof initMaterias === 'function') initMaterias();
                if (stepNumber === 6 && typeof initRecord === 'function') initRecord();
                if (stepNumber === 7 && typeof initFamiliares === 'function') initFamiliares();
                if (stepNumber === 9 && typeof renderResumen === 'function') renderResumen();
            };
            document.body.appendChild(script);

            actualizarInterfaz(stepNumber);
        } catch (e) { console.error("Error al cargar el paso:", e); }
    }

    function actualizarInterfaz(step) {
        if (progressBar) progressBar.style.width = `${(step / totalSteps) * 100}%`;
        
        // Navegación
        document.getElementById('prevBtn').style.display = (step <= 1 || step >= 10) ? 'none' : 'inline-block';
        document.getElementById('nextBtn').textContent = (step === 9) ? "Confirmar" : "Siguiente";

        // Visibilidad del botón Limpiar (Solo hasta paso 8)
        btnLimpiar.style.display = (step > 0 && step <= 8) ? 'block' : 'none';
    }

    document.getElementById('nextBtn').onclick = () => {
        if (currentStep === 1 && typeof validarPaso1 === 'function') {
            if (!validarPaso1()) return;
        }

        if (currentStep === 3) {
            alert("⚠️ No podrás solicitar la beca, puesto que al poseer un trabajo no cumples con los requisitos.");
            return;
        }

        if (currentStep === 6 && typeof validarRecord === 'function') {
            if (!validarRecord()) return;
        }

        window.saveCurrentData();

        if (currentStep === 2 && !window.formDataStorage['trabaja']) {
            currentStep = 4;
        } else {
            currentStep++;
        }
        
        loadStep(currentStep);
    };

    document.getElementById('prevBtn').onclick = () => {
        window.saveCurrentData();

        if (currentStep === 4 && !window.formDataStorage['trabaja']) {
            currentStep = 2;
        } else {
            currentStep--;
        }

        loadStep(currentStep);
    };

    loadStep(currentStep);
});