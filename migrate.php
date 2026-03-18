<?php
require 'db.php';
// migrate pnfs table to include needed columns
$pdo->exec("ALTER TABLE pnfs ADD COLUMN carrera varchar(255) DEFAULT NULL");
$pdo->exec("ALTER TABLE pnfs ADD COLUMN codigo_estudiante varchar(100) DEFAULT NULL");
echo "migration complete\n";
