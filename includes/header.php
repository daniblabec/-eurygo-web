<?php
/**
 * Header compartido del frontend público.
 * Variables esperadas: $idioma, $titulo_pagina, $meta_description, $url_canonica, $es_articulo (bool)
 */
header_remove('X-Powered-By');
header('X-Frame-Options: SAMEORIGIN');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

$idioma         = $idioma ?? 'es';
$titulo_pagina  = $titulo_pagina ?? 'EuryGo — Erasmus+';
$meta_description = $meta_description ?? '';
$url_canonica   = $url_canonica ?? SITE_URL;
$es_articulo    = $es_articulo ?? false;
$og_type        = $es_articulo ? 'article' : 'website';
$base_url       = $idioma === 'en' ? '/en' : '';
$skip_text      = $idioma === 'en' ? 'Skip to main content' : 'Ir al contenido principal';
$nav_label      = $idioma === 'en' ? 'Main navigation' : 'Navegación principal';
?>
<!DOCTYPE html>
<html lang="<?= $idioma ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($titulo_pagina) ?></title>
<?php if ($meta_description): ?>
  <meta name="description" content="<?= htmlspecialchars($meta_description) ?>">
<?php endif; ?>
  <meta property="og:title" content="<?= htmlspecialchars($titulo_pagina) ?>">
<?php if ($meta_description): ?>
  <meta property="og:description" content="<?= htmlspecialchars($meta_description) ?>">
<?php endif; ?>
  <meta property="og:type" content="<?= $og_type ?>">
  <meta property="og:url" content="<?= htmlspecialchars($url_canonica) ?>">
  <meta name="twitter:card" content="summary_large_image">
<?php if ($idioma === 'es'): ?>
  <link rel="alternate" hreflang="es" href="<?= htmlspecialchars($url_canonica) ?>">
  <link rel="alternate" hreflang="en" href="<?= str_replace('www.eurygo.com/blog/', 'www.eurygo.com/en/blog/', $url_canonica) ?>">
  <link rel="alternate" hreflang="x-default" href="<?= htmlspecialchars($url_canonica) ?>">
<?php else: ?>
  <link rel="alternate" hreflang="en" href="<?= htmlspecialchars($url_canonica) ?>">
  <link rel="alternate" hreflang="es" href="<?= str_replace('www.eurygo.com/en/blog/', 'www.eurygo.com/blog/', $url_canonica) ?>">
  <link rel="alternate" hreflang="x-default" href="<?= str_replace('www.eurygo.com/en/blog/', 'www.eurygo.com/blog/', $url_canonica) ?>">
<?php endif; ?>
  <link rel="canonical" href="<?= htmlspecialchars($url_canonica) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/main.css">
<?php if (defined('GA4_ID') && GA4_ID !== 'G-XXXXXXXX'): ?>
  <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('consent','default',{'analytics_storage':'denied','ad_storage':'denied','ad_user_data':'denied','ad_personalization':'denied','wait_for_update':500});</script>
  <script async src="https://www.googletagmanager.com/gtag/js?id=<?= GA4_ID ?>"></script>
  <script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','<?= GA4_ID ?>',{'anonymize_ip':true});</script>
<?php endif; ?>
<?php if (!empty($schema_json)): ?>
  <script type="application/ld+json"><?= $schema_json ?></script>
<?php endif; ?>
</head>
<body>

  <a href="#main" class="skip-link"><?= $skip_text ?></a>

  <nav class="nav" role="navigation" aria-label="<?= $nav_label ?>">
    <div class="nav__inner">
      <a href="<?= $base_url ?>/" class="nav__logo" aria-label="EuryGo">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 360 100" width="160" height="44">
          <defs>
            <linearGradient id="navPinGrad" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#38BDF8"/><stop offset="50%" stop-color="#0284C7"/><stop offset="100%" stop-color="#0C4A6E"/></linearGradient>
            <linearGradient id="navPlaneLight" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#FDE68A"/><stop offset="100%" stop-color="#FBBF24"/></linearGradient>
            <linearGradient id="navPlaneMed" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#FBBF24"/><stop offset="100%" stop-color="#F59E0B"/></linearGradient>
            <linearGradient id="navPlaneDark" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="#F59E0B"/><stop offset="100%" stop-color="#D97706"/></linearGradient>
            <linearGradient id="navGoldText" x1="0%" y1="0%" x2="100%" y2="0%"><stop offset="0%" stop-color="#D97706"/><stop offset="100%" stop-color="#F59E0B"/></linearGradient>
          </defs>
          <g transform="translate(15,5)"><ellipse cx="40" cy="88" rx="14" ry="3" fill="#000" opacity="0.1"/><polygon points="0,-5 1.5,-1.5 5,-1.5 2,1 3,5 0,2.5 -3,5 -2,1 -5,-1.5 -1.5,-1.5" transform="translate(6,40) scale(0.5)" fill="#FBBF24"/><polygon points="0,-5 1.5,-1.5 5,-1.5 2,1 3,5 0,2.5 -3,5 -2,1 -5,-1.5 -1.5,-1.5" transform="translate(10,22) scale(0.7)" fill="#F59E0B"/><polygon points="0,-5 1.5,-1.5 5,-1.5 2,1 3,5 0,2.5 -3,5 -2,1 -5,-1.5 -1.5,-1.5" transform="translate(24,8) scale(0.9)" fill="#D97706"/><path d="M22,60 Q30,52 38,44" fill="none" stroke="#FBBF24" stroke-width="2.5" stroke-linecap="round" stroke-dasharray="4,5" opacity="0.8"/><path d="M40,85 C20,65 12,48 12,38 A28,28 0 0,1 58,16" fill="none" stroke="url(#navPinGrad)" stroke-width="7" stroke-linecap="round"/><path d="M40,85 C55,70 65,55 67,45" fill="none" stroke="url(#navPinGrad)" stroke-width="7" stroke-linecap="round"/><g transform="translate(2,4)" fill="#0C4A6E" opacity="0.25"><polygon points="68,12 28,36 44,40"/><polygon points="68,12 44,40 54,44"/><polygon points="44,40 48,56 54,44"/></g><g><polygon points="68,12 28,36 44,40" fill="url(#navPlaneLight)"/><polygon points="68,12 44,40 54,44" fill="url(#navPlaneMed)"/><polygon points="44,40 48,56 54,44" fill="url(#navPlaneDark)"/></g></g>
          <text x="105" y="58" font-family="'DM Sans',sans-serif" font-weight="800" font-size="46" fill="#0C4A6E" letter-spacing="-1">Eury<tspan fill="url(#navGoldText)">Go</tspan></text>
          <text x="108" y="78" font-family="'DM Sans',sans-serif" font-weight="600" font-size="11.5" fill="#64748B" letter-spacing="3.5">MOBILITY EXPERIENCE</text>
        </svg>
      </a>
      <div class="nav__links">
<?php if ($idioma === 'es'): ?>
        <a href="/#about" class="nav__link" data-i18n="nav_about">¿Qué es EuryGo?</a>
        <a href="/#schools" class="nav__link" data-i18n="nav_schools">Centros Escolares</a>
        <a href="/#agencies" class="nav__link" data-i18n="nav_agencies">Agencias</a>
        <a href="/cursos/" class="nav__link" data-i18n="nav_courses">Cursos de Formación</a>
        <a href="/blog/" class="nav__link" data-i18n="nav_blog">Blog</a>
        <a href="/#contact" class="nav__link" data-i18n="nav_contact">Contacto</a>
<?php else: ?>
        <a href="/en/#about" class="nav__link" data-i18n="nav_about">What is EuryGo?</a>
        <a href="/en/#schools" class="nav__link" data-i18n="nav_schools">Schools</a>
        <a href="/en/#agencies" class="nav__link" data-i18n="nav_agencies">Agencies</a>
        <a href="/en/cursos/" class="nav__link" data-i18n="nav_courses">Training Courses</a>
        <a href="/en/blog/" class="nav__link" data-i18n="nav_blog">Blog</a>
        <a href="/en/#contact" class="nav__link" data-i18n="nav_contact">Contact</a>
<?php endif; ?>
      </div>
      <div class="nav__right">
        <div class="nav__social">
          <a href="https://www.linkedin.com/in/eurygo" target="_blank" rel="noopener noreferrer" class="social--linkedin" data-track="social" data-social="linkedin" aria-label="LinkedIn">
            <svg viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
          </a>
          <a href="https://www.instagram.com/eury.go/" target="_blank" rel="noopener noreferrer" class="social--instagram" data-track="social" data-social="instagram" aria-label="Instagram">
            <svg viewBox="0 0 24 24"><path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678a6.162 6.162 0 100 12.324 6.162 6.162 0 100-12.324zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405a1.441 1.441 0 11-2.882 0 1.441 1.441 0 012.882 0z"/></svg>
          </a>
          <a href="https://www.facebook.com/profile.php?id=61567452016442" target="_blank" rel="noopener noreferrer" class="social--facebook" data-track="social" data-social="facebook" aria-label="Facebook">
            <svg viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
          </a>
        </div>
        <div class="lang-switch">
          <button class="lang-switch__btn<?= $idioma === 'es' ? ' lang-switch__btn--active' : '' ?>" data-lang="es">ES</button>
          <button class="lang-switch__btn<?= $idioma === 'en' ? ' lang-switch__btn--active' : '' ?>" data-lang="en">EN</button>
        </div>
        <button class="nav__toggle" aria-label="<?= $idioma === 'en' ? 'Menu' : 'Menú' ?>"><span></span><span></span><span></span></button>
      </div>
    </div>
  </nav>
