/**
 * Lógica para la tabla dinámica de familiares (Paso 7)
 * Permite gestionar un grupo familiar con campos dinámicos que se guardan y restauran
 * utilizando identificadores únicos basados en marcas de tiempo (timestamps).
 */
function initFamiliares() {
    // --- REFERENCIAS AL DOM ---
    const cuerpoTabla = document.getElementById('cuerpo-tabla'); // El elemento <tbody> donde se inyectan las filas
    const btnAgregar = document.getElementById('btn-agregar'); // Botón para añadir nuevos familiares
    const avisoLimite = document.getElementById('aviso-limite'); // Texto de advertencia cuando se alcanza el tope
    const maxFamiliares = 5; // Regla de negocio: Límite máximo de registros permitidos

    // Salida de seguridad si el HTML necesario no está presente en la vista cargada
    if (!btnAgregar || !cuerpoTabla) return;

    /**
     * Función interna para crear el HTML de una nueva fila.
     * @param {string|number} id - Identificador único para que los inputs tengan nombres únicos.
     */
    const crearFilaHTML = (id) => {
        const nuevaFila = cuerpoTabla.insertRow(); // Crea un elemento <tr>
        nuevaFila.setAttribute('data-id', id); // Atributo personalizado para identificar la fila al eliminarla
        
        // Inyectamos las celdas con inputs. El atributo 'name' incluye el ID para evitar colisiones en el storage.
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

    // --- PROCESO DE RECONSTRUCCIÓN (PERSISTENCIA) ---
    // Al cargar la página, limpiamos la tabla para reconstruirla con los datos que ya tengamos guardados
    cuerpoTabla.innerHTML = "";
    const datosCargados = window.formDataStorage || {};
    
    // Extraemos los IDs únicos buscando en las llaves del storage que empiecen con 'f_nom_'
    // Ejemplo: 'f_nom_1715456' -> extrae '1715456'
    const idsExistentes = [...new Set(
        Object.keys(datosCargados)
            .filter(key => key.startsWith('f_nom_'))
            .map(key => key.split('_')[2])
    )];

    if (idsExistentes.length > 0) {
        // Si hay datos previos, creamos las filas correspondientes a esos IDs
        idsExistentes.forEach(id => crearFilaHTML(id));
        // Invocamos la función global de main.js para rellenar los valores en los inputs recién creados
        if (typeof window.restoreDataGlobal === 'function') window.restoreDataGlobal();
    } else {
        // Si no hay datos (primera vez en la página), creamos una fila vacía por defecto usando el tiempo actual como ID
        crearFilaHTML(Date.now());
    }

    /**
     * EVENTO: Añadir Familiar
     */
    btnAgregar.onclick = (e) => {
        e.preventDefault(); // Evita cualquier comportamiento de envío de formulario por error
        // Verifica que no se supere el límite de 5 filas antes de agregar
        if (cuerpoTabla.querySelectorAll('tr').length < maxFamiliares) {
            crearFilaHTML(Date.now());
            actualizarEstado(cuerpoTabla, btnAgregar, avisoLimite, maxFamiliares);
        }
    };

    /**
     * EVENTO: Eliminar Familiar (Delegación de eventos)
     * En lugar de asignar un evento a cada botón, escuchamos en la tabla y detectamos el clic en '.btn-remove'
     */
    cuerpoTabla.onclick = (e) => {
        if (e.target.classList.contains('btn-remove')) {
            const fila = e.target.closest('tr');
            const idFila = fila.getAttribute('data-id');
            
            // LIMPIEZA DEL STORAGE:
            // Es vital eliminar las llaves del objeto global para que no se envíen datos de familiares borrados
            Object.keys(window.formDataStorage).forEach(key => {
                if (key.endsWith(`_${idFila}`)) delete window.formDataStorage[key];
            });

            fila.remove(); // Elimina el elemento visual de la tabla
            actualizarEstado(cuerpoTabla, btnAgregar, avisoLimite, maxFamiliares);
        }
    };

    /**
     * Controla la habilitación del botón de agregar y la visibilidad de los mensajes de alerta
     */
    function actualizarEstado(tabla, boton, aviso, max) {
        const filas = tabla.querySelectorAll('tr').length;
        const limiteAlcanzado = filas >= max;
        
        boton.disabled = limiteAlcanzado;
        boton.style.opacity = limiteAlcanzado ? "0.5" : "1"; // Feedback visual para botón deshabilitado
        aviso.style.display = limiteAlcanzado ? 'inline' : 'none'; // Muestra "Límite alcanzado"
    }

    // Ejecución inicial del estado para asegurar que el botón esté correcto desde el inicio
    actualizarEstado(cuerpoTabla, btnAgregar, avisoLimite, maxFamiliares);
}