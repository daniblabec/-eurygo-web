<?php
/**
 * Endpoint AJAX para subida de imágenes desde TinyMCE y portada.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requiere_login();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

if (empty($_FILES['file'])) {
    http_response_code(400);
    echo json_encode(['error' => 'No se recibió archivo']);
    exit;
}

$file = $_FILES['file'];

// Validar MIME real
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

if (!in_array($mime, ALLOWED_TYPES)) {
    http_response_code(400);
    echo json_encode(['error' => 'Tipo de archivo no permitido']);
    exit;
}

if ($file['size'] > MAX_FILE_SIZE) {
    http_response_code(400);
    echo json_encode(['error' => 'Archivo demasiado grande (máx 5MB)']);
    exit;
}

// Nombre único
$nombre = uniqid() . '-' . substr(md5($file['name']), 0, 8) . '.webp';
$destino = UPLOAD_DIR . $nombre;

if (!is_dir(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0755, true);
}

// Cargar imagen
$img_src = null;
if ($mime === 'image/jpeg') $img_src = imagecreatefromjpeg($file['tmp_name']);
elseif ($mime === 'image/png') $img_src = imagecreatefrompng($file['tmp_name']);
elseif ($mime === 'image/webp') $img_src = imagecreatefromwebp($file['tmp_name']);

if (!$img_src) {
    http_response_code(500);
    echo json_encode(['error' => 'No se pudo procesar la imagen']);
    exit;
}

$w = imagesx($img_src);
$h = imagesy($img_src);

// Redimensionar si supera 1200px
if ($w > 1200) {
    $new_h = (int)round($h * (1200 / $w));
    $resized = imagecreatetruecolor(1200, $new_h);
    imagecopyresampled($resized, $img_src, 0, 0, 0, 0, 1200, $new_h, $w, $h);
    imagewebp($resized, $destino, 85);
    imagedestroy($resized);
} else {
    imagewebp($img_src, $destino, 85);
}
imagedestroy($img_src);

echo json_encode(['location' => UPLOAD_URL . $nombre]);
