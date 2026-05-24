<?php
/**
 * Back Office — Editor de campañas de newsletter
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/funciones.php';
require_once __DIR__ . '/../includes/brevo.php';

requiere_login();

$db = get_db();
$mensaje = '';
$error = '';

// ─── Cargar campaña existente (edición) ───
$campana_id = (int)($_GET['id'] ?? 0);
$campana = null;
if ($campana_id) {
    $stmt = $db->prepare("SELECT * FROM newsletter_campanas WHERE id = ? AND estado = 'borrador'");
    $stmt->execute([$campana_id]);
    $campana = $stmt->fetch();
}

// ─── Procesar POST ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verificar_csrf()) {
    $accion = $_POST['accion'] ?? '';
    $asunto = trim($_POST['asunto'] ?? '');
    $preheader = trim($_POST['preheader'] ?? '');
    $idioma = in_array($_POST['idioma'] ?? '', ['es', 'en']) ? $_POST['idioma'] : 'es';
    $contenido_html = $_POST['contenido_html'] ?? '';

    if (empty($asunto) || empty($contenido_html)) {
        $error = 'El asunto y el contenido son obligatorios.';
    } else {
        // Plantilla de email
        $email_html = generar_plantilla_email($asunto, $contenido_html, $idioma);
        $contenido_texto = strip_tags($contenido_html);

        if ($accion === 'guardar') {
            if ($campana) {
                $db->prepare("UPDATE newsletter_campanas SET asunto = ?, preheader = ?, contenido_html = ?, contenido_texto = ?, idioma = ? WHERE id = ?")
                    ->execute([$asunto, $preheader, $email_html, $contenido_texto, $idioma, $campana['id']]);
                $campana_id = $campana['id'];
            } else {
                $db->prepare("INSERT INTO newsletter_campanas (asunto, preheader, contenido_html, contenido_texto, idioma) VALUES (?, ?, ?, ?, ?)")
                    ->execute([$asunto, $preheader, $email_html, $contenido_texto, $idioma]);
                $campana_id = (int)$db->lastInsertId();
            }
            $mensaje = 'Campaña guardada como borrador.';
            // Recargar
            $stmt = $db->prepare("SELECT * FROM newsletter_campanas WHERE id = ?");
            $stmt->execute([$campana_id]);
            $campana = $stmt->fetch();
        }

        if ($accion === 'prueba') {
            $email_prueba = trim($_POST['email_prueba'] ?? '');
            if (!filter_var($email_prueba, FILTER_VALIDATE_EMAIL)) {
                $error = 'Email de prueba no válido.';
            } else {
                $brevo = new BrevoAPI();
                $result = $brevo->sendTransactional($email_prueba, 'Test', '[PRUEBA] ' . $asunto, $email_html, $contenido_texto, MAIL_NEWSLETTER, MAIL_FROM_NAME);
                if ($result['success']) {
                    $mensaje = "Email de prueba enviado a {$email_prueba}.";
                } else {
                    $error = 'Error al enviar prueba: ' . $result['error'];
                }
            }
        }

        if ($accion === 'enviar') {
            // Contar destinatarios
            $stmt = $db->prepare("SELECT COUNT(*) FROM newsletter_suscriptores WHERE activo = 1 AND confirmado = 1 AND idioma = ?");
            $stmt->execute([$idioma]);
            $total_dest = (int)$stmt->fetchColumn();

            if ($total_dest === 0) {
                $error = 'No hay suscriptores activos para el idioma ' . strtoupper($idioma) . '.';
            } else {
                $listId = ($idioma === 'en') ? BREVO_LIST_ID_EN : BREVO_LIST_ID_ES;
                if ($listId <= 0) {
                    $error = 'El ID de lista de Brevo no está configurado para ' . strtoupper($idioma) . '. Revisa config.php.';
                } else {
                    $brevo = new BrevoAPI();

                    // Guardar en BD si no existe
                    if (!$campana) {
                        $db->prepare("INSERT INTO newsletter_campanas (asunto, preheader, contenido_html, contenido_texto, idioma, estado) VALUES (?, ?, ?, ?, ?, 'enviando')")
                            ->execute([$asunto, $preheader, $email_html, $contenido_texto, $idioma]);
                        $campana_id = (int)$db->lastInsertId();
                    } else {
                        $campana_id = $campana['id'];
                        $db->prepare("UPDATE newsletter_campanas SET asunto = ?, preheader = ?, contenido_html = ?, contenido_texto = ?, idioma = ?, estado = 'enviando' WHERE id = ?")
                            ->execute([$asunto, $preheader, $email_html, $contenido_texto, $idioma, $campana_id]);
                    }

                    // Crear campaña en Brevo
                    $result = $brevo->createCampaign('EuryGo Newsletter ' . date('Y-m-d'), $asunto, $email_html, $listId, $preheader);
                    if ($result['success'] && !empty($result['data']['id'])) {
                        $brevo_id = (int)$result['data']['id'];
                        $db->prepare("UPDATE newsletter_campanas SET brevo_campaign_id = ? WHERE id = ?")->execute([$brevo_id, $campana_id]);

                        // Enviar ahora
                        $send = $brevo->sendCampaignNow($brevo_id);
                        if ($send['success']) {
                            $db->prepare("UPDATE newsletter_campanas SET estado = 'enviada', fecha_envio = NOW(), total_enviados = ? WHERE id = ?")
                                ->execute([$total_dest, $campana_id]);
                            $mensaje = "Campaña enviada a {$total_dest} suscriptores.";
                        } else {
                            $db->prepare("UPDATE newsletter_campanas SET estado = 'borrador' WHERE id = ?")->execute([$campana_id]);
                            $error = 'Error al activar envío en Brevo: ' . $send['error'];
                        }
                    } else {
                        $db->prepare("UPDATE newsletter_campanas SET estado = 'borrador' WHERE id = ?")->execute([$campana_id]);
                        $error = 'Error al crear campaña en Brevo: ' . $result['error'];
                    }

                    // Recargar
                    $stmt = $db->prepare("SELECT * FROM newsletter_campanas WHERE id = ?");
                    $stmt->execute([$campana_id]);
                    $campana = $stmt->fetch();
                }
            }
        }

        if ($accion === 'programar') {
            $fecha_prog = $_POST['fecha_programada'] ?? '';
            if (empty($fecha_prog)) {
                $error = 'Selecciona una fecha y hora para el envío programado.';
            } else {
                if (!$campana) {
                    $db->prepare("INSERT INTO newsletter_campanas (asunto, preheader, contenido_html, contenido_texto, idioma, estado, fecha_programada) VALUES (?, ?, ?, ?, ?, 'programada', ?)")
                        ->execute([$asunto, $preheader, $email_html, $contenido_texto, $idioma, $fecha_prog]);
                    $campana_id = (int)$db->lastInsertId();
                } else {
                    $db->prepare("UPDATE newsletter_campanas SET asunto = ?, preheader = ?, contenido_html = ?, contenido_texto = ?, idioma = ?, estado = 'programada', fecha_programada = ? WHERE id = ?")
                        ->execute([$asunto, $preheader, $email_html, $contenido_texto, $idioma, $fecha_prog, $campana['id']]);
                    $campana_id = $campana['id'];
                }
                $mensaje = 'Campaña programada para ' . $fecha_prog . '.';
                $stmt = $db->prepare("SELECT * FROM newsletter_campanas WHERE id = ?");
                $stmt->execute([$campana_id]);
                $campana = $stmt->fetch();
            }
        }
    }
}

// Contar suscriptores por idioma
$count_es = (int)$db->query("SELECT COUNT(*) FROM newsletter_suscriptores WHERE activo = 1 AND confirmado = 1 AND idioma = 'es'")->fetchColumn();
$count_en = (int)$db->query("SELECT COUNT(*) FROM newsletter_suscriptores WHERE activo = 1 AND confirmado = 1 AND idioma = 'en'")->fetchColumn();

function generar_plantilla_email(string $asunto, string $contenido, string $idioma): string {
    $baja_text = ($idioma === 'en') ? 'Unsubscribe' : 'Darte de baja';
    return '<!DOCTYPE html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>body{margin:0;padding:0;font-family:system-ui,-apple-system,sans-serif;background:#f4f4f4;color:#1a1a1a;}
.wrapper{max-width:600px;margin:0 auto;background:#fff;}
.header{background:#0C4A6E;padding:24px;text-align:center;}
.header img,.header svg{height:36px;}
.content{padding:32px 24px;line-height:1.7;font-size:16px;}
.content h2{color:#0C4A6E;margin-top:24px;}
.content a{color:#0284C7;}
.footer{background:#f8f9fa;padding:24px;text-align:center;font-size:13px;color:#64748B;border-top:1px solid #e2e8f0;}
.footer a{color:#0284C7;}</style></head><body>
<div class="wrapper">
<div class="header"><span style="color:#fff;font-size:24px;font-weight:800;">Eury<span style="color:#F59E0B;">Go</span></span></div>
<div class="content">' . $contenido . '</div>
<div class="footer">
<p>EuryGo — Jerez de la Frontera, Cádiz, España</p>
<p><a href="{{unsubscribe}}">' . $baja_text . '</a></p>
<p>&copy; ' . date('Y') . ' EuryGo</p>
</div></div></body></html>';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $campana ? 'Editar campaña' : 'Nueva campaña' ?> — EuryGo Admin</title>
  <link rel="stylesheet" href="/admin/assets/admin.css">
  <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
  <meta name="robots" content="noindex, nofollow">
</head>
<body class="admin-body">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <div class="admin-main">
    <?php include __DIR__ . '/partials/topbar.php'; ?>

    <div class="admin-content">
<?php if ($mensaje): ?>
      <div class="alert alert--success"><?= htmlspecialchars($mensaje) ?></div>
<?php endif; ?>
<?php if ($error): ?>
      <div class="alert alert--error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (strpos(BREVO_API_KEY, 'XXXX') !== false): ?>
      <div class="alert alert--warning">
        Para enviar newsletters necesitas configurar tu cuenta de Brevo.
        Asegúrate de tener tu API key en <code>config.php</code> y los IDs de lista correctos.
        <a href="/INSTALACION.md" target="_blank">Ver instrucciones</a>
      </div>
<?php endif; ?>

      <form method="POST" id="campaign-form">
        <?= campo_csrf() ?>

        <div class="editor-actions">
          <h1><?= $campana ? 'Editar campaña' : 'Nueva campaña' ?></h1>
          <div class="editor-actions__buttons">
            <button type="submit" name="accion" value="guardar" class="btn-admin btn-admin--outline">Guardar borrador</button>
            <button type="button" id="btn-prueba" class="btn-admin btn-admin--outline">Enviar prueba</button>
            <button type="button" id="btn-programar" class="btn-admin btn-admin--outline">Programar envío</button>
            <button type="button" id="btn-enviar" class="btn-admin btn-admin--primary">Enviar ahora</button>
          </div>
        </div>

        <div class="editor-section">
          <h2>Contenido del email</h2>
          <div class="form-group">
            <label>Asunto *</label>
            <input type="text" name="asunto" value="<?= htmlspecialchars($campana['asunto'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label>Preheader <small>(texto que aparece tras el asunto en el cliente de correo)</small> <span class="char-count" id="preheader-count">0/100</span></label>
            <input type="text" name="preheader" value="<?= htmlspecialchars($campana['preheader'] ?? '') ?>" maxlength="100" id="preheader-input">
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Idioma</label>
              <select name="idioma" id="select-idioma">
                <option value="es" <?= ($campana['idioma'] ?? 'es') === 'es' ? 'selected' : '' ?>>ES — <?= $count_es ?> suscriptores</option>
                <option value="en" <?= ($campana['idioma'] ?? '') === 'en' ? 'selected' : '' ?>>EN — <?= $count_en ?> suscriptores</option>
              </select>
            </div>
          </div>
          <div class="form-group">
            <label>Contenido *</label>
            <textarea name="contenido_html" id="editor-newsletter" rows="16"><?= htmlspecialchars($campana['contenido_html'] ?? '') ?></textarea>
          </div>
        </div>

        <!-- Campos ocultos para acciones especiales -->
        <input type="hidden" name="accion" id="hidden-accion" value="guardar">
        <input type="hidden" name="email_prueba" id="hidden-email-prueba" value="">
        <input type="hidden" name="fecha_programada" id="hidden-fecha-programada" value="">
      </form>

      <!-- Modal envío -->
      <div id="modal-enviar" class="modal-overlay" hidden>
        <div class="modal-box">
          <h3>Confirmar envío</h3>
          <p id="modal-text">Vas a enviar este newsletter a <strong id="modal-count">0</strong> suscriptores en <strong id="modal-lang">ES</strong>. Esta acción no se puede deshacer.</p>
          <div style="display:flex;gap:0.75rem;justify-content:flex-end;margin-top:1.5rem;">
            <button class="btn-admin btn-admin--outline" onclick="document.getElementById('modal-enviar').hidden=true">Cancelar</button>
            <button class="btn-admin btn-admin--primary" id="modal-confirm-btn">Sí, enviar ahora</button>
          </div>
        </div>
      </div>

      <!-- Modal prueba -->
      <div id="modal-prueba" class="modal-overlay" hidden>
        <div class="modal-box">
          <h3>Enviar email de prueba</h3>
          <div class="form-group" style="margin-top:1rem;">
            <label>Enviar prueba a:</label>
            <input type="email" id="input-email-prueba" placeholder="tu@email.com" value="<?= htmlspecialchars($_SESSION['admin_nombre'] ?? '') ?>">
          </div>
          <div style="display:flex;gap:0.75rem;justify-content:flex-end;margin-top:1rem;">
            <button class="btn-admin btn-admin--outline" onclick="document.getElementById('modal-prueba').hidden=true">Cancelar</button>
            <button class="btn-admin btn-admin--primary" id="modal-prueba-btn">Enviar prueba</button>
          </div>
        </div>
      </div>

      <!-- Modal programar -->
      <div id="modal-programar" class="modal-overlay" hidden>
        <div class="modal-box">
          <h3>Programar envío</h3>
          <div class="form-group" style="margin-top:1rem;">
            <label>Fecha y hora de envío:</label>
            <input type="datetime-local" id="input-fecha-programada">
          </div>
          <div style="display:flex;gap:0.75rem;justify-content:flex-end;margin-top:1rem;">
            <button class="btn-admin btn-admin--outline" onclick="document.getElementById('modal-programar').hidden=true">Cancelar</button>
            <button class="btn-admin btn-admin--primary" id="modal-programar-btn">Programar</button>
          </div>
        </div>
      </div>
    </div>
  </div>

<script>
tinymce.init({
  selector: '#editor-newsletter',
  height: 400,
  menubar: false,
  plugins: 'lists link image code',
  toolbar: 'undo redo | bold italic underline | bullist numlist | link image | code',
  content_style: 'body { font-family: system-ui, sans-serif; font-size: 16px; line-height: 1.7; }'
});

// Preheader counter
var phInput = document.getElementById('preheader-input');
var phCount = document.getElementById('preheader-count');
if (phInput) {
  phCount.textContent = phInput.value.length + '/100';
  phInput.addEventListener('input', function() { phCount.textContent = this.value.length + '/100'; });
}

var counts = { es: <?= $count_es ?>, en: <?= $count_en ?> };

// Enviar ahora
document.getElementById('btn-enviar').addEventListener('click', function() {
  var lang = document.getElementById('select-idioma').value;
  document.getElementById('modal-count').textContent = counts[lang];
  document.getElementById('modal-lang').textContent = lang.toUpperCase();
  document.getElementById('modal-enviar').hidden = false;
});

document.getElementById('modal-confirm-btn').addEventListener('click', function() {
  document.getElementById('modal-enviar').hidden = true;
  tinymce.triggerSave();
  document.getElementById('hidden-accion').value = 'enviar';
  document.getElementById('campaign-form').submit();
});

// Prueba
document.getElementById('btn-prueba').addEventListener('click', function() {
  document.getElementById('modal-prueba').hidden = false;
});

document.getElementById('modal-prueba-btn').addEventListener('click', function() {
  var email = document.getElementById('input-email-prueba').value;
  if (!email) return;
  document.getElementById('modal-prueba').hidden = true;
  tinymce.triggerSave();
  document.getElementById('hidden-accion').value = 'prueba';
  document.getElementById('hidden-email-prueba').value = email;
  document.getElementById('campaign-form').submit();
});

// Programar
document.getElementById('btn-programar').addEventListener('click', function() {
  document.getElementById('modal-programar').hidden = false;
});

document.getElementById('modal-programar-btn').addEventListener('click', function() {
  var fecha = document.getElementById('input-fecha-programada').value;
  if (!fecha) return;
  document.getElementById('modal-programar').hidden = true;
  tinymce.triggerSave();
  document.getElementById('hidden-accion').value = 'programar';
  document.getElementById('hidden-fecha-programada').value = fecha;
  document.getElementById('campaign-form').submit();
});
</script>
</body>
</html>
