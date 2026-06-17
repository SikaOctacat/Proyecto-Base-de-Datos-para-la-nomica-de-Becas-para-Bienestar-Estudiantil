<?php

if(session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Carga de variables de entorno para producción o valores por defecto para desarrollo local
$host = getenv('DB_HOST') ?: '127.0.0.1';
$dbname = getenv('DB_NAME') ?: 'becas_database';
$dbuser = getenv('DB_USER') ?: 'root';
$dbpass = getenv('DB_PASS') ?: '';
$port = getenv('DB_PORT') ?: '3306';

// Definición de la cadena de conexión de origen de datos para MySQL
$dsn = "mysql:host=$host;dbname=$dbname;port=$port;charset=utf8mb4";

try {
    // Configuración general de atributos para el manejo de errores y formato de datos
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    if (getenv('DB_HOST')) {
        $options[PDO::MYSQL_ATTR_SSL_CA] = true;
        $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }

    // Creación de la instancia PDO para establecer la conexión definitiva
    $pdo = new PDO($dsn, $dbuser, $dbpass, $options);
} catch (PDOException $e) {
    // Captura del error y detención del script en caso de fallo en la conexión
    die("Error de conexión a la base de datos: " . $e->getMessage());
}