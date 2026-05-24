<?php
/**
 * CRM EuryGo — Ficha de contacto (centros y agencias)
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requiere_login();

$db = get_db();
$id = (int)($_GET['id'] ?? 0);
$nuevo = !empty($_GET['nuevo']);
$pata_param = ($_GET['pata'] ?? 'centros') === 'agencias' ? 'agencias' : 'centros';

// POST: guardar contacto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verificar_csrf()) {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'guardar') {
        $datos = [
            'pata'              => $_POST['pata'] ?? 'centros',
            'nombre_centro'     => trim($_POST['nombre_centro'] ?? ''),
            'tipo_accion'       => $_POST['tipo_accion'] ?? 'OTRO',
            'tipo_centro'       => $_POST['tipo_centro'] ?? 'OTRO',
            'titularidad'       => $_POST['titularidad'] ?? 'nd',
            'pais'              => trim($_POST['pais'] ?? ''),
            'comunidad'         => trim($_POST['comunidad'] ?? ''),
            'provincia'         => trim($_POST['provincia'] ?? ''),
            'municipio'         => trim($_POST['municipio'] ?? ''),
            'cp'                => trim($_POST['cp'] ?? ''),
            'aeropuerto_cercano'=> $_POST['aeropuerto_cercano'] ?? null,
            'volumen_estimado'  => $_POST['volumen_estimado'] ?? 'nd',
            'fiabilidad'        => $_POST['fiabilidad'] ? (int)$_POST['fiabilidad'] : null,
            'paises_docentes'   => trim($_POST['paises_docentes'] ?? ''),
            'contacto_nombre'   => trim($_POST['contacto_nombre'] ?? ''),
            'contacto_cargo'    => trim($_POST['contacto_cargo'] ?? ''),
            'contacto_telefono' => trim($_POST['contacto_telefono'] ?? ''),
            'contacto_email'    => trim($_POST['contacto_email'] ?? ''),
            'contacto_linkedin' => trim($_POST['contacto_linkedin'] ?? ''),
            'estado'            => $_POST['estado'] ?? 'sin_contactar',
            'prioridad'         => $_POST['prioridad'] ?? 'media',
            'tipo_reunion'      => $_POST['tipo_reunion'] ?? 'telematica',
            'fecha_proximo_contacto' => $_POST['fecha_proximo_contacto'] ?: null,
            'notas'             => trim($_POST['notas'] ?? ''),
            'notas_internas'    => trim($_POST['notas_internas'] ?? ''),
        ];

        if ($nuevo || !$id) {
            $cols = array_keys($datos);
            $placeholders = array_map(fn($c) => ':' . $c, $cols);
            $sql = "INSERT INTO crm_contactos (" . implode(',', $cols) . ", fuente)
                    VALUES (" . implode(',', $placeholders) . ", 'web')";
            $stmt = $db->prepare($sql);
            $params = [];
            foreach ($datos as $k => $v) $params[':' . $k] = $v;
            $stmt->execute($params);
            $id = (int)$db->lastInsertId();
            header("Location: /admin/crm/ficha.php?id=$id&msg=creado");
            exit;
        } else {
            $sets = [];
            $params = [':id' => $id];
            foreach ($datos as $k => $v) {
                $sets[] = "$k = :$k";
                $params[":$k"] = $v;
            }
            $sql = "UPDATE crm_contactos SET " . implode(', ', $sets) . " WHERE id = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            header("Location: /admin/crm/ficha.php?id=$id&msg=guardado");
            exit;
        }
    } elseif ($accion === 'registrar_actividad' && $id) {
        $tipo = $_POST['tipo_actividad'] ?? 'llamada';
        $fecha = $_POST['fecha_actividad'] ?: date('Y-m-d H:i:s');
        $resultado = $_POST['resultado'] ?? 'pendiente';
        $resumen = trim($_POST['resumen'] ?? '');
        $proximo_paso = trim($_POST['proximo_paso'] ?? '');
        $fecha_seg = $_POST['fecha_seguimiento'] ?: null;
        $nuevo_estado = $_POST['nuevo_estado'] ?? '';

        if ($resumen) {
            $db->prepare("INSERT INTO crm_actividad
                (contacto_id, tipo, fecha, resultado, resumen, proximo_paso, fecha_seguimiento)
                VALUES (?, ?, ?, ?, ?, ?, ?)")
              ->execute([$id, $tipo, $fecha, $resultado, $resumen, $proximo_paso, $fecha_seg]);

            // Update fecha_ultimo_contacto + estado + proximo_contacto
            $upd = ["fecha_ultimo_contacto = CURDATE()"];
            $up_params = [':id' => $id];
            if ($fecha_seg) {
                $upd[] = "fecha_proximo_contacto = :prox";
                $up_params[':prox'] = $fecha_seg;
            }
            if ($nuevo_estado) {
                $upd[] = "estado = :estado";
                $up_params[':estado'] = $nuevo_estado;
            }
            $db->prepare("UPDATE crm_contactos SET " . implode(', ', $upd) . " WHERE id = :id")->execute($up_params);
        }
        header("Location: /admin/crm/ficha.php?id=$id&msg=accion_registrada");
        exit;
    } elseif ($accion === 'eliminar' && $id) {
        $db->prepare("DELETE FROM crm_contactos WHERE id = ?")->execute([$id]);
        header("Location: /admin/crm/centros.php?msg=eliminado");
        exit;
    }
}

// Cargar contacto
$c = null;
if ($id) {
    $st = $db->prepare("SELECT * FROM crm_contactos WHERE id = ?");
    $st->execute([$id]);
    $c = $st->fetch();
    if (!$c) {
        header('Location: /admin/crm/centros.php?msg=no_existe');
        exit;
    }
} elseif ($nuevo) {
    $c = [
        'id' => 0, 'pata' => $pata_param,
        'nombre_centro' => '', 'tipo_accion' => 'OTRO', 'tipo_centro' => 'OTRO',
        'titularidad' => 'nd', 'pais' => 'España', 'comunidad' => '', 'provincia' => '',
        'municipio' => '', 'cp' => '', 'aeropuerto_cercano' => null,
        'volumen_estimado' => 'nd', 'fiabilidad' => null, 'paises_docentes' => '',
        'contacto_nombre' => '', 'contacto_cargo' => '', 'contacto_telefono' => '',
        'contacto_email' => '', 'contacto_linkedin' => '',
        'estado' => 'sin_contactar', 'prioridad' => 'media', 'tipo_reunion' => 'telematica',
        'fecha_proximo_contacto' => null, 'fecha_ultimo_contacto' => null,
        'distancia_jerez_km' => null, 'notas' => '', 'notas_internas' => '',
    ];
} else {
    header('Location: /admin/crm/centros.php');
    exit;
}

// Cargar actividad
$actividad = [];
if ($id) {
    $st = $db->prepare("SELECT * FROM crm_actividad WHERE contacto_id = ? ORDER BY fecha DESC");
    $st->execute([$id]);
    $actividad = $st->fetchAll();
}

$msg = $_GET['msg'] ?? '';
$is_agencia = $c['pata'] === 'agencias';

function tipo_actividad_icono($t) {
    return match($t) {
        'llamada' => '📞', 'email' => '✉️', 'reunion_presencial' => '🏢',
        'videollamada' => '💻', 'whatsapp' => '💬', 'linkedin' => '💼',
        'nota_interna' => '📝', 'propuesta' => '📄', default => '•',
    };
}

$estados_orden = ['sin_contactar','contactado_tel','contactado_email','reunion_programada','reunion_realizada','propuesta_enviada','negociacion','cliente'];
$estados_label_corto = ['Sin contactar','Tel','Email','Reunión prog.','Reunión real.','Propuesta','Negociación','CLIENTE ✓'];
$idx_estado_actual = array_search($c['estado'], $estados_orden);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ficha · <?= htmlspecialchars($c['nombre_centro'] ?: 'Nuevo') ?></title>
  <link rel="stylesheet" href="/admin/assets/admin.css">
  <link rel="stylesheet" href="/admin/crm/assets/crm.css">
</head>
<body class="admin-body">
  <?php include __DIR__ . '/../partials/sidebar.php'; ?>
  <div class="admin-main">
    <?php include __DIR__ . '/../partials/topbar.php'; ?>
    <div class="admin-content" style="max-width:1200px;">

      <a href="/admin/crm/<?= $is_agencia ? 'agencias' : 'centros' ?>.php" style="color:#0284C7;font-size:0.85rem;">&larr; Volver al listado</a>

      <?php if ($msg === 'guardado'): ?>
      <div class="alert alert--success">Cambios guardados.</div>
      <?php elseif ($msg === 'creado'): ?>
      <div class="alert alert--success">Contacto creado correctamente.</div>
      <?php elseif ($msg === 'accion_registrada'): ?>
      <div class="alert alert--success">Acción registrada en el historial.</div>
      <?php endif; ?>

      <form method="POST" id="form-ficha">
        <?= campo_csrf() ?>
        <input type="hidden" name="accion" value="guardar">
        <input type="hidden" name="pata" value="<?= htmlspecialchars($c['pata']) ?>">

        <!-- HEADER -->
        <div class="ficha-header">
          <div class="ficha-header__info">
            <h1 style="margin:0;">
              <?php if ($nuevo): ?>Nuevo <?= $is_agencia ? 'agencia' : 'centro' ?><?php else: ?>
              <?= htmlspecialchars($c['nombre_centro']) ?>
              <?php endif; ?>
            </h1>
            <?php if (!$nuevo): ?>
            <div class="ficha-meta">
              <?php if ($c['tipo_accion'] === 'KA121-SCH'): ?>
              <span class="badge-ka121">● KA121 Acreditado</span>
              <?php elseif ($c['tipo_accion'] === 'KA122-SCH'): ?>
              <span class="badge-ka122">● KA122 Corta duración</span>
              <?php elseif ($c['tipo_accion']): ?>
              <span class="badge"><?= htmlspecialchars($c['tipo_accion']) ?></span>
              <?php endif; ?>
              <?php if ($c['municipio']): ?>· <?= htmlspecialchars($c['municipio']) ?><?php endif; ?>
              <?php if ($c['distancia_jerez_km']): ?> · <?= round($c['distancia_jerez_km']) ?> km de Jerez<?php endif; ?>
              · <span class="badge badge--<?= $c['prioridad'] ?>">Prioridad <?= $c['prioridad'] ?></span>
            </div>
            <?php endif; ?>
          </div>
          <div class="ficha-header__actions">
            <?php if ($c['contacto_telefono']): ?>
            <a href="tel:<?= htmlspecialchars($c['contacto_telefono']) ?>" class="btn-admin btn-admin--outline">📞 Llamar</a>
            <?php endif; ?>
            <?php if ($c['contacto_email']): ?>
            <a href="mailto:<?= htmlspecialchars($c['contacto_email']) ?>" class="btn-admin btn-admin--outline">✉️ Email</a>
            <?php endif; ?>
            <?php if ($c['contacto_linkedin']): ?>
            <a href="<?= htmlspecialchars($c['contacto_linkedin']) ?>" target="_blank" class="btn-admin btn-admin--outline">💼 LinkedIn</a>
            <?php endif; ?>
            <button type="submit" class="btn-admin btn-admin--primary">💾 Guardar</button>
          </div>
        </div>

        <!-- ESTADO PROGRESS -->
        <?php if (!$nuevo): ?>
        <div class="ficha-section">
          <h2>Estado comercial</h2>
          <div class="estado-progress">
            <?php foreach ($estados_orden as $i => $est):
              $clase = 'estado-progress__step';
              if ($i === $idx_estado_actual) $clase .= ' estado-progress__step--active';
              elseif ($i < $idx_estado_actual) $clase .= ' estado-progress__step--done';
              if ($est === 'cliente' && $c['estado'] === 'cliente') $clase = 'estado-progress__step estado-progress__step--cliente';
            ?>
            <button type="button" class="<?= $clase ?>" data-estado="<?= $est ?>" onclick="document.getElementById('estado-input').value='<?= $est ?>'; document.getElementById('form-ficha').submit();">
              <?= $estados_label_corto[$i] ?>
            </button>
            <?php endforeach; ?>
          </div>
          <input type="hidden" name="estado" id="estado-input" value="<?= $c['estado'] ?>">
          <?php if (in_array($c['estado'], ['descartado','no_interesado'])): ?>
          <p style="margin-top:0.5rem; color:#dc2626; font-size:0.9rem;">⚠️ Este contacto está marcado como <?= str_replace('_',' ',$c['estado']) ?>.</p>
          <?php endif; ?>
        </div>
        <?php else: ?>
        <input type="hidden" name="estado" value="sin_contactar">
        <?php endif; ?>

        <!-- DATOS PRINCIPALES -->
        <div class="ficha-section">
          <h2>Datos del <?= $is_agencia ? 'partner' : 'centro' ?></h2>
          <div class="form-group">
            <label>Nombre <?= $is_agencia ? 'de la agencia' : 'del centro' ?></label>
            <input type="text" name="nombre_centro" value="<?= htmlspecialchars($c['nombre_centro']) ?>" required>
          </div>

          <div class="ficha-grid">
            <?php if (!$is_agencia): ?>
            <div class="form-group">
              <label>Tipo de acción</label>
              <select name="tipo_accion">
                <?php foreach (['KA121-SCH','KA122-SCH','OTRO'] as $opt): ?>
                <option value="<?= $opt ?>" <?= $c['tipo_accion'] === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Tipo de centro</label>
              <select name="tipo_centro">
                <?php foreach (['CEIP','IES','CP','CIFP','EOI','CONSERVATORIO','EPA','FP','OTRO'] as $opt): ?>
                <option value="<?= $opt ?>" <?= $c['tipo_centro'] === $opt ? 'selected' : '' ?>><?= $opt ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Titularidad</label>
              <select name="titularidad">
                <?php foreach (['publico'=>'Público','concertado'=>'Concertado','privado'=>'Privado','nd'=>'No definida'] as $k=>$v): ?>
                <option value="<?= $k ?>" <?= $c['titularidad'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Tipo de reunión</label>
              <select name="tipo_reunion">
                <option value="presencial" <?= $c['tipo_reunion'] === 'presencial' ? 'selected' : '' ?>>🏢 Presencial</option>
                <option value="telematica" <?= $c['tipo_reunion'] === 'telematica' ? 'selected' : '' ?>>💻 Telemática</option>
              </select>
            </div>
            <?php else: ?>
            <input type="hidden" name="tipo_accion" value="AGENCIA">
            <input type="hidden" name="tipo_centro" value="AGENCIA_EU">
            <input type="hidden" name="titularidad" value="nd">
            <input type="hidden" name="tipo_reunion" value="telematica">
            <div class="form-group">
              <label>Aeropuerto más cercano</label>
              <select name="aeropuerto_cercano">
                <option value="">—</option>
                <option value="XRY" <?= $c['aeropuerto_cercano'] === 'XRY' ? 'selected' : '' ?>>XRY (Jerez)</option>
                <option value="SVQ" <?= $c['aeropuerto_cercano'] === 'SVQ' ? 'selected' : '' ?>>SVQ (Sevilla)</option>
                <option value="AGP" <?= $c['aeropuerto_cercano'] === 'AGP' ? 'selected' : '' ?>>AGP (Málaga)</option>
                <option value="OTHER" <?= $c['aeropuerto_cercano'] === 'OTHER' ? 'selected' : '' ?>>Otros</option>
              </select>
            </div>
            <div class="form-group">
              <label>Volumen estimado</label>
              <select name="volumen_estimado">
                <option value="nd" <?= $c['volumen_estimado'] === 'nd' ? 'selected' : '' ?>>—</option>
                <option value="grande" <?= $c['volumen_estimado'] === 'grande' ? 'selected' : '' ?>>Grande</option>
                <option value="medio" <?= $c['volumen_estimado'] === 'medio' ? 'selected' : '' ?>>Medio</option>
                <option value="pequeño" <?= $c['volumen_estimado'] === 'pequeño' ? 'selected' : '' ?>>Pequeño</option>
              </select>
            </div>
            <div class="form-group">
              <label>Fiabilidad (1-5)</label>
              <select name="fiabilidad">
                <option value="">—</option>
                <?php for ($i=5; $i>=1; $i--): ?>
                <option value="<?= $i ?>" <?= $c['fiabilidad'] == $i ? 'selected' : '' ?>><?= str_repeat('★',$i) ?></option>
                <?php endfor; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Países origen docentes</label>
              <input type="text" name="paises_docentes" value="<?= htmlspecialchars($c['paises_docentes'] ?? '') ?>" placeholder="ej: Italia, Francia, Alemania">
            </div>
            <?php endif; ?>

            <div class="form-group">
              <label>Prioridad</label>
              <select name="prioridad">
                <option value="alta" <?= $c['prioridad'] === 'alta' ? 'selected' : '' ?>>Alta</option>
                <option value="media" <?= $c['prioridad'] === 'media' ? 'selected' : '' ?>>Media</option>
                <option value="baja" <?= $c['prioridad'] === 'baja' ? 'selected' : '' ?>>Baja</option>
              </select>
            </div>
            <div class="form-group">
              <label>Próximo contacto</label>
              <input type="date" name="fecha_proximo_contacto" value="<?= htmlspecialchars($c['fecha_proximo_contacto'] ?? '') ?>">
            </div>
          </div>

          <h3 style="margin-top:1rem; font-size:0.95rem; color:#666;">Ubicación</h3>
          <div class="ficha-grid">
            <div class="form-group">
              <label>País</label>
              <input type="text" name="pais" value="<?= htmlspecialchars($c['pais'] ?? 'España') ?>">
            </div>
            <div class="form-group">
              <label>Comunidad autónoma</label>
              <input type="text" name="comunidad" value="<?= htmlspecialchars($c['comunidad'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label>Provincia</label>
              <input type="text" name="provincia" value="<?= htmlspecialchars($c['provincia'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label>Municipio</label>
              <input type="text" name="municipio" value="<?= htmlspecialchars($c['municipio'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label>Código postal</label>
              <input type="text" name="cp" value="<?= htmlspecialchars($c['cp'] ?? '') ?>">
            </div>
            <?php if ($c['distancia_jerez_km']): ?>
            <div class="form-group">
              <label>Distancia a Jerez</label>
              <div class="value" style="padding:0.6rem 0.85rem; color:#666;"><?= round($c['distancia_jerez_km']) ?> km</div>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- PERSONA DE CONTACTO -->
        <div class="ficha-section">
          <h2>Persona de contacto</h2>
          <div class="ficha-grid">
            <div class="form-group">
              <label>Nombre y apellidos</label>
              <input type="text" name="contacto_nombre" value="<?= htmlspecialchars($c['contacto_nombre'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label>Cargo</label>
              <input type="text" name="contacto_cargo" value="<?= htmlspecialchars($c['contacto_cargo'] ?? '') ?>" placeholder="Director, Coordinador Erasmus+, Product Manager…">
            </div>
            <div class="form-group">
              <label>Teléfono</label>
              <input type="text" name="contacto_telefono" value="<?= htmlspecialchars($c['contacto_telefono'] ?? '') ?>">
            </div>
            <div class="form-group">
              <label>Email</label>
              <input type="email" name="contacto_email" value="<?= htmlspecialchars($c['contacto_email'] ?? '') ?>">
            </div>
            <div class="form-group" style="grid-column: span 2;">
              <label>LinkedIn</label>
              <input type="url" name="contacto_linkedin" value="<?= htmlspecialchars($c['contacto_linkedin'] ?? '') ?>" placeholder="https://www.linkedin.com/in/...">
            </div>
          </div>
        </div>

        <!-- NOTAS -->
        <div class="ficha-section">
          <h2>Notas</h2>
          <div class="form-group">
            <label>Notas (visibles al exportar)</label>
            <textarea name="notas" rows="3"><?= htmlspecialchars($c['notas'] ?? '') ?></textarea>
          </div>
          <div class="form-group">
            <label>Notas internas <small>— privadas, no se exportan por defecto</small></label>
            <textarea name="notas_internas" rows="3"><?= htmlspecialchars($c['notas_internas'] ?? '') ?></textarea>
          </div>
        </div>
      </form>

<?php if (!$nuevo): ?>
      <!-- TIMELINE DE ACTIVIDAD -->
      <div class="ficha-section">
        <h2>Historial de actividad</h2>

        <?php if (empty($actividad)): ?>
        <p style="color:#999; font-size:0.9rem;">Aún no hay interacciones registradas. Registra la primera abajo.</p>
        <?php else: ?>
        <div class="timeline">
          <?php foreach ($actividad as $act): ?>
          <div class="timeline-item timeline-item--<?= $act['resultado'] ?>">
            <div class="timeline-item__header">
              <span class="timeline-item__tipo">
                <?= tipo_actividad_icono($act['tipo']) ?>
                <?= ucfirst(str_replace('_',' ',$act['tipo'])) ?>
              </span>
              <span class="timeline-item__fecha"><?= date('d/m/Y H:i', strtotime($act['fecha'])) ?></span>
              <?php if ($act['resultado'] !== 'pendiente'): ?>
              <span class="badge badge--<?= match($act['resultado']) {
                'positivo' => 'success', 'negativo' => 'error', 'sin_respuesta' => 'warning', default => 'info'
              } ?>"><?= ucfirst(str_replace('_',' ',$act['resultado'])) ?></span>
              <?php endif; ?>
            </div>
            <div class="timeline-item__resumen"><?= nl2br(htmlspecialchars($act['resumen'])) ?></div>
            <?php if ($act['proximo_paso']): ?>
            <div class="timeline-item__proximo">→ Próximo: <?= htmlspecialchars($act['proximo_paso']) ?>
            <?php if ($act['fecha_seguimiento']): ?>(<?= date('d/m/Y', strtotime($act['fecha_seguimiento'])) ?>)<?php endif; ?>
            </div>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- FORMULARIO REGISTRAR ACCIÓN -->
        <form method="POST" class="action-form" style="margin-top:1.5rem;">
          <?= campo_csrf() ?>
          <input type="hidden" name="accion" value="registrar_actividad">
          <h3 style="font-size:1rem; margin-bottom:0.75rem;">Registrar nueva acción</h3>

          <div class="form-row">
            <div class="form-group">
              <label>Tipo</label>
              <select name="tipo_actividad">
                <option value="llamada">📞 Llamada</option>
                <option value="email">✉️ Email</option>
                <option value="reunion_presencial">🏢 Reunión presencial</option>
                <option value="videollamada">💻 Videollamada</option>
                <option value="whatsapp">💬 WhatsApp</option>
                <option value="linkedin">💼 LinkedIn</option>
                <option value="propuesta">📄 Propuesta enviada</option>
                <option value="nota_interna">📝 Nota interna</option>
              </select>
            </div>
            <div class="form-group">
              <label>Resultado</label>
              <select name="resultado">
                <option value="pendiente">Pendiente</option>
                <option value="positivo">✓ Positivo</option>
                <option value="neutro">⊝ Neutro</option>
                <option value="negativo">✗ Negativo</option>
                <option value="sin_respuesta">Sin respuesta</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label>Resumen *</label>
            <textarea name="resumen" rows="3" required placeholder="Hablé con la jefa de estudios. Está interesada y pide que le envíe info por email..."></textarea>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label>Próximo paso</label>
              <input type="text" name="proximo_paso" placeholder="Enviar dossier el lunes">
            </div>
            <div class="form-group">
              <label>Fecha de seguimiento</label>
              <input type="date" name="fecha_seguimiento">
            </div>
          </div>

          <div class="form-group">
            <label>Cambiar estado del contacto a</label>
            <select name="nuevo_estado">
              <option value="">— mantener actual —</option>
              <option value="contactado_tel">Contactado (teléfono)</option>
              <option value="contactado_email">Contactado (email)</option>
              <option value="reunion_programada">Reunión programada</option>
              <option value="reunion_realizada">Reunión realizada</option>
              <option value="propuesta_enviada">Propuesta enviada</option>
              <option value="negociacion">En negociación</option>
              <option value="cliente">Cliente ✅</option>
              <option value="descartado">Descartado</option>
              <option value="no_interesado">No interesado</option>
            </select>
          </div>

          <button type="submit" class="btn-admin btn-admin--primary">Registrar acción</button>
        </form>
      </div>

      <!-- PLANTILLAS DE CONTACTO -->
      <div class="ficha-section">
        <h2>Plantillas rápidas</h2>
        <a href="/admin/crm/plantillas.php?<?= $is_agencia ? 'pata=agencias' : 'pata=centros' ?>" class="btn-admin btn-admin--outline btn-admin--sm">Ver todas las plantillas</a>
      </div>

      <!-- ELIMINAR -->
      <div class="ficha-section" style="border:1px solid #fee2e2;">
        <h2 style="color:#dc2626;">Zona peligrosa</h2>
        <form method="POST" onsubmit="return confirm('¿Eliminar este contacto y todo su historial? Esta acción no se puede deshacer.');">
          <?= campo_csrf() ?>
          <input type="hidden" name="accion" value="eliminar">
          <button type="submit" class="btn-admin btn-admin--danger">Eliminar contacto</button>
        </form>
      </div>
<?php endif; ?>

    </div>
  </div>
</body>
</html>
