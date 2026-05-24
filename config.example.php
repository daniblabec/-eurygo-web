<?php
/**
 * PLANTILLA DE CONFIGURACIÓN — copiar a config.php y rellenar.
 *
 * config.php está en .gitignore y NUNCA debe subirse al repositorio.
 * Las credenciales viven solo en el servidor de producción y en tu copia local.
 */

// ─── Base de datos OVH ───────────────────────────────────────────
define('DB_HOST', 'TU_HOST_MYSQL_OVH');
define('DB_NAME', 'TU_DB_NAME');
define('DB_USER', 'TU_DB_USER');
define('DB_PASS', 'TU_DB_PASS');
define('DB_CHARSET', 'utf8mb4');

// ─── URLs base ───────────────────────────────────────────────────
define('SITE_URL', 'https://www.eurygo.com');
define('ADMIN_URL', 'https://www.eurygo.com/admin');
define('UPLOAD_DIR', __DIR__ . '/assets/images/blog/');
define('UPLOAD_URL', SITE_URL . '/assets/images/blog/');

// ─── Uploads ─────────────────────────────────────────────────────
define('MAX_FILE_SIZE', 5 * 1024 * 1024);
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/webp']);

// ─── Google Analytics 4 ──────────────────────────────────────────
define('GA4_ID', 'G-XXXXXXXX');

// ─── SMTP (OVH) ──────────────────────────────────────────────────
define('MAIL_HOST', 'ssl0.ovh.net');
define('MAIL_PORT', 587);
define('MAIL_USER', 'TU_EMAIL_SMTP');
define('MAIL_PASS', 'TU_PASS_SMTP');
define('MAIL_FROM', 'info@eurygo.eu');
define('MAIL_FROM_NAME', 'EuryGo');
define('MAIL_CONTACT', 'info@eurygo.eu');
define('MAIL_CENTROS', 'centros@eurygo.eu');
define('MAIL_AGENCIAS', 'agencias@eurygo.eu');
define('MAIL_NEWSLETTER', 'newsletter@eurygo.eu');
define('MAIL_CURSOS', 'cursos@eurygo.eu');

// ─── Brevo (newsletter) ──────────────────────────────────────────
define('BREVO_API_KEY', 'TU_BREVO_API_KEY');
define('BREVO_LIST_ID_ES', 0);
define('BREVO_LIST_ID_EN', 0);
define('BREVO_FROM_EMAIL', MAIL_FROM);
define('BREVO_FROM_NAME', MAIL_FROM_NAME);

define('NEWSLETTER_CONFIRM_URL', SITE_URL . '/newsletter/confirmar/');
define('NEWSLETTER_BAJA_URL', SITE_URL . '/newsletter/baja/');

// ─── Cloudflare Turnstile (CAPTCHA invisible) ────────────────────
// El SITE_KEY es público; el SECRET nunca debe quedar expuesto en HTML.
define('TURNSTILE_SITE_KEY', 'TU_TURNSTILE_SITE_KEY');
define('TURNSTILE_SECRET',   'TU_TURNSTILE_SECRET');
