<?php
/**
 * Back Office — Listado de artículos
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/funciones.php';

requiere_login();

$db = get_db();

// Filtros
$filtro_idioma = $_GET['idioma'] ?? '';
$filtro_cat = $_GET['categoria'] ?? '';
$filtro_estado = $_GET['estado'] ?? '';
$filtro_busca = trim($_GET['q'] ?? '');
$pagina = max(1, (int)($_GET['pagina'] ?? 1));
$por_pagina = 20;
$offset = ($pagina - 1) * $por_pagina;

// Construir query
$where = [];
$params = [];

if ($filtro_idioma && in_array($filtro_idioma, ['es', 'en'])) {
    $where[] = "idioma = :idioma";
    $params[':idioma'] = $filtro_idioma;
}
if ($filtro_cat && in_array($filtro_cat, ['centros','agencias','erasmus','novedades','casos-exito'])) {
    $where[] = "categoria = :cat";
    $params[':cat'] = $filtro_cat;
}
if ($filtro_estado === 'publicado') {
    $where[] = "publicado = 1";
} elseif ($filtro_estado === 'borrador') {
    $where[] = "publicado = 0";
}
if ($filtro_busca) {
    $where[] = "titulo LIKE :q";
    $params[':q'] = '%' . $filtro_busca . '%';
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$total = (int)$db->prepare("SELECT COUNT(*) FROM articulos $where_sql")->execute($params) ?
    $db->prepare("SELECT COUNT(*) FROM articulos $where_sql") : null;
$stmt_count = $db->prepare("SELECT COUNT(*) FROM articulos $where_sql");
$stmt_count->execute($params);
$total = (int)$stmt_count->fetchColumn();
$total_paginas = max(1, (int)ceil($total / $por_pagina));

$stmt = $db->prepare("SELECT * FROM articulos $where_sql ORDER BY fecha_publicacion DESC, fecha_creacion DESC LIMIT $por_pagina OFFSET $offset");
$stmt->execute($params);
$articulos = $stmt->fetchAll();

// Mensaje flash
$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Artículos — EuryGo Admin</title>
  <link rel="stylesheet" href="/admin/assets/admin.css">
  <meta name="robots" content="noindex, nofollow">
</head>
<body class="admin-body">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <div class="admin-main">
    <?php include __DIR__ . '/partials/topbar.php'; ?>

    <div class="admin-content">
      <div class="admin-content__header">
        <h1>Artículos</h1>
        <a href="/admin/editor.php" class="btn-admin btn-admin--primary">+ Nuevo artículo</a>
      </div>

<?php if ($msg === 'borrado'): ?>
      <div class="alert alert--success">Artículo borrado correctamente.</div>
<?php elseif ($msg === 'duplicado'): ?>
      <div class="alert alert--success">Artículo duplicado. Edita el nuevo borrador.</div>
<?php endif; ?>

      <!-- Filtros -->
      <form class="admin-filters" method="GET" action="">
        <select name="idioma">
          <option value="">Todos los idiomas</option>
          <option value="es" <?= $filtro_idioma === 'es' ? 'selected' : '' ?>>ES</option>
          <option value="en" <?= $filtro_idioma === 'en' ? 'selected' : '' ?>>EN</option>
        </select>
        <select name="categoria">
          <option value="">Todas las categorías</option>
          <?php foreach (['centros','agencias','erasmus','novedades','casos-exito'] as $c): ?>
          <option value="<?= $c ?>" <?= $filtro_cat === $c ? 'selected' : '' ?>><?= nombre_categoria($c, 'es') ?></option>
          <?php endforeach; ?>
        </select>
        <select name="estado">
          <option value="">Todos los estados</option>
          <option value="publicado" <?= $filtro_estado === 'publicado' ? 'selected' : '' ?>>Publicados</option>
          <option value="borrador" <?= $filtro_estado === 'borrador' ? 'selected' : '' ?>>Borradores</option>
        </select>
        <input type="text" name="q" placeholder="Buscar por título..." value="<?= htmlspecialchars($filtro_busca) ?>">
        <button type="submit" class="btn-admin btn-admin--outline">Filtrar</button>
        <?php if ($filtro_idioma || $filtro_cat || $filtro_estado || $filtro_busca): ?>
        <a href="/admin/articulos.php" class="btn-admin btn-admin--text">Limpiar</a>
        <?php endif; ?>
      </form>

      <!-- Tabla -->
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Título</th>
              <th>Idioma</th>
              <th>Categoría</th>
              <th>Estado</th>
              <th>Fecha</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
<?php if (empty($articulos)): ?>
            <tr><td colspan="6" style="text-align:center; padding: 2rem;">No se encontraron artículos.</td></tr>
<?php endif; ?>
<?php foreach ($articulos as $art): ?>
            <tr>
              <td class="admin-table__title"><?= htmlspecialchars(truncar($art['titulo'], 60)) ?></td>
              <td><span class="badge badge--lang"><?= strtoupper($art['idioma']) ?></span></td>
              <td><?= nombre_categoria($art['categoria'], 'es') ?></td>
              <td>
                <?php if ($art['publicado']): ?>
                <span class="badge badge--success">Publicado</span>
                <?php else: ?>
                <span class="badge badge--draft">Borrador</span>
                <?php endif; ?>
              </td>
              <td><?= $art['fecha_publicacion'] ? formato_fecha($art['fecha_publicacion'], 'es') : '—' ?></td>
              <td class="admin-table__actions">
                <a href="/admin/editor.php?id=<?= $art['id'] ?>" class="btn-admin btn-admin--sm">Editar</a>
                <a href="/blog/<?= htmlspecialchars($art['slug']) ?>/" target="_blank" class="btn-admin btn-admin--sm btn-admin--outline">Ver</a>
                <form method="POST" action="/admin/duplicar-articulo.php" style="display:inline;">
                  <?= campo_csrf() ?>
                  <input type="hidden" name="id" value="<?= $art['id'] ?>">
                  <button type="submit" class="btn-admin btn-admin--sm btn-admin--outline">Duplicar</button>
                </form>
                <form method="POST" action="/admin/borrar-articulo.php" style="display:inline;"
                      onsubmit="return confirm('¿Seguro que quieres borrar este artículo? Esta acción no se puede deshacer.');">
                  <?= campo_csrf() ?>
                  <input type="hidden" name="id" value="<?= $art['id'] ?>">
                  <button type="submit" class="btn-admin btn-admin--sm btn-admin--danger">Borrar</button>
                </form>
              </td>
            </tr>
<?php endforeach; ?>
          </tbody>
        </table>
      </div>

<?php if ($total_paginas > 1): ?>
      <div class="admin-pagination">
        <?php if ($pagina > 1): ?>
        <a href="?pagina=<?= $pagina - 1 ?>&idioma=<?= $filtro_idioma ?>&categoria=<?= $filtro_cat ?>&estado=<?= $filtro_estado ?>&q=<?= urlencode($filtro_busca) ?>">&larr; Anterior</a>
        <?php endif; ?>
        <span>Página <?= $pagina ?> de <?= $total_paginas ?></span>
        <?php if ($pagina < $total_paginas): ?>
        <a href="?pagina=<?= $pagina + 1 ?>&idioma=<?= $filtro_idioma ?>&categoria=<?= $filtro_cat ?>&estado=<?= $filtro_estado ?>&q=<?= urlencode($filtro_busca) ?>">Siguiente &rarr;</a>
        <?php endif; ?>
      </div>
<?php endif; ?>
    </div>
  </div>
</body>
</html>
