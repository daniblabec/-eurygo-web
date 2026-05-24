<?php
/**
 * Homepage (EN) — Loads dynamic images from the DB.
 */
require_once __DIR__ . '/../includes/auth.php';
iniciar_sesion_segura();
$csrf_token = generar_csrf();

$_home_imagenes = [];
try {
    require_once __DIR__ . '/../includes/db.php';
    $db = get_db();
    $stmt = $db->query("SELECT * FROM home_imagenes WHERE activa = 1 ORDER BY orden ASC");
    while ($row = $stmt->fetch()) {
        $_home_imagenes[$row['posicion']] = $row;
    }
} catch (Throwable $e) {
    // DB unavailable — SVG fallback illustrations will be used
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>EuryGo | Erasmus+ Partner for Schools and Travel Agencies</title>
  <meta name="description" content="EuryGo connects schools, mobility agencies and Erasmus+ EU funding. Accreditation management, B2B Jobshadowing and KA1 teacher training in Cadiz, Spain.">

  <!-- Open Graph -->
  <meta property="og:title" content="EuryGo | Erasmus+ Partner for Schools and Travel Agencies">
  <meta property="og:description" content="We connect schools with Europe. No red tape, full guarantees. End-to-end Erasmus+ support.">
  <meta property="og:type" content="website">
  <meta property="og:url" content="https://www.eurygo.com/en/">
  <meta property="og:image" content="https://www.eurygo.com/assets/images/og-image.jpg">
  <meta property="og:locale" content="en_GB">
  <meta property="og:locale:alternate" content="es_ES">

  <!-- Twitter -->
  <meta name="twitter:card" content="summary_large_image">
  <meta name="twitter:title" content="EuryGo | Erasmus+ Partner">
  <meta name="twitter:description" content="We connect schools with Europe. No red tape, full guarantees.">

  <!-- Hreflang -->
  <link rel="alternate" hreflang="es" href="https://www.eurygo.com/">
  <link rel="alternate" hreflang="en" href="https://www.eurygo.com/en/">
  <link rel="alternate" hreflang="x-default" href="https://www.eurygo.com/">

  <!-- Canonical -->
  <link rel="canonical" href="https://www.eurygo.com/en/">

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700;800&display=swap" rel="stylesheet">

  <!-- Styles -->
  <link rel="stylesheet" href="/assets/css/main.css">

  <!-- Weglot (uncomment when API key is ready)
  <script type="text/javascript" src="https://cdn.weglot.com/weglot.min.js"></script>
  <script>Weglot.initialize({ api_key: 'YOUR_WEGLOT_API_KEY' });</script>
  -->

  <!-- Google Consent Mode v2 (BEFORE any Google script) -->
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('consent', 'default', {
      'analytics_storage': 'denied',
      'ad_storage': 'denied',
      'ad_user_data': 'denied',
      'ad_personalization': 'denied',
      'wait_for_update': 500
    });
  </script>

<?php if (defined('GA4_ID') && GA4_ID !== 'G-XXXXXXXX'): ?>
  <!-- GA4 (respects Consent Mode) -->
  <script async src="https://www.googletagmanager.com/gtag/js?id=<?= GA4_ID ?>"></script>
  <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', '<?= GA4_ID ?>', { 'anonymize_ip': true });
  </script>
<?php endif; ?>

  <!-- Schema.org Organization -->
  <script type="application/ld+json">
  {
    "@context": "https://schema.org",
    "@type": "Organization",
    "name": "EuryGo",
    "alternateName": "EuryGo Mobility Experience",
    "url": "https://www.eurygo.com",
    "logo": "https://www.eurygo.com/assets/images/logo.svg",
    "description": "Erasmus+ intermediary connecting schools, mobility agencies and EU funding.",
    "address": {
      "@type": "PostalAddress",
      "addressLocality": "Jerez de la Frontera",
      "addressRegion": "Cadiz",
      "addressCountry": "ES"
    },
    "sameAs": [
      "https://www.linkedin.com/in/eurygo",
      "https://www.instagram.com/eury.go/",
      "https://www.facebook.com/profile.php?id=61567452016442"
    ]
  }
  </script>
</head>
<body>

  <!-- Skip to content (a11y) -->
  <a href="#main" class="skip-link">Skip to main content</a>

  <!-- ========== NAVIGATION ========== -->
  <nav class="nav" role="navigation" aria-label="Main navigation">
    <div class="nav__inner">
      <!-- Logo -->
      <a href="/en/" class="nav__logo" aria-label="EuryGo - Home">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 360 100" width="160" height="44">
          <defs>
            <linearGradient id="navPinGrad" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" stop-color="#38BDF8"/><stop offset="50%" stop-color="#0284C7"/><stop offset="100%" stop-color="#0C4A6E"/>
            </linearGradient>
            <linearGradient id="navPlaneLight" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" stop-color="#FDE68A"/><stop offset="100%" stop-color="#FBBF24"/>
            </linearGradient>
            <linearGradient id="navPlaneMed" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" stop-color="#FBBF24"/><stop offset="100%" stop-color="#F59E0B"/>
            </linearGradient>
            <linearGradient id="navPlaneDark" x1="0%" y1="0%" x2="100%" y2="100%">
              <stop offset="0%" stop-color="#F59E0B"/><stop offset="100%" stop-color="#D97706"/>
            </linearGradient>
            <linearGradient id="navGoldText" x1="0%" y1="0%" x2="100%" y2="0%">
              <stop offset="0%" stop-color="#D97706"/><stop offset="100%" stop-color="#F59E0B"/>
            </linearGradient>
          </defs>
          <g transform="translate(15, 5)">
            <ellipse cx="40" cy="88" rx="14" ry="3" fill="#000000" opacity="0.1"/>
            <polygon points="0,-5 1.5,-1.5 5,-1.5 2,1 3,5 0,2.5 -3,5 -2,1 -5,-1.5 -1.5,-1.5" transform="translate(6, 40) scale(0.5)" fill="#FBBF24"/>
            <polygon points="0,-5 1.5,-1.5 5,-1.5 2,1 3,5 0,2.5 -3,5 -2,1 -5,-1.5 -1.5,-1.5" transform="translate(10, 22) scale(0.7)" fill="#F59E0B"/>
            <polygon points="0,-5 1.5,-1.5 5,-1.5 2,1 3,5 0,2.5 -3,5 -2,1 -5,-1.5 -1.5,-1.5" transform="translate(24, 8) scale(0.9)" fill="#D97706"/>
            <path d="M 22,60 Q 30,52 38,44" fill="none" stroke="#FBBF24" stroke-width="2.5" stroke-linecap="round" stroke-dasharray="4,5" opacity="0.8"/>
            <path d="M 40,85 C 20,65 12,48 12,38 A 28,28 0 0,1 58,16" fill="none" stroke="url(#navPinGrad)" stroke-width="7" stroke-linecap="round"/>
            <path d="M 40,85 C 55,70 65,55 67,45" fill="none" stroke="url(#navPinGrad)" stroke-width="7" stroke-linecap="round"/>
            <g transform="translate(2, 4)" fill="#0C4A6E" opacity="0.25">
              <polygon points="68,12 28,36 44,40"/><polygon points="68,12 44,40 54,44"/><polygon points="44,40 48,56 54,44"/>
            </g>
            <g>
              <polygon points="68,12 28,36 44,40" fill="url(#navPlaneLight)"/>
              <polygon points="68,12 44,40 54,44" fill="url(#navPlaneMed)"/>
              <polygon points="44,40 48,56 54,44" fill="url(#navPlaneDark)"/>
            </g>
          </g>
          <text x="105" y="58" font-family="'DM Sans', sans-serif" font-weight="800" font-size="46" fill="#0C4A6E" letter-spacing="-1">Eury<tspan fill="url(#navGoldText)">Go</tspan></text>
          <text x="108" y="78" font-family="'DM Sans', sans-serif" font-weight="600" font-size="11.5" fill="#64748B" letter-spacing="3.5">MOBILITY EXPERIENCE</text>
        </svg>
      </a>

      <!-- Links -->
      <div class="nav__links">
        <a href="#about" class="nav__link" data-i18n="nav_about">What is EuryGo?</a>
        <a href="#schools" class="nav__link" data-i18n="nav_schools">Schools</a>
        <a href="#agencies" class="nav__link" data-i18n="nav_agencies">Agencies</a>
        <a href="/en/cursos/" class="nav__link" data-i18n="nav_courses">Training Courses</a>
        <a href="/en/blog/" class="nav__link" data-i18n="nav_blog">Blog</a>
        <a href="#contact" class="nav__link" data-i18n="nav_contact">Contact</a>
      </div>

      <!-- Right side -->
      <div class="nav__right">
        <!-- Social icons -->
        <div class="nav__social">
          <!-- TODO: Actualizar a linkedin.com/company/eurygo cuando se cree la página de empresa -->
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

        <!-- Language switch -->
        <div class="lang-switch">
          <button class="lang-switch__btn" data-lang="es">ES</button>
          <button class="lang-switch__btn lang-switch__btn--active" data-lang="en">EN</button>
        </div>

        <!-- Mobile toggle -->
        <button class="nav__toggle" aria-label="Menu" aria-expanded="false">
          <span></span><span></span><span></span>
        </button>
      </div>
    </div>
  </nav>

  <!-- ========== MAIN CONTENT ========== -->
  <main id="main">

    <!-- ===== HERO ===== -->
    <section class="hero" id="hero"<?php if (!empty($_home_imagenes['hero']['ruta_imagen'])): ?> style="background-image: linear-gradient(135deg, rgba(12,74,110,0.82) 0%, rgba(14,60,94,0.85) 50%, rgba(10,41,64,0.88) 100%), url('<?= htmlspecialchars($_home_imagenes['hero']['ruta_imagen']) ?>'); background-size: cover; background-position: center;"<?php endif; ?>>
      <canvas id="hero-canvas" class="hero__canvas" aria-hidden="true"></canvas>
      <div class="container">
        <div class="hero__content">
          <div class="hero__badge">
            <svg viewBox="0 0 24 24"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>
            <span data-i18n="hero_badge">Your Trusted Erasmus+ Partner</span>
          </div>
          <h1>
            <span data-i18n="hero_title_1">Bridging Schools and Europe.</span><br>
            <span class="text-gold" data-i18n="hero_title_2">Effortlessly.</span>
          </h1>
          <p class="hero__subtitle" data-i18n="hero_subtitle">EuryGo connects schools, mobility agencies and Erasmus+ European funding. We guide you at every step — from accreditation to logistics — so you can focus on what truly matters: education.</p>
          <div class="hero__ctas">
            <a href="#schools" class="btn btn--gold btn--lg" data-track="cta-centros" data-i18n="hero_cta_schools">I'm a School</a>
            <a href="#agencies" class="btn btn--outline-white btn--lg" data-track="cta-agencias" data-i18n="hero_cta_agencies">I'm an Agency</a>
            <a href="/cursos/" class="btn btn--eu btn--lg" data-track="cta-cursos" data-i18n="hero_cta_courses"><svg viewBox="0 0 24 24" width="18" height="18" fill="currentColor" style="vertical-align:-3px;margin-right:6px;"><path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.72L12 15l5-2.73v3.72z"/></svg>Training Courses</a>
          </div>
          <div class="hero__stats">
            <div>
              <span class="hero__stat-number" data-i18n="hero_stat_1_number">6+</span>
              <span class="hero__stat-label" data-i18n="hero_stat_1_label">Years of experience</span>
            </div>
            <div>
              <span class="hero__stat-number" data-i18n="hero_stat_2_number">&euro;26.2B</span>
              <span class="hero__stat-label" data-i18n="hero_stat_2_label">Erasmus+ budget 2021-2027</span>
            </div>
            <div>
              <span class="hero__stat-number" data-i18n="hero_stat_3_number">100%</span>
              <span class="hero__stat-label" data-i18n="hero_stat_3_label">End-to-end Erasmus+ support</span>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== ABOUT / WHAT IS EURYGO? ===== -->
    <section class="section" id="about">
      <div class="container">
        <div class="section__header reveal">
          <span class="section__tag" data-i18n="about_tag">Our mission</span>
          <h2 data-i18n="about_title">What is EuryGo?</h2>
          <p data-i18n="about_subtitle">The missing piece in the Erasmus+ school ecosystem.</p>
        </div>
        <div class="grid grid--2 reveal" style="margin-bottom: var(--space-xl); align-items: center;">
          <div>
            <p data-i18n="about_p1" style="font-size: 1.05rem;">EuryGo was born from over 6 years of real-world Erasmus+ experience: navigating the Beneficiary Module, preparing Ulises reports and supporting eTwinning projects from the inside.</p>
            <p data-i18n="about_p2" style="font-size: 1.05rem;">We act as intermediaries between schools seeking internationalisation and European mobility agencies looking for a reliable partner in southern Spain. Our base in Jerez de la Frontera places us in a premium Jobshadowing destination: exceptional climate, rich cultural heritage (wineries, flamenco, horses) and a network of committed schools.</p>
          </div>
          <div class="segment__illustration">
<?php if (!empty($_home_imagenes['about']['ruta_imagen'])): ?>
            <img src="<?= htmlspecialchars($_home_imagenes['about']['ruta_imagen']) ?>" alt="<?= htmlspecialchars($_home_imagenes['about']['alt_texto'] ?? 'EuryGo — Erasmus+ Intermediary') ?>" loading="lazy">
<?php else: ?>
            <svg viewBox="0 0 400 300" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect width="400" height="300" fill="none"/>
              <circle cx="200" cy="150" r="100" stroke="#38BDF8" stroke-width="1.5" stroke-dasharray="6 4" opacity="0.3"/>
              <circle cx="200" cy="150" r="60" stroke="#F59E0B" stroke-width="1" stroke-dasharray="4 4" opacity="0.2"/>
              <g fill="#FBBF24" opacity="0.6">
                <polygon points="200,60 203,68 212,68 205,73 208,82 200,76 192,82 195,73 188,68 197,68" transform="scale(0.8) translate(50,-10)"/>
                <polygon points="200,60 203,68 212,68 205,73 208,82 200,76 192,82 195,73 188,68 197,68" transform="scale(0.6) translate(180,40)"/>
                <polygon points="200,60 203,68 212,68 205,73 208,82 200,76 192,82 195,73 188,68 197,68" transform="scale(0.5) translate(80,300)"/>
              </g>
              <g transform="translate(140, 100)">
                <polygon points="120,10 20,60 50,66" fill="#FDE68A"/>
                <polygon points="120,10 50,66 70,74" fill="#FBBF24"/>
                <polygon points="50,66 58,100 70,74" fill="#F59E0B"/>
              </g>
              <circle cx="110" cy="130" r="4" fill="#38BDF8" opacity="0.5"/>
              <circle cx="290" cy="170" r="4" fill="#38BDF8" opacity="0.5"/>
              <circle cx="200" cy="250" r="4" fill="#38BDF8" opacity="0.5"/>
              <line x1="110" y1="130" x2="200" y2="150" stroke="#38BDF8" stroke-width="1" opacity="0.2"/>
              <line x1="290" y1="170" x2="200" y2="150" stroke="#38BDF8" stroke-width="1" opacity="0.2"/>
              <line x1="200" y1="250" x2="200" y2="150" stroke="#38BDF8" stroke-width="1" opacity="0.2"/>
            </svg>
<?php endif; ?>
          </div>
        </div>
        <div class="grid grid--3">
          <div class="card reveal reveal--delay-1">
            <div class="card__icon card__icon--blue">
              <svg viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            </div>
            <h3 data-i18n="about_card1_title">Advisory & Accreditation</h3>
            <p data-i18n="about_card1_text">Expert guidance on European platforms. Beneficiary Module, Ulises and <a href="https://www.sepie.es" target="_blank" rel="noopener noreferrer">SEPIE</a> reporting — error-free.</p>
          </div>
          <div class="card reveal reveal--delay-2">
            <div class="card__icon card__icon--gold">
              <svg viewBox="0 0 24 24"><path d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <h3 data-i18n="about_card2_title">B2B Jobshadowing</h3>
            <p data-i18n="about_card2_text">High-level partnerships in the province of Cádiz. Complete professional and cultural immersion: Jerez sherry bodegas, Royal Andalusian School of Equestrian Art and authentic flamenco.</p>
          </div>
          <div class="card reveal reveal--delay-3">
            <div class="card__icon card__icon--green">
              <svg viewBox="0 0 24 24"><path d="M12 14l9-5-9-5-9 5 9 5z"/><path d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>
            </div>
            <h3 data-i18n="about_card3_title">KA1 Teacher Training</h3>
            <p data-i18n="about_card3_text">Structured courses for European educators: Spanish education system, AI in teaching and active methodologies. Next editions: February and June 2026.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== FOR SCHOOLS ===== -->
    <section class="section section--alt" id="schools">
      <div class="container">
        <div class="segment reveal">
          <div class="segment__visual">
            <div class="segment__illustration">
<?php if (!empty($_home_imagenes['schools']['ruta_imagen'])): ?>
              <img src="<?= htmlspecialchars($_home_imagenes['schools']['ruta_imagen']) ?>" alt="<?= htmlspecialchars($_home_imagenes['schools']['alt_texto'] ?? 'Erasmus+ school') ?>" loading="lazy">
<?php else: ?>
              <svg viewBox="0 0 400 300" fill="none">
                <!-- School building illustration -->
                <rect x="120" y="120" width="160" height="120" rx="4" fill="#E2E8F0"/>
                <rect x="140" y="100" width="120" height="30" rx="2" fill="#0284C7" opacity="0.2"/>
                <rect x="180" y="80" width="40" height="30" rx="2" fill="#0284C7" opacity="0.3"/>
                <!-- Windows -->
                <rect x="145" y="145" width="30" height="25" rx="2" fill="#38BDF8" opacity="0.3"/>
                <rect x="185" y="145" width="30" height="25" rx="2" fill="#38BDF8" opacity="0.3"/>
                <rect x="225" y="145" width="30" height="25" rx="2" fill="#38BDF8" opacity="0.3"/>
                <rect x="145" y="185" width="30" height="25" rx="2" fill="#38BDF8" opacity="0.3"/>
                <rect x="225" y="185" width="30" height="25" rx="2" fill="#38BDF8" opacity="0.3"/>
                <!-- Door -->
                <rect x="185" y="195" width="30" height="45" rx="2" fill="#0284C7" opacity="0.4"/>
                <!-- Flag -->
                <line x1="200" y1="80" x2="200" y2="55" stroke="#64748B" stroke-width="2"/>
                <rect x="200" y="55" width="25" height="15" rx="1" fill="#003399"/>
                <!-- Stars -->
                <circle cx="207" cy="62" r="1.5" fill="#FBBF24"/>
                <circle cx="213" cy="58" r="1.5" fill="#FBBF24"/>
                <circle cx="219" cy="62" r="1.5" fill="#FBBF24"/>
                <!-- Checkmarks floating -->
                <circle cx="90" cy="120" r="16" fill="#38BDF8" opacity="0.15"/>
                <path d="M84 120l4 4 8-8" stroke="#0284C7" stroke-width="2" fill="none" stroke-linecap="round"/>
                <circle cx="310" cy="150" r="16" fill="#F59E0B" opacity="0.15"/>
                <path d="M304 150l4 4 8-8" stroke="#D97706" stroke-width="2" fill="none" stroke-linecap="round"/>
              </svg>
<?php endif; ?>
            </div>
          </div>
          <div class="segment__content">
            <span class="section__tag" data-i18n="schools_tag">For schools</span>
            <h2 data-i18n="schools_title">Your Erasmus+ project, supported end to end</h2>
            <p data-i18n="schools_subtitle">Whether your school already holds accreditation or you want to take the first step towards internationalisation, EuryGo is with you every step of the way.</p>
            <div class="segment__features">
              <div class="segment__feature">
                <span class="segment__feature-icon"><svg viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg></span>
                <span data-i18n="schools_feature1">Expert guidance on Erasmus+ accreditation (KA121/KA122)</span>
              </div>
              <div class="segment__feature">
                <span class="segment__feature-icon"><svg viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg></span>
                <span data-i18n="schools_feature2">Comprehensive Beneficiary Module and Ulises assistance</span>
              </div>
              <div class="segment__feature">
                <span class="segment__feature-icon"><svg viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg></span>
                <span data-i18n="schools_feature3">SEPIE report preparation and review</span>
              </div>
              <div class="segment__feature">
                <span class="segment__feature-icon"><svg viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg></span>
                <span data-i18n="schools_feature4">Student and staff mobility coordination</span>
              </div>
              <div class="segment__feature">
                <span class="segment__feature-icon"><svg viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg></span>
                <span data-i18n="schools_feature5">Audit-ready: flawless documentation</span>
              </div>
              <div class="segment__feature">
                <span class="segment__feature-icon"><svg viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg></span>
                <span data-i18n="schools_feature6">Automated forms and data collection</span>
              </div>
            </div>
            <a href="#contact" class="btn btn--primary" data-i18n="schools_cta">Request information</a>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== FOR AGENCIES ===== -->
    <section class="section" id="agencies">
      <div class="container">
        <div class="segment segment--reverse reveal">
          <div class="segment__visual">
            <div class="segment__illustration">
<?php if (!empty($_home_imagenes['agencies']['ruta_imagen'])): ?>
              <img src="<?= htmlspecialchars($_home_imagenes['agencies']['ruta_imagen']) ?>" alt="<?= htmlspecialchars($_home_imagenes['agencies']['alt_texto'] ?? 'Erasmus+ mobility agency') ?>" loading="lazy">
<?php else: ?>
              <svg viewBox="0 0 400 300" fill="none">
                <!-- Globe / network illustration -->
                <circle cx="200" cy="150" r="90" stroke="#F59E0B" stroke-width="1.5" opacity="0.2"/>
                <ellipse cx="200" cy="150" rx="90" ry="40" stroke="#F59E0B" stroke-width="1" opacity="0.15"/>
                <ellipse cx="200" cy="150" rx="40" ry="90" stroke="#F59E0B" stroke-width="1" opacity="0.15"/>
                <!-- Location pins -->
                <g transform="translate(160,100)">
                  <circle cx="0" cy="0" r="8" fill="#0284C7" opacity="0.2"/>
                  <circle cx="0" cy="0" r="4" fill="#0284C7"/>
                </g>
                <g transform="translate(250,130)">
                  <circle cx="0" cy="0" r="8" fill="#D97706" opacity="0.2"/>
                  <circle cx="0" cy="0" r="4" fill="#D97706"/>
                </g>
                <g transform="translate(180,200)">
                  <circle cx="0" cy="0" r="8" fill="#38BDF8" opacity="0.2"/>
                  <circle cx="0" cy="0" r="4" fill="#38BDF8"/>
                </g>
                <g transform="translate(140,160)">
                  <circle cx="0" cy="0" r="6" fill="#FBBF24" opacity="0.2"/>
                  <circle cx="0" cy="0" r="3" fill="#FBBF24"/>
                </g>
                <!-- Connections -->
                <line x1="160" y1="100" x2="250" y2="130" stroke="#38BDF8" stroke-width="1" opacity="0.3" stroke-dasharray="4 3"/>
                <line x1="250" y1="130" x2="180" y2="200" stroke="#F59E0B" stroke-width="1" opacity="0.3" stroke-dasharray="4 3"/>
                <line x1="180" y1="200" x2="140" y2="160" stroke="#38BDF8" stroke-width="1" opacity="0.3" stroke-dasharray="4 3"/>
                <line x1="140" y1="160" x2="160" y2="100" stroke="#F59E0B" stroke-width="1" opacity="0.3" stroke-dasharray="4 3"/>
                <!-- Plane -->
                <g transform="translate(230, 80) rotate(20)">
                  <polygon points="30,0 0,15 8,17" fill="#FDE68A"/>
                  <polygon points="30,0 8,17 14,20" fill="#FBBF24"/>
                  <polygon points="8,17 10,30 14,20" fill="#F59E0B"/>
                </g>
              </svg>
<?php endif; ?>
            </div>
          </div>
          <div class="segment__content">
            <span class="section__tag" data-i18n="agencies_tag">For mobility agencies</span>
            <h2 data-i18n="agencies_title">The partner that turns schools into real bookings</h2>
            <p data-i18n="agencies_subtitle">If you are a European mobility agency and need a reliable partner in southern Spain, EuryGo is your gateway to Andalusia.</p>
            <div class="segment__features">
              <div class="segment__feature">
                <span class="segment__feature-icon"><svg viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg></span>
                <span data-i18n="agencies_feature1">Own network of host schools in Jerez, Cadiz and Sanlucar</span>
              </div>
              <div class="segment__feature">
                <span class="segment__feature-icon"><svg viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg></span>
                <span data-i18n="agencies_feature2">Turnkey Jobshadowing packages ready to sell</span>
              </div>
              <div class="segment__feature">
                <span class="segment__feature-icon"><svg viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg></span>
                <span data-i18n="agencies_feature3">High-level professional + cultural immersion (wineries, horses, flamenco)</span>
              </div>
              <div class="segment__feature">
                <span class="segment__feature-icon"><svg viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg></span>
                <span data-i18n="agencies_feature4">Flawless logistics: accommodation, transport, scheduling</span>
              </div>
              <div class="segment__feature">
                <span class="segment__feature-icon"><svg viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg></span>
                <span data-i18n="agencies_feature5">Specialised training on the Spanish education system</span>
              </div>
              <div class="segment__feature">
                <span class="segment__feature-icon"><svg viewBox="0 0 24 24"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round"/></svg></span>
                <span data-i18n="agencies_feature6">Stable collaboration and guaranteed volume</span>
              </div>
            </div>
            <a href="#contact" class="btn btn--gold" data-i18n="agencies_cta">Explore partnership</a>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== UPCOMING COURSES ===== -->
<?php
// Show 3 distinct courses. Dedupe by course (each course appears once with its
// most upcoming open future edition). If we still have fewer than 3, fall back
// to published courses without a future edition.
$_proximos = [];
try {
    $stmt_prox = $db->query("
        SELECT
            ce.fecha_inicio, ce.fecha_fin, ce.plazas_disponibles,
            c.id AS curso_id, c.titulo, c.slug, c.extracto,
            c.precio, c.duracion_dias, c.ubicacion, c.imagen
        FROM cursos_ediciones ce
        JOIN cursos c ON c.id = ce.curso_id
        WHERE ce.estado = 'abierta'
          AND ce.fecha_inicio >= CURDATE()
          AND c.estado = 'publicado'
          AND c.idioma = 'en'
        ORDER BY ce.fecha_inicio ASC
    ");
    $vistos = [];
    foreach ($stmt_prox->fetchAll() as $row) {
        $cid = (int)$row['curso_id'];
        if (isset($vistos[$cid])) continue;
        $vistos[$cid] = true;
        $_proximos[] = $row;
        if (count($_proximos) >= 3) break;
    }
    if (count($_proximos) < 3) {
        $ids_ya = array_keys($vistos);
        $placeholders = $ids_ya ? implode(',', array_fill(0, count($ids_ya), '?')) : 'NULL';
        $faltan = 3 - count($_proximos);
        $stmt_fb = $db->prepare("
            SELECT
                c.fecha_inicio, c.fecha_fin,
                GREATEST(COALESCE(c.plazas, 0) - COALESCE(c.inscritos, 0), 0) AS plazas_disponibles,
                c.id AS curso_id, c.titulo, c.slug, c.extracto,
                c.precio, c.duracion_dias, c.ubicacion, c.imagen
            FROM cursos c
            WHERE c.estado = 'publicado'
              AND c.idioma = 'en'
              AND c.id NOT IN ($placeholders)
            ORDER BY (c.fecha_inicio IS NULL), c.fecha_inicio ASC, c.id ASC
            LIMIT $faltan
        ");
        $stmt_fb->execute($ids_ya);
        foreach ($stmt_fb->fetchAll() as $row) {
            $_proximos[] = $row;
        }
    }
} catch (Throwable $e) {}
?>
<?php if (!empty($_proximos)): ?>
    <section class="section" id="courses">
      <div class="container">
        <div class="section__header reveal">
          <span class="section__tag">KA1 Training</span>
          <h2>Upcoming Training Courses</h2>
          <p>Structured 5-day courses for European educators in Jerez de la Frontera. Europass certificate included.</p>
        </div>
        <div class="grid grid--3 reveal">
<?php foreach ($_proximos as $prox): $_fi = !empty($prox['fecha_inicio']) ? strtotime($prox['fecha_inicio']) : null; ?>
          <a href="/en/cursos/<?= htmlspecialchars($prox['slug']) ?>/" class="upcoming-card">
            <div class="upcoming-card__date">
<?php if ($_fi): ?>
              <span class="upcoming-card__month"><?= strtoupper(date('M', $_fi)) ?></span>
              <span class="upcoming-card__day"><?= date('d', $_fi) ?></span>
              <span class="upcoming-card__year"><?= date('Y', $_fi) ?></span>
<?php else: ?>
              <span class="upcoming-card__month">SOON</span>
              <span class="upcoming-card__day">·</span>
              <span class="upcoming-card__year">2026</span>
<?php endif; ?>
            </div>
            <div class="upcoming-card__info">
              <h3 class="upcoming-card__title"><?= htmlspecialchars(mb_substr($prox['titulo'], 0, 60)) ?></h3>
              <div class="upcoming-card__meta">
<?php if ($_fi && !empty($prox['fecha_fin'])): ?>
                <?= date('d/m', $_fi) ?> — <?= date('d/m/Y', strtotime($prox['fecha_fin'])) ?> · <?= $prox['duracion_dias'] ?> days
<?php else: ?>
                Upcoming editions · <?= $prox['duracion_dias'] ?> days
<?php endif; ?>
              </div>
              <div class="upcoming-card__meta"><?= htmlspecialchars($prox['ubicacion']) ?></div>
              <div class="upcoming-card__footer">
                <span class="upcoming-card__price"><?= number_format($prox['precio'], 0, ',', '.') ?> €</span>
                <span class="upcoming-card__spots"><?= (int)$prox['plazas_disponibles'] ?> spots</span>
              </div>
            </div>
          </a>
<?php endforeach; ?>
        </div>
        <div style="text-align:center; margin-top: var(--space-lg);">
          <a href="/en/cursos/" class="btn btn--eu">View all courses</a>
        </div>
      </div>
    </section>
<?php endif; ?>

    <!-- ===== HOW IT WORKS ===== -->
    <section class="section section--alt" id="how">
      <div class="container">
        <div class="section__header reveal">
          <span class="section__tag" data-i18n="how_tag">Process</span>
          <h2 data-i18n="how_title">How EuryGo works</h2>
          <p data-i18n="how_subtitle">An ecosystem where everyone wins: schools, agencies and the European educational community.</p>
        </div>

        <!-- Flow diagram -->
        <div class="flow-diagram reveal">
          <div class="flow-node">
            <div class="flow-node__icon flow-node__icon--school">
              <svg viewBox="0 0 24 24"><path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.72L12 15l5-2.73v3.72z"/></svg>
            </div>
            <span class="flow-node__label" data-i18n="how_node_school">School</span>
            <span class="flow-node__sublabel" data-i18n="how_node_school_sub">Accreditation & paperwork</span>
          </div>
          <div class="flow-arrow" aria-hidden="true">
            <svg viewBox="0 0 40 24"><path d="M2 12h32m-8-6l8 6-8 6" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </div>
          <div class="flow-node">
            <div class="flow-node__icon flow-node__icon--eurygo">
              <svg viewBox="0 0 24 24" fill="white" width="40" height="40"><polygon points="12,2 15.09,8.26 22,9.27 17,14.14 18.18,21.02 12,17.77 5.82,21.02 7,14.14 2,9.27 8.91,8.26"/></svg>
            </div>
            <span class="flow-node__label" data-i18n="how_node_eurygo">EuryGo</span>
            <span class="flow-node__sublabel" data-i18n="how_node_eurygo_sub">End-to-end intermediation</span>
          </div>
          <div class="flow-arrow" aria-hidden="true">
            <svg viewBox="0 0 40 24"><path d="M2 12h32m-8-6l8 6-8 6" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </div>
          <div class="flow-node">
            <div class="flow-node__icon flow-node__icon--agency">
              <svg viewBox="0 0 24 24"><path d="M20 6h-3V4c0-1.1-.9-2-2-2H9c-1.1 0-2 .9-2 2v2H4c-1.1 0-2 .9-2 2v11c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2zM9 4h6v2H9V4zm11 15H4v-2h16v2zm0-5H4V8h3v1h2V8h6v1h2V8h3v6z"/></svg>
            </div>
            <span class="flow-node__label" data-i18n="how_node_agency">Agency</span>
            <span class="flow-node__sublabel" data-i18n="how_node_agency_sub">Mobility & logistics</span>
          </div>
          <div class="flow-arrow" aria-hidden="true">
            <svg viewBox="0 0 40 24"><path d="M2 12h32m-8-6l8 6-8 6" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"/></svg>
          </div>
          <div class="flow-node">
            <div class="flow-node__icon flow-node__icon--eu">
              <svg viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-1 17.93c-3.95-.49-7-3.85-7-7.93 0-.62.08-1.21.21-1.79L9 15v1c0 1.1.9 2 2 2v1.93zm6.9-2.54c-.26-.81-1-1.39-1.9-1.39h-1v-3c0-.55-.45-1-1-1H8v-2h2c.55 0 1-.45 1-1V7h2c1.1 0 2-.9 2-2v-.41c2.93 1.19 5 4.06 5 7.41 0 2.08-.8 3.97-2.1 5.39z"/></svg>
            </div>
            <span class="flow-node__label" data-i18n="how_node_eu">EU Funding</span>
            <span class="flow-node__sublabel" data-i18n="how_node_eu_sub">Erasmus+ / ESF+</span>
          </div>
        </div>

        <!-- Steps -->
        <div class="grid grid--2" style="margin-top: var(--space-xl);">
          <div class="card reveal reveal--delay-1">
            <div class="card__icon card__icon--blue">
              <svg viewBox="0 0 24 24"><path d="M15.5 14h-.79l-.28-.27A6.471 6.471 0 0016 9.5 6.5 6.5 0 109.5 16c1.61 0 3.09-.59 4.23-1.57l.27.28v.79l5 4.99L20.49 19l-4.99-5zm-6 0C7.01 14 5 11.99 5 9.5S7.01 5 9.5 5 14 7.01 14 9.5 11.99 14 9.5 14z"/></svg>
            </div>
            <h3 data-i18n="how_step1_title">Initial assessment</h3>
            <p data-i18n="how_step1_text">We analyse your school or agency situation, identify Erasmus+ opportunities and design a personalised action plan.</p>
          </div>
          <div class="card reveal reveal--delay-2">
            <div class="card__icon card__icon--gold">
              <svg viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
            </div>
            <h3 data-i18n="how_step2_title">Paperwork assistance</h3>
            <p data-i18n="how_step2_text">We take care of all documentation: accreditations, Beneficiary Module forms, Ulises and SEPIE reports.</p>
          </div>
          <div class="card reveal reveal--delay-3">
            <div class="card__icon card__icon--green">
              <svg viewBox="0 0 24 24"><path d="M16 6l2.29 2.29-4.88 4.88-4-4L2 16.59 3.41 18l6-6 4 4 6.3-6.29L22 12V6z"/></svg>
            </div>
            <h3 data-i18n="how_step3_title">Mobility coordination</h3>
            <p data-i18n="how_step3_text">We connect schools with European agencies, organise Jobshadowing, KA1 training and exchanges with full guarantees.</p>
          </div>
          <div class="card reveal reveal--delay-4">
            <div class="card__icon card__icon--blue">
              <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/></svg>
            </div>
            <h3 data-i18n="how_step4_title">Follow-up and closure</h3>
            <p data-i18n="how_step4_text">We ensure all closing documentation is correct so you pass audits with no risk of fund clawback.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== WHY EURYGO ===== -->
    <section class="section" id="why">
      <div class="container">
        <div class="section__header reveal">
          <span class="section__tag" data-i18n="why_tag">Differentiators</span>
          <h2 data-i18n="why_title">Why choose EuryGo</h2>
          <p data-i18n="why_subtitle">We are not just another agency. We are the only partner that understands Erasmus+ from the inside.</p>
        </div>
        <div class="differentiators">
          <div class="diff-card reveal reveal--delay-1">
            <div class="diff-card__number">01</div>
            <h3 data-i18n="why_diff1_title">Real experience</h3>
            <p data-i18n="why_diff1_text">Over 6 years as a European platform coordinator. We know every form, deadline and SEPIE audit.</p>
          </div>
          <div class="diff-card reveal reveal--delay-2">
            <div class="diff-card__number">02</div>
            <h3 data-i18n="why_diff2_title">Hyper-localisation</h3>
            <p data-i18n="why_diff2_text">Jerez de la Frontera is a premium destination: 300 days of sunshine a year, centuries-old sherry bodegas, UNESCO-listed flamenco, the Royal Equestrian School and a network of schools committed to European education.</p>
          </div>
          <div class="diff-card reveal reveal--delay-3">
            <div class="diff-card__number">03</div>
            <h3 data-i18n="why_diff3_title">Technology & automation</h3>
            <p data-i18n="why_diff3_text">We use automation tools and artificial intelligence to let you track your project progress in real time.</p>
          </div>
          <div class="diff-card reveal reveal--delay-1">
            <div class="diff-card__number">04</div>
            <h3 data-i18n="why_diff4_title">Zero Bureaucracy</h3>
            <p data-i18n="why_diff4_text">Our tagline and our promise. We assist with all the paperwork so you can focus on education.</p>
          </div>
          <div class="diff-card reveal reveal--delay-2">
            <div class="diff-card__number">05</div>
            <h3 data-i18n="why_diff5_title">More demand than supply</h3>
            <p data-i18n="why_diff5_text">The Erasmus+ budget for 2021-2027 reaches €26.2 billion, 80% more than the previous period. Demand for school mobility grows with every call.</p>
          </div>
          <div class="diff-card reveal reveal--delay-3">
            <div class="diff-card__number">06</div>
            <h3 data-i18n="why_diff6_title">Closeness and trust</h3>
            <p data-i18n="why_diff6_text">Personalised service, direct contact with school leadership teams and continuous project follow-up.</p>
          </div>
        </div>
      </div>
    </section>

    <!-- ===== CONTACT ===== -->
    <section class="section section--alt" id="contact">
      <div class="container">
        <div class="section__header reveal">
          <span class="section__tag" data-i18n="contact_tag">Contact</span>
          <h2 data-i18n="contact_title">Let's talk about your Erasmus+ project</h2>
          <p data-i18n="contact_subtitle">Whether you are a school or a mobility agency, we are here to help. Tell us about your situation and we will propose a plan.</p>
        </div>
        <div class="contact reveal">
          <!-- Info column -->
          <div class="contact__info">
            <div class="contact__detail">
              <div class="contact__detail-icon">
                <svg viewBox="0 0 24 24"><path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/></svg>
              </div>
              <div>
                <strong>Email</strong><br>
                <a href="mailto:info@eurygo.com" data-i18n="contact_email">info@eurygo.com</a>
              </div>
            </div>
            <div class="contact__detail">
              <div class="contact__detail-icon">
                <svg viewBox="0 0 24 24"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
              </div>
              <div>
                <strong data-i18n="contact_location">Jerez de la Frontera, Cadiz, Spain</strong>
              </div>
            </div>

            <!-- Social block -->
            <div class="contact__social-block">
              <h4 data-i18n="contact_social_title">Follow us</h4>
              <div class="contact__social-links">
                <!-- TODO: Actualizar a linkedin.com/company/eurygo cuando se cree la página de empresa -->
                <a href="https://www.linkedin.com/in/eurygo" target="_blank" rel="noopener noreferrer" class="contact__social-item social--linkedin" data-track="social" data-social="linkedin">
                  <svg viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
                  <div class="contact__social-text">
                    <strong>LinkedIn</strong>
                    <span data-i18n="contact_linkedin_desc">News and partnerships</span>
                  </div>
                </a>
                <a href="https://www.instagram.com/eury.go/" target="_blank" rel="noopener noreferrer" class="contact__social-item social--instagram" data-track="social" data-social="instagram">
                  <svg viewBox="0 0 24 24"><path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678a6.162 6.162 0 100 12.324 6.162 6.162 0 100-12.324zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405a1.441 1.441 0 11-2.882 0 1.441 1.441 0 012.882 0z"/></svg>
                  <div class="contact__social-text">
                    <strong>Instagram</strong>
                    <span data-i18n="contact_instagram_desc">European mobility stories</span>
                  </div>
                </a>
                <a href="https://www.facebook.com/profile.php?id=61567452016442" target="_blank" rel="noopener noreferrer" class="contact__social-item social--facebook" data-track="social" data-social="facebook">
                  <svg viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                  <div class="contact__social-text">
                    <strong>Facebook</strong>
                    <span data-i18n="contact_facebook_desc">EuryGo community</span>
                  </div>
                </a>
              </div>
            </div>
          </div>

          <!-- Form column -->
          <form class="form" id="contact-form" novalidate>
            <div class="form__body">
              <div class="form__tabs">
                <button type="button" class="form__tab form__tab--active" data-type="school" data-i18n="contact_form_tab_school">I'm a School</button>
                <button type="button" class="form__tab" data-type="agency" data-i18n="contact_form_tab_agency">I'm an Agency</button>
              </div>
              <div class="form__group">
                <label class="form__label" for="contact-name" data-i18n="contact_form_name">Full name</label>
                <input type="text" id="contact-name" name="name" class="form__input" required>
                <span class="form__error">This field is required</span>
              </div>
              <div class="form__group">
                <label class="form__label" for="contact-org" data-i18n="contact_form_org">School / Organisation</label>
                <input type="text" id="contact-org" name="organization" class="form__input">
              </div>
              <div class="form__group">
                <label class="form__label" for="contact-email" data-i18n="contact_form_email">Email</label>
                <input type="email" id="contact-email" name="email" class="form__input" required>
                <span class="form__error">Please enter a valid email</span>
              </div>
              <div class="form__group">
                <label class="form__label" for="contact-phone" data-i18n="contact_form_phone">Phone (optional)</label>
                <input type="tel" id="contact-phone" name="phone" class="form__input">
              </div>
              <div class="form__group">
                <label class="form__label" for="contact-message" data-i18n="contact_form_message">Tell us about your situation</label>
                <textarea id="contact-message" name="message" class="form__textarea" required placeholder="E.g.: We have the KA121 accreditation but need help managing the Beneficiary Module..."></textarea>
                <span class="form__error">This field is required</span>
              </div>
              <!-- Hidden fields -->
              <input type="hidden" name="csrf_token" id="csrf-contact" value="<?= htmlspecialchars($csrf_token) ?>">
              <input type="hidden" name="_segment" value="school">
              <div style="position:absolute;left:-9999px;" aria-hidden="true">
                <input type="text" name="website" tabindex="-1" autocomplete="off">
              </div>
              <!-- GDPR checkbox -->
              <div class="form__checkbox">
                <input type="checkbox" id="contact-privacy" name="privacy" required>
                <label for="contact-privacy" data-i18n="contact_form_privacy">I have read and accept EuryGo's <a href="/en/privacy/">Privacy Policy</a>.</label>
              </div>
              <span class="form__error" style="margin-top: -0.5rem; margin-bottom: 0.75rem;">You must accept the Privacy Policy</span>
              <button type="submit" class="btn btn--primary btn--lg" style="width: 100%;" data-i18n="contact_form_submit">Send message</button>
              <p class="form__legal-text" data-i18n="contact_form_legal">EuryGo processes your data to respond to your enquiry. You may exercise your rights at info@eurygo.com. More info: <a href="/en/privacy/">Privacy Policy</a>.</p>
            </div>
            <div class="form__success">
              <svg viewBox="0 0 24 24"><path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z" fill="currentColor"/></svg>
              <h3 data-i18n="contact_form_success_title">Message sent!</h3>
              <p data-i18n="contact_form_success_text">Thank you for getting in touch. We will reply within 24-48 hours.</p>
            </div>
          </form>
        </div>
      </div>
    </section>

  </main>

  <!-- ========== FOOTER ========== -->
  <footer class="footer">
    <div class="container">
      <div class="footer__grid">
        <!-- Brand -->
        <div class="footer__brand">
          <a href="/en/" class="nav__logo" aria-label="EuryGo">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 360 100">
              <defs>
                <linearGradient id="ftPinGrad" x1="0%" y1="0%" x2="100%" y2="100%">
                  <stop offset="0%" stop-color="#7DD3FC"/><stop offset="50%" stop-color="#38BDF8"/><stop offset="100%" stop-color="#0284C7"/>
                </linearGradient>
                <linearGradient id="ftPlaneLight" x1="0%" y1="0%" x2="100%" y2="100%">
                  <stop offset="0%" stop-color="#FDE68A"/><stop offset="100%" stop-color="#FBBF24"/>
                </linearGradient>
                <linearGradient id="ftPlaneMed" x1="0%" y1="0%" x2="100%" y2="100%">
                  <stop offset="0%" stop-color="#FBBF24"/><stop offset="100%" stop-color="#F59E0B"/>
                </linearGradient>
                <linearGradient id="ftPlaneDark" x1="0%" y1="0%" x2="100%" y2="100%">
                  <stop offset="0%" stop-color="#F59E0B"/><stop offset="100%" stop-color="#D97706"/>
                </linearGradient>
                <linearGradient id="ftGoldText" x1="0%" y1="0%" x2="100%" y2="0%">
                  <stop offset="0%" stop-color="#D97706"/><stop offset="100%" stop-color="#F59E0B"/>
                </linearGradient>
              </defs>
              <g transform="translate(15, 5)">
                <ellipse cx="40" cy="88" rx="14" ry="3" fill="#000" opacity="0.2"/>
                <polygon points="0,-5 1.5,-1.5 5,-1.5 2,1 3,5 0,2.5 -3,5 -2,1 -5,-1.5 -1.5,-1.5" transform="translate(6, 40) scale(0.5)" fill="#FBBF24"/>
                <polygon points="0,-5 1.5,-1.5 5,-1.5 2,1 3,5 0,2.5 -3,5 -2,1 -5,-1.5 -1.5,-1.5" transform="translate(10, 22) scale(0.7)" fill="#F59E0B"/>
                <polygon points="0,-5 1.5,-1.5 5,-1.5 2,1 3,5 0,2.5 -3,5 -2,1 -5,-1.5 -1.5,-1.5" transform="translate(24, 8) scale(0.9)" fill="#D97706"/>
                <path d="M 22,60 Q 30,52 38,44" fill="none" stroke="#FBBF24" stroke-width="2.5" stroke-linecap="round" stroke-dasharray="4,5" opacity="0.8"/>
                <path d="M 40,85 C 20,65 12,48 12,38 A 28,28 0 0,1 58,16" fill="none" stroke="url(#ftPinGrad)" stroke-width="7" stroke-linecap="round"/>
                <path d="M 40,85 C 55,70 65,55 67,45" fill="none" stroke="url(#ftPinGrad)" stroke-width="7" stroke-linecap="round"/>
                <g transform="translate(2, 4)" fill="#000" opacity="0.4">
                  <polygon points="68,12 28,36 44,40"/><polygon points="68,12 44,40 54,44"/><polygon points="44,40 48,56 54,44"/>
                </g>
                <g>
                  <polygon points="68,12 28,36 44,40" fill="url(#ftPlaneLight)"/>
                  <polygon points="68,12 44,40 54,44" fill="url(#ftPlaneMed)"/>
                  <polygon points="44,40 48,56 54,44" fill="url(#ftPlaneDark)"/>
                </g>
              </g>
              <text x="105" y="58" font-family="'DM Sans', sans-serif" font-weight="800" font-size="46" fill="#FFFFFF" letter-spacing="-1">Eury<tspan fill="url(#ftGoldText)">Go</tspan></text>
              <text x="108" y="78" font-family="'DM Sans', sans-serif" font-weight="600" font-size="11.5" fill="#E2E8F0" letter-spacing="3.5">MOBILITY EXPERIENCE</text>
            </svg>
          </a>
          <p data-i18n="footer_desc">Erasmus+ intermediary connecting schools, mobility agencies and EU funding. Zero Bureaucracy. Premium Mobility.</p>
        </div>

        <!-- Navigation -->
        <div>
          <h4 data-i18n="footer_nav">Navigation</h4>
          <div class="footer__links">
            <a href="#about" data-i18n="nav_about">What is EuryGo?</a>
            <a href="#schools" data-i18n="nav_schools">Schools</a>
            <a href="#agencies" data-i18n="nav_agencies">Agencies</a>
            <a href="/en/cursos/" data-i18n="nav_courses">Training Courses</a>
            <a href="/en/blog/" data-i18n="nav_blog">Blog</a>
            <a href="#contact" data-i18n="nav_contact">Contact</a>
          </div>
        </div>

        <!-- Legal -->
        <div>
          <h4 data-i18n="footer_legal_title">Legal</h4>
          <div class="footer__links">
            <a href="/en/privacy/" data-i18n="footer_privacy">Privacy Policy</a>
            <a href="/en/cookies/" data-i18n="footer_cookies">Cookie Policy</a>
            <a href="/en/legal-notice/" data-i18n="footer_legal">Legal Notice</a>
            <a href="#" data-action="manage-cookies" data-i18n="footer_manage_cookies">Manage Cookies</a>
          </div>
        </div>

        <!-- Social -->
        <div>
          <h4 data-i18n="footer_social">Follow us</h4>
          <div class="footer__social-block">
            <!-- TODO: Actualizar a linkedin.com/company/eurygo cuando se cree la página de empresa -->
            <a href="https://www.linkedin.com/in/eurygo" target="_blank" rel="noopener noreferrer" class="footer__social-item" data-track="social" data-social="linkedin">
              <svg viewBox="0 0 24 24"><path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433a2.062 2.062 0 01-2.063-2.065 2.064 2.064 0 112.063 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/></svg>
              <span>LinkedIn <small data-i18n="contact_linkedin_desc">News and partnerships</small></span>
            </a>
            <a href="https://www.instagram.com/eury.go/" target="_blank" rel="noopener noreferrer" class="footer__social-item" data-track="social" data-social="instagram">
              <svg viewBox="0 0 24 24"><path d="M12 0C8.74 0 8.333.015 7.053.072 5.775.132 4.905.333 4.14.63c-.789.306-1.459.717-2.126 1.384S.935 3.35.63 4.14C.333 4.905.131 5.775.072 7.053.012 8.333 0 8.74 0 12s.015 3.667.072 4.947c.06 1.277.261 2.148.558 2.913.306.788.717 1.459 1.384 2.126.667.666 1.336 1.079 2.126 1.384.766.296 1.636.499 2.913.558C8.333 23.988 8.74 24 12 24s3.667-.015 4.947-.072c1.277-.06 2.148-.262 2.913-.558.788-.306 1.459-.718 2.126-1.384.666-.667 1.079-1.335 1.384-2.126.296-.765.499-1.636.558-2.913.06-1.28.072-1.687.072-4.947s-.015-3.667-.072-4.947c-.06-1.277-.262-2.149-.558-2.913-.306-.789-.718-1.459-1.384-2.126C21.319 1.347 20.651.935 19.86.63c-.765-.297-1.636-.499-2.913-.558C15.667.012 15.26 0 12 0zm0 2.16c3.203 0 3.585.016 4.85.071 1.17.055 1.805.249 2.227.415.562.217.96.477 1.382.896.419.42.679.819.896 1.381.164.422.36 1.057.413 2.227.057 1.266.07 1.646.07 4.85s-.015 3.585-.074 4.85c-.061 1.17-.256 1.805-.421 2.227-.224.562-.479.96-.899 1.382-.419.419-.824.679-1.38.896-.42.164-1.065.36-2.235.413-1.274.057-1.649.07-4.859.07-3.211 0-3.586-.015-4.859-.074-1.171-.061-1.816-.256-2.236-.421-.569-.224-.96-.479-1.379-.899-.421-.419-.69-.824-.9-1.38-.165-.42-.359-1.065-.42-2.235-.045-1.26-.061-1.649-.061-4.844 0-3.196.016-3.586.061-4.861.061-1.17.255-1.814.42-2.234.21-.57.479-.96.9-1.381.419-.419.81-.689 1.379-.898.42-.166 1.051-.361 2.221-.421 1.275-.045 1.65-.06 4.859-.06l.045.03zm0 3.678a6.162 6.162 0 100 12.324 6.162 6.162 0 100-12.324zM12 16c-2.21 0-4-1.79-4-4s1.79-4 4-4 4 1.79 4 4-1.79 4-4 4zm7.846-10.405a1.441 1.441 0 11-2.882 0 1.441 1.441 0 012.882 0z"/></svg>
              <span>Instagram <small data-i18n="contact_instagram_desc">European mobility stories</small></span>
            </a>
            <a href="https://www.facebook.com/profile.php?id=61567452016442" target="_blank" rel="noopener noreferrer" class="footer__social-item" data-track="social" data-social="facebook">
              <svg viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
              <span>Facebook <small data-i18n="contact_facebook_desc">EuryGo community</small></span>
            </a>
          </div>
        </div>
      </div>

      <!-- Bottom bar -->
      <div class="footer__bottom">
        <span data-i18n="footer_rights">&copy; 2025 EuryGo. All rights reserved.</span>
        <div class="footer__bottom-links">
          <a href="/en/legal-notice/" data-i18n="footer_legal">Legal Notice</a>
          <a href="/en/privacy/" data-i18n="footer_privacy">Privacy Policy</a>
          <a href="/en/cookies/" data-i18n="footer_cookies">Cookie Policy</a>
          <a href="#" data-action="manage-cookies" data-i18n="footer_manage_cookies">Manage Cookies</a>
        </div>
      </div>
    </div>
  </footer>

  <!-- ========== COOKIE BANNER ========== -->
  <div class="cookie-banner" id="cookie-banner" role="dialog" aria-label="Cookie consent">
    <div class="cookie-banner__inner">
      <p class="cookie-banner__text">
        <span data-i18n="cookie_text">We use our own and third-party cookies to analyse site usage and improve your experience. You can accept all, configure them or reject non-essential cookies.</span>
        <a href="/en/cookies/" data-i18n="cookie_more">Cookie Policy</a>
      </p>
      <div class="cookie-banner__actions">
        <button class="btn btn--primary btn--sm" id="cookie-accept-all" data-i18n="cookie_accept">Accept all</button>
        <button class="btn btn--outline btn--sm" id="cookie-essential" data-i18n="cookie_essential">Essential only</button>
        <button class="btn btn--sm" id="cookie-manage" style="color: var(--color-primary); font-weight: 600;" data-i18n="cookie_manage">Configure</button>
      </div>
    </div>
  </div>

  <!-- ========== COOKIE PREFERENCES MODAL ========== -->
  <div class="cookie-modal" id="cookie-modal" role="dialog" aria-label="Cookie preferences">
    <div class="cookie-modal__overlay"></div>
    <div class="cookie-modal__content">
      <button class="cookie-modal__close" id="cookie-modal-close" aria-label="Close">&times;</button>
      <h3 data-i18n="cookie_modal_title">Cookie preferences</h3>
      <p data-i18n="cookie_modal_text">Manage which cookies you wish to allow. Essential cookies are required for the site to function and cannot be disabled.</p>

      <!-- Essential -->
      <div class="cookie-category">
        <div class="cookie-category__header">
          <h4 data-i18n="cookie_cat_essential">Essential cookies</h4>
          <span class="badge" data-i18n="cookie_cat_essential_badge">Always active</span>
        </div>
        <p data-i18n="cookie_cat_essential_desc">Required for the site to function (language, form security, consent). They do not collect personal data.</p>
      </div>

      <!-- Analytics -->
      <div class="cookie-category">
        <div class="cookie-category__header">
          <h4 data-i18n="cookie_cat_analytics">Analytics cookies</h4>
          <label class="toggle">
            <input type="checkbox" id="cookie-analytics">
            <span class="toggle__slider"></span>
          </label>
        </div>
        <p data-i18n="cookie_cat_analytics_desc">Google Analytics 4 -- helps us understand how the site is used so we can improve it. Data is anonymous and aggregated. Provider: Google LLC.</p>
      </div>

      <!-- Marketing -->
      <div class="cookie-category">
        <div class="cookie-category__header">
          <h4 data-i18n="cookie_cat_marketing">Marketing cookies</h4>
          <label class="toggle">
            <input type="checkbox" id="cookie-marketing">
            <span class="toggle__slider"></span>
          </label>
        </div>
        <p data-i18n="cookie_cat_marketing_desc">Personalised advertising and social media tracking. Reserved for future use if paid campaigns are activated.</p>
      </div>

      <div class="cookie-modal__actions">
        <button class="btn btn--primary btn--sm" id="cookie-modal-accept" data-i18n="cookie_modal_accept">Accept all</button>
        <button class="btn btn--outline btn--sm" id="cookie-modal-save" data-i18n="cookie_modal_save">Save preferences</button>
      </div>
    </div>
  </div>

  <!-- Floating CTA Bar -->
  <div class="floating-cta" id="floating-cta" aria-hidden="true">
    <div class="floating-cta__inner">
      <span class="floating-cta__text" data-i18n="floating_cta_text">Ready for your Erasmus+ project?</span>
      <a href="#contact" class="btn btn--gold btn--sm" data-i18n="floating_cta_btn">Contact us now</a>
    </div>
  </div>

  <!-- Scripts -->
  <script src="/assets/js/i18n.js"></script>
  <script src="/assets/js/cookies.js"></script>
  <script src="/assets/js/analytics.js"></script>
  <script src="/assets/js/main.js"></script>

</body>
</html>
