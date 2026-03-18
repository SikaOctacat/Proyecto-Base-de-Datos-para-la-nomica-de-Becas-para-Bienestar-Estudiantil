/**
 * FUNCIONES DEL PASO 10 (Pantalla Final)
 */
window.renderFinalStatus = function() {
    const container = document.getElementById('status-container');
    if (!container) return;

    if (window.formSubmitted) {
        container.innerHTML = `
            <div class="success-icon">✓</div>
            <h2 style="font-size: 1.5rem; margin-top: 20px;">¡Envío Exitoso!</h2>
            <p>Los datos han sido enviados exitosamente al sistema de UPTAG.</p>
            <p style="background: #fff3e0; padding: 15px; border-radius: 12px; border: 1px solid #ffe0b2; color: #e65100; font-weight: 600; margin: 20px 0;">
                Si quiere consultar sus datos para ver si fue elegido o no para la beca, ingrese con el usuario y clave que le asignó el administrador.
            </p>
            <div style="display: flex; gap: 10px; justify-content: center; margin-top: 20px; flex-wrap: wrap;">
                <button type="button" class="btn-next" onclick="location.href='index.php'" style="background: #FF6600; border: none; padding: 12px 25px; border-radius: 10px; color: white; cursor: pointer; font-weight: bold;">
                    Volver al Inicio
                </button>
                <button type="button" class="btn-next" onclick="location.href='logout.php'" style="background: #2c3e50; border: none; padding: 12px 25px; border-radius: 10px; color: white; cursor: pointer; font-weight: bold;">
                    Terminar y Salir
                </button>
            </div>
        `;
    } else {
        container.innerHTML = `
            <div class="success-icon" style="background: #f39c12;">!</div>
            <h2 style="font-size: 1.5rem; margin-top: 20px; color: #e67e22;">Envío Pendiente</h2>
            <p>Aún no has confirmado el envío de tus datos en el paso anterior.</p>
            <p style="background: #f8f9fa; padding: 15px; border-radius: 12px; border: 1px solid #ddd; color: #666; margin: 20px 0;">
                Para finalizar el proceso, por favor dirígete al paso de <strong>Verificación</strong> y haz clic en <strong>Confirmar</strong>.
            </p>
            <button type="button" class="btn-next" onclick="if(window.loadStep) window.loadStep(9);" style="margin-top: 10px; background: #FF6600; border: none; padding: 10px 20px; border-radius: 8px; color: white; cursor: pointer;">
                Ir a Verificación
            </button>
        `;
    }
};
