<?php
/**
 * Envío de email vía SMTP (OVH) usando PHPMailer.
 * Fallback a mail() nativo si PHPMailer falla.
 *
 * IMPORTANTE: OVH requiere que el From coincida con el usuario autenticado.
 * Por eso siempre enviamos FROM info@eurygo.com y usamos Reply-To
 * para indicar la dirección de respuesta deseada.
 */

require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Envía un email vía SMTP.
 * Si PHPMailer falla, intenta con mail() nativo como fallback.
 *
 * @param string      $toEmail      Destinatario
 * @param string      $toName       Nombre del destinatario
 * @param string      $subject      Asunto
 * @param string      $htmlBody     Cuerpo HTML
 * @param string|null $fromEmail    Reply-To deseado (null = MAIL_FROM)
 * @param string|null $fromName     Nombre remitente (null = MAIL_FROM_NAME)
 * @param string|null $replyTo      Reply-To explícito (null = se usa $fromEmail)
 * @return array      ['success' => bool, 'error' => string]
 */
function enviar_email(
    string $toEmail,
    string $toName,
    string $subject,
    string $htmlBody,
    ?string $fromEmail = null,
    ?string $fromName = null,
    ?string $replyTo = null
): array {
    $mail = new PHPMailer(true);

    try {
        // SMTP
        $mail->isSMTP();
        $mail->Host       = MAIL_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = MAIL_USER;
        $mail->Password   = MAIL_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // STARTTLS puerto 587
        $mail->Port       = MAIL_PORT;
        $mail->CharSet    = 'UTF-8';
        $mail->Encoding   = 'base64';

        // OVH exige que From = usuario autenticado
        $mail->setFrom(MAIL_USER, $fromName ?? MAIL_FROM_NAME);

        // Reply-To: la dirección "lógica" del remitente
        $reply = $replyTo ?? $fromEmail ?? MAIL_FROM;
        if ($reply !== MAIL_USER) {
            $mail->addReplyTo($reply, $fromName ?? MAIL_FROM_NAME);
        }

        // Destinatario
        $mail->addAddress($toEmail, $toName);

        // Contenido
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $htmlBody;
        $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $htmlBody));

        $mail->send();
        return ['success' => true, 'error' => ''];

    } catch (Exception $e) {
        $errorSmtp = $mail->ErrorInfo;

        // Log del error SMTP
        _log_mail_error('SMTP', $errorSmtp, $toEmail, $subject);

        // ── Fallback: mail() nativo ──
        return enviar_email_nativo($toEmail, $toName, $subject, $htmlBody, $fromName ?? MAIL_FROM_NAME, $errorSmtp);
    }
}

/**
 * Fallback con mail() nativo de PHP.
 */
function enviar_email_nativo(
    string $toEmail,
    string $toName,
    string $subject,
    string $htmlBody,
    string $fromName,
    string $smtpError
): array {
    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: {$fromName} <" . MAIL_USER . ">\r\n";
    $headers .= "Reply-To: " . MAIL_FROM . "\r\n";

    $to = $toName ? "{$toName} <{$toEmail}>" : $toEmail;

    if (@mail($to, $subject, $htmlBody, $headers)) {
        _log_mail_error('SMTP→mail() OK', "SMTP falló ({$smtpError}), mail() funcionó", $toEmail, $subject);
        return ['success' => true, 'error' => ''];
    }

    _log_mail_error('mail()', 'mail() también falló tras error SMTP: ' . $smtpError, $toEmail, $subject);
    return ['success' => false, 'error' => 'SMTP: ' . $smtpError . ' | mail(): también falló'];
}

/**
 * Log interno de errores de email.
 */
function _log_mail_error(string $method, string $error, string $to, string $subject): void {
    $logDir = __DIR__ . '/../admin/logs';
    if (!is_dir($logDir)) { mkdir($logDir, 0755, true); }
    file_put_contents(
        $logDir . '/mail.log',
        '[' . date('Y-m-d H:i:s') . '] ' . $method . ' | ' . $error . ' | To: ' . $to . ' | Subject: ' . $subject . PHP_EOL,
        FILE_APPEND | LOCK_EX
    );
}
