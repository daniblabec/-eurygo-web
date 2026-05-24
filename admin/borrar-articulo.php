<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requiere_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verificar_csrf()) {
    header('Location: /admin/articulos.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id > 0) {
    $db = get_db();
    $stmt = $db->prepare("DELETE FROM articulos WHERE id = :id");
    $stmt->execute([':id' => $id]);
}

header('Location: /admin/articulos.php?msg=borrado');
exit;
