<?php
/**
 * CRM EuryGo — Importar centros desde JSON (extraído del PDF SEPIE)
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requiere_login();

$db = get_db();
$msg = '';
$stats = null;

// Procesar importación
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verificar_csrf()) {
    $jsonFile = __DIR__ . '/centros_sepie.json';
    if (!file_exists($jsonFile)) {
        $msg = 'error:No se encontró el archivo centros_sepie.json. Ejecuta primero el extractor.';
    } else {
        $data = json_decode(file_get_contents($jsonFile), true);
        $centros = $data['centros'] ?? [];
        $municipiosNR = $data['municipiosNoReconocidos'] ?? [];

        $stmt = $db->prepare("
            INSERT INTO crm_contactos
            (pata, nombre_centro, tipo_accion, num_solicitud, num_proyecto,
             tipo_centro, titularidad, municipio, provincia, comunidad,
             lat, lng, distancia_jerez_km, tipo_reunion, prioridad,
             estado, fuente)
            VALUES
            ('centros', :nombre, :tipo_accion, :num_sol, :num_proy,
             :tipo_centro, :titularidad, :municipio, :provincia, :comunidad,
             :lat, :lng, :distancia, :tipo_reunion, :prioridad,
             'sin_contactar', 'sepie_2026')
            ON DUPLICATE KEY UPDATE
              nombre_centro = VALUES(nombre_centro),
              municipio = VALUES(municipio),
              provincia = VALUES(provincia),
              comunidad = VALUES(comunidad),
              lat = VALUES(lat),
              lng = VALUES(lng),
              distancia_jerez_km = VALUES(distancia_jerez_km),
              tipo_reunion = VALUES(tipo_reunion),
              prioridad = VALUES(prioridad)
        ");

        $insertados = 0;
        $actualizados = 0;
        $errores = 0;

        foreach ($centros as $c) {
            try {
                $stmt->execute([
                    ':nombre'       => $c['nombre_centro'],
                    ':tipo_accion'  => $c['tipo_accion'],
                    ':num_sol'      => $c['num_solicitud'],
                    ':num_proy'     => $c['num_proyecto'],
                    ':tipo_centro'  => $c['tipo_centro'],
                    ':titularidad'  => $c['titularidad'],
                    ':municipio'    => $c['municipio'],
                    ':provincia'    => $c['provincia'],
                    ':comunidad'    => $c['comunidad'],
                    ':lat'          => $c['lat'],
                    ':lng'          => $c['lng'],
                    ':distancia'    => $c['distancia_jerez_km'],
                    ':tipo_reunion' => $c['tipo_reunion'],
                    ':prioridad'    => $c['prioridad'],
                ]);
                if ($stmt->rowCount() === 1) $insertados++;
                elseif ($stmt->rowCount() === 2) $actualizados++;
            } catch (PDOException $e) {
                $errores++;
            }
        }

        $stats = [
            'total' => count($centros),
            'insertados' => $insertados,
            'actualizados' => $actualizados,
            'errores' => $errores,
            'ka121' => count(array_filter($centros, fn($c) => $c['tipo_accion'] === 'KA121-SCH')),
            'ka122' => count(array_filter($centros, fn($c) => $c['tipo_accion'] === 'KA122-SCH')),
            'alta' => count(array_filter($centros, fn($c) => $c['prioridad'] === 'alta')),
            'media' => count(array_filter($centros, fn($c) => $c['prioridad'] === 'media')),
            'baja' => count(array_filter($centros, fn($c) => $c['prioridad'] === 'baja')),
            'presencial' => count(array_filter($centros, fn($c) => $c['tipo_reunion'] === 'presencial')),
            'telematica' => count(array_filter($centros, fn($c) => $c['tipo_reunion'] === 'telematica')),
            'municipios_nr' => $municipiosNR,
        ];
        $msg = 'ok:Importación completada';
    }
}

// Check if JSON file exists
$jsonExists = file_exists(__DIR__ . '/centros_sepie.json');
$jsonData = null;
if ($jsonExists) {
    $jsonData = json_decode(file_get_contents(__DIR__ . '/centros_sepie.json'), true);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Importar SEPIE — CRM EuryGo</title>
  <link rel="stylesheet" href="/admin/assets/admin.css">
  <link rel="stylesheet" href="/admin/crm/assets/crm.css">
</head>
<body class="admin-body">
  <?php include __DIR__ . '/../partials/sidebar.php'; ?>
  <div class="admin-main">
    <?php include __DIR__ . '/../partials/topbar.php'; ?>
    <div class="admin-content">
      <h1>Importar centros del SEPIE</h1>

<?php if ($stats): ?>
      <div class="alert alert--success">
        <strong>Importación completada</strong>
      </div>
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-card__number"><?= $stats['total'] ?></div>
          <div class="stat-card__label">Total procesados</div>
        </div>
        <div class="stat-card">
          <div class="stat-card__number" style="color:#16a34a;"><?= $stats['insertados'] ?></div>
          <div class="stat-card__label">Insertados</div>
        </div>
        <div class="stat-card">
          <div class="stat-card__number" style="color:#D97706;"><?= $stats['actualizados'] ?></div>
          <div class="stat-card__label">Actualizados</div>
        </div>
        <div class="stat-card">
          <div class="stat-card__number" style="color:#dc2626;"><?= $stats['errores'] ?></div>
          <div class="stat-card__label">Errores</div>
        </div>
      </div>

      <div class="editor-section">
        <h2>Desglose</h2>
        <table class="admin-table" style="max-width:500px;">
          <tr><td>KA121-SCH (acreditación)</td><td><strong><?= $stats['ka121'] ?></strong></td></tr>
          <tr><td>KA122-SCH (corta duración)</td><td><strong><?= $stats['ka122'] ?></strong></td></tr>
          <tr><td colspan="2" style="border-top:2px solid #e2e8f0;"></td></tr>
          <tr><td>🔴 Prioridad alta (Andalucía)</td><td><strong><?= $stats['alta'] ?></strong></td></tr>
          <tr><td>🟡 Prioridad media</td><td><strong><?= $stats['media'] ?></strong></td></tr>
          <tr><td>🟢 Prioridad baja</td><td><strong><?= $stats['baja'] ?></strong></td></tr>
          <tr><td colspan="2" style="border-top:2px solid #e2e8f0;"></td></tr>
          <tr><td>🏢 Presencial (< 150 km)</td><td><strong><?= $stats['presencial'] ?></strong></td></tr>
          <tr><td>💻 Telemática (≥ 150 km)</td><td><strong><?= $stats['telematica'] ?></strong></td></tr>
        </table>

<?php if (!empty($stats['municipios_nr'])): ?>
        <h2 style="margin-top:1.5rem;">Municipios no reconocidos (<?= count($stats['municipios_nr']) ?>)</h2>
        <p style="font-size:0.85rem; color:#666; margin-bottom:0.5rem;">Estos municipios no pudieron geolocalizarse. Los centros se importaron igualmente pero sin coordenadas.</p>
        <div style="max-height:200px; overflow-y:auto; background:#f8f9fa; padding:1rem; border-radius:6px; font-size:0.8rem; column-count:3;">
          <?= htmlspecialchars(implode(' · ', $stats['municipios_nr'])) ?>
        </div>
<?php endif; ?>
      </div>

      <div class="quick-actions" style="margin-top:1.5rem;">
        <a href="/admin/crm/centros.php" class="btn-admin btn-admin--primary">Ver centros importados</a>
      </div>

<?php elseif (str_starts_with($msg, 'error:')): ?>
      <div class="alert alert--error"><?= htmlspecialchars(substr($msg, 6)) ?></div>
<?php endif; ?>

<?php if (!$stats): ?>
      <div class="editor-section">
        <h2>Instrucciones</h2>
        <ol style="font-size:0.9rem; line-height:1.8; padding-left:1.5rem;">
          <li>Descarga el PDF del SEPIE con los listados provisionales de la Convocatoria 2026.</li>
          <li>Ejecuta el extractor Node.js:
            <code style="background:#f1f5f9; padding:2px 6px; border-radius:4px;">node admin/crm/extract-sepie.js "ruta/al/pdf.pdf"</code>
          </li>
          <li>El extractor genera <code>admin/crm/centros_sepie.json</code> con todos los centros.</li>
          <li>Pulsa el botón de importar para cargarlos en la base de datos.</li>
        </ol>
      </div>

<?php if ($jsonExists && $jsonData): ?>
      <div class="alert alert--info">
        Archivo <strong>centros_sepie.json</strong> encontrado con <strong><?= count($jsonData['centros'] ?? []) ?></strong> centros listos para importar.
      </div>
      <form method="POST">
        <?= campo_csrf() ?>
        <button type="submit" class="btn-admin btn-admin--primary" onclick="return confirm('¿Importar <?= count($jsonData['centros'] ?? []) ?> centros a la base de datos?')">
          Importar <?= count($jsonData['centros'] ?? []) ?> centros
        </button>
      </form>
<?php else: ?>
      <div class="alert alert--warning">
        No se encontró el archivo <code>centros_sepie.json</code>. Ejecuta primero el extractor Node.js.
      </div>
<?php endif; ?>
<?php endif; ?>

    </div>
  </div>
</body>
</html>
