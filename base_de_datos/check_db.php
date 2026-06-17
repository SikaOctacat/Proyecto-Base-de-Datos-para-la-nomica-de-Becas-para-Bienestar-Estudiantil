<?php
require 'base_de_datos/db.php';
header('Content-Type: text/plain');

$tablas = ['usuarios', 'estudiante', 'bitacora', 'residencia', 'familiar'];

foreach ($tablas as $tabla) {
    echo "--- ESTRUCTURA DE LA TABLA: $tabla ---\n";
    try {
        // Esto nos dará el comando exacto con el que se creó la tabla
        $stmt = $pdo->query("SHOW CREATE TABLE $tabla");
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        echo $res['Create Table'] . "\n\n";
        
        // Esto nos dirá si las columnas aceptan nulos o tienen defaults
        echo "DETALLES DE COLUMNAS:\n";
        $stmt = $pdo->query("DESCRIBE $tabla");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            print_r($row);
        }
    } catch (Exception $e) {
        echo "Error leyendo tabla $tabla: " . $e->getMessage() . "\n";
    }
    echo "\n" . str_repeat("=", 50) . "\n\n";
}