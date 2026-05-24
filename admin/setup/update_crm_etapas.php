<?php
/**
 * EuryGo CRM — Migración: añadir columnas etapa_educativa y titularidad
 *
 * Ejecutar UNA VEZ desde el navegador: /admin/setup/update_crm_etapas.php
 * Borrar el archivo después por seguridad.
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requiere_login();

$db = get_db();
$resultados = [];

// Comprobar columnas existentes (MySQL antiguo no soporta IF NOT EXISTS en ADD COLUMN)
function columna_existe($db, $tabla, $columna) {
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?
    ");
    $stmt->execute([$tabla, $columna]);
    return (int)$stmt->fetchColumn() > 0;
}

function indice_existe($db, $tabla, $indice) {
    $stmt = $db->prepare("
        SELECT COUNT(*) FROM information_schema.STATISTICS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?
    ");
    $stmt->execute([$tabla, $indice]);
    return (int)$stmt->fetchColumn() > 0;
}

// ─── etapa_educativa ───
if (!columna_existe($db, 'crm_contactos', 'etapa_educativa')) {
    try {
        $db->exec("
            ALTER TABLE crm_contactos
            ADD COLUMN etapa_educativa
              ENUM('infantil_0_3','infantil','primaria','secundaria',
                   'bachillerato','fp_medio','fp_superior',
                   'adultos','especial','otro')
              DEFAULT 'otro' AFTER tipo_centro
        ");
        $resultados[] = ['ok', 'Columna etapa_educativa añadida'];
    } catch (PDOException $e) {
        $resultados[] = ['error', 'etapa_educativa: ' . $e->getMessage()];
    }
} else {
    $resultados[] = ['skip', 'etapa_educativa ya existía'];
}

// ─── titularidad ya existe en la tabla original — solo verificar ───
if (!columna_existe($db, 'crm_contactos', 'titularidad')) {
    try {
        $db->exec("
            ALTER TABLE crm_contactos
            ADD COLUMN titularidad
              ENUM('publico','concertado','privado','nd')
              DEFAULT 'nd' AFTER etapa_educativa
        ");
        $resultados[] = ['ok', 'Columna titularidad añadida'];
    } catch (PDOException $e) {
        $resultados[] = ['error', 'titularidad: ' . $e->getMessage()];
    }
} else {
    $resultados[] = ['skip', 'titularidad ya existía (de update_crm.php)'];
}

// ─── Índices ───
if (!indice_existe($db, 'crm_contactos', 'idx_etapa')) {
    try {
        $db->exec("ALTER TABLE crm_contactos ADD INDEX idx_etapa (etapa_educativa)");
        $resultados[] = ['ok', 'Índice idx_etapa creado'];
    } catch (PDOException $e) {
        $resultados[] = ['error', 'idx_etapa: ' . $e->getMessage()];
    }
} else {
    $resultados[] = ['skip', 'idx_etapa ya existía'];
}

if (!indice_existe($db, 'crm_contactos', 'idx_titularidad')) {
    try {
        $db->exec("ALTER TABLE crm_contactos ADD INDEX idx_titularidad (titularidad)");
        $resultados[] = ['ok', 'Índice idx_titularidad creado'];
    } catch (PDOException $e) {
        $resultados[] = ['error', 'idx_titularidad: ' . $e->getMessage()];
    }
} else {
    $resultados[] = ['skip', 'idx_titularidad ya existía'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Migración CRM Etapas — EuryGo</title>
  <style>
    body { font-family: system-ui, sans-serif; max-width: 700px; margin: 2rem auto; padding: 1rem; }
    .ok { color: #16a34a; } .error { color: #dc2626; } .skip { color: #6b7280; }
    .result { padding: 0.5rem 1rem; margin: 0.5rem 0; border-radius: 6px; }
    .result.ok { background: #dcfce7; }
    .result.error { background: #fee2e2; }
    .result.skip { background: #f3f4f6; }
  </style>
</head>
<body>
  <h1>Migración CRM — Etapas educativas</h1>
  <?php foreach ($resultados as [$tipo, $msg]): ?>
  <div class="result <?= $tipo ?>">
    <?= $tipo === 'ok' ? '✅' : ($tipo === 'skip' ? '↪️' : '❌') ?>
    <?= htmlspecialchars($msg) ?>
  </div>
  <?php endforeach; ?>

  <div style="margin-top:2rem; padding:1rem; background:#fef3c7; border-radius:6px;">
    <strong>⚠️ Borra este archivo después de ejecutarlo</strong> por seguridad.<br>
    Ruta: <code>admin/setup/update_crm_etapas.php</code>
  </div>

  <p style="margin-top:1rem;"><a href="/admin/index.php">&larr; Volver al panel</a></p>
</body>
</html>
