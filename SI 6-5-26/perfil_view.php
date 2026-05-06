<?php
require 'db.php';
if (session_status() === PHP_SESSION_NONE) { session_start(); }

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo "<div style='color:white; background:red; padding:20px;'>Error: Sesión no iniciada.</div>";
    exit;
}

function normalizar($texto) {
    if (!$texto) return 'N/A';
    $limpio = str_replace('_', ' ', $texto);
    return ucfirst(strtolower(htmlspecialchars($limpio)));
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            e.*, 
            r.t_res, r.t_viv, r.estado_res, r.municipio_res, r.t_loc, r.r_prop, r.tel_local, r.dir_local,
            ra.ira_anterior as m_ira
        FROM estudiante e
        LEFT JOIN residencia r ON e.ci = r.ci_estudiante 
        LEFT JOIN record_academico ra ON e.ci = ra.ci_estudiante
        WHERE e.usuario_id = ?
    ");
    $stmt->execute([$user_id]);
    $estudiante = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$estudiante) {
        echo "<div class='glass-card' style='margin:40px; text-align:center;'><h3>No se encontró información de postulación.</h3></div>";
        exit;
    }
} catch (PDOException $e) {
    echo "Error en la consulta: " . $e->getMessage();
    exit;
}

// --- LÓGICA PARA LA TABLA DE FAMILIARES ---
$stmtFam = $pdo->prepare("SELECT * FROM familiar WHERE ci_estudiante = ?");
$stmtFam->execute([$estudiante['ci']]);
$familiares = $stmtFam->fetchAll(PDO::FETCH_ASSOC);

$tabla_familia = '<table style="width:100%; border-collapse: collapse; font-size:0.85rem; min-width: 600px;">
    <thead>
        <tr style="background:#f8f9fa; text-align:left; border-bottom: 2px solid #eee;">
            <th style="padding:12px;">Nombre y Apellido</th>
            <th style="padding:12px;">Parentesco</th>
            <th style="padding:12px; text-align:center;">Edad</th>
            <th style="padding:12px;">Instrucción</th>
            <th style="padding:12px;">Ocupación</th>
            <th style="padding:12px; text-align:right;">Ingresos</th>
        </tr>
    </thead>
    <tbody>';

if (empty($familiares)) {
    $tabla_familia .= '<tr><td colspan="6" style="padding:20px; text-align:center; color:#999; font-style:italic;">No hay familiares registrados.</td></tr>';
} else {
    foreach ($familiares as $f) {
        $ingreso = number_format($f['f_ing'] ?? 0, 2, ',', '.');
        $tabla_familia .= "
        <tr style='border-bottom:1px solid #f0f0f0;'>
            <td style='padding:12px; font-weight:600;'>".normalizar($f['f_nom'])." ".normalizar($f['f_ape'])."</td>
            <td style='padding:12px;'>".normalizar($f['f_par'])."</td>
            <td style='padding:12px; text-align:center;'>".htmlspecialchars($f['f_eda'] ?? '--')."</td>
            <td style='padding:12px;'>".normalizar($f['f_ins'])."</td>
            <td style='padding:12px;'>".normalizar($f['f_ocu'])."</td>
            <td style='padding:12px; text-align:right; font-weight:700; color:#2e7d32;'>Bs. $ingreso</td>
        </tr>";
    }
}
$tabla_familia .= '</tbody></table>';
?>

