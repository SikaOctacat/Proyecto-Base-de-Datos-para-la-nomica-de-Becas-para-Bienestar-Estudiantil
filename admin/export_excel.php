<?php
ob_start();
session_start();
require '../db.php';

// 1. Verificación de sesión
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    die("Acceso denegado.");
}

// Función de bitácora (asegurando que exista)
if (!function_exists('registrarMovimiento')) {
    function registrarMovimiento($pdo, $usuario_id, $accion, $tabla, $detalles = null) {
        $stmt = $pdo->prepare('INSERT INTO bitacora (usuario_id, accion, tabla_afectada, detalles) VALUES (?, ?, ?, ?)');
        $stmt->execute([$usuario_id, $accion, $tabla, $detalles]);
    }
}

// --- REGISTRO EN BITÁCORA ---
if (isset($_SESSION['user_id'])) {
    registrarMovimiento(
        $pdo, 
        $_SESSION['user_id'], 
        "Exportación", 
        "Estudiante/Varios", 
        "Generación de reporte Excel con listado completo de estudiantes y datos residenciales."
    );
}

// 2. Query Corregida según tu SQL Dump (Eliminada la columna inexistente 'parroquia_res')
$query = $pdo->query("
    SELECT 
        e.*, 
        r.t_res, r.t_viv, r.t_loc, r.r_prop, r.estado_res, r.municipio_res, r.dir_local, r.tel_local,
        ra.indice_trimestre
    FROM estudiante e
    LEFT JOIN residencia r ON e.ci = r.ci_estudiante
    LEFT JOIN record_academico ra ON e.ci = ra.ci_estudiante
    ORDER BY e.ci ASC
");
$data = $query->fetchAll(PDO::FETCH_ASSOC);

// 3. Preparación del archivo
$filename = "reporte_becas_completo_" . date('Y-m-d') . ".xls";

if (ob_get_length()) ob_clean();

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<table border="1">
    <thead>
        <tr style="background-color: #004a99; color: white; font-weight: bold;">
            <th>Cédula</th>
            <th>Código Est.</th>
            <th>Nombres</th>
            <th>Apellidos</th>
            <th>F. Nacimiento</th>
            <th>Edad</th>
            <th>Edo. Civil</th>
            <th>Teléfono</th>
            <th>Correo</th>
            <th>Carrera</th>
            <th>Trayecto</th>
            <th>Trimestre</th>
            <th>IRA Anterior</th>
            <th>Tipo Beneficio</th>
            <th>Viaja</th>
            <th>Ubicación (Tipo)</th>
            <th>Vivienda</th>
            <th>Estado</th>
            <th>Municipio</th>
            <th>Dirección Exacta</th>
            <th>Observaciones</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($data as $row): 
            $nombres = trim(($row['nombre1'] ?? '') . ' ' . ($row['nombre2'] ?? ''));
            $apellidos = trim(($row['apellido_paterno'] ?? '') . ' ' . ($row['apellido_materno'] ?? ''));
        ?>
        <tr>
            <td><?php echo $row['ci']; ?></td>
            <td><?php echo $row['cod_est'] ?? 'N/A'; ?></td>
            <td><?php echo htmlspecialchars($nombres); ?></td>
            <td><?php echo htmlspecialchars($apellidos); ?></td>
            <td><?php echo $row['f_nac']; ?></td>
            <td><?php echo $row['edad']; ?></td>
            <td><?php echo ucfirst($row['edo_civil'] ?? 'N/A'); ?></td>
            <td><?php echo $row['tel_estudiante']; ?></td>
            <td><?php echo strtolower($row['correo']); ?></td>
            <td><?php echo htmlspecialchars($row['carrera']); ?></td>
            <td style="text-align: center;"><?php echo $row['trayecto']; ?></td>
            <td style="text-align: center;"><?php echo $row['trimestre']; ?></td>
            <td><?php echo number_format($row['ira_anterior'] ?? 0, 2); ?></td>
            <td><?php echo $row['tipo_beneficio']; ?></td>
            <td><?php echo $row['viaja']; ?></td>
            <td><?php echo ($row['t_res'] ?? '') . " / " . ($row['t_loc'] ?? ''); ?></td>
            <td><?php echo $row['t_viv'] ?? 'N/A'; ?></td>
            <td><?php echo $row['estado_res'] ?? 'N/A'; ?></td>
            <td><?php echo $row['municipio_res'] ?? 'N/A'; ?></td>
            <td><?php echo htmlspecialchars($row['dir_local'] ?? 'N/A'); ?></td>
            <td><?php echo htmlspecialchars($row['observaciones'] ?? ''); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php
exit;
?>