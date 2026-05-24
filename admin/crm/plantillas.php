<?php
/**
 * CRM EuryGo — Plantillas de contacto
 */
require_once __DIR__ . '/../../includes/auth.php';
requiere_login();

$pata = $_GET['pata'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Plantillas · CRM EuryGo</title>
  <link rel="stylesheet" href="/admin/assets/admin.css">
  <link rel="stylesheet" href="/admin/crm/assets/crm.css">
</head>
<body class="admin-body">
  <?php include __DIR__ . '/../partials/sidebar.php'; ?>
  <div class="admin-main">
    <?php include __DIR__ . '/../partials/topbar.php'; ?>
    <div class="admin-content" style="max-width:900px;">
      <h1>Plantillas de contacto</h1>
      <p style="color:#666; margin-bottom:1.5rem;">Textos predefinidos para contactar con centros y agencias. Pulsa "Copiar" y pega en email/WhatsApp/teléfono.</p>

      <div class="admin-tabs" style="margin-bottom:1.5rem;">
        <a href="?pata=centros" class="admin-tabs__item <?= $pata !== 'agencias' ? 'active' : '' ?>">🏫 Centros escolares</a>
        <a href="?pata=agencias" class="admin-tabs__item <?= $pata === 'agencias' ? 'active' : '' ?>">🌍 Agencias europeas</a>
      </div>

<?php if ($pata !== 'agencias'): ?>

      <!-- ─── Centros ─── -->
      <div class="plantilla-card">
        <h3>📞 Guion de llamada — Primer contacto</h3>
        <pre class="plantilla-card__texto" id="p1">Buenos días, ¿podría hablar con el/la director/a o coordinador/a Erasmus+?

Hola, soy Daniel Blanco de EuryGo, una asesoría Erasmus+ de Jerez de la Frontera. Le llamo porque vuestro centro ha sido admitido en la convocatoria Erasmus+ 2026 y me gustaría saber si puedo ayudaros con el proyecto.

¿Tiene 2 minutos ahora o prefiere que le llame en otro momento?</pre>
        <div class="plantilla-card__actions">
          <button class="btn-admin btn-admin--outline btn-admin--sm" onclick="copiar('p1', this)">📋 Copiar</button>
        </div>
      </div>

      <div class="plantilla-card">
        <h3>✉️ Email — Centro admitido en Convocatoria 2026</h3>
        <p class="plantilla-card__asunto"><strong>Asunto:</strong> Erasmus+ 2026 · EuryGo puede acompañar a [nombre del centro]</p>
        <pre class="plantilla-card__texto" id="p2">Estimado/a [nombre],

Me pongo en contacto con usted porque [nombre del centro] figura entre los centros admitidos en la convocatoria Erasmus+ 2026, lo que confirma que vuestro proyecto de internacionalización está en marcha.

Soy Daniel Blanco, fundador de EuryGo, una asesoría especializada en Erasmus+ con sede en Jerez de la Frontera. Llevamos 7 años trabajando dentro del sistema — como coordinadores, no como consultores externos — y acompañamos a centros como el suyo en todo el proceso: acreditación KA121/KA122, Beneficiary Module, Ulises e informes para el SEPIE.

¿Tendría 20 minutos esta semana para una llamada? Me gustaría entender en qué punto está vuestro proyecto y ver si podemos ser de ayuda.

Quedo a su disposición.

Daniel Blanco
EuryGo · www.eurygo.com · info@eurygo.com</pre>
        <div class="plantilla-card__actions">
          <button class="btn-admin btn-admin--outline btn-admin--sm" onclick="copiar('p2', this)">📋 Copiar texto</button>
          <button class="btn-admin btn-admin--outline btn-admin--sm" onclick="copiarAsunto('Erasmus+ 2026 · EuryGo puede acompañar a [nombre del centro]', this)">📋 Copiar asunto</button>
        </div>
      </div>

      <div class="plantilla-card">
        <h3>✉️ Email — Seguimiento tras llamada</h3>
        <p class="plantilla-card__asunto"><strong>Asunto:</strong> EuryGo · información Erasmus+ tal como hablamos</p>
        <pre class="plantilla-card__texto" id="p3">Estimado/a [nombre],

Tal como hemos hablado por teléfono, le adjunto la información sobre los servicios de EuryGo que más se ajustan al proyecto Erasmus+ de [nombre del centro]:

• Asesoría integral en acreditación KA121/KA122
• Acompañamiento en el Beneficiary Module y Ulises
• Preparación de la documentación para el SEPIE
• Coste cubierto por la partida de Apoyo Organizativo del proyecto

Quedo a su disposición para concertar una reunión telemática y revisar juntos vuestra situación.

Un cordial saludo,
Daniel Blanco
EuryGo · www.eurygo.com</pre>
        <div class="plantilla-card__actions">
          <button class="btn-admin btn-admin--outline btn-admin--sm" onclick="copiar('p3', this)">📋 Copiar</button>
        </div>
      </div>

      <div class="plantilla-card">
        <h3>💬 WhatsApp — Mensaje breve</h3>
        <pre class="plantilla-card__texto" id="p4">Hola [nombre], soy Daniel de EuryGo (asesoría Erasmus+ de Jerez). Te escribo por el proyecto Erasmus+ 2026 de [centro]. ¿Tienes 5 minutos esta semana para una llamada rápida? Gracias 🙏</pre>
        <div class="plantilla-card__actions">
          <button class="btn-admin btn-admin--outline btn-admin--sm" onclick="copiar('p4', this)">📋 Copiar</button>
        </div>
      </div>

<?php else: ?>

      <!-- ─── Agencias ─── -->
      <div class="plantilla-card">
        <h3>✉️ Email — First contact (English)</h3>
        <p class="plantilla-card__asunto"><strong>Subject:</strong> EuryGo · KA1 Partner in Jerez, Andalusia (Spain)</p>
        <pre class="plantilla-card__texto" id="a1">Dear [name],

I am reaching out because EuryGo offers a unique proposition for mobility agencies looking for a reliable KA1 partner in southern Spain.

We are based in Jerez de la Frontera, Cádiz — 30 minutes from Jerez airport (XRY), 1 hour from Seville (SVQ), 1.5 hours from Málaga (AGP). We offer:

• Certified KA1 training courses (20 hours, €480/participant)
• Job shadowing programmes in our network of Erasmus+ accredited schools in Andalusia
• Cultural programme included: equestrian show, sherry wineries, Jerez old town
• Bilingual ES/EN professional communication

I would love to explore whether there is a fit for collaboration. Would you be available for a brief call or video meeting in the coming days?

Best regards,
Daniel Blanco
EuryGo · www.eurygo.com · info@eurygo.com</pre>
        <div class="plantilla-card__actions">
          <button class="btn-admin btn-admin--outline btn-admin--sm" onclick="copiar('a1', this)">📋 Copy</button>
        </div>
      </div>

      <div class="plantilla-card">
        <h3>💼 LinkedIn — Connection request (English)</h3>
        <pre class="plantilla-card__texto" id="a2">Hi [name], I'm Daniel from EuryGo, a KA1 training provider in Jerez, Andalusia. We deliver certified courses for European teachers and coordinate job shadowing in local schools. Would love to connect and explore potential collaboration with your agency.</pre>
        <div class="plantilla-card__actions">
          <button class="btn-admin btn-admin--outline btn-admin--sm" onclick="copiar('a2', this)">📋 Copy</button>
        </div>
      </div>

      <div class="plantilla-card">
        <h3>✉️ Email — Follow-up after first call</h3>
        <p class="plantilla-card__asunto"><strong>Subject:</strong> EuryGo · Catalogue & next steps</p>
        <pre class="plantilla-card__texto" id="a3">Dear [name],

Thank you for your time today. As discussed, please find attached our KA1 course catalogue and our Erasmus+ accredited schools network for job shadowing.

Highlights:
• 6 KA1 courses (20h each, €480/participant) — flexible dates
• Min. group size: 6 participants. Tailored programmes for groups +10
• Network of 12+ accredited schools in Cádiz, Sevilla and Málaga
• Cultural programme: Real Escuela del Arte Ecuestre, sherry wineries, Cádiz coast

Looking forward to your feedback. Happy to schedule a follow-up to discuss specific groups for your 2026/27 calendar.

Kind regards,
Daniel Blanco
EuryGo · www.eurygo.com</pre>
        <div class="plantilla-card__actions">
          <button class="btn-admin btn-admin--outline btn-admin--sm" onclick="copiar('a3', this)">📋 Copy</button>
        </div>
      </div>

<?php endif; ?>

    </div>
  </div>
<script>
function copiar(id, btn) {
  const txt = document.getElementById(id).innerText;
  navigator.clipboard.writeText(txt).then(() => {
    const orig = btn.textContent;
    btn.textContent = '✓ Copiado';
    btn.classList.add('btn-copied');
    setTimeout(() => { btn.textContent = orig; btn.classList.remove('btn-copied'); }, 2000);
  });
}
function copiarAsunto(txt, btn) {
  navigator.clipboard.writeText(txt).then(() => {
    const orig = btn.textContent;
    btn.textContent = '✓ Copiado';
    btn.classList.add('btn-copied');
    setTimeout(() => { btn.textContent = orig; btn.classList.remove('btn-copied'); }, 2000);
  });
}
</script>
</body>
</html>
