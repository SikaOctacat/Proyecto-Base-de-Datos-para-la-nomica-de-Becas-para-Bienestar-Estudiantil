<style>
    @keyframes slideIn {
        from { opacity: 0; transform: translateY(20px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .profile-container {
        animation: slideIn 0.5s ease-out;
        max-width: 1200px;
        margin: 40px auto;
        padding: 20px;
        font-family: 'Segoe UI', Roboto, sans-serif;
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 25px;
        align-items: start;
    }

    .profile-header-card {
        grid-column: 1 / -1;
        text-align: center;
        padding: 40px;
        background: white;
        border-radius: 24px;
        box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        border: 1px solid #f0f0f0;
    }

    .glass-card {
        background: #ffffff;
        border-radius: 20px;
        padding: 24px;
        border: 1px solid #f0f0f0;
        box-shadow: 0 4px 15px rgba(0,0,0,0.03);
        display: flex;
        flex-direction: column;
        min-height: 350px; /* Ajustado para que quepan los nuevos datos */
    }
    
    .card-title {
        color: #FF6600;
        font-size: 1.1rem;
        font-weight: 700;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        border-bottom: 2px solid #fff3e0;
        padding-bottom: 10px;
    }

    .data-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        padding-bottom: 6px;
        border-bottom: 1px dashed #f0f0f0;
        font-size: 0.9rem;
    }

    .label { color: #888; font-weight: 500; }
    .value { color: #333; font-weight: 600; text-align: right; }

    .address-box {
        font-size: 0.85rem;
        color: #666;
        background: #f9f9f9;
        padding: 10px;
        border-radius: 10px;
        border-left: 4px solid #FF6600;
        margin-top: 5px;
    }

    .full-width { grid-column: 1 / -1; min-height: auto; }

    .status-badge {
        margin-top: 10px; display: inline-block;
        background: #e8f5e9; color: #2e7d32;
        padding: 6px 20px; border-radius: 50px;
        font-weight: 700; font-size: 0.85rem;
    }

    .security-tag {
        background: #fff3e0; color: #e65100;
        padding: 2px 8px; border-radius: 4px; font-size: 0.75rem;
    }

    @media (max-width: 1100px) { .profile-container { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 768px) { .profile-container { grid-template-columns: 1fr; } }
</style>

<div class="profile-container">
    <div class="profile-header-card">
        <div style="background: linear-gradient(135deg, #FF6600, #FF9D00); width: 80px; height: 80px; border-radius: 20px; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; color: white; font-size: 30px; font-weight: bold;">{{inicial}}</div>
        <h1 style="margin:0;">{{nombre1}} {{nombre2}} {{apellido_paterno}} {{apellido_materno}}</h1>
        <p style="color:#999; margin: 5px 0;">Usuario: <strong>{{cedula}}</strong> | ID: {{id_sistema}}</p>
        <div class="status-badge">Beca: {{tipo_beneficio}} ({{estado_beca}})</div>
        <p style="font-size: 0.8rem; color: #aaa;">Estatus académico: {{estatus_estudio}}</p>
    </div>

    <div class="glass-card">
        <div class="card-title"><span>🎓</span> Académico</div>
        <div class="data-item"><span class="label">Carrera</span><span class="value">{{carrera}}</span></div>
        <div class="data-item"><span class="label">Código</span><span class="value">{{cod_est}}</span></div>
        <div class="data-item"><span class="label">Trayecto / Trim</span><span class="value">{{trayecto}} - {{trimestre}}</span></div>
        <div class="data-item"><span class="label">Ingreso</span><span class="value">{{f_ingreso}}</span></div>
        <div style="margin-top:15px; padding-top:10px; border-top: 2px solid #f9f9f9;">
            <div class="data-item"><span class="label">Índice Trimestral</span><span class="value" style="color:#FF6600;">{{record_indice}}</span></div>
            <div class="data-item"><span class="label">IRA</span><span class="value">{{m_ira}}</span></div>
        </div>
    </div>

    <div class="glass-card">
        <div class="card-title"><span>👤</span> Personal</div>
        <div class="data-item"><span class="label">Edad</span><span class="value">{{edad}} años</span></div>
        <div class="data-item"><span class="label">Nacimiento</span><span class="value">{{f_nac}}</span></div>
        <div class="data-item"><span class="label">Estado Civil</span><span class="value">{{edo_civil}}</span></div>
        <div class="data-item"><span class="label">Teléfono</span><span class="value">{{tel_estudiante}}</span></div>
        <div class="data-item"><span class="label">Correo</span><span class="value">{{correo}}</span></div>
        <div class="data-item"><span class="label">C. Patria</span><span class="value">{{C_Patria}}</span></div>
    </div>

    <div class="glass-card">
        <div class="card-title"><span>🏠</span> Residencia</div>
        <div class="data-item"><span class="label">Vivienda</span><span class="value">{{t_viv}}</span></div>
        <div class="data-item"><span class="label">Estado</span><span class="value">{{estado_res}}</span></div>
        <div class="data-item"><span class="label">Municipio</span><span class="value">{{municipio_res}}</span></div>
        <div class="data-item"><span class="label">Localidad</span><span class="value">{{localidad}}</span></div>
        <div class="data-item"><span class="label">Tel. Local</span><span class="value">{{tel_local}}</span></div>
        <div style="margin-top:10px;">
            <span class="label" style="font-size:0.8rem;">Dirección Exacta:</span>
            <div class="address-box">{{dir_local}}</div>
        </div>
    </div>

    <div class="glass-card full-width">
        <div class="card-title"><span>👨‍👩‍👧‍👦</span> Grupo Familiar</div>
        <div style="overflow-x: auto;">
            {{tabla_familia}}
        </div>
    </div>

    <div class="glass-card full-width" style="background: #fffaf5;">

        <div class="card-title"><span>📝</span> Notas Adicionales</div>

        <p style="font-size: 0.95rem; color: #666; line-height: 1.6;">{{observaciones}}</p>

    </div>

    <div class="full-width" style="text-align: center; padding: 40px 0;">
        <a href="logout.php" style="background: #ff4d4d; color: white; padding: 12px 35px; border-radius: 12px; text-decoration: none; font-weight: 700; box-shadow: 0 5px 15px rgba(255,77,77,0.3);">
            Cerrar Sesión Segura
        </a>
    </div>
</div>