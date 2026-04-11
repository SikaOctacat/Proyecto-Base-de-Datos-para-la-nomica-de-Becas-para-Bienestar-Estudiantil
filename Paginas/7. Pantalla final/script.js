window.renderFinalStatus = function() {
    const container = document.getElementById('status-container');
    if (!container) return;

    // Esto hace que te de exito aunque no hayas llenado nada, no actualiza la base datos al hacelor(Borrar esto después de probar)
    // window.formSubmitted = true;

    if (window.formSubmitted) {
        container.innerHTML = `
            <div class="success-icon">✓</div>
            <h2 style="font-size: 1.8rem; margin-top: 20px; color: #2c3e50;">¡Envío Exitoso!</h2>
            <p style="font-size: 1.1rem; color: #555;">
                Sus datos han sido procesados y enviados correctamente al <strong>sistema de la UPTAG</strong>.
            </p>
            
            <div style="background: #f1f8e9; padding: 20px; border-radius: 12px; border: 1px solid #c8e6c9; color: #2e7d32; margin: 30px 0; line-height: 1.6;">
                <i class="fas fa-check-circle"></i> 
                <strong>Registro Completado:</strong> Ya puede cerrar esta ventana o volver al inicio. 
                Si desea consultar el estatus de su solicitud más adelante, ingrese al portal con sus credenciales.
            </div>

            <div style="display: flex; justify-content: center; margin-top: 30px;">
                <button type="button" id="btnFinalizarTodo" 
                    style="background: #FF6600; border: none; padding: 16px 40px; border-radius: 10px; color: white; cursor: pointer; font-weight: bold; transition: all 0.3s; box-shadow: 0 4px 15px rgba(255,102,0,0.2); font-size: 1.1rem; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-home"></i> Finalizar y Volver al Inicio
                </button>
            </div>
        `;

        // Asignamos la lógica al botón recién creado
        const btnFinal = document.getElementById('btnFinalizarTodo');
        if (btnFinal) {
            btnFinal.onclick = () => {
                // Como window.formSubmitted es true, la verificación del main.js 
                // permitirá la salida sin alertas de error.
                location.href = 'index.php?completado=1';
            };
        }
        
    } else {
        // Estado de "Envío Pendiente" ajustado para no confundir al usuario
        container.innerHTML = `
            <div class="success-icon" style="background: #f39c12;">!</div>
            <h2 style="font-size: 1.6rem; margin-top: 20px; color: #e67e22;">Envío Pendiente</h2>
            <p>Aún no ha confirmado el envío de su solicitud en el sistema.</p>
            <p style="background: #f8f9fa; padding: 15px; border-radius: 12px; border: 1px solid #ddd; color: #666; margin: 20px 0;">
                Para finalizar el proceso en el <strong>sistema de la UPTAG</strong>, por favor regrese al paso de <strong>Verificación</strong> y pulse el botón "Confirmar".
            </p>
            <button type="button" class="btn-next" onclick="if(window.loadStep) window.loadStep(7);" 
                style="margin-top: 10px; background: #FF6600; border: none; padding: 12px 25px; border-radius: 8px; color: white; cursor: pointer; font-weight: bold;">
                Regresar a Verificación
            </button>
        `;
    }
};