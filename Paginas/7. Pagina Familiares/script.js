/**
 * Lógica para la tabla dinámica de familiares (Paso 7)
 */
function initFamiliares() {
    const cuerpoTabla = document.getElementById('cuerpo-tabla');
    const btnAgregar = document.getElementById('btn-agregar');
    const avisoLimite = document.getElementById('aviso-limite');
    const maxFamiliares = 5;

    if (!btnAgregar || !cuerpoTabla) return;

    // Función para crear el HTML de la fila
    const crearFilaHTML = (id) => {
        const nuevaFila = cuerpoTabla.insertRow();
        nuevaFila.setAttribute('data-id', id);
        nuevaFila.innerHTML = `
            <td><input type="text" name="f_nom_${id}" placeholder="Nombre" required style="width:90%"></td>
            <td><input type="text" name="f_par_${id}" placeholder="Parentesco" required style="width:90%"></td>
            <td><input type="number" name="f_eda_${id}" min="0" max="120" placeholder="0" style="width:50px"></td>
            <td><input type="text" name="f_ins_${id}" placeholder="Nivel" style="width:90%"></td>
            <td><input type="text" name="f_ocu_${id}" placeholder="Ocupación" style="width:90%"></td>
            <td>
                <div style="display: flex; align-items: center; gap: 4px;">
                    <input type="number" name="f_ing_${id}" step="0.01" placeholder="0.00" style="width:70px">
                    <button type="button" class="btn-remove" title="Eliminar" 
                        style="background: #ff4444; color: white; border: none; border-radius: 4px; cursor: pointer; padding: 2px 8px;">&times;</button>
                </div>
            </td>
        `;
    };

    // --- RECONSTRUCCIÓN ---
    cuerpoTabla.innerHTML = "";
    const datosCargados = window.formDataStorage || {};
    const idsExistentes = [...new Set(
        Object.keys(datosCargados)
            .filter(key => key.startsWith('f_nom_'))
            .map(key => key.split('_')[2])
    )];

    if (idsExistentes.length > 0) {
        idsExistentes.forEach(id => crearFilaHTML(id));
        if (typeof window.restoreDataGlobal === 'function') window.restoreDataGlobal();
    } else {
        crearFilaHTML(Date.now()); // Una fila inicial por defecto
    }

    // Evento Añadir
    btnAgregar.onclick = (e) => {
        e.preventDefault();
        if (cuerpoTabla.querySelectorAll('tr').length < maxFamiliares) {
            crearFilaHTML(Date.now());
            actualizarEstado(cuerpoTabla, btnAgregar, avisoLimite, maxFamiliares);
        }
    };

    // Evento Eliminar (Delegación de eventos)
    cuerpoTabla.onclick = (e) => {
        if (e.target.classList.contains('btn-remove')) {
            const fila = e.target.closest('tr');
            const idFila = fila.getAttribute('data-id');
            
            // Limpiar datos del storage para ese ID específico
            Object.keys(window.formDataStorage).forEach(key => {
                if (key.endsWith(`_${idFila}`)) delete window.formDataStorage[key];
            });

            fila.remove();
            actualizarEstado(cuerpoTabla, btnAgregar, avisoLimite, maxFamiliares);
        }
    };

    function actualizarEstado(tabla, boton, aviso, max) {
        const filas = tabla.querySelectorAll('tr').length;
        const limiteAlcanzado = filas >= max;
        boton.disabled = limiteAlcanzado;
        boton.style.opacity = limiteAlcanzado ? "0.5" : "1";
        aviso.style.display = limiteAlcanzado ? 'inline' : 'none';
    }

    actualizarEstado(cuerpoTabla, btnAgregar, avisoLimite, maxFamiliares);
}