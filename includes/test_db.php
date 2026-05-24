<?php
/**
 * Test rápido — tablas existentes + error del blog
 * BORRAR DESPUÉS DE USAR
 */
require_once __DIR__ . '/includes/db.php';

echo "<h2>Test de tablas — EuryGo</h2>";
echo "<p>Conexión: OK</p>";

$db = get_db();

// Listar tablas
$tablas = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "<h3>Tablas existentes (" . count($tablas) . "):</h3><ul>";
foreach ($tablas as $t) {
    echo "<li><b>{$t}</b></li>";
}
echo "</ul>";

// Tablas necesarias
$necesarias = ['articulos', 'admin_usuarios', 'estadisticas_visitas', 'newsletter_suscriptores', 'newsletter_campanas', 'formularios_contacto', 'cursos', 'cursos_programa', 'cursos_inscripciones'];
$faltan = array_diff($necesarias, $tablas);
if ($faltan) {
    echo "<p style='color:red'><b>FALTAN estas tablas:</b> " . implode(', ', $faltan) . "</p>";
    echo "<p>Ejecuta los instaladores en este orden:</p><ol>";
    if (in_array('articulos', $faltan) || in_array('admin_usuarios', $faltan)) {
        echo "<li><a href='/admin/setup/install.php'>/admin/setup/install.php</a></li>";
    }
    if (in_array('estadisticas_visitas', $faltan) || in_array('newsletter_suscriptores', $faltan)) {
        echo "<li><a href='/admin/setup/update_v2.php'>/admin/setup/update_v2.php</a></li>";
    }
    if (in_array('cursos', $faltan)) {
        echo "<li><a href='/admin/setup/update_v3.php'>/admin/setup/update_v3.php</a></li>";
    }
    echo "</ol>";
} else {
    echo "<p style='color:green'><b>Todas las tablas existen.</b></p>";
}

// Test del blog
echo "<h3>Test del blog:</h3>";
try {
    require_once __DIR__ . '/includes/funciones.php';
    echo "<p style='color:green'>funciones.php — OK</p>";
} catch (Throwable $e) {
    echo "<p style='color:red'>funciones.php — ERROR: " . htmlspecialchars($e->getMessage()) . " en " . $e->getFile() . ":" . $e->getLine() . "</p>";
}

try {
    require_once __DIR__ . '/includes/tracker.php';
    echo "<p style='color:green'>tracker.php — OK</p>";
} catch (Throwable $e) {
    echo "<p style='color:red'>tracker.php — ERROR: " . htmlspecialchars($e->getMessage()) . " en " . $e->getFile() . ":" . $e->getLine() . "</p>";
}

try {
    registrar_visita($db);
    echo "<p style='color:green'>registrar_visita() — OK</p>";
} catch (Throwable $e) {
    echo "<p style='color:red'>registrar_visita() — ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
}

try {
    $stmt = $db->prepare("SELECT COUNT(*) FROM articulos WHERE publicado = 1 AND idioma = 'es'");
    $stmt->execute();
    $total = $stmt->fetchColumn();
    echo "<p style='color:green'>Artículos ES publicados: <b>{$total}</b></p>";
} catch (Throwable $e) {
    echo "<p style='color:red'>Query artículos — ERROR: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr><p style='color:#92400e'><b>BORRA este archivo del servidor.</b></p>";
