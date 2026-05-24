<?php
/**
 * Endpoint AJAX para inscripción en cursos (ES + EN)
 * POST con: csrf_token, curso_id, nombre, email, telefono, organizacion, pais, mensaje, rgpd
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/brevo.php';

iniciar_sesion_segura();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['ok' => false, 'error' => 'Método no permitido.']);
    exit;
}

// CSRF
if (!verificar_csrf()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Token de seguridad inválido. Recarga la página.']);
    exit;
}

/**
 * Sanitiza un valor para evitar inyección en cabeceras SMTP.
 */
function sanitizar_header_insc(string $valor): string {
    return str_replace(
        ["\r", "\n", "\0", "%0a", "%0d", "%00"],
        '',
        trim($valor)
    );
}

// Validar campos
$curso_id     = (int)($_POST['curso_id'] ?? 0);
$nombre       = sanitizar_header_insc($_POST['nombre'] ?? '');
$email        = trim($_POST['email'] ?? '');
$telefono     = sanitizar_header_insc($_POST['telefono'] ?? '');
$organizacion = sanitizar_header_insc($_POST['organizacion'] ?? '');
$pais         = sanitizar_header_insc($_POST['pais'] ?? '');
$mensaje      = trim($_POST['mensaje'] ?? '');
$edicion_id   = (int)($_POST['edicion_id'] ?? 0);
$rgpd         = !empty($_POST['rgpd']);

