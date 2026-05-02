class TablaDinamica {
    constructor(config) {
        // Elementos del DOM
        this.cuerpo = document.getElementById(config.cuerpoId);
        this.btnAgregar = document.getElementById(config.btnAgregarId);
        this.checkOmitir = document.getElementById(config.checkOmitirId);
        this.contenedor = document.querySelector(config.contenedorClass);
        this.aviso = document.getElementById(config.avisoId);
        this.nextBtn = document.getElementById('nextBtn');

        // Configuración
        this.columnas = config.columnas; 
        this.maxFilas = config.maxFilas || 5;
        this.prefijoKey = config.prefijoKey || 'f'; 
        this.storageKey = config.storageKey || 'formDataStorage';
        
        this.init();
    }

    init() {
        if (!this.cuerpo || !this.btnAgregar) return;

        // Limpiar cuerpo al iniciar
        this.cuerpo.innerHTML = "";

        // Evento Agregar
        this.btnAgregar.onclick = (e) => {
            e.preventDefault();
            if (this.cuerpo.querySelectorAll('tr').length < this.maxFilas) {
                this.crearFila(Date.now());
                this.actualizar();
            }
        };

        // Evento Omitir
        if (this.checkOmitir) {
            this.checkOmitir.onchange = (e) => this.handleOmitir(e);
        }

        // Evento Eliminar (Delegación de eventos)
        this.cuerpo.onclick = (e) => this.handleEliminar(e);

        // No creamos fila inicial aquí si vamos a cargar datos externos luego,
        // pero por seguridad, si está vacío tras 100ms, creamos una.
        setTimeout(() => {
            if (this.cuerpo.querySelectorAll('tr').length === 0 && (!this.checkOmitir || !this.checkOmitir.checked)) {
                this.crearFila(Date.now());
                this.actualizar();
            }
        }, 100);
    }

    /**
     * MÉTODO NUEVO: Para cargar datos directamente desde PHP/Base de Datos
     * @param {Object} datos Objeto con los valores de la fila
     */
    agregarFilaConDatos(datos) {
        const id = Date.now() + Math.random(); // ID único temporal
        const fila = this.crearFila(id);
        const inputs = fila.querySelectorAll('input');

        // Mapeamos cada columna con su dato correspondiente
        this.columnas.forEach((col, index) => {
            if (datos[col.name] !== undefined) {
                inputs[index].value = datos[col.name];
            }
        });
        this.actualizar();
    }

    crearFila(id) {
        const fila = this.cuerpo.insertRow();
        fila.setAttribute('data-id', id);
        
        let htmlColumnas = "";
        this.columnas.forEach(col => {
            // Formato array [] para que PHP lo reciba correctamente
            const nameParaPHP = `${this.prefijoKey}_${col.name}[]`; 
            
            htmlColumnas += `<td>
                <input type="${col.type || 'text'}" 
                    name="${nameParaPHP}" 
                    placeholder="${col.placeholder}" 
                    step="${col.step || ''}" 
                    style="${col.style || 'width:95%'}">
            </td>`;
        });

        htmlColumnas += `<td>
            <button type="button" class="btn-remove" style="background:#ff4444; color:white; border:none; border-radius:50%; cursor:pointer; width:28px; height:28px;">&times;</button>
        </td>`;

        fila.innerHTML = htmlColumnas;

        // Eventos de validación en tiempo real
        fila.querySelectorAll('input').forEach(input => {
            input.addEventListener('input', () => this.actualizar());
        });

        return fila;
    }

    actualizar() {
        const filas = this.cuerpo.querySelectorAll('tr');
        const numFilas = filas.length;
        const estaOmitido = this.checkOmitir?.checked;

        if (this.contenedor) this.contenedor.style.display = estaOmitido ? 'none' : 'block';
        this.btnAgregar.style.display = estaOmitido ? 'none' : 'inline-block';

        // Ocultar botón eliminar si solo queda una fila
        filas.forEach(f => {
            const btn = f.querySelector('.btn-remove');
            if(btn) btn.style.display = (numFilas <= 1) ? 'none' : 'block';
        });

        // Validación de campos vacíos
        let vacios = false;
        if (!estaOmitido) {
            this.cuerpo.querySelectorAll('input').forEach(input => {
                const isVacío = input.value.trim() === "";
                if (isVacío) vacios = true;
                input.style.border = isVacío ? "1px solid #ff6600" : "1px solid #ccc";
            });
        }

        // Actualizar UI de avisos y botón siguiente
        if (this.nextBtn) {
            this.nextBtn.disabled = vacios;
            this.nextBtn.style.opacity = vacios ? "0.5" : "1";
        }

        if (this.aviso) {
            this.aviso.textContent = estaOmitido ? "Sección omitida." : (vacios ? "⚠️ Datos incompletos" : "✓ Completo");
            this.aviso.style.color = vacios ? "#d9534f" : "#28a745";
        }

        this.btnAgregar.disabled = (numFilas >= this.maxFilas || (vacios && numFilas > 0));
        this.btnAgregar.style.background = this.btnAgregar.disabled ? "#ccc" : "#00dc0b";
    }

    handleEliminar(e) {
        const btn = e.target.closest('.btn-remove');
        if (btn && confirm("¿Eliminar registro?")) {
            const fila = btn.closest('tr');
            fila.remove();
            this.actualizar();
        }
    }

    handleOmitir(e) {
        if (e.target.checked) {
            if (!confirm("¿Omitir esta sección? Se borrarán los datos de la tabla.")) {
                e.target.checked = false;
                return;
            }
            this.cuerpo.innerHTML = ""; // Limpiamos la tabla si omiten
        } else {
            this.crearFila(Date.now()); // Creamos una fila si desmarcan
        }
        this.actualizar();
    }
}