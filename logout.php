<?php
require 'db.php';

if (session_status() === PHP_SESSION_NONE) { session_start(); }

session_unset();
session_destroy();

// Si es una navegación normal, redirige. 
// Si es un Beacon (petición de fondo), simplemente termina.
if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) && !empty($_SERVER['REQUEST_METHOD'])) {
    header('Location: index.php');
}
exit;