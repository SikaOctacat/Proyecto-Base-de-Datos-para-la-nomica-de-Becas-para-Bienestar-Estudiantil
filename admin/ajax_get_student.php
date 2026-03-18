<?php
require '../db.php';

// Session check to ensure only admins can use this
if (!isset($_SESSION['user']) || (isset($_SESSION['rol']) && $_SESSION['rol'] !== 'admin')) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

$ci = $_GET['ci'] ?? '';

if (empty($ci)) {
    echo json_encode(['found' => false]);
    exit;
}

try {
    // Search in the estudiantes table
    $stmt = $pdo->prepare('SELECT id, nombres, apellidos FROM estudiantes WHERE ci = ? LIMIT 1');
    $stmt->execute([$ci]);
    $student = $stmt->fetch();

    if ($student) {
        echo json_encode([
            'found' => true,
            'id' => $student['id'],
            'nombres' => $student['nombres'],
            'apellidos' => $student['apellidos']
        ]);
    } else {
        echo json_encode(['found' => false]);
    }
} catch (PDOException $e) {
    echo json_encode(['found' => false, 'error' => $e->getMessage()]);
}
