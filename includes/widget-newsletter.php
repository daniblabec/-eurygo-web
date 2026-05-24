<?php
/**
 * Widget de newsletter reutilizable.
 * Incluir en cualquier página con: <?php include __DIR__ . '/includes/widget-newsletter.php'; ?>
 * Requiere: $idioma_actual definida antes del include.
 */

$idioma_actual = $idioma_actual ?? $idioma ?? 'es';
$es_en = ($idioma_actual === 'en');
?>
<section class="newsletter-widget">
  <div class="newsletter-widget__inner">
    <h3><?= $es_en ? 'Calls, guides and trends — once a month.' : 'Convocatorias, guías y tendencias, una vez al mes.' ?></h3>
    <p><?= $es_en ? 'Get Erasmus+ updates directly in your inbox.' : 'Recibe novedades sobre Erasmus+ directamente en tu correo.' ?></p>
    <form id="form-newsletter" class="newsletter-form" method="POST" action="/newsletter/suscribir.php">
      <?= campo_csrf() ?>
      <input type="hidden" name="idioma" value="<?= $idioma_actual ?>">
      <input type="hidden" name="form_time" id="newsletter_form_time" value="">
      <div class="newsletter-fields">
        <input type="text" name="nombre" placeholder="<?= $es_en ? 'Your name (optional)' : 'Tu nombre (opcional)' ?>" class="newsletter-fields__input">
        <input type="email" name="email" placeholder="<?= $es_en ? 'Your email *' : 'Tu email *' ?>" required class="newsletter-fields__input">
        <button type="submit" class="newsletter-fields__btn"><?= $es_en ? 'Subscribe' : 'Suscribirme' ?></button>
      </div>
      <label class="newsletter-rgpd">
        <input type="checkbox" name="consentimiento_rgpd" value="1" required>
        <?= $es_en
            ? 'I have read and accept the <a href="/en/privacy/" target="_blank">Privacy Policy</a>. I can unsubscribe at any time.'
            : 'He leído y acepto la <a href="/privacidad/" target="_blank">Política de Privacidad</a>. Puedo darme de baja en cualquier momento.' ?>
      </label>

      <!-- Honeypot anti-bot (oculto a humanos) -->
      <div style="position:absolute;left:-9999px;top:-9999px" aria-hidden="true" tabindex="-1">
        <input type="text" name="website" value="" tabindex="-1" autocomplete="off">
      </div>

<?php if (defined('TURNSTILE_SITE_KEY') && TURNSTILE_SITE_KEY && strpos(TURNSTILE_SITE_KEY, 'TU_') !== 0): ?>
      <!-- Cloudflare Turnstile (CAPTCHA invisible) -->
      <div class="cf-turnstile" data-sitekey="<?= htmlspecialchars(TURNSTILE_SITE_KEY) ?>" data-callback="onTurnstileSuccess" data-theme="light"></div>
      <input type="hidden" name="cf_turnstile_response" id="cf-turnstile-response" value="">
<?php endif; ?>

      <div id="newsletter-feedback" class="newsletter-feedback" hidden></div>
    </form>
  </div>
</section>
<?php if (defined('TURNSTILE_SITE_KEY') && TURNSTILE_SITE_KEY && strpos(TURNSTILE_SITE_KEY, 'TU_') !== 0): ?>
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
<script>
function onTurnstileSuccess(token) {
  var f = document.getElementById('cf-turnstile-response');
  if (f) f.value = token;
}
</script>
<?php endif; ?>
<script>
(function(){
  var form = document.getElementById('form-newsletter');
  if (!form) return;

  // Rellenar timestamp del formulario al cargar (anti-bot)
  var timeField = document.getElementById('newsletter_form_time');
  if (timeField) timeField.value = Math.floor(Date.now() / 1000);

  form.addEventListener('submit', function(e) {
    e.preventDefault();
    var fb = document.getElementById('newsletter-feedback');
    var btn = form.querySelector('button[type="submit"]');
    var origText = btn.textContent;
    btn.disabled = true;
    btn.textContent = '...';
    fb.hidden = true;

    var data = new FormData(form);
    fetch(form.action, { method: 'POST', body: data })
      .then(function(r) { return r.json(); })
      .then(function(j) {
        fb.hidden = false;
        fb.textContent = j.mensaje || (j.ok ? 'OK' : 'Error');
        fb.className = 'newsletter-feedback ' + (j.ok ? 'newsletter-feedback--ok' : 'newsletter-feedback--error');
        if (j.ok) form.reset();
      })
      .catch(function() {
        fb.hidden = false;
        fb.textContent = '<?= $es_en ? "Connection error. Try again." : "Error de conexión. Inténtalo de nuevo." ?>';
        fb.className = 'newsletter-feedback newsletter-feedback--error';
      })
      .finally(function() {
        btn.disabled = false;
        btn.textContent = origText;
      });
  });
})();
</script>
