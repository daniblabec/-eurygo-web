<?php
/**
 * Bloque "What is Included" — partial reutilizable para detalle de curso.
 *
 * Se incluye entre la descripción del curso y los bloques OPTIONAL.
 * Requiere que la página padre defina la variable $idioma ('es' | 'en').
 * Si no está definida, asume 'en'.
 */

$wi_lang = (isset($idioma) && $idioma === 'es') ? 'es' : 'en';

$wi_t = $wi_lang === 'es' ? [
    'heading'      => 'Qué incluye',
    'erasmus_link' => 'ayudas KA1 Erasmus+',
    'items' => [
        ['icon' => '💬', 'title' => 'Soporte continuo',
         'body' => 'Asistencia por chat durante toda la jornada del curso. Siempre disponibles para lo que necesites.'],
        ['icon' => '🇪🇺', 'title' => 'Financiable con Erasmus+',
         'body_pre'  => 'Totalmente elegible para las ',
         'body_post' => '. Nuestros cursos cumplen todos los requisitos de SEPIE y EACEA.'],
        ['icon' => '🔄', 'title' => 'Flexibilidad garantizada',
         'body' => 'Cambios fáciles con mínimas restricciones. Reprograma o cambia de curso sin complicaciones.'],
        ['icon' => '🌍', 'title' => 'Experiencia 360°',
         'body' => 'Desde los coffee breaks hasta las visitas culturales — cada detalle está cuidado.'],
        ['icon' => '📜', 'title' => 'Certificado de asistencia',
         'body' => '5 horas al día. 25 horas en cursos de 1 semana, 50 horas en cursos de 2 semanas.'],
    ],
] : [
    'heading'      => 'What is Included',
    'erasmus_link' => 'KA1 Erasmus+ grants',
    'items' => [
        ['icon' => '💬', 'title' => 'Unmatched Support',
         'body' => 'Full-day chat assistance throughout the course. We are always available for anything you need.'],
        ['icon' => '🇪🇺', 'title' => 'Erasmus+ Fundable',
         'body_pre'  => 'Fully eligible for ',
         'body_post' => '. Our courses meet all SEPIE and EACEA requirements.'],
        ['icon' => '🔄', 'title' => 'Flexibility Guaranteed',
         'body' => 'Easy changes with minimal restrictions. Reschedule or swap courses with no hassle.'],
        ['icon' => '🌍', 'title' => '360° Experience',
         'body' => 'From coffee breaks to cultural visits — every detail is taken care of.'],
        ['icon' => '📜', 'title' => 'Certificate of Attendance',
         'body' => '5 hours per day. 25 hours for 1-week courses, 50 hours for 2-week courses.'],
    ],
];
?>
<section class="what-included" aria-label="<?= htmlspecialchars($wi_t['heading']) ?>">
  <h2><?= htmlspecialchars($wi_t['heading']) ?></h2>
  <div class="included-grid">
<?php foreach ($wi_t['items'] as $item): ?>
    <div class="included-item">
      <span class="included-icon" aria-hidden="true"><?= $item['icon'] ?></span>
      <h3><?= htmlspecialchars($item['title']) ?></h3>
      <p>
<?php if (isset($item['body_pre'])): ?>
<?= htmlspecialchars($item['body_pre']) ?><a href="https://erasmus-plus.ec.europa.eu/" target="_blank" rel="noopener"><?= htmlspecialchars($wi_t['erasmus_link']) ?></a><?= htmlspecialchars($item['body_post']) ?>
<?php else: ?>
<?= htmlspecialchars($item['body']) ?>
<?php endif; ?>
      </p>
    </div>
<?php endforeach; ?>
  </div>
</section>
