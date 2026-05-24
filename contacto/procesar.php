<?php
/**
 * Procesador del formulario de contacto (AJAX)
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/brevo.php';

iniciar_sesion_segura();
header('Content-Type: application/json; charset=utf-8');
header_remove('X-Powered-By');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido']);
    exit;
}

// Validar token CSRF
if (!verificar_csrf()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'mensaje' => 'Solicitud no válida. Recarga la página.']);
    exit;
}

// Honeypot: rechazar si el campo no viene (bot usando API directa)
if (!isset($_POST['website'])) {
    echo json_encode(['ok' => true, 'mensaje' => 'Mensaje enviado.']);
    exit;
}
// Honeypot: rechazar si viene con contenido (bot que rellena campos ocultos)
if (!empty($_POST['website'])) {
    echo json_encode(['ok' => true, 'mensaje' => 'Mensaje enviado.']);
    exit;
}

/**
 * Sanitiza un valor para evitar inyección en cabeceras SMTP.
 */
function sanitizar_header(string $valor): string {
    return str_replace(
        ["\r", "\n", "\0", "%0a", "%0d", "%00"],
        '',
        trim($valor)
    );
}

$tipo    = in_array($_POST['tipo'] ?? '', ['centro', 'agencia', 'otro']) ? $_POST['tipo'] : 'otro';
$nombre  = sanitizar_header($_POST['nombre'] ?? '');
$email   = trim($_POST['email'] ?? '');
$telefono = sanitizar_header($_POST['telefono'] ?? '');
$organizacion = sanitizar_header($_POST['organizacion'] ?? '');
$mensaje = trim($_POST['mensaje'] ?? '');
$idioma  = in_array($_POST['idioma'] ?? '', ['es', 'en']) ? $_POST['idioma'] : 'es';
$rgpd    = (int)($_POST['consentimiento_rgpd'] ?? 0);

