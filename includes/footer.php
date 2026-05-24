<?php
/**
 * Footer compartido del frontend público.
 * Variable esperada: $idioma
 */
$idioma = $idioma ?? 'es';
?>

  <footer class="footer">
    <div class="container">
      <div class="footer__bottom">
        <span><?= $idioma === 'en' ? '© 2025 EuryGo. All rights reserved.' : '© 2025 EuryGo. Todos los derechos reservados.' ?></span>
        <div class="footer__bottom-links">
<?php if ($idioma === 'en'): ?>
          <a href="/en/legal-notice/">Legal Notice</a>
          <a href="/en/privacy/">Privacy Policy</a>
          <a href="/en/cookies/">Cookie Policy</a>
          <a href="#" data-action="manage-cookies">Manage Cookies</a>
<?php else: ?>
          <a href="/aviso-legal/">Aviso Legal</a>
          <a href="/privacidad/">Política de Privacidad</a>
          <a href="/cookies/">Política de Cookies</a>
          <a href="#" data-action="manage-cookies">Gestionar Cookies</a>
<?php endif; ?>
        </div>
      </div>
    </div>
  </footer>

  <!-- Cookie Banner -->
  <div class="cookie-banner" id="cookie-banner" role="dialog">
    <div class="cookie-banner__inner">
      <p class="cookie-banner__text"><?= $idioma === 'en'
        ? 'We use cookies to analyse site usage and improve your experience. <a href="/en/cookies/">Cookie Policy</a>'
        : 'Usamos cookies para analizar el uso del sitio. <a href="/cookies/">Más info</a>' ?></p>
      <div class="cookie-banner__actions">
        <button class="btn btn--primary btn--sm" id="cookie-accept-all"><?= $idioma === 'en' ? 'Accept all' : 'Aceptar todas' ?></button>
        <button class="btn btn--outline btn--sm" id="cookie-essential"><?= $idioma === 'en' ? 'Essential only' : 'Solo esenciales' ?></button>
        <button class="btn btn--sm" id="cookie-manage" style="color:var(--color-primary);font-weight:600;"><?= $idioma === 'en' ? 'Manage' : 'Configurar' ?></button>
      </div>
    </div>
  </div>
  <div class="cookie-modal" id="cookie-modal" role="dialog">
    <div class="cookie-modal__overlay"></div>
    <div class="cookie-modal__content">
      <button class="cookie-modal__close" id="cookie-modal-close">&times;</button>
      <h3><?= $idioma === 'en' ? 'Cookie preferences' : 'Preferencias de cookies' ?></h3>
      <div class="cookie-category"><div class="cookie-category__header"><h4><?= $idioma === 'en' ? 'Essential' : 'Esenciales' ?></h4><span class="badge"><?= $idioma === 'en' ? 'Always on' : 'Siempre activas' ?></span></div></div>
      <div class="cookie-category"><div class="cookie-category__header"><h4><?= $idioma === 'en' ? 'Analytics' : 'Analíticas' ?></h4><label class="toggle"><input type="checkbox" id="cookie-analytics"><span class="toggle__slider"></span></label></div></div>
      <div class="cookie-category"><div class="cookie-category__header"><h4>Marketing</h4><label class="toggle"><input type="checkbox" id="cookie-marketing"><span class="toggle__slider"></span></label></div></div>
      <div class="cookie-modal__actions">
        <button class="btn btn--primary btn--sm" id="cookie-modal-accept"><?= $idioma === 'en' ? 'Accept all' : 'Aceptar todas' ?></button>
        <button class="btn btn--outline btn--sm" id="cookie-modal-save"><?= $idioma === 'en' ? 'Save' : 'Guardar' ?></button>
      </div>
    </div>
  </div>

  <script src="/assets/js/i18n.js"></script>
  <script src="/assets/js/cookies.js"></script>
  <script src="/assets/js/analytics.js"></script>
  <script src="/assets/js/main.js"></script>
</body>
</html>
