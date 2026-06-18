document.addEventListener('DOMContentLoaded', () => {
    let currentStep = 1;
    let maxStepReached = 1; 
    window.formSubmitted = false; 
    const totalSteps = 7;
    
    const viewPort = document.getElementById('dynamic-content'); 
    const progressBar = document.getElementById('progressBar'); 
    
    const userRole = document.body.getAttribute('data-rol');
    if (userRole === 'estudiante') {
        // Ocultamos los contenedores que no pertenecen al perfil
        const navContainer = document.getElementById('nav-buttons');
        const progContainer = document.getElementById('progress-wrapper');
        if(navContainer) navContainer.style.display = 'none';
        if(progContainer) progContainer.style.display = 'none';
        
        showRegistrationSummary();
        return;
    }
    
    window.formDataStorage = {}; 

    const folderMap = {
        1: "identificacion",
        2: "residencia",
        3: "PNF",
        4: "familiares",
        5: "datos_extra",
        6: "verificacion",
        7: "pantalla_de_exito"
    };
    window.saveCurrentData = () => {
        const viewPort = document.getElementById('dynamic-content');
        const inputs = viewPort.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            if (input.name) {
                let valueToSave;

                if (input.type === 'checkbox') {
                    valueToSave = input.checked;
                } 
                else if (input.type === 'radio') {
                    if (input.checked) valueToSave = input.value;
                    else return; // No guardar radios no seleccionados
                } 
                else {
                    valueToSave = input.value;
                }

                // --- FILTRO DE LIMPIEZA ---
                // Si es un texto, reemplazamos guiones bajos por espacios
                if (typeof valueToSave === 'string') {
                    valueToSave = valueToSave.replace(/_/g, ' ');
                }

                window.formDataStorage[input.name] = valueToSave;
            }
        });
    };

    /**
     * Restaura datos guardados
     */
    window.restoreDataGlobal = () => {
        const viewPort = document.getElementById('dynamic-content');
        const inputs = viewPort.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            const savedValue = window.formDataStorage[input.name];
            if (savedValue !== undefined) {
                if (input.type === 'checkbox') input.checked = savedValue;
                else if (input.type === 'radio') input.checked = (input.value === savedValue);
                else input.value = savedValue;
            } 
        });
    };

    /**
     * Carga dinámica de pasos
     */
    async function loadStep(stepNumber) {
        stepNumber = Number(stepNumber) || 1;
        currentStep = stepNumber; 
        
        const folder = folderMap[stepNumber];
        if (!folder) return;

        if (stepNumber > maxStepReached) maxStepReached = stepNumber;

        if(viewPort) {
            viewPort.classList.add('loading');
            viewPort.innerHTML = '<div style="text-align:center; padding:50px; color:#666;">Cargando paso...</div>';
        }
        
        const htmlPath = `${folder}/view.php`; 
        const scriptPath = `${folder}/script.js`;

        try {
            const response = await fetch(htmlPath);
            if (!response.ok) throw new Error(`No se pudo acceder a ${folder}`);
            const html = await response.text();
            
            viewPort.innerHTML = html;
            viewPort.querySelectorAll('footer.site-footer').forEach(f => f.remove());

            window.restoreDataGlobal();
            if(typeof updateMenuState === 'function') updateMenuState(stepNumber);

            const oldScript = document.getElementById('step-script');
            if (oldScript) oldScript.remove();

            const script = document.createElement('script');
            script.id = 'step-script';
            script.src = `${scriptPath}?v=${Date.now()}`;
            
            script.onload = () => {
                const initFuncs = {
                    1: 'initIdentificacion',
                    2: 'initResidencia',
                    3: 'initPNF',
                    4: 'initFamiliares',
                    5: 'initDatosExtra',
                    6: 'renderResumen',
                    7: 'renderFinalStatus'
                };
                const funcName = initFuncs[stepNumber];
                if (funcName && typeof window[funcName] === 'function') {
                    window[funcName]();
                }
                
                // IMPORTANTE: Llamar a la validación aquí
                validarFormularioActual(); 
            };

            script.onerror = () => {
                console.error(`[Flow] Error al cargar el script: ${scriptPath}`);
            };
            
            document.body.appendChild(script);

            // 4. Actualizar botones y progreso
            actualizarInterfaz(stepNumber);

        } catch (e) { 
            console.error("Error crítico en loadStep:", e);
            if(viewPort) {
                viewPort.innerHTML = `
                    <div style="background:#fff3f3; color:#d32f2f; padding:30px; border-radius:15px; border:1px solid #ffcdd2; margin:20px;">
                        <h3 style="margin-top:0;">⚠️ Error de Carga</h3>
                        <p style="font-size:0.9rem;">${e.message}</p>
                        <hr style="border:0; border-top:1px solid #ffcdd2; margin:15px 0;">
                        <button onclick="location.reload()" class="btn-primary" style="background:#d32f2f;">Reintentar</button>
                    </div>`;
            }
        } finally {
            // Quitar clase de carga
            setTimeout(() => { 
                if(viewPort) viewPort.classList.remove('loading'); 
            }, 150);
        }
    }

    // Lógica para el botón "Borrar formulario"
    const btnLimpiar = document.getElementById('btnLimpiarRegistro');