<style>
    .profile-container { 
        display: grid; 
        grid-template-columns: repeat(3, 1fr); 
        gap: 20px; 
        max-width: 1200px; 
        margin: 20px auto; 
        padding: 10px; 
    }
    .profile-header-card { grid-column: 1 / -1; background: white; padding: 30px; border-radius: 20px; text-align: center; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
    
    .glass-card { 
        background: white; 
        padding: 20px; 
        border-radius: 18px; 
        box-shadow: 0 2px 10px rgba(0,0,0,0.04); 
        border: 1px solid #f0f0f0; 
        display: flex; 
        flex-direction: column;
        height: 100%; /* Obliga a todas a tener la misma altura */
    }

    /* Contenedor de items: Se expande para llenar la tarjeta */
    .card-content { 
        flex-grow: 1; 
        display: flex; 
        flex-direction: column; 
        justify-content: space-between; /* Distribuye los items equitativamente */
    }

    .card-title { color: #FF6600; font-weight: 700; margin-bottom: 15px; padding-bottom: 8px; border-bottom: 2px solid #fff5f0; display: flex; align-items: center; gap: 8px; }
    
    /* Cada item ahora tiene flex-grow para ayudar a la distribución */
    .data-item { 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        padding: 10px 0; 
        border-bottom: 1px dashed #f5f5f5; 
        font-size: 0.9rem;
        flex-grow: 1; /* Hace que cada fila aporte al llenado del espacio */
    }
    
    .label { color: #888; font-weight: 500; }
    .value { color: #333; font-weight: 600; text-align: right; }
    
    /* Footer: Se mantiene al fondo y se ajusta al contenido */
    .card-footer {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 1px solid #f0f0f0;
    }

    .address-box { background: #f9f9f9; padding: 10px; border-radius: 8px; border-left: 4px solid #FF6600; font-size: 0.8rem; color: #555; line-height: 1.3; }
    .full-width { grid-column: 1 / -1; }
    .status-badge { background: #e8f5e9; color: #2e7d32; padding: 5px 15px; border-radius: 20px; font-size: 0.85rem; font-weight: 700; display: inline-block; margin-top: 10px; }
    
    @media (max-width: 992px) { .profile-container { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 600px) { .profile-container { grid-template-columns: 1fr; } }
</style>

<div class="profile-container">
    <div class="profile-header-card">
        <div style="background: linear-gradient(135deg, #FF6600, #FF9D00); width: 70px; height: 70px; border-radius: 18px; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; color: white; font-size: 28px; font-weight: bold;">
            <?php echo strtoupper(substr($estudiante['nombre1'] ?? 'U', 0, 1)); ?>
        </div>
        <h2 style="margin:0; color:#333;">
            <?php 
                $nombre_completo = [$estudiante['nombre1'], $estudiante['nombre2'], $estudiante['apellido_paterno'], $estudiante['apellido_materno']];
                echo implode(' ', array_map('normalizar', array_filter($nombre_completo))); 
            ?>
        </h2>
        <p style="color:#777; font-size: 0.9rem; margin: 5px 0;">
            CI: <strong><?php echo htmlspecialchars($estudiante['ci']); ?></strong> | ID: <?php echo htmlspecialchars($estudiante['cod_est'] ?? 'N/A'); ?>
        </p>
        <div class="status-badge"><?php echo normalizar($estudiante['tipo_beneficio']); ?> — Activa</div>
    </div>

    <div class="glass-card">
        <div class="card-title"><span>🎓</span> Académico:</div>
        <div class="card-content">
            <div class="data-item"><span class="label">Carrera:</span><span class="value"><?php echo normalizar($estudiante['carrera']); ?></span></div>
            <div class="data-item"><span class="label">Trayecto:</span><span class="value"><?php echo htmlspecialchars($estudiante['trayecto'] ?? '0'); ?></span></div>
            <div class="data-item"><span class="label">Trimestre:</span><span class="value"><?php echo htmlspecialchars($estudiante['trimestre'] ?? '0'); ?></span></div>
            <div class="data-item" style="border:none;"><span class="label">Ingreso:</span><span class="value"><?php echo htmlspecialchars($estudiante['f_ingreso'] ?? 'N/A'); ?></span></div>
        </div>
        <div class="card-footer">
            <span class="label" style="font-size:0.85rem;">IRA Anterior:</span>
            <div style="font-size: 1.1rem; font-weight: 800; color: #FF6600; margin-top:5px;">
                <?php echo number_format($estudiante['m_ira'] ?? 0, 2); ?> <span style="font-size:0.8rem; color:#ccc;">/ 20 pts</span>
            </div>
        </div>
    </div>

    <div class="glass-card">
        <div class="card-title"><span>👤</span> Personal:</div>
        <div class="card-content">
            <div class="data-item"><span class="label">Edad:</span><span class="value"><?php echo htmlspecialchars($estudiante['edad'] ?? '0'); ?> años</span></div>
            <div class="data-item"><span class="label">Nacimiento:</span><span class="value"><?php echo htmlspecialchars($estudiante['f_nac'] ?? 'N/A'); ?></span></div>
            <div class="data-item"><span class="label">Edo. Civil:</span><span class="value"><?php echo normalizar($estudiante['edo_civil']); ?></span></div>
            <div class="data-item"><span class="label">Teléfono:</span><span class="value"><?php echo htmlspecialchars($estudiante['tel_estudiante'] ?? 'N/A'); ?></span></div>
        </div>
        
        <div class="card-footer">
            <span class="label" style="font-size:0.85rem;">Correo Electrónico:</span>
            <div style="font-weight: 700; color: #333; font-size:0.9rem; margin-top:5px">
                <?php 
                    $email = $estudiante['correo'] ?? 'no-disponible@correo.com';
                    $partes = explode('@', $email);
                    echo "<span style='color:#FF6600;'>" . htmlspecialchars($partes[0]) . "</span>@" . htmlspecialchars($partes[1] ?? '');
                ?>
            </div>
        </div>
    </div>

    <div class="glass-card">
        <div class="card-title"><span>🏠</span> Residencia:</div>
        <div class="card-content">
            <div class="data-item"><span class="label">Residencia:</span><span class="value"><?php echo normalizar($estudiante['t_res']); ?></span></div>
            <div class="data-item"><span class="label">Vivienda:</span><span class="value"><?php echo normalizar($estudiante['t_viv']); ?></span></div>
            <div class="data-item"><span class="label">Localidad:</span><span class="value"><?php echo normalizar($estudiante['t_loc']); ?></span></div>
            <div class="data-item" style="border:none;"><span class="label">Municipio:</span><span class="value"><?php echo normalizar($estudiante['municipio_res']); ?></span></div>
        </div>
        <div class="card-footer">
            <span class="label" style="font-size:0.85rem;">Dirección Exacta:</span>
            <div class="address-box">
                <?php echo htmlspecialchars($estudiante['dir_local'] ?? 'Sin dirección registrada'); ?>
            </div>
        </div>
    </div>
</div>

<div>
    <div class="glass-card full-width"style="margin-top: 50px;">
        <div class="card-title"><span>👨‍👩‍👧‍👦</span> Grupo Familiar:</div>
        <div style="overflow-x: auto;">
            <?php echo $tabla_familia; ?> 
        </div>
    </div>

    <div class="glass-card full-width" style="margin-top: 10px;">
        <div class="card-title"><span>📝</span> Observaciones del Sistema:</div>
        <div style="background: #fffcf5; padding: 15px; border-radius: 12px; border: 1px solid #ffeeba; color: #856404; font-size: 0.9rem;">
            <?php 
                $obs = $estudiante['observaciones'] ?? ''; 
                echo !empty($obs) ? nl2br(htmlspecialchars($obs)) : '<em>No hay observaciones registradas para este expediente.</em>';
            ?>
        </div>
    </div>
</div>