<?php
/**
 * API — Eliminar una foto (BD + archivos físicos)
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

$id = (int)($data['id'] ?? 0);
if ($id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'ID inválido']);
    exit;
}

try {
    $db = get_db();
    $stmt = $db->prepare("SELECT curso_id, nombre_archivo FROM curso_fotos WHERE id = ?");
    $stmt->execute([$id]);
    $foto = $stmt->fetch();

    if (!$foto) {
        echo json_encode(['ok' => false, 'error' => 'No existe']);
        exit;
    }

    // Borrar archivos físicos
    $dir = __DIR__ . '/../../uploads/cursos/' . (int)$foto['curso_id'] . '/';
    $nombre = basename($foto['nombre_archivo']); // anti path traversal
    $thumb  = str_replace('foto_', 'thumb_', $nombre);
    @unlink($dir . $nombre);
    @unlink($dir . $thumb);

    // Borrar registro
    $db->prepare("DELETE FROM curso_fotos WHERE id = ?")->execute([$id]);

    echo json_encode(['ok' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'DB error']);
}
