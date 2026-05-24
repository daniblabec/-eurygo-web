<?php
/**
 * API — Subir / eliminar imagen de portada de un curso.
 *
 * POST multipart/form-data
 *   accion      : 'subir' | 'eliminar'
 *   curso_id    : int (PK de cursos)
 *   csrf_token  : token CSRF
 *   imagen      : file (solo si accion=subir)
 *
 * Devuelve JSON. Requiere sesión admin.
 *
 * Reutiliza el campo cursos.imagen (no se crea columna nueva).
 * Guarda en /assets/images/blog/ con nombre aleatorio + timestamp.
 * Imagen resize 800x500 con crop centrado, JPEG 85.
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

// CSRF
$token = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'CSRF']);
    exit;
}

$accion   = $_POST['accion'] ?? '';
$curso_id = (int)($_POST['curso_id'] ?? 0);

if (!in_array($accion, ['subir', 'eliminar'], true) || $curso_id <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Parámetros inválidos']);
    exit;
}

$db = get_db();

// Verificar que el curso existe + obtener imagen actual
$stmt = $db->prepare("SELECT id, imagen FROM cursos WHERE id = ? LIMIT 1");
$stmt->execute([$curso_id]);
$curso = $stmt->fetch();
if (!$curso) {
    echo json_encode(['ok' => false, 'error' => 'Curso no encontrado']);
    exit;
}

$dir_fisico = realpath(__DIR__ . '/../../assets/images/blog');
if ($dir_fisico === false) {
    // si no existe, lo creamos
    $dir_fisico = __DIR__ . '/../../assets/images/blog';
    if (!is_dir($dir_fisico)) @mkdir($dir_fisico, 0755, true);
    $dir_fisico = realpath($dir_fisico);
}
$prefijo_url = '/assets/images/blog/';

/** Borra el fichero físico de la portada anterior si vive en nuestra carpeta. */
function borrar_portada_anterior(?string $ruta_db, string $dir_fisico, string $prefijo_url): void {
    if (!$ruta_db) return;
    if (strpos($ruta_db, $prefijo_url) !== 0) return;
    $base = basename($ruta_db);
    // anti path-traversal: basename ya neutraliza ../, pero validamos al final
    $abs = $dir_fisico . DIRECTORY_SEPARATOR . $base;
    $abs_real = realpath($abs);
    if ($abs_real && strpos($abs_real, $dir_fisico . DIRECTORY_SEPARATOR) === 0 && is_file($abs_real)) {
        @unlink($abs_real);
    }
}

// ───────────────────────────────────────────────────────────────
//  ELIMINAR
// ───────────────────────────────────────────────────────────────
if ($accion === 'eliminar') {
    borrar_portada_anterior($curso['imagen'], $dir_fisico, $prefijo_url);
    $db->prepare("UPDATE cursos SET imagen = NULL WHERE id = ?")->execute([$curso_id]);
    echo json_encode(['ok' => true, 'mensaje' => 'Portada eliminada', 'imagen' => null]);
    exit;
}

// ───────────────────────────────────────────────────────────────
//  SUBIR
// ───────────────────────────────────────────────────────────────
if (!isset($_FILES['imagen']) || $_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['ok' => false, 'error' => 'No se ha recibido la imagen']);
    exit;
}

$file = $_FILES['imagen'];
$max_size = 3 * 1024 * 1024; // 3MB

if ($file['size'] > $max_size) {
    echo json_encode(['ok' => false, 'error' => 'Máximo 3 MB']);
    exit;
}

// MIME real (no confiar en $_FILES['type'] del cliente)
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime  = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$tipos_ok = ['image/jpeg', 'image/png', 'image/webp'];
if (!in_array($mime, $tipos_ok, true)) {
    echo json_encode(['ok' => false, 'error' => 'Solo JPG, PNG o WEBP']);
    exit;
}

// Cargar la imagen con GD según MIME
try {
    switch ($mime) {
        case 'image/jpeg': $src = @imagecreatefromjpeg($file['tmp_name']); break;
        case 'image/png':  $src = @imagecreatefrompng($file['tmp_name']);  break;
        case 'image/webp': $src = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($file['tmp_name']) : false; break;
        default:           $src = false;
    }
    if (!$src) {
        echo json_encode(['ok' => false, 'error' => 'No se pudo procesar la imagen']);
        exit;
    }

    $w_src = imagesx($src);
    $h_src = imagesy($src);

    $w_dst = 800;
    $h_dst = 500;
    $ratio_dst = $w_dst / $h_dst;       // 1.6
    $ratio_src = $w_src / $h_src;

    if ($ratio_src > $ratio_dst) {
        // Imagen más ancha → recortamos por los lados
        $crop_h = $h_src;
        $crop_w = (int)round($h_src * $ratio_dst);
        $crop_x = (int)round(($w_src - $crop_w) / 2);
        $crop_y = 0;
    } else {
        // Imagen más alta → recortamos arriba/abajo
        $crop_w = $w_src;
        $crop_h = (int)round($w_src / $ratio_dst);
        $crop_x = 0;
        $crop_y = (int)round(($h_src - $crop_h) / 2);
    }

    $dst = imagecreatetruecolor($w_dst, $h_dst);
    // Fondo blanco por si la fuente tiene transparencia (PNG/WebP)
    $blanco = imagecolorallocate($dst, 255, 255, 255);
    imagefilledrectangle($dst, 0, 0, $w_dst, $h_dst, $blanco);

    imagecopyresampled($dst, $src, 0, 0, $crop_x, $crop_y, $w_dst, $h_dst, $crop_w, $crop_h);

    // Nombre aleatorio + timestamp — nunca usar el del cliente
    $nombre = sprintf('curso-%d-portada-%d-%s.jpg', $curso_id, time(), bin2hex(random_bytes(4)));
    $abs    = $dir_fisico . DIRECTORY_SEPARATOR . $nombre;

    if (!imagejpeg($dst, $abs, 85)) {
        imagedestroy($src); imagedestroy($dst);
        echo json_encode(['ok' => false, 'error' => 'No se pudo guardar la imagen']);
        exit;
    }

    imagedestroy($src);
    imagedestroy($dst);

    // Borrar la portada anterior (después de guardar la nueva con éxito)
    borrar_portada_anterior($curso['imagen'], $dir_fisico, $prefijo_url);

    $ruta_db = $prefijo_url . $nombre;
    $db->prepare("UPDATE cursos SET imagen = ? WHERE id = ?")->execute([$ruta_db, $curso_id]);

    echo json_encode(['ok' => true, 'imagen' => $ruta_db, 'mensaje' => 'Portada actualizada']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Error procesando la imagen']);
}
