<?php
/**
 * Back Office — Dashboard
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/funciones.php';

requiere_login();

$db = get_db();

// Estadísticas
$total_pub = (int)$db->query("SELECT COUNT(*) FROM articulos WHERE publicado = 1")->fetchColumn();
$total_borr = (int)$db->query("SELECT COUNT(*) FROM articulos WHERE publicado = 0")->fetchColumn();

// Formularios sin leer (tabla puede no existir todavía)
$forms_sin_leer = 0;
try {
    $forms_sin_leer = (int)$db->query("SELECT COUNT(*) FROM formularios_contacto WHERE leido = 0")->fetchColumn();
} catch (PDOException $e) {}

// Inscripciones pendientes en cursos
$insc_pendientes = 0;
try {
    $insc_pendientes = (int)$db->query("SELECT COUNT(*) FROM cursos_inscripciones WHERE estado = 'pendiente'")->fetchColumn();
} catch (PDOException $e) {}

// CRM widget — estado de captación Andalucía (la tabla puede no existir aún)
$crm_stats = null;
try {
    $row = $db->query("
        SELECT
          COUNT(*) AS total,
          SUM(CASE WHEN contacto_telefono IS NOT NULL AND contacto_telefono <> '' THEN 1 ELSE 0 END) AS con_tel,
          SUM(CASE WHEN contacto_email IS NOT NULL AND contacto_email <> '' THEN 1 ELSE 0 END) AS con_email,
          SUM(CASE WHEN tipo_accion = 'KA121-SCH' THEN 1 ELSE 0 END) AS ka121,
          SUM(CASE WHEN tipo_accion = 'KA122-SCH' THEN 1 ELSE 0 END) AS ka122,
          SUM(CASE WHEN estado = 'sin_contactar' THEN 1 ELSE 0 END) AS sin_contactar,
          SUM(CASE WHEN estado NOT IN ('sin_contactar','cliente','descartado','no_interesado') THEN 1 ELSE 0 END) AS en_proceso,
          SUM(CASE WHEN estado = 'cliente' THEN 1 ELSE 0 END) AS clientes
        FROM crm_contactos
        WHERE pata = 'centros' AND comunidad = 'Andalucía'
    ")->fetch();
    if ($row && $row['total'] > 0) {
        $crm_stats = $row;
    }
} catch (PDOException $e) {}

// Por categoría
$cats_stmt = $db->query("SELECT categoria, COUNT(*) as total FROM articulos WHERE publicado = 1 GROUP BY categoria ORDER BY total DESC");
$categorias_stats = $cats_stmt->fetchAll();
$max_cat = $categorias_stats ? max(array_column($categorias_stats, 'total')) : 1;

// Último artículo publicado
$ultimo = $db->query("SELECT titulo, fecha_publicacion FROM articulos WHERE publicado = 1 ORDER BY fecha_publicacion DESC LIMIT 1")->fetch();

$nombre = htmlspecialchars($_SESSION['admin_nombre'] ?? 'Admin');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard — EuryGo Admin</title>
  <link rel="stylesheet" href="/admin/assets/admin.css">
  <meta name="robots" content="noindex, nofollow">
</head>
<body class="admin-body">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <div class="admin-main">
    <?php include __DIR__ . '/partials/topbar.php'; ?>

    <div class="admin-content">
      <h1>Bienvenido, <?= $nombre ?></h1>

<?php if ($forms_sin_leer > 0): ?>
      <div class="alert alert--error">Tienes <?= $forms_sin_leer ?> consulta<?= $forms_sin_leer > 1 ? 's' : '' ?> sin leer. <a href="/admin/estadisticas.php#formularios" style="color:inherit;font-weight:700;">Ver ahora &rarr;</a></div>
<?php endif; ?>
<?php if ($insc_pendientes > 0): ?>
      <div class="alert alert--warning" style="background:#fef3c7;border-color:#f59e0b;color:#92400e;">Tienes <?= $insc_pendientes ?> inscripci<?= $insc_pendientes > 1 ? 'ones pendientes' : 'ón pendiente' ?> en cursos. <a href="/admin/inscripciones.php?estado=pendiente" style="color:inherit;font-weight:700;">Gestionar &rarr;</a></div>
<?php endif; ?>

<?php if ($total_borr > 0): ?>
      <div class="alert alert--warning">Tienes <?= $total_borr ?> artículo<?= $total_borr > 1 ? 's' : '' ?> en borrador pendiente<?= $total_borr > 1 ? 's' : '' ?> de publicar.</div>
<?php endif; ?>

      <!-- Stats cards -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-card__number"><?= $total_pub ?></div>
          <div class="stat-card__label">Publicados</div>
        </div>
        <div class="stat-card">
          <div class="stat-card__number"><?= $total_borr ?></div>
          <div class="stat-card__label">Borradores</div>
        </div>
        <div class="stat-card stat-card--wide">
          <div class="stat-card__label">Artículos por categoría</div>
          <div class="mini-chart">
<?php foreach ($categorias_stats as $cs): ?>
            <div class="mini-chart__row">
              <span class="mini-chart__label"><?= nombre_categoria($cs['categoria'], 'es') ?></span>
              <div class="mini-chart__bar-bg">
                <div class="mini-chart__bar" style="width: <?= round(($cs['total'] / $max_cat) * 100) ?>%"></div>
              </div>
              <span class="mini-chart__value"><?= $cs['total'] ?></span>
            </div>
<?php endforeach; ?>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-card__label">Último publicado</div>
<?php if ($ultimo): ?>
          <div class="stat-card__text"><?= htmlspecialchars(truncar($ultimo['titulo'], 60)) ?></div>
          <div class="stat-card__date"><?= formato_fecha($ultimo['fecha_publicacion'], 'es') ?></div>
<?php else: ?>
          <div class="stat-card__text">Ninguno todavía</div>
<?php endif; ?>
        </div>
      </div>

      <!-- Quick actions -->
      <div class="quick-actions">
        <a href="/admin/editor.php" class="btn-admin btn-admin--primary">+ Nuevo artículo</a>
        <a href="/admin/articulos.php" class="btn-admin btn-admin--outline">Ver todos los artículos</a>
      </div>

<?php if ($crm_stats): $t = (int)$crm_stats['total']; ?>
      <!-- CRM widget -->
      <div class="crm-widget">
        <h3>📋 CRM Captación — Andalucía</h3>
        <div class="crm-widget__row">
          <div><span>Centros Andalucía</span><strong><?= $t ?></strong></div>
          <div><span>Sin contactar 🔴</span><strong><?= (int)$crm_stats['sin_contactar'] ?></strong></div>
          <div><span>Con teléfono 📞</span><strong><?= (int)$crm_stats['con_tel'] ?><span class="crm-widget__pct"><?= $t > 0 ? round($crm_stats['con_tel']/$t*100) : 0 ?>%</span></strong></div>
          <div><span>En proceso 🟡</span><strong><?= (int)$crm_stats['en_proceso'] ?></strong></div>
          <div><span>Con email ✉️</span><strong><?= (int)$crm_stats['con_email'] ?><span class="crm-widget__pct"><?= $t > 0 ? round($crm_stats['con_email']/$t*100) : 0 ?>%</span></strong></div>
          <div><span>Clientes 🟢</span><strong style="color:#16a34a;"><?= (int)$crm_stats['clientes'] ?></strong></div>
          <div><span><span class="badge-ka121">KA121</span> Acreditados</span><strong><?= (int)$crm_stats['ka121'] ?></strong></div>
          <div><span><span class="badge-ka122">KA122</span> Corta duración</span><strong><?= (int)$crm_stats['ka122'] ?></strong></div>
        </div>
        <div class="crm-widget__cta">
          <a href="/admin/crm/centros.php?ccaa=Andaluc%C3%ADa" class="btn-admin btn-admin--primary btn-admin--sm">→ Ir al CRM</a>
        </div>
      </div>
<?php endif; ?>
    </div>
  </div>
</body>
</html>
