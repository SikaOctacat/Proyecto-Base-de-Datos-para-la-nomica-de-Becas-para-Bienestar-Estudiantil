<?php
// 1. Incluir la base de datos con una ruta dinámica para que funcione desde cualquier carpeta
// Esto evita que falle si el admin y el estudiante están en niveles distintos
$root = $_SERVER['DOCUMENT_ROOT'] . '/Proyecto-Base-de-Datos-para-la-nomica-de-Becas-para-Bienestar-Estudiantil/'; 
require_once __DIR__ . '/db.php'; // Usa __DIR__ para asegurar que busca en la misma carpeta del archivo

if (session_status() === PHP_SESSION_NONE) { 
    session_start(); 
}

// 2. REGISTRO EN BITÁCORA (CRÍTICO: Antes de destruir la sesión)
// Verificamos tanto 'user_id' como 'user' para cubrir ambos roles (Admin/Estudiante)
if (isset($_SESSION['user_id'])) {
    $id_log = $_SESSION['user_id'];
    $nombre_log = $_SESSION['usuario'] ?? $_SESSION['user'] ?? 'Usuario';
    $rol_log = $_SESSION['rol'] ?? 'estudiante';

    try {
        $stmt = $pdo->prepare("INSERT INTO bitacora (usuario_id, accion, tabla_afectada, detalles) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $id_log, 
            "Cierre de Sesión", 
            "Seguridad/Sesiones", 
            "Cerró sesión"
        ]);
    } catch (PDOException $e) {
        // Si falla el registro, al menos que no bloquee el cierre de sesión
    }
}

// 3. DESTRUCCIÓN TOTAL DE LA SESIÓN
$_SESSION = array(); // Limpia el array
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// 4. REDIRECCIÓN INTELIGENTE
// Si es una petición de fondo (Beacon de JS), no imprimimos nada
if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) || empty($_SERVER['REQUEST_METHOD'])) {
    exit;
}

// Redirigir al login principal
header("Location: ../login.php");
exit;