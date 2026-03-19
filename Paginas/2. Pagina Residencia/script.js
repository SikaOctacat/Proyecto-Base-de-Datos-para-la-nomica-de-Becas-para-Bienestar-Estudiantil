/**
 * Lógica para el Paso 2: Residencia y Ubicación Dinámica
 * Maneja la carga de estados/municipios desde JSON y reglas de negocio de vivienda.
 */
async function initResidencia() {
    const estadoSelect = document.getElementById('estado_res');
    const municipioSelect = document.getElementById('municipio_res');
    const tRes = document.getElementById('t_res');
    const rProp = document.getElementById('r_prop');

    // 1. CARGA DINÁMICA DE ESTADOS Y MUNICIPIOS
    if (estadoSelect && municipioSelect) {
        try {
            // Buscamos el JSON en la misma carpeta de la página 2
            const response = await fetch('Paginas/2. Pagina Residencia/venezuela.json');
            const datosVenezuela = await response.json();

            // Llenar estados
            estadoSelect.innerHTML = '<option value="" disabled selected>Seleccione Estado</option>';
            datosVenezuela.forEach(item => {
                const option = document.createElement('option');
                option.value = item.estado;
                option.textContent = item.estado;
                estadoSelect.appendChild(option);
            });

            // Evento de cambio de estado para cargar municipios
            estadoSelect.onchange = () => {
                const estadoSeleccionado = estadoSelect.value;
                const infoEstado = datosVenezuela.find(est => est.estado === estadoSeleccionado);

                municipioSelect.innerHTML = '<option value="" disabled selected>Seleccione Municipio</option>';
                municipioSelect.disabled = !infoEstado;

                if (infoEstado) {
                    // Ordenamos alfabéticamente los municipios antes de insertarlos
                    infoEstado.municipios.sort().forEach(muni => {
                        const option = document.createElement('option');
                        option.value = muni;
                        option.textContent = muni;
                        municipioSelect.appendChild(option);
                    });
                }
                
                // Persistencia manual inmediata si es necesario
                if(window.formDataStorage) window.formDataStorage['estado_res'] = estadoSeleccionado;
            };

        } catch (error) {
            console.error("Error al cargar el archivo de municipios:", error);
            // Fallback: Si el JSON falla, convertir a inputs de texto para no bloquear al usuario
            alert("Error al cargar lista de municipios. Por favor, escriba los datos manualmente.");
        }
    }

    // 2. LÓGICA DE REDUNDANCIA (VIVIENDA)
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
        // Ejecutar al inicio por si viene de un "Atrás"
        gestionarDinamismo();
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