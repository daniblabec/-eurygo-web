<?php
/**
 * Endpoint que devuelve un token CSRF para formularios públicos.
 */
require_once __DIR__ . '/../includes/auth.php';

iniciar_sesion_segura();
header('Content-Type: application/json; charset=utf-8');

echo json_encode(['token' => generar_csrf()]);
