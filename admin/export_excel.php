<?php
ob_start();
session_start();
require '../db.php';

// 1. Verificación de sesión (Asegúrate de que coincida con tu login)
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    die("Acceso denegado.");
}

// 2. Query ajustada a TU base de datos real
// Nota: He unido los nombres y apellidos para que el Excel sea más limpio
$query = $pdo->query("
    SELECT 
        e.ci, 
        e.nombre1, e.nombre2, e.apellido_paterno, e.apellido_materno,
        e.f_nac, e.edad, e.edo_civil, e.tel_estudiante, e.correo,
        e.carrera, e.trayecto, e.trimestre, e.ira_anterior,
        e.tipo_beneficio, e.viaja,
        r.t_viv, r.municipio_res, r.dir_local
    FROM estudiante e
    LEFT JOIN residencia r ON e.ci = r.ci_estudiante
    ORDER BY e.ci ASC
");
$data = $query->fetchAll(PDO::FETCH_ASSOC);

// 3. Preparación del archivo
$filename = "reporte_becas_" . date('Y-m-d') . ".xls";

if (ob_get_length()) ob_clean();

// Headers para forzar descarga y compatibilidad con caracteres especiales (UTF-8)
header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Creamos la estructura de la tabla HTML que Excel interpreta perfectamente
?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<table border="1">
    <thead>
        <tr style="background-color: #FF6600; color: white; font-weight: bold;">
            <th>Cédula</th>
            <th>Nombres</th>
            <th>Apellidos</th>
            <th>F. Nacimiento</th>
            <th>Edad</th>
            <th>Edo. Civil</th>
            <th>Teléfono</th>
            <th>Correo</th>
            <th>Carrera</th>
            <th>Trayecto/Trim</th>
            <th>IRA</th>
            <th>Tipo Beneficio</th>
            <th>Viaja</th>
            <th>Vivienda</th>
            <th>Municipio</th>
            <th>Dirección</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($data as $row): 
            $nombres = $row['nombre1'] . ' ' . ($row['nombre2'] ?? '');
            $apellidos = $row['apellido_paterno'] . ' ' . ($row['apellido_materno'] ?? '');
        ?>
        <tr>
            <td><?php echo $row['ci']; ?></td>
            <td><?php echo htmlspecialchars($nombres); ?></td>
            <td><?php echo htmlspecialchars($apellidos); ?></td>
            <td><?php echo $row['f_nac']; ?></td>
            <td><?php echo $row['edad']; ?></td>
            <td><?php echo ucfirst($row['edo_civil']); ?></td>
            <td><?php echo $row['tel_estudiante']; ?></td>
            <td><?php echo $row['correo']; ?></td>
            <td><?php echo htmlspecialchars($row['carrera']); ?></td>
            <td><?php echo "T" . $row['trayecto'] . " / Trim " . $row['trimestre']; ?></td>
            <td><?php echo number_format($row['ira_anterior'], 2); ?></td>
            <td><?php echo $row['tipo_beneficio']; ?></td>
            <td><?php echo $row['viaja']; ?></td>
            <td><?php echo $row['t_viv']; ?></td>
            <td><?php echo $row['municipio_res']; ?></td>
            <td><?php echo htmlspecialchars($row['dir_local']); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php
exit;
?>