<?php
/**
 * DIAGNÓSTICO — Estado de cursos e imagen_home
 *
 * Script de SOLO LECTURA. No modifica nada.
 * Sirve para entender por qué la sección de cursos de la home no aparece
 * después de la migración v12.
 *
 * USO:
 *   1. Sube este archivo por FTP a /www/admin/setup/
 *   2. Inicia sesión en /admin/
 *   3. Abre https://www.eurygo.com/admin/setup/diagnostico-cursos.php
 *   4. Comparte el resultado.
 *   5. BORRA este archivo después.
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requiere_login();

$db = get_db();
header('Content-Type: text/html; charset=utf-8');
echo "<style>body{font-family:system-ui,sans-serif;max-width:1100px;margin:1rem auto;padding:0 1rem} table{border-collapse:collapse;width:100%;margin:0.5rem 0;font-size:0.9rem} th,td{padding:6px 10px;border:1px solid #cbd5e1;text-align:left;vertical-align:top} th{background:#f1f5f9} h2{margin-top:2rem;color:#0c4a6e} .ok{color:#16a34a} .err{color:#dc2626} .warn{color:#d97706} code{background:#f1f5f9;padding:2px 5px;border-radius:3px}</style>";

echo "<h1>Diagnóstico cursos · imagen_home</h1>";
echo "<p>Hoy en el servidor: <code>" . date('Y-m-d H:i:s') . "</code></p>";

// ─── 1) ¿Existe la columna imagen_home? ─────────────────────────────────
echo "<h2>1. ¿Existe la columna <code>imagen_home</code>?</h2>";
try {
    $col = $db->query("SHOW COLUMNS FROM cursos LIKE 'imagen_home'")->fetch();
    if ($col) {
        echo "<p class='ok'>✅ La columna EXISTE en la tabla <code>cursos</code>.</p>";
        echo "<table><tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th></tr>";
        echo "<tr><td>" . htmlspecialchars($col['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($col['Default'] ?? 'NULL') . "</td></tr></table>";
    } else {
        echo "<p class='err'>❌ La columna NO existe — hay que ejecutar <code>update_v12.php</code> antes.</p>";
    }
} catch (Throwable $e) {
    echo "<p class='err'>❌ Error consultando estructura: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// ─── 2) Lista completa de cursos ────────────────────────────────────────
echo "<h2>2. Todos los cursos en la BD</h2>";
try {
    $cursos = $db->query("SELECT id, titulo, idioma, estado, imagen, imagen_home FROM cursos ORDER BY idioma, id")->fetchAll();
    if (empty($cursos)) {
        echo "<p class='warn'>⚠️ No hay cursos en la BD.</p>";
    } else {
        echo "<table><tr><th>ID</th><th>Idioma</th><th>Estado</th><th>Título</th><th>imagen</th><th>imagen_home</th></tr>";
        foreach ($cursos as $c) {
            $img = $c['imagen'] ?? '';
            $imgh = $c['imagen_home'] ?? '';
            echo "<tr>";
            echo "<td>" . $c['id'] . "</td>";
            echo "<td>" . htmlspecialchars($c['idioma']) . "</td>";
            echo "<td>" . htmlspecialchars($c['estado']) . "</td>";
            echo "<td>" . htmlspecialchars(mb_substr($c['titulo'], 0, 60)) . "</td>";
            echo "<td>" . ($img ? htmlspecialchars(basename($img)) : "<span class='warn'>—</span>") . "</td>";
            echo "<td>" . ($imgh ? "<span class='ok'>" . htmlspecialchars(basename($imgh)) . "</span>" : "<span class='warn'>—</span>") . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Throwable $e) {
    echo "<p class='err'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// ─── 3) Ediciones futuras y abiertas ────────────────────────────────────
echo "<h2>3. Ediciones de cursos (todas)</h2>";
echo "<p>El query de la home busca ediciones con <code>estado='abierta'</code> y <code>fecha_inicio &gt;= hoy</code>.</p>";
try {
    $ed = $db->query("
        SELECT ce.id, ce.curso_id, ce.fecha_inicio, ce.fecha_fin, ce.estado, ce.plazas_disponibles,
               c.titulo, c.idioma, c.estado AS curso_estado
        FROM cursos_ediciones ce
        LEFT JOIN cursos c ON c.id = ce.curso_id
        ORDER BY ce.fecha_inicio
    ")->fetchAll();
    if (empty($ed)) {
        echo "<p class='warn'>⚠️ No hay ninguna edición registrada en <code>cursos_ediciones</code>.</p>";
    } else {
        echo "<table><tr><th>Curso (idioma)</th><th>Inicio</th><th>Fin</th><th>Estado edición</th><th>Estado curso</th><th>Plazas</th><th>¿Cuenta para home?</th></tr>";
        $hoy = date('Y-m-d');
        foreach ($ed as $e) {
            $cuenta = ($e['estado'] === 'abierta' && $e['fecha_inicio'] >= $hoy && $e['curso_estado'] === 'publicado' && $e['idioma'] === 'es');
            echo "<tr>";
            echo "<td>" . htmlspecialchars(($e['titulo'] ?? '?') . " (" . ($e['idioma'] ?? '?') . ")") . "</td>";
            echo "<td>" . $e['fecha_inicio'] . "</td>";
            echo "<td>" . $e['fecha_fin'] . "</td>";
            echo "<td>" . htmlspecialchars($e['estado']) . "</td>";
            echo "<td>" . htmlspecialchars($e['curso_estado'] ?? '?') . "</td>";
            echo "<td>" . $e['plazas_disponibles'] . "</td>";
            echo "<td>" . ($cuenta ? "<span class='ok'>✅ Sí</span>" : "<span class='warn'>No</span>") . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Throwable $e) {
    echo "<p class='err'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// ─── 4) Simular el query exacto de la home (ES) ─────────────────────────
echo "<h2>4. Simulación del query principal de la home (ES)</h2>";
try {
    $stmt = $db->query("
        SELECT
            ce.fecha_inicio, ce.fecha_fin, ce.plazas_disponibles,
            c.id AS curso_id, c.titulo, c.slug, c.extracto,
            c.precio, c.duracion_dias, c.ubicacion, c.imagen, c.imagen_home
        FROM cursos_ediciones ce
        JOIN cursos c ON c.id = ce.curso_id
        WHERE ce.estado = 'abierta'
          AND ce.fecha_inicio >= CURDATE()
          AND c.estado = 'publicado'
          AND c.idioma = 'es'
        ORDER BY ce.fecha_inicio ASC
    ");
    $rows = $stmt->fetchAll();
    if (empty($rows)) {
        echo "<p class='warn'>⚠️ El query principal devuelve <strong>0 filas</strong>. Es por eso que la home cae al fallback o al empty state.</p>";
    } else {
        echo "<p class='ok'>✅ El query principal devuelve " . count($rows) . " fila(s).</p>";
        echo "<table><tr><th>Curso</th><th>Inicio</th><th>imagen</th><th>imagen_home</th></tr>";
        foreach ($rows as $r) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($r['titulo']) . "</td>";
            echo "<td>" . $r['fecha_inicio'] . "</td>";
            echo "<td>" . ($r['imagen'] ? htmlspecialchars(basename($r['imagen'])) : '—') . "</td>";
            echo "<td>" . ($r['imagen_home'] ? htmlspecialchars(basename($r['imagen_home'])) : '—') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Throwable $e) {
    echo "<p class='err'>❌ ERROR en query principal: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>👉 Este error es seguramente la causa del empty state.</p>";
}

// ─── 5) Simular el query de fallback (publicados sin edición) ───────────
echo "<h2>5. Simulación del query de fallback</h2>";
try {
    $stmt = $db->query("
        SELECT
            c.fecha_inicio, c.fecha_fin,
            GREATEST(COALESCE(c.plazas, 0) - COALESCE(c.inscritos, 0), 0) AS plazas_disponibles,
            c.id AS curso_id, c.titulo, c.slug, c.extracto,
            c.precio, c.duracion_dias, c.ubicacion, c.imagen, c.imagen_home
        FROM cursos c
        WHERE c.estado = 'publicado'
          AND c.idioma = 'es'
        ORDER BY (c.fecha_inicio IS NULL), c.fecha_inicio ASC, c.id ASC
        LIMIT 3
    ");
    $rows = $stmt->fetchAll();
    if (empty($rows)) {
        echo "<p class='err'>⚠️ El query de fallback también devuelve 0 filas. La sección 'Próximas convocatorias' aparecerá vacía.</p>";
    } else {
        echo "<p class='ok'>✅ El query de fallback devuelve " . count($rows) . " fila(s).</p>";
        echo "<table><tr><th>Curso</th><th>imagen</th><th>imagen_home</th></tr>";
        foreach ($rows as $r) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($r['titulo']) . "</td>";
            echo "<td>" . ($r['imagen'] ? htmlspecialchars(basename($r['imagen'])) : '—') . "</td>";
            echo "<td>" . ($r['imagen_home'] ? htmlspecialchars(basename($r['imagen_home'])) : '—') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Throwable $e) {
    echo "<p class='err'>❌ ERROR en query fallback: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr><p><strong>Cuando termines, borra este archivo del servidor por FTP.</strong></p>";
