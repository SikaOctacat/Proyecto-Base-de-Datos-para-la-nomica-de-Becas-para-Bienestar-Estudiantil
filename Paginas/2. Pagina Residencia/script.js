/**
 * Lógica para el Paso 2: Residencia y Vivienda
 * Maneja la visibilidad de campos y validaciones de redundancia.
 */
function initResidencia() {
    const tRes = document.getElementById('t_res');
    const rProp = document.getElementById('r_prop');
    const seccionProcedencia = document.getElementById('seccion_procedencia');

    if (!tRes || !rProp) return; // Seguridad por si los elementos no cargaron

    const gestionarDinamismo = () => {
        const tipoResidencia = tRes.value;
        const regimenPropiedad = rProp.value;

        // 1. Lógica de Redundancia: Residencia Universitaria
        if (tipoResidencia === 'universitaria') {
            rProp.value = 'cedida'; // Se asume cedida por la institución
            rProp.disabled = true;
            rProp.classList.add('input-disabled'); // Opcional: clase CSS para estilo visual
        } else {
            rProp.disabled = false;
            rProp.classList.remove('input-disabled');
        }

        // 2. Lógica de Doble Dirección (Requerimiento de Tutor)
        // Se muestra procedencia si: está alquilado O si no vive con la familia (procedente de otra ciudad)
        const necesitaProcedencia = (regimenPropiedad === 'alquilada' || (tipoResidencia !== 'familiar' && tipoResidencia !== ""));
        
        if (necesitaProcedencia) {
            seccionProcedencia.style.display = 'block';
            // Hacer que los inputs internos sean requeridos si están visibles
            seccionProcedencia.querySelectorAll('input').forEach(i => i.required = true);
        } else {
            seccionProcedencia.style.display = 'none';
            seccionProcedencia.querySelectorAll('input').forEach(i => {
                i.required = false;
                i.value = ''; // Limpiar si se oculta
            });
        }
    };

    // Escuchar cambios
    tRes.addEventListener('change', gestionarDinamismo);
    rProp.addEventListener('change', gestionarDinamismo);

    // Ejecutar una vez al cargar por si hay datos restaurados por restoreDataGlobal
    gestionarDinamismo();
}