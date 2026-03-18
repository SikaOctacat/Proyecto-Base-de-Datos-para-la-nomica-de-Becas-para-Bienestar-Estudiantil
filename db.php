<?php
// db.php: establish database connection and start session
if(session_status() === PHP_SESSION_NONE) {
    session_start();
}

$dsn = 'mysql:host=127.0.0.1;dbname=proyecto_db;charset=utf8mb4';
$dbuser = 'root';
$dbpass = '';

try {
    $pdo = new PDO($dsn, $dbuser, $dbpass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    // in production you might log this instead
    die("Error de conexión a la base de datos: " . $e->getMessage());
}
