/**
 * Renderiza el resumen final (Paso 9)
 * Esta función extrae todos los datos acumulados en window.formDataStorage y 
 * genera un reporte HTML dinámico para que el usuario revise su información.
 */
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

    // Diccionario local de colores para pintar las filas estáticas del resumen
    const coloresResumen = {
        'primaria':   { separador: '#c6f6d5', texto: '#22543d', filas: '#f0fff4' },
        'secundaria': { separador: '#feebc8', texto: '#744210', filas: '#fffaf0' },
        'otros':      { separador: '#edf2f7', texto: '#2d3748', filas: '#f7fafc' }
    };

    let html = `<div class="resumen-texto-container">`;

    // --- SECCIÓN 1: IDENTIFICACIÓN PERSONAL ---
    html += `
    <div class="resumen-seccion">
        <h3>1. Identificación del Estudiante</h3>
        <div class="dato-fila"><span class="dato-label">Nombre completo:</span> <span class="dato-valor">${getV('nombre1')} ${data['nombre2'] || ''} ${getV('apellido_paterno')} ${getV('apellido_materno')}</span></div>
        <div class="dato-fila"><span class="dato-label">Cédula:</span> <span class="dato-valor">${getV('cedula')}</span></div>
        <div class="dato-fila"><span class="dato-label">Código de Estudiante:</span> <span class="dato-valor">${getV('cod_est')}</span></div>
        <div class="dato-fila"><span class="dato-label">Fecha de nacimiento: / Edad:</span> <span class="dato-valor">${getV('f_nac')} (${getV('edad')} años)</span></div>
        <div class="dato-fila"><span class="dato-label">Teléfono:</span> <span class="dato-valor">${getV('tel_estudiante')}</span></div>
        <div class="dato-fila"><span class="dato-label">Correo:</span> <span class="dato-valor">${getV('correo')}</span></div>
        <div class="dato-fila"><span class="dato-label">Estado Civil:</span> <span class="dato-valor">${getV('edo_civil')}</span></div>
        <div class="dato-fila"><span class="dato-label">Beneficio solicitado:</span> <span class="dato-valor">${getV('tipo_beneficio')}</span></div>
        <div class="dato-fila"><span class="dato-label">Carnet de la Patria:</span> <span class="dato-valor">${getV('C_Patria')}</span></div>
        <div class="dato-fila"><span class="dato-label">¿Viaja frecuentemente?:</span> <span class="dato-valor">${getRadio('viaja')}</span></div>
        <div class="dato-fila"><span class="dato-label">Estatus de estudio:</span> <span class="dato-valor">${getRadio('estatus_estudio')}</span></div>
    </div>`;

    // --- SECCIÓN 2: RESIDENCIA Y VIVIENDA (ACTUALIZADA) ---
    html += `
    <div class="resumen-seccion">
        <h3>2. Residencia / Vivienda</h3>
        <div class="dato-fila"><span class="dato-label">Tipo de Residencia:</span> <span class="dato-valor">${getV('t_res')}</span></div>
        <div class="dato-fila"><span class="dato-label">Tipo de Vivienda:</span> <span class="dato-valor">${getV('t_viv')}</span></div>
        <div class="dato-fila"><span class="dato-label">Localidad / Propiedad:</span> <span class="dato-valor">${getV('t_loc')} / ${getV('r_prop')}</span></div>
        <div class="dato-fila"><span class="dato-label">Estado/Municipio:</span> <span class="dato-valor">${getV('estado_res')}, ${getV('municipio_res')}</span></div>
        <div class="dato-fila"><span class="dato-label">Teléfono Local:</span> <span class="dato-valor">${getV('tel_local')}</span></div>
        <div class="dato-fila"><span class="dato-label">Dirección Local Actual:</span> <span class="dato-valor">${getV('dir_local')}</span></div>
        <div class="dato-fila"><span class="dato-label">Dirección de Procedencia:</span> <span class="dato-valor">${getV('dir_procedencia')}</span></div>
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

    // --- SECCIÓN 4: CARGA FAMILIAR CON SOPORTE DE SEPARADORES ---
    html += `
    <div class="resumen-seccion">
        <h3 style="margin-bottom: 10px;">4. Grupo Familiar Conviviente</h3>`;

    if (data['no_familiares'] === true || data['no_familiares'] === "on") {
        html += `
        <div style="padding: 12px; background: #fff5e6; border: 1px solid #ffe0b3; border-radius: 6px; color: #856404; font-style: italic;">
            📢 <strong>Declaración:</strong> El estudiante ha declarado que no convive con familiares (Vive solo).
        </div>`;
    } else {
        const estructuraTable = data['estructura_tabla'];

        // Si tenemos un mapa de estructura dinámico guardado
        if (estructuraTable && estructuraTable.length > 0) {
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
            
            let grupoActual = null;

            estructuraTable.forEach(item => {
                const [tipo, identificador] = item.split(':');

                if (tipo === 'separador') {
                    grupoActual = identificador;
                    const config = coloresResumen[grupoActual] || { separador: '#edf2f7', texto: '#2d3748' };
                    
                    let tituloGrupo = grupoActual.toUpperCase();
                    if (grupoActual === 'primaria') tituloGrupo = 'FAMILIA PRIMARIA';
                    if (grupoActual === 'secundaria') tituloGrupo = 'CARGA SECUNDARIA';
                    if (grupoActual === 'otros') tituloGrupo = 'OTROS PARIENTES';

                    html += `
                        <tr style="background-color: ${config.separador}; user-select: none;">
                            <td colspan="6" style="padding: 8px 12px; color: ${config.texto}; font-weight: bold; font-size: 0.8rem; letter-spacing: 0.5px; border-bottom: 1px solid rgba(0,0,0,0.05);">
                                ☰ &nbsp; ${tituloGrupo}
                            </td>
                        </tr>`;

                } else if (tipo === 'fila') {
                    const id = identificador;
                    const ingreso = parseFloat(data['f_ing_' + id]) || 0;
                    const fondoFila = (grupoActual && coloresResumen[grupoActual]) ? coloresResumen[grupoActual].filas : '#ffffff';

                    html += `
                        <tr style="border-bottom: 1px solid #f0f0f0; background-color: ${fondoFila};">
                            <td style="padding: 10px;">${data['f_nom_' + id] || ''} ${data['f_ape_' + id] || ''}</td>
                            <td style="padding: 10px;">${data['f_par_' + id] || '---'}</td>
                            <td style="padding: 10px; text-align: center;">${data['f_eda_' + id] || '--'}</td>
                            <td style="padding: 10px;">${data['f_ins_' + id] || '---'}</td>
                            <td style="padding: 10px;">${data['f_ocu_' + id] || '---'}</td>
                            <td style="padding: 10px; text-align:right; font-weight: 600;">${ingreso.toLocaleString('es-VE', {minimumFractionDigits: 2})} Bs</td>
                        </tr>`;
                }
            });

            html += `
                    </tbody>
                </table>
            </div>`;
        } else {
            // Fallback de contingencia en caso de que la estructura esté limpia pero existan llaves sueltas
            const idsFam = [...new Set(Object.keys(data).filter(key => key.startsWith('f_nom_')).map(key => key.split('_')[2]))];
            if (idsFam.length > 0) {
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
                    const ingreso = parseFloat(data['f_ing_' + id]) || 0;
                    html += `
                        <tr style="border-bottom: 1px solid #f0f0f0;">
                            <td style="padding: 10px;">${data['f_nom_' + id]} ${data['f_ape_' + id]}</td>
                            <td style="padding: 10px;">${data['f_par_' + id] || '---'}</td>
                            <td style="padding: 10px; text-align: center;">${data['f_eda_' + id] || '--'}</td>
                            <td style="padding: 10px;">${data['f_ins_' + id] || '---'}</td>
                            <td style="padding: 10px;">${data['f_ocu_' + id] || '---'}</td>
                            <td style="padding: 10px; text-align:right; font-weight: 600;">${ingreso.toLocaleString('es-VE', {minimumFractionDigits: 2})} Bs</td>
                        </tr>`;
                });

                html += `</tbody></table></div>`;
            } else {
                html += `
                <div style="padding: 10px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 6px; color: #721c24;">
                    ⚠️ No se detectaron familiares registrados en el sistema.
                </div>`;
            }
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
    
    container.innerHTML = html;
}