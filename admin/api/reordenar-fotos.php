<?php
/**
 * API — Reordenar fotos de un curso (AJAX)
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

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

// CSRF
$token = $data['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'CSRF']);
    exit;
}

$orden = $data['orden'] ?? [];
if (!is_array($orden)) {
    echo json_encode(['ok' => false, 'error' => 'Datos inválidos']);
    exit;
}

try {
    $db = get_db();
    $stmt = $db->prepare("UPDATE curso_fotos SET orden = ? WHERE id = ?");
    $db->beginTransaction();
    foreach ($orden as $item) {
        $id    = (int)($item['id'] ?? 0);
        $pos   = (int)($item['orden'] ?? 0);
        if ($id > 0) $stmt->execute([$pos, $id]);
    }
    $db->commit();
    echo json_encode(['ok' => true]);
} catch (PDOException $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'DB error']);
}
