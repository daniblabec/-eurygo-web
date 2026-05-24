<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requiere_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verificar_csrf()) {
    header('Location: /admin/articulos.php');
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if ($id > 0) {
    $db = get_db();
    $stmt = $db->prepare("SELECT * FROM articulos WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $art = $stmt->fetch();

    if ($art) {
        $nuevo_slug = $art['slug'] . '-copia-' . time();
        $ins = $db->prepare("INSERT INTO articulos (slug, idioma, titulo, subtitulo, extracto, contenido, imagen_portada, alt_imagen, categoria, autor, publicado, meta_title, meta_description, tiempo_lectura)
            VALUES (:slug, :idioma, :titulo, :subtitulo, :extracto, :contenido, :imagen, :alt, :cat, :autor, 0, :mt, :md, :tl)");
        $ins->execute([
            ':slug' => $nuevo_slug, ':idioma' => $art['idioma'],
            ':titulo' => $art['titulo'] . ' (copia)', ':subtitulo' => $art['subtitulo'],
            ':extracto' => $art['extracto'], ':contenido' => $art['contenido'],
            ':imagen' => $art['imagen_portada'], ':alt' => $art['alt_imagen'],
            ':cat' => $art['categoria'], ':autor' => $art['autor'],
            ':mt' => $art['meta_title'], ':md' => $art['meta_description'],
            ':tl' => $art['tiempo_lectura'],
        ]);
        $nuevo_id = $db->lastInsertId();
        header('Location: /admin/editor.php?id=' . $nuevo_id);
        exit;
    }
}

header('Location: /admin/articulos.php?msg=duplicado');
exit;
