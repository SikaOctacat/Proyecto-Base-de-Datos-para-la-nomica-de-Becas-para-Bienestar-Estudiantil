/**
 * ARCHIVO: main.js (Raíz) - Lógica de Navegación e Integración
 * Versión simplificada: 8 pasos (Se eliminó Laboral y Materias)
 */
document.addEventListener('DOMContentLoaded', () => {
    // --- ESTADO INICIAL ---
    let currentStep = 1; 
    let maxStepReached = 1; 
    window.formSubmitted = false; 
    const totalSteps = 8; // Nuevo límite total
    
    const viewPort = document.getElementById('dynamic-content'); 
    const progressBar = document.getElementById('progressBar'); 
    
    // --- PRIORIDAD: PERFIL DE ESTUDIANTE ---
    const userRole = document.body.getAttribute('data-rol');
    if (userRole === 'estudiante') {
        showRegistrationSummary();
        return;
    }
    
    window.formDataStorage = {}; 

    // Mapeo reestructurado (8 pasos seguidos)
    const folderMap = {
        1: "1. Pagina Identificacion",
        2: "2. Pagina Residencia",
        3: "3. Pagina PNF", // Antes era 4
        4: "4. Pagina Record academico", // Antes era 6
        5: "5. Pagina Familiares", // Antes era 7
        6: "6. Pagina Datos extra", // Antes era 8
        7: "7. Verificacion", // Antes era 9
        8: "8. Pantalla final" // Antes era 10
    };

    /**
     * Guarda los valores actuales en memoria
     */
    window.saveCurrentData = () => {
        const viewPort = document.getElementById('dynamic-content');
        const inputs = viewPort.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            if (input.name) {
                if (input.type === 'checkbox') {
                    window.formDataStorage[input.name] = input.checked;
                } 
                else if (input.type === 'radio') {
                    if (input.checked) window.formDataStorage[input.name] = input.value;
                } 
                else {
                    window.formDataStorage[input.name] = input.value;
                }
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
        
        const htmlPath = `Paginas/${folder}/view.php`; 
        const scriptPath = `Paginas/${folder}/script.js`;

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
                    4: 'initRecord',
                    5: 'initFamiliares',
                    6: 'initDatosExtra',
                    7: 'renderResumen',
                    8: 'renderFinalStatus'
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

    /**
     * Lógica para el botón "Borrar formulario"
     * Ahora solo limpia la vista actual y remueve esos datos específicos del storage
     */
    const btnLimpiar = document.getElementById('btnLimpiarRegistro');
    if (btnLimpiar) {
        btnLimpiar.onclick = (e) => {
            // Evitamos que el botón recargue la página si está dentro de un form
            e.preventDefault();

            if (confirm("¿Deseas limpiar los campos de esta página actual?")) {
                const viewPort = document.getElementById('dynamic-content');
                const inputs = viewPort.querySelectorAll('input, select, textarea');

                inputs.forEach(input => {
                    if (input.name) {
                        // 1. Limpiamos el valor visualmente
                        if (input.type === 'checkbox' || input.type === 'radio') {
                            input.checked = false;
                        } else {
                            input.value = "";
                        }

                        // 2. Eliminamos la clave específica de la memoria global
                        // para que no se restaure al navegar
                        delete window.formDataStorage[input.name];
                    }
                });

                // 3. Forzamos la re-validación para deshabilitar el botón "Siguiente"
                validarFormularioActual();
                
                console.log("Campos de la página actual limpiados.");
            }
        };
    }


    /**
     * Muestra el perfil completo y oculta toda la interfaz de formulario.
     */
    function showRegistrationSummary() {
        const sp = window.studentProfile;
        if (!sp) return;
        
        // 1. Hide EVERYTHING else from the layout
        const elementsToHide = [
            '.page-header', 
            '.progress-wrapper', 
            '.nav-buttons', 
            '.site-footer', 
            '#menuToggleSPA', 
            '#slideMenuSPA',
            '.btn-clear-data'
        ];
        elementsToHide.forEach(selector => {
            const el = document.querySelector(selector);
            if(el) el.style.display = 'none';
        });

        // 2. Format Family Members Table
        let familyHtml = '';
        if (sp.familiares && sp.familiares.length > 0) {
            familyHtml = `
                <table style="width:100%; border-collapse: collapse; font-size: 0.85rem; margin-top: 10px;">
                    <thead>
                        <tr style="background:rgba(0,0,0,0.05);">
                            <th style="padding:10px; text-align:left; border-bottom:1px solid #ddd;">Nombre</th>
                            <th style="padding:10px; text-align:left; border-bottom:1px solid #ddd;">Parentesco</th>
                            <th style="padding:10px; text-align:left; border-bottom:1px solid #ddd;">Ingreso</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${sp.familiares.map(f => `
                            <tr>
                                <td style="padding:8px; border-bottom:1px solid #eee;">${f.nombres} ${f.apellidos}</td>
                                <td style="padding:8px; border-bottom:1px solid #eee;">${f.parentesco}</td>
                                <td style="padding:8px; border-bottom:1px solid #eee; font-weight:700;">${f.ingreso} Bs</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        } else {
            familyHtml = '<p style="color:#999; font-style:italic;">No se registraron familiares.</p>';
        }

        // 3. Format Enrolled Subjects
        const subjectsHtml = (sp.materias_inscritas && sp.materias_inscritas.length > 0) 
            ? sp.materias_inscritas.map(m => `<span style="display:inline-block; background:#f0f0f0; padding:4px 10px; border-radius:15px; font-size:0.8rem; margin:2px;">${m}</span>`).join('') 
            : '<p style="color:#999; font-style:italic;">Sin materias inscritas.</p>';

        // 4. Prepare the clean Profile View
        const fullProfileHtml = `
            <style>
                @keyframes slideIn {
                    from { opacity: 0; transform: translateY(30px) scale(0.98); }
                    to { opacity: 1; transform: translateY(0) scale(1); }
                }
                .glass-card:hover { transform: translateY(-5px); transition: transform 0.3s ease; box-shadow: 0 15px 35px rgba(0,0,0,0.1) !important; }
            </style>
            <div class="registration-summary full-profile"
 style="margin-top:20px; animation: slideIn 0.6s cubic-bezier(0.23, 1, 0.32, 1); max-width: 960px; margin: 0 auto; padding-bottom: 80px;">
                <div style="background: rgba(255, 255, 255, 0.1); backdrop-filter: blur(20px); border-radius: 30px; padding: 40px; text-align:center; margin-bottom:35px; border: 1px solid rgba(255,255,255,0.2); box-shadow: 0 20px 40px rgba(0,0,0,0.1);">
                    <div style="width:110px; height:110px; background: linear-gradient(135deg, #FF6600, #FF3D00); color:white; border-radius:30px; display:flex; align-items:center; justify-content:center; margin:0 auto 20px; font-size:45px; font-weight:900; box-shadow:0 15px 30px rgba(255,61,0,0.3); transform: rotate(-5deg);">
                        ${(sp.nombres || "E").charAt(0).toUpperCase()}
                    </div>
                    <h1 style="color:#2c3e50; margin:0; font-size:2.5rem; font-weight: 800; letter-spacing: -1px;">${sp.nombres || "Estudiante"} ${sp.apellidos || ""}</h1>
                    <p style="color:#546e7a; margin:8px 0; font-size: 1.1rem; font-weight: 500;">C.I. ${sp.ci || "N/A"} ${sp.id ? `· ID de Sistema: #${sp.id}` : ''}</p>
                    <div style="display:inline-flex; align-items:center; gap:8px; background:#e8f5e9; color:#2e7d32; padding:8px 20px; border-radius:50px; font-size:0.95rem; font-weight:700; border: 1px solid #c8e6c9; margin-top:15px;">
                        <span style="font-size:1.2rem;">●</span> Estatus de Postulación: ${sp.estado_beca || "Registrada"}
                    </div>

                    <!-- Eligibility Check Message -->
                    ${(() => {
                        const age = parseInt(sp.edad) || 0;
                        const index = parseFloat(sp.indice_trimestre) || 0;
                        const works = !!sp.empresa;
                        const isActive = sp.estatus_estudio === 'activo';

                        const meetsAge = age >= 17 && age <= 39;
                        const meetsIndex = index >= 16;
                        const meetsWork = !works;
                        const meetsStatus = isActive;

                        const isEligible = meetsAge && meetsIndex && meetsWork && meetsStatus;

                        if (isEligible) {
                            return `
                                <div style="margin-top:20px; padding:15px; background:#e8f5e9; color:#2e7d32; border-radius:15px; border:1px solid #c8e6c9; text-align:left; display:flex; align-items:center; gap:15px;">
                                    <span style="font-size:2rem;">✅</span>
                                    <div>
                                        <strong style="display:block; font-size:1.1rem;">¡Felicidades! Cumples con los requisitos.</strong>
                                        <span style="font-size:0.85rem; opacity:0.8;">Tu perfil cumple con la edad, promedio, estatus laboral y académico para optar por la beca.</span>
                                    </div>
                                </div>`;
                        } else {
                            let reasons = [];
                            if (!meetsAge) reasons.push("Edad fuera del rango (17-39)");
                            if (!meetsIndex) reasons.push("Promedio menor a 16");
                            if (works) reasons.push("Posees un empleo");
                            if (!meetsStatus) reasons.push("No te encuentras activo en tus estudios");

                            return `
                                <div style="margin-top:20px; padding:15px; background:#fff3e0; color:#e65100; border-radius:15px; border:1px solid #ffe0b2; text-align:left; display:flex; align-items:center; gap:15px;">
                                    <span style="font-size:2rem;">⚠️</span>
                                    <div>
                                        <strong style="display:block; font-size:1.1rem;">No cumples con todos los requisitos.</strong>
                                        <span style="font-size:0.85rem; opacity:0.8;">Motivos: ${reasons.join(', ')}.</span>
                                    </div>
                                </div>`;
                        }
                    })()}
                </div>

                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:20px;">
                    <!-- Académico -->
                    <div class="glass-card" style="background:rgba(255,255,255,0.8); backdrop-filter:blur(10px); padding:20px; border-radius:20px; box-shadow:0 8px 32px rgba(0,0,0,0.05); border:1px solid rgba(255,255,255,0.5);">
                        <h4 style="color:#FF6600; margin-top:0; border-bottom:2px solid #fff5e6; padding-bottom:10px; margin-bottom:15px; display:flex; align-items:center; gap:10px; font-size: 1.1rem;">
                            🎓 Info. Académica
                        </h4>
                        <div style="display:grid; gap:8px;">
                            <p style="margin:0; font-size:0.95rem;"><strong style="color: #666;">Carrera:</strong> ${sp.carrera || "No asignada"}</p>
                            <p style="margin:0; font-size:0.95rem;"><strong style="color: #666;">Trayecto:</strong> ${sp.trayecto || "-"} / Trimestre: ${sp.trimestre_actual || "-"}</p>
                            <p style="margin:0; font-size:0.95rem;"><strong style="color: #666;">Código:</strong> ${sp.codigo_estudiante || "N/A"}</p>
                            <p style="margin:8px 0 0 0; font-size:0.95rem;"><strong style="color: #666;">Índice:</strong> <span style="background:#fff3e0; color:#e65100; padding:2px 8px; border-radius:10px; font-weight:700;">${sp.indice_trimestre || "0.0"}</span></p>
                        </div>
                    </div>

                    <!-- Personal y Contacto -->
                    <div class="glass-card" style="background:rgba(255,255,255,0.8); backdrop-filter:blur(10px); padding:20px; border-radius:20px; box-shadow:0 8px 32px rgba(0,0,0,0.05); border:1px solid rgba(255,255,255,0.5);">
                        <h4 style="color:#FF6600; margin-top:0; border-bottom:2px solid #fff5e6; padding-bottom:10px; margin-bottom:15px; display:flex; align-items:center; gap:10px; font-size: 1.1rem;">
                            👤 Info. Personal
                        </h4>
                        <div style="display:grid; gap:8px;">
                            <p style="margin:0; font-size:0.95rem;"><strong style="color: #666;">Teléfono:</strong> ${sp.telefono || "-"}</p>
                            <p style="margin:0; font-size:0.95rem;"><strong style="color: #666;">Correo:</strong> ${sp.correo || "-"}</p>
                            <p style="margin:0; font-size:0.95rem;"><strong style="color: #666;">Estado Civil:</strong> ${sp.estado_civil || "-"}</p>
                            <p style="margin:0; font-size:0.95rem;"><strong style="color: #666;">C. Patria:</strong> ${sp.carnet_patria || "-"}</p>
                        </div>
                    </div>

                    <!-- Vivienda y Trabajo -->
                    <div class="glass-card" style="background:rgba(255,255,255,0.8); backdrop-filter:blur(10px); padding:20px; border-radius:20px; box-shadow:0 8px 32px rgba(0,0,0,0.05); border:1px solid rgba(255,255,255,0.5);">
                        <h4 style="color:#FF6600; margin-top:0; border-bottom:2px solid #fff5e6; padding-bottom:10px; margin-bottom:15px; display:flex; align-items:center; gap:10px; font-size: 1.1rem;">
                            🏠 Residencia y Trabajo
                        </h4>
                        <div style="display:grid; gap:8px;">
                            <p style="margin:0; font-size:0.9rem;"><strong style="color: #666;">Vivienda:</strong> ${sp.tipo_vivienda || "-"}</p>
                            <p style="margin:0; font-size:0.9rem; line-height:1.2; height:2.4em; overflow:hidden;" title="${sp.dir_residencia || '-'}"><strong style="color: #666;">Dirección:</strong> ${sp.dir_residencia || "-"}</p>
                            <p style="margin:5px 0 0 0; font-size:0.9rem; border-top:1px dashed #eee; padding-top:5px;"><strong style="color: #666;">Empresa:</strong> ${sp.empresa || "No trabaja"}</p>
                            ${sp.cargo_trabajo ? `<p style="margin:0; font-size:0.9rem;"><strong style="color: #666;">Sueldo:</strong> ${sp.sueldo || "0"} Bs</p>` : ''}
                        </div>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap:20px; margin-top:20px;">
                    <!-- Familiares -->
                    <div class="glass-card" style="background:rgba(255,255,255,0.8); backdrop-filter:blur(10px); padding:20px; border-radius:20px; box-shadow:0 8px 32px rgba(0,0,0,0.05); border:1px solid rgba(255,255,255,0.5);">
                        <h4 style="color:#FF6600; margin-top:0; border-bottom:2px solid #fff5e6; padding-bottom:10px; margin-bottom:10px; display:flex; align-items:center; gap:10px; font-size: 1.1rem;">
                            👨‍👩‍👧‍👦 Carga Familiar
                        </h4>
                        <div style="max-height: 200px; overflow-y: auto;">
                            ${familyHtml}
                        </div>
                    </div>

                    <!-- Materias -->
                    <div class="glass-card" style="background:rgba(255,255,255,0.8); backdrop-filter:blur(10px); padding:20px; border-radius:20px; box-shadow:0 8px 32px rgba(0,0,0,0.05); border:1px solid rgba(255,255,255,0.5);">
                        <h4 style="color:#FF6600; margin-top:0; border-bottom:2px solid #fff5e6; padding-bottom:10px; margin-bottom:10px; display:flex; align-items:center; gap:10px; font-size: 1.1rem;">
                            📚 Materias Inscritas
                        </h4>
                        <div style="display:flex; flex-wrap:wrap; gap:5px;">
                            ${subjectsHtml}
                        </div>
                        <div style="margin-top:15px; background:rgba(0,0,0,0.02); padding:10px; border-radius:10px;">
                            <p style="margin:0; font-size:0.85rem;"><strong style="color: #555;">Inscritas:</strong> ${sp.n_materias_inscritas || "0"}</p>
                            <p style="margin:0; font-size:0.85rem;"><strong style="color: #555;">Aprobadas:</strong> ${sp.n_materias_aprobadas || "0"}</p>
                        </div>
                    </div>
                </div>

                <div style="margin-top:40px; text-align:center; padding:25px; border-radius: 20px; background: rgba(0,0,0,0.02);">
                    <p style="color:#95a5a6; font-size:0.9rem; margin-bottom:20px; max-width: 500px; margin-left: auto; margin-right: auto;">
                        Tus datos están en proceso de verificación por la oficina de Bienestar Estudiantil. Si detectas algún error, solicita asistencia técnica.
                    </p>
                    <a href="logout.php" class="btn-clear-data logout-profile" style="display:inline-block; background:#2c3e50; color:white; padding:12px 35px; border-radius:12px; text-decoration:none; font-weight:700; transition:all 0.3s; box-shadow: 0 4px 12px rgba(0,0,0,0.1); font-size:0.95rem;">
                        Cerrar Sesión Segura
                    </a>
                </div>
            </div>
        `;

        // 3. Inject the clean view into the dynamic content area
        const contentArea = document.getElementById('dynamic-content');
        if (contentArea) {
            contentArea.innerHTML = fullProfileHtml;
            
            // Further ensure the outer layout doesn't interfere
            const mainCard = document.querySelector('.card');
            const mainContainer = document.getElementById('main-container');
            if(mainCard) {
                mainCard.style.background = 'transparent';
                mainCard.style.boxShadow = 'none';
                mainCard.style.padding = '0';
            }
            if(mainContainer) {
                mainContainer.style.background = 'transparent';
                mainContainer.style.paddingTop = '10px';
                mainContainer.style.boxShadow = 'none';
            }
            // Hide the progress bar container
            const progWrapper = document.querySelector('.progress-wrapper');
            if(progWrapper) progWrapper.style.display = 'none';
            
            // Hide header entirely
            const pageHeader = document.querySelector('.page-header');
            if(pageHeader) pageHeader.style.display = 'none';
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
        if (progressBar) progressBar.style.width = `${(step / totalSteps) * 100}%`;
        
        const prevBtn = document.getElementById('prevBtn');
        if (prevBtn) prevBtn.style.display = (step <= 1 || step >= 8) ? 'none' : 'inline-block';

        // --- GESTIÓN DEL BOTÓN DE LIMPIEZA ---
        const btnLimpiar = document.getElementById('btnLimpiarRegistro');
        if (btnLimpiar) {
            // Se oculta desde el paso 7 en adelante (Verificación y Final)
            btnLimpiar.style.display = (step >= 7) ? 'none' : 'block';
        }
        
        const nextBtn = document.getElementById('nextBtn');
        if (nextBtn) {
            nextBtn.textContent = (step === 7) ? "Confirmar" : "Siguiente";
            nextBtn.style.display = (step >= 8) ? 'none' : 'inline-block';
            
            // Ejecutar validación inicial al cargar el paso
            validarFormularioActual(); 
        }
    }

    /**
     * Manejador del botón "Siguiente" / "Confirmar"
     */
    const nextBtnEl = document.getElementById('nextBtn');
    if (nextBtnEl) {
        nextBtnEl.onclick = async () => {
            if (currentStep === 1 && typeof validarPaso1 === 'function' && !validarPaso1()) return;
            if (currentStep === 4 && typeof validarRecord === 'function' && !validarRecord()) return;

            window.saveCurrentData();

            if (currentStep === 7) { // Paso de Verificación
                try {
                    const res = await fetch('submit.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(window.formDataStorage)
                    });
                    const obj = await res.json();
                    if (obj.status !== 'ok') { alert('Error: ' + obj.error); return; }
                    window.formSubmitted = true;
                } catch(e) { alert('Error de red'); return; }
            }

            currentStep++;
            loadStep(currentStep);
        };
    }

    const prevBtnEl = document.getElementById('prevBtn');
    if (prevBtnEl) {
        prevBtnEl.onclick = () => {
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