<?php
/**
 * DEBUG TEMPORAL — Diagnóstico de home_imagenes
 * BORRAR DESPUÉS DE USAR
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requiere_login();

header('Content-Type: text/html; charset=utf-8');
$db = get_db();

echo '<h2>Tabla home_imagenes</h2>';
$stmt = $db->query("SELECT * FROM home_imagenes");
$rows = $stmt->fetchAll();

if (empty($rows)) {
    echo '<p style="color:red;font-weight:bold;">LA TABLA ESTÁ VACÍA — no hay ningún registro.</p>';
} else {
    echo '<table border="1" cellpadding="8" cellspacing="0" style="border-collapse:collapse;font-family:monospace;">';
    echo '<tr style="background:#eee;"><th>id</th><th>posicion</th><th>titulo</th><th>ruta_imagen</th><th>alt_texto</th><th>activa</th><th>orden</th></tr>';
    foreach ($rows as $r) {
        $ruta_color = empty($r['ruta_imagen']) ? 'color:red;' : 'color:green;';
        $pos_color = empty($r['posicion']) ? 'color:red;font-weight:bold;' : '';
        echo '<tr>';
        echo '<td>' . $r['id'] . '</td>';
        echo '<td style="' . $pos_color . '">' . htmlspecialchars($r['posicion'] ?: '(VACÍO)') . '</td>';
        echo '<td>' . htmlspecialchars($r['titulo'] ?? '') . '</td>';
        echo '<td style="' . $ruta_color . '">' . htmlspecialchars($r['ruta_imagen'] ?: '(VACÍO)') . '</td>';
        echo '<td>' . htmlspecialchars($r['alt_texto'] ?? '') . '</td>';
        echo '<td>' . ($r['activa'] ?? '?') . '</td>';
        echo '<td>' . ($r['orden'] ?? '?') . '</td>';
        echo '</tr>';
    }
    echo '</table>';
}

// Verificar archivos en disco
echo '<h2>Archivos en /assets/images/home/</h2>';
$dir = __DIR__ . '/../assets/images/home/';
if (!is_dir($dir)) {
    echo '<p style="color:red;">La carpeta NO existe: ' . $dir . '</p>';
} else {
    $files = glob($dir . '*');
    if (empty($files)) {
        echo '<p style="color:orange;">La carpeta existe pero está vacía.</p>';
    } else {
        echo '<ul>';
        foreach ($files as $f) {
            echo '<li>' . basename($f) . ' (' . round(filesize($f)/1024) . ' KB)</li>';
        }
        echo '</ul>';
    }
}

// Test de lo que vería index.php
echo '<h2>Lo que index.php carga</h2>';
$_home_imagenes = [];
$stmt = $db->query("SELECT * FROM home_imagenes WHERE activa = 1 ORDER BY orden ASC");
while ($row = $stmt->fetch()) {
    $_home_imagenes[$row['posicion']] = $row;
}
echo '<pre>' . htmlspecialchars(print_r($_home_imagenes, true)) . '</pre>';
