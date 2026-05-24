<?php
/**
 * Tracking de visitas propio, sin cookies, cumplimiento RGPD.
 * IP hasheada con sal diaria — no se almacena la IP real.
 * No requiere consentimiento de cookies.
 */

function registrar_visita(PDO $db, ?int $articulo_id = null): void {
    // No trackear bots conocidos
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    if (preg_match('/bot|crawler|spider|slurp|bingbot|googlebot|yandex|baidu|semrush|ahref/i', $ua)) {
        return;
    }

    // IP hasheada con sal diaria (no almacenamos la IP real)
    $ip_hash = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? '') . date('Y-m-d'));

    $url = $_SERVER['REQUEST_URI'] ?? '';

    // Evitar duplicados: misma IP+URL en el mismo día
    $stmt = $db->prepare("
        SELECT id FROM estadisticas_visitas
        WHERE url = ? AND ip_hash = ? AND fecha = CURDATE()
        LIMIT 1
    ");
    $stmt->execute([$url, $ip_hash]);
    if ($stmt->fetch()) {
        return;
    }

    $idioma = (strpos($url, '/en/') === 0) ? 'en' : 'es';

    $stmt = $db->prepare("
        INSERT INTO estadisticas_visitas
        (url, articulo_id, idioma, referrer, user_agent, ip_hash, fecha, hora)
        VALUES (?, ?, ?, ?, ?, ?, CURDATE(), HOUR(NOW()))
    ");
    $stmt->execute([
        $url,
        $articulo_id,
        $idioma,
        substr($_SERVER['HTTP_REFERER'] ?? '', 0, 500),
        substr($ua, 0, 500),
        $ip_hash,
    ]);
}
