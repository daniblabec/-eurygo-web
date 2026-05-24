<?php
/**
 * ACTUALIZACIÓN v9 — EuryGo: Limpieza quirúrgica del artículo de blog KA1
 *
 * Brief 2026-05-20: separar línea de "cursos de formación KA1" (que NO incluyen
 * school visits / job shadowing) de la línea de "job shadowing / school
 * observation" (servicio independiente para agencias).
 *
 * Este script retira las menciones que MEZCLAN ambos productos en los dos
 * artículos de blog insertados por update_v7.php, conservando las menciones
 * legítimas (catálogo de servicios de la empresa y descripción del programa
 * KA1 en general).
 *
 *  - Artículo EN: erasmus-ka1-teacher-training-jerez-andalusia
 *  - Artículo ES: cursos-ka1-formacion-docente-jerez-andalucia
 *
 * USO:
 *   /admin/setup/update_v9.php                          → PREVIEW (sin escritura)
 *   /admin/setup/update_v9.php?mode=apply&confirm=YES   → APPLY (transacción)
 *
 * Requiere sesión de admin. BORRAR del servidor después de ejecutar.
 *
 * Idempotente: cada cambio se aplica solo si encuentra el texto original.
 * Si ya está limpio, el cambio se reporta como "(no change)".
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';

requiere_login();

$db   = get_db();
$mode = ($_GET['mode'] ?? 'preview') === 'apply' && ($_GET['confirm'] ?? '') === 'YES' ? 'apply' : 'preview';

// ───────────────────────────────────────────────────────────────────────────
//  REGLAS DE LIMPIEZA
//  Cada regla: [slug, idioma, campo, texto_buscar, texto_reemplazar, etiqueta]
//  El "texto_buscar" se hace match con strpos (substring exacta) para máxima
//  seguridad — sin regex. Si no aparece, se considera ya limpio.
// ───────────────────────────────────────────────────────────────────────────
$reglas = [
    // ───── ARTÍCULO EN ─────
    [
        'slug'     => 'erasmus-ka1-teacher-training-jerez-andalusia',
        'idioma'   => 'en',
        'campo'    => 'subtitulo',
        'buscar'   => 'Professional development, school visits and cultural immersion in the heart of southern Spain',
        'reemp'    => 'Professional development, certified KA1 training and cultural immersion in the heart of southern Spain',
        'etiqueta' => 'EN · subtitulo — reposiciona "school visits" como "certified KA1 training"',
    ],
    [
        'slug'     => 'erasmus-ka1-teacher-training-jerez-andalusia',
        'idioma'   => 'en',
        'campo'    => 'extracto',
        'buscar'   => 'Five-day programmes combining expert-led training, school visits, and Andalusian cultural immersion for European educators.',
        'reemp'    => 'Five-day programmes combining expert-led training and Andalusian cultural immersion for European educators.',
        'etiqueta' => 'EN · extracto — retira ", school visits"',
    ],
    [
        'slug'     => 'erasmus-ka1-teacher-training-jerez-andalusia',
        'idioma'   => 'en',
        'campo'    => 'meta_description',
        'buscar'   => 'Spanish education, AI, inclusion, school visits and cultural immersion.',
        'reemp'    => 'Spanish education, AI, inclusion and Andalusian cultural immersion.',
        'etiqueta' => 'EN · meta_description — retira "school visits"',
    ],
    [
        'slug'     => 'erasmus-ka1-teacher-training-jerez-andalusia',
        'idioma'   => 'en',
        'campo'    => 'contenido',
        'buscar'   => 'combine intensive professional learning with cultural and school-based experiences',
        'reemp'    => 'combine intensive professional learning with cultural and hands-on classroom experiences',
        'etiqueta' => 'EN · contenido — sustituye "school-based" por "hands-on classroom"',
    ],
    [
        'slug'     => 'erasmus-ka1-teacher-training-jerez-andalusia',
        'idioma'   => 'en',
        'campo'    => 'contenido',
        // Eliminar el <li> entero sobre School visits. Incluimos también el salto
        // y los 2 espacios de indentación para no dejar líneas en blanco huérfanas.
        'buscar'   => "  <li><strong>School visits</strong> to local primary and secondary schools, where participants observe classes and speak with Spanish teachers.</li>\n",
        'reemp'    => '',
        'etiqueta' => 'EN · contenido — elimina el <li> "School visits to local schools"',
    ],

    // ───── ARTÍCULO ES ─────
    [
        'slug'     => 'cursos-ka1-formacion-docente-jerez-andalucia',
        'idioma'   => 'es',
        'campo'    => 'contenido',
        // Reescritura del bullet "Una red de centros comprometidos" para no
        // sugerir que los cursos KA1 incluyen visita/job shadowing.
        'buscar'   => '<li><strong>Una red de centros comprometidos.</strong> EuryGo trabaja con una red de centros escolares locales con años de experiencia en proyectos europeos. Tu visita o job shadowing será un intercambio profesional real, no una visita protocolaria.</li>',
        'reemp'    => '<li><strong>Una red de partners locales.</strong> EuryGo trabaja con una red de centros y profesionales locales con años de experiencia en proyectos europeos. Esa red enriquece la formación con perspectivas reales del aula andaluza y conexiones que no encontrarás en una gran ciudad.</li>',
        'etiqueta' => 'ES · contenido — reescribe el bullet "red de centros" sin sugerir visita/job shadowing dentro del curso',
    ],
    [
        'slug'     => 'cursos-ka1-formacion-docente-jerez-andalucia',
        'idioma'   => 'es',
        'campo'    => 'contenido',
        'buscar'   => 'Marco legal, Diseño Universal para el Aprendizaje, estrategias prácticas y visita a un centro escolar con buenas prácticas reconocidas en inclusión.',
        'reemp'    => 'Marco legal, Diseño Universal para el Aprendizaje, estrategias prácticas y análisis de casos reales de buenas prácticas reconocidas en inclusión.',
        'etiqueta' => 'ES · contenido — curso 5: sustituye "visita a un centro escolar" por "análisis de casos reales"',
    ],
];

// ───────────────────────────────────────────────────────────────────────────
//  Preparar resumen (preview) + ejecución (apply)
// ───────────────────────────────────────────────────────────────────────────
$resumen     = [];
$log         = [];
$errores_apply = [];

// Cache de artículos cargados por (slug, idioma) → fila actual
$cache_articulos = [];
function cargar_articulo(PDO $db, string $slug, string $idioma, array &$cache): ?array {
    $key = $slug . '|' . $idioma;
    if (!array_key_exists($key, $cache)) {
        $st = $db->prepare("SELECT id, titulo, subtitulo, extracto, contenido, meta_description FROM articulos WHERE slug = :s AND idioma = :i LIMIT 1");
        $st->execute([':s' => $slug, ':i' => $idioma]);
        $cache[$key] = $st->fetch() ?: null;
    }
    return $cache[$key];
}

// Construir resumen
foreach ($reglas as $r) {
    $art = cargar_articulo($db, $r['slug'], $r['idioma'], $cache_articulos);
    if (!$art) {
        $resumen[] = $r + ['estado' => 'no_article', 'antes' => null, 'despues' => null];
        continue;
    }
    $valor = (string)$art[$r['campo']];
    if (strpos($valor, $r['buscar']) === false) {
        $resumen[] = $r + ['estado' => 'no_match', 'antes' => $valor, 'despues' => $valor];
        continue;
    }
    $nuevo = str_replace($r['buscar'], $r['reemp'], $valor);
    $resumen[] = $r + ['estado' => 'change', 'antes' => $valor, 'despues' => $nuevo];
}

// Aplicar
if ($mode === 'apply') {
    try {
        $db->beginTransaction();

        // Agrupar cambios por (id, campo) para hacer una sola UPDATE por campo
        $cache_articulos = []; // recargar limpio
        $pending = []; // [articulo_id => [campo => valor_actualizado]]
        foreach ($reglas as $r) {
            $art = cargar_articulo($db, $r['slug'], $r['idioma'], $cache_articulos);
            if (!$art) {
                $log[] = "✗ Artículo no encontrado: {$r['slug']} ({$r['idioma']}) — regla saltada: {$r['etiqueta']}";
                continue;
            }
            $aid = (int)$art['id'];
            $valor_actual = $pending[$aid][$r['campo']] ?? (string)$art[$r['campo']];
            if (strpos($valor_actual, $r['buscar']) === false) {
                $log[] = "ℹ Sin cambios (ya limpio): {$r['etiqueta']}";
                continue;
            }
            $pending[$aid][$r['campo']] = str_replace($r['buscar'], $r['reemp'], $valor_actual);
            $log[] = "✓ Programado: {$r['etiqueta']}";
        }

        // Ejecutar UPDATEs
        foreach ($pending as $aid => $campos) {
            $sets = [];
            $params = [':id' => $aid];
            $i = 0;
            foreach ($campos as $c => $v) {
                // Whitelist de campos editables, defensa contra inyección
                if (!in_array($c, ['subtitulo','extracto','contenido','meta_description'], true)) continue;
                $sets[] = "{$c} = :v{$i}";
                $params[":v{$i}"] = $v;
                $i++;
            }
            if (empty($sets)) continue;
            $sql = "UPDATE articulos SET " . implode(', ', $sets) . " WHERE id = :id";
            $db->prepare($sql)->execute($params);
            $log[] = "→ UPDATE articulos id={$aid} (" . count($sets) . " campo/s).";
        }

        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        $errores_apply[] = $e->getMessage();
    }
}

// ───────────────────────────────────────────────────────────────────────────
//  Utilidad para mostrar diff (resalta la zona del cambio)
// ───────────────────────────────────────────────────────────────────────────
function resaltar_substring(string $haystack, string $needle, string $clase): string {
    $pos = strpos($haystack, $needle);
    if ($pos === false) return htmlspecialchars($haystack);
    $antes   = htmlspecialchars(substr($haystack, 0, $pos));
    $match   = htmlspecialchars($needle);
    $despues = htmlspecialchars(substr($haystack, $pos + strlen($needle)));
    return $antes . '<mark class="' . $clase . '">' . $match . '</mark>' . $despues;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Update v9 — Limpieza blog KA1</title>
  <meta name="robots" content="noindex, nofollow">
  <style>
    body { font-family: system-ui, -apple-system, sans-serif; max-width: 1100px; margin: 32px auto; padding: 0 20px; color:#1f2937; }
    h1 { color:#0c4a6e; border-bottom:3px solid #0284c7; padding-bottom:8px; }
    h2 { margin-top: 28px; color:#0c4a6e; }
    h3 { margin-top: 16px; color:#1f2937; font-size: 0.95rem; }
    .box { padding:14px 18px; border-radius:10px; margin:12px 0; }
    .ok    { background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; }
    .warn  { background:#fffbeb; border:1px solid #fde68a; color:#92400e; }
    .err   { background:#fef2f2; border:1px solid #fecaca; color:#991b1b; }
    .info  { background:#eff6ff; border:1px solid #bfdbfe; color:#1e40af; }
    .rule { background:#f8fafc; border:1px solid #e2e8f0; border-radius:10px; padding:14px 18px; margin:14px 0; }
    .pill  { display:inline-block; padding:2px 8px; border-radius:99px; font-size:0.7rem; font-weight:700; text-transform:uppercase; }
    .pill--change { background:#fde68a; color:#92400e; }
    .pill--ok    { background:#bbf7d0; color:#166534; }
    .pill--miss  { background:#fecaca; color:#991b1b; }
    mark.del { background:#fee2e2; color:#7f1d1d; text-decoration: line-through; padding: 1px 3px; border-radius: 3px; }
    mark.ins { background:#dcfce7; color:#166534; padding: 1px 3px; border-radius: 3px; }
    .field-block { background:#fff; border-left:3px solid #cbd5e1; padding:10px 14px; margin:8px 0; font-size:0.88rem; line-height:1.55; }
    .btn-apply { display:inline-block; padding:14px 28px; background:#dc2626; color:#fff; font-weight:700; border-radius:8px; text-decoration:none; font-size:1.05rem; margin-top:12px; }
    .btn-apply:hover { background:#991b1b; }
    code { background:#f1f5f9; padding:1px 6px; border-radius:4px; font-size:0.85rem; }
  </style>
</head>
<body>
  <h1>Update v9 — Limpieza blog article KA1</h1>

  <?php if ($mode === 'apply'): ?>
    <?php if (empty($errores_apply)): ?>
      <div class="box ok"><strong>✓ APPLY completado.</strong> Todos los cambios commiteados en transacción.</div>
      <h2>Log</h2>
      <ol>
        <?php foreach ($log as $l): ?>
          <li><?= htmlspecialchars($l) ?></li>
        <?php endforeach; ?>
      </ol>
      <div class="box warn"><strong>IMPORTANTE:</strong> borra este archivo del servidor:<br><code>/admin/setup/update_v9.php</code></div>
    <?php else: ?>
      <div class="box err"><strong>✗ APPLY abortado y revertido (ROLLBACK).</strong><br><pre><?= htmlspecialchars(implode("\n\n", $errores_apply)) ?></pre></div>
    <?php endif; ?>
  <?php else: ?>
    <div class="box info">
      <strong>Modo PREVIEW.</strong> No se ha modificado nada. Revisa los cambios abajo.
      <br><br><a class="btn-apply" href="?mode=apply&amp;confirm=YES" onclick="return confirm('¿Aplicar los cambios al blog en producción?');">▶ Aplicar cambios ahora</a>
    </div>

    <?php
      $por_articulo = [];
      foreach ($resumen as $r) {
          $por_articulo[$r['slug'] . '|' . $r['idioma']][] = $r;
      }
    ?>

    <?php foreach ($por_articulo as $key => $reglas_art): list($slug, $idioma) = explode('|', $key); ?>
      <h2><?= strtoupper($idioma) ?> — <code><?= htmlspecialchars($slug) ?></code></h2>

      <?php foreach ($reglas_art as $r): ?>
        <div class="rule">
          <h3>
            <?php if ($r['estado'] === 'change'): ?>
              <span class="pill pill--change">Se cambia</span>
            <?php elseif ($r['estado'] === 'no_match'): ?>
              <span class="pill pill--ok">Ya limpio</span>
            <?php else: ?>
              <span class="pill pill--miss">Artículo no encontrado</span>
            <?php endif; ?>
            &nbsp; <?= htmlspecialchars($r['etiqueta']) ?>
          </h3>
          <p style="margin:6px 0 4px; font-size:0.85rem; color:#64748b;">Campo: <code><?= htmlspecialchars($r['campo']) ?></code></p>

          <?php if ($r['estado'] === 'change'): ?>
            <div class="field-block">
              <strong>Antes:</strong><br>
              <?= resaltar_substring($r['antes'], $r['buscar'], 'del') ?>
            </div>
            <div class="field-block" style="border-left-color:#16a34a;">
              <strong>Después:</strong><br>
              <?= $r['reemp'] === '' ? resaltar_substring($r['despues'], '', 'ins') . ' <em style="color:#64748b;">(línea eliminada)</em>' : resaltar_substring($r['despues'], $r['reemp'], 'ins') ?>
            </div>
          <?php elseif ($r['estado'] === 'no_match'): ?>
            <p style="font-size:0.85rem; color:#64748b;">No se ha encontrado el texto original — el campo ya está limpio o tiene una variante distinta. No se hará ningún cambio.</p>
          <?php else: ?>
            <p style="font-size:0.85rem; color:#991b1b;">El artículo no existe en la BD. Es posible que <code>update_v7.php</code> no se haya ejecutado en este entorno, o que el slug haya cambiado manualmente.</p>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php endforeach; ?>

    <h2>Lo que NO se toca (por diseño)</h2>
    <ul style="font-size:0.9rem; line-height:1.5;">
      <li><strong>EN</strong> — <code>contenido</code>: "EuryGo offers structured courses, job shadowing, and school observation programmes" → <em>catálogo de servicios de la empresa, coherente con la regla de posicionamiento</em>.</li>
      <li><strong>ES</strong> — <code>contenido</code>: "KA1 (Acción Clave 1) financia cursos de formación, estancias de observación profesional (job shadowing) y actividades de enseñanza…" → <em>descripción del programa KA1 en general, no de los cursos de EuryGo</em>.</li>
      <li><strong>CRM</strong> — <code>admin/crm/plantillas.php</code>: plantillas internas para agencias. No se toca.</li>
    </ul>
  <?php endif; ?>

  <hr style="margin:32px 0;">
  <p style="font-size:0.8rem; color:#94a3b8;">EuryGo Update v9 — 2026-05-20</p>
</body>
</html>
