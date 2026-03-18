/**
 * ARCHIVO: main.js - Lógica de Navegación e Integración SPA (Versión Unificada)
 */

// --- 1. ESTADO GLOBAL ---
let currentStep = 1;
window.formDataStorage = {}; 

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

// --- 2. LÓGICA DE NAVEGACIÓN DINÁMICA ---

/**
 * Determina qué pasos deben mostrarse. 
 * Si no trabaja, el paso 3 (Laboral) se excluye de la lista.
 */
function obtenerPasosActivos() {
    const pasos = [1, 2]; 
    const estudianteTrabaja = window.formDataStorage['trabaja'] === 'si';

    if (estudianteTrabaja) {
        pasos.push(3); 
    }

    pasos.push(4, 5, 6, 7, 8, 9, 10);
    return pasos;
}

async function loadStep(stepNumber) {
    const viewPort = document.getElementById('dynamic-content');
    if (!viewPort) return;

    const folder = folderMap[stepNumber];
    const htmlPath = `Paginas/${encodeURIComponent(folder)}/view.html`;
    const scriptPath = `Paginas/${encodeURIComponent(folder)}/script.js`;

    try {
        const response = await fetch(htmlPath);
        if (!response.ok) throw new Error("No se pudo cargar el paso");
        
        viewPort.innerHTML = await response.text();
        
        // Restaurar datos guardados en los inputs
        window.restoreDataGlobal();

        // Limpiar y cargar el script específico del paso
        const oldScript = document.getElementById('step-script');
        if (oldScript) oldScript.remove();

        const script = document.createElement('script');
        script.src = `${scriptPath}?v=${new Date().getTime()}`; 
        script.id = 'step-script';
        
        script.onload = () => {
            // Inicializadores automáticos según el paso
            if (stepNumber === 1 && typeof initIdentificacion === 'function') initIdentificacion();
            if (stepNumber === 2 && typeof initResidencia === 'function') initResidencia();
            if (stepNumber === 5 && typeof initMaterias === 'function') initMaterias();
            if (stepNumber === 6 && typeof initRecord === 'function') initRecord();
            if (stepNumber === 7 && typeof initFamiliares === 'function') initFamiliares();
            if (stepNumber === 9 && typeof renderResumen === 'function') renderResumen();
        };
        
        document.body.appendChild(script);
        actualizarInterfaz(stepNumber);

    } catch (e) { 
        console.error("Error en loadStep:", e); 
    }
}

function actualizarInterfaz(step) {
    const pasosActivos = obtenerPasosActivos();
    const indiceActual = pasosActivos.indexOf(step);
    const totalPasosReales = pasosActivos.length;

    const progressBar = document.getElementById('progressBar');
    if (progressBar) {
        progressBar.style.width = `${((indiceActual + 1) / totalPasosReales) * 100}%`;
    }
    
    const prevBtn = document.getElementById('prevBtn');
    const nextBtn = document.getElementById('nextBtn');
    const btnLimpiar = document.querySelector('.btn-clear-data');

    if (prevBtn) prevBtn.style.display = (step <= 1 || step >= 10) ? 'none' : 'inline-block';
    if (nextBtn) nextBtn.textContent = (step === 9) ? "Confirmar" : "Siguiente";
    if (btnLimpiar) btnLimpiar.style.display = (step > 0 && step <= 8) ? 'block' : 'none';
}

// --- 3. PERSISTENCIA DE DATOS (Mejorada para Radios y Checkboxes) ---

window.saveCurrentData = () => {
    const viewPort = document.getElementById('dynamic-content');
    const inputs = viewPort.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        if (!input.name) return;

        if (input.type === 'checkbox') {
            window.formDataStorage[input.name] = input.checked;
        } else if (input.type === 'radio') {
            if (input.checked) window.formDataStorage[input.name] = input.value;
        } else {
            window.formDataStorage[input.name] = input.value;
        }
    });
};

window.restoreDataGlobal = () => {
    const viewPort = document.getElementById('dynamic-content');
    const inputs = viewPort.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
        const savedValue = window.formDataStorage[input.name];
        if (savedValue === undefined) return;

        if (input.type === 'checkbox') {
            input.checked = savedValue;
        } else if (input.type === 'radio') {
            input.checked = (input.value === savedValue);
        } else {
            input.value = savedValue;
        }
    });
};

// --- 4. INICIALIZACIÓN Y EVENTOS ---

document.addEventListener('DOMContentLoaded', () => {
    const viewPort = document.getElementById('dynamic-content');
    const mainContainer = document.getElementById('main-container');

    // Crear botón de limpieza dinámicamente
    const btnLimpiar = document.createElement('button');
    btnLimpiar.className = 'btn-clear-data';
    btnLimpiar.innerHTML = 'Borrar Campos';
    if (mainContainer) mainContainer.appendChild(btnLimpiar);

    btnLimpiar.onclick = () => {
        if (confirm("¿Estás seguro de que deseas borrar los datos de esta página?")) {
            const inputs = viewPort.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                if (input.type === 'checkbox' || input.type === 'radio') input.checked = false;
                else input.value = '';
                if (input.name) delete window.formDataStorage[input.name];
            });
            // Caso especial para el campo calculado de edad
            if (document.getElementById('edad')) document.getElementById('edad').value = '00';
        }
    };

    // Navegación Siguiente
    document.getElementById('nextBtn').onclick = () => {
        // Validaciones por paso
        if (currentStep === 1 && typeof validarPaso1 === 'function' && !validarPaso1()) return;
        if (currentStep === 6 && typeof validarRecord === 'function' && !validarRecord()) return;

        window.saveCurrentData();
        
        // Bloqueo de lógica de negocio (No puede optar si trabaja)
        if (currentStep === 3 && window.formDataStorage['trabaja'] === 'si') {
            alert("⚠️ No cumples con los requisitos para la beca al poseer un trabajo.");
            return;
        }

        const pasosActivos = obtenerPasosActivos();
        const currentIndex = pasosActivos.indexOf(currentStep);
        
        if (currentIndex < pasosActivos.length - 1) {
            currentStep = pasosActivos[currentIndex + 1];
            loadStep(currentStep);
        }
    };

    // Navegación Anterior
    document.getElementById('prevBtn').onclick = () => {
        window.saveCurrentData();
        const pasosActivos = obtenerPasosActivos();
        const currentIndex = pasosActivos.indexOf(currentStep);
        
        if (currentIndex > 0) {
            currentStep = pasosActivos[currentIndex - 1];
            loadStep(currentStep);
        }
    };

    // Soporte para el Sidebar
    window.navegarA = (paso) => {
        window.saveCurrentData();
        currentStep = paso;
        loadStep(paso).then(() => {
            const checkMenu = document.getElementById('btn-menu');
            if (checkMenu) checkMenu.checked = false;
        });
    };

    loadStep(currentStep);
});