<?php
/**
 * TEST — Verificar que el envío de email funciona.
 * Ejecutar UNA VEZ en el navegador y BORRAR inmediatamente.
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/mailer.php';

$resultado = enviar_email(
    MAIL_NEWSLETTER,
    'EuryGo Newsletter',
    'Test newsletter — ' . date('d/m/Y H:i'),
    '<p>Test de envío del sistema de newsletter de EuryGo.</p>
     <p>Si recibes esto, el SMTP funciona correctamente.</p>'
);

if ($resultado['success']) {
    echo '✅ Email enviado a ' . MAIL_NEWSLETTER;
} else {
    echo '❌ Error al enviar: ' . htmlspecialchars($resultado['error']);
    echo '<br>Revisa admin/logs/mail.log para más detalles.';
}
