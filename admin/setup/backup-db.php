<?php
/**
 * Backup de la base de datos — descarga como .sql.gz o .sql
 *
 * USO:
 *   /admin/setup/backup-db.php
 *     → dump completo de TODAS las tablas (.sql.gz)
 *
 *   /admin/setup/backup-db.php?tablas=cursos,cursos_programa,cursos_ediciones
 *     → solo las tablas indicadas (separadas por coma)
 *
 *   /admin/setup/backup-db.php?formato=sql
 *     → sin comprimir (texto plano .sql)
 *
 * Requiere sesión admin. Recomendado antes de cualquier update_v*.php
 * que toque la BD.
 *
 * NOTA TÉCNICA: el dump se construye en memoria (no streaming) para
 * evitar conflictos con output_buffering y zlib.output_compression que
 * suelen estar activos en hosting OVH. Para BDs muy grandes (>100MB)
 * podría dar OOM — en ese caso bajar memory_limit a la realidad del
 * hosting y dumpear por tablas con ?tablas=...
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requiere_login();

// Resetear todo posible output previo + desactivar zlib auto-compression
@ini_set('zlib.output_compression', '0');
while (ob_get_level()) {
    ob_end_clean();
}

@set_time_limit(300);
@ini_set('memory_limit', '1024M');
ignore_user_abort(false);

try {
    $pdo = get_db();
} catch (Throwable $e) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    exit('Error de conexión a BD: ' . $e->getMessage());
}

// ── Lista de tablas existentes ──
$todas_tablas = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

// ── Filtrado por parámetro ?tablas= ──
$param_tablas = trim($_GET['tablas'] ?? '');
if ($param_tablas !== '') {
    $solicitadas = array_filter(array_map('trim', explode(',', $param_tablas)));
    foreach ($solicitadas as $t) {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $t)) {
            http_response_code(400);
            header('Content-Type: text/plain; charset=utf-8');
            exit('Nombre de tabla inválido: ' . htmlspecialchars($t));
        }
    }
    $tablas = array_values(array_intersect($todas_tablas, $solicitadas));
    if (empty($tablas)) {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        exit('Ninguna tabla coincide con: ' . htmlspecialchars($param_tablas));
    }
} else {
    $tablas = $todas_tablas;
}

// ── Formato: gzip por defecto, sql plano con ?formato=sql ──
$formato    = $_GET['formato'] ?? 'gz';
$comprimido = $formato !== 'sql';

// ── Construir el dump COMPLETO en memoria ──
$sql  = "-- ─────────────────────────────────────────────────\n";
$sql .= "-- EuryGo MySQL dump\n";
$sql .= "-- Generated: " . date('c') . "\n";
$sql .= "-- Database:  " . DB_NAME . "\n";
$sql .= "-- Tables:    " . count($tablas) . " (" . implode(', ', $tablas) . ")\n";
$sql .= "-- ─────────────────────────────────────────────────\n\n";
$sql .= "SET NAMES utf8mb4;\n";
$sql .= "SET FOREIGN_KEY_CHECKS=0;\n";
$sql .= "SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n";

foreach ($tablas as $tabla) {
    $row = $pdo->query("SHOW CREATE TABLE `$tabla`")->fetch(PDO::FETCH_ASSOC);
    $create_sql = $row['Create Table'] ?? null;
    if (!$create_sql) continue;

    $sql .= "-- ─────────────────────────────────────────────────\n";
    $sql .= "-- Table: `$tabla`\n";
    $sql .= "-- ─────────────────────────────────────────────────\n";
    $sql .= "DROP TABLE IF EXISTS `$tabla`;\n";
    $sql .= $create_sql . ";\n\n";

    $stmt = $pdo->query("SELECT * FROM `$tabla`");
    $cols_sql = null;
    $contador = 0;
    while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($cols_sql === null) {
            $cols = array_keys($r);
            $cols_sql = '`' . implode('`,`', $cols) . '`';
        }
        $vals = [];
        foreach ($r as $v) {
            if ($v === null) {
                $vals[] = 'NULL';
            } elseif (is_int($v) || is_float($v)) {
                $vals[] = (string)$v;
            } else {
                $vals[] = $pdo->quote((string)$v);
            }
        }
        $sql .= "INSERT INTO `$tabla` ($cols_sql) VALUES (" . implode(',', $vals) . ");\n";
        $contador++;
    }
    $stmt->closeCursor();
    $sql .= "\n-- ($contador filas)\n\n";
}

$sql .= "SET FOREIGN_KEY_CHECKS=1;\n";
$sql .= "-- ─── Fin del dump ───\n";

// ── Preparar payload final + cabeceras ──
$ts       = date('Y-m-d_His');
$tags     = $param_tablas ? '_' . preg_replace('/[^a-z0-9]+/i', '-', $param_tablas) : '';
$ext      = $comprimido ? 'sql.gz' : 'sql';
$filename = "eurygo_backup_{$ts}{$tags}.{$ext}";

if ($comprimido) {
    $payload      = gzencode($sql, 6);
    $content_type = 'application/gzip';
} else {
    $payload      = $sql;
    $content_type = 'application/sql; charset=utf-8';
}
unset($sql);

header('Content-Type: ' . $content_type);
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($payload));
header('Content-Transfer-Encoding: binary');
header('Cache-Control: no-store, no-cache, must-revalidate, private');
header('Pragma: no-cache');
header('Expires: 0');
// Forzar identity para que mod_deflate/zlib no recomprima
header('Content-Encoding: identity');

echo $payload;
exit;
