<?php
/**
 * Newsletter — Endpoint POST de suscripción (AJAX)
 *
 * 5 CAPAS DE PROTECCIÓN ANTI-BOT:
 *   1. Honeypot 'website' (bot debe no rellenarlo y debe enviarlo)
 *   2. Tiempo mínimo de relleno (form_time, mínimo 3s)
 *   3. Rate limit por IP (3 intentos / hora)
 *   4. Validación de nombre (vocales, longitud, patrones)
 *   5. Cloudflare Turnstile (si está configurado)
 *
 * Si cualquier capa detecta un bot → silent discard:
 *   responde {ok:true} para no dar pistas pero no guarda nada.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/brevo.php';

header('Content-Type: application/json; charset=utf-8');

// Mensaje de éxito que se devuelve siempre para silent-discard (no da pistas al bot)
function silent_ok(string $idioma = 'es'): void {
    echo json_encode(['ok' => true, 'mensaje' => $idioma === 'en'
        ? 'Almost done! Check your inbox to confirm your subscription.'
        : 'Casi listo. Revisa tu bandeja de entrada para confirmar tu suscripción.']);
    exit;
}

// ─── Solo POST ───
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'mensaje' => 'Método no permitido']);
    exit;
}

$idioma = in_array($_POST['idioma'] ?? '', ['es', 'en']) ? $_POST['idioma'] : 'es';

// ═══════════════════════════════════════════════════════════════
// CAPA 1 — HONEYPOT
// ═══════════════════════════════════════════════════════════════
// El campo 'website' debe estar presente (formulario completo)
// y vacío (humanos no lo ven, bots lo rellenan)
if (!isset($_POST['website']) || !empty($_POST['website'])) {
    silent_ok($idioma);
}

// ═══════════════════════════════════════════════════════════════
// CAPA 2 — TIEMPO MÍNIMO DE RELLENO (mínimo 3 segundos)
// ═══════════════════════════════════════════════════════════════
$form_time = (int)($_POST['form_time'] ?? 0);
if ($form_time > 0) {
    $delta = time() - $form_time;
    // Demasiado rápido (< 3s) o sospechosamente futuro/lejano
    if ($delta < 3 || $delta > 86400) {
        silent_ok($idioma);
    }
}
// Si form_time no llega (formularios antiguos cacheados), seguimos con resto de capas

// ═══════════════════════════════════════════════════════════════
// CAPA 3 — RATE LIMIT POR IP (3 intentos / hora)
// ═══════════════════════════════════════════════════════════════
function check_rate_limit_newsletter(PDO $db, string $ip): bool {
    $ip_hash = hash('sha256', $ip);

    // Asegurar que la tabla existe (no falla si no — silenciosa)
    try {
        // Limpiar registros antiguos (> 1h)
        $db->prepare("DELETE FROM newsletter_rate_limit WHERE fecha < DATE_SUB(NOW(), INTERVAL 1 HOUR)")
           ->execute();

        // Contar intentos recientes
        $stmt = $db->prepare("SELECT COUNT(*) FROM newsletter_rate_limit
                              WHERE ip_hash = ? AND fecha > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $stmt->execute([$ip_hash]);
        $intentos = (int)$stmt->fetchColumn();

        if ($intentos >= 3) return false;

        // Registrar este intento
        $db->prepare("INSERT INTO newsletter_rate_limit (ip_hash, fecha) VALUES (?, NOW())")
           ->execute([$ip_hash]);

        return true;
    } catch (PDOException $e) {
        // Si la tabla no existe, no bloquear (degradación)
        return true;
    }
}

$db = get_db();
$ip = $_SERVER['REMOTE_ADDR'] ?? '';

if (!check_rate_limit_newsletter($db, $ip)) {
    silent_ok($idioma);
}

// ═══════════════════════════════════════════════════════════════
// CAPA 4 — VALIDACIÓN DE NOMBRE
// ═══════════════════════════════════════════════════════════════
function es_nombre_valido(string $nombre): bool {
    $nombre = trim($nombre);

    // Nombre vacío es OK (campo opcional)
    if ($nombre === '') return true;

    // Mínimo 2 caracteres
    if (mb_strlen($nombre) < 2) return false;

    // Máximo 60 caracteres
    if (mb_strlen($nombre) > 60) return false;

    // Debe contener al menos una vocal (nombres reales las tienen)
    if (!preg_match('/[aeiouáéíóúüàèìòùAEIOUÁÉÍÓÚ]/u', $nombre)) {
        return false;
    }

    // Patrón: minúsculas seguidas sin espacios (10+ chars) → bot
    if (preg_match('/^[a-z]{10,}$/', $nombre)) return false;

    // Más de 5 consonantes seguidas → bot
    if (preg_match('/[bcdfghjklmnpqrstvwxyz]{6,}/i', $nombre)) return false;

    return true;
}

/**
 * Sanitiza un valor para evitar inyección en cabeceras SMTP.
 */
