<?php
/**
 * EuryGo — Migración: tabla curso_fotos para los sliders
 *
 * Ejecutar UNA VEZ en: /admin/setup/update_sliders.php
 * Borrar el archivo después por seguridad.
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requiere_login();

$db = get_db();
$resultados = [];

// ─── Tabla curso_fotos ───
try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS curso_fotos (
          id             INT AUTO_INCREMENT PRIMARY KEY,
          curso_id       INT NOT NULL,
          nombre_archivo VARCHAR(255) NOT NULL,
          alt_text       VARCHAR(255) DEFAULT '',
          orden          TINYINT UNSIGNED DEFAULT 0,
          activa         TINYINT(1) DEFAULT 1,
          fecha_subida   DATETIME DEFAULT CURRENT_TIMESTAMP,
          INDEX idx_curso (curso_id),
          INDEX idx_orden (curso_id, orden),
          FOREIGN KEY (curso_id) REFERENCES cursos(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $resultados[] = ['ok', 'Tabla curso_fotos creada/verificada'];
} catch (PDOException $e) {
    $resultados[] = ['error', 'Error: ' . $e->getMessage()];
}

// ─── Directorio uploads/cursos/ ───
$dir = __DIR__ . '/../../uploads/cursos';
if (!is_dir($dir)) {
    if (@mkdir($dir, 0755, true)) {
        $resultados[] = ['ok', 'Directorio uploads/cursos/ creado'];
    } else {
        $resultados[] = ['error', 'No se pudo crear uploads/cursos/ — créalo manualmente con permisos 755'];
    }
} else {
    $resultados[] = ['skip', 'Directorio uploads/cursos/ ya existía'];
}

// ─── .htaccess en uploads/cursos para bloquear PHP (compatible PHP-FPM) ───
$htaccess = $dir . '/.htaccess';
$contenido = "# Bloquear ejecución PHP en uploads (compatible PHP-FPM y mod_php)\n"
           . "<FilesMatch \"\\.(php|phtml|phar|php3|php4|php5|php7|php8|pht|cgi|pl|py|rb|sh)$\">\n"
           . "  Require all denied\n"
           . "</FilesMatch>\n"
           . "<IfModule mod_mime.c>\n"
           . "  RemoveHandler .php .phtml .phar .php3 .php4 .php5 .php7 .php8 .pht\n"
           . "  RemoveType    .php .phtml .phar .php3 .php4 .php5 .php7 .php8 .pht\n"
           . "</IfModule>\n"
           . "<IfModule mod_headers.c>\n"
           . "  Header set X-Content-Type-Options \"nosniff\"\n"
           . "</IfModule>\n";
// Sobrescribir siempre para corregir versiones antiguas que rompían el server
if (@file_put_contents($htaccess, $contenido) !== false) {
    $resultados[] = ['ok', '.htaccess de seguridad creado/actualizado en uploads/cursos/'];
} else {
    $resultados[] = ['error', 'No se pudo crear .htaccess — créalo manualmente con permisos 644'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Migración Sliders — EuryGo</title>
  <style>
    body { font-family: system-ui, sans-serif; max-width: 720px; margin: 2rem auto; padding: 1rem; }
    .result { padding: 0.5rem 1rem; margin: 0.5rem 0; border-radius: 6px; }
    .result.ok { background: #dcfce7; color: #166534; }
    .result.error { background: #fee2e2; color: #991b1b; }
    .result.skip { background: #f3f4f6; color: #6b7280; }
  </style>
</head>
<body>
  <h1>Migración Sliders — EuryGo</h1>
  <?php foreach ($resultados as [$tipo, $msg]): ?>
  <div class="result <?= $tipo ?>">
    <?= $tipo === 'ok' ? '✅' : ($tipo === 'skip' ? '↪️' : '❌') ?>
    <?= htmlspecialchars($msg) ?>
  </div>
  <?php endforeach; ?>
  <div style="margin-top:2rem; padding:1rem; background:#fef3c7; border-radius:6px;">
    <strong>⚠️ Borra este archivo después de ejecutarlo:</strong><br>
    <code>admin/setup/update_sliders.php</code>
  </div>
  <p style="margin-top:1rem;"><a href="/admin/cursos.php">&larr; Ir a gestión de cursos</a></p>
</body>
</html>
