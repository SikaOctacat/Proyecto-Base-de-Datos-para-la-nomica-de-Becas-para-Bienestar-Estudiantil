/**
 * FUNCIONES DEL PASO 8 (Pantalla Final)
 * Reflejando el éxito del envío al sistema de la UPTAG.
 */
window.renderFinalStatus = function() {
    const container = document.getElementById('status-container');
    if (!container) return;

    if (window.formSubmitted) {
        container.innerHTML = `
            <div class="success-icon">✓</div>
            <h2 style="font-size: 1.8rem; margin-top: 20px; color: #2c3e50;">¡Envío Exitoso!</h2>
            <p style="font-size: 1.1rem; color: #555;">
                Sus datos han sido procesados y enviados correctamente al <strong>sistema de la UPTAG</strong>.
            </p>
            
            <div style="background: #fff3e0; padding: 20px; border-radius: 12px; border: 1px solid #ffe0b2; color: #e65100; margin: 30px 0; line-height: 1.6;">
                <i class="fas fa-info-circle"></i> 
                Si desea consultar el estatus de su solicitud para verificar si fue seleccionado para el beneficio, 
                ingrese al portal con el usuario y clave asignados por la administración.
            </div>

            <div style="display: flex; gap: 15px; justify-content: center; margin-top: 30px; flex-wrap: wrap;">
                <button type="button" class="btn-next" onclick="location.href='index.php'" 
                    style="background: #FF6600; border: none; padding: 14px 30px; border-radius: 10px; color: white; cursor: pointer; font-weight: bold; transition: background 0.3s;">
                    <i class="fas fa-home"></i> Volver al Inicio
                </button>
                <button type="button" class="btn-next" onclick="location.href='logout.php'" 
                    style="background: #2c3e50; border: none; padding: 14px 30px; border-radius: 10px; color: white; cursor: pointer; font-weight: bold; transition: background 0.3s;">
                    <i class="fas fa-sign-out-alt"></i> Terminar y Salir
                </button>
            </div>
        `;
    } else {
        // Estado de "Envío Pendiente" se mantiene similar pero ajustado visualmente
        container.innerHTML = `
            <div class="success-icon" style="background: #f39c12;">!</div>
            <h2 style="font-size: 1.6rem; margin-top: 20px; color: #e67e22;">Envío Pendiente</h2>
            <p>Aún no ha confirmado el envío de su solicitud en el sistema.</p>
            <p style="background: #f8f9fa; padding: 15px; border-radius: 12px; border: 1px solid #ddd; color: #666; margin: 20px 0;">
                Para finalizar el proceso en el <strong>sistema de la UPTAG</strong>, por favor regrese al paso de <strong>Revisión</strong> y confirme sus datos.
            </p>
            <button type="button" class="btn-next" onclick="if(window.loadStep) window.loadStep(7);" 
                style="margin-top: 10px; background: #FF6600; border: none; padding: 12px 25px; border-radius: 8px; color: white; cursor: pointer; font-weight: bold;">
                Regresar a Revisión
            </button>
        `;
    }
};