<?php
require 'db.php';
$ci = $_GET['ci'] ?? '';
$stmt = $pdo->prepare("SELECT COUNT(*) FROM estudiante WHERE ci = ?");
$stmt->execute([$ci]);
echo json_encode(['existe' => $stmt->fetchColumn() > 0]);