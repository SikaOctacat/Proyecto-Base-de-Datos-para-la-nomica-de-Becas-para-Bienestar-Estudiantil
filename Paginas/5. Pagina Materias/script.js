/**
 * Lógica para la gestión de materias (Paso 5)
 */
function initMaterias() {
    const listaDisponibles = document.getElementById('materias-disponibles');
    const listaSeleccionadas = document.getElementById('materias-seleccionadas');
    const contadorVisor = document.getElementById('count-materias');
    const inputManual = document.getElementById('manual-materia-name');
    const btnManual = document.getElementById('btn-add-manual');

    if (!listaDisponibles || !listaSeleccionadas) return;

    const MAX_MATERIAS = 8;
    // Recuperar materias si ya existen en el storage global
    let seleccionadas = window.formDataStorage['lista_materias_nombres'] || [];

    // --- FUNCIÓN PARA RENDERIZAR LA LISTA DE LA DERECHA ---
    function actualizarVista() {
        listaSeleccionadas.innerHTML = '';
        
        seleccionadas.forEach((materia, index) => {
            const div = document.createElement('div');
            div.style = "display: flex; justify-content: space-between; padding: 5px; border-bottom: 1px solid #eee; align-items: center;";
            div.innerHTML = `
                <span>${materia}</span>
                <button type="button" onclick="eliminarMateria(${index})" style="background: #dc3545; color: white; border: none; border-radius: 3px; cursor: pointer;">×</button>
            `;
            listaSeleccionadas.appendChild(div);
        });

        // Actualizar contador y Storage Global
        contadorVisor.innerText = seleccionadas.length;
        window.formDataStorage['m_ins'] = seleccionadas.length; // Para el paso 6
        window.formDataStorage['lista_materias_nombres'] = seleccionadas;

        // Sincronizar Checkboxes (por si se borró algo de la lista)
        const cbs = listaDisponibles.querySelectorAll('.materia-cb');
        cbs.forEach(cb => {
            const label = cb.parentElement.textContent.trim();
            cb.checked = seleccionadas.includes(label);
        });
    }

    // --- EVENTO: CLICK EN CHECKBOXES ---
    listaDisponibles.addEventListener('change', (e) => {
        if (e.target.classList.contains('materia-cb')) {
            const nombreMateria = e.target.parentElement.textContent.trim();
            
            if (e.target.checked) {
                if (seleccionadas.length < MAX_MATERIAS) {
                    if (!seleccionadas.includes(nombreMateria)) seleccionadas.push(nombreMateria);
                } else {
                    e.target.checked = false;
                    alert("⚠️ Solo puedes inscribir un máximo de 8 materias.");
                }
            } else {
                seleccionadas = seleccionadas.filter(m => m !== nombreMateria);
            }
            actualizarVista();
        }
    });

    // --- EVENTO: AÑADIR MANUAL ---
    btnManual.onclick = () => {
        const nombre = inputManual.value.trim();
        if (!nombre) return;
        
        if (seleccionadas.length >= MAX_MATERIAS) {
            alert("⚠️ Máximo 8 materias alcanzado.");
            return;
        }

        if (seleccionadas.includes(nombre)) {
            alert("Esta materia ya está en la lista.");
        } else {
            seleccionadas.push(nombre);
            inputManual.value = '';
            actualizarVista();
        }
    };

    // --- FUNCIÓN GLOBAL PARA EL BOTÓN ELIMINAR (X) ---
    window.eliminarMateria = (index) => {
        seleccionadas.splice(index, 1);
        actualizarVista();
    };

    // Renderizar al cargar por si el usuario regresó del paso 6
    actualizarVista();
}