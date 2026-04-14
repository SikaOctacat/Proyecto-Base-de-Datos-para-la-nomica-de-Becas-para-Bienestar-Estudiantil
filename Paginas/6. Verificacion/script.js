/**
 * Renderiza el resumen final (Paso 9)
 * Esta función extrae todos los datos acumulados en window.formDataStorage y 
 * genera un reporte HTML dinámico para que el usuario revise su información.
 */
// Función global de sincronización para el Slide de Edición

function renderResumen() {
    const container = document.getElementById('panel-edicion-global');
    if (!container) return;

    const data = window.formDataStorage;
    
    // Helper para valores faltantes con estilo de alerta
    const getV = (key) => data[key] || '<span style="color:#d9534f; font-weight:bold;">No suministrado</span>';
    
    // Helper para campos de radio/si-no
    const getRadio = (key) => {
        if (!data[key]) return '<span style="color:#d9534f">No seleccionado</span>';
        return data[key].charAt(0).toUpperCase() + data[key].slice(1);
    };

    let html = `<div class="resumen-texto-container">`;

    // --- SECCIÓN 1: IDENTIFICACIÓN PERSONAL ---
    html += `
    <div class="resumen-seccion">
        <h3>1. Identificación del Estudiante</h3>
        <div class="dato-fila"><span class="dato-label">Nombre Completo:</span> <span class="dato-valor">${getV('nombre1')} ${data['nombre2'] || ''} ${getV('apellido_paterno')} ${getV('apellido_materno')}</span></div>
        <div class="dato-fila"><span class="dato-label">Cédula / Código:</span> <span class="dato-valor">${getV('cedula')} / ${getV('cod_est')}</span></div>
        <div class="dato-fila"><span class="dato-label">Fecha Nac. / Edad:</span> <span class="dato-valor">${getV('f_nac')} (${getV('edad')} años)</span></div>
        <div class="dato-fila"><span class="dato-label">Teléfono:</span> <span class="dato-valor">${getV('tel_estudiante')}</span></div>
        <div class="dato-fila"><span class="dato-label">Correo:</span> <span class="dato-valor">${getV('correo')}</span></div>
        <div class="dato-fila"><span class="dato-label">Estado Civil:</span> <span class="dato-valor">${getV('edo_civil')}</span></div>
        <div class="dato-fila"><span class="dato-label">Beneficio solicitado:</span> <span class="dato-valor">${getV('tipo_beneficio')}</span></div>
        <div class="dato-fila"><span class="dato-label">Carnet de la Patria:</span> <span class="dato-valor">${getV('C_Patria')}</span></div>
        <div class="dato-fila"><span class="dato-label">¿Viaja frecuentemente?:</span> <span class="dato-valor">${getRadio('viaja')}</span></div>
        <div class="dato-fila"><span class="dato-label">Estatus de estudio:</span> <span class="dato-valor">${getRadio('estatus_estudio')}</span></div>
    </div>`;

    // --- SECCIÓN 2: RESIDENCIA Y VIVIENDA ---
    html += `
    <div class="resumen-seccion">
        <h3>2. Residencia / Vivienda</h3>
        <div class="dato-fila"><span class="dato-label">Tipo de Residencia:</span> <span class="dato-valor">${getV('t_res')}</span></div>
        <div class="dato-fila"><span class="dato-label">Tipo de Vivienda:</span> <span class="dato-valor">${getV('t_viv')}</span></div>
        <div class="dato-fila"><span class="dato-label">Localidad / Propiedad:</span> <span class="dato-valor">${getV('t_loc')} / ${getV('r_prop')}</span></div>
        <div class="dato-fila"><span class="dato-label">Estado/Municipio:</span> <span class="dato-valor">${getV('estado_res')}, ${getV('municipio_res')}</span></div>
        <div class="dato-fila"><span class="dato-label">Dirección Local:</span> <span class="dato-valor">${getV('dir_local')}</span></div>
        <div class="dato-fila"><span class="dato-label">Teléfono Local:</span> <span class="dato-valor">${getV('tel_local')}</span></div>
    </div>`;

    // --- SECCIÓN 3: INFORMACIÓN ACADÉMICA (PNF) ---
    html += `
    <div class="resumen-seccion">
        <h3>3. Información del PNF</h3>
        <div class="dato-fila"><span class="dato-label">Carrera (PNF):</span> <span class="dato-valor" style="text-transform: capitalize;">${getV('carrera')}</span></div>
        <div class="dato-fila"><span class="dato-label">Fecha de Ingreso:</span> <span class="dato-valor">${getV('f_ingreso')}</span></div>
        <div class="dato-fila"><span class="dato-label">Período actual:</span> <span class="dato-valor">Trayecto ${getV('trayecto')} - Trimestre ${getV('trimestre')}</span></div>
        <div class="dato-fila">
            <span class="dato-label">Índice de Rendimiento Académico (IRA):</span> 
            <span class="dato-valor">${getV('ira_anterior')} / 20</span>
        </div>
    </div>`;


    // --- SECCIÓN 4: CARGA FAMILIAR ---
    html += `
    <div class="resumen-seccion">
        <h3 style="margin-bottom: 10px;">4. Grupo Familiar Conviviente</h3>`;

    if (data['no_familiares'] === true || data['no_familiares'] === "on") {
        html += `
        <div style="padding: 12px; background: #fff5e6; border: 1px solid #ffe0b3; border-radius: 6px; color: #856404; font-style: italic;">
            📢 <strong>Declaración:</strong> El estudiante ha declarado que no convive con familiares (Vive solo).
        </div>`;
    } else {
        const idsFam = [...new Set(
            Object.keys(data)
                .filter(key => key.startsWith('f_nom_'))
                .map(key => key.split('_')[2])
        )];

        if (idsFam.length > 0) {
            // Se quitó el margin-top y se ajustó el borde del contenedor
            html += `
            <div style="overflow-x: auto; border: 1px solid #eee; border-radius: 8px; margin-top: 5px;">
                <table style="width:100%; font-size: 0.85rem; border-collapse: collapse; min-width: 600px; margin: 0;">
                    <thead>
                        <tr style="background: #f8f9fa; border-bottom: 2px solid #dee2e6; text-align: left;">
                            <th style="padding: 10px; border-top: none;">Nombre y Apellido</th>
                            <th style="padding: 10px; border-top: none;">Parentesco</th>
                            <th style="padding: 10px; border-top: none; text-align: center;">Edad</th>
                            <th style="padding: 10px; border-top: none;">Instrucción</th>
                            <th style="padding: 10px; border-top: none;">Ocupación</th>
                            <th style="padding: 10px; border-top: none; text-align:right;">Ingreso</th>
                        </tr>
                    </thead>
                    <tbody>`;
            
            idsFam.forEach(id => {
                const ingreso = parseFloat(data['f_ing_'+id]) || 0;
                html += `
                    <tr style="border-bottom: 1px solid #f0f0f0;">
                        <td style="padding: 10px;">${data['f_nom_'+id]} ${data['f_ape_'+id]}</td>
                        <td style="padding: 10px;">${data['f_par_'+id] || '---'}</td>
                        <td style="padding: 10px; text-align: center;">${data['f_eda_'+id] || '--'}</td>
                        <td style="padding: 10px;">${data['f_ins_'+id] || '---'}</td>
                        <td style="padding: 10px;">${data['f_ocu_'+id] || '---'}</td>
                        <td style="padding: 10px; text-align:right; font-weight: 600;">${ingreso.toLocaleString('es-VE', {minimumFractionDigits: 2})} Bs</td>
                    </tr>`;
            });
            
            html += `
                    </tbody>
                </table>
            </div>`;
        } else {
            html += `
            <div style="padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 6px; color: #721c24;">
                ⚠️ No se detectaron familiares registrados en el sistema.
            </div>`;
        }
    }
    html += `</div>`;

    // --- SECCIÓN 5: DATOS ADICIONALES ---
    html += `
    <div class="resumen-seccion" style="border-bottom: none; margin-bottom: 0; padding-bottom: 0;">
        <h3>5. Observaciones y Comentarios</h3>
        <div style="background: #fafafa; padding: 15px; border-left: 4px solid var(--primary); border-radius: 4px; font-size: 0.9rem; color: #444; line-height: 1.5; word-break: break-word; overflow-wrap: break-word; white-space: normal;">
            ${data['comentarios'] ? data['comentarios'] : '<i>Sin observaciones adicionales proporcionadas.</i>'}
        </div>
    </div>`;

    html += `</div>`; // Cierre de resumen-texto-container
    
    // Inyectar todo el HTML construido en el contenedor
    container.innerHTML = html;
}

