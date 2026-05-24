/* ============================================
   EuryGo — Google Analytics 4 + Custom Events
   GA4 Measurement ID: set via GA4_ID in config.php
   ============================================ */

(function() {
  'use strict';

  /**
   * Helper: track a GA4 event
   */
  function trackEvent(eventName, params) {
    if (typeof gtag === 'function') {
      gtag('event', eventName, params || {});
    }
  }

  /**
   * 1. click_cta_centros — hero CTA "Soy Centro Escolar"
   */
  function trackCTACentros() {
    document.querySelectorAll('[data-track="cta-centros"]').forEach(function(el) {
      el.addEventListener('click', function() {
        trackEvent('click_cta_centros', {
          'event_category': 'engagement',
          'event_label': 'hero_cta_schools'
        });
      });
    });
  }

  /**
   * 2. click_cta_agencias — hero CTA "Soy Agencia"
   */
  function trackCTAAgencias() {
    document.querySelectorAll('[data-track="cta-agencias"]').forEach(function(el) {
      el.addEventListener('click', function() {
        trackEvent('click_cta_agencias', {
          'event_category': 'engagement',
          'event_label': 'hero_cta_agencies'
        });
      });
    });
  }

  /**
   * 3. form_submit_contact — contact form submission
   */
  function trackFormSubmit() {
    var form = document.getElementById('contact-form');
    if (form) {
      form.addEventListener('submit', function() {
        trackEvent('form_submit_contact', {
          'event_category': 'conversion',
          'event_label': 'contact_form'
        });
      });
    }
  }

  /**
   * 4. newsletter_signup — newsletter subscription
   */
  function trackNewsletter() {
    document.querySelectorAll('[data-track="newsletter"]').forEach(function(form) {
      form.addEventListener('submit', function() {
        trackEvent('newsletter_signup', {
          'event_category': 'conversion',
          'event_label': 'newsletter_blog'
        });
      });
    });
  }

  /**
   * 5. blog_article_read — time on article page > 60s
   */
  function trackArticleRead() {
    var article = document.querySelector('.article__content');
    if (!article) return;

    var tracked = false;
    setTimeout(function() {
      if (!tracked && document.visibilityState === 'visible') {
        tracked = true;
        trackEvent('blog_article_read', {
          'event_category': 'engagement',
          'event_label': document.title,
          'article_url': window.location.pathname
        });
      }
    }, 60000);

    document.addEventListener('visibilitychange', function() {
      // Reset if user leaves before 60s
    });
  }

  /**
   * 6. social_click — click on social media icons
   */
  function trackSocialClicks() {
    document.querySelectorAll('[data-track="social"]').forEach(function(link) {
      link.addEventListener('click', function() {
        trackEvent('social_click', {
          'event_category': 'engagement',
          'event_label': this.getAttribute('data-social') || 'unknown',
          'outbound': true
        });
      });
    });
  }

  /**
   * 7. language_switch — handled in i18n.js switchLang()
   * (already integrated there)
   */

  /**
   * Init all tracking
   */
  function init() {
    trackCTACentros();
    trackCTAAgencias();
    trackFormSubmit();
    trackNewsletter();
    trackArticleRead();
    trackSocialClicks();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

})();
