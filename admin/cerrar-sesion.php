<?php
require_once __DIR__ . '/../includes/auth.php';

iniciar_sesion_segura();
$_SESSION = [];
session_destroy();
header('Location: /admin/login.php');
exit;
