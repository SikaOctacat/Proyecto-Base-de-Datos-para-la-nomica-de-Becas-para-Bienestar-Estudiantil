<?php
require 'db.php';
$cols = $pdo->query('SHOW COLUMNS FROM pnfs')->fetchAll(PDO::FETCH_ASSOC);
print_r($cols);
