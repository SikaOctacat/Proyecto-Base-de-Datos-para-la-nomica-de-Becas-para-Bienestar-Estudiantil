<?php
require 'db.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `alertas` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `titulo` varchar(150) NOT NULL,
        `mensaje` text DEFAULT NULL,
        `imagen_url` varchar(255) DEFAULT NULL,
        `tipo` varchar(50) DEFAULT 'info',
        `activo` tinyint(1) DEFAULT 1,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");
    echo "Tabla 'alertas' creada o ya existía.\n";
} catch (PDOException $e) {
    echo "Error al crear la tabla: " . $e->getMessage() . "\n";
}
