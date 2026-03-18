/**
 * ARCHIVO: main.js (Raíz) - Lógica de Navegación e Integración
 * Este script coordina la carga dinámica de formularios (SPA), la persistencia de datos 
 * en memoria y el control del flujo de pasos del usuario.
 */
document.addEventListener('DOMContentLoaded', () => {
    // --- ESTADO INICIAL ---
    let currentStep = 1; // Rastrea el paso actual del formulario
    let maxStepReached = 1; // Rastrea el paso más lejano alcanzado por el usuario
    window.formSubmitted = false; // Flag para validar si los datos fueron realmente enviados
    const totalSteps = 10; // Límite total de pantallas definidas
    
    // Referencias a elementos clave del DOM
    const viewPort = document.getElementById('dynamic-content'); // Contenedor donde se inyecta el HTML de cada paso
    const progressBar = document.getElementById('progressBar'); // Elemento visual de progreso
    const mainContainer = document.getElementById('main-container'); // Contenedor principal del layout
    
    // --- PRIORIDAD: PERFIL DE ESTUDIANTE ---
    // Si el usuario es estudiante, mostramos sus datos directamente y quitamos el formulario
    const userRole = document.body.getAttribute('data-rol');
    if (userRole === 'estudiante') {
        showRegistrationSummary();
        return;
    }
    
    // Objeto global para almacenar los datos del formulario entre cambios de página
    window.formDataStorage = {}; 

    // --- INTEGRACIÓN DEL BOTÓN DE LIMPIEZA ---
    // Se crea dinámicamente el botón para resetear los campos de la vista actual
    const btnLimpiar = document.createElement('button');
    btnLimpiar.className = 'btn-clear-data';
    btnLimpiar.innerHTML = 'Borrar Campos';
    // agregarlo al área de enlaces del encabezado (Admin, Logout)
    const headerLinks = document.querySelector('.page-header .header-links');
    if (headerLinks) {
        // insert before logout link to ensure margin-left:auto works
        const logout = headerLinks.querySelector('a.btn-danger');
        if (logout) headerLinks.insertBefore(btnLimpiar, logout);
        else headerLinks.appendChild(btnLimpiar);
    } else if (mainContainer) {
        // fallback: en caso de no existir, añadir al mainContainer
        mainContainer.appendChild(btnLimpiar);
    }

    // Lógica al hacer clic en "Borrar Campos"
    btnLimpiar.onclick = () => {
        if (confirm("¿Estás seguro de que deseas borrar los datos de esta página?")) {
            // Selecciona todos los elementos de entrada en la vista actual
            const inputs = viewPort.querySelectorAll('input, select, textarea');
            inputs.forEach(input => {
                // Resetea el valor según el tipo de input
                if (input.type === 'checkbox') input.checked = false;
                else input.value = '';
                
                // Elimina la entrada correspondiente del almacenamiento global si tiene un nombre (name)
                if (input.name) delete window.formDataStorage[input.name];
            });
            
            // Caso especial: Reset manual para el campo 'edad' si está en el Paso 1
            if (currentStep === 1 && document.getElementById('edad')) {
                document.getElementById('edad').value = '00';
            }
        }
    };

    // Mapeo de los números de paso con sus respectivas carpetas físicas en el servidor
    const folderMap = {
        1: "1. Pagina Identificacion",
        2: "2. Pagina Residencia",
        3: "3. Pagina Laboral",
        4: "4. Pagina PNF",
        5: "5. Pagina Materias",
        6: "6. Pagina Record academico",
        7: "7. Pagina Familiares",
        8: "8. Pagina Datos extra",
        9: "9. Verificacion",
        10: "10. Pantalla final"
    };

    /**
     * Guarda los valores actuales de los inputs del DOM en el objeto global formDataStorage
     */
    window.saveCurrentData = () => {
        const inputs = viewPort.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            if (input.name) {
                // Almacena booleanos para checkboxes o el string value para el resto
                window.formDataStorage[input.name] = (input.type === 'checkbox') ? input.checked : input.value;
            }
        });
    };

    /**
     * Recupera los valores guardados en formDataStorage y los inyecta en los inputs del DOM
     */
    window.restoreDataGlobal = () => {
        const inputs = viewPort.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            // Priority 1: Data already in storage (user typed something)
            if (window.formDataStorage[input.name] !== undefined) {
                if (input.type === 'checkbox') input.checked = window.formDataStorage[input.name];
                else input.value = window.formDataStorage[input.name];
            } 
            // Priority 2: Pre-filled data from Admin (studentProfile) if input is empty
            else if (window.studentProfile) {
                if (input.name === 'cedula' && window.studentProfile.ci) input.value = window.studentProfile.ci;
                if (input.name === 'nombres' && window.studentProfile.nombres) input.value = window.studentProfile.nombres;
                if (input.name === 'apellidos' && window.studentProfile.apellidos) input.value = window.studentProfile.apellidos;
            }
        });
    };

    /**
     * Función principal de carga: Obtiene el HTML y JS de cada paso de forma asíncrona
     */
    async function loadStep(stepNumber) {
        // asegurar un step válido
        if (typeof stepNumber === 'undefined' || stepNumber === null) {
            stepNumber = Number(currentStep) || 1;
        }
        stepNumber = Number(stepNumber) || 1;
        currentStep = stepNumber; 
        
        const folder = folderMap[stepNumber];
        if (!folder) {
            console.error('loadStep: paso inválido', stepNumber);
            return;
        }

        console.log(`[Flow] Cargando paso ${stepNumber}: ${folder}`);

        // Actualizar el paso máximo alcanzado
        if (stepNumber > maxStepReached) {
            maxStepReached = stepNumber;
        }

        // Mostrar estado de carga
        if(viewPort) {
            viewPort.classList.add('loading');
            // Proporcionar feedback visual inmediato si tarda
            if (viewPort.innerHTML.trim() === "") {
                viewPort.innerHTML = '<div style="text-align:center; padding:50px; color:#666;">Cargando paso...</div>';
            }
        }
        
        // Codificar el nombre de la carpeta para manejar espacios correctamente en el servidor
        const folderEncoded = encodeURIComponent(folder).replace(/%20/g, ' '); // Mantener espacios como espacios o codificar? 
        // Mejor usar encodeURI que es para URLs completas y maneja espacios como %20
        // Usar encodeURIComponent para cada segmento de la ruta, pero permitiendo slashes si es necesario?
        // En este caso, folder es el segmento variable.
        const htmlPath = `Paginas/${folder}/view.php`; 
        const scriptPath = `Paginas/${folder}/script.js`;

        try {
            console.log(`[Fetch] Iniciando petición a: ${htmlPath}`);
            // 1. Cargar HTML
            const response = await fetch(htmlPath);
            if (!response.ok) {
                throw new Error(`Error ${response.status}: No se pudo acceder a la carpeta "${folder}". Verifique la ruta.`);
            }
            const html = await response.text();
            
            // Verificar si el HTML está vacío
            if (!html || html.trim().length === 0) {
                throw new Error("El sistema devolvió una página vacía. Verifique los archivos PHP del paso.");
            }

            // Limpiar marcador de posición e inyectar
            viewPort.innerHTML = html;

            // Limpiar footers internos
            viewPort.querySelectorAll('footer.site-footer').forEach(f => f.remove());

            // 2. Restaurar datos y UI
            window.restoreDataGlobal();
            if(typeof updateMenuState === 'function') updateMenuState(stepNumber);

            // 3. Cargar Script
            const oldScript = document.getElementById('step-script');
            if (oldScript) oldScript.remove();

            const script = document.createElement('script');
            script.id = 'step-script';
            script.src = `${scriptPath}?v=${Date.now()}`;
            
            script.onload = () => {
                console.log(`[Flow] Script cargado para paso ${stepNumber}`);
                const initFuncs = {
                    1: 'initIdentificacion',
                    5: 'initMaterias',
                    6: 'initRecord',
                    7: 'initFamiliares',
                    9: 'renderResumen',
                    10: 'renderFinalStatus'
                };
                const funcName = initFuncs[stepNumber];
                if (funcName && typeof window[funcName] === 'function') {
                    window[funcName]();
                }
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
     * Gestiona la visibilidad y estados de los elementos de navegación
     */
    function actualizarInterfaz(step) {
        // Actualiza el ancho de la barra de progreso proporcionalmente
        if (progressBar) progressBar.style.width = `${(step / totalSteps) * 100}%`;
        
        // Oculta "Atrás" en la primera y última página
        const prevBtn = document.getElementById('prevBtn');
        if (prevBtn) prevBtn.style.display = (step <= 1 || step >= 10) ? 'none' : 'inline-block';
        
        // Cambia el texto del botón principal en el paso de verificación y oculta en el final
        const nextBtn = document.getElementById('nextBtn');
        if (nextBtn) {
            nextBtn.textContent = (step === 9) ? "Confirmar" : "Siguiente";
            nextBtn.style.display = (step >= 10) ? 'none' : 'inline-block';
        }
        
        // El botón de limpiar solo es visible en los pasos de captura de datos (1 al 8)
        btnLimpiar.style.display = (step > 0 && step <= 8) ? 'block' : 'none';
    }

    /**
     * Manejador del botón "Siguiente" / "Confirmar"
     */
    const nextBtnEl = document.getElementById('nextBtn');
    if (nextBtnEl) {
        nextBtnEl.onclick = async () => {
            // Ejecución de validaciones externas si están presentes en los scripts de cada paso
            if (currentStep === 1 && typeof validarPaso1 === 'function') {
                if (!validarPaso1()) return; // Detiene el avance si falla la validación
            }

            // Lógica de negocio: Bloqueo de beca si el usuario trabaja (Paso 3)
            if (currentStep === 3) {
                alert("⚠️ No podrás solicitar la beca, puesto que al poseer un trabajo no cumples con los requisitos.");
                return;
            }

            if (currentStep === 6 && typeof validarRecord === 'function') {
                if (!validarRecord()) return;
            }

            // Persiste los datos antes de cambiar de vista
            window.saveCurrentData();

            // Si estamos en el paso 9 (verificación) se envían los datos al servidor
            if (currentStep === 9) {
                try {
                    const res = await fetch('submit.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify(window.formDataStorage)
                    });
                    const obj = await res.json();
                    if (obj.status !== 'ok') {
                        alert('Error enviando datos: ' + (obj.error||'')); 
                        return;
                    }
                    window.formSubmitted = true; // El envío fue exitoso
                } catch(e) {
                    alert('Error de red al enviar los datos');
                    console.error(e);
                    return;
                }
            }

            // Lógica de "Salto" (Skipping): Si en el paso 2 indica que NO trabaja, se salta el paso 3 (Laboral)
            if (currentStep === 2 && !window.formDataStorage['trabaja']) {
                currentStep = 4;
            } else {
                currentStep++;
            }
            
            loadStep(currentStep);
        };
    }

    /**
     * Manejador del botón "Atrás"
     */
    const prevBtnEl = document.getElementById('prevBtn');
    if (prevBtnEl) {
        prevBtnEl.onclick = () => {
            window.saveCurrentData();

            // Lógica inversa del salto: Si retrocede desde el paso 4 y no trabaja, vuelve al paso 2
            if (currentStep === 4 && !window.formDataStorage['trabaja']) {
                currentStep = 2;
            } else {
                currentStep--;
            }

            loadStep(currentStep);
        };
    }

    // función auxiliar para ajustar menús según el paso actual
    function updateMenuState(step) {
        const links = document.querySelectorAll('#slideMenuSPA a[data-step]');
        links.forEach(a => {
            const s = parseInt(a.getAttribute('data-step'), 10);
            a.classList.remove('active','disabled');
            if(s === step) {
                a.classList.add('active');
            }
            if(s > maxStepReached) {
                a.classList.add('disabled');
            }
        });
    }

    // Ejecución inicial para mostrar el paso 1 al cargar el sitio
    // Exponer la función para que el menú desplegable en las vistas pueda invocarla
    window.loadStep = loadStep;
    loadStep(currentStep);
});