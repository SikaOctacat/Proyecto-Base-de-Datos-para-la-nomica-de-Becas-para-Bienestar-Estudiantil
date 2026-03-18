function initFamiliares() {
    const cuerpoTabla = document.getElementById('cuerpo-tabla');
    const btnAgregar = document.getElementById('btn-agregar');
    const avisoEstado = document.getElementById('aviso-estado');
    const checkNoFamiliares = document.getElementById('no-familiares');
    const contenedorTabla = document.querySelector('.table-responsive'); 
    const maxFamiliares = 5;

    if (!btnAgregar || !cuerpoTabla || !checkNoFamiliares) return;

    /**
     * Crea una fila con todos los campos necesarios y listeners de validación
     */
    const crearFilaHTML = (id) => {
        const nuevaFila = cuerpoTabla.insertRow();
        nuevaFila.setAttribute('data-id', id);
        
        nuevaFila.innerHTML = `
            <td><input type="text" name="f_nom_${id}" placeholder="Nombre" style="width:95%"></td>
            <td><input type="text" name="f_ape_${id}" placeholder="Apellidos" style="width:95%"></td>
            <td><input type="text" name="f_par_${id}" placeholder="Vínculo" style="width:95%"></td>
            <td><input type="number" name="f_eda_${id}" min="0" max="120" placeholder="0" style="width:65px"></td>
            <td><input type="text" name="f_ins_${id}" placeholder="Nivel" style="width:95%"></td>
            <td><input type="text" name="f_ocu_${id}" placeholder="Trabajo" style="width:95%"></td>
            <td>
                <div style="display: flex; align-items: center; gap: 8px;">
                    <input type="number" name="f_ing_${id}" step="0.01" placeholder="0.00" style="width:80px">
                    <button type="button" class="btn-remove" title="Eliminar" 
                        style="background: #ff4444; color: white; border: none; border-radius: 50%; cursor: pointer; width: 28px; height: 28px;">&times;</button>
                </div>
            </td>
        `;

        nuevaFila.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', () => actualizarEstado());
        });
    };

    /**
     * Gestiona la visibilidad, validación y estados del botón de agregar
     */
    function actualizarEstado() {
        const estaOmitido = checkNoFamiliares.checked;
        const filas = cuerpoTabla.querySelectorAll('tr');
        const numFilas = filas.length;

        // 1. Visibilidad de la sección completa
        if (estaOmitido) {
            contenedorTabla.style.display = 'none';
            btnAgregar.style.display = 'none';
            avisoEstado.textContent = "Sección omitida (Desmarque para recuperar sus datos).";
            avisoEstado.style.color = "#888";
            return;
        } else {
            contenedorTabla.style.display = 'block';
            btnAgregar.style.display = 'inline-block';
        }

        // 2. Ocultar botón borrar si solo hay una fila
        const botonesEliminar = cuerpoTabla.querySelectorAll('.btn-remove');
        botonesEliminar.forEach(btn => {
            btn.style.visibility = (numFilas <= 1) ? 'hidden' : 'visible';
        });

        // 3. Validación de campos y texto dinámico del botón
        let hayCamposVacios = false;
        const todosLosInputs = cuerpoTabla.querySelectorAll('input');
        for (let input of todosLosInputs) {
            if (input.value.trim() === "") {
                hayCamposVacios = true;
                break;
            }
        }

        const limiteAlcanzado = numFilas >= maxFamiliares;

        if (limiteAlcanzado) {
            btnAgregar.disabled = true;
            btnAgregar.innerText = "Límite de 5 familiares alcanzado";
            btnAgregar.style.background = "#888";
            avisoEstado.textContent = "";
        } else {
            btnAgregar.innerText = "+ Añadir Familiar";
            btnAgregar.style.background = "var(--success)";
            
            if (hayCamposVacios) {
                btnAgregar.disabled = true;
                avisoEstado.textContent = "Complete todos los campos para añadir otro.";
                avisoEstado.style.color = "#ff6600";
            } else {
                btnAgregar.disabled = false;
                avisoEstado.textContent = "✓ Todos los campos listos.";
                avisoEstado.style.color = "#28a745";
            }
        }
    }

    /**
     * Evento del Checkbox con advertencia detallada
     */
    checkNoFamiliares.onchange = (e) => {
        if (e.target.checked) {
            const mensaje = "¿Estás seguro?\n\nLa tabla de familiares se ocultará y no se incluirá en la solicitud. Los datos que ya ingresaste no se borrarán; puedes desmarcar esta opción en cualquier momento para recuperarlos.";
            if (!confirm(mensaje)) {
                e.target.checked = false;
                return;
            }
        } else {
            // Si al volver no hay nada (error raro), aseguramos una fila
            if (cuerpoTabla.querySelectorAll('tr').length === 0) {
                crearFilaHTML(Date.now());
            }
        }
        
        window.formDataStorage['no_familiares'] = e.target.checked;
        actualizarEstado();
    };

    // --- RECONSTRUCCIÓN (Persistencia) ---
    cuerpoTabla.innerHTML = "";
    const datosCargados = window.formDataStorage || {};
    
    // Cargar filas guardadas si existen
    const idsExistentes = [...new Set(Object.keys(datosCargados).filter(k => k.startsWith('f_nom_')).map(k => k.split('_')[2]))];
    if (idsExistentes.length > 0) {
        idsExistentes.forEach(id => crearFilaHTML(id));
        if (typeof window.restoreDataGlobal === 'function') window.restoreDataGlobal();
    } else {
        crearFilaHTML(Date.now());
    }

    // Estado inicial del check
    if (datosCargados['no_familiares'] === true) {
        checkNoFamiliares.checked = true;
    }

    // --- EVENTOS BOTONES ---
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
            const idFila = fila.getAttribute('data-id');
            const nombre = document.querySelector(`input[name="f_nom_${idFila}"]`).value.trim();
            
            if (confirm(`¿Eliminar a ${nombre || "este familiar"}?`)) {
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