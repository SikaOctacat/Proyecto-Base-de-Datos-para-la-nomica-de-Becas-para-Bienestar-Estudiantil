<?php
require 'base_de_datos/db.php';
header('Content-Type: application/json'); // Indicar que la respuesta es JSON

$ci = isset($_GET['ci']) ? trim($_GET['ci']) : '';

if (empty($ci)) {
    echo json_encode(['existe' => false]);
    exit;
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM estudiante WHERE cedula = ?"); // Verifica si tu columna se llama 'ci' o 'cedula'
$stmt->execute([$ci]);
echo json_encode(['existe' => $stmt->fetchColumn() > 0]);