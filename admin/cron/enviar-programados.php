<?php
/**
 * Cron Job — Envío de newsletters programados
 *
 * Configurar en OVH: ejecutar cada hora.
 * Comando: php /home/[usuario]/public_html/admin/cron/enviar-programados.php
 */

// Solo ejecutar desde CLI o con token de seguridad
if (php_sapi_name() !== 'cli' && ($_GET['token'] ?? '') !== 'eurygo_cron_2025') {
    http_response_code(403);
    exit('Acceso denegado');
}

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/brevo.php';

$db = get_db();

// Buscar campañas programadas cuya fecha ya pasó
$stmt = $db->query("SELECT * FROM newsletter_campanas WHERE estado = 'programada' AND fecha_programada <= NOW()");
$campanas = $stmt->fetchAll();

if (empty($campanas)) {
    echo "No hay campañas programadas pendientes.\n";
    exit;
}

$brevo = new BrevoAPI();

foreach ($campanas as $c) {
    $listId = ($c['idioma'] === 'en') ? BREVO_LIST_ID_EN : BREVO_LIST_ID_ES;

    if ($listId <= 0) {
        echo "ERROR: Lista de Brevo no configurada para idioma {$c['idioma']}. Campaña #{$c['id']} omitida.\n";
        continue;
    }

    // Contar destinatarios
    $count = $db->prepare("SELECT COUNT(*) FROM newsletter_suscriptores WHERE activo = 1 AND confirmado = 1 AND idioma = ?");
    $count->execute([$c['idioma']]);
    $total = (int)$count->fetchColumn();

    if ($total === 0) {
        echo "Sin suscriptores para idioma {$c['idioma']}. Campaña #{$c['id']} omitida.\n";
        continue;
    }

    // Actualizar estado a 'enviando'
    $db->prepare("UPDATE newsletter_campanas SET estado = 'enviando' WHERE id = ?")->execute([$c['id']]);

    // Crear campaña en Brevo
    $result = $brevo->createCampaign(
        'EuryGo Newsletter ' . date('Y-m-d H:i'),
        $c['asunto'],
        $c['contenido_html'],
        $listId,
        $c['preheader'] ?? ''
    );

    if (!$result['success'] || empty($result['data']['id'])) {
        echo "ERROR creando campaña en Brevo para #{$c['id']}: {$result['error']}\n";
        $db->prepare("UPDATE newsletter_campanas SET estado = 'programada' WHERE id = ?")->execute([$c['id']]);
        continue;
    }

    $brevo_id = (int)$result['data']['id'];
    $db->prepare("UPDATE newsletter_campanas SET brevo_campaign_id = ? WHERE id = ?")->execute([$brevo_id, $c['id']]);

    // Enviar
    $send = $brevo->sendCampaignNow($brevo_id);
    if ($send['success']) {
        $db->prepare("UPDATE newsletter_campanas SET estado = 'enviada', fecha_envio = NOW(), total_enviados = ? WHERE id = ?")
            ->execute([$total, $c['id']]);
        echo "OK: Campaña #{$c['id']} enviada a {$total} suscriptores.\n";
    } else {
        echo "ERROR enviando campaña #{$c['id']}: {$send['error']}\n";
        $db->prepare("UPDATE newsletter_campanas SET estado = 'programada' WHERE id = ?")->execute([$c['id']]);
    }
}
