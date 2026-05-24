<?php
/**
 * Training Courses KA1 — Public listing (EN)
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/tracker.php';

$idioma = 'en';
$db = get_db();
registrar_visita($db);

// Get published courses
$stmt = $db->prepare("
    SELECT * FROM cursos
    WHERE estado = 'publicado' AND idioma = :idioma
    ORDER BY destacado DESC, fecha_inicio ASC
");
$stmt->execute([':idioma' => $idioma]);
$cursos = $stmt->fetchAll();

// Load open editions per course
$ediciones_por_curso = [];
$stmt_ed = $db->query("SELECT * FROM cursos_ediciones WHERE estado = 'abierta' ORDER BY fecha_inicio ASC");
foreach ($stmt_ed->fetchAll() as $ed) {
    $ediciones_por_curso[$ed['curso_id']][] = $ed;
}

// SEO
$titulo_pagina = 'Erasmus+ KA1 Training Courses | EuryGo';
$meta_description = 'Structured training courses for European educators in Jerez de la Frontera. Spanish education system, AI in education, active methodologies. Erasmus+ certificate included.';
$url_canonica = SITE_URL . '/en/cursos/';
$schema_json = json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'ItemList',
    'name' => 'EuryGo Training Courses',
    'itemListElement' => array_map(function($c, $i) {
        return [
            '@type' => 'ListItem',
            'position' => $i + 1,
            'url' => SITE_URL . '/en/cursos/' . $c['slug'] . '/',
            'name' => $c['titulo']
        ];
    }, $cursos, array_keys($cursos))
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

require_once __DIR__ . '/../../includes/header.php';
?>

  <main id="main" class="course-listing" data-alt-lang-url="/cursos/">
    <div class="container">
      <div class="section__header" style="padding-top: var(--space-lg);">
        <span class="section__tag">Erasmus+ KA1 Training</span>
        <h1 class="section__title">Training Courses for Educators</h1>
        <p class="section__subtitle">Structured 5-day programmes in Jerez de la Frontera. Certified KA1 academic training and cultural immersion in the heart of Andalusia.</p>
      </div>

<?php if (empty($cursos)): ?>
      <div style="text-align:center; padding: var(--space-xl) 0;">
        <p>No courses available at the moment. Check back soon for upcoming editions.</p>
        <a href="/en/#contact" class="btn btn--primary" style="margin-top: var(--space-md);">Request information</a>
      </div>
<?php else: ?>
      <div class="courses-grid">
<?php foreach ($cursos as $curso): ?>
<?php
    $eds = $ediciones_por_curso[$curso['id']] ?? [];
    $plazas_disponibles = 0;
    foreach ($eds as $ed) { $plazas_disponibles += $ed['plazas_disponibles']; }
    if (empty($eds)) { $plazas_disponibles = $curso['plazas'] - $curso['inscritos']; }
?>
        <article class="course-card">
          <div class="course-card__image <?= empty($curso['imagen']) ? 'course-card__image--placeholder' : '' ?>">
<?php if (!empty($curso['imagen'])): ?>
            <img src="<?= htmlspecialchars($curso['imagen']) ?>" alt="<?= htmlspecialchars($curso['titulo']) ?>" loading="lazy">
<?php else: ?>
            <svg viewBox="0 0 24 24"><path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.72L12 15l5-2.73v3.72z"/></svg>
<?php endif; ?>
<?php if ($curso['destacado']): ?>
            <span class="course-card__badge">Featured</span>
<?php endif; ?>
          </div>
          <div class="course-card__body">
            <div class="course-card__meta">
<?php if (!empty($eds)): ?>
              <div class="course-card__editions">
<?php   foreach ($eds as $ed):
            $pronto = ($ed['fecha_inicio'] <= date('Y-m-d', strtotime('+30 days')));
            $badge_class = $pronto ? 'course-card__edition-badge--soon' : '';
?>
                <span class="course-card__edition-badge <?= $badge_class ?>">
                  <svg viewBox="0 0 24 24" width="14" height="14"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zm0-12H5V6h14v2z"/></svg>
                  <?= date('d M', strtotime($ed['fecha_inicio'])) ?> — <?= date('d M Y', strtotime($ed['fecha_fin'])) ?>
                </span>
<?php   endforeach; ?>
              </div>
<?php endif; ?>
              <span class="course-card__meta-item">
                <svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                <?= htmlspecialchars($curso['ubicacion']) ?>
              </span>
              <span class="course-card__meta-item">
                <svg viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
                <?= $curso['duracion_dias'] ?> days
              </span>
            </div>
            <h2 class="course-card__title">
              <a href="/en/cursos/<?= htmlspecialchars($curso['slug']) ?>/"><?= htmlspecialchars($curso['titulo']) ?></a>
            </h2>
            <p class="course-card__excerpt"><?= htmlspecialchars(mb_substr($curso['extracto'], 0, 160)) ?>…</p>
            <div class="course-card__footer">
              <span class="course-card__price"><?= number_format($curso['precio'], 0, ',', '.') ?> € <small>/ participant</small></span>
<?php if ($plazas_disponibles <= 0): ?>
              <span class="course-card__spots course-card__spots--low">Sold out</span>
<?php elseif ($plazas_disponibles <= 5): ?>
              <span class="course-card__spots course-card__spots--low">Only <?= $plazas_disponibles ?> spots left!</span>
<?php else: ?>
              <span class="course-card__spots"><?= $plazas_disponibles ?> spots available</span>
<?php endif; ?>
            </div>
          </div>
        </article>
<?php endforeach; ?>
      </div>
<?php endif; ?>

      <!-- CTA -->
      <div class="article__cta" style="margin-top: var(--space-xl); margin-bottom: var(--space-xl);">
        <h3>Need a custom course for your group?</h3>
        <p>We design tailored programmes adapted to your school or agency needs. Contact us for a no-obligation proposal.</p>
        <a href="/en/#contact" class="btn btn--gold">Contact us</a>
      </div>
    </div>
  </main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
