/* ============================================
   EuryGo — Main JavaScript
   ============================================ */

(function() {
  'use strict';

  /* --- Navigation scroll effect --- */
  var nav = document.querySelector('.nav');
  function onScroll() {
    if (!nav) return;
    if (window.scrollY > 20) {
      nav.classList.add('nav--scrolled');
    } else {
      nav.classList.remove('nav--scrolled');
    }
  }
  window.addEventListener('scroll', onScroll, { passive: true });

  /* --- Mobile menu toggle --- */
  var navToggle = document.querySelector('.nav__toggle');
  var navLinks = document.querySelector('.nav__links');
  if (navToggle && navLinks) {
    navToggle.addEventListener('click', function() {
      navToggle.classList.toggle('nav__toggle--active');
      navLinks.classList.toggle('nav__links--open');
    });
    // Close on link click
    navLinks.querySelectorAll('.nav__link').forEach(function(link) {
      link.addEventListener('click', function() {
        navToggle.classList.remove('nav__toggle--active');
        navLinks.classList.remove('nav__links--open');
      });
    });
  }

  /* --- Scroll reveal animations --- */
  function initReveal() {
    var reveals = document.querySelectorAll('.reveal');
    if (!reveals.length) return;

    var observer = new IntersectionObserver(function(entries) {
      entries.forEach(function(entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add('reveal--visible');
          observer.unobserve(entry.target);
        }
      });
    }, {
      threshold: 0.1,
      rootMargin: '0px 0px -40px 0px'
    });

    reveals.forEach(function(el) {
      observer.observe(el);
    });
  }

  /* --- Contact form tabs --- */
  var currentContactType = 'school';

  function initFormTabs() {
    var tabs = document.querySelectorAll('.form__tab');
    var messageField = document.getElementById('contact-message');
    var segmentField = document.querySelector('input[name="_segment"]');
    if (!tabs.length || !messageField) return;

    tabs.forEach(function(tab) {
      tab.addEventListener('click', function() {
        tabs.forEach(function(t) { t.classList.remove('form__tab--active'); });
        this.classList.add('form__tab--active');
        currentContactType = this.dataset.type;
        if (segmentField) segmentField.value = currentContactType;
        var lang = document.documentElement.lang || 'es';
        var placeholders = {
          school: {
            es: 'Ej: Tenemos la acreditación KA121 pero necesitamos ayuda con la gestión del Beneficiary Module...',
            en: 'E.g.: We hold a KA121 accreditation but need help managing the Beneficiary Module...'
          },
          agency: {
            es: 'Ej: Somos una agencia de movilidad en Alemania y buscamos un partner en Andalucía para organizar Jobshadowing...',
            en: 'E.g.: We are a mobility agency in Germany looking for a partner in Andalusia to organise Jobshadowing...'
          }
        };
        if (placeholders[currentContactType] && placeholders[currentContactType][lang]) {
          messageField.placeholder = placeholders[currentContactType][lang];
        }
      });
    });
  }

  /* --- Contact form validation & submission --- */
  function initContactForm() {
    var form = document.getElementById('contact-form');
    if (!form) return;

    form.addEventListener('submit', async function(e) {
      e.preventDefault();

      // Basic validation
      var name = form.querySelector('[name="name"]');
      var email = form.querySelector('[name="email"]');
      var message = form.querySelector('[name="message"]');
      var privacy = form.querySelector('[name="privacy"]');
      var valid = true;

      // Reset errors
      form.querySelectorAll('.form__error').forEach(function(err) {
        err.style.display = 'none';
      });

      if (!name || !name.value.trim()) { showError(name, true); valid = false; }
      if (!email || !email.value.trim() || !isValidEmail(email.value)) { showError(email, true); valid = false; }
      if (!message || !message.value.trim()) { showError(message, true); valid = false; }
      if (!privacy || !privacy.checked) { showError(privacy, true); valid = false; }
      if (!valid) return;

      // Disable button while sending
      var submitBtn = form.querySelector('button[type="submit"]');
      var idioma = document.documentElement.lang || 'es';
      var textoOriginal = submitBtn ? submitBtn.textContent : '';
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = idioma === 'en' ? 'Sending...' : 'Enviando...';
      }

      // Map type: school → centro, agency → agencia
      var tipo = currentContactType === 'school' ? 'centro' : (currentContactType === 'agency' ? 'agencia' : 'otro');

      // Build FormData with field names matching procesar.php
      var formData = new FormData();
      formData.append('tipo', tipo);
      formData.append('nombre', name.value.trim());
      formData.append('email', email.value.trim());
      formData.append('telefono', (form.querySelector('[name="phone"]') || {}).value || '');
      formData.append('organizacion', (form.querySelector('[name="organization"]') || {}).value || '');
      formData.append('mensaje', message.value.trim());
      formData.append('idioma', idioma);
      formData.append('consentimiento_rgpd', '1');
      formData.append('website', '');

      try {
        // Obtain fresh CSRF token before sending (same pattern as course form)
        var csrfRes = await fetch('/contacto/csrf.php');
        var csrfData = await csrfRes.json();

        // Update hidden field if it exists
        var tokenField = form.querySelector('[name="csrf_token"]');
        if (tokenField && csrfData.token) {
          tokenField.value = csrfData.token;
        }
        formData.set('csrf_token', csrfData.token);

        var res = await fetch('/contacto/procesar.php', {
          method: 'POST',
          body: formData
        });

        var text = await res.text();
        var result;
        try {
          var jsonStart = text.indexOf('{');
          result = jsonStart >= 0 ? JSON.parse(text.substring(jsonStart)) : JSON.parse(text);
        } catch(parseErr) {
          result = res.ok ? { ok: true } : { ok: false, mensaje: 'Error en el servidor.' };
        }

        if (result.ok) {
          var formBody = form.querySelector('.form__body');
          var formSuccess = form.querySelector('.form__success');
          if (formBody) formBody.style.display = 'none';
          if (formSuccess) formSuccess.style.display = 'block';
        } else {
          showFormFeedback(form, 'error', result.mensaje || (idioma === 'en' ? 'Error sending message.' : 'Error al enviar el mensaje.'));
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = textoOriginal;
          }
          // If CSRF error, token was already refreshed above
        }
      } catch(err) {
        console.error('Error form:', err);
        showFormFeedback(form, 'error', idioma === 'en' ? 'Connection error. Please try again.' : 'Error de conexión. Inténtalo de nuevo.');
        if (submitBtn) {
          submitBtn.disabled = false;
          submitBtn.textContent = textoOriginal;
        }
      }
    });
  }

  /* --- Form feedback helper --- */
  function showFormFeedback(form, tipo, mensaje) {
    var feedback = form.querySelector('.form-feedback');
    if (!feedback) {
      feedback = document.createElement('div');
      feedback.className = 'form-feedback';
      var body = form.querySelector('.form__body');
      if (body) body.appendChild(feedback);
      else form.appendChild(feedback);
    }
    feedback.className = 'form-feedback form-feedback--' + tipo;
    feedback.textContent = mensaje;
    feedback.style.display = 'block';
    feedback.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    if (tipo === 'success') {
      setTimeout(function() { feedback.style.display = 'none'; }, 6000);
    }
  }

  function showError(field, show) {
    if (!field) return;
    var group = field.closest('.form__group') || field.closest('.form__checkbox');
    if (!group) return;
    var error = group.querySelector('.form__error');
    if (error) error.style.display = show ? 'block' : 'none';
  }

  function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }

  /* --- Newsletter form --- */
  function initNewsletter() {
    document.querySelectorAll('[data-track="newsletter"]').forEach(function(form) {
      // Rellenar timestamp anti-bot al cargar el formulario
      var ts = form.querySelector('input[name="form_time"]');
      if (ts && !ts.value) ts.value = Math.floor(Date.now() / 1000);

      form.addEventListener('submit', function(e) {
        e.preventDefault();
        var emailInput = this.querySelector('input[type="email"]');
        var privacy = this.querySelector('[name="newsletter-privacy"]');
        var nameInput = this.querySelector('input[name="newsletter-name"]');
        if (!emailInput || !emailInput.value.trim() || !isValidEmail(emailInput.value)) {
          emailInput.style.borderColor = '#dc2626';
          return;
        }
        if (privacy && !privacy.checked) {
          privacy.parentElement.style.color = '#dc2626';
          return;
        }

        var idioma = document.documentElement.lang || 'es';
        var thisForm = this;
        var btn = thisForm.querySelector('[type="submit"]');
        var textoOriginal = btn ? btn.textContent : '';
        if (btn) { btn.disabled = true; btn.textContent = idioma === 'en' ? 'Sending...' : 'Enviando...'; }

        var formData = new FormData();
        formData.append('email', emailInput.value.trim());
        formData.append('nombre', nameInput ? nameInput.value.trim() : '');
        formData.append('idioma', idioma);
        formData.append('consentimiento_rgpd', '1');
        formData.append('website', '');
        // Anti-bot: timestamp del formulario (mínimo 3s para considerar humano)
        var formTimeField = thisForm.querySelector('input[name="form_time"]');
        if (formTimeField && formTimeField.value) {
          formData.append('form_time', formTimeField.value);
        }
        // Cloudflare Turnstile (si está configurado)
        var turnstileToken = thisForm.querySelector('input[name="cf_turnstile_response"]');
        if (turnstileToken && turnstileToken.value) {
          formData.append('cf_turnstile_response', turnstileToken.value);
        }

        fetch('/newsletter/suscribir.php', {
          method: 'POST',
          body: formData
        })
          .then(function(res) { return res.json(); })
          .then(function(result) {
            if (result.ok) {
              // Hide form, show success message
              thisForm.style.display = 'none';
              var container = thisForm.closest('.newsletter') || thisForm.parentElement;
              var successEl = document.createElement('div');
              successEl.className = 'newsletter-success';
              successEl.innerHTML = '<div class="newsletter-success__inner">'
                + '<span class="newsletter-success__icon">&#10003;</span>'
                + '<p class="newsletter-success__titulo">' + (idioma === 'en' ? 'Thank you for subscribing!' : '¡Gracias por suscribirte!') + '</p>'
                + '<p class="newsletter-success__texto">' + (result.mensaje || (idioma === 'en' ? 'Check your inbox to confirm.' : 'Revisa tu correo para confirmar.')) + '</p>'
                + '</div>';
              container.appendChild(successEl);
            } else {
              // Show error inline
              var errorEl = thisForm.querySelector('.newsletter-inline-error');
              if (!errorEl) {
                errorEl = document.createElement('div');
                errorEl.className = 'newsletter-inline-error form-feedback form-feedback--error';
                thisForm.appendChild(errorEl);
              }
              errorEl.textContent = result.mensaje || (idioma === 'en' ? 'Error. Try again.' : 'Error. Inténtalo de nuevo.');
              errorEl.style.display = 'block';
              if (btn) { btn.disabled = false; btn.textContent = textoOriginal; }
            }
          })
          .catch(function(err) {
            console.error('Error newsletter:', err);
            if (btn) { btn.disabled = false; btn.textContent = textoOriginal; }
          });
      });
    });
  }

  /* --- Smooth scroll for anchor links --- */
  function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(function(link) {
      link.addEventListener('click', function(e) {
        var id = this.getAttribute('href');
        if (id === '#') return;
        var target = document.querySelector(id);
        if (target) {
          e.preventDefault();
          target.scrollIntoView({ behavior: 'smooth' });
        }
      });
    });
  }

  /* --- Hero particles (simple geometric dots) --- */
  function initHeroParticles() {
    var canvas = document.getElementById('hero-canvas');
    if (!canvas) return;

    var ctx = canvas.getContext('2d');
    var particles = [];
    var particleCount = 50;
    var connectionDistance = 120;

    function resize() {
      canvas.width = canvas.offsetWidth;
      canvas.height = canvas.offsetHeight;
    }

    function createParticle() {
      return {
        x: Math.random() * canvas.width,
        y: Math.random() * canvas.height,
        vx: (Math.random() - 0.5) * 0.4,
        vy: (Math.random() - 0.5) * 0.4,
        radius: Math.random() * 2 + 1
      };
    }

    function init() {
      resize();
      particles = [];
      for (var i = 0; i < particleCount; i++) {
        particles.push(createParticle());
      }
    }

    function draw() {
      ctx.clearRect(0, 0, canvas.width, canvas.height);

      // Draw connections
      for (var i = 0; i < particles.length; i++) {
        for (var j = i + 1; j < particles.length; j++) {
          var dx = particles[i].x - particles[j].x;
          var dy = particles[i].y - particles[j].y;
          var dist = Math.sqrt(dx * dx + dy * dy);
          if (dist < connectionDistance) {
            var opacity = 1 - dist / connectionDistance;
            ctx.strokeStyle = 'rgba(56, 189, 248, ' + (opacity * 0.15) + ')';
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(particles[i].x, particles[i].y);
            ctx.lineTo(particles[j].x, particles[j].y);
            ctx.stroke();
          }
        }
      }

      // Draw particles
      for (var k = 0; k < particles.length; k++) {
        var p = particles[k];
        ctx.fillStyle = 'rgba(56, 189, 248, 0.4)';
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.radius, 0, Math.PI * 2);
        ctx.fill();

        // Move
        p.x += p.vx;
        p.y += p.vy;

        // Bounce
        if (p.x < 0 || p.x > canvas.width) p.vx *= -1;
        if (p.y < 0 || p.y > canvas.height) p.vy *= -1;
      }

      requestAnimationFrame(draw);
    }

    window.addEventListener('resize', function() {
      resize();
    });

    init();
    draw();
  }

  /* --- Floating CTA bar (show after scrolling past hero) --- */
  function initFloatingCTA() {
    var cta = document.getElementById('floating-cta');
    var contact = document.getElementById('contact');
    if (!cta) return;

    function checkScroll() {
      var scrollY = window.scrollY;
      var heroHeight = window.innerHeight;
      var contactTop = contact ? contact.getBoundingClientRect().top + scrollY - window.innerHeight : Infinity;

      if (scrollY > heroHeight && scrollY < contactTop) {
        cta.classList.add('floating-cta--visible');
        cta.setAttribute('aria-hidden', 'false');
      } else {
        cta.classList.remove('floating-cta--visible');
        cta.setAttribute('aria-hidden', 'true');
      }
    }

    window.addEventListener('scroll', checkScroll, { passive: true });
  }

  /* --- Curso slider (galería de fotos) --- */
  function initCursoSlider() {
    document.querySelectorAll('.curso-slider').forEach(function(slider) {
      var track = slider.querySelector('.slider-track');
      var slides = slider.querySelectorAll('.slide');
      var dots = slider.querySelectorAll('.dot');
      var total = slides.length;
      if (!track || total <= 1) return;

      var current = 0;
      var timer = null;
      var AUTOPLAY_MS = 5000;

      function goTo(n) {
        current = ((n % total) + total) % total;
        track.style.transform = 'translateX(-' + (current * 100) + '%)';
        dots.forEach(function(d, i) { d.classList.toggle('active', i === current); });
      }
      function startAuto() {
        stopAuto();
        timer = setInterval(function() { goTo(current + 1); }, AUTOPLAY_MS);
      }
      function stopAuto() { if (timer) { clearInterval(timer); timer = null; } }

      var prev = slider.querySelector('.slider-prev');
      var next = slider.querySelector('.slider-next');
      if (prev) prev.addEventListener('click', function() { stopAuto(); goTo(current - 1); startAuto(); });
      if (next) next.addEventListener('click', function() { stopAuto(); goTo(current + 1); startAuto(); });
      dots.forEach(function(d, i) {
        d.addEventListener('click', function() { stopAuto(); goTo(i); startAuto(); });
      });

      // Swipe táctil
      var touchX = 0;
      slider.addEventListener('touchstart', function(e) {
        touchX = e.touches[0].clientX;
        stopAuto();
      }, { passive: true });
      slider.addEventListener('touchend', function(e) {
        var diff = touchX - e.changedTouches[0].clientX;
        if (Math.abs(diff) > 40) goTo(current + (diff > 0 ? 1 : -1));
        startAuto();
      });

      // Pausar al hacer hover
      slider.addEventListener('mouseenter', stopAuto);
      slider.addEventListener('mouseleave', startAuto);

      // Pausar si la pestaña no es visible
      document.addEventListener('visibilitychange', function() {
        if (document.hidden) stopAuto(); else startAuto();
      });

      startAuto();
    });
  }

  /* --- Init everything on DOMContentLoaded --- */
  document.addEventListener('DOMContentLoaded', function() {
    initReveal();
    initFormTabs();
    initContactForm();
    initNewsletter();
    initSmoothScroll();
    initHeroParticles();
    initFloatingCTA();
    initCursoSlider();
  });

})();
