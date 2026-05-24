<?php
/**
 * CRM EuryGo — Migración de base de datos
 * Crea tablas crm_contactos y crm_actividad + agencias de referencia
 *
 * Ejecutar UNA VEZ desde el navegador: /admin/setup/update_crm.php
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';

requiere_login();

$db = get_db();
$resultados = [];

// ─── PASO 1: Tabla crm_contactos ─────────────────────────────────
try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS crm_contactos (
          id                   INT AUTO_INCREMENT PRIMARY KEY,
          pata                 ENUM('centros','agencias') NOT NULL,

          nombre_centro        VARCHAR(500) NOT NULL,
          tipo_accion          ENUM('KA121-SCH','KA122-SCH','AGENCIA','OTRO') DEFAULT 'KA121-SCH',
          num_solicitud        VARCHAR(50),
          num_proyecto         VARCHAR(100),
          tipo_centro          ENUM('CEIP','IES','CP','CIFP','EOI','CONSERVATORIO','EPA','FP','OTRO','AGENCIA_EU') DEFAULT 'OTRO',
          titularidad          ENUM('publico','concertado','privado','nd') DEFAULT 'nd',

          pais                 VARCHAR(100) DEFAULT 'España',
          comunidad            VARCHAR(100),
          provincia            VARCHAR(100),
          municipio            VARCHAR(255),
          cp                   VARCHAR(10),
          lat                  DECIMAL(10,7),
          lng                  DECIMAL(10,7),
          distancia_jerez_km   DECIMAL(8,2),
          tipo_reunion         ENUM('presencial','telematica') DEFAULT 'telematica',

          aeropuerto_cercano   ENUM('XRY','SVQ','AGP','OTHER'),
          paises_docentes      VARCHAR(500),
          volumen_estimado     ENUM('pequeño','medio','grande','nd') DEFAULT 'nd',
          fiabilidad           TINYINT DEFAULT NULL,

          contacto_nombre      VARCHAR(255),
          contacto_cargo       VARCHAR(255),
          contacto_telefono    VARCHAR(50),
          contacto_email       VARCHAR(255),
          contacto_linkedin    VARCHAR(500),

          estado               ENUM(
                                 'sin_contactar','contactado_tel',
                                 'contactado_email','reunion_programada',
                                 'reunion_realizada','propuesta_enviada',
                                 'negociacion','cliente',
                                 'descartado','no_interesado'
                               ) DEFAULT 'sin_contactar',
          prioridad            ENUM('alta','media','baja') DEFAULT 'media',
          fecha_ultimo_contacto    DATE,
          fecha_proximo_contacto   DATE,
          fuente               ENUM('sepie_2026','linkedin','web','school_education_gateway','referido','otro') DEFAULT 'sepie_2026',

          notas                TEXT,
          notas_internas       TEXT,
          fecha_creacion       DATETIME DEFAULT CURRENT_TIMESTAMP,
          fecha_modificacion   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

          UNIQUE KEY uq_solicitud (num_solicitud),
          INDEX idx_pata (pata),
          INDEX idx_estado (estado),
          INDEX idx_prioridad (prioridad),
          INDEX idx_comunidad (comunidad),
          INDEX idx_distancia (distancia_jerez_km),
          INDEX idx_accion (tipo_accion)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $resultados[] = ['ok', 'Tabla crm_contactos creada/verificada'];
} catch (PDOException $e) {
    $resultados[] = ['error', 'Error crm_contactos: ' . $e->getMessage()];
}

// ─── PASO 2: Tabla crm_actividad ─────────────────────────────────
try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS crm_actividad (
          id             INT AUTO_INCREMENT PRIMARY KEY,
          contacto_id    INT NOT NULL,
          tipo           ENUM('llamada','email','reunion_presencial',
                           'videollamada','whatsapp','linkedin',
                           'nota_interna','propuesta','otro') NOT NULL,
          fecha          DATETIME NOT NULL,
          resultado      ENUM('positivo','neutro','negativo',
                           'sin_respuesta','pendiente') DEFAULT 'pendiente',
          resumen        TEXT NOT NULL,
          proximo_paso   VARCHAR(500),
          fecha_seguimiento DATE,
          FOREIGN KEY (contacto_id)
            REFERENCES crm_contactos(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $resultados[] = ['ok', 'Tabla crm_actividad creada/verificada'];
} catch (PDOException $e) {
    $resultados[] = ['error', 'Error crm_actividad: ' . $e->getMessage()];
}

// ─── PASO 3: Agencias europeas de referencia ─────────────────────
$agencias = [
    [
        'nombre' => 'Europass Teacher Academy',
        'pais' => 'Italia',
        'aeropuerto' => 'SVQ',
        'volumen' => 'grande',
        'fiabilidad' => 4,
        'notas' => 'Más de 400 cursos KA1, 70.000 profesores/año. Competidor directo en formación, posible partner para envío de grupos a Jerez.',
        'contacto_email' => '',
        'contacto_linkedin' => 'https://www.linkedin.com/school/europassteacheracademy/',
    ],
    [
        'nombre' => 'Erasmus Learning Academy (ELA)',
        'pais' => 'Italia',
        'aeropuerto' => 'SVQ',
        'volumen' => 'grande',
        'fiabilidad' => 5,
        'notas' => '+1500 profesores/año, 25 países, 5/5 Google Reviews. Muy consolidada. Posible partner para grupos en Jerez.',
        'contacto_email' => '',
        'contacto_linkedin' => '',
    ],
    [
        'nombre' => 'Primera Erasmus+ Courses',
        'pais' => 'Estonia',
        'aeropuerto' => 'OTHER',
        'volumen' => 'medio',
        'fiabilidad' => 4,
        'notas' => '+10 años en el sector, grupos a medida para +10 personas. Interesante para jobshadowing en centros de Jerez.',
        'contacto_email' => '',
        'contacto_linkedin' => '',
    ],
    [
        'nombre' => 'Learnlight',
        'pais' => 'España',
        'aeropuerto' => 'OTHER',
        'volumen' => 'grande',
        'fiabilidad' => null,
        'notas' => 'Agencia española con proyección europea (Madrid). Contacto presencial posible. Priorizar.',
        'contacto_email' => '',
        'contacto_linkedin' => '',
    ],
    [
        'nombre' => 'Agolearn',
        'pais' => 'Grecia',
        'aeropuerto' => 'OTHER',
        'volumen' => 'medio',
        'fiabilidad' => null,
        'notas' => 'Agencia consolidada en destinos mediterráneos. Competidora en destinos, posible partner.',
        'contacto_email' => '',
        'contacto_linkedin' => '',
    ],
];

$stmt_check = $db->prepare("SELECT id FROM crm_contactos WHERE nombre_centro = ? AND pata = 'agencias' LIMIT 1");
$stmt_ins = $db->prepare("
    INSERT INTO crm_contactos
    (pata, nombre_centro, tipo_accion, tipo_centro, pais, aeropuerto_cercano,
     volumen_estimado, fiabilidad, notas, contacto_email, contacto_linkedin,
     estado, prioridad, fuente)
    VALUES
    ('agencias', ?, 'AGENCIA', 'AGENCIA_EU', ?, ?, ?, ?, ?, ?, ?,
     'sin_contactar', 'alta', 'school_education_gateway')
");

$insertadas = 0;
foreach ($agencias as $ag) {
    $stmt_check->execute([$ag['nombre']]);
    if (!$stmt_check->fetch()) {
        $stmt_ins->execute([
            $ag['nombre'], $ag['pais'], $ag['aeropuerto'],
            $ag['volumen'], $ag['fiabilidad'], $ag['notas'],
            $ag['contacto_email'], $ag['contacto_linkedin'],
        ]);
        $insertadas++;
    }
}
$resultados[] = ['ok', "Agencias de referencia: $insertadas insertadas (de " . count($agencias) . " totales)"];

// ─── Resultado ───────────────────────────────────────────────────
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Migración CRM — EuryGo</title>
  <style>
    body { font-family: system-ui, sans-serif; max-width: 700px; margin: 2rem auto; padding: 1rem; }
    .ok { color: #16a34a; } .error { color: #dc2626; }
    .result { padding: 0.5rem 1rem; margin: 0.5rem 0; border-radius: 6px; }
    .result.ok { background: #dcfce7; } .result.error { background: #fee2e2; }
  </style>
</head>
<body>
  <h1>Migración CRM — EuryGo</h1>
  <?php foreach ($resultados as [$tipo, $msg]): ?>
  <div class="result <?= $tipo ?>">
    <?= $tipo === 'ok' ? '✅' : '❌' ?> <?= htmlspecialchars($msg) ?>
  </div>
  <?php endforeach; ?>
  <p style="margin-top:2rem;"><a href="/admin/index.php">&larr; Volver al panel</a></p>
</body>
</html>
