<?php
/**
 * CRM API — Actualización inline de campo único de contacto (AJAX)
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

$id = (int)($_POST['id'] ?? 0);
$campo = $_POST['campo'] ?? '';
$valor = $_POST['valor'] ?? '';

$campos_permitidos = [
    'nombre_centro','tipo_centro','titularidad','pais','comunidad','provincia',
    'municipio','cp','aeropuerto_cercano','volumen_estimado','fiabilidad',
    'paises_docentes','contacto_nombre','contacto_cargo','contacto_telefono',
    'contacto_email','contacto_linkedin','estado','prioridad','tipo_reunion',
    'fecha_proximo_contacto','notas','notas_internas',
];

if (!in_array($campo, $campos_permitidos)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Campo no permitido']);
    exit;
}

if ($valor === '' && in_array($campo, ['fecha_proximo_contacto','fiabilidad'])) $valor = null;

try {
    $db = get_db();
    $stmt = $db->prepare("UPDATE crm_contactos SET $campo = ? WHERE id = ?");
    $stmt->execute([$valor, $id]);
    echo json_encode(['ok' => true]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'DB error']);
}
