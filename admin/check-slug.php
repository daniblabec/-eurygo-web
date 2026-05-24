<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requiere_login();

header('Content-Type: application/json');

$slug = $_GET['slug'] ?? '';
$idioma = $_GET['idioma'] ?? 'es';
$id = (int)($_GET['id'] ?? 0);

$db = get_db();
$sql = "SELECT id FROM articulos WHERE slug = :s AND idioma = :i";
$params = [':s' => $slug, ':i' => $idioma];
if ($id > 0) {
    $sql .= " AND id != :id";
    $params[':id'] = $id;
}
$stmt = $db->prepare($sql);
$stmt->execute($params);

echo json_encode(['disponible' => !$stmt->fetch()]);
