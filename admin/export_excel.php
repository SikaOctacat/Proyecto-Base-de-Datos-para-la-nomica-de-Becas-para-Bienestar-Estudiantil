<?php
require '../db.php';

// Simple session check
if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
    die("Acceso denegado.");
}

// Fetch all student data with related info
$query = $pdo->query("
    SELECT 
        e.id, e.ci, e.nombres, e.apellidos, e.fecha_nacimiento, e.edad, e.estado_civil, e.telefono, e.correo,
        p.carrera, p.trayecto, p.trimestre_actual,
        r.indice_trimestre,
        res.direccion, res.tipo_vivienda,
        t.lugar as trabajo_lugar, t.ingreso as trabajo_ingreso
    FROM estudiantes e
    LEFT JOIN pnfs p ON e.pnf_id = p.id
    LEFT JOIN records_academicos r ON e.id = r.estudiante_id
    LEFT JOIN residencias res ON e.id = res.estudiante_id
    LEFT JOIN trabajos t ON e.id = t.estudiante_id
    ORDER BY e.id ASC
");
$data = $query->fetchAll();

// Filename
$filename = "reporte_becas_" . date('Y-m-d') . ".xls"; // Use .xls to force Excel to treat it as a spreadsheet

// Clear output buffer
if (ob_get_length()) ob_clean();

// Headers for Excel download (UTF-16LE Tab-Separated)
header('Content-Type: application/vnd.ms-excel; charset=UTF-16LE');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

// Open the output stream
$output = fopen('php://output', 'w');

// Add UTF-16LE BOM
fwrite($output, chr(0xFF).chr(0xFE));

// Helper function to convert text to UTF-16LE and escape quotes
function toUTF16($text) {
    if ($text === null) $text = '';
    $text = (string)$text;
    $text = str_replace('"', '""', $text); // Escape double quotes
    return mb_convert_encoding('"' . $text . '"', 'UTF-16LE', 'UTF-8');
}

// Prepare header row
$headers = [
    'ID', 'Cédula', 'Nombres', 'Apellidos', 'Fecha de Nacimiento', 'Edad', 'Estado Civil', 'Teléfono', 'Correo Electrónico',
    'Carrera Universitaria', 'Carrera (Trayecto/Trimestre)', 'Índice Académico', 'Dirección de Habitación', 'Tipo de Vivienda', 'Lugar de Trabajo', 'Ingreso Mensual (Bs)'
];

// Write header row
$headerLine = [];
foreach ($headers as $h) { $headerLine[] = toUTF16($h); }
fwrite($output, implode(mb_convert_encoding("\t", 'UTF-16LE', 'UTF-8'), $headerLine) . mb_convert_encoding("\r\n", 'UTF-16LE', 'UTF-8'));

// Add data rows
foreach ($data as $row) {
    $line = [
        $row['id'],
        $row['ci'],
        $row['nombres'],
        $row['apellidos'],
        $row['fecha_nacimiento'],
        $row['edad'],
        $row['estado_civil'],
        $row['telefono'],
        $row['correo'],
        $row['carrera'],
        $row['trayecto'] . " / " . $row['trimestre_actual'],
        $row['indice_trimestre'],
        $row['direccion'],
        $row['tipo_vivienda'],
        $row['trabajo_lugar'] ?? 'No trabaja',
        $row['trabajo_ingreso'] ?? '0.00'
    ];
    
    $rowCells = [];
    foreach ($line as $cell) { $rowCells[] = toUTF16($cell); }
    fwrite($output, implode(mb_convert_encoding("\t", 'UTF-16LE', 'UTF-8'), $rowCells) . mb_convert_encoding("\r\n", 'UTF-16LE', 'UTF-8'));
}

fclose($output);
exit;
?>