function sanitizar_header_nl(string $valor): string {
    return str_replace(
        ["\r", "\n", "\0", "%0a", "%0d", "%00"],
        '',
        trim($valor)
    );
}

$nombre = sanitizar_header_nl($_POST['nombre'] ?? '');
if (!es_nombre_valido($nombre)) {
    silent_ok($idioma);
}

// ═══════════════════════════════════════════════════════════════
// CAPA 5 — CLOUDFLARE TURNSTILE (opcional, solo si está configurado)
// ═══════════════════════════════════════════════════════════════
function verificar_turnstile(string $token, string $ip): bool {
    if (!defined('TURNSTILE_SECRET') || empty($token)) return false;
    $secret = constant('TURNSTILE_SECRET');
    if (!$secret || strpos($secret, 'TU_') === 0) return false;

    $ctx = stream_context_create(['http' => [
        'method'  => 'POST',
        'header'  => 'Content-Type: application/x-www-form-urlencoded',
        'content' => http_build_query([
            'secret'   => $secret,
            'response' => $token,
            'remoteip' => $ip,
        ]),
        'timeout' => 5,
        'ignore_errors' => true,
    ]]);
    $resp = @file_get_contents('https://challenges.cloudflare.com/turnstile/v0/siteverify', false, $ctx);
    if ($resp === false) return false;
    $data = json_decode($resp, true);
    return $data['success'] ?? false;
}

if (defined('TURNSTILE_SECRET') &&
    constant('TURNSTILE_SECRET') &&
    strpos(constant('TURNSTILE_SECRET'), 'TU_') !== 0) {
    $token = $_POST['cf_turnstile_response'] ?? '';
    if (!verificar_turnstile($token, $ip)) {
        silent_ok($idioma);
    }
}

// ═══════════════════════════════════════════════════════════════
// VALIDACIONES NORMALES (email, RGPD)
// ═══════════════════════════════════════════════════════════════
$email = trim($_POST['email'] ?? '');
$rgpd  = (int)($_POST['consentimiento_rgpd'] ?? 0);

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok' => false, 'mensaje' => $idioma === 'en'
        ? 'Please enter a valid email address.'
        : 'Introduce un email válido.']);
    exit;
}

if ($rgpd !== 1) {
    echo json_encode(['ok' => false, 'mensaje' => $idioma === 'en'
        ? 'You must accept the Privacy Policy.'
        : 'Debes aceptar la Política de Privacidad.']);
    exit;
}

// ═══════════════════════════════════════════════════════════════
// LÓGICA DE NEGOCIO (igual que antes)
// ═══════════════════════════════════════════════════════════════

// Comprobar si ya existe
$stmt = $db->prepare("SELECT id, confirmado, activo FROM newsletter_suscriptores WHERE email = ?");
$stmt->execute([$email]);
$existente = $stmt->fetch();

if ($existente) {
    if ($existente['confirmado'] && $existente['activo']) {
        echo json_encode(['ok' => true, 'mensaje' => $idioma === 'en' ? 'You are already subscribed!' : 'Ya estás suscrito/a.']);
        exit;
    }

    if (!$existente['confirmado']) {
        // Reenviar email de confirmación
        $token_conf = bin2hex(random_bytes(32));
        $stmt = $db->prepare("UPDATE newsletter_suscriptores SET token_confirmacion = ?, fecha_suscripcion = NOW() WHERE id = ?");
        $stmt->execute([$token_conf, $existente['id']]);

        enviar_email_confirmacion($email, $nombre, $idioma, $token_conf);

        echo json_encode(['ok' => true, 'mensaje' => $idioma === 'en'
            ? 'We have resent the confirmation email. Check your inbox.'
            : 'Hemos reenviado el email de confirmación. Revisa tu bandeja de entrada.']);
        exit;
    }

    // Era baja, reactivar
    $token_conf = bin2hex(random_bytes(32));
    $token_baja = bin2hex(random_bytes(32));
    $stmt = $db->prepare("UPDATE newsletter_suscriptores SET activo = 1, confirmado = 0, token_confirmacion = ?, token_baja = ?, fecha_suscripcion = NOW(), fecha_baja = NULL, consentimiento_rgpd = 1, timestamp_consentimiento = NOW() WHERE id = ?");
    $stmt->execute([$token_conf, $token_baja, $existente['id']]);

    enviar_email_confirmacion($email, $nombre, $idioma, $token_conf);

    echo json_encode(['ok' => true, 'mensaje' => $idioma === 'en'
        ? 'Check your inbox to confirm your subscription.'
        : 'Revisa tu bandeja de entrada para confirmar tu suscripción.']);
    exit;
}

