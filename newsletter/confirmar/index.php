<?php
/**
 * Newsletter — Confirmación doble opt-in
 */

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/brevo.php';

$token = trim($_GET['token'] ?? '');
$error = false;
$idioma = 'es';

if (empty($token) || strlen($token) !== 64) {
    $error = true;
} else {
    $db = get_db();

    $stmt = $db->prepare("
        SELECT id, email, nombre, idioma, fecha_suscripcion
        FROM newsletter_suscriptores
        WHERE token_confirmacion = ? AND confirmado = 0
    ");
    $stmt->execute([$token]);
    $sub = $stmt->fetch();

    if (!$sub) {
        $error = true;
    } elseif (strtotime($sub['fecha_suscripcion']) < time() - 48 * 3600) {
        // Token caducado (más de 48h)
        $error = true;
        $idioma = $sub['idioma'];
    } else {
        $idioma = $sub['idioma'];

        // Confirmar suscripción
        $stmt = $db->prepare("UPDATE newsletter_suscriptores SET confirmado = 1, fecha_confirmacion = NOW(), token_confirmacion = NULL WHERE id = ?");
        $stmt->execute([$sub['id']]);

        // Añadir a Brevo
        $listId = ($idioma === 'en') ? BREVO_LIST_ID_EN : BREVO_LIST_ID_ES;
        if ($listId > 0) {
            $brevo = new BrevoAPI();
            $brevo->addContact($sub['email'], $sub['nombre'] ?? '', $listId);
        }
    }
}

$blogUrl = ($idioma === 'en') ? '/en/blog/' : '/blog/';
?>
<!DOCTYPE html>
<html lang="<?= $idioma ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $error ? ($idioma === 'en' ? 'Error' : 'Error') : ($idioma === 'en' ? 'Subscription Confirmed' : 'Suscripción confirmada') ?> — EuryGo</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'DM Sans', system-ui, sans-serif; background: #F8F9FA; color: #1a1a1a; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .card { background: #fff; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); max-width: 500px; width: 100%; padding: 3rem 2.5rem; text-align: center; }
        .card__icon { font-size: 3rem; margin-bottom: 1rem; }
        .card__title { font-size: 1.5rem; font-weight: 700; color: #0C4A6E; margin-bottom: 0.75rem; }
        .card__text { color: #64748B; line-height: 1.6; margin-bottom: 1.5rem; }
        .card__btn { display: inline-block; background: #0284C7; color: #fff; padding: 0.75rem 2rem; border-radius: 6px; text-decoration: none; font-weight: 600; transition: background 0.2s; }
        .card__btn:hover { background: #0C4A6E; }
        .card__btn--outline { background: transparent; border: 2px solid #0284C7; color: #0284C7; }
        .card__btn--outline:hover { background: #0284C7; color: #fff; }
    </style>
</head>
<body>
    <div class="card">
<?php if (!$error): ?>
        <div class="card__icon">&#10004;&#65039;</div>
        <h1 class="card__title"><?= $idioma === 'en' ? 'Welcome to EuryGo Newsletter!' : 'Bienvenido/a a EuryGo Newsletter' ?></h1>
        <p class="card__text"><?= $idioma === 'en'
            ? 'Your subscription has been confirmed. Every month you will receive guides, calls and Erasmus+ news directly in your inbox.'
            : 'Tu suscripción ha sido confirmada. Cada mes recibirás guías, convocatorias y novedades Erasmus+ directamente en tu correo.' ?></p>
        <a href="<?= $blogUrl ?>" class="card__btn"><?= $idioma === 'en' ? 'Go to the blog' : 'Ir al blog' ?></a>
<?php else: ?>
        <div class="card__icon">&#9888;&#65039;</div>
        <h1 class="card__title"><?= $idioma === 'en' ? 'Invalid or expired link' : 'Enlace inválido o caducado' ?></h1>
        <p class="card__text"><?= $idioma === 'en'
            ? 'This confirmation link is not valid or has expired (48 hours). You can subscribe again from our blog.'
            : 'Este enlace de confirmación no es válido o ha caducado (48 horas). Puedes volver a suscribirte desde nuestro blog.' ?></p>
        <a href="<?= $blogUrl ?>" class="card__btn card__btn--outline"><?= $idioma === 'en' ? 'Go to the blog' : 'Ir al blog' ?></a>
<?php endif; ?>
    </div>
</body>
</html>
