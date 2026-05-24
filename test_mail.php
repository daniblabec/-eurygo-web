<?php
/**
 * Test directo de procesar.php — BORRAR DESPUÉS DE USAR
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>Test directo de procesar.php</h2>";

// Simular un POST a procesar.php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = [
    'tipo'                 => 'centro',
    'nombre'               => 'Test Directo',
    'email'                => 'info@eurygo.eu',
    'telefono'             => '600000000',
    'organizacion'         => 'Test Org',
    'mensaje'              => 'Esto es un test directo de procesar.php',
    'idioma'               => 'es',
    'consentimiento_rgpd'  => '1',
];

echo "<p>Simulando POST con datos de test...</p>";
echo "<pre>";

// Capturar la salida
ob_start();
try {
    require __DIR__ . '/contacto/procesar.php';
} catch (Throwable $e) {
    echo "\n\nEXCEPCIÓN: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString();
}
$output = ob_get_clean();

echo htmlspecialchars($output);
echo "</pre>";

echo "<hr><p style='color:#92400e'><b>BORRA este archivo del servidor.</b></p>";