// Nuevo suscriptor
$token_conf = bin2hex(random_bytes(32));
$token_baja = bin2hex(random_bytes(32));
$ip_hash    = hash('sha256', $ip);

$stmt = $db->prepare("
    INSERT INTO newsletter_suscriptores
    (email, nombre, idioma, origen, token_confirmacion, token_baja, ip_suscripcion, consentimiento_rgpd, timestamp_consentimiento)
    VALUES (?, ?, ?, 'web', ?, ?, ?, 1, NOW())
");
$stmt->execute([$email, $nombre, $idioma, $token_conf, $token_baja, $ip_hash]);

enviar_email_confirmacion($email, $nombre, $idioma, $token_conf);

echo json_encode(['ok' => true, 'mensaje' => $idioma === 'en'
    ? 'Almost done! Check your inbox to confirm your subscription.'
    : 'Casi listo. Revisa tu bandeja de entrada para confirmar tu suscripción.']);

// ─── Función helper ───

function enviar_email_confirmacion(string $email, string $nombre, string $idioma, string $token): void {
    $link = NEWSLETTER_CONFIRM_URL . '?token=' . $token;

    if ($idioma === 'en') {
        $subject = 'Confirm your EuryGo Newsletter subscription';
        $saludo  = $nombre ?: 'Erasmus+ enthusiast';
        $html    = "
        <div style='font-family:system-ui,sans-serif;max-width:560px;margin:0 auto;padding:20px;'>
            <h2 style='color:#0C4A6E;'>Welcome to EuryGo Newsletter!</h2>
            <p>Hi {$saludo},</p>
            <p>Thank you for subscribing to the EuryGo newsletter.</p>
            <p>To confirm your subscription and start receiving guides, news and success stories about Erasmus+, click the button below:</p>
            <p style='text-align:center;margin:30px 0;'>
                <a href='{$link}' style='background:#0284C7;color:#fff;padding:14px 32px;border-radius:6px;text-decoration:none;font-weight:600;display:inline-block;'>Confirm my subscription</a>
            </p>
            <p style='font-size:0.85rem;color:#64748B;'>This link expires in 48 hours.</p>
            <p style='font-size:0.85rem;color:#64748B;'>If you did not request this subscription, simply ignore this message.</p>
            <hr style='border:none;border-top:1px solid #e2e8f0;margin:30px 0;'>
            <p style='font-size:0.8rem;color:#94a3b8;'>— The EuryGo Team</p>
        </div>";
    } else {
        $subject = 'Confirma tu suscripción a EuryGo Newsletter';
        $saludo  = $nombre ?: 'apasionado/a de Erasmus+';
        $html    = "
        <div style='font-family:system-ui,sans-serif;max-width:560px;margin:0 auto;padding:20px;'>
            <h2 style='color:#0C4A6E;'>Bienvenido/a a EuryGo Newsletter</h2>
            <p>Hola {$saludo},</p>
            <p>Gracias por suscribirte al newsletter de EuryGo.</p>
            <p>Para confirmar tu suscripción y empezar a recibir guías, novedades y casos de éxito sobre Erasmus+, haz clic en el botón:</p>
            <p style='text-align:center;margin:30px 0;'>
                <a href='{$link}' style='background:#0284C7;color:#fff;padding:14px 32px;border-radius:6px;text-decoration:none;font-weight:600;display:inline-block;'>Confirmar mi suscripción</a>
            </p>
            <p style='font-size:0.85rem;color:#64748B;'>Este enlace caduca en 48 horas.</p>
            <p style='font-size:0.85rem;color:#64748B;'>Si no solicitaste esta suscripción, ignora este mensaje.</p>
            <hr style='border:none;border-top:1px solid #e2e8f0;margin:30px 0;'>
            <p style='font-size:0.8rem;color:#94a3b8;'>— El equipo de EuryGo</p>
        </div>";
    }

    $brevo = new BrevoAPI();
    $brevo->sendTransactional($email, $nombre ?: $email, $subject, $html, '', MAIL_FROM, MAIL_FROM_NAME);
}
