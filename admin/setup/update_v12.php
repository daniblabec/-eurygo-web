<?php
/**
 * ACTUALIZACIÓN v12 — EuryGo: Imagen específica para la miniatura de la home
 *
 * Cambios:
 * 1. Añade columna `imagen_home` a la tabla `cursos`.
 *    Se usa en la home (sección "Próximas convocatorias en Jerez") como
 *    miniatura grande del curso protagonista. Permite subir una foto
 *    distinta (horizontal) sin tocar la imagen de portada del curso.
 *
 * USO:
 *   1. Sube este archivo por FTP a /www/admin/setup/
 *   2. Inicia sesión en /admin/
 *   3. Abre https://www.eurygo.com/admin/setup/update_v12.php
 *   4. Cuando veas el ✅, BORRA este archivo del servidor por seguridad.
 *
 * Ejecutar UNA SOLA VEZ. Idempotente: si la columna ya existe, no hace nada.
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requiere_login();

$db = get_db();
$errores = [];
$pasos = 0;

// ─── PASO 1: Añadir columna imagen_home a cursos ───
try {
    $col = $db->query("SHOW COLUMNS FROM cursos LIKE 'imagen_home'")->fetch();
    if (!$col) {
        $db->exec("ALTER TABLE cursos ADD COLUMN imagen_home VARCHAR(255) NULL DEFAULT NULL AFTER imagen");
        echo "<p>✅ Columna <code>imagen_home</code> añadida a la tabla <code>cursos</code>.</p>";
    } else {
        echo "<p>ℹ️ La columna <code>imagen_home</code> ya existe en <code>cursos</code> — no se ha hecho nada.</p>";
    }
    $pasos++;
} catch (Throwable $e) {
    $errores[] = "PASO 1: " . $e->getMessage();
}

// ─── RESULTADO ───
echo "<hr>";
if (empty($errores)) {
    echo "<h2 style='color:green;'>✅ Migración v12 completada — {$pasos} paso(s)</h2>";
    echo "<p>Ya puedes subir una <strong>Imagen para la home</strong> desde <code>/admin/ → Cursos → Editar curso → pestaña Imagen</code>.</p>";
    echo "<p><strong>IMPORTANTE:</strong> Borra este archivo del servidor después de ejecutarlo.</p>";
} else {
    echo "<h2 style='color:red;'>⚠️ Errores:</h2><ul>";
    foreach ($errores as $err) echo "<li>" . htmlspecialchars($err) . "</li>";
    echo "</ul>";
}