if (!$curso_id || !$nombre || !$email || !$pais) {
    echo json_encode(['ok' => false, 'error' => 'Rellena todos los campos obligatorios.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['ok' => false, 'error' => 'Email no válido.']);
    exit;
}

if (!$rgpd) {
    echo json_encode(['ok' => false, 'error' => 'Debes aceptar la política de privacidad.']);
    exit;
}

$db = get_db();

// Verificar que el curso existe y tiene plazas
$stmt = $db->prepare("SELECT * FROM cursos WHERE id = :id AND estado = 'publicado' LIMIT 1");
$stmt->execute([':id' => $curso_id]);
$curso = $stmt->fetch();

if (!$curso) {
    echo json_encode(['ok' => false, 'error' => 'Curso no encontrado.']);
    exit;
}

// Si hay edicion_id, verificar plazas por edición
$edicion = null;
if ($edicion_id) {
    $stmt = $db->prepare("SELECT * FROM cursos_ediciones WHERE id = :id AND curso_id = :cid AND estado = 'abierta' LIMIT 1");
    $stmt->execute([':id' => $edicion_id, ':cid' => $curso_id]);
    $edicion = $stmt->fetch();
}

$plazas_disponibles = $edicion ? $edicion['plazas_disponibles'] : ($curso['plazas'] - $curso['inscritos']);
$estado_inscripcion = $plazas_disponibles > 0 ? 'pendiente' : 'lista_espera';

// Verificar duplicados
$dup_sql = "SELECT id FROM cursos_inscripciones WHERE curso_id = :cid AND email = :email";
$dup_params = [':cid' => $curso_id, ':email' => $email];
if ($edicion_id) {
    $dup_sql .= " AND edicion_id = :eid";
    $dup_params[':eid'] = $edicion_id;
}
$dup_sql .= " LIMIT 1";
$stmt = $db->prepare($dup_sql);
$stmt->execute($dup_params);
if ($stmt->fetch()) {
    echo json_encode(['ok' => false, 'error' => 'Ya existe una inscripción con este email para este curso.']);
    exit;
}

// Insertar inscripción
$stmt = $db->prepare("
    INSERT INTO cursos_inscripciones (curso_id, edicion_id, nombre, email, telefono, organizacion, pais, mensaje, estado, rgpd)
    VALUES (:curso_id, :edicion_id, :nombre, :email, :telefono, :organizacion, :pais, :mensaje, :estado, 1)
");
$stmt->execute([
    ':curso_id'     => $curso_id,
    ':edicion_id'   => $edicion_id ?: null,
    ':nombre'       => $nombre,
    ':email'        => $email,
    ':telefono'     => $telefono,
    ':organizacion' => $organizacion,
    ':pais'         => $pais,
    ':mensaje'      => $mensaje,
    ':estado'       => $estado_inscripcion,
]);

// Actualizar plazas disponibles
if ($plazas_disponibles > 0) {
    if ($edicion_id && $edicion) {
        $db->prepare("UPDATE cursos_ediciones SET plazas_disponibles = plazas_disponibles - 1 WHERE id = :id AND plazas_disponibles > 0")->execute([':id' => $edicion_id]);
    }
    $db->prepare("UPDATE cursos SET inscritos = inscritos + 1 WHERE id = :id")->execute([':id' => $curso_id]);
}

// Fechas de la edición para los emails
$email_fecha_inicio = $edicion ? $edicion['fecha_inicio'] : $curso['fecha_inicio'];
$email_fecha_fin = $edicion ? $edicion['fecha_fin'] : $curso['fecha_fin'];

// Detectar idioma del curso para emails
$es_en = $curso['idioma'] === 'en' ? 'en' : 'es';

// Email de confirmación al usuario
$asunto_user = $es_en === 'en'
    ? 'Enrollment received — ' . $curso['titulo']
    : 'Inscripción recibida — ' . $curso['titulo'];

$cuerpo_user = $es_en === 'en'
    ? "<h2>Thank you for your enrollment, {$nombre}!</h2>
       <p>We have received your enrollment request for the course <strong>{$curso['titulo']}</strong>.</p>
       <p><strong>Dates:</strong> {$email_fecha_inicio} — {$email_fecha_fin}<br>
       <strong>Location:</strong> {$curso['ubicacion']}</p>
       <p>" . ($estado_inscripcion === 'lista_espera' ? 'You have been added to the <strong>waiting list</strong>. We will contact you if a spot becomes available.' : 'We will contact you shortly to confirm your spot and provide further details.') . "</p>
       <p>Best regards,<br>The EuryGo Team</p>"
    : "<h2>¡Gracias por tu inscripción, {$nombre}!</h2>
       <p>Hemos recibido tu solicitud de inscripción para el curso <strong>{$curso['titulo']}</strong>.</p>
       <p><strong>Fechas:</strong> {$email_fecha_inicio} — {$email_fecha_fin}<br>
       <strong>Ubicación:</strong> {$curso['ubicacion']}</p>
       <p>" . ($estado_inscripcion === 'lista_espera' ? 'Has sido añadido/a a la <strong>lista de espera</strong>. Te contactaremos si se libera una plaza.' : 'Nos pondremos en contacto contigo para confirmar tu plaza y darte más detalles.') . "</p>
       <p>Un saludo,<br>El equipo de EuryGo</p>";

// Responder al navegador ANTES de enviar emails
$mensaje_ok = $es_en === 'en'
    ? ($estado_inscripcion === 'lista_espera'
        ? 'You have been added to the waiting list. We will contact you if a spot becomes available.'
        : 'Enrollment received successfully! We will contact you shortly to confirm your spot.')
    : ($estado_inscripcion === 'lista_espera'
        ? 'Has sido añadido/a a la lista de espera. Te contactaremos si se libera una plaza.'
        : '¡Inscripción recibida correctamente! Nos pondremos en contacto contigo para confirmar tu plaza.');

echo json_encode(['ok' => true, 'mensaje' => $mensaje_ok]);

// Flush response to browser
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
} else {
    if (ob_get_level() > 0) ob_end_flush();
    flush();
}

// ── Enviar emails en segundo plano ──
try {
    $brevo = new BrevoAPI();
    $brevo->sendTransactional($email, $nombre, $asunto_user, $cuerpo_user);

    // Email de notificación al admin
    $asunto_admin = "Nueva inscripción: {$curso['titulo']} — " . htmlspecialchars($nombre);
    $cuerpo_admin = "<h2>Nueva inscripción en curso</h2>
        <p><strong>Curso:</strong> " . htmlspecialchars($curso['titulo']) . "</p>
        <p><strong>Nombre:</strong> " . htmlspecialchars($nombre) . "</p>
        <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
        <p><strong>Teléfono:</strong> " . htmlspecialchars($telefono) . "</p>
        <p><strong>Organización:</strong> " . htmlspecialchars($organizacion) . "</p>
        <p><strong>País:</strong> " . htmlspecialchars($pais) . "</p>
        <p><strong>Mensaje:</strong> " . htmlspecialchars($mensaje) . "</p>
        <p><strong>Estado:</strong> {$estado_inscripcion}</p>
        <p><strong>Plazas restantes:</strong> " . max(0, $plazas_disponibles - 1) . " / {$curso['plazas']}</p>";

    $brevo->sendTransactional(MAIL_CURSOS, MAIL_FROM_NAME, $asunto_admin, $cuerpo_admin);
} catch (Throwable $e) {
    $logDir = __DIR__ . '/../admin/logs';
    if (!is_dir($logDir)) mkdir($logDir, 0755, true);
    file_put_contents(
        $logDir . '/mail.log',
        '[' . date('Y-m-d H:i:s') . '] ENROLLMENT ERROR: ' . $e->getMessage() . ' — Email: ' . $email . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
}
