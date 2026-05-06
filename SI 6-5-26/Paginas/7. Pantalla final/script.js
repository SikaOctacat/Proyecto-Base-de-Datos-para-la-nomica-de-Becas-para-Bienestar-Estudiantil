window.renderFinalStatus = function() {
    const container = document.getElementById('status-container');
    if (!container) return;

    // window.formSubmitted = true; // Forzado para pruebas

    // 1. Detectar el rol (Prioridad: Variable global -> Atributo Body -> Por defecto estudiante)
    const userRol = window.userRol || document.body.getAttribute('data-rol') || 'estudiante'; 
    
    // 2. Definir la URL de destino según la lógica del código anterior
    // Si es admin va a 'admin/index.php', de lo contrario a 'index.php'
    const urlDestino = (userRol === 'admin') ? 'admin/index.php' : 'index.php?completado=1';

    console.log("Rol detectado:", userRol, "Redirigiendo a:", urlDestino);

    if (window.formSubmitted) {
        container.innerHTML = `
            <div class="success-icon">✓</div>
            <h2 style="font-size: 1.8rem; margin-top: 20px; color: #2c3e50;">¡Envío Exitoso!</h2>
            <p style="font-size: 1.1rem; color: #555;">
                Sus datos han sido procesados y enviados correctamente al <strong>sistema de la UPTAG</strong>.
            </p>
            
            <div style="background: #f1f8e9; padding: 20px; border-radius: 12px; border: 1px solid #c8e6c9; color: #2e7d32; margin: 30px 0; line-height: 1.6;">
                <i class="fas fa-check-circle"></i> 
                <strong>Registro Completado:</strong> 
                ${userRol === 'admin' 
                    ? 'El registro del estudiante ha sido almacenado correctamente en la base de datos administrativa.' 
                    : 'Ya puede cerrar esta ventana o volver al inicio para consultar su estatus.'}
            </div>

            <div style="display: flex; justify-content: center; margin-top: 30px;">
                <button type="button" id="btnFinalizarTodo" 
                    style="background: #FF6600; border: none; padding: 16px 40px; border-radius: 10px; color: white; cursor: pointer; font-weight: bold; transition: all 0.3s; box-shadow: 0 4px 15px rgba(255,102,0,0.2); font-size: 1.1rem; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-home"></i> 
                    ${userRol === 'admin' ? 'Volver al Panel Admin' : 'Finalizar y Volver al Inicio'}
                </button>
            </div>
        `;

        const btnFinal = document.getElementById('btnFinalizarTodo');
        if (btnFinal) {
            btnFinal.onclick = function() {
                // Aplicamos la redirección calculada
                window.location.href = urlDestino;
            };
        }
    }
};