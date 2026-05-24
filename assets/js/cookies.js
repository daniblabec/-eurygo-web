/* ============================================
   EuryGo — Cookie Consent Manager (RGPD)
   ============================================ */

(function() {
  'use strict';

  var STORAGE_KEY = 'eurygo_cookie_consent';
  var CONSENT_VERSION = '1.0';
  var CONSENT_DURATION_DAYS = 365;

  /**
   * Get saved consent from localStorage
   */
  function getConsent() {
    try {
      var raw = localStorage.getItem(STORAGE_KEY);
      if (!raw) return null;
      var data = JSON.parse(raw);
      if (data.version !== CONSENT_VERSION) return null;
      // Check if consent is still valid (12 months)
      var savedDate = new Date(data.timestamp);
      var now = new Date();
      var diffDays = (now - savedDate) / (1000 * 60 * 60 * 24);
      if (diffDays > CONSENT_DURATION_DAYS) return null;
      return data;
    } catch (e) {
      return null;
    }
  }

  /**
   * Save consent to localStorage
   */
  function saveConsent(analytics, marketing) {
    var data = {
      essential: true,
      analytics: !!analytics,
      marketing: !!marketing,
      timestamp: new Date().toISOString(),
      version: CONSENT_VERSION
    };
    localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
    updateGoogleConsent(data);
    return data;
  }

  /**
   * Update Google Consent Mode
   */
  function updateGoogleConsent(data) {
    if (typeof gtag !== 'function') return;
    gtag('consent', 'update', {
      'analytics_storage': data.analytics ? 'granted' : 'denied',
      'ad_storage': data.marketing ? 'granted' : 'denied',
      'ad_user_data': data.marketing ? 'granted' : 'denied',
      'ad_personalization': data.marketing ? 'granted' : 'denied'
    });
  }

  /**
   * Show cookie banner
   */
  function showBanner() {
    var banner = document.getElementById('cookie-banner');
    if (banner) {
      setTimeout(function() {
        banner.classList.add('cookie-banner--visible');
      }, 800);
    }
  }

  /**
   * Hide cookie banner
   */
  function hideBanner() {
    var banner = document.getElementById('cookie-banner');
    if (banner) {
      banner.classList.remove('cookie-banner--visible');
    }
  }

  /**
   * Show preferences modal
   */
  function showModal() {
    var modal = document.getElementById('cookie-modal');
    if (modal) {
      modal.classList.add('cookie-modal--visible');
      document.body.style.overflow = 'hidden';
      // Load current state into toggles
      var consent = getConsent();
      var analyticsToggle = document.getElementById('cookie-analytics');
      var marketingToggle = document.getElementById('cookie-marketing');
      if (analyticsToggle) analyticsToggle.checked = consent ? consent.analytics : false;
      if (marketingToggle) marketingToggle.checked = consent ? consent.marketing : false;
    }
  }

  /**
   * Hide preferences modal
   */
  function hideModal() {
    var modal = document.getElementById('cookie-modal');
    if (modal) {
      modal.classList.remove('cookie-modal--visible');
      document.body.style.overflow = '';
    }
  }

  /**
   * Accept all cookies
   */
  function acceptAll() {
    saveConsent(true, true);
    hideBanner();
    hideModal();
  }

  /**
   * Accept essential only
   */
  function acceptEssential() {
    saveConsent(false, false);
    hideBanner();
    hideModal();
  }

  /**
   * Save preferences from modal
   */
  function savePreferences() {
    var analyticsToggle = document.getElementById('cookie-analytics');
    var marketingToggle = document.getElementById('cookie-marketing');
    var analytics = analyticsToggle ? analyticsToggle.checked : false;
    var marketing = marketingToggle ? marketingToggle.checked : false;
    saveConsent(analytics, marketing);
    hideBanner();
    hideModal();
  }

  /**
   * Init on DOM ready
   */
  function init() {
    var consent = getConsent();

    // If no consent saved, show the banner
    if (!consent) {
      showBanner();
    } else {
      // Apply saved consent to Google
      updateGoogleConsent(consent);
    }

    // Banner buttons
    var btnAcceptAll = document.getElementById('cookie-accept-all');
    var btnEssential = document.getElementById('cookie-essential');
    var btnManage = document.getElementById('cookie-manage');

    if (btnAcceptAll) btnAcceptAll.addEventListener('click', acceptAll);
    if (btnEssential) btnEssential.addEventListener('click', acceptEssential);
    if (btnManage) btnManage.addEventListener('click', function() {
      hideBanner();
      showModal();
    });

    // Modal buttons
    var btnModalAccept = document.getElementById('cookie-modal-accept');
    var btnModalSave = document.getElementById('cookie-modal-save');
    var btnModalClose = document.getElementById('cookie-modal-close');
    var modalOverlay = document.querySelector('.cookie-modal__overlay');

    if (btnModalAccept) btnModalAccept.addEventListener('click', acceptAll);
    if (btnModalSave) btnModalSave.addEventListener('click', savePreferences);
    if (btnModalClose) btnModalClose.addEventListener('click', hideModal);
    if (modalOverlay) modalOverlay.addEventListener('click', hideModal);

    // Footer "Manage cookies" link
    document.querySelectorAll('[data-action="manage-cookies"]').forEach(function(link) {
      link.addEventListener('click', function(e) {
        e.preventDefault();
        showModal();
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // Expose for external use
  window.EurygoCookies = {
    showModal: showModal,
    getConsent: getConsent
  };

})();
