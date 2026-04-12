function initFamiliares() {
    const cuerpoTabla = document.getElementById('cuerpo-tabla');
    const btnAgregar = document.getElementById('btn-agregar');
    const avisoEstado = document.getElementById('aviso-estado'); // ID unificado
    const checkNoFamiliares = document.getElementById('no-familiares');
    const contenedorTabla = document.querySelector('.table-responsive'); 
    const maxFamiliares = 5;

    if (!btnAgregar || !cuerpoTabla || !checkNoFamiliares) return;

    /**
     * Crea una fila con inputs y oculta el botón eliminar por defecto
     */
    const crearFilaHTML = (id) => {
        const nuevaFila = cuerpoTabla.insertRow();
        nuevaFila.setAttribute('data-id', id);
        
        nuevaFila.innerHTML = `
            <td><input type="text" name="f_nom_${id}" placeholder="Nombre" style="width:95%"></td>
            <td><input type="text" name="f_ape_${id}" placeholder="Apellido" style="width:95%"></td>
            <td><input type="text" name="f_par_${id}" placeholder="Vínculo" style="width:95%"></td>
            <td><input type="number" name="f_eda_${id}" min="0" max="120" placeholder="0" style="width:65px"></td>
            <td><input type="text" name="f_ins_${id}" placeholder="Nivel" style="width:95%"></td>
            <td><input type="text" name="f_ocu_${id}" placeholder="Trabajo" style="width:95%"></td>
            <td>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <input type="number" name="f_ing_${id}" step="0.01" placeholder="0.00" style="width:80px">
                    <button type="button" class="btn-remove" title="Eliminar" 
                        style="background: #ff4444; color: white; border: none; border-radius: 50%; cursor: pointer; width: 28px; height: 28px; display: none;">&times;</button>
                </div>
            </td>
        `;

        nuevaFila.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', () => actualizarEstado());
        });

        
    };

    /**
     * Gestiona:
     * 1. Visibilidad de la tabla vs Checkbox.
     * 2. Visibilidad del botón eliminar (solo si hay > 1 fila).
     * 3. Bloqueo del botón "Siguiente" del formulario principal.
     */
    function actualizarEstado() {
        const estaOmitido = checkNoFamiliares.checked;
        const filas = cuerpoTabla.querySelectorAll('tr');
        const numFilas = filas.length;
        const nextBtn = document.getElementById('nextBtn');

        if (estaOmitido) {
            contenedorTabla.style.display = 'none';
            btnAgregar.style.display = 'none';
            if(avisoEstado) {
                avisoEstado.textContent = "Sección omitida correctamente.";
                avisoEstado.style.color = "#888";
            }
            
            // Forzar habilitación del botón Siguiente
            setTimeout(() => {
                if(nextBtn) {
                    nextBtn.disabled = false;
                    nextBtn.style.opacity = "1";
                    nextBtn.style.cursor = "pointer";
                }
            }, 50);
            return;
        } else {
            contenedorTabla.style.display = 'block';
            btnAgregar.style.display = 'inline-block';
        }

        // --- Lógica del Botón Eliminar ---
        const botonesEliminar = cuerpoTabla.querySelectorAll('.btn-remove');
        botonesEliminar.forEach(btn => {
            btn.style.display = (numFilas <= 1) ? 'none' : 'block';
        });

        // --- Validación de campos vacíos ---
        let hayCamposVacios = false;
        const todosLosInputs = cuerpoTabla.querySelectorAll('input');
        todosLosInputs.forEach(input => {
            if (input.value.trim() === "") {
                hayCamposVacios = true;
                input.style.border = "1px solid #ff6600";
            } else {
                input.style.border = "1px solid #ccc";
            }
        });

        // --- Control de Navegación (Siguiente) ---
        setTimeout(() => {
            if (nextBtn) {
                const esInvalido = hayCamposVacios;
                nextBtn.disabled = esInvalido;
                nextBtn.style.opacity = esInvalido ? "0.5" : "1";
                nextBtn.style.cursor = esInvalido ? "not-allowed" : "pointer";
                
                if(avisoEstado) {
                    avisoEstado.textContent = esInvalido ? "⚠️ Complete todos los datos para continuar." : "✓ Información completa.";
                    avisoEstado.style.color = esInvalido ? "#d9534f" : "#28a745";
                }
            }
        }, 50);

        // Control del botón de agregar (Límite 5)
        if (numFilas >= maxFamiliares) {
            btnAgregar.disabled = true;
            btnAgregar.innerText = "Límite alcanzado (5)";
            btnAgregar.style.background = "#888";
        } else {
            btnAgregar.disabled = hayCamposVacios;
            btnAgregar.innerText = "+ Añadir Familiar";
            btnAgregar.style.background = hayCamposVacios ? "#ccc" : "#00dc0b";
        }
    }

    checkNoFamiliares.onchange = (e) => {
        if (e.target.checked) {
            if (!confirm("¿Estás seguro? La tabla se ocultará y no se incluirá en la solicitud.")) {
                e.target.checked = false;
                return;
            }
        }
        window.formDataStorage['no_familiares'] = e.target.checked;
        actualizarEstado();
    };

    // Reconstrucción y Persistencia
    cuerpoTabla.innerHTML = "";
    const datosCargados = window.formDataStorage || {};
    const idsExistentes = [...new Set(Object.keys(datosCargados).filter(k => k.startsWith('f_nom_')).map(k => k.split('_')[2]))];
    
    if (idsExistentes.length > 0) {
        idsExistentes.forEach(id => crearFilaHTML(id));
        if (typeof window.restoreDataGlobal === 'function') window.restoreDataGlobal();
    } else {
        crearFilaHTML(Date.now());
    }

    if (datosCargados['no_familiares'] === true) {
        checkNoFamiliares.checked = true;
    }

    btnAgregar.onclick = (e) => {
        e.preventDefault();
        if (cuerpoTabla.querySelectorAll('tr').length < maxFamiliares) {
            crearFilaHTML(Date.now());
            actualizarEstado();
        }
    };

    cuerpoTabla.onclick = (e) => {
        const btnRemove = e.target.closest('.btn-remove');
        if (btnRemove && cuerpoTabla.querySelectorAll('tr').length > 1) {
            const fila = btnRemove.closest('tr');
            if (confirm(`¿Eliminar este familiar?`)) {
                const idFila = fila.getAttribute('data-id');
                Object.keys(window.formDataStorage).forEach(key => {
                    if (key.endsWith(`_${idFila}`)) delete window.formDataStorage[key];
                });
                fila.remove();
                actualizarEstado();
            }
        }
    };

    actualizarEstado();
}