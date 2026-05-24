<?php
/**
 * CRM API — Exportación CSV (delegado desde centros.php / agencias.php)
 * Esta API mantiene la compatibilidad con el endpoint nombrado /api/exportar.php
 * Redirecciona al endpoint principal con los parámetros adecuados.
 */
require_once __DIR__ . '/../../../includes/auth.php';
requiere_login();

$pata = ($_GET['pata'] ?? 'centros') === 'agencias' ? 'agencias' : 'centros';
$qs = http_build_query(array_merge($_GET, ['export' => 'csv']));
header("Location: /admin/crm/{$pata}.php?{$qs}");
exit;
