<?php
/**
 * Training Courses KA1 — Course detail (EN)
 * Receives ?slug=xxx via .htaccess
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/tracker.php';

iniciar_sesion_segura();
$idioma = 'en';
$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    header('Location: /en/cursos/');
    exit;
}

$db = get_db();

// Get course in current language
$stmt = $db->prepare("SELECT * FROM cursos WHERE slug = :slug AND idioma = :idioma AND estado = 'publicado' LIMIT 1");
$stmt->execute([':slug' => $slug, ':idioma' => $idioma]);
$curso = $stmt->fetch();

// Fallback: if not found in this language, search any language and redirect
if (!$curso) {
    $stmt = $db->prepare("SELECT slug, idioma FROM cursos WHERE slug = :slug AND estado = 'publicado' LIMIT 1");
    $stmt->execute([':slug' => $slug]);
    $curso_otro = $stmt->fetch();
    if ($curso_otro) {
        $redirect = $curso_otro['idioma'] === 'en' ? '/en/cursos/' : '/cursos/';
        header('Location: ' . $redirect . $curso_otro['slug'] . '/', true, 301);
        exit;
    }
    http_response_code(404);
    $titulo_pagina = 'Course not found | EuryGo';
    $meta_description = '';
    $url_canonica = SITE_URL . '/en/cursos/';
    require_once __DIR__ . '/../../includes/header.php';
    echo '<main id="main"><div class="container" style="padding: var(--space-2xl) 0; text-align:center;">';
    echo '<h1>Course not found</h1><p>The course you are looking for does not exist or is not available.</p>';
    echo '<a href="/en/cursos/" class="btn btn--primary">View all courses</a></div></main>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

// Get translated course slug (for language switcher)
$slug_traduccion = '';
if ($curso['traduccion_id']) {
    $stmt_trad = $db->prepare("SELECT slug FROM cursos WHERE id = :id AND estado = 'publicado' LIMIT 1");
    $stmt_trad->execute([':id' => $curso['traduccion_id']]);
    $trad = $stmt_trad->fetch();
    if ($trad) { $slug_traduccion = $trad['slug']; }
}

registrar_visita($db);

// Course photos (slider). Table may not exist yet → empty array.
$curso_fotos = [];
try {
    $stmt_f = $db->prepare("SELECT nombre_archivo, alt_text FROM curso_fotos WHERE curso_id = ? AND activa = 1 ORDER BY orden ASC, id ASC");
    $stmt_f->execute([$curso['id']]);
    $curso_fotos = $stmt_f->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// Get programme
$stmt_prog = $db->prepare("SELECT * FROM cursos_programa WHERE curso_id = :id ORDER BY dia ASC, orden ASC");
$stmt_prog->execute([':id' => $curso['id']]);
$programa = $stmt_prog->fetchAll();

// Group by day
$dias = [];
foreach ($programa as $act) {
    $dias[$act['dia']][] = $act;
}

// Load open editions
$stmt_ed = $db->prepare("SELECT * FROM cursos_ediciones WHERE curso_id = :id AND estado = 'abierta' ORDER BY fecha_inicio ASC");
$stmt_ed->execute([':id' => $curso['id']]);
$ediciones = $stmt_ed->fetchAll();

$plazas_disponibles = 0;
foreach ($ediciones as $ed) { $plazas_disponibles += $ed['plazas_disponibles']; }
if (empty($ediciones)) { $plazas_disponibles = $curso['plazas'] - $curso['inscritos']; }

$fecha_inicio_fmt = !empty($ediciones) ? date('d/m/Y', strtotime($ediciones[0]['fecha_inicio'])) : ($curso['fecha_inicio'] ? date('d/m/Y', strtotime($curso['fecha_inicio'])) : '—');
$fecha_fin_fmt = !empty($ediciones) ? date('d/m/Y', strtotime($ediciones[0]['fecha_fin'])) : ($curso['fecha_fin'] ? date('d/m/Y', strtotime($curso['fecha_fin'])) : '');

// SEO
$titulo_pagina = $curso['meta_title'] ?: $curso['titulo'] . ' | EuryGo';
$meta_description = $curso['meta_description'] ?: $curso['extracto'];
$url_canonica = SITE_URL . '/en/cursos/' . $curso['slug'] . '/';

$schema_json = json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Course',
    'name' => $curso['titulo'],
    'description' => $curso['extracto'],
    'provider' => [
        '@type' => 'Organization',
        'name' => 'EuryGo',
        'url' => SITE_URL
    ],
    'url' => $url_canonica,
    'courseMode' => 'onsite',
    'inLanguage' => $idioma,
    'offers' => [
        '@type' => 'Offer',
        'price' => $curso['precio'],
        'priceCurrency' => $curso['moneda'],
        'availability' => $plazas_disponibles > 0 ? 'https://schema.org/InStock' : 'https://schema.org/SoldOut',
        'validFrom' => $curso['created_at']
    ],
    'hasCourseInstance' => array_map(function($ed) use ($curso) {
        return [
            '@type' => 'CourseInstance',
            'courseMode' => 'onsite',
            'startDate' => $ed['fecha_inicio'],
            'endDate' => $ed['fecha_fin'],
            'location' => [
                '@type' => 'Place',
                'name' => $curso['ubicacion'],
                'address' => $curso['ubicacion']
            ]
        ];
    }, !empty($ediciones) ? $ediciones : [['fecha_inicio' => $curso['fecha_inicio'], 'fecha_fin' => $curso['fecha_fin']]])
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

require_once __DIR__ . '/../../includes/header.php';

$csrf_token = generar_csrf();

// Alternate language URL for language switcher
$alt_lang_url = $slug_traduccion ? '/cursos/' . $slug_traduccion . '/' : '/cursos/';
?>

  <main id="main" class="course-detail" data-alt-lang-url="<?= htmlspecialchars($alt_lang_url) ?>">
    <div class="container">

      <!-- Breadcrumb -->
      <nav class="course-detail__breadcrumb" aria-label="Breadcrumb">
        <a href="/en/">Home</a> <span class="separator">/</span>
        <a href="/en/cursos/">Courses</a> <span class="separator">/</span>
        <span><?= htmlspecialchars(mb_substr($curso['titulo'], 0, 50)) ?>…</span>
      </nav>

      <!-- Header -->
      <div class="course-detail__header">
        <h1 class="course-detail__title"><?= htmlspecialchars($curso['titulo']) ?></h1>
        <div class="course-detail__meta">
          <div class="course-detail__meta-card">
            <svg viewBox="0 0 24 24"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zm0-12H5V6h14v2z"/></svg>
            <span><strong><?= $fecha_inicio_fmt ?></strong> — <?= $fecha_fin_fmt ?></span>
          </div>
          <div class="course-detail__meta-card">
            <svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
            <span><?= htmlspecialchars($curso['ubicacion']) ?></span>
          </div>
          <div class="course-detail__meta-card">
            <svg viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
            <span><?= $curso['duracion_dias'] ?> days</span>
          </div>
          <div class="course-detail__meta-card">
            <svg viewBox="0 0 24 24"><path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.72L12 15l5-2.73v3.72z"/></svg>
            <span>Erasmus+ KA1</span>
          </div>
        </div>
      </div>

<?php if (!empty($curso_fotos)): ?>
      <!-- Course photo slider -->
      <div class="curso-slider" id="slider-<?= $curso['id'] ?>" data-fotos="<?= count($curso_fotos) ?>" aria-label="Course photo gallery">
        <div class="slider-track">
<?php foreach ($curso_fotos as $idx => $f):
    $alt = $f['alt_text'] ?: $curso['titulo'];
    $src = '/uploads/cursos/' . (int)$curso['id'] . '/' . basename($f['nombre_archivo']);
?>
          <div class="slide<?= $idx === 0 ? ' active' : '' ?>">
            <img src="<?= htmlspecialchars($src) ?>" alt="<?= htmlspecialchars($alt) ?>"<?= $idx === 0 ? '' : ' loading="lazy"' ?>>
          </div>
<?php endforeach; ?>
        </div>
<?php if (count($curso_fotos) > 1): ?>
        <button class="slider-btn slider-prev" type="button" aria-label="Previous photo">&#8249;</button>
        <button class="slider-btn slider-next" type="button" aria-label="Next photo">&#8250;</button>
        <div class="slider-dots">
<?php foreach ($curso_fotos as $idx => $f): ?>
          <button class="dot<?= $idx === 0 ? ' active' : '' ?>" type="button" aria-label="Photo <?= $idx + 1 ?>"></button>
<?php endforeach; ?>
        </div>
<?php endif; ?>
      </div>
<?php endif; ?>

      <!-- Layout: content + sidebar -->
      <div class="course-detail__layout">

        <!-- Main content -->
        <div class="course-detail__content">
<?php
          // Split description from OPTIONAL blocks so "What is Included" can sit between them.
          $desc_full = (string)$curso['descripcion'];
          $marker    = '<!-- EURYGO_OPTIONALS_V8_START -->';
          $pos_marker = strpos($desc_full, $marker);
          if ($pos_marker !== false) {
              $desc_limpia  = rtrim(substr($desc_full, 0, $pos_marker));
              $desc_optional = substr($desc_full, $pos_marker);
          } else {
              $desc_limpia  = $desc_full;
              $desc_optional = '';
          }
?>
          <?= $desc_limpia ?>

          <!-- Day-by-day programme -->
<?php if (!empty($dias)): ?>
          <h2>Day-by-day Programme</h2>
          <div class="programme">
<?php foreach ($dias as $dia_num => $actividades): ?>
            <div class="programme__day">
              <div class="programme__day-header" role="button" aria-expanded="<?= $dia_num <= 2 ? 'true' : 'false' ?>" onclick="this.setAttribute('aria-expanded', this.getAttribute('aria-expanded')==='true'?'false':'true'); this.nextElementSibling.style.display = this.getAttribute('aria-expanded')==='true'?'block':'none';">
                <span class="programme__day-number"><?= $dia_num ?></span>
                <span class="programme__day-title">Day <?= $dia_num ?> — <?= htmlspecialchars($actividades[0]['titulo']) ?></span>
                <span class="programme__day-toggle">▼</span>
              </div>
              <div class="programme__activities" style="<?= $dia_num > 2 ? 'display:none;' : '' ?>">
<?php foreach ($actividades as $act): ?>
                <div class="programme__activity <?= $act['tipo'] === 'excursion' ? 'programme__activity--excursion' : '' ?>">
                  <div class="programme__activity-time"><?= htmlspecialchars($act['horario']) ?></div>
                  <div class="programme__activity-title"><?= htmlspecialchars($act['titulo']) ?></div>
                  <div class="programme__activity-desc"><?= htmlspecialchars($act['descripcion']) ?></div>
                  <span class="programme__activity-tag programme__activity-tag--<?= $act['tipo'] ?>">
                    <?= $act['tipo'] === 'sesion' ? 'Session' : ($act['tipo'] === 'excursion' ? 'Excursion' : 'Activity') ?>
                  </span>
                </div>
<?php endforeach; ?>
              </div>
            </div>
<?php endforeach; ?>
          </div>
<?php endif; ?>

          <?php include __DIR__ . '/../../includes/what-included.php'; ?>

          <?= $desc_optional ?>
        </div>

        <!-- Sidebar -->
        <aside class="course-sidebar">
          <div class="course-sidebar__card">
            <div class="course-sidebar__price">
              <?= number_format($curso['precio'], 0, ',', '.') ?> € <small>/ participant</small>
            </div>
            <div style="font-size:0.78rem; color:#64748B; margin-top:-0.5rem; margin-bottom:0.75rem;">Fundable with Erasmus+ KA1 grants</div>

<?php if ($plazas_disponibles <= 0): ?>
            <div class="course-sidebar__spots course-sidebar__spots--full">Sold out</div>
<?php elseif ($plazas_disponibles <= 5): ?>
            <div class="course-sidebar__spots course-sidebar__spots--low">Only <?= $plazas_disponibles ?> spots left!</div>
<?php else: ?>
            <div class="course-sidebar__spots"><?= $plazas_disponibles ?> spots available</div>
<?php endif; ?>

            <ul class="course-sidebar__list">
<?php if (!empty($ediciones)): ?>
<?php   foreach ($ediciones as $ed): ?>
              <li>
                <svg viewBox="0 0 24 24"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10z"/></svg>
                <span><?= date('d/m/Y', strtotime($ed['fecha_inicio'])) ?> — <?= date('d/m/Y', strtotime($ed['fecha_fin'])) ?> <small style="color:#64748B;">(<?= $ed['plazas_disponibles'] ?> spots)</small></span>
              </li>
<?php   endforeach; ?>
<?php else: ?>
              <li>
                <svg viewBox="0 0 24 24"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10z"/></svg>
                <span><?= $fecha_inicio_fmt ?> — <?= $fecha_fin_fmt ?></span>
              </li>
<?php endif; ?>
              <li>
                <svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7z"/></svg>
                <span><?= htmlspecialchars($curso['ubicacion']) ?></span>
              </li>
              <li>
                <svg viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                <span><?= $curso['plazas'] ?> max participants</span>
              </li>
              <li>
                <svg viewBox="0 0 24 24"><path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3z"/></svg>
                <span>Europass certificate included</span>
              </li>
              <li>
                <svg viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg>
                <span><?= $curso['duracion_dias'] ?> days / <?= $curso['duracion_dias'] - 1 ?> nights</span>
              </li>
            </ul>

<?php if ($plazas_disponibles > 0): ?>
            <a href="#enrolment" class="btn btn--eu btn--lg" style="width:100%; text-align:center; display:block;">Enrol now</a>
<?php else: ?>
            <a href="/en/#contact" class="btn btn--outline btn--lg" style="width:100%; text-align:center; display:block;">Waiting list</a>
<?php endif; ?>
          </div>
        </aside>
      </div>

      <!-- Enrolment form -->
<?php if ($plazas_disponibles > 0): ?>
      <div class="enrollment-form" id="enrolment">
        <h2 class="enrollment-form__title">Enrolment Form</h2>
        <p class="enrollment-form__subtitle">Fill in the form to reserve your spot on this course. We will contact you to confirm your enrolment.</p>

        <form id="enrollment-form" class="enrollment-form__grid" novalidate>
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
          <input type="hidden" name="curso_id" value="<?= $curso['id'] ?>">

<?php if (count($ediciones) > 1): ?>
          <div class="enrollment-form__group enrollment-form__group--full">
            <div class="edition-selector">
              <div class="edition-selector__title">Select your edition *</div>
              <div class="edition-selector__list">
<?php   foreach ($ediciones as $i => $ed): ?>
                <label class="edition-option" onclick="this.querySelector('input').checked=true; document.querySelectorAll('.edition-option').forEach(e=>e.classList.remove('edition-option--selected')); this.classList.add('edition-option--selected');">
                  <input type="radio" name="edicion_id" value="<?= $ed['id'] ?>" <?= $i === 0 ? 'checked' : '' ?> required>
                  <div class="edition-option__info">
                    <div class="edition-option__dates"><?= date('d/m/Y', strtotime($ed['fecha_inicio'])) ?> — <?= date('d/m/Y', strtotime($ed['fecha_fin'])) ?></div>
                    <div class="edition-option__spots <?= $ed['plazas_disponibles'] <= 3 ? 'edition-option__spots--low' : '' ?>">
                      <?= $ed['plazas_disponibles'] ?> spots available
                    </div>
                  </div>
                </label>
<?php   endforeach; ?>
              </div>
            </div>
          </div>
<?php elseif (count($ediciones) === 1): ?>
          <input type="hidden" name="edicion_id" value="<?= $ediciones[0]['id'] ?>">
<?php endif; ?>

          <div class="enrollment-form__group">
            <label class="enrollment-form__label" for="enroll-nombre">Full name *</label>
            <input class="enrollment-form__input" type="text" id="enroll-nombre" name="nombre" required>
          </div>

          <div class="enrollment-form__group">
            <label class="enrollment-form__label" for="enroll-email">Email *</label>
            <input class="enrollment-form__input" type="email" id="enroll-email" name="email" required>
          </div>

          <div class="enrollment-form__group">
            <label class="enrollment-form__label" for="enroll-telefono">Phone</label>
            <input class="enrollment-form__input" type="tel" id="enroll-telefono" name="telefono">
          </div>

          <div class="enrollment-form__group">
            <label class="enrollment-form__label" for="enroll-pais">Country *</label>
            <input class="enrollment-form__input" type="text" id="enroll-pais" name="pais" required>
          </div>

          <div class="enrollment-form__group enrollment-form__group--full">
            <label class="enrollment-form__label" for="enroll-organizacion">School / Organisation</label>
            <input class="enrollment-form__input" type="text" id="enroll-organizacion" name="organizacion">
          </div>

          <div class="enrollment-form__group enrollment-form__group--full">
            <label class="enrollment-form__label" for="enroll-mensaje">Message (optional)</label>
            <textarea class="enrollment-form__textarea" id="enroll-mensaje" name="mensaje" rows="3"></textarea>
          </div>

          <div class="enrollment-form__rgpd">
            <input type="checkbox" id="enroll-rgpd" name="rgpd" required>
            <label for="enroll-rgpd">I accept the <a href="/en/privacy/" target="_blank">privacy policy</a> and the processing of my data to manage my enrolment in this course. *</label>
          </div>

          <div class="enrollment-form__actions">
            <button type="submit" class="btn btn--eu btn--lg">Submit enrolment</button>
          </div>

          <div class="enrollment-form__feedback" id="enrollment-feedback" style="display:none;"></div>
        </form>
      </div>
<?php endif; ?>

    </div>
  </main>

  <script>
  document.getElementById('enrollment-form')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const form = e.target;
    const feedback = document.getElementById('enrollment-feedback');
    const btn = form.querySelector('button[type="submit"]');

    const nombre = form.nombre.value.trim();
    const email = form.email.value.trim();
    const pais = form.pais.value.trim();
    const rgpd = form.rgpd.checked;

    if (!nombre || !email || !pais) {
      feedback.style.display = 'block';
      feedback.className = 'enrollment-form__feedback enrollment-form__feedback--error';
      feedback.textContent = 'Please fill in all required fields.';
      return;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      feedback.style.display = 'block';
      feedback.className = 'enrollment-form__feedback enrollment-form__feedback--error';
      feedback.textContent = 'Please enter a valid email address.';
      return;
    }
    if (!rgpd) {
      feedback.style.display = 'block';
      feedback.className = 'enrollment-form__feedback enrollment-form__feedback--error';
      feedback.textContent = 'You must accept the privacy policy.';
      return;
    }

    btn.disabled = true;
    btn.textContent = 'Sending…';

    try {
      // Get a fresh CSRF token before sending
      const csrfRes = await fetch('/contacto/csrf.php');
      const csrfData = await csrfRes.json();
      const tokenField = form.querySelector('[name="csrf_token"]');
      if (tokenField && csrfData.token) {
        tokenField.value = csrfData.token;
      }

      const res = await fetch('/cursos/inscribir.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams(new FormData(form)).toString()
      });
      const data = await res.json();
      feedback.style.display = 'block';
      if (data.ok) {
        feedback.className = 'enrollment-form__feedback enrollment-form__feedback--ok';
        feedback.textContent = data.mensaje;
        form.reset();
      } else {
        feedback.className = 'enrollment-form__feedback enrollment-form__feedback--error';
        feedback.textContent = data.error || 'Error submitting enrolment.';
      }
    } catch (err) {
      feedback.style.display = 'block';
      feedback.className = 'enrollment-form__feedback enrollment-form__feedback--error';
      feedback.textContent = 'Connection error. Please try again.';
    }

    btn.disabled = false;
    btn.textContent = 'Submit enrolment';
  });
  </script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
