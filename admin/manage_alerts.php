<?php
session_start();
require '../db.php';

// Verificar sesión de administrador (simple check based on usual patterns in this app)
if (!isset($_SESSION['user']) || $_SESSION['user'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Manejar eliminación
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare("SELECT imagen_url FROM alertas WHERE id = ?");
    $stmt->execute([$id]);
    $alert = $stmt->fetch();
    if ($alert && $alert['imagen_url']) {
        @unlink('../' . $alert['imagen_url']);
    }
    $pdo->prepare("DELETE FROM alertas WHERE id = ?")->execute([$id]);
    header("Location: manage_alerts.php?msg=Alert eliminada");
    exit();
}

// Manejar creación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
    $titulo = $_POST['titulo'];
    $mensaje = $_POST['mensaje'];
    $tipo = $_POST['tipo'];
    $imagen_url = '';

    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $ext = pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION);
        $filename = time() . '_' . uniqid() . '.' . $ext;
        $targetPath = '../img/alerts/' . $filename;
        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $targetPath)) {
            $imagen_url = 'img/alerts/' . $filename;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO alertas (titulo, mensaje, imagen_url, tipo) VALUES (?, ?, ?, ?)");
    $stmt->execute([$titulo, $mensaje, $imagen_url, $tipo]);
    header("Location: manage_alerts.php?msg=Alerta creada");
    exit();
}

$alertas = $pdo->query("SELECT * FROM alertas ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestionar Alertas - Admin</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .admin-container { max-width: 1000px; margin: 40px auto; padding: 20px; background: white; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .alert-form { background: #f9f9f9; padding: 20px; border-radius: 10px; margin-bottom: 30px; border: 1px solid #eee; }
        .alert-form grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
        .alert-list-item { display: flex; align-items: center; gap: 20px; padding: 15px; border-bottom: 1px solid #eee; }
        .alert-list-item:last-child { border-bottom: none; }
        .alert-img-preview { width: 80px; height: 60px; object-fit: cover; border-radius: 5px; background: #eee; }
        .btn-delete { background: #e74c3c; color: white; padding: 5px 12px; border-radius: 5px; text-decoration: none; font-size: 0.85rem; }
        .badge { padding: 4px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: bold; text-transform: uppercase; }
        .badge-info { background: #3498db; color: white; }
        .badge-warning { background: #f1c40f; color: black; }
        .badge-danger { background: #e74c3c; color: white; }
    </style>
</head>
<body>
    <div class="admin-container">
        <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; border-bottom: 2px solid #FF6600; padding-bottom: 10px;">
            <h1 style="color: #2c3e50; margin: 0;">Gestionar Alertas e Inicio</h1>
            <a href="index.php" style="color: #FF6600; text-decoration: none; font-weight: bold;">← Volver al Panel</a>
        </header>

        <?php if (isset($_GET['msg'])): ?>
            <div style="background: #e8f5e9; color: #2e7d32; padding: 10px; border-radius: 5px; margin-bottom: 20px; text-align: center;">
                <?= htmlspecialchars($_GET['msg']) ?>
            </div>
        <?php endif; ?>

        <section class="alert-form">
            <h2 style="margin-top: 0; color: #FF6600; font-size: 1.2rem;">Nueva Alerta / Anuncio</h2>
            <form action="manage_alerts.php" method="POST" enctype="multipart/form-data">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Título</label>
                        <input type="text" name="titulo" style="width: 100%;" required placeholder="Ej: Inscripciones Abiertas">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px; font-weight: bold;">Tipo de Alerta</label>
                        <select name="tipo" style="width: 100%;">
                            <option value="info">Información (Azul)</option>
                            <option value="warning">Aviso (Amarillo)</option>
                            <option value="danger">Crítico (Rojo)</option>
                        </select>
                    </div>
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Mensaje</label>
                    <textarea name="mensaje" style="width: 100%; height: 80px;" required placeholder="Escriba el contenido del anuncio..."></textarea>
                </div>
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px; font-weight: bold;">Imagen (Opcional)</label>
                    <input type="file" name="imagen" accept="image/*">
                </div>
                <button type="submit" name="create" style="background: #FF6600; color: white; border: none; padding: 10px 25px; border-radius: 8px; font-weight: bold; cursor: pointer;">Publicar Alerta</button>
            </form>
        </section>

        <section>
            <h2 style="color: #2c3e50; font-size: 1.2rem; margin-bottom: 15px;">Alertas Activas</h2>
            <div style="background: white; border: 1px solid #eee; border-radius: 10px; overflow: hidden;">
                <?php if (empty($alertas)): ?>
                    <p style="padding: 20px; text-align: center; color: #999;">No hay alertas publicadas.</p>
                <?php else: ?>
                    <?php foreach ($alertas as $a): ?>
                        <div class="alert-list-item">
                            <img src="<?= $a['imagen_url'] ? '../' . $a['imagen_url'] : 'https://via.placeholder.com/80x60?text=Sin+Imagen' ?>" class="alert-img-preview">
                            <div style="flex-grow: 1;">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <h4 style="margin: 0; color: #2c3e50;"><?= htmlspecialchars($a['titulo']) ?></h4>
                                    <span class="badge badge-<?= $a['tipo'] ?>"><?= $a['tipo'] ?></span>
                                </div>
                                <p style="margin: 5px 0 0; color: #666; font-size: 0.9rem;"><?= htmlspecialchars(substr($a['mensaje'], 0, 100)) ?>...</p>
                            </div>
                            <a href="manage_alerts.php?delete=<?= $a['id'] ?>" class="btn-delete" onclick="return confirm('¿Seguro que deseas eliminar esta alerta?')">Eliminar</a>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>
    </div>

    <?php include 'footer.php'; ?>
</body>
</html>
