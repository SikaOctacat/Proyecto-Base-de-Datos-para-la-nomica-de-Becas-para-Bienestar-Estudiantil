/**
 * Lógica para el Paso 2: Residencia y Ubicación Dinámica
 * Maneja la carga de estados/municipios desde JSON y reglas de negocio de vivienda.
 */
async function initResidencia() {
    const estadoSelect = document.getElementById('estado_res');
    const municipioSelect = document.getElementById('municipio_res');
    const tRes = document.getElementById('t_res');
    const rProp = document.getElementById('r_prop');

    if (estadoSelect && municipioSelect) {
        try {
            const response = await fetch('Paginas/2. Pagina Residencia/venezuela.json');
            const datosVenezuela = await response.json();

            // 1. Llenar estados
            estadoSelect.innerHTML = '<option value="" disabled selected>Seleccione Estado</option>';
            datosVenezuela.forEach(item => {
                const option = document.createElement('option');
                option.value = item.estado;
                option.textContent = item.estado;
                estadoSelect.appendChild(option);
            });

            // --- LÓGICA DE RECUPERACIÓN (ESTADO) ---
            const estadoGuardado = window.formDataStorage?.['estado_res'];
            if (estadoGuardado) {
                estadoSelect.value = estadoGuardado;
                llenarMunicipios(estadoGuardado, datosVenezuela, municipioSelect);
                
                // --- LÓGICA DE RECUPERACIÓN (MUNICIPIO) ---
                const municipioGuardado = window.formDataStorage?.['municipio_res'];
                if (municipioGuardado) {
                    municipioSelect.value = municipioGuardado;
                }

                // --- NUEVO: Forzar validación tras recuperar datos ---
                // Esto dispara el evento 'change' manualmente para que cualquier 
                // validador externo se de cuenta de que ya hay datos.
                estadoSelect.dispatchEvent(new Event('change'));
                municipioSelect.dispatchEvent(new Event('change'));
            }

            // Evento de cambio de estado
            estadoSelect.onchange = () => {
                const estadoSeleccionado = estadoSelect.value;
                llenarMunicipios(estadoSeleccionado, datosVenezuela, municipioSelect);
                
                if(window.formDataStorage) {
                    window.formDataStorage['estado_res'] = estadoSeleccionado;
                    window.formDataStorage['municipio_res'] = ""; // Resetear municipio al cambiar estado
                }
            };

            // Evento de cambio de municipio para persistencia
            municipioSelect.onchange = () => {
                if(window.formDataStorage) {
                    window.formDataStorage['municipio_res'] = municipioSelect.value;
                }
            };

        } catch (error) {
            console.error("Error al cargar el archivo de municipios:", error);
            alert("Error al cargar lista de municipios.");
        }
    }

    // Lógica de vivienda (se mantiene igual)
    if (tRes && rProp) {
        const gestionarDinamismo = () => {
            const tipoResidencia = tRes.value;
            if (tipoResidencia === 'universitaria') {
                rProp.value = 'cedida';
                rProp.disabled = true;
                rProp.style.backgroundColor = "#f0f0f0";
                if(window.formDataStorage) window.formDataStorage['r_prop'] = 'cedida';
            } else {
                rProp.disabled = false;
                rProp.style.backgroundColor = "";
            }
        };
        tRes.addEventListener('change', gestionarDinamismo);
        gestionarDinamismo();
    }
}

/**
 * Función auxiliar para evitar repetir código al llenar municipios
 */
function llenarMunicipios(estadoNombre, datos, selectElement) {
    const infoEstado = datos.find(est => est.estado === estadoNombre);
    selectElement.innerHTML = '<option value="" disabled selected>Seleccione Municipio</option>';
    selectElement.disabled = !infoEstado;

    if (infoEstado) {
        infoEstado.municipios.sort().forEach(muni => {
            const option = document.createElement('option');
            option.value = muni;
            option.textContent = muni;
            selectElement.appendChild(option);
        });
    }
}

/**
 * Validación del Paso 2
 */
function validarPaso2() {
    const estado = document.getElementById('estado_res').value;
    const municipio = document.getElementById('municipio_res').value;
    const dir = document.querySelector('input[name="dir_local"]').value;

    if (!estado || !municipio || dir.trim().length < 5) {
        alert("⚠️ Por favor, complete la ubicación exacta y seleccione estado/municipio.");
        return false;
    }
    return true; 
}