<?php
/**
 * Newsletter — Gestión de bajas
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

    $stmt = $db->prepare("SELECT id, email, idioma FROM newsletter_suscriptores WHERE token_baja = ? AND activo = 1");
    $stmt->execute([$token]);
    $sub = $stmt->fetch();

    if (!$sub) {
        $error = true;
    } else {
        $idioma = $sub['idioma'];

        // Dar de baja
        $stmt = $db->prepare("UPDATE newsletter_suscriptores SET activo = 0, fecha_baja = NOW() WHERE id = ?");
        $stmt->execute([$sub['id']]);

        // Eliminar de Brevo
        $listId = ($idioma === 'en') ? BREVO_LIST_ID_EN : BREVO_LIST_ID_ES;
        if ($listId > 0) {
            $brevo = new BrevoAPI();
            $brevo->removeContact($sub['email'], $listId);
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
    <title><?= $idioma === 'en' ? 'Unsubscribed' : 'Baja confirmada' ?> — EuryGo</title>
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
    </style>
</head>
<body>
    <div class="card">
<?php if (!$error): ?>
        <div class="card__icon">&#128075;</div>
        <h1 class="card__title"><?= $idioma === 'en' ? 'You have been unsubscribed' : 'Te has dado de baja correctamente' ?></h1>
        <p class="card__text"><?= $idioma === 'en'
            ? "We're sorry to see you go. If you change your mind, you can always subscribe again from our blog."
            : 'Lamentamos verte partir. Si cambias de opinión, siempre puedes volver a suscribirte desde nuestro blog.' ?></p>
        <a href="<?= $blogUrl ?>" class="card__btn"><?= $idioma === 'en' ? 'Go to the blog' : 'Ir al blog' ?></a>
<?php else: ?>
        <div class="card__icon">&#9888;&#65039;</div>
        <h1 class="card__title"><?= $idioma === 'en' ? 'Invalid link' : 'Enlace no válido' ?></h1>
        <p class="card__text"><?= $idioma === 'en'
            ? 'This unsubscribe link is not valid or has already been used.'
            : 'Este enlace de baja no es válido o ya ha sido utilizado.' ?></p>
        <a href="<?= $blogUrl ?>" class="card__btn"><?= $idioma === 'en' ? 'Go to the blog' : 'Ir al blog' ?></a>
<?php endif; ?>
    </div>
</body>
</html>
