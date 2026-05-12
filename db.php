<?php
// db.php: establish database connection and start session
if(session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Intentamos leer las variables de Render, si no existen, usa los valores locales
$host = getenv('DB_HOST') ?: '127.0.0.1';
$dbname = getenv('DB_NAME') ?: 'becas_database';
$dbuser = getenv('DB_USER') ?: 'root';
$dbpass = getenv('DB_PASS') ?: '';
$port = getenv('DB_PORT') ?: '3306';

$dsn = "mysql:host=$host;dbname=$dbname;port=$port;charset=utf8mb4";

try {
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    // Si estamos en Render (detectando el host de TiDB), activamos SSL
    if (getenv('DB_HOST')) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = true;
    }

    $pdo = new PDO($dsn, $dbuser, $dbpass, $options);
} catch (PDOException $e) {
    // En producción, es mejor no mostrar detalles sensibles, pero para desarrollo:
    die("Error de conexión a la base de datos: " . $e->getMessage());
}
