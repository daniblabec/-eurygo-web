<?php
/**
 * API — Actualizar alt text de una foto (AJAX)
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requiere_login();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

$token = $data['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'CSRF']);
    exit;
}

$id  = (int)($data['id'] ?? 0);
$alt = trim($data['alt'] ?? '');
if (mb_strlen($alt) > 255) $alt = mb_substr($alt, 0, 255);

if ($id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'ID inválido']);
    exit;
}

try {
    $db = get_db();
    $stmt = $db->prepare("UPDATE curso_fotos SET alt_text = ? WHERE id = ?");
    $stmt->execute([$alt, $id]);
    echo json_encode(['ok' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'DB error']);
}
