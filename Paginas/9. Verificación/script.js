/**
 * Renderiza el resumen final (Paso 9)
 */
function renderResumen() {
    const container = document.getElementById('resumen');
    if (!container) return;

    const data = window.formDataStorage;
    const getVal = (key) => data[key] || '<span style="color:#999">No indicado</span>';
    const getCheck = (key) => data[key] ? 'Sí' : 'No';

    // 1. Datos Personales
    let html = `
        <div style="margin-bottom: 25px; border-bottom: 2px solid #eee; padding-bottom: 10px;">
            <h3 style="color: #0056b3; margin-bottom: 10px;">I. Identificación</h3>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; font-size: 0.95rem;">
                <p><strong>Nombres:</strong> ${getVal('nombres')} ${getVal('apellidos')}</p>
                <p><strong>Cédula:</strong> ${getVal('cedula')}</p>
                <p><strong>Edad:</strong> ${getVal('edad')} años</p>
                <p><strong>Estado Civil:</strong> ${getVal('edo_civil')}</p>
                <p><strong>Teléfono:</strong> ${getVal('tel_estudiante')}</p>
                <p><strong>Trabaja:</strong> ${getCheck('trabaja')}</p>
            </div>
        </div>
    `;

    // 2. Datos Académicos y Materias
    const materias = Object.keys(data)
        .filter(k => k.startsWith('mat_') && data[k] === true)
        .map(k => k.replace('mat_', '').replace('manual_', '').replace(/_/g, ' ').toUpperCase());

    html += `
        <div style="margin-bottom: 25px; border-bottom: 2px solid #eee; padding-bottom: 10px;">
            <h3 style="color: #0056b3; margin-bottom: 10px;">II. Información Académica</h3>
            <p><strong>PNF:</strong> ${getVal('pnf')}</p>
            <p><strong>Total Materias:</strong> ${data['m_ins'] || 0}</p>
            <p style="font-size: 0.85rem; background: #f8f9fa; padding: 10px; border-radius: 4px; margin-top: 5px;">
                <strong>Lista:</strong> ${materias.length > 0 ? materias.join(', ') : 'Ninguna seleccionada'}
            </p>
        </div>
    `;

    // 3. Tabla de Familiares
    const idsFam = [...new Set(
        Object.keys(data)
            .filter(key => key.startsWith('f_nom_'))
            .map(key => key.split('_')[2])
    )];

    html += `
        <div>
            <h3 style="color: #0056b3; margin-bottom: 10px;">III. Carga Familiar</h3>
            <table style="width:100%; border-collapse: collapse; font-size: 0.85rem; text-align: left;">
                <thead>
                    <tr style="background: #f4f4f4;">
                        <th style="border: 1px solid #ddd; padding: 8px;">Nombre</th>
                        <th style="border: 1px solid #ddd; padding: 8px;">Parentesco</th>
                        <th style="border: 1px solid #ddd; padding: 8px;">Ingreso</th>
                    </tr>
                </thead>
                <tbody>`;

    if (idsFam.length > 0) {
        idsFam.forEach(id => {
            html += `
                <tr>
                    <td style="border: 1px solid #ddd; padding: 8px;">${data['f_nom_' + id]}</td>
                    <td style="border: 1px solid #ddd; padding: 8px;">${data['f_par_' + id]}</td>
                    <td style="border: 1px solid #ddd; padding: 8px;">${data['f_ing_' + id] || 0} Bs</td>
                </tr>`;
        });
    } else {
        html += `<tr><td colspan="3" style="text-align:center; padding:15px; border: 1px solid #ddd;">No se registraron familiares.</td></tr>`;
    }

    html += `</tbody></table></div>`;
    container.innerHTML = html;
}

function resetFormulario() {
    if (confirm("¿Seguro que deseas borrar todo y empezar de nuevo?")) {
        window.formDataStorage = {};
        location.reload();
    }
}