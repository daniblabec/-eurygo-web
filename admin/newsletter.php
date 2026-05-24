<?php
/**
 * Back Office — Gestión de Newsletter (suscriptores + campañas)
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/brevo.php';

requiere_login();

$db = get_db();
$tab = $_GET['tab'] ?? 'suscriptores';
$mensaje = '';
$error = '';

// ─── Acciones POST ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verificar_csrf()) {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'baja' && !empty($_POST['sub_id'])) {
        $stmt = $db->prepare("SELECT email, idioma FROM newsletter_suscriptores WHERE id = ?");
        $stmt->execute([$_POST['sub_id']]);
        $sub = $stmt->fetch();
        if ($sub) {
            $db->prepare("UPDATE newsletter_suscriptores SET activo = 0, fecha_baja = NOW() WHERE id = ?")->execute([$_POST['sub_id']]);
            $listId = ($sub['idioma'] === 'en') ? BREVO_LIST_ID_EN : BREVO_LIST_ID_ES;
            if ($listId > 0) { (new BrevoAPI())->removeContact($sub['email'], $listId); }
            $mensaje = 'Suscriptor dado de baja.';
        }
    }

    if ($accion === 'eliminar' && !empty($_POST['sub_id'])) {
        $db->prepare("DELETE FROM newsletter_suscriptores WHERE id = ?")->execute([$_POST['sub_id']]);
        $mensaje = 'Suscriptor eliminado.';
    }

    if ($accion === 'sync_stats' && !empty($_POST['campana_id'])) {
        $stmt = $db->prepare("SELECT brevo_campaign_id FROM newsletter_campanas WHERE id = ?");
        $stmt->execute([$_POST['campana_id']]);
        $camp = $stmt->fetch();
        if ($camp && $camp['brevo_campaign_id']) {
            $brevo = new BrevoAPI();
            $result = $brevo->getCampaignStats((int)$camp['brevo_campaign_id']);
            if ($result['success']) {
                $stats = $result['data']['statistics']['globalStats'] ?? [];
                $db->prepare("UPDATE newsletter_campanas SET total_enviados = ?, total_abiertos = ?, total_clics = ? WHERE id = ?")
                    ->execute([$stats['delivered'] ?? 0, $stats['uniqueOpens'] ?? 0, $stats['uniqueClicks'] ?? 0, $_POST['campana_id']]);
                $mensaje = 'Estadísticas actualizadas desde Brevo.';
            } else {
                $error = 'Error al sincronizar: ' . $result['error'];
            }
        }
    }
}

// ─── Exportar CSV ───
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="suscriptores_eurygo_' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['email', 'nombre', 'idioma', 'fecha_suscripcion']);
    $rows = $db->query("SELECT email, nombre, idioma, fecha_suscripcion FROM newsletter_suscriptores WHERE activo = 1 AND confirmado = 1 ORDER BY fecha_suscripcion DESC");
    while ($row = $rows->fetch()) {
        fputcsv($out, $row);
    }
    fclose($out);
    exit;
}

// ─── Datos suscriptores ───
$filtro_idioma = $_GET['idioma'] ?? '';
$filtro_estado = $_GET['estado'] ?? '';
$filtro_confirmado = $_GET['confirmado'] ?? '';
$filtro_buscar = trim($_GET['buscar'] ?? '');
$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$por_pagina = 25;

$where = '1=1';
$params = [];
if ($filtro_idioma && in_array($filtro_idioma, ['es', 'en'])) { $where .= ' AND idioma = ?'; $params[] = $filtro_idioma; }
if ($filtro_estado === 'activo') { $where .= ' AND activo = 1'; }
if ($filtro_estado === 'baja') { $where .= ' AND activo = 0'; }
if ($filtro_confirmado === 'si') { $where .= ' AND confirmado = 1'; }
if ($filtro_confirmado === 'no') { $where .= ' AND confirmado = 0'; }
if ($filtro_buscar !== '') { $where .= ' AND (email LIKE ? OR nombre LIKE ?)'; $params[] = "%{$filtro_buscar}%"; $params[] = "%{$filtro_buscar}%"; }

$total_stmt = $db->prepare("SELECT COUNT(*) FROM newsletter_suscriptores WHERE {$where}");
$total_stmt->execute($params);
$total = (int)$total_stmt->fetchColumn();
$total_paginas = max(1, (int)ceil($total / $por_pagina));

$offset = ($pagina - 1) * $por_pagina;
$list_stmt = $db->prepare("SELECT * FROM newsletter_suscriptores WHERE {$where} ORDER BY fecha_suscripcion DESC LIMIT {$por_pagina} OFFSET {$offset}");
$list_stmt->execute($params);
$suscriptores = $list_stmt->fetchAll();

// Stats rápidas
$activos_es = (int)$db->query("SELECT COUNT(*) FROM newsletter_suscriptores WHERE activo = 1 AND confirmado = 1 AND idioma = 'es'")->fetchColumn();
$activos_en = (int)$db->query("SELECT COUNT(*) FROM newsletter_suscriptores WHERE activo = 1 AND confirmado = 1 AND idioma = 'en'")->fetchColumn();
$bajas_mes = (int)$db->query("SELECT COUNT(*) FROM newsletter_suscriptores WHERE activo = 0 AND fecha_baja >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();

// ─── Datos campañas ───
$campanas = $db->query("SELECT * FROM newsletter_campanas ORDER BY fecha_creacion DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Newsletter — EuryGo Admin</title>
  <link rel="stylesheet" href="/admin/assets/admin.css">
  <meta name="robots" content="noindex, nofollow">
</head>
<body class="admin-body">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <div class="admin-main">
    <?php include __DIR__ . '/partials/topbar.php'; ?>

    <div class="admin-content">
      <div class="admin-content__header">
        <h1>Newsletter</h1>
        <a href="/admin/nueva-campana.php" class="btn-admin btn-admin--primary">+ Nueva campaña</a>
      </div>

<?php if ($mensaje): ?>
      <div class="alert alert--success"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>
<?php if ($error): ?>
      <div class="alert alert--error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

      <!-- Tabs -->
      <div class="admin-tabs">
        <a href="?tab=suscriptores" class="admin-tabs__item<?= $tab === 'suscriptores' ? ' active' : '' ?>">Suscriptores</a>
        <a href="?tab=campanas" class="admin-tabs__item<?= $tab === 'campanas' ? ' active' : '' ?>">Campañas</a>
      </div>

<?php if ($tab === 'suscriptores'): ?>
      <!-- Stats rápidas -->
      <div class="stats-grid" style="margin-top:1.25rem;">
        <div class="stat-card">
          <div class="stat-card__number"><?= $activos_es ?></div>
          <div class="stat-card__label">Activos ES</div>
        </div>
        <div class="stat-card">
          <div class="stat-card__number"><?= $activos_en ?></div>
          <div class="stat-card__label">Activos EN</div>
        </div>
        <div class="stat-card">
          <div class="stat-card__number"><?= $bajas_mes ?></div>
          <div class="stat-card__label">Bajas este mes</div>
        </div>
      </div>

      <!-- Filtros -->
      <form class="admin-filters" method="GET">
        <input type="hidden" name="tab" value="suscriptores">
        <select name="idioma">
          <option value="">Todos los idiomas</option>
          <option value="es" <?= $filtro_idioma === 'es' ? 'selected' : '' ?>>ES</option>
          <option value="en" <?= $filtro_idioma === 'en' ? 'selected' : '' ?>>EN</option>
        </select>
        <select name="estado">
          <option value="">Todos los estados</option>
          <option value="activo" <?= $filtro_estado === 'activo' ? 'selected' : '' ?>>Activo</option>
          <option value="baja" <?= $filtro_estado === 'baja' ? 'selected' : '' ?>>Baja</option>
        </select>
        <select name="confirmado">
          <option value="">Confirmación</option>
          <option value="si" <?= $filtro_confirmado === 'si' ? 'selected' : '' ?>>Confirmado</option>
          <option value="no" <?= $filtro_confirmado === 'no' ? 'selected' : '' ?>>Pendiente</option>
        </select>
        <input type="text" name="buscar" placeholder="Buscar email o nombre..." value="<?= htmlspecialchars($filtro_buscar) ?>">
        <button type="submit" class="btn-admin btn-admin--outline btn-admin--sm">Filtrar</button>
        <a href="?tab=suscriptores&export=csv" class="btn-admin btn-admin--outline btn-admin--sm">Exportar CSV</a>
      </form>

      <!-- Tabla suscriptores -->
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Email</th>
              <th>Nombre</th>
              <th>Idioma</th>
              <th>Estado</th>
              <th>Confirmado</th>
              <th>Origen</th>
              <th>Fecha</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
<?php if (empty($suscriptores)): ?>
            <tr><td colspan="8" style="text-align:center;color:var(--admin-text-light);">No hay suscriptores</td></tr>
<?php else: ?>
<?php foreach ($suscriptores as $s): ?>
            <tr>
              <td><?= htmlspecialchars($s['email']) ?></td>
              <td><?= htmlspecialchars($s['nombre'] ?: '—') ?></td>
              <td><span class="badge badge--lang"><?= strtoupper($s['idioma']) ?></span></td>
              <td><?= $s['activo'] ? '<span class="badge badge--success">Activo</span>' : '<span class="badge badge--draft">Baja</span>' ?></td>
              <td><?= $s['confirmado'] ? 'Sí' : 'Pendiente' ?></td>
              <td><?= htmlspecialchars($s['origen']) ?></td>
              <td><?= formato_fecha($s['fecha_suscripcion'], 'es') ?></td>
              <td class="admin-table__actions">
<?php if ($s['activo']): ?>
                <form method="POST" style="display:inline;" onsubmit="return confirm('¿Dar de baja a este suscriptor?')">
                  <?= campo_csrf() ?>
                  <input type="hidden" name="accion" value="baja">
                  <input type="hidden" name="sub_id" value="<?= $s['id'] ?>">
                  <button class="btn-admin btn-admin--text btn-admin--sm" type="submit">Baja</button>
                </form>
<?php endif; ?>
                <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar permanentemente?')">
                  <?= campo_csrf() ?>
                  <input type="hidden" name="accion" value="eliminar">
                  <input type="hidden" name="sub_id" value="<?= $s['id'] ?>">
                  <button class="btn-admin btn-admin--danger btn-admin--sm" type="submit">Eliminar</button>
                </form>
              </td>
            </tr>
<?php endforeach; ?>
<?php endif; ?>
          </tbody>
        </table>
      </div>

<?php if ($total_paginas > 1): ?>
      <div class="admin-pagination">
<?php if ($pagina > 1): ?>
        <a href="?tab=suscriptores&pagina=<?= $pagina - 1 ?>&idioma=<?= $filtro_idioma ?>&estado=<?= $filtro_estado ?>&confirmado=<?= $filtro_confirmado ?>&buscar=<?= urlencode($filtro_buscar) ?>">Anterior</a>
<?php endif; ?>
        <span>Página <?= $pagina ?> de <?= $total_paginas ?></span>
<?php if ($pagina < $total_paginas): ?>
        <a href="?tab=suscriptores&pagina=<?= $pagina + 1 ?>&idioma=<?= $filtro_idioma ?>&estado=<?= $filtro_estado ?>&confirmado=<?= $filtro_confirmado ?>&buscar=<?= urlencode($filtro_buscar) ?>">Siguiente</a>
<?php endif; ?>
      </div>
<?php endif; ?>

<?php else: /* tab = campanas */ ?>
      <!-- Tabla campañas -->
      <div class="admin-table-wrap" style="margin-top:1.25rem;">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Asunto</th>
              <th>Idioma</th>
              <th>Enviados</th>
              <th>Abiertos</th>
              <th>Clics</th>
              <th>Estado</th>
              <th>Fecha</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
<?php if (empty($campanas)): ?>
            <tr><td colspan="8" style="text-align:center;color:var(--admin-text-light);">No hay campañas</td></tr>
<?php else: ?>
<?php foreach ($campanas as $c): ?>
            <tr>
              <td class="admin-table__title"><?= htmlspecialchars($c['asunto']) ?></td>
              <td><span class="badge badge--lang"><?= strtoupper($c['idioma']) ?></span></td>
              <td><?= $c['total_enviados'] ?></td>
              <td><?= $c['total_abiertos'] ?> <?= $c['total_enviados'] > 0 ? '<small>(' . round(($c['total_abiertos'] / $c['total_enviados']) * 100, 1) . '%)</small>' : '' ?></td>
              <td><?= $c['total_clics'] ?></td>
              <td>
<?php
$badge_class = match($c['estado']) {
    'enviada' => 'badge--success',
    'programada' => 'badge--lang',
    'enviando' => 'badge--lang',
    default => 'badge--draft',
};
?>
                <span class="badge <?= $badge_class ?>"><?= ucfirst($c['estado']) ?></span>
              </td>
              <td><?= $c['fecha_envio'] ? formato_fecha($c['fecha_envio'], 'es') : ($c['fecha_programada'] ? formato_fecha($c['fecha_programada'], 'es') : '—') ?></td>
              <td class="admin-table__actions">
<?php if ($c['brevo_campaign_id']): ?>
                <form method="POST" style="display:inline;">
                  <?= campo_csrf() ?>
                  <input type="hidden" name="accion" value="sync_stats">
                  <input type="hidden" name="campana_id" value="<?= $c['id'] ?>">
                  <button class="btn-admin btn-admin--outline btn-admin--sm" type="submit">Actualizar stats</button>
                </form>
<?php endif; ?>
<?php if ($c['estado'] === 'borrador'): ?>
                <a href="/admin/nueva-campana.php?id=<?= $c['id'] ?>" class="btn-admin btn-admin--text btn-admin--sm">Editar</a>
<?php endif; ?>
              </td>
            </tr>
<?php endforeach; ?>
<?php endif; ?>
          </tbody>
        </table>
      </div>
      <p style="margin-top:1rem;font-size:0.85rem;color:var(--admin-text-light);">Referencia del sector educativo: tasa de apertura media 25-35%, tasa de clics 2-5%.</p>
<?php endif; ?>
    </div>
  </div>
</body>
</html>
