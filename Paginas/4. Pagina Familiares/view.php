<?php if(session_status()==PHP_SESSION_NONE) session_start(); ?>
<footer class="site-footer">
    <div style="max-width:900px;margin:0 auto;padding:12px;color:#666;text-align:center;">
        <small>UPTAG - Sistema de Solicitudes · &copy; <?php echo date('Y'); ?></small>
    </div>
</footer>
<div class="step-content">
    <h2>4. Familiares del Estudiante</h2>
    <p style="font-size: 0.8rem; color: #666; margin-bottom: 10px;">Indique las personas que conviven con usted.</p>
    
    <div style="background: rgba(255,102,0,0.05); padding: 12px; border-radius: 8px; margin-bottom: 15px; border: 1px dashed var(--primary);">
        <label class="radio-item" style="justify-content: flex-start; gap: 10px; cursor: pointer;">
            <input type="checkbox" id="no-familiares" name="no_familiares" style="width: 20px !important; height: 20px !important;">
            <span style="font-weight: 600; color: #444;">No convivo con familiares / Vivo solo</span>
        </label>
    </div>

    <div class="table-responsive">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background-color: #f4f4f4;">
                    <th>Nombre</th>
                    <th>Apellido</th>
                    <th>Parentesco</th>
                    <th style="width: 60px;">Edad</th>
                    <th>Instrucción</th>
                    <th>Ocupación</th>
                    <th>Ingreso (Bs)</th>
                </tr>
            </thead>
            <tbody id="cuerpo-tabla">
                </tbody>
        </table>
    </div>

    <div class="controles-tabla" style="margin-top: 20px; display: flex; align-items: center; gap: 12px; flex-wrap: wrap;">
        <button type="button" id="btn-agregar" class="btn-add" 
                style="background: #00dc0b; color: white; border: none; padding: 10px 18px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 0.9rem; transition: background 0.2s;">
            + Añadir Familiar
        </button>
        
        <div class="dropdown-grupo" style="position: relative; display: inline-block;">
            <button type="button" id="btn-dropdown-grupo" 
                    style="background: #2b6cb0; color: white; border: none; padding: 10px 18px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 0.9rem; display: flex; align-items: center; gap: 8px; transition: background 0.2s; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                🗂️ Asignar Grupo <span style="font-size: 0.7rem;">▼</span>
            </button>
            <div id="menu-dropdown-grupo" style="display: none; position: absolute; bottom: 125%; left: 0; background: white; min-width: 220px; box-shadow: 0px 4px 15px rgba(0,0,0,0.2); border-radius: 6px; z-index: 100; border: 1px solid #cbd5e0; padding: 4px 0;">
                <a href="#" data-valor="primaria" style="display: block; padding: 10px 14px; color: #2d3748; text-decoration: none; font-size: 0.85rem; font-weight: 600; transition: background 0.2s; border-left: 4px solid #48bb78;">👨‍👩‍👧‍👦 Familia Primaria</a>
                <a href="#" data-valor="secundaria" style="display: block; padding: 10px 14px; color: #2d3748; text-decoration: none; font-size: 0.85rem; font-weight: 600; transition: background 0.2s; border-left: 4px solid #ed8936;">🏡 Carga Secundaria</a>
                <a href="#" data-valor="otros" style="display: block; padding: 10px 14px; color: #2d3748; text-decoration: none; font-size: 0.85rem; font-weight: 600; transition: background 0.2s; border-left: 4px solid #a0aec0;">🔗 Otros Parientes</a>
            </div>
        </div>

        <span id="aviso-estado" style="font-size: 0.85rem; font-weight: 600;"></span>
    </div>

    <style>
        #menu-dropdown-grupo a:hover { background-color: #f7fafc; }
        #menu-dropdown-grupo a.disabled { color: #a0aec0; cursor: not-allowed; background-color: #edf2f7; border-left-color: #cbd5e0 !important; opacity: 0.6; }
        #btn-dropdown-grupo:hover { background-color: #2b6cb0; }
        .fila-datos, .fila-separador { transition: background-color 0.2s ease, opacity 0.15s; }
        .fila-datos[draggable="true"]:hover, .fila-separador[draggable="true"]:hover { cursor: grab; }
        .fila-datos.dragging, .fila-separador.dragging { opacity: 0.3; background-color: #cbd5e0 !important; }
        .drag-over { border-top: 3px solid #3182ce !important; }
    </style>
</div>