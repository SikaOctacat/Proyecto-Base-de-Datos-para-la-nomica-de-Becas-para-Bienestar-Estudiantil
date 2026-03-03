/**
 * ARCHIVO: main.js (Raíz) - Lógica de Navegación e Integración
 * Este script coordina la carga dinámica de formularios (SPA), la persistencia de datos 
 * en memoria y el control del flujo de pasos del usuario.
 */
document.addEventListener('DOMContentLoaded', () => {
    // --- ESTADO INICIAL ---
    let currentStep = 1; // Rastrea el paso actual del formulario
    const totalSteps = 10; // Límite total de pantallas definidas
    
    // Referencias a elementos clave del DOM
    const viewPort = document.getElementById('dynamic-content'); // Contenedor donde se inyecta el HTML de cada paso
    const progressBar = document.getElementById('progressBar'); // Elemento visual de progreso
    const mainContainer = document.getElementById('main-container'); // Contenedor principal del layout
    
    // Objeto global para almacenar los datos del formulario entre cambios de página
    window.formDataStorage = {}; 

    // --- INTEGRACIÓN DEL BOTÓN DE LIMPIEZA ---
    const btnLimpiar = document.createElement('button');
    btnLimpiar.className = 'btn-clear-data';
    btnLimpiar.innerHTML = 'Borrar Campos';
    mainContainer.appendChild(btnLimpiar);

    btnLimpiar.onclick = () => {
        if (confirm("¿Estás seguro de que deseas borrar los datos de esta página?")) {
            const inputs = viewPort.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                if (input.type === 'checkbox' || input.type === 'radio') {
                    input.checked = false;
                } else {
                    input.value = '';
                }
                if (input.name) delete window.formDataStorage[input.name];
            });
            
            if (currentStep === 1 && document.getElementById('edad')) {
                document.getElementById('edad').value = '00';
            }
        }
    };

    // Mapeo de carpetas
    const folderMap = {
        1: "1. Pagina Identificacion",
        2: "2. Pagina Residencia",
        3: "3. Pagina Laboral",
        4: "4. Pagina PNF",
        5: "5. Pagina Materias",
        6: "6. Pagina Record academico",
        7: "7. Pagina Familiares",
        8: "8. Pagina Datos extra",
        9: "9. Verificacion",
        10: "10. Pantalla final"
    };

    /**
     * GUARDA los valores actuales (CORREGIDO PARA RADIOS)
     */
    window.saveCurrentData = () => {
        const inputs = viewPort.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            if (!input.name) return;

            if (input.type === 'checkbox') {
                window.formDataStorage[input.name] = input.checked;
            } else if (input.type === 'radio') {
                // Solo guardamos el valor si el radio está seleccionado
                if (input.checked) {
                    window.formDataStorage[input.name] = input.value;
                }
            } else {
                window.formDataStorage[input.name] = input.value;
            }
        });
    };

    /**
     * RESTAURA los valores (CORREGIDO PARA RADIOS)
     */
    window.restoreDataGlobal = () => {
        const inputs = viewPort.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            const savedValue = window.formDataStorage[input.name];
            
            if (savedValue !== undefined && savedValue !== null) {
                if (input.type === 'checkbox') {
                    input.checked = savedValue;
                } else if (input.type === 'radio') {
                    // Se marca si el valor coincide con el guardado
                    input.checked = (input.value === savedValue);
                } else {
                    input.value = savedValue;
                }
            }
        });
    };

    /**
     * Función principal de carga asíncrona
     */
    async function loadStep(stepNumber) {
        const folder = folderMap[stepNumber];
        const htmlPath = `Paginas/${encodeURIComponent(folder)}/view.html`;
        const scriptPath = `Paginas/${encodeURIComponent(folder)}/script.js`;

        try {
            const response = await fetch(htmlPath);
            viewPort.innerHTML = await response.text();
            
            // Inyectar datos guardados ANTES de cargar el script
            window.restoreDataGlobal();

            const oldScript = document.getElementById('step-script');
            if (oldScript) oldScript.remove();

            const script = document.createElement('script');
            script.src = `${scriptPath}?v=${new Date().getTime()}`; 
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

        } catch (e) { 
            console.error("Error al cargar el paso:", e); 
        }
    }

    function actualizarInterfaz(step) {
        if (progressBar) progressBar.style.width = `${(step / totalSteps) * 100}%`;
        document.getElementById('prevBtn').style.display = (step <= 1 || step >= 10) ? 'none' : 'inline-block';
        document.getElementById('nextBtn').textContent = (step === 9) ? "Confirmar" : "Siguiente";
        btnLimpiar.style.display = (step > 0 && step <= 8) ? 'block' : 'none';
    }

    /**
     * Manejador del botón "Siguiente"
     */
    document.getElementById('nextBtn').onclick = () => {
        if (currentStep === 1 && typeof validarPaso1 === 'function') {
            if (!validarPaso1()) return; 
        }

        // Primero guardamos para tener los datos frescos
        window.saveCurrentData();

        // Lógica de negocio corregida: Bloqueo si trabaja
        if (currentStep === 3 && window.formDataStorage['trabaja'] === 'si') {
            alert("⚠️ No podrás solicitar la beca, puesto que al poseer un trabajo no cumples con los requisitos.");
            return;
        }

        if (currentStep === 6 && typeof validarRecord === 'function') {
            if (!validarRecord()) return;
        }

        // Lógica de "Salto": Si NO trabaja (valor 'no'), se salta el paso laboral (3)
        if (currentStep === 2 && window.formDataStorage['trabaja'] === 'no') {
            currentStep = 4;
        } else {
            currentStep++;
        }
        
        loadStep(currentStep);
    };

    /**
     * Manejador del botón "Atrás"
     */
    document.getElementById('prevBtn').onclick = () => {
        window.saveCurrentData();

        // Lógica inversa del salto: Si vuelve del 4 y no trabaja, va al 2
        if (currentStep === 4 && window.formDataStorage['trabaja'] === 'no') {
            currentStep = 2;
        } else {
            currentStep--;
        }

        loadStep(currentStep);
    };

    loadStep(currentStep);
});