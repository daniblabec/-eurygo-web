<?php
/**
 * Endpoint AJAX para subida de imágenes del inicio.
 * Recibe: file (imagen), posicion, alt_texto, titulo, csrf_token
 * Devuelve JSON con la nueva ruta o error.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requiere_login();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
    exit;
}

// CSRF
if (!verificar_csrf()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Token CSRF inválido. Recarga la página.']);
    exit;
}

$posicion  = $_POST['posicion'] ?? '';
$alt_texto = trim($_POST['alt_texto'] ?? '');
$titulo    = trim($_POST['titulo'] ?? '');

$posiciones_validas = ['hero', 'about', 'schools', 'agencies'];
if (!in_array($posicion, $posiciones_validas)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Posición no válida']);
    exit;
}

if ($alt_texto === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'El texto alternativo (alt) es obligatorio para SEO']);
    exit;
}

$db = get_db();

// Asegurar que existe el registro para esta posición
$stmt = $db->prepare("SELECT id FROM home_imagenes WHERE posicion = ?");
$stmt->execute([$posicion]);
if (!$stmt->fetch()) {
    $orden_map = ['hero' => 1, 'about' => 2, 'schools' => 3, 'agencies' => 4];
    $stmt = $db->prepare("INSERT INTO home_imagenes (posicion, titulo, alt_texto, activa, orden) VALUES (?, ?, ?, 1, ?)");
    $stmt->execute([$posicion, $titulo, $alt_texto, $orden_map[$posicion] ?? 0]);
}

// Si no hay archivo, solo actualizar textos
if (empty($_FILES['file']) || $_FILES['file']['error'] === UPLOAD_ERR_NO_FILE) {
    $stmt = $db->prepare("UPDATE home_imagenes SET alt_texto = ?, titulo = ? WHERE posicion = ?");
    $stmt->execute([$alt_texto, $titulo, $posicion]);
    echo json_encode(['ok' => true, 'mensaje' => 'Textos actualizados correctamente']);
    exit;
}

$file = $_FILES['file'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Error en la subida del archivo (código ' . $file['error'] . ')']);
    exit;
}

// Validar tamaño (5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'El archivo supera los 5MB']);
    exit;
}

// Validar MIME real
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$tipos_permitidos = ['image/jpeg', 'image/png', 'image/webp'];
if (!in_array($mime, $tipos_permitidos)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Tipo de archivo no permitido. Usa JPG, PNG o WEBP.']);
    exit;
}

// Validar que es realmente una imagen
$info = @getimagesize($file['tmp_name']);
if ($info === false) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'El archivo no es una imagen válida']);
    exit;
}

// Cargar imagen con GD
$img_src = null;
if ($mime === 'image/jpeg')      $img_src = @imagecreatefromjpeg($file['tmp_name']);
elseif ($mime === 'image/png')   $img_src = @imagecreatefrompng($file['tmp_name']);
elseif ($mime === 'image/webp')  $img_src = @imagecreatefromwebp($file['tmp_name']);

if (!$img_src) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'No se pudo procesar la imagen']);
    exit;
}

$w = imagesx($img_src);
$h = imagesy($img_src);

// Ancho máximo según posición
$max_ancho = ($posicion === 'hero') ? 1920 : 1200;

// Redimensionar si supera el máximo
if ($w > $max_ancho) {
    $new_h = (int)round($h * ($max_ancho / $w));
    $resized = imagecreatetruecolor($max_ancho, $new_h);
    // Preservar transparencia para PNG
    imagealphablending($resized, false);
    imagesavealpha($resized, true);
    imagecopyresampled($resized, $img_src, 0, 0, 0, 0, $max_ancho, $new_h, $w, $h);
    imagedestroy($img_src);
    $img_src = $resized;
}

// Directorio de destino
$homeDir = __DIR__ . '/../assets/images/home';
if (!is_dir($homeDir)) {
    mkdir($homeDir, 0755, true);
}

// Nombre: posicion-timestamp.webp
$nombre = $posicion . '-' . time() . '.webp';
$destino = $homeDir . '/' . $nombre;

// Convertir a WebP
$soporte_webp = function_exists('imagewebp');
if ($soporte_webp) {
    imagewebp($img_src, $destino, 85);
} else {
    // Fallback: guardar como JPEG
    $nombre = $posicion . '-' . time() . '.jpg';
    $destino = $homeDir . '/' . $nombre;
    imagejpeg($img_src, $destino, 90);
}
imagedestroy($img_src);

$ruta_web = '/assets/images/home/' . $nombre;

// Obtener imagen anterior para borrarla
$stmt = $db->prepare("SELECT ruta_imagen FROM home_imagenes WHERE posicion = ?");
$stmt->execute([$posicion]);
$anterior = $stmt->fetchColumn();

// Actualizar BD
$stmt = $db->prepare("
    UPDATE home_imagenes
    SET ruta_imagen = ?, alt_texto = ?, titulo = ?
    WHERE posicion = ?
");
$stmt->execute([$ruta_web, $alt_texto, $titulo, $posicion]);

// Verificar que la BD se actualizó
$stmt = $db->prepare("SELECT ruta_imagen FROM home_imagenes WHERE posicion = ?");
$stmt->execute([$posicion]);
$verificacion = $stmt->fetchColumn();
if ($verificacion !== $ruta_web) {
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) mkdir($logDir, 0755, true);
    error_log(
        '[' . date('Y-m-d H:i:s') . "] HOME IMG UPDATE FAILED — pos=$posicion ruta=$ruta_web db_value=" . var_export($verificacion, true) . PHP_EOL,
        3, $logDir . '/upload.log'
    );
}

// Borrar imagen anterior del servidor
if ($anterior && $anterior !== $ruta_web) {
    $ruta_anterior = __DIR__ . '/..' . $anterior;
    if (file_exists($ruta_anterior) && is_file($ruta_anterior)) {
        @unlink($ruta_anterior);
    }
}

echo json_encode([
    'ok'      => true,
    'mensaje' => 'Imagen actualizada correctamente',
    'ruta'    => $ruta_web,
]);
