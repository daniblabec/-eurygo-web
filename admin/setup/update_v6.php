<?php
/**
 * ACTUALIZACIÓN v6 — EuryGo: Vincular traducciones de cursos
 *
 * Cambios:
 * 1. Añade columna traduccion_id a la tabla cursos
 * 2. Vincula los pares ES/EN existentes
 *
 * Ejecutar UNA SOLA VEZ desde el navegador y BORRAR después.
 */

require_once __DIR__ . '/../../includes/db.php';

$db = get_db();
$errores = [];
$pasos = 0;

// ─── PASO 1: Añadir columna traduccion_id ───
try {
    $col = $db->query("SHOW COLUMNS FROM cursos LIKE 'traduccion_id'")->fetch();
    if (!$col) {
        $db->exec("ALTER TABLE cursos ADD COLUMN traduccion_id INT NULL DEFAULT NULL AFTER idioma");
        echo "<p>✅ Columna traduccion_id añadida a cursos.</p>";
    } else {
        echo "<p>ℹ️ Columna traduccion_id ya existe.</p>";
    }
    $pasos++;
} catch (Throwable $e) {
    $errores[] = "PASO 1: " . $e->getMessage();
}

// ─��─ PASO 2: Vincular pares ES/EN por slug ───
$pares = [
    ['sistema-educativo-espanol%', 'spanish-education-system%'],
    ['espanol-para-docentes%',    'spanish-language-for-teachers%'],
    ['educacion-inclusiva%',      'inclusive-education%'],
];

try {
    foreach ($pares as $par) {
        // Buscar curso ES
        $stmt = $db->prepare("SELECT id, slug FROM cursos WHERE slug LIKE :slug AND idioma = 'es' LIMIT 1");
        $stmt->execute([':slug' => $par[0]]);
        $es = $stmt->fetch();

        // Buscar curso EN
        $stmt = $db->prepare("SELECT id, slug FROM cursos WHERE slug LIKE :slug AND idioma = 'en' LIMIT 1");
        $stmt->execute([':slug' => $par[1]]);
        $en = $stmt->fetch();

        if ($es && $en) {
            $db->prepare("UPDATE cursos SET traduccion_id = :tid WHERE id = :id")
               ->execute([':tid' => $en['id'], ':id' => $es['id']]);
            $db->prepare("UPDATE cursos SET traduccion_id = :tid WHERE id = :id")
               ->execute([':tid' => $es['id'], ':id' => $en['id']]);
            echo "<p>✅ Vinculados: {$es['slug']} ↔ {$en['slug']}</p>";
        } else {
            echo "<p>⚠️ Par no encontrado: {$par[0]} / {$par[1]}</p>";
        }
    }
    $pasos++;
} catch (Throwable $e) {
    $errores[] = "PASO 2: " . $e->getMessage();
}

// ─── RESULTADO ───
echo "<hr>";
if (empty($errores)) {
    echo "<h2 style='color:green;'>✅ Migración v6 completada — {$pasos} pasos</h2>";
    echo "<p><strong>IMPORTANTE:</strong> Borra este archivo del servidor.</p>";
} else {
    echo "<h2 style='color:red;'>⚠️ Errores:</h2><ul>";
    foreach ($errores as $err) echo "<li>{$err}</li>";
    echo "</ul>";
}
