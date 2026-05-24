<?php
/**
 * Back Office — Dashboard de estadísticas unificado
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/funciones.php';

requiere_login();

$db = get_db();

// Período global
$periodo = $_GET['periodo'] ?? '30d';
$filtro_idioma = $_GET['idioma'] ?? '';

$dias_map = ['7d' => 7, '30d' => 30, '3m' => 90, '1y' => 365, 'todo' => 3650];
$dias = $dias_map[$periodo] ?? 30;
$dias_prev = $dias * 2; // para calcular variación

$fecha_desde = date('Y-m-d', strtotime("-{$dias} days"));
$fecha_prev  = date('Y-m-d', strtotime("-{$dias_prev} days"));

// ─── RESUMEN EJECUTIVO ───

// Visitas actuales
$sql_vis = "SELECT COUNT(*) FROM estadisticas_visitas WHERE fecha >= ?";
$params_vis = [$fecha_desde];
if ($filtro_idioma) { $sql_vis .= " AND idioma = ?"; $params_vis[] = $filtro_idioma; }
$stmt = $db->prepare($sql_vis);
$stmt->execute($params_vis);
$visitas_actual = (int)$stmt->fetchColumn();

// Visitas período anterior
$sql_vis_prev = "SELECT COUNT(*) FROM estadisticas_visitas WHERE fecha >= ? AND fecha < ?";
$params_prev = [$fecha_prev, $fecha_desde];
if ($filtro_idioma) { $sql_vis_prev .= " AND idioma = ?"; $params_prev[] = $filtro_idioma; }
$stmt = $db->prepare($sql_vis_prev);
$stmt->execute($params_prev);
$visitas_prev = (int)$stmt->fetchColumn();

// Suscriptores nuevos
$stmt = $db->prepare("SELECT COUNT(*) FROM newsletter_suscriptores WHERE fecha_suscripcion >= ? AND confirmado = 1");
$stmt->execute([$fecha_desde]);
$subs_actual = (int)$stmt->fetchColumn();
$stmt = $db->prepare("SELECT COUNT(*) FROM newsletter_suscriptores WHERE fecha_suscripcion >= ? AND fecha_suscripcion < ? AND confirmado = 1");
$stmt->execute([$fecha_prev, $fecha_desde]);
$subs_prev = (int)$stmt->fetchColumn();

// Formularios
$stmt = $db->prepare("SELECT COUNT(*) FROM formularios_contacto WHERE fecha_envio >= ?");
$stmt->execute([$fecha_desde]);
$forms_actual = (int)$stmt->fetchColumn();
$stmt = $db->prepare("SELECT COUNT(*) FROM formularios_contacto WHERE fecha_envio >= ? AND fecha_envio < ?");
$stmt->execute([$fecha_prev, $fecha_desde]);
$forms_prev = (int)$stmt->fetchColumn();

// Artículo más leído
$sql_top = "SELECT a.titulo, COUNT(v.id) as total FROM estadisticas_visitas v JOIN articulos a ON v.articulo_id = a.id WHERE v.fecha >= ? AND v.articulo_id IS NOT NULL";
$params_top = [$fecha_desde];
if ($filtro_idioma) { $sql_top .= " AND v.idioma = ?"; $params_top[] = $filtro_idioma; }
$sql_top .= " GROUP BY v.articulo_id ORDER BY total DESC LIMIT 1";
$stmt = $db->prepare($sql_top);
$stmt->execute($params_top);
$top_art = $stmt->fetch();

function variacion(int $actual, int $anterior): string {
    if ($anterior == 0) return $actual > 0 ? '<span class="var-up">+100%</span>' : '<span class="var-neutral">—</span>';
    $pct = round((($actual - $anterior) / $anterior) * 100);
    if ($pct > 0) return '<span class="var-up">&uarr; +' . $pct . '%</span>';
    if ($pct < 0) return '<span class="var-down">&darr; ' . $pct . '%</span>';
    return '<span class="var-neutral">=</span>';
}

// ─── DATOS GRÁFICOS ───

// Visitas por día
$sql_chart = "SELECT fecha, COUNT(*) as total FROM estadisticas_visitas WHERE fecha >= ?";
$params_chart = [$fecha_desde];
if ($filtro_idioma) { $sql_chart .= " AND idioma = ?"; $params_chart[] = $filtro_idioma; }
$sql_chart .= " GROUP BY fecha ORDER BY fecha ASC";
$stmt = $db->prepare($sql_chart);
$stmt->execute($params_chart);
$visitas_dia = $stmt->fetchAll();

$chart_labels = [];
$chart_data = [];
$visitas_map = [];
foreach ($visitas_dia as $v) { $visitas_map[$v['fecha']] = $v['total']; }
for ($i = $dias - 1; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $chart_labels[] = date('d M', strtotime($d));
    $chart_data[] = $visitas_map[$d] ?? 0;
}

// Top 10 artículos
$sql_top10 = "SELECT a.titulo, a.categoria, COUNT(v.id) as total FROM estadisticas_visitas v JOIN articulos a ON v.articulo_id = a.id WHERE v.fecha >= ? AND v.articulo_id IS NOT NULL";
$params_t10 = [$fecha_desde];
if ($filtro_idioma) { $sql_top10 .= " AND v.idioma = ?"; $params_t10[] = $filtro_idioma; }
$sql_top10 .= " GROUP BY v.articulo_id ORDER BY total DESC LIMIT 10";
$stmt = $db->prepare($sql_top10);
$stmt->execute($params_t10);
$top10 = $stmt->fetchAll();
$max_top10 = $top10 ? max(array_column($top10, 'total')) : 1;

// Fuentes de tráfico
$sql_ref = "SELECT CASE WHEN referrer = '' OR referrer IS NULL THEN 'Directo' WHEN referrer LIKE '%google%' THEN 'Google' WHEN referrer LIKE '%linkedin%' THEN 'LinkedIn' WHEN referrer LIKE '%facebook%' THEN 'Facebook' WHEN referrer LIKE '%twitter%' OR referrer LIKE '%t.co%' THEN 'X / Twitter' WHEN referrer LIKE '%instagram%' THEN 'Instagram' ELSE SUBSTRING_INDEX(SUBSTRING_INDEX(REPLACE(REPLACE(referrer, 'https://', ''), 'http://', ''), '/', 1), '?', 1) END as fuente, COUNT(*) as total FROM estadisticas_visitas WHERE fecha >= ? GROUP BY fuente ORDER BY total DESC LIMIT 10";
$stmt = $db->prepare($sql_ref);
$stmt->execute([$fecha_desde]);
$referrers = $stmt->fetchAll();

// Visitas por hora
$stmt = $db->prepare("SELECT hora, COUNT(*) as total FROM estadisticas_visitas WHERE fecha >= ? GROUP BY hora ORDER BY hora ASC");
$stmt->execute([$fecha_desde]);
$horas_data = $stmt->fetchAll();
$horas_arr = array_fill(0, 24, 0);
foreach ($horas_data as $h) { $horas_arr[(int)$h['hora']] = (int)$h['total']; }

// ─── NEWSLETTER STATS ───
$activos_es = (int)$db->query("SELECT COUNT(*) FROM newsletter_suscriptores WHERE activo = 1 AND confirmado = 1 AND idioma = 'es'")->fetchColumn();
$activos_en = (int)$db->query("SELECT COUNT(*) FROM newsletter_suscriptores WHERE activo = 1 AND confirmado = 1 AND idioma = 'en'")->fetchColumn();
$pendientes = (int)$db->query("SELECT COUNT(*) FROM newsletter_suscriptores WHERE activo = 1 AND confirmado = 0")->fetchColumn();
$bajas_total = (int)$db->query("SELECT COUNT(*) FROM newsletter_suscriptores WHERE activo = 0")->fetchColumn();
$total_subs = $activos_es + $activos_en + $pendientes;
$tasa_conf = ($total_subs + $bajas_total) > 0 ? round((($activos_es + $activos_en) / ($total_subs + $bajas_total)) * 100, 1) : 0;

// Suscriptores por mes (últimos 12 meses)
$subs_mes = $db->query("SELECT DATE_FORMAT(fecha_suscripcion, '%Y-%m') as mes, idioma, COUNT(*) as total FROM newsletter_suscriptores WHERE confirmado = 1 AND fecha_suscripcion >= DATE_SUB(NOW(), INTERVAL 12 MONTH) GROUP BY mes, idioma ORDER BY mes ASC")->fetchAll();

$meses_labels = [];
$subs_es_data = [];
$subs_en_data = [];
for ($i = 11; $i >= 0; $i--) {
    $m = date('Y-m', strtotime("-{$i} months"));
    $meses_labels[] = date('M Y', strtotime($m . '-01'));
    $subs_es_data[$m] = 0;
    $subs_en_data[$m] = 0;
}
foreach ($subs_mes as $s) {
    if ($s['idioma'] === 'es' && isset($subs_es_data[$s['mes']])) $subs_es_data[$s['mes']] = (int)$s['total'];
    if ($s['idioma'] === 'en' && isset($subs_en_data[$s['mes']])) $subs_en_data[$s['mes']] = (int)$s['total'];
}

// Campañas
$campanas = $db->query("SELECT asunto, total_enviados, total_abiertos, total_clics, estado, fecha_envio FROM newsletter_campanas WHERE estado = 'enviada' ORDER BY fecha_envio DESC LIMIT 10")->fetchAll();

// ─── FORMULARIOS ───
$forms_sin_leer = (int)$db->query("SELECT COUNT(*) FROM formularios_contacto WHERE leido = 0")->fetchColumn();

$stmt = $db->prepare("SELECT * FROM formularios_contacto WHERE fecha_envio >= ? ORDER BY fecha_envio DESC LIMIT 50");
$stmt->execute([$fecha_desde]);
$formularios = $stmt->fetchAll();

$forms_centros = 0; $forms_agencias = 0; $forms_otro = 0;
foreach ($formularios as $f) {
    if ($f['tipo'] === 'centro') $forms_centros++;
    elseif ($f['tipo'] === 'agencia') $forms_agencias++;
    else $forms_otro++;
}

// Formularios por mes
$forms_mes = $db->query("SELECT DATE_FORMAT(fecha_envio, '%Y-%m') as mes, tipo, COUNT(*) as total FROM formularios_contacto WHERE fecha_envio >= DATE_SUB(NOW(), INTERVAL 6 MONTH) GROUP BY mes, tipo ORDER BY mes ASC")->fetchAll();

// ─── CURSOS STATS ───
try {
    $cursos_total = (int)$db->query("SELECT COUNT(*) FROM cursos WHERE estado = 'publicado'")->fetchColumn();
    $inscripciones_total = (int)$db->prepare("SELECT COUNT(*) FROM cursos_inscripciones WHERE created_at >= ?")->execute([$fecha_desde]) ? 0 : 0;
    $stmt_insc = $db->prepare("SELECT COUNT(*) FROM cursos_inscripciones WHERE created_at >= ?");
    $stmt_insc->execute([$fecha_desde]);
    $inscripciones_actual = (int)$stmt_insc->fetchColumn();
    $stmt_insc_prev = $db->prepare("SELECT COUNT(*) FROM cursos_inscripciones WHERE created_at >= ? AND created_at < ?");
    $stmt_insc_prev->execute([$fecha_prev, $fecha_desde]);
    $inscripciones_prev = (int)$stmt_insc_prev->fetchColumn();

    $inscripciones_pendientes = (int)$db->query("SELECT COUNT(*) FROM cursos_inscripciones WHERE estado = 'pendiente'")->fetchColumn();
    $inscripciones_confirmadas = (int)$db->query("SELECT COUNT(*) FROM cursos_inscripciones WHERE estado = 'confirmada'")->fetchColumn();

    // Top cursos por inscripciones
    $top_cursos = $db->query("SELECT c.titulo, c.idioma, c.inscritos, c.plazas FROM cursos WHERE estado = 'publicado' ORDER BY inscritos DESC LIMIT 5")->fetchAll();
    $cursos_stats_ok = true;
} catch (PDOException $e) {
    $cursos_stats_ok = false;
}

// ─── POST: marcar leído / respondido / nota / eliminar ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verificar_csrf()) {
    $fa = $_POST['form_accion'] ?? '';
    $fid = (int)($_POST['form_id'] ?? 0);
    if ($fa === 'leido' && $fid) {
        $db->prepare("UPDATE formularios_contacto SET leido = 1 WHERE id = ?")->execute([$fid]);
    }
    if ($fa === 'respondido' && $fid) {
        $db->prepare("UPDATE formularios_contacto SET respondido = 1, leido = 1 WHERE id = ?")->execute([$fid]);
    }
    if ($fa === 'nota' && $fid) {
        $nota = trim($_POST['nota'] ?? '');
        $db->prepare("UPDATE formularios_contacto SET notas_internas = ? WHERE id = ?")->execute([$nota, $fid]);
    }
    if ($fa === 'eliminar' && $fid) {
        $db->prepare("DELETE FROM formularios_contacto WHERE id = ?")->execute([$fid]);
    }
    header('Location: /admin/estadisticas.php?periodo=' . $periodo . '#formularios');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Estadísticas — EuryGo Admin</title>
  <link rel="stylesheet" href="/admin/assets/admin.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <meta name="robots" content="noindex, nofollow">
</head>
<body class="admin-body">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <div class="admin-main">
    <?php include __DIR__ . '/partials/topbar.php'; ?>

    <div class="admin-content">
      <div class="admin-content__header">
        <h1>Estadísticas</h1>
        <form class="admin-filters" method="GET" style="margin-bottom:0;">
          <select name="periodo" onchange="this.form.submit()">
            <option value="7d" <?= $periodo === '7d' ? 'selected' : '' ?>>7 días</option>
            <option value="30d" <?= $periodo === '30d' ? 'selected' : '' ?>>30 días</option>
            <option value="3m" <?= $periodo === '3m' ? 'selected' : '' ?>>3 meses</option>
            <option value="1y" <?= $periodo === '1y' ? 'selected' : '' ?>>Este año</option>
            <option value="todo" <?= $periodo === 'todo' ? 'selected' : '' ?>>Todo</option>
          </select>
          <select name="idioma" onchange="this.form.submit()">
            <option value="">Todos los idiomas</option>
            <option value="es" <?= $filtro_idioma === 'es' ? 'selected' : '' ?>>ES</option>
            <option value="en" <?= $filtro_idioma === 'en' ? 'selected' : '' ?>>EN</option>
          </select>
        </form>
      </div>

      <!-- RESUMEN EJECUTIVO -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-card__number"><?= $visitas_actual ?></div>
          <div class="stat-card__label">Visitas al blog</div>
          <div><?= variacion($visitas_actual, $visitas_prev) ?> vs período anterior</div>
        </div>
        <div class="stat-card">
          <div class="stat-card__number"><?= $subs_actual ?></div>
          <div class="stat-card__label">Suscriptores nuevos</div>
          <div><?= variacion($subs_actual, $subs_prev) ?> vs período anterior</div>
        </div>
        <div class="stat-card">
          <div class="stat-card__number"><?= $forms_actual ?></div>
          <div class="stat-card__label">Formularios recibidos</div>
          <div><?= variacion($forms_actual, $forms_prev) ?> vs período anterior</div>
        </div>
        <div class="stat-card">
          <div class="stat-card__label">Artículo más leído</div>
          <div class="stat-card__text"><?= $top_art ? htmlspecialchars(truncar($top_art['titulo'], 60)) : 'Sin datos' ?></div>
          <?php if ($top_art): ?><div class="stat-card__date"><?= $top_art['total'] ?> visitas</div><?php endif; ?>
        </div>
<?php if (!empty($cursos_stats_ok)): ?>
        <div class="stat-card">
          <div class="stat-card__number"><?= $inscripciones_actual ?></div>
          <div class="stat-card__label">Inscripciones cursos</div>
          <div><?= variacion($inscripciones_actual, $inscripciones_prev) ?> vs período anterior</div>
        </div>
<?php endif; ?>
      </div>

      <!-- BLOQUE 1: VISITAS -->
      <div class="editor-section">
        <h2>Visitas al blog</h2>
        <div class="chart-container" style="max-height:320px;">
          <canvas id="chart-visitas"></canvas>
        </div>
      </div>

      <div class="editor-section">
        <h2>Artículos más visitados</h2>
<?php if (empty($top10)): ?>
        <p style="color:var(--admin-text-light);">Sin datos de visitas aún.</p>
<?php else: ?>
        <table class="admin-table">
          <thead><tr><th>#</th><th>Título</th><th>Categoría</th><th>Visitas</th><th></th></tr></thead>
          <tbody>
<?php foreach ($top10 as $i => $t): ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td class="admin-table__title"><?= htmlspecialchars(truncar($t['titulo'], 50)) ?></td>
              <td><span class="badge badge--lang"><?= nombre_categoria($t['categoria'], 'es') ?></span></td>
              <td><?= $t['total'] ?></td>
              <td style="width:200px;"><div class="progress-bar"><div class="progress-bar__fill" style="width:<?= round(($t['total'] / $max_top10) * 100) ?>%"></div></div></td>
            </tr>
<?php endforeach; ?>
          </tbody>
        </table>
<?php endif; ?>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:1.25rem;">
        <div class="editor-section">
          <h2>Fuentes de tráfico</h2>
<?php if (empty($referrers)): ?>
          <p style="color:var(--admin-text-light);">Sin datos aún.</p>
<?php else: ?>
          <table class="admin-table">
            <thead><tr><th>Fuente</th><th>Visitas</th></tr></thead>
            <tbody>
<?php foreach ($referrers as $r): ?>
              <tr><td><?= htmlspecialchars($r['fuente']) ?></td><td><?= $r['total'] ?></td></tr>
<?php endforeach; ?>
            </tbody>
          </table>
<?php endif; ?>
        </div>

        <div class="editor-section">
          <h2>Visitas por hora del día</h2>
          <div class="chart-container" style="max-height:250px;">
            <canvas id="chart-horas"></canvas>
          </div>
        </div>
      </div>

      <!-- BLOQUE 2: NEWSLETTER -->
      <div class="editor-section">
        <h2>Newsletter</h2>
        <div class="stats-grid">
          <div class="stat-card"><div class="stat-card__number"><?= $activos_es ?></div><div class="stat-card__label">Activos ES</div></div>
          <div class="stat-card"><div class="stat-card__number"><?= $activos_en ?></div><div class="stat-card__label">Activos EN</div></div>
          <div class="stat-card"><div class="stat-card__number"><?= $pendientes ?></div><div class="stat-card__label">Pendientes confirmar</div></div>
          <div class="stat-card"><div class="stat-card__number"><?= $bajas_total ?></div><div class="stat-card__label">Bajas totales</div></div>
          <div class="stat-card"><div class="stat-card__number"><?= $tasa_conf ?>%</div><div class="stat-card__label">Tasa de confirmación</div></div>
        </div>

        <div class="chart-container" style="max-height:280px;margin-top:1.5rem;">
          <canvas id="chart-suscriptores"></canvas>
        </div>
      </div>

<?php if (!empty($campanas)): ?>
      <div class="editor-section">
        <h2>Historial de campañas</h2>
        <table class="admin-table">
          <thead><tr><th>Asunto</th><th>Enviados</th><th>Abiertos</th><th>% apertura</th><th>Clics</th><th>% clics</th><th>Fecha</th></tr></thead>
          <tbody>
<?php foreach ($campanas as $c): ?>
            <tr>
              <td class="admin-table__title"><?= htmlspecialchars($c['asunto']) ?></td>
              <td><?= $c['total_enviados'] ?></td>
              <td><?= $c['total_abiertos'] ?></td>
              <td><?= $c['total_enviados'] > 0 ? round(($c['total_abiertos'] / $c['total_enviados']) * 100, 1) . '%' : '—' ?></td>
              <td><?= $c['total_clics'] ?></td>
              <td><?= $c['total_enviados'] > 0 ? round(($c['total_clics'] / $c['total_enviados']) * 100, 1) . '%' : '—' ?></td>
              <td><?= $c['fecha_envio'] ? formato_fecha($c['fecha_envio'], 'es') : '—' ?></td>
            </tr>
<?php endforeach; ?>
          </tbody>
        </table>
        <p style="margin-top:0.75rem;font-size:0.85rem;color:var(--admin-text-light);">Referencia del sector educativo: apertura 25-35%, clics 2-5%.</p>
      </div>
<?php endif; ?>

      <!-- BLOQUE 3: FORMULARIOS -->
      <div id="formularios" class="editor-section">
        <h2>Formularios de contacto</h2>

<?php if ($forms_sin_leer > 0): ?>
        <div class="alert alert--error">Tienes <?= $forms_sin_leer ?> consulta<?= $forms_sin_leer > 1 ? 's' : '' ?> sin leer.</div>
<?php endif; ?>

        <div class="stats-grid">
          <div class="stat-card"><div class="stat-card__number"><?= count($formularios) ?></div><div class="stat-card__label">Recibidos</div></div>
          <div class="stat-card"><div class="stat-card__number"><?= $forms_centros ?> <small>(<?= count($formularios) > 0 ? round($forms_centros / count($formularios) * 100) : 0 ?>%)</small></div><div class="stat-card__label">De centros</div></div>
          <div class="stat-card"><div class="stat-card__number"><?= $forms_agencias ?> <small>(<?= count($formularios) > 0 ? round($forms_agencias / count($formularios) * 100) : 0 ?>%)</small></div><div class="stat-card__label">De agencias</div></div>
          <div class="stat-card"><div class="stat-card__number"><?= $forms_sin_leer ?></div><div class="stat-card__label">Sin leer</div></div>
        </div>

        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead><tr><th>Tipo</th><th>Nombre</th><th>Email</th><th>Organización</th><th>Fecha</th><th>Estado</th><th>Acciones</th></tr></thead>
            <tbody>
<?php if (empty($formularios)): ?>
              <tr><td colspan="7" style="text-align:center;color:var(--admin-text-light);">Sin formularios en este período</td></tr>
<?php else: ?>
<?php foreach ($formularios as $f): ?>
              <tr>
                <td><?= ucfirst($f['tipo']) ?></td>
                <td><?= htmlspecialchars($f['nombre']) ?></td>
                <td><a href="mailto:<?= htmlspecialchars($f['email']) ?>?subject=Re: EuryGo"><?= htmlspecialchars($f['email']) ?></a></td>
                <td><?= htmlspecialchars($f['organizacion'] ?: '—') ?></td>
                <td><?= formato_fecha($f['fecha_envio'], 'es') ?></td>
                <td>
<?php if ($f['respondido']): ?>
                  <span class="status-dot status-dot--green"></span>Respondido
<?php elseif ($f['leido']): ?>
                  <span class="status-dot status-dot--yellow"></span>Leído
<?php else: ?>
                  <span class="status-dot status-dot--red"></span>Sin leer
<?php endif; ?>
                </td>
                <td class="admin-table__actions">
<?php if (!$f['leido']): ?>
                  <form method="POST" style="display:inline;"><?= campo_csrf() ?><input type="hidden" name="form_accion" value="leido"><input type="hidden" name="form_id" value="<?= $f['id'] ?>"><button class="btn-admin btn-admin--text btn-admin--sm" type="submit">Marcar leído</button></form>
<?php endif; ?>
<?php if (!$f['respondido']): ?>
                  <form method="POST" style="display:inline;"><?= campo_csrf() ?><input type="hidden" name="form_accion" value="respondido"><input type="hidden" name="form_id" value="<?= $f['id'] ?>"><button class="btn-admin btn-admin--text btn-admin--sm" type="submit">Respondido</button></form>
<?php endif; ?>
                  <button class="btn-admin btn-admin--text btn-admin--sm" onclick="toggleDetalle(<?= $f['id'] ?>)">Ver detalle</button>
                  <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar formulario?')"><?= campo_csrf() ?><input type="hidden" name="form_accion" value="eliminar"><input type="hidden" name="form_id" value="<?= $f['id'] ?>"><button class="btn-admin btn-admin--danger btn-admin--sm" type="submit">Eliminar</button></form>
                </td>
              </tr>
              <tr id="detalle-<?= $f['id'] ?>" hidden>
                <td colspan="7" style="background:#f8fafc;padding:1.25rem;">
                  <strong>Mensaje:</strong><br>
                  <p style="white-space:pre-wrap;margin:0.5rem 0;"><?= htmlspecialchars($f['mensaje']) ?></p>
                  <?php if ($f['telefono']): ?><p><strong>Teléfono:</strong> <?= htmlspecialchars($f['telefono']) ?></p><?php endif; ?>
                  <form method="POST" style="margin-top:0.75rem;">
                    <?= campo_csrf() ?>
                    <input type="hidden" name="form_accion" value="nota">
                    <input type="hidden" name="form_id" value="<?= $f['id'] ?>">
                    <label style="font-weight:600;font-size:0.85rem;">Notas internas:</label>
                    <textarea name="nota" rows="2" style="width:100%;margin-top:0.25rem;padding:0.5rem;border:1px solid #d1d5db;border-radius:6px;font-family:inherit;font-size:0.9rem;"><?= htmlspecialchars($f['notas_internas'] ?? '') ?></textarea>
                    <button type="submit" class="btn-admin btn-admin--outline btn-admin--sm" style="margin-top:0.5rem;">Guardar nota</button>
                  </form>
                </td>
              </tr>
<?php endforeach; ?>
<?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

<?php if (!empty($cursos_stats_ok)): ?>
      <!-- BLOQUE 4: CURSOS -->
      <div style="margin-top:2rem;">
        <h2>Cursos de Formación</h2>
        <div class="stats-grid" style="margin-top:1rem;">
          <div class="stat-card">
            <div class="stat-card__number"><?= $cursos_total ?></div>
            <div class="stat-card__label">Cursos publicados</div>
          </div>
          <div class="stat-card">
            <div class="stat-card__number"><?= $inscripciones_actual ?></div>
            <div class="stat-card__label">Inscripciones (período) <?= variacion($inscripciones_actual, $inscripciones_prev) ?></div>
          </div>
          <div class="stat-card">
            <div class="stat-card__number" style="color:#D97706;"><?= $inscripciones_pendientes ?></div>
            <div class="stat-card__label">Pendientes de confirmar</div>
          </div>
          <div class="stat-card">
            <div class="stat-card__number" style="color:#16a34a;"><?= $inscripciones_confirmadas ?></div>
            <div class="stat-card__label">Confirmadas</div>
          </div>
        </div>

<?php if (!empty($top_cursos)): ?>
        <div class="admin-table-wrap" style="margin-top:1rem;">
          <table class="admin-table">
            <thead>
              <tr><th>Curso</th><th>Idioma</th><th>Inscritos</th><th>Plazas</th><th>Ocupación</th></tr>
            </thead>
            <tbody>
<?php foreach ($top_cursos as $tc): ?>
<?php $pct = $tc['plazas'] > 0 ? round($tc['inscritos'] / $tc['plazas'] * 100) : 0; ?>
              <tr>
                <td><strong><?= htmlspecialchars($tc['titulo']) ?></strong></td>
                <td><span class="badge"><?= strtoupper($tc['idioma']) ?></span></td>
                <td><?= $tc['inscritos'] ?></td>
                <td><?= $tc['plazas'] ?></td>
                <td>
                  <div style="display:flex;align-items:center;gap:0.5rem;">
                    <div class="progress-bar" style="width:100px;">
                      <div class="progress-bar__fill" style="width:<?= $pct ?>%;<?= $pct >= 90 ? 'background:#dc3545;' : '' ?>"></div>
                    </div>
                    <span style="font-size:0.85rem; font-weight:600;"><?= $pct ?>%</span>
                  </div>
                </td>
              </tr>
<?php endforeach; ?>
            </tbody>
          </table>
        </div>
<?php endif; ?>

        <div style="margin-top:1rem;">
          <a href="/admin/inscripciones.php" class="btn-admin btn-admin--outline">Ver todas las inscripciones →</a>
        </div>
      </div>
<?php endif; ?>

  </div>

<script>
function toggleDetalle(id) {
  var el = document.getElementById('detalle-' + id);
  if (el) el.hidden = !el.hidden;
}

// Chart: Visitas por día
new Chart(document.getElementById('chart-visitas'), {
  type: 'line',
  data: {
    labels: <?= json_encode($chart_labels) ?>,
    datasets: [{
      label: 'Visitas',
      data: <?= json_encode($chart_data) ?>,
      borderColor: '#0284C7',
      backgroundColor: 'rgba(2,132,199,0.1)',
      fill: true,
      tension: 0.3,
      pointRadius: 2
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
  }
});

// Chart: Visitas por hora
new Chart(document.getElementById('chart-horas'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_map(fn($h) => str_pad($h, 2, '0', STR_PAD_LEFT) . ':00', range(0, 23))) ?>,
    datasets: [{
      label: 'Visitas',
      data: <?= json_encode(array_values($horas_arr)) ?>,
      backgroundColor: 'rgba(2,132,199,0.6)',
      borderRadius: 3
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
  }
});

// Chart: Suscriptores por mes
new Chart(document.getElementById('chart-suscriptores'), {
  type: 'bar',
  data: {
    labels: <?= json_encode(array_values($meses_labels)) ?>,
    datasets: [
      { label: 'ES', data: <?= json_encode(array_values($subs_es_data)) ?>, backgroundColor: '#0284C7', borderRadius: 3 },
      { label: 'EN', data: <?= json_encode(array_values($subs_en_data)) ?>, backgroundColor: '#F59E0B', borderRadius: 3 }
    ]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { position: 'top' } },
    scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } } }
  }
});
</script>
</body>
</html>
