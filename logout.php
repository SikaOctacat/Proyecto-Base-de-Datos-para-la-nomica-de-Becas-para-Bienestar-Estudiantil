<?php
require 'db.php';
// destruir sesión y redirigir al index (landing pública)
session_unset();
session_destroy();
header('Location: index.php');
exit;
