function initFamiliares() {
    const cuerpoTabla = document.getElementById('cuerpo-tabla');
    const btnAgregar = document.getElementById('btn-agregar');
    const avisoEstado = document.getElementById('aviso-estado'); 
    const checkNoFamiliares = document.getElementById('no-familiares');
    const contenedorTabla = document.querySelector('.table-responsive'); 
    
    const btnDropdown = document.getElementById('btn-dropdown-grupo');
    const menuDropdown = document.getElementById('menu-dropdown-grupo');

    if (!btnAgregar || !cuerpoTabla || !checkNoFamiliares || !btnDropdown || !menuDropdown) return;

    // Configuración de paleta de colores con mayor contraste y vivacidad
    const coloresConfig = {
        'primaria': {
            separador: '#c6f6d5', // Verde pastel definido
            texto: '#22543d',
            filas: '#f0fff4'      // Fondo de filas bajo este grupo
        },
        'secundaria': {
            separador: '#feebc8', // Naranja/Crema pastel definido
            texto: '#744210',
            filas: '#fffaf0'      // Fondo de filas bajo este grupo
        },
        'otros': {
            separador: '#edf2f7', // Gris azulado con presencia
            texto: '#2d3748',
            filas: '#f7fafc'      // Fondo de filas bajo este grupo
        }
    };

    /**
     * Guarda en el almacenamiento global la disposición exacta de las filas y separadores
     */
    function guardarEstructuraEnStorage() {
        if (!window.formDataStorage) window.formDataStorage = {};
        
        const filas = Array.from(cuerpoTabla.querySelectorAll('tr'));
        const mapaEstructura = filas.map(fila => {
            if (fila.classList.contains('fila-separador')) {
                return `separador:${fila.getAttribute('data-grupo')}`;
            } else if (fila.classList.contains('fila-datos')) {
                return `fila:${fila.getAttribute('data-id')}`;
            }
            return null;
        }).filter(Boolean);

        window.formDataStorage['estructura_tabla'] = mapaEstructura;
    }

    /**
     * Escanea la estructura del DOM y tiñe las filas según el separador que tengan arriba
     */
    function actualizarPertenenciaVisual() {
        const filas = Array.from(cuerpoTabla.querySelectorAll('tr'));
        let grupoActual = null;

        filas.forEach(fila => {
            if (fila.classList.contains('fila-separador')) {
                grupoActual = fila.getAttribute('data-grupo');
            } else if (fila.classList.contains('fila-datos')) {
                if (grupoActual && coloresConfig[grupoActual]) {
                    fila.style.backgroundColor = coloresConfig[grupoActual].filas;
                } else {
                    fila.style.backgroundColor = ''; // Blanco limpio si no hay separadores encima
                }
            }
        });
    }

    /**
     * Habilita los listeners del Drag and Drop nativo en los elementos TR
     */
    function hacerFilaArrastrable(fila) {
        fila.setAttribute('draggable', 'true');

        fila.addEventListener('dragstart', (e) => {
            fila.classList.add('dragging');
            e.dataTransfer.effectAllowed = 'move';
            e.dataTransfer.setData('text/plain', '');
        });

        fila.addEventListener('dragend', () => {
            fila.classList.remove('dragging');
            cuerpoTabla.querySelectorAll('tr').forEach(f => f.classList.remove('drag-over'));
            actualizarPertenenciaVisual(); 
            guardarEstructuraEnStorage(); // Guardar orden tras mover elementos
        });

        fila.addEventListener('dragover', (e) => {
            e.preventDefault();
            const draggingRow = cuerpoTabla.querySelector('.dragging');
            if (!draggingRow || draggingRow === fila) return;
            fila.classList.add('drag-over');
        });

        fila.addEventListener('dragleave', () => {
            fila.classList.remove('drag-over');
        });

        fila.addEventListener('drop', (e) => {
            e.preventDefault();
            fila.classList.remove('drag-over');
            const draggingRow = cuerpoTabla.querySelector('.dragging');
            if (!draggingRow || draggingRow === fila) return;

            const filas = Array.from(cuerpoTabla.querySelectorAll('tr'));
            const indexObjetivo = filas.indexOf(fila);
            const indexArrastrado = filas.indexOf(draggingRow);

            if (indexArrastrado < indexObjetivo) {
                fila.after(draggingRow);
            } else {
                fila.before(draggingRow);
            }
        });
    }

    /**
     * Inserta una fila de datos estándar
     */
    const crearFilaHTML = (id, appendAlFinal = true) => {
        const nuevaFila = document.createElement('tr');
        nuevaFila.setAttribute('data-id', id);
        nuevaFila.classList.add('fila-datos');
        
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
            input.addEventListener('input', () => {
                actualizarEstado();
            });
        });

        hacerFilaArrastrable(nuevaFila);
        
        if (appendAlFinal) {
            cuerpoTabla.appendChild(nuevaFila);
        }
        
        actualizarPertenenciaVisual();
        return nuevaFila;
    };

    /**
     * Crea e inyecta dinámicamente el separador basándose en las reglas de posición solicitadas
     */
    const crearSeparadorHTML = (valor, texto, forzadoDesdeStorage = false) => {
        const nuevaFila = document.createElement('tr');
        nuevaFila.setAttribute('data-grupo', valor);
        nuevaFila.classList.add('fila-separador');
        
        const estiloColor = coloresConfig[valor] || { separador: '#e2e8f0', texto: '#2d3748' };
        nuevaFila.style.backgroundColor = estiloColor.separador;
        nuevaFila.style.userSelect = "none";
        
        nuevaFila.innerHTML = `
            <td colspan="7" style="padding: 10px 14px; border-bottom: 2px solid rgba(0,0,0,0.08); border-top: 1px solid rgba(0,0,0,0.05);">
                <div style="display: flex; justify-content: space-between; align-items: center; color: ${estiloColor.texto}; font-weight: bold; font-size: 0.85rem; letter-spacing: 0.6px;">
                    <span>☰ &nbsp; ${texto.toUpperCase()}</span>
                    <button type="button" class="btn-remove-separador" data-valor="${valor}" title="Quitar grupo"
                            style="background: none; border: none; color: ${estiloColor.texto}; opacity: 0.6; cursor: pointer; font-size: 1.2rem; line-height: 1; padding: 0 5px;">&times;</button>
                </div>
            </td>
        `;

        // Deshabilitar la opción en el dropdown para evitar duplicación
        const enlaceMenu = menuDropdown.querySelector(`a[data-valor="${valor}"]`);
        if (enlaceMenu) enlaceMenu.classList.add('disabled');

        hacerFilaArrastrable(nuevaFila);

        // Si se está restaurando el storage, no aplicamos la lógica de inserción por defecto al fondo/inicio
        if (!forzadoDesdeStorage) {
            const separadoresExistentes = cuerpoTabla.querySelectorAll('.fila-separador');
            if (separadoresExistentes.length === 0) {
                cuerpoTabla.insertAdjacentElement('afterbegin', nuevaFila);
            } else {
                cuerpoTabla.appendChild(nuevaFila);
            }
        }

        actualizarPertenenciaVisual();
        return nuevaFila;
    };

    /**
     * Valida el comportamiento general de botones y campos del formulario
     */
    function actualizarEstado() {
        const estaOmitido = checkNoFamiliares.checked;
        const filasDatos = cuerpoTabla.querySelectorAll('.fila-datos');
        const numFilas = filasDatos.length;
        const nextBtn = document.getElementById('nextBtn');

        if (estaOmitido) {
            contenedorTabla.style.display = 'none';
            btnAgregar.style.display = 'none';
            btnDropdown.parentElement.style.display = 'none';
            if(avisoEstado) {
                avisoEstado.textContent = "Sección omitida correctamente.";
                avisoEstado.style.color = "#888";
            }
            
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
            btnDropdown.parentElement.style.display = 'inline-block';
        }

        const botonesEliminar = cuerpoTabla.querySelectorAll('.btn-remove');
        botonesEliminar.forEach(btn => {
            btn.style.display = (numFilas <= 1) ? 'none' : 'block';
        });

        let hayCamposVacios = false;
        const todosLosInputs = cuerpoTabla.querySelectorAll('.fila-datos input');
        todosLosInputs.forEach(input => {
            if (input.value.trim() === "") {
                hayCamposVacios = true;
                input.style.border = "1px solid #ff6600";
            } else {
                input.style.border = "1px solid #ccc";
            }
        });

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

        btnAgregar.disabled = hayCamposVacios;
        btnAgregar.style.background = hayCamposVacios ? "#ccc" : "#00dc0b";
        btnAgregar.style.cursor = hayCamposVacios ? "not-allowed" : "pointer";
    }

    // Comportamiento del Dropdown
    btnDropdown.onclick = (e) => {
        e.preventDefault();
        e.stopPropagation();
        menuDropdown.style.display = (menuDropdown.style.display === 'block') ? 'none' : 'block';
    };

    document.addEventListener('click', () => {
        menuDropdown.style.display = 'none';
    });

    menuDropdown.onclick = (e) => {
        e.preventDefault();
        const enlace = e.target.closest('a');
        if (!enlace || enlace.classList.contains('disabled')) return;

        const valor = enlace.getAttribute('data-valor');
        const texto = enlace.innerText;

        crearSeparadorHTML(valor, texto);
        menuDropdown.style.display = 'none';
        actualizarEstado();
        guardarEstructuraEnStorage(); // Guardar cambios tras inyectar separador
    };

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

    // --- CARGA INICIAL Y PERSISTENCIA AVANZADA ---
    cuerpoTabla.innerHTML = "";
    const datosCargados = window.formDataStorage || {};
    const estructuraGuardada = datosCargados['estructura_tabla'];

    if (estructuraGuardada && estructuraGuardada.length > 0) {
        // Reconstrucción en base al mapa exacto guardado
        estructuraGuardada.forEach(item => {
            const [tipo, identificador] = item.split(':');
            if (tipo === 'separador') {
                const enlace = menuDropdown.querySelector(`a[data-valor="${identificador}"]`);
                const textoGrupo = enlace ? enlace.innerText : identificador;
                const nodoSep = crearSeparadorHTML(identificador, textoGrupo, true);
                cuerpoTabla.appendChild(nodoSep);
            } else if (tipo === 'fila') {
                crearFilaHTML(identificador, true);
            }
        });
        
        // Ejecutar restauración de valores en los inputs de las filas creadas
        if (typeof window.restoreDataGlobal === 'function') window.restoreDataGlobal();
    } else {
        // Fallback clásico si no hay un mapa de estructura guardado previamente
        const idsExistentes = [...new Set(Object.keys(datosCargados).filter(k => k.startsWith('f_nom_')).map(k => k.split('_')[2]))];
        if (idsExistentes.length > 0) {
            idsExistentes.forEach(id => crearFilaHTML(id, true));
            if (typeof window.restoreDataGlobal === 'function') window.restoreDataGlobal();
        } else {
            crearFilaHTML(Date.now(), true);
        }
    }

    if (datosCargados['no_familiares'] === true) {
        checkNoFamiliares.checked = true;
    }

    btnAgregar.onclick = (e) => {
        e.preventDefault();
        crearFilaHTML(Date.now(), true);
        actualizarEstado();
        guardarEstructuraEnStorage(); // Guardar nueva fila en la estructura
    };

    // Manejo de eventos click delegados dentro de la tabla
    cuerpoTabla.onclick = (e) => {
        const btnRemove = e.target.closest('.btn-remove');
        if (btnRemove && cuerpoTabla.querySelectorAll('.fila-datos').length > 1) {
            const fila = btnRemove.closest('tr');
            if (confirm(`¿Eliminar este familiar?`)) {
                const idFila = fila.getAttribute('data-id');
                Object.keys(window.formDataStorage).forEach(key => {
                    if (key.endsWith(`_${idFila}`)) delete window.formDataStorage[key];
                });
                fila.remove();
                actualizarEstado();
                actualizarPertenenciaVisual();
                guardarEstructuraEnStorage(); // Guardar tras remover fila
            }
            return;
        }

        const btnRemoveSep = e.target.closest('.btn-remove-separador');
        if (btnRemoveSep) {
            const filaSep = btnRemoveSep.closest('tr');
            const valorGrupo = btnRemoveSep.getAttribute('data-valor');
            
            const enlaceMenu = menuDropdown.querySelector(`a[data-valor="${valorGrupo}"]`);
            if (enlaceMenu) enlaceMenu.classList.remove('disabled');
            
            filaSep.remove();
            actualizarEstado();
            actualizarPertenenciaVisual();
            guardarEstructuraEnStorage(); // Guardar tras remover separador
        }
    };

    actualizarEstado();
}