<?php
/**
 * Blog — Single article (EN)
 * Receives ?slug=xxx via .htaccess
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/funciones.php';
require_once __DIR__ . '/../../includes/tracker.php';

iniciar_sesion_segura();
$idioma = 'en';
$slug = $_GET['slug'] ?? '';
$preview = $_GET['preview'] ?? null;

if (empty($slug)) {
    header('Location: /en/blog/');
    exit;
}

$db = get_db();

$stmt = $db->prepare("SELECT * FROM articulos WHERE slug = :slug AND idioma = :idioma LIMIT 1");
$stmt->execute([':slug' => $slug, ':idioma' => $idioma]);
$art = $stmt->fetch();

// Si no se encuentra en este idioma, buscar en el otro y redirigir
if (!$art) {
    $stmt_otro = $db->prepare("SELECT slug, idioma FROM articulos WHERE slug = :slug AND publicado = 1 LIMIT 1");
    $stmt_otro->execute([':slug' => $slug]);
    $art_otro = $stmt_otro->fetch();
    if ($art_otro) {
        $redirect = $art_otro['idioma'] === 'en' ? '/en/blog/' : '/blog/';
        header('Location: ' . $redirect . $art_otro['slug'] . '/', true, 301);
        exit;
    }
}

// Track visit
if ($art) { registrar_visita($db, (int)$art['id']); }

if (!$art || ($art['publicado'] != 1 && $preview !== '1')) {
    http_response_code(404);
    $titulo_pagina = 'Article not found | EuryGo';
    $meta_description = '';
    $url_canonica = SITE_URL . '/en/blog/';
    require_once __DIR__ . '/../../includes/header.php';
    echo '<main id="main"><div class="container" style="padding: var(--space-2xl) 0; text-align:center;">';
    echo '<h1>Article not found</h1><p>The article you are looking for does not exist or is not published.</p>';
    echo '<a href="/en/blog/" class="btn btn--primary">Back to blog</a></div></main>';
    require_once __DIR__ . '/../../includes/footer.php';
    exit;
}

// Buscar traducción vinculada
$slug_traduccion = '';
if (!empty($art['traduccion_id'])) {
    $stmt_trad = $db->prepare("SELECT slug FROM articulos WHERE id = :id AND publicado = 1 LIMIT 1");
    $stmt_trad->execute([':id' => $art['traduccion_id']]);
    $trad = $stmt_trad->fetch();
    if ($trad) { $slug_traduccion = $trad['slug']; }
}
$alt_lang_url = $slug_traduccion ? '/blog/' . $slug_traduccion . '/' : '/blog/';

// Related articles
$stmt_rel = $db->prepare("SELECT slug, titulo, extracto, categoria, fecha_publicacion, tiempo_lectura FROM articulos WHERE publicado = 1 AND idioma = :idioma AND categoria = :cat AND id != :id ORDER BY fecha_publicacion DESC LIMIT 3");
$stmt_rel->execute([':idioma' => $idioma, ':cat' => $art['categoria'], ':id' => $art['id']]);
$relacionados = $stmt_rel->fetchAll();

// SEO
$titulo_pagina = $art['meta_title'] ?: $art['titulo'] . ' | EuryGo';
$meta_description = $art['meta_description'] ?: $art['extracto'];
$url_canonica = SITE_URL . '/en/blog/' . $art['slug'] . '/';
$es_articulo = true;

$schema_json = json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'BlogPosting',
    'headline' => $art['titulo'],
    'author' => ['@type' => 'Organization', 'name' => 'EuryGo'],
    'publisher' => ['@type' => 'Organization', 'name' => 'EuryGo', 'url' => SITE_URL],
    'datePublished' => date('Y-m-d', strtotime($art['fecha_publicacion'])),
    'dateModified' => date('Y-m-d', strtotime($art['fecha_modificacion'] ?? $art['fecha_publicacion'])),
    'url' => $url_canonica,
    'description' => $art['extracto'],
    'image' => $art['imagen_portada'] ? SITE_URL . $art['imagen_portada'] : null,
    'wordCount' => str_word_count(strip_tags($art['contenido'])),
    'timeRequired' => 'PT' . $art['tiempo_lectura'] . 'M',
], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

$cta = cta_por_categoria($art['categoria'], 'en');
$url_compartir = urlencode($url_canonica);
$titulo_compartir = urlencode($art['titulo']);

require_once __DIR__ . '/../../includes/header.php';
?>

  <main id="main" data-alt-lang-url="<?= htmlspecialchars($alt_lang_url) ?>">
    <div class="container">
      <article class="article">
        <nav class="article__breadcrumb" aria-label="Breadcrumb">
          <a href="/en/">Home</a> <span class="separator">/</span>
          <a href="/en/blog/">Blog</a> <span class="separator">/</span>
          <a href="/en/blog/?category=<?= htmlspecialchars($art['categoria']) ?>"><?= nombre_categoria($art['categoria'], 'en') ?></a> <span class="separator">/</span>
          <span><?= htmlspecialchars(truncar($art['titulo'], 50)) ?></span>
        </nav>

        <header class="article__header">
          <span class="article__category-tag <?= clase_categoria($art['categoria']) ?>"><?= nombre_categoria($art['categoria'], 'en') ?></span>
          <h1><?= htmlspecialchars($art['titulo']) ?></h1>
<?php if ($art['subtitulo']): ?>
          <p class="article__subtitle"><?= htmlspecialchars($art['subtitulo']) ?></p>
<?php endif; ?>
          <div class="article__meta">
            <span><?= htmlspecialchars($art['autor']) ?></span>
            <span><?= formato_fecha($art['fecha_publicacion'], 'en') ?></span>
            <span><?= $art['tiempo_lectura'] ?> min read</span>
          </div>
        </header>

<?php
$imagen     = $art['imagen_portada'] ?? '';
$alt_img    = $art['alt_imagen'] ?? htmlspecialchars($art['titulo'] ?? '');
$imagen_src = !empty($imagen)
    ? htmlspecialchars($imagen)
    : '/assets/images/blog/blog-placeholder.svg';
?>
        <div class="article__hero-image">
          <img src="<?= $imagen_src ?>"
               alt="<?= htmlspecialchars($alt_img) ?>"
               loading="eager"
               onerror="this.src='/assets/images/blog/blog-placeholder.svg'">
        </div>

        <div class="article__content">
          <?= $art['contenido'] ?>
        </div>

<?php
$url_articulo    = urlencode('https://www.eurygo.com/en/blog/' . ($art['slug'] ?? '') . '/');
$titulo_articulo = urlencode($art['titulo'] ?? 'EuryGo Blog');
$whatsapp_texto  = urlencode('Check out this article from EuryGo: ' . ($art['titulo'] ?? ''));
?>
        <div class="share-buttons">
          <span class="share-label">Share:</span>
          <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= $url_articulo ?>"
             target="_blank" rel="noopener noreferrer" aria-label="Share on LinkedIn"
             class="share-btn share-btn--linkedin">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
          </a>
          <a href="https://www.facebook.com/sharer/sharer.php?u=<?= $url_articulo ?>"
             target="_blank" rel="noopener noreferrer" aria-label="Share on Facebook"
             class="share-btn share-btn--facebook">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
          </a>
          <a href="https://wa.me/?text=<?= $whatsapp_texto ?>%20<?= $url_articulo ?>"
             target="_blank" rel="noopener noreferrer" aria-label="Share on WhatsApp"
             class="share-btn share-btn--whatsapp">
            <svg viewBox="0 0 24 24" width="20" height="20" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
          </a>
        </div>

        <div class="article__cta">
          <h3><?= htmlspecialchars($cta['titulo']) ?></h3>
          <p><?= htmlspecialchars($cta['texto']) ?></p>
          <a href="/en/#contact" class="btn btn--gold">Contact now</a>
        </div>

        <!-- Newsletter -->
        <?php $idioma_actual = 'en'; include __DIR__ . '/../../includes/widget-newsletter.php'; ?>

<?php if (!empty($relacionados)): ?>
        <div class="article__related">
          <h3>Related articles</h3>
          <div class="blog-grid">
<?php foreach ($relacionados as $rel): ?>
            <article class="blog-card">
              <div class="blog-card__body">
                <h3><a href="/en/blog/<?= htmlspecialchars($rel['slug']) ?>/"><?= htmlspecialchars($rel['titulo']) ?></a></h3>
                <p class="blog-card__excerpt"><?= htmlspecialchars(truncar($rel['extracto'], 120)) ?></p>
                <a href="/en/blog/<?= htmlspecialchars($rel['slug']) ?>/" class="blog-card__link">Read more <svg viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"/></svg></a>
              </div>
            </article>
<?php endforeach; ?>
          </div>
        </div>
<?php endif; ?>
      </article>
    </div>
  </main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
