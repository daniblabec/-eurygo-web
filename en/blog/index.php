<?php
/**
 * Blog — Listado dinámico de artículos (EN)
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/tracker.php';

iniciar_sesion_segura(); // necesario para campo_csrf() en el form de newsletter
$idioma = 'en';
$por_pagina = 6;
$pagina = max(1, (int)($_GET['page'] ?? 1));
$offset = ($pagina - 1) * $por_pagina;
$categoria_filtro = $_GET['category'] ?? null;

$db = get_db();
registrar_visita($db);

// Contar total
$sql_count = "SELECT COUNT(*) FROM articulos WHERE publicado = 1 AND idioma = :idioma";
$params_count = [':idioma' => $idioma];
if ($categoria_filtro && in_array($categoria_filtro, ['centros','agencias','erasmus','novedades','casos-exito'])) {
    $sql_count .= " AND categoria = :cat";
    $params_count[':cat'] = $categoria_filtro;
}
$stmt = $db->prepare($sql_count);
$stmt->execute($params_count);
$total = (int)$stmt->fetchColumn();
$total_paginas = max(1, (int)ceil($total / $por_pagina));

// Obtener artículos
$sql = "SELECT * FROM articulos WHERE publicado = 1 AND idioma = :idioma";
$params = [':idioma' => $idioma];
if ($categoria_filtro && in_array($categoria_filtro, ['centros','agencias','erasmus','novedades','casos-exito'])) {
    $sql .= " AND categoria = :cat";
    $params[':cat'] = $categoria_filtro;
}
$sql .= " ORDER BY fecha_publicacion DESC LIMIT $por_pagina OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$articulos = $stmt->fetchAll();

// SEO
$titulo_pagina = 'Blog | EuryGo — Erasmus+, educational mobility and accreditations';
$meta_description = 'Guides, news and success stories about Erasmus+ and European educational mobility. Articles for schools and educational travel agencies.';
$url_canonica = SITE_URL . '/en/blog/';
$schema_json = json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => 'EuryGo',
    'url' => SITE_URL,
    'sameAs' => ['https://www.linkedin.com/in/eurygo','https://www.instagram.com/eury.go/','https://www.facebook.com/profile.php?id=61567452016442']
], JSON_UNESCAPED_SLASHES);

require_once __DIR__ . '/../../includes/header.php';
?>

  <main id="main" class="blog-listing" data-alt-lang-url="/blog/">
    <div class="container">
      <div class="section__header" style="padding-top: var(--space-lg);">
        <span class="section__tag">Blog</span>
        <h1>Blog</h1>
        <p>Guides, news and success stories about Erasmus+ and European educational mobility.</p>
      </div>

      <div class="blog-filters" style="text-align:center; margin-bottom: var(--space-lg);">
        <a href="/en/blog/" class="btn btn--sm <?= !$categoria_filtro ? 'btn--primary' : 'btn--outline' ?>">All</a>
        <?php foreach (['centros','agencias','erasmus','casos-exito'] as $cat): ?>
        <a href="/en/blog/?category=<?= $cat ?>" class="btn btn--sm <?= $categoria_filtro === $cat ? 'btn--primary' : 'btn--outline' ?>"><?= nombre_categoria($cat, 'en') ?></a>
        <?php endforeach; ?>
      </div>

      <div class="blog-grid" style="padding-bottom: var(--space-2xl);">
<?php if (empty($articulos)): ?>
        <p style="text-align:center; grid-column: 1/-1; padding: var(--space-xl) 0;">No articles in this category.</p>
<?php endif; ?>
<?php foreach ($articulos as $i => $art): ?>
        <article class="blog-card reveal<?= $i > 0 ? ' reveal--delay-' . min($i, 3) : '' ?>">
          <div class="blog-card__image">
<?php if ($art['imagen_portada']): ?>
            <img src="<?= htmlspecialchars($art['imagen_portada']) ?>"
                 alt="<?= htmlspecialchars($art['alt_imagen'] ?? $art['titulo']) ?>"
                 loading="lazy"
                 onerror="this.style.display='none';this.parentElement.innerHTML='<svg viewBox=\'0 0 640 360\' fill=\'none\'><rect width=\'640\' height=\'360\' fill=\'%23EFF6FF\'/><circle cx=\'320\' cy=\'160\' r=\'60\' fill=\'%2338BDF8\' opacity=\'0.15\'/><rect x=\'260\' y=\'220\' width=\'120\' height=\'8\' rx=\'4\' fill=\'%230284C7\' opacity=\'0.1\'/></svg>'+this.parentElement.innerHTML;">
<?php else: ?>
            <svg viewBox="0 0 640 360" fill="none"><rect width="640" height="360" fill="#EFF6FF"/><circle cx="320" cy="160" r="60" fill="#38BDF8" opacity="0.15"/><rect x="260" y="220" width="120" height="8" rx="4" fill="#0284C7" opacity="0.1"/></svg>
<?php endif; ?>
            <span class="blog-card__category <?= clase_categoria($art['categoria']) ?>"><?= nombre_categoria($art['categoria'], 'en') ?></span>
          </div>
          <div class="blog-card__body">
            <div class="blog-card__meta">
              <span><svg viewBox="0 0 24 24"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zM5 8V6h14v2H5z"/></svg> <?= formato_fecha($art['fecha_publicacion'], 'en') ?></span>
              <span><svg viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg> <?= $art['tiempo_lectura'] ?> min read</span>
            </div>
            <h3><a href="/en/blog/<?= htmlspecialchars($art['slug']) ?>/"><?= htmlspecialchars($art['titulo']) ?></a></h3>
            <p class="blog-card__excerpt"><?= htmlspecialchars($art['extracto']) ?></p>
            <a href="/en/blog/<?= htmlspecialchars($art['slug']) ?>/" class="blog-card__link">
              Read more <svg viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
            </a>
          </div>
        </article>
<?php endforeach; ?>
      </div>

<?php if ($total_paginas > 1): ?>
      <div class="blog-pagination" style="text-align:center; padding-bottom: var(--space-2xl);">
        <?php if ($pagina > 1): ?>
        <a href="?page=<?= $pagina - 1 ?><?= $categoria_filtro ? '&category=' . $categoria_filtro : '' ?>" class="btn btn--outline btn--sm">&larr; Previous</a>
        <?php endif; ?>
        <span style="margin: 0 var(--space-md); color: var(--color-text-light);">Page <?= $pagina ?> of <?= $total_paginas ?></span>
        <?php if ($pagina < $total_paginas): ?>
        <a href="?page=<?= $pagina + 1 ?><?= $categoria_filtro ? '&category=' . $categoria_filtro : '' ?>" class="btn btn--outline btn--sm">Next &rarr;</a>
        <?php endif; ?>
      </div>
<?php endif; ?>

      <div class="newsletter reveal">
        <h3>Get Erasmus+ updates in your inbox</h3>
        <p>Calls, guides and European educational mobility trends — once a month.</p>
        <form id="form-newsletter-blog-en" class="newsletter__form" method="POST" action="/newsletter/suscribir.php">
          <?= campo_csrf() ?>
          <input type="hidden" name="idioma" value="en">
          <input type="hidden" name="form_time" id="newsletter-blog-en-form-time" value="">
          <input type="email" name="email" placeholder="you@email.com" required>
          <button type="submit" class="btn btn--primary btn--sm">Subscribe</button>

          <!-- Honeypot anti-bot (oculto a humanos) -->
          <div style="position:absolute;left:-9999px;top:-9999px" aria-hidden="true" tabindex="-1">
            <input type="text" name="website" value="" tabindex="-1" autocomplete="off">
          </div>

<?php if (defined('TURNSTILE_SITE_KEY') && TURNSTILE_SITE_KEY && strpos(TURNSTILE_SITE_KEY, 'TU_') !== 0): ?>
          <div class="cf-turnstile" data-sitekey="<?= htmlspecialchars(TURNSTILE_SITE_KEY) ?>" data-callback="onTurnstileBlogEn" data-theme="light"></div>
          <input type="hidden" name="cf_turnstile_response" id="cf-turnstile-response-blog-en" value="">
<?php endif; ?>
        </form>

        <!-- Privacy checkbox y feedback: FUERA del <form> para que .newsletter__form (display:flex)
             no los meta en la fila horizontal. Quedan asociados al form vía atributo HTML5 `form=` —
             el checkbox sigue validándose con `required` y new FormData(form) lo captura. -->
        <div class="newsletter__consent">
          <div class="form__checkbox">
            <input type="checkbox" id="newsletter-privacy-blog-en" name="consentimiento_rgpd" value="1" form="form-newsletter-blog-en" required>
            <label for="newsletter-privacy-blog-en">I have read and accept the <a href="/en/privacy/" target="_blank">Privacy Policy</a>. I can unsubscribe at any time.</label>
          </div>
        </div>

        <div id="newsletter-feedback-blog-en" class="newsletter-feedback" hidden></div>
      </div>
<?php if (defined('TURNSTILE_SITE_KEY') && TURNSTILE_SITE_KEY && strpos(TURNSTILE_SITE_KEY, 'TU_') !== 0): ?>
      <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
      <script>
      function onTurnstileBlogEn(token) {
        var f = document.getElementById('cf-turnstile-response-blog-en');
        if (f) f.value = token;
      }
      </script>
<?php endif; ?>
      <script>
      (function(){
        var form = document.getElementById('form-newsletter-blog-en');
        if (!form) return;

        var timeField = document.getElementById('newsletter-blog-en-form-time');
        if (timeField) timeField.value = Math.floor(Date.now() / 1000);

        form.addEventListener('submit', function(e) {
          e.preventDefault();
          var fb = document.getElementById('newsletter-feedback-blog-en');
          var btn = form.querySelector('button[type="submit"]');
          var origText = btn.textContent;
          btn.disabled = true;
          btn.textContent = '...';
          fb.hidden = true;

          fetch(form.action, { method: 'POST', body: new FormData(form) })
            .then(function(r){ return r.json(); })
            .then(function(j){
              fb.hidden = false;
              fb.textContent = j.mensaje || (j.ok ? 'OK' : 'Error');
              fb.className = 'newsletter-feedback ' + (j.ok ? 'newsletter-feedback--ok' : 'newsletter-feedback--error');
              if (j.ok) form.reset();
            })
            .catch(function(){
              fb.hidden = false;
              fb.textContent = 'Connection error. Try again.';
              fb.className = 'newsletter-feedback newsletter-feedback--error';
            })
            .finally(function(){
              btn.disabled = false;
              btn.textContent = origText;
            });
        });
      })();
      </script>
    </div>
  </main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
