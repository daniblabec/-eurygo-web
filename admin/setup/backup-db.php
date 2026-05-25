<?php
/**
 * Backup de la base de datos — descarga directa como .sql.gz
 *
 * USO:
 *   /admin/setup/backup-db.php
 *     → dump completo de TODAS las tablas
 *
 *   /admin/setup/backup-db.php?tablas=cursos,cursos_programa,cursos_ediciones
 *     → solo las tablas indicadas (separadas por coma)
 *
 *   /admin/setup/backup-db.php?formato=sql
 *     → sin comprimir (texto plano .sql, más fácil de inspeccionar)
 *
 * Requiere sesión admin. El fichero se descarga al navegador y se guarda
 * donde el navegador descargue por defecto (normalmente /Downloads).
 *
 * RECOMENDACIÓN: ejecutar antes de cualquier update_v*.php que toque la BD.
 *   1. Abre esta URL → descarga el .sql.gz a /Downloads
 *   2. Muévelo a `eurygo-web/backups/` en tu local (la carpeta está en .gitignore)
 *   3. Ejecuta el update_vX.php
 *   4. Si algo sale mal: importa el dump en phpMyAdmin de OVH
 *
 * El dump genera SQL portable y compatible con phpMyAdmin / mysqldump:
 *   - DROP TABLE IF EXISTS + CREATE TABLE (de SHOW CREATE TABLE)
 *   - INSERTs uno por fila (sin batch — más lento pero más legible y resistente
 *     a max_allowed_packet bajos en OVH)
 *   - SET FOREIGN_KEY_CHECKS=0 al inicio y =1 al final
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requiere_login();

// BD pueden ser pesadas — sin límite de tiempo ni memoria estricto
@set_time_limit(0);
@ini_set('memory_limit', '512M');
ignore_user_abort(true);

$pdo = get_db();
$pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);

// ── Obtener lista de tablas existentes ──
$todas_tablas = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

// ── Filtrado por parámetro ?tablas= ──
$param_tablas = trim($_GET['tablas'] ?? '');
if ($param_tablas !== '') {
    $solicitadas = array_filter(array_map('trim', explode(',', $param_tablas)));
    // Validar formato (solo alfanuméricos + underscore) — defensa contra SQL injection
    foreach ($solicitadas as $t) {
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $t)) {
            http_response_code(400);
            exit('Nombre de tabla inválido: ' . htmlspecialchars($t));
        }
    }
    $tablas = array_values(array_intersect($todas_tablas, $solicitadas));
    if (empty($tablas)) {
        http_response_code(404);
        exit('Ninguna tabla coincide con: ' . htmlspecialchars($param_tablas));
    }
} else {
    $tablas = $todas_tablas;
}

// ── Formato: gzip por defecto, sql plano con ?formato=sql ──
$formato = $_GET['formato'] ?? 'gz';
$comprimido = $formato !== 'sql';

// ── Headers de descarga ──
$ts = date('Y-m-d_His');
$tags = $param_tablas ? '_' . preg_replace('/[^a-z0-9]+/i', '-', $param_tablas) : '';
$ext  = $comprimido ? 'sql.gz' : 'sql';
$filename = "eurygo_backup_{$ts}{$tags}.{$ext}";

header('Content-Type: ' . ($comprimido ? 'application/gzip' : 'application/sql'));
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');

// ── Apertura del stream (gzip directo a php://output o stdout) ──
if ($comprimido) {
    $out = gzopen('php://output', 'wb6');
    $write = function(string $s) use ($out) { gzwrite($out, $s); };
    $close = function() use ($out) { gzclose($out); };
} else {
    $write = function(string $s) { echo $s; };
    $close = function() {};
}

// ── Cabecera del dump ──
$write("-- ─────────────────────────────────────────────────\n");
$write("-- EuryGo MySQL dump\n");
$write("-- Generated: " . date('c') . "\n");
$write("-- Database:  " . DB_NAME . "\n");
$write("-- Tables:    " . count($tablas) . " (" . implode(', ', $tablas) . ")\n");
$write("-- ─────────────────────────────────────────────────\n\n");
$write("SET NAMES utf8mb4;\n");
$write("SET FOREIGN_KEY_CHECKS=0;\n");
$write("SET SQL_MODE='NO_AUTO_VALUE_ON_ZERO';\n\n");

// ── Por cada tabla ──
foreach ($tablas as $tabla) {
    // SHOW CREATE TABLE
    $row = $pdo->query("SHOW CREATE TABLE `$tabla`")->fetch(PDO::FETCH_ASSOC);
    $create_sql = $row['Create Table'] ?? null;
    if (!$create_sql) continue;

    $write("-- ─────────────────────────────────────────────────\n");
    $write("-- Table: `$tabla`\n");
    $write("-- ─────────────────────────────────────────────────\n");
    $write("DROP TABLE IF EXISTS `$tabla`;\n");
    $write($create_sql . ";\n\n");

    // INSERTs en streaming (cursor no buferizado)
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
        $write("INSERT INTO `$tabla` ($cols_sql) VALUES (" . implode(',', $vals) . ");\n");
        $contador++;
        // Flush periódico para no acumular en buffer
        if ($contador % 200 === 0) {
            @ob_flush();
            @flush();
        }
    }
    $stmt->closeCursor();
    $write("\n-- (" . $contador . " filas)\n\n");
}

$write("SET FOREIGN_KEY_CHECKS=1;\n");
$write("-- ─── Fin del dump ───\n");
$close();
exit;