// Función para mantener la lógica de tabla en el resumen
function generarTablaEditableFamiliares(data) {
    const ids = [...new Set(Object.keys(data).filter(k => k.startsWith('f_nom_')).map(k => k.split('_')[2]))];
    
    let tablaHtml = `
        <table style="width:100%; border-collapse: collapse;">
            <thead>
                <tr style="font-size:0.8rem; background:#eee;">
                    <th>Nombre</th><th>Parentesco</th><th>Ingreso</th>
                </tr>
            </thead>
            <tbody>`;
    
    ids.forEach(id => {
        tablaHtml += `
            <tr>
                <td><input type="text" value="${data['f_nom_'+id]}" oninput="syncData('f_nom_${id}', this.value)"></td>
                <td><input type="text" value="${data['f_par_'+id]}" oninput="syncData('f_par_${id}', this.value)"></td>
                <td><input type="number" value="${data['f_ing_'+id]}" oninput="syncData('f_ing_${id}', this.value)"></td>
            </tr>`;
    });
    
    tablaHtml += `</tbody></table>`;
    return tablaHtml;
}
/**
 * Función para reiniciar todo el proceso.
 * Limpia el objeto global de datos y recarga la página desde cero (Paso 1).
 */
/**
 * Función para reiniciar todo el proceso con doble advertencia.
 * Limpia el objeto global de datos, el almacenamiento del navegador y recarga.
 */
function resetFormulario() {
    // PRIMERA ADVERTENCIA
    const primeraConfirmacion = confirm(
        "⚠️ ADVERTENCIA: Estás a punto de borrar TODOS los datos ingresados en los 5 pasos.\n\n" +
        "¿Deseas continuar?"
    );

    if (primeraConfirmacion) {
        // SEGUNDA ADVERTENCIA (Doble verificación)
        const segundaConfirmacion = confirm(
            "🛑 ¡ESTA ACCIÓN NO SE PUEDE DESHACER!\n\n" +
            "Se eliminarán nombres, registros familiares, récords académicos y comentarios.\n" +
            "¿Confirmas que quieres empezar desde cero?"
        );

        if (segundaConfirmacion) {
            // 1. Vaciar el objeto en memoria
            window.formDataStorage = {};

            // 2. Limpiar persistencia (por si usas Storage para recargas accidentales)
            if (window.localStorage) localStorage.clear();
            if (window.sessionStorage) sessionStorage.clear();

            // 3. Feedback visual antes de recargar
            const container = document.getElementById('panel-edicion-global');
            if (container) {
                container.innerHTML = "<h3 style='color: #d9534f; text-align: center;'>Borrando datos...</h3>";
            }

            // 4. Recargar la página para volver al paso 1
            setTimeout(() => {
                location.reload();
            }, 800);
        }
    }
}