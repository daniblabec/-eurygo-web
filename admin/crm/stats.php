<?php
/**
 * CRM EuryGo — Estadísticas
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requiere_login();

$db = get_db();

// Embudo por pata
function funnel($db, $pata) {
    $stmt = $db->prepare("
        SELECT estado, COUNT(*) AS n FROM crm_contactos WHERE pata = ? GROUP BY estado
    ");
    $stmt->execute([$pata]);
    $rows = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    $sin = (int)($rows['sin_contactar'] ?? 0);
    $contactados = (int)($rows['contactado_tel'] ?? 0) + (int)($rows['contactado_email'] ?? 0);
    $reuniones = (int)($rows['reunion_programada'] ?? 0) + (int)($rows['reunion_realizada'] ?? 0);
    $propuestas = (int)($rows['propuesta_enviada'] ?? 0) + (int)($rows['negociacion'] ?? 0);
    $clientes = (int)($rows['cliente'] ?? 0);
    $total = array_sum($rows);
    return compact('sin','contactados','reuniones','propuestas','clientes','total');
}

$f_centros = funnel($db, 'centros');
$f_agencias = funnel($db, 'agencias');

// Conversiones
$conv_contacto_reunion = $f_centros['contactados'] > 0 ? round($f_centros['reuniones'] / max($f_centros['contactados'],1) * 100) : 0;
$conv_reunion_propuesta = $f_centros['reuniones'] > 0 ? round($f_centros['propuestas'] / max($f_centros['reuniones'],1) * 100) : 0;
$conv_propuesta_cliente = $f_centros['propuestas'] > 0 ? round($f_centros['clientes'] / max($f_centros['propuestas'],1) * 100) : 0;

// Actividad por semana (últimas 8 semanas)
$actividad_semanas = $db->query("
    SELECT YEARWEEK(fecha, 1) AS sem, tipo, COUNT(*) AS n
    FROM crm_actividad
    WHERE fecha >= DATE_SUB(CURDATE(), INTERVAL 8 WEEK)
    GROUP BY sem, tipo
    ORDER BY sem
")->fetchAll();

// Agrupar
$semanas_data = [];
foreach ($actividad_semanas as $r) {
    $semanas_data[$r['sem']][$r['tipo']] = (int)$r['n'];
}

// Centros por CCAA
$ccaa_stats = $db->query("
    SELECT comunidad,
           COUNT(*) AS total,
           SUM(estado='sin_contactar') AS sin_contactar,
           SUM(estado IN ('contactado_tel','contactado_email','reunion_programada','reunion_realizada','propuesta_enviada','negociacion')) AS en_proceso,
           SUM(estado='cliente') AS clientes
    FROM crm_contactos
    WHERE pata = 'centros' AND comunidad IS NOT NULL
    GROUP BY comunidad
    ORDER BY total DESC
")->fetchAll();

// Agencias por país y aeropuerto
$ag_stats = $db->query("
    SELECT pais, COUNT(*) AS total, aeropuerto_cercano,
           GROUP_CONCAT(nombre_centro SEPARATOR ', ') AS agencias
    FROM crm_contactos
    WHERE pata = 'agencias'
    GROUP BY pais, aeropuerto_cercano
    ORDER BY pais
")->fetchAll();

// Etapa + titularidad — barras apiladas (centros)
$etapa_titu = [];
try {
    $rs = $db->query("
        SELECT etapa_educativa, titularidad, COUNT(*) AS n
        FROM crm_contactos
        WHERE pata = 'centros' AND etapa_educativa IS NOT NULL
        GROUP BY etapa_educativa, titularidad
    ")->fetchAll();
    foreach ($rs as $r) {
        $etapa_titu[$r['etapa_educativa']][$r['titularidad'] ?: 'nd'] = (int)$r['n'];
    }
} catch (PDOException $e) {}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Estadísticas · CRM EuryGo</title>
  <link rel="stylesheet" href="/admin/assets/admin.css">
  <link rel="stylesheet" href="/admin/crm/assets/crm.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body class="admin-body">
  <?php include __DIR__ . '/../partials/sidebar.php'; ?>
  <div class="admin-main">
    <?php include __DIR__ . '/../partials/topbar.php'; ?>
    <div class="admin-content" style="max-width:1200px;">
      <h1>Estadísticas CRM</h1>

      <!-- EMBUDOS -->
      <div class="ficha-section">
        <h2>Embudo de captación</h2>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:2rem;">
          <div>
            <h3 style="font-size:0.95rem; margin-bottom:0.75rem;">🏫 Centros escolares (<?= $f_centros['total'] ?> total)</h3>
            <?php
              $max_c = max($f_centros['sin'], $f_centros['contactados'], $f_centros['reuniones'], $f_centros['propuestas'], $f_centros['clientes'], 1);
              $steps = [
                ['Sin contactar', $f_centros['sin'], '#9ca3af'],
                ['Contactados', $f_centros['contactados'], '#3b82f6'],
                ['Reuniones', $f_centros['reuniones'], '#f59e0b'],
                ['Propuestas/Negociación', $f_centros['propuestas'], '#8b5cf6'],
                ['Clientes', $f_centros['clientes'], '#16a34a'],
              ];
            ?>
            <div class="funnel">
              <?php foreach ($steps as [$lbl, $val, $col]):
                $w = max(20, round($val / $max_c * 100));
              ?>
              <div class="funnel-step" style="background:<?= $col ?>; width:<?= $w ?>%;"><?= $lbl ?>: <?= $val ?></div>
              <?php endforeach; ?>
            </div>
          </div>

          <div>
            <h3 style="font-size:0.95rem; margin-bottom:0.75rem;">🌍 Agencias europeas (<?= $f_agencias['total'] ?> total)</h3>
            <?php
              $max_a = max($f_agencias['sin'], $f_agencias['contactados'], $f_agencias['reuniones'], $f_agencias['propuestas'], $f_agencias['clientes'], 1);
              $steps_a = [
                ['Sin contactar', $f_agencias['sin'], '#9ca3af'],
                ['Contactadas', $f_agencias['contactados'], '#3b82f6'],
                ['Reuniones', $f_agencias['reuniones'], '#f59e0b'],
                ['Propuestas/Negociación', $f_agencias['propuestas'], '#8b5cf6'],
                ['Partners', $f_agencias['clientes'], '#16a34a'],
              ];
            ?>
            <div class="funnel">
              <?php foreach ($steps_a as [$lbl, $val, $col]):
                $w = max(20, round($val / $max_a * 100));
              ?>
              <div class="funnel-step" style="background:<?= $col ?>; width:<?= $w ?>%;"><?= $lbl ?>: <?= $val ?></div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- TASAS DE CONVERSIÓN -->
      <div class="ficha-section">
        <h2>Tasas de conversión (centros)</h2>
        <div class="stats-grid">
          <div class="stat-card">
            <div class="stat-card__number"><?= $conv_contacto_reunion ?>%</div>
            <div class="stat-card__label">Contacto → Reunión</div>
          </div>
          <div class="stat-card">
            <div class="stat-card__number"><?= $conv_reunion_propuesta ?>%</div>
            <div class="stat-card__label">Reunión → Propuesta</div>
          </div>
          <div class="stat-card">
            <div class="stat-card__number"><?= $conv_propuesta_cliente ?>%</div>
            <div class="stat-card__label">Propuesta → Cliente</div>
          </div>
        </div>
      </div>

      <!-- ACTIVIDAD SEMANAL -->
      <div class="ficha-section">
        <h2>Actividad — últimas 8 semanas</h2>
        <div class="chart-container" style="max-height:300px;">
          <canvas id="chart-actividad"></canvas>
        </div>
      </div>

      <!-- ETAPA EDUCATIVA + TITULARIDAD (barras apiladas) -->
<?php if (!empty($etapa_titu)): ?>
      <div class="ficha-section">
        <h2>Distribución por etapa educativa y titularidad</h2>
        <div class="chart-container" style="max-height:380px;">
          <canvas id="chart-etapas"></canvas>
        </div>
        <p style="margin-top:1rem; padding:0.75rem 1rem; background:#dbeafe; border-left:3px solid #003399; border-radius:4px; font-size:0.9rem;">
          <strong>EuryGo prioriza:</strong> Secundaria + Bachillerato + FP — son los centros con
          mayor presupuesto Erasmus+ y más movilidades de profesorado.
        </p>
      </div>
<?php endif; ?>

      <!-- POR CCAA -->
      <div class="ficha-section">
        <h2>Centros por comunidad autónoma</h2>
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr><th>Comunidad</th><th>Total</th><th>Sin contactar</th><th>En proceso</th><th>Clientes</th></tr>
            </thead>
            <tbody>
<?php foreach ($ccaa_stats as $cc): ?>
              <tr>
                <td><strong><?= htmlspecialchars($cc['comunidad']) ?></strong></td>
                <td><?= $cc['total'] ?></td>
                <td style="color:#6b7280;"><?= $cc['sin_contactar'] ?></td>
                <td style="color:#3b82f6;"><?= $cc['en_proceso'] ?></td>
                <td style="color:#16a34a; font-weight:700;"><?= $cc['clientes'] ?></td>
              </tr>
<?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- AGENCIAS POR PAÍS Y AEROPUERTO -->
      <div class="ficha-section">
        <h2>Agencias por país y aeropuerto</h2>
        <div class="admin-table-wrap">
          <table class="admin-table">
            <thead>
              <tr><th>País</th><th>Aeropuerto</th><th>Nº</th><th>Agencias</th></tr>
            </thead>
            <tbody>
<?php if (empty($ag_stats)): ?>
              <tr><td colspan="4" style="text-align:center; color:#999;">No hay agencias aún.</td></tr>
<?php endif; ?>
<?php foreach ($ag_stats as $ag): ?>
              <tr>
                <td><strong><?= htmlspecialchars($ag['pais'] ?: '—') ?></strong></td>
                <td><?= htmlspecialchars($ag['aeropuerto_cercano'] ?: '—') ?></td>
                <td><?= $ag['total'] ?></td>
                <td style="font-size:0.85rem;"><?= htmlspecialchars(mb_substr($ag['agencias'], 0, 100)) ?>…</td>
              </tr>
<?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
  </div>

<script>
// Activity chart
const semanas = <?= json_encode(array_keys($semanas_data)) ?>;
const semanasData = <?= json_encode($semanas_data) ?>;

const tipos = ['llamada', 'email', 'reunion_presencial', 'videollamada', 'whatsapp', 'linkedin', 'propuesta'];
const colores = {
  llamada: '#3b82f6', email: '#10b981', reunion_presencial: '#f59e0b',
  videollamada: '#8b5cf6', whatsapp: '#25d366', linkedin: '#0077b5', propuesta: '#ef4444',
};

const labels = semanas.map(s => 'Sem ' + String(s).slice(-2));
const datasets = tipos.map(tipo => ({
  label: tipo.charAt(0).toUpperCase() + tipo.slice(1).replace('_',' '),
  data: semanas.map(s => semanasData[s]?.[tipo] || 0),
  backgroundColor: colores[tipo],
}));

new Chart(document.getElementById('chart-actividad'), {
  type: 'bar',
  data: { labels, datasets },
  options: {
    responsive: true, maintainAspectRatio: false,
    scales: { x: { stacked: true }, y: { stacked: true, beginAtZero: true } },
    plugins: { legend: { position: 'bottom' } },
  },
});

// Chart etapas + titularidad (barras apiladas)
const etapaTitu = <?= json_encode($etapa_titu) ?>;
const elEtapas = document.getElementById('chart-etapas');
if (elEtapas && Object.keys(etapaTitu).length > 0) {
  const ordenEtapas = ['secundaria','bachillerato','fp_superior','fp_medio','primaria','infantil','infantil_0_3','adultos','especial','otro'];
  const etiquetas = {
    secundaria: '⭐ Secundaria', bachillerato: 'Bachillerato',
    fp_superior: 'FP Superior', fp_medio: 'FP Medio',
    primaria: 'Primaria', infantil: 'Infantil', infantil_0_3: 'Infantil 0-3',
    adultos: 'Adultos', especial: 'Especial', otro: 'Otro',
  };
  const labelsEt = ordenEtapas.filter(e => etapaTitu[e]).map(e => etiquetas[e]);
  const etapasFiltered = ordenEtapas.filter(e => etapaTitu[e]);
  const dsPub  = etapasFiltered.map(e => etapaTitu[e]?.publico || 0);
  const dsConc = etapasFiltered.map(e => etapaTitu[e]?.concertado || 0);
  const dsPriv = etapasFiltered.map(e => etapaTitu[e]?.privado || 0);
  const dsNd   = etapasFiltered.map(e => etapaTitu[e]?.nd || 0);

  new Chart(elEtapas, {
    type: 'bar',
    data: {
      labels: labelsEt,
      datasets: [
        { label: '🏛️ Pública',     data: dsPub,  backgroundColor: '#003399' },
        { label: '🤝 Concertada',  data: dsConc, backgroundColor: '#fd7e14' },
        { label: '🏫 Privada',     data: dsPriv, backgroundColor: '#dc3545' },
        { label: '— No definida',  data: dsNd,   backgroundColor: '#cccccc' },
      ],
    },
    options: {
      responsive: true, maintainAspectRatio: false,
      scales: {
        x: { stacked: true },
        y: { stacked: true, beginAtZero: true, title: { display: true, text: 'Nº de centros' } },
      },
      plugins: { legend: { position: 'bottom' } },
    },
  });
}
</script>
</body>
</html>