// Validaciones
if ($nombre === '' || $email === '' || $mensaje === '') {
    $msg = ($idioma === 'en') ? 'Please fill in all required fields.' : 'Rellena todos los campos obligatorios.';
    echo json_encode(['ok' => false, 'mensaje' => $msg]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $msg = ($idioma === 'en') ? 'Please enter a valid email address.' : 'Introduce un email válido.';
    echo json_encode(['ok' => false, 'mensaje' => $msg]);
    exit;
}

if ($rgpd !== 1) {
    $msg = ($idioma === 'en') ? 'You must accept the Privacy Policy.' : 'Debes aceptar la Política de Privacidad.';
    echo json_encode(['ok' => false, 'mensaje' => $msg]);
    exit;
}

$db = get_db();
$ip_hash = hash('sha256', $_SERVER['REMOTE_ADDR'] ?? '');

// Guardar en BD
$stmt = $db->prepare("
    INSERT INTO formularios_contacto
    (tipo, nombre, email, telefono, organizacion, mensaje, idioma, ip_hash, consentimiento_rgpd)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
");
$stmt->execute([$tipo, $nombre, $email, $telefono, $organizacion, $mensaje, $idioma, $ip_hash]);

// Responder al navegador ANTES de enviar emails (para que no haya timeout)
$ok_msg = ($idioma === 'en')
    ? 'Message sent! We will be in touch shortly.'
    : 'Mensaje enviado. Te contactaremos en breve.';

echo json_encode(['ok' => true, 'mensaje' => $ok_msg]);

// Flush response to browser
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
} else {
    if (ob_get_level() > 0) ob_end_flush();
    flush();
}

// ── Enviar emails en segundo plano (el navegador ya recibió su respuesta) ──

try {
    $brevo = new BrevoAPI();
    $tipo_label = match($tipo) { 'centro' => 'Centro escolar', 'agencia' => 'Agencia', default => 'Otro' };
    $destinatario_admin = match($tipo) {
        'centro'  => MAIL_CENTROS,
        'agencia' => MAIL_AGENCIAS,
        default   => MAIL_CONTACT,
    };

    $admin_html = "
    <div style='font-family:system-ui,sans-serif;max-width:560px;margin:0 auto;padding:20px;'>
        <h2 style='color:#0C4A6E;'>Nuevo contacto desde la web</h2>
        <table style='width:100%;border-collapse:collapse;'>
            <tr><td style='padding:8px;border-bottom:1px solid #e2e8f0;font-weight:600;'>Tipo</td><td style='padding:8px;border-bottom:1px solid #e2e8f0;'>{$tipo_label}</td></tr>
            <tr><td style='padding:8px;border-bottom:1px solid #e2e8f0;font-weight:600;'>Nombre</td><td style='padding:8px;border-bottom:1px solid #e2e8f0;'>" . htmlspecialchars($nombre) . "</td></tr>
            <tr><td style='padding:8px;border-bottom:1px solid #e2e8f0;font-weight:600;'>Email</td><td style='padding:8px;border-bottom:1px solid #e2e8f0;'><a href='mailto:" . htmlspecialchars($email) . "'>" . htmlspecialchars($email) . "</a></td></tr>
            <tr><td style='padding:8px;border-bottom:1px solid #e2e8f0;font-weight:600;'>Teléfono</td><td style='padding:8px;border-bottom:1px solid #e2e8f0;'>" . htmlspecialchars($telefono ?: '—') . "</td></tr>
            <tr><td style='padding:8px;border-bottom:1px solid #e2e8f0;font-weight:600;'>Organización</td><td style='padding:8px;border-bottom:1px solid #e2e8f0;'>" . htmlspecialchars($organizacion ?: '—') . "</td></tr>
            <tr><td style='padding:8px;font-weight:600;' colspan='2'>Mensaje:</td></tr>
            <tr><td style='padding:8px;' colspan='2'>" . nl2br(htmlspecialchars($mensaje)) . "</td></tr>
        </table>
        <p style='margin-top:20px;'><a href='" . ADMIN_URL . "/estadisticas.php#formularios' style='background:#0284C7;color:#fff;padding:10px 24px;border-radius:6px;text-decoration:none;display:inline-block;'>Ver en el panel</a></p>
    </div>";

    $brevo->sendTransactional(
        $destinatario_admin,
        MAIL_FROM_NAME,
        "Nuevo contacto [{$tipo_label}] — " . htmlspecialchars($nombre) . " — EuryGo Web",
        $admin_html
    );

    // Email de confirmación al usuario
    if ($idioma === 'en') {
        $conf_subject = 'We have received your message — EuryGo';
        $conf_html = "<div style='font-family:system-ui,sans-serif;max-width:560px;margin:0 auto;padding:20px;'>
            <h2 style='color:#0C4A6E;'>Thank you for contacting us!</h2>
            <p>Hi " . htmlspecialchars($nombre) . ",</p>
            <p>We have received your message and will get back to you within 24–48 hours.</p>
            <p>Best regards,<br>The EuryGo Team</p></div>";
    } else {
        $conf_subject = 'Hemos recibido tu mensaje — EuryGo';
        $conf_html = "<div style='font-family:system-ui,sans-serif;max-width:560px;margin:0 auto;padding:20px;'>
            <h2 style='color:#0C4A6E;'>Gracias por contactarnos</h2>
            <p>Hola " . htmlspecialchars($nombre) . ",</p>
            <p>Hemos recibido tu mensaje. Te contactaremos en las próximas 24–48 horas.</p>
            <p>Un saludo,<br>El equipo de EuryGo</p></div>";
    }

    $brevo->sendTransactional($email, $nombre, $conf_subject, $conf_html);

} catch (Throwable $e) {
    // Log error but don't crash — response already sent
    $logDir = __DIR__ . '/../admin/logs';
    if (!is_dir($logDir)) mkdir($logDir, 0755, true);
    file_put_contents(
        $logDir . '/mail.log',
        '[' . date('Y-m-d H:i:s') . '] FORM ERROR: ' . $e->getMessage() . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
}
