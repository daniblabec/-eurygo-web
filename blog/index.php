<?php
/**
 * Blog — Listado dinámico de artículos (ES)
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/tracker.php';

$idioma = 'es';
$por_pagina = 6;
$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$offset = ($pagina - 1) * $por_pagina;
$categoria_filtro = $_GET['categoria'] ?? null;

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
$titulo_pagina = 'Blog | EuryGo — Erasmus+, movilidad educativa y acreditaciones';
$meta_description = 'Guías, novedades y casos de éxito sobre Erasmus+ y movilidad educativa europea. Artículos para centros escolares y agencias de viaje educativo.';
$url_canonica = SITE_URL . '/blog/';
$schema_json = json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => 'EuryGo',
    'url' => SITE_URL,
    'sameAs' => ['https://www.linkedin.com/in/eurygo','https://www.instagram.com/eury.go/','https://www.facebook.com/profile.php?id=61567452016442']
], JSON_UNESCAPED_SLASHES);

require_once __DIR__ . '/../includes/header.php';
?>

  <main id="main" class="blog-listing" data-alt-lang-url="/en/blog/">
    <div class="container">
      <div class="section__header" style="padding-top: var(--space-lg);">
        <span class="section__tag">Blog</span>
        <h1>Blog</h1>
        <p>Guías, novedades y casos de éxito sobre Erasmus+ y movilidad educativa europea.</p>
      </div>

      <!-- Filtro por categoría -->
      <div class="blog-filters" style="text-align:center; margin-bottom: var(--space-lg);">
        <a href="/blog/" class="btn btn--sm <?= !$categoria_filtro ? 'btn--primary' : 'btn--outline' ?>">Todos</a>
        <?php foreach (['centros','agencias','erasmus','casos-exito'] as $cat): ?>
        <a href="/blog/?categoria=<?= $cat ?>" class="btn btn--sm <?= $categoria_filtro === $cat ? 'btn--primary' : 'btn--outline' ?>"><?= nombre_categoria($cat, 'es') ?></a>
        <?php endforeach; ?>
      </div>

      <div class="blog-grid" style="padding-bottom: var(--space-2xl);">
<?php if (empty($articulos)): ?>
        <p style="text-align:center; grid-column: 1/-1; padding: var(--space-xl) 0;">No hay artículos en esta categoría.</p>
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
            <svg viewBox="0 0 640 360" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect width="640" height="360" fill="#EFF6FF"/>
              <circle cx="320" cy="160" r="60" fill="#38BDF8" opacity="0.15"/>
              <rect x="260" y="220" width="120" height="8" rx="4" fill="#0284C7" opacity="0.1"/>
              <rect x="280" y="240" width="80" height="6" rx="3" fill="#0284C7" opacity="0.07"/>
            </svg>
<?php endif; ?>
            <span class="blog-card__category <?= clase_categoria($art['categoria']) ?>"><?= nombre_categoria($art['categoria'], 'es') ?></span>
          </div>
          <div class="blog-card__body">
            <div class="blog-card__meta">
              <span><svg viewBox="0 0 24 24"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V10h14v10zM5 8V6h14v2H5z"/></svg> <?= formato_fecha($art['fecha_publicacion'], 'es') ?></span>
              <span><svg viewBox="0 0 24 24"><path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"/></svg> <?= $art['tiempo_lectura'] ?> min de lectura</span>
            </div>
            <h3><a href="/blog/<?= htmlspecialchars($art['slug']) ?>/"><?= htmlspecialchars($art['titulo']) ?></a></h3>
            <p class="blog-card__excerpt"><?= htmlspecialchars($art['extracto']) ?></p>
            <a href="/blog/<?= htmlspecialchars($art['slug']) ?>/" class="blog-card__link">
              Leer más <svg viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg>
            </a>
          </div>
        </article>
<?php endforeach; ?>
      </div>

<?php if ($total_paginas > 1): ?>
      <!-- Paginación -->
      <div class="blog-pagination" style="text-align:center; padding-bottom: var(--space-2xl);">
        <?php if ($pagina > 1): ?>
        <a href="?pagina=<?= $pagina - 1 ?><?= $categoria_filtro ? '&categoria=' . $categoria_filtro : '' ?>" class="btn btn--outline btn--sm">&larr; Anterior</a>
        <?php endif; ?>
        <span style="margin: 0 var(--space-md); color: var(--color-text-light);">Página <?= $pagina ?> de <?= $total_paginas ?></span>
        <?php if ($pagina < $total_paginas): ?>
        <a href="?pagina=<?= $pagina + 1 ?><?= $categoria_filtro ? '&categoria=' . $categoria_filtro : '' ?>" class="btn btn--outline btn--sm">Siguiente &rarr;</a>
        <?php endif; ?>
      </div>
<?php endif; ?>

      <!-- Newsletter -->
      <div class="newsletter reveal">
        <h3>Recibe novedades sobre Erasmus+ en tu correo</h3>
        <p>Convocatorias, guías y tendencias de movilidad educativa europea, una vez al mes.</p>
        <form class="newsletter__form" data-track="newsletter">
          <input type="hidden" name="form_time" value="">
          <input type="email" placeholder="tu@email.com" required>
          <button type="submit" class="btn btn--primary btn--sm">Suscribirme</button>
          <!-- Honeypot anti-bot -->
          <div style="position:absolute;left:-9999px;top:-9999px" aria-hidden="true" tabindex="-1">
            <input type="text" name="website" value="" tabindex="-1" autocomplete="off">
          </div>
<?php if (defined('TURNSTILE_SITE_KEY') && TURNSTILE_SITE_KEY && strpos(TURNSTILE_SITE_KEY, 'TU_') !== 0): ?>
          <!-- Cloudflare Turnstile (CAPTCHA invisible) -->
          <div class="cf-turnstile" data-sitekey="<?= htmlspecialchars(TURNSTILE_SITE_KEY) ?>" data-callback="onNewsletterTurnstile"></div>
          <input type="hidden" name="cf_turnstile_response" value="">
<?php endif; ?>
        </form>
        <div class="newsletter__consent">
          <div class="form__checkbox">
            <input type="checkbox" id="newsletter-privacy-blog" name="newsletter-privacy" required>
            <label for="newsletter-privacy-blog">He leído y acepto la <a href="/privacidad/">Política de Privacidad</a>.</label>
          </div>
          <div class="form__checkbox">
            <input type="checkbox" id="newsletter-commercial-blog" name="newsletter-commercial">
            <label for="newsletter-commercial-blog">Acepto recibir comunicaciones comerciales y novedades de EuryGo. Puedo darme de baja en cualquier momento.</label>
          </div>
        </div>
      </div>
<?php if (defined('TURNSTILE_SITE_KEY') && TURNSTILE_SITE_KEY && strpos(TURNSTILE_SITE_KEY, 'TU_') !== 0): ?>
      <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
      <script>function onNewsletterTurnstile(token){var f=document.querySelector('[data-track="newsletter"] input[name="cf_turnstile_response"]');if(f)f.value=token;}</script>
<?php endif; ?>
    </div>
  </main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
