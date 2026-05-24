<?php
/**
 * CRM API — Registrar nueva acción/actividad (AJAX)
 */
require_once __DIR__ . '/../../../includes/db.php';
require_once __DIR__ . '/../../../includes/auth.php';
requiere_login();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verificar_csrf()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'CSRF']);
    exit;
}

$cid = (int)($_POST['contacto_id'] ?? 0);
$tipo = $_POST['tipo'] ?? 'llamada';
$resultado = $_POST['resultado'] ?? 'pendiente';
$resumen = trim($_POST['resumen'] ?? '');
$proximo = trim($_POST['proximo_paso'] ?? '');
$fecha_seg = $_POST['fecha_seguimiento'] ?: null;
$nuevo_estado = $_POST['nuevo_estado'] ?? '';

if (!$cid || !$resumen) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Faltan datos']);
    exit;
}

try {
    $db = get_db();
    $db->prepare("INSERT INTO crm_actividad
        (contacto_id, tipo, fecha, resultado, resumen, proximo_paso, fecha_seguimiento)
        VALUES (?, ?, NOW(), ?, ?, ?, ?)")
       ->execute([$cid, $tipo, $resultado, $resumen, $proximo, $fecha_seg]);

    $upd = ["fecha_ultimo_contacto = CURDATE()"];
    $params = [':id' => $cid];
    if ($fecha_seg) {
        $upd[] = "fecha_proximo_contacto = :prox";
        $params[':prox'] = $fecha_seg;
    }
    if ($nuevo_estado) {
        $upd[] = "estado = :estado";
        $params[':estado'] = $nuevo_estado;
    }
    $db->prepare("UPDATE crm_contactos SET " . implode(', ', $upd) . " WHERE id = :id")->execute($params);

    echo json_encode(['ok' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'DB error']);
}