if (btnLimpiar) {
        btnLimpiar.onclick = (e) => {
            e.preventDefault();

            if (confirm("¿Deseas limpiar los campos de esta página actual?")) {
                const viewPort = document.getElementById('dynamic-content');
                const inputs = viewPort.querySelectorAll('input, select, textarea');
                const errorText = document.getElementById('error-pass'); // El texto de advertencia

                inputs.forEach(input => {
                    // 1. Limpieza visual (aplica a TODOS los inputs del viewport)
                    if (input.type === 'checkbox' || input.type === 'radio') {
                        input.checked = false;
                    } else {
                        input.value = "";
                    }

                    // 2. Limpieza de memoria (solo si tienen 'name')
                    if (input.name) {
                        delete window.formDataStorage[input.name];
                    }
                    
                    // 3. Reset de estilos visuales de error (específico para contraseñas)
                    input.style.borderColor = '#ddd';
                });

                // 4. Ocultamos el textico de error si existe
                if (errorText) {
                    errorText.style.display = 'none';
                }

                // 5. Forzamos re-validación
                validarFormularioActual();
                
                console.log("Campos de la página actual y confirmación de contraseña limpiados.");
            }
        };
    }
    /**
     * Lógica para el botón "Volver al Inicio" con verificación
     */
    const btnVolver = document.getElementById('btnVolverInicio');
    if (btnVolver) {
        btnVolver.onclick = (e) => {
            // Si el formulario ya se envió (paso 7) o está en el primer paso vacío, no preguntamos
            if (window.formSubmitted || currentStep >= 7) {
                return true; // Permite la navegación normal
            }

            // Si hay datos, pedimos confirmación
            const confirmacion = confirm("¿Estás seguro de que quieres salir? Se perderá el progreso que no haya sido guardado en el sistema.");
            
            if (!confirmacion) {
                e.preventDefault(); // Cancela el redireccionamiento a index.php
            }
        };
    }


    /**
     * Muestra el perfil completo y oculta toda la interfaz de formulario.
     */
    async function showRegistrationSummary() {
        const sp = window.studentProfile;
        if (!sp || !viewPort) return;

        try {
            const response = await fetch('usuario/perfil.php');
            if (!response.ok) throw new Error("No se pudo cargar la vista");
            let html = await response.text();

            // ... (lógica de reemplazos {{...}} se mantiene igual) ...

            // Insertamos directamente en el viewport para que no se pierda el diseño
            viewPort.innerHTML = html;
            window.scrollTo(0, 0);

        } catch (e) {
            console.error("Error en perfil:", e);
            viewPort.innerHTML = "<h3>Error al cargar el perfil.</h3>";
        }
    }

    /**
     * Valida los campos 'required' del paso actual.
     * Pone bordes rojos y deshabilita el botón 'Siguiente' si falta algo.
     */
    function validarFormularioActual() {
        const viewPort = document.getElementById('dynamic-content');
        const nextBtn = document.getElementById('nextBtn');
        if (!viewPort || !nextBtn) return;

        // Buscamos todos los campos que tengan el atributo 'required'
        const inputs = viewPort.querySelectorAll('input[required], select[required], textarea[required]');
        let todoValido = true;

        inputs.forEach(input => {
            // Función para marcar error
            const checkValidity = () => {
                let esValido = true;
                
                if (input.type === 'checkbox' || input.type === 'radio') {
                    // Para radio/checkbox, verificamos que alguno del mismo nombre esté marcado
                    const group = viewPort.querySelectorAll(`input[name="${input.name}"]`);
                    esValido = Array.from(group).some(i => i.checked);
                } else {
                    esValido = input.value.trim() !== "";
                }

                if (!esValido) {
                    input.style.borderColor = '#d9534f'; // Rojo
                    input.style.backgroundColor = '#fff8f8';
                    todoValido = false;
                } else {
                    input.style.borderColor = ''; // Restaurar
                    input.style.backgroundColor = '';
                }
                
                // Re-evaluar el estado del botón cada vez que se escribe
                actualizarEstadoBoton();
            };

            // Escuchar eventos para validar mientras el usuario escribe o cambia
            input.removeEventListener('input', checkValidity);
            input.removeEventListener('change', checkValidity);
            input.addEventListener('input', checkValidity);
            input.addEventListener('change', checkValidity);
            
            // Validación inicial
            if (input.type !== 'checkbox' && input.type !== 'radio' && input.value.trim() === "") {
                todoValido = false;
            }
        });

        function actualizarEstadoBoton() {

            // Si el paso actual ha marcado el botón como bloqueado por lógica de negocio
            if (nextBtn.dataset.locked === "true") {
                nextBtn.disabled = true;
                nextBtn.style.opacity = "0.5";
                return;
            }

            let actualValido = true;
            inputs.forEach(i => {
                if (i.type === 'checkbox' || i.type === 'radio') {
                    const group = viewPort.querySelectorAll(`input[name="${i.name}"]`);
                    if (!Array.from(group).some(radio => radio.checked)) actualValido = false;
                } else {
                    if (i.value.trim() === "") actualValido = false;
                }
            });
            
            nextBtn.disabled = !actualValido;
            nextBtn.style.opacity = actualValido ? "1" : "0.5";
            nextBtn.style.cursor = actualValido ? "pointer" : "not-allowed";
        }

        actualizarEstadoBoton();
    }

    /**
     * Gestiona la visibilidad y estados de los elementos de navegación
     */
    function actualizarInterfaz(step) {
    // 1. Barra de progreso (ahora se calcula sobre 7)
    if (progressBar) {
        progressBar.style.width = `${(step / totalSteps) * 100}%`;
    }

    // --- GESTIÓN DE BOTONES DE VOLVER ---
    const btnVolverCabecera = document.getElementById('btnVolverInicio');
    if (btnVolverCabecera) {
        // Si el paso es 7 (Final), OCULTAMOS el botón verde de la esquina
        if (step >= 7) {
            btnVolverCabecera.style.display = 'none'; 
        } else {
            btnVolverCabecera.style.display = 'flex';
        }
    }

    const prevBtn = document.getElementById('prevBtn');
        if (prevBtn) {
            // No hay "Atrás" en el paso 1 ni en el paso final (7)
            prevBtn.style.display = (step <= 1 || step >= 7) ? 'none' : 'inline-block';
        }

        // --- GESTIÓN DEL BOTÓN DE LIMPIEZA ---
        const btnLimpiar = document.getElementById('btnLimpiarRegistro');
        if (btnLimpiar) {
            // Se oculta en Verificación (6) y Pantalla Final (7)
            btnLimpiar.style.display = (step >= 4) ? 'none' : 'block';
        }

        // --- GESTIÓN DEL BOTÓN SIGUIENTE ---
        const nextBtn = document.getElementById('nextBtn');
        if (nextBtn) {
            // En el paso 6 (Verificación), el texto cambia a Confirmar
            nextBtn.textContent = (step === 6) ? "Confirmar" : "Siguiente";
            
            // Se oculta en la pantalla final (7)
            nextBtn.style.display = (step >= 7) ? 'none' : 'inline-block';
            
            // Ejecutar validación inicial al cargar el paso
            validarFormularioActual(); 
        }
    }

    /**
     * Manejador del botón "Siguiente" / "Confirmar"
     */
    const nextBtnEl = document.getElementById('nextBtn');
    if (nextBtnEl) {
        nextBtnEl.type = 'button'; 

        nextBtnEl.onclick = async (e) => {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }

            // Validación manual del paso 1 si existe la función
            if (currentStep === 1 && typeof validarPaso1 === 'function' && !validarPaso1()) return false;

            // Guardamos lo que haya en los inputs actuales antes de movernos o enviar
            window.saveCurrentData();

            // --- BLOQUE DE ENVÍO FINAL (PASO 6) ---
            if (currentStep === 6) {
                nextBtnEl.disabled = true;
                nextBtnEl.textContent = "Procesando...";

                if (!window.formDataStorage || Object.keys(window.formDataStorage).length === 0) {
                    alert("Error: No hay datos para enviar.");
                    nextBtnEl.disabled = false;
                    nextBtnEl.textContent = "Confirmar";
                    return;
                }

                try {
                    const urlDestino = window.urlSubmit || 'submit.php';
                    
                    const res = await fetch(urlDestino, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(window.formDataStorage)
                    });

                    const textoRespuesta = await res.text();
                    const obj = JSON.parse(textoRespuesta);
                    
                    if (obj.status === 'ok') {
                        // ¡LOGRADO! 
                        window.formSubmitted = true;
                        currentStep = 7; // Saltamos al paso final
                        loadStep(7);     // Cargamos la vista de éxito
                        return;          // IMPORTANTE: Salimos de la función aquí
                    } else {
                        alert("Error del servidor: " + (obj.error || "Desconocido"));
                        nextBtnEl.disabled = false;
                        nextBtnEl.textContent = "Confirmar";
                        return; // No avanzamos si hay error
                    }

                } catch (err) {
                    console.error("ERROR CRÍTICO:", err);
                    alert("Fallo la comunicación: " + err.message);
                    nextBtnEl.disabled = false;
                    nextBtnEl.textContent = "Confirmar";
                    return;
                }
            }

            // --- NAVEGACIÓN NORMAL (Pasos 1 al 5) ---
            if (currentStep < 6) {
                currentStep++;
                loadStep(currentStep);
            }
        };
    }

    const prevBtnEl = document.getElementById('prevBtn');
    if (prevBtnEl) {
        prevBtnEl.onclick = (e) => {
            // Bloqueamos la recarga también en el botón de retroceso
            e.preventDefault(); 
            window.saveCurrentData();
            currentStep--;
            loadStep(currentStep);
        };
    }

    function updateMenuState(step) {
        const links = document.querySelectorAll('#slideMenuSPA a[data-step]');
        links.forEach(a => {
            const s = parseInt(a.getAttribute('data-step'), 10);
            a.classList.remove('active','disabled');
            if(s === step) a.classList.add('active');
            if(s > maxStepReached) a.classList.add('disabled');
        });
    }
    
    window.loadStep = loadStep;
    loadStep(currentStep);
});