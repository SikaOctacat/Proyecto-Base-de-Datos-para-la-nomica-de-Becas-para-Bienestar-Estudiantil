<div class="step-content">
    <h2>7. Revisión y Corrección Final</h2>
    <p style="margin-bottom: 20px;">Esta es una vista completa de tus datos. Puedes modificar cualquier campo directamente aquí si necesitas corregir algo antes de enviar.</p>
    
    <div id="panel-edicion-global" class="edicion-container">
        </div>
</div>

<style>
    .edicion-container {
        background: rgba(255, 255, 255, 0.9);
        border-radius: 12px;
        padding: 25px;
        border: 1px solid #ddd;
        max-height: 70vh; /* Slide enorme con scroll interno */
        overflow-y: auto;
    }
    
    .seccion-revision {
        margin-bottom: 30px;
        border-bottom: 2px solid var(--primary);
        padding-bottom: 15px;
    }
    
    .seccion-revision h3 {
        color: var(--primary-dark);
        font-size: 1.1rem;
        margin-bottom: 15px;
        text-transform: uppercase;
    }

    /* Estilos para los inputs dentro del resumen */
    .input-revision-group {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 10px;
        align-items: center;
        margin-bottom: 8px;
    }

    .input-revision-group label {
        margin-bottom: 0;
        font-size: 0.8rem;
        color: #666;
    }

    .input-revision {
        padding: 6px 10px !important;
        font-size: 0.9rem !important;
        border: 1px solid #eee !important;
        background: #fff !important;
    }

    .input-revision:focus {
        border-color: var(--primary) !important;
        background: #fff8f4 !important;
    }
</style>