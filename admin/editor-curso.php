<?php
/**
 * Back Office — Editor de cursos (crear y editar)
 * 5 secciones: datos generales, descripción, programa día a día, SEO, imagen
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/funciones.php';

requiere_login();

$db = get_db();
$id = (int)($_GET['id'] ?? 0);
$es_edicion = $id > 0;
$curso = null;
$programa = [];
$ediciones = [];
$msg = $_GET['msg'] ?? '';
$msg_tipo = $msg ? 'success' : '';

// Cargar curso si es edición
if ($es_edicion) {
    $stmt = $db->prepare("SELECT * FROM cursos WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $curso = $stmt->fetch();
    if (!$curso) {
        header('Location: /admin/cursos.php');
        exit;
    }
    // Cargar programa
    $stmt_prog = $db->prepare("SELECT * FROM cursos_programa WHERE curso_id = :id ORDER BY dia ASC, orden ASC");
    $stmt_prog->execute([':id' => $id]);
    $programa = $stmt_prog->fetchAll();
    // Cargar ediciones
    $stmt_ed = $db->prepare("SELECT * FROM cursos_ediciones WHERE curso_id = :id ORDER BY fecha_inicio ASC");
    $stmt_ed->execute([':id' => $id]);
    $ediciones = $stmt_ed->fetchAll();
}

// Procesar POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificar_csrf()) {
        $msg = 'Error de seguridad (CSRF). Recarga e intenta de nuevo.';
        $msg_tipo = 'error';
    } else {
        $datos = [
            ':titulo'       => trim($_POST['titulo'] ?? ''),
            ':slug'         => trim($_POST['slug'] ?? ''),
            ':idioma'       => $_POST['idioma'] ?? 'es',
            ':traduccion_id' => ((int)($_POST['traduccion_id'] ?? 0)) ?: null,
            ':extracto'     => trim($_POST['extracto'] ?? ''),
            ':descripcion'  => $_POST['descripcion'] ?? '',
            ':precio'       => (float)($_POST['precio'] ?? 0),
            ':duracion_dias' => max(1, (int)($_POST['duracion_dias'] ?? 5)),
            ':plazas'       => max(1, (int)($_POST['plazas'] ?? 25)),
            ':fecha_inicio' => $_POST['fecha_inicio'] ?? null,
            ':fecha_fin'    => $_POST['fecha_fin'] ?? null,
            ':ubicacion'    => trim($_POST['ubicacion'] ?? ''),
            ':destacado'    => isset($_POST['destacado']) ? 1 : 0,
            ':meta_title'   => trim($_POST['meta_title'] ?? '') ?: null,
            ':meta_description' => trim($_POST['meta_description'] ?? '') ?: null,
        ];

        $accion_post = $_POST['accion'] ?? 'borrador';

        // Validación
        if (empty($datos[':titulo']) || empty($datos[':extracto'])) {
            $msg = 'Título y extracto son obligatorios.';
            $msg_tipo = 'error';
        }

        if (empty($datos[':slug'])) {
            $datos[':slug'] = generar_slug($datos[':titulo']);
        }

        if (empty($msg)) {
            // Verificar slug único
            $check_sql = "SELECT id FROM cursos WHERE slug = :s AND idioma = :i";
            $check_params = [':s' => $datos[':slug'], ':i' => $datos[':idioma']];
            if ($es_edicion) {
                $check_sql .= " AND id != :id";
                $check_params[':id'] = $id;
            }
            $check = $db->prepare($check_sql);
            $check->execute($check_params);
            if ($check->fetch()) {
                $msg = 'El slug "' . $datos[':slug'] . '" ya existe. Elige otro.';
                $msg_tipo = 'error';
            }
        }

        // Imagen
        $imagen = $curso['imagen'] ?? null;
        if (!empty($_FILES['imagen']['name']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['imagen'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            $mimes_ok = ['image/jpeg', 'image/png', 'image/webp', 'image/avif'];
            if (in_array($mime, $mimes_ok) && $file['size'] <= 5 * 1024 * 1024) {
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $nombre_archivo = 'curso-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
                $destino = __DIR__ . '/../assets/images/blog/' . $nombre_archivo;
                if (move_uploaded_file($file['tmp_name'], $destino)) {
                    $imagen = '/assets/images/blog/' . $nombre_archivo;
                }
            } else {
                $msg = 'Imagen: solo JPG/PNG/WebP/AVIF, máx 5MB.';
                $msg_tipo = 'error';
            }
        }

        if (empty($msg) || $msg_tipo !== 'error') {
            $estado = $accion_post === 'publicar' ? 'publicado' : ($accion_post === 'cancelar' ? 'cancelado' : 'borrador');

            if ($es_edicion) {
                $sql = "UPDATE cursos SET titulo = :titulo, slug = :slug, idioma = :idioma, traduccion_id = :traduccion_id, extracto = :extracto,
                        descripcion = :descripcion, imagen = :imagen, precio = :precio, duracion_dias = :duracion_dias,
                        plazas = :plazas, fecha_inicio = :fecha_inicio, fecha_fin = :fecha_fin, ubicacion = :ubicacion,
                        estado = :estado, destacado = :destacado, meta_title = :meta_title, meta_description = :meta_description
                        WHERE id = :id";
                $datos[':imagen'] = $imagen;
                $datos[':estado'] = $estado;
                $datos[':id'] = $id;
                $db->prepare($sql)->execute($datos);
            } else {
                $sql = "INSERT INTO cursos (titulo, slug, idioma, traduccion_id, extracto, descripcion, imagen, precio, duracion_dias, plazas, fecha_inicio, fecha_fin, ubicacion, estado, destacado, meta_title, meta_description)
                        VALUES (:titulo, :slug, :idioma, :traduccion_id, :extracto, :descripcion, :imagen, :precio, :duracion_dias, :plazas, :fecha_inicio, :fecha_fin, :ubicacion, :estado, :destacado, :meta_title, :meta_description)";
                $datos[':imagen'] = $imagen;
                $datos[':estado'] = $estado;
                $db->prepare($sql)->execute($datos);
                $id = (int)$db->lastInsertId();
                $es_edicion = true;
            }

            // Guardar programa
            // Borrar programa existente
            $db->prepare("DELETE FROM cursos_programa WHERE curso_id = :id")->execute([':id' => $id]);

            // Insertar nuevas actividades del programa
            $prog_dias = $_POST['prog_dia'] ?? [];
            $prog_titulos = $_POST['prog_titulo'] ?? [];
            $prog_descs = $_POST['prog_descripcion'] ?? [];
            $prog_horarios = $_POST['prog_horario'] ?? [];
            $prog_tipos = $_POST['prog_tipo'] ?? [];

            for ($i = 0; $i < count($prog_titulos); $i++) {
                $pt = trim($prog_titulos[$i] ?? '');
                if (empty($pt)) continue;
                $db->prepare("INSERT INTO cursos_programa (curso_id, dia, titulo, descripcion, horario, tipo, orden) VALUES (:cid, :dia, :titulo, :desc, :horario, :tipo, :orden)")
                    ->execute([
                        ':cid' => $id,
                        ':dia' => (int)($prog_dias[$i] ?? 1),
                        ':titulo' => $pt,
                        ':desc' => trim($prog_descs[$i] ?? ''),
                        ':horario' => trim($prog_horarios[$i] ?? ''),
                        ':tipo' => in_array($prog_tipos[$i] ?? '', ['sesion','actividad','excursion']) ? $prog_tipos[$i] : 'sesion',
                        ':orden' => $i + 1,
                    ]);
            }

            // Guardar ediciones
            $db->prepare("DELETE FROM cursos_ediciones WHERE curso_id = :id")->execute([':id' => $id]);

            $ed_inicios = $_POST['ed_fecha_inicio'] ?? [];
            $ed_fines = $_POST['ed_fecha_fin'] ?? [];
            $ed_plazas = $_POST['ed_plazas'] ?? [];
            $ed_estados = $_POST['ed_estado'] ?? [];
            $ed_destacadas = $_POST['ed_destacada'] ?? [];

            for ($i = 0; $i < count($ed_inicios); $i++) {
                $fi = trim($ed_inicios[$i] ?? '');
                $ff = trim($ed_fines[$i] ?? '');
                if (empty($fi) || empty($ff)) continue;
                $pl = max(1, (int)($ed_plazas[$i] ?? 25));
                $est = in_array($ed_estados[$i] ?? '', ['abierta','cerrada','cancelada','finalizada']) ? $ed_estados[$i] : 'abierta';
                $dest = in_array((string)$i, $ed_destacadas) ? 1 : 0;
                $db->prepare("INSERT INTO cursos_ediciones (curso_id, fecha_inicio, fecha_fin, plazas_totales, plazas_disponibles, estado, destacada) VALUES (:cid, :fi, :ff, :pl, :pl, :est, :dest)")
                    ->execute([':cid' => $id, ':fi' => $fi, ':ff' => $ff, ':pl' => $pl, ':est' => $est, ':dest' => $dest]);
            }

            // Recargar curso
            $stmt = $db->prepare("SELECT * FROM cursos WHERE id = :id LIMIT 1");
            $stmt->execute([':id' => $id]);
            $curso = $stmt->fetch();

            $stmt_prog = $db->prepare("SELECT * FROM cursos_programa WHERE curso_id = :id ORDER BY dia ASC, orden ASC");
            $stmt_prog->execute([':id' => $id]);
            $programa = $stmt_prog->fetchAll();

            $stmt_ed = $db->prepare("SELECT * FROM cursos_ediciones WHERE curso_id = :id ORDER BY fecha_inicio ASC");
            $stmt_ed->execute([':id' => $id]);
            $ediciones = $stmt_ed->fetchAll();

            $msg = $estado === 'publicado' ? 'Curso publicado correctamente.' : 'Curso guardado como borrador.';
            $msg_tipo = 'success';
        }
    }
}

// Valores para el formulario
$v = [
    'titulo'       => $curso['titulo'] ?? '',
    'slug'         => $curso['slug'] ?? '',
    'idioma'       => $curso['idioma'] ?? 'es',
    'traduccion_id' => $curso['traduccion_id'] ?? '',
    'extracto'     => $curso['extracto'] ?? '',
    'descripcion'  => $curso['descripcion'] ?? '',
    'precio'       => $curso['precio'] ?? 480,
    'duracion_dias' => $curso['duracion_dias'] ?? 5,
    'plazas'       => $curso['plazas'] ?? 25,
    'fecha_inicio' => $curso['fecha_inicio'] ?? '',
    'fecha_fin'    => $curso['fecha_fin'] ?? '',
    'ubicacion'    => $curso['ubicacion'] ?? 'Jerez de la Frontera, Cádiz, España',
    'estado'       => $curso['estado'] ?? 'borrador',
    'destacado'    => $curso['destacado'] ?? 0,
    'imagen'       => $curso['imagen'] ?? '',
    'meta_title'   => $curso['meta_title'] ?? '',
    'meta_description' => $curso['meta_description'] ?? '',
];

// Cargar lista de cursos para selector de traducción
$cursos_para_traduccion = $db->query("SELECT id, titulo, idioma, slug FROM cursos ORDER BY idioma, titulo")->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $es_edicion ? 'Editar curso' : 'Nuevo curso' ?> — EuryGo Admin</title>
  <link rel="stylesheet" href="/admin/assets/admin.css">
  <script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
  <meta name="robots" content="noindex, nofollow">
  <style>
    .curso-tabs { display: flex; gap: 0; border-bottom: 2px solid #e2e8f0; margin-bottom: 1.5rem; }
    .curso-tab { padding: 0.75rem 1.25rem; border: none; background: none; cursor: pointer; font-size: 0.9rem; font-weight: 600; color: #64748B; border-bottom: 2px solid transparent; margin-bottom: -2px; transition: all 0.2s; }
    .curso-tab:hover { color: #0284C7; }
    .curso-tab.active { color: #0284C7; border-bottom-color: #0284C7; }
    .curso-panel { display: none; }
    .curso-panel.active { display: block; }
    .programa-item { background: #f8f9fa; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem; margin-bottom: 0.75rem; position: relative; }
    .programa-item__header { display: grid; grid-template-columns: 60px 1fr 120px 120px 40px; gap: 0.5rem; align-items: end; margin-bottom: 0.5rem; }
    .programa-item__desc { width: 100%; }
    .programa-item__remove { position: absolute; top: 8px; right: 8px; background: #dc3545; color: #fff; border: none; border-radius: 4px; width: 24px; height: 24px; cursor: pointer; font-size: 1rem; line-height: 1; }
    .programa-add { background: #0284C7; color: #fff; border: none; padding: 0.6rem 1.2rem; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 0.85rem; }
    .programa-add:hover { background: #0C4A6E; }
    .img-preview { max-width: 200px; max-height: 120px; border-radius: 6px; margin-top: 0.5rem; }
    @media (max-width: 768px) {
      .programa-item__header { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body class="admin-body">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <div class="admin-main">
    <?php include __DIR__ . '/partials/topbar.php'; ?>

    <div class="admin-content">
      <div class="editor-actions">
        <h1><?= $es_edicion ? 'Editar curso' : 'Nuevo curso' ?></h1>
        <div>
          <a href="/admin/cursos.php" class="btn-admin btn-admin--outline">← Volver</a>
<?php if ($es_edicion && $v['estado'] === 'publicado'): ?>
          <a href="/<?= $v['idioma'] === 'en' ? 'en/' : '' ?>cursos/<?= htmlspecialchars($v['slug']) ?>/" target="_blank" class="btn-admin btn-admin--sm">Ver en web ↗</a>
<?php endif; ?>
        </div>
      </div>

<?php if ($msg): ?>
      <div class="alert alert--<?= $msg_tipo ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

      <form method="POST" enctype="multipart/form-data" id="curso-form">
        <?= campo_csrf() ?>

        <!-- Tabs -->
        <div class="curso-tabs">
          <button type="button" class="curso-tab active" data-tab="general">Datos generales</button>
          <button type="button" class="curso-tab" data-tab="ediciones">Ediciones</button>
          <button type="button" class="curso-tab" data-tab="contenido">Descripción</button>
          <button type="button" class="curso-tab" data-tab="programa">Programa</button>
          <button type="button" class="curso-tab" data-tab="imagen">Imagen</button>
          <button type="button" class="curso-tab" data-tab="seo">SEO</button>
        </div>

        <!-- Tab: Datos generales -->
        <div class="curso-panel active" id="tab-general">
          <div class="form-row">
            <div class="form-group form-group--wide">
              <label class="form-label">Título del curso *</label>
              <input type="text" name="titulo" value="<?= htmlspecialchars($v['titulo']) ?>" class="form-input" required>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Slug (URL)</label>
              <input type="text" name="slug" value="<?= htmlspecialchars($v['slug']) ?>" class="form-input" placeholder="Se genera automáticamente">
            </div>
            <div class="form-group">
              <label class="form-label">Idioma</label>
              <select name="idioma" class="form-input">
                <option value="es" <?= $v['idioma'] === 'es' ? 'selected' : '' ?>>Español</option>
                <option value="en" <?= $v['idioma'] === 'en' ? 'selected' : '' ?>>English</option>
              </select>
            </div>
            <div class="form-group">
              <label class="form-label">Traducción (curso en otro idioma)</label>
              <select name="traduccion_id" class="form-input">
                <option value="">— Sin traducción —</option>
<?php foreach ($cursos_para_traduccion as $ct): if ($ct['id'] == $id) continue; ?>
                <option value="<?= $ct['id'] ?>" <?= (int)$v['traduccion_id'] === $ct['id'] ? 'selected' : '' ?>>[<?= strtoupper($ct['idioma']) ?>] <?= htmlspecialchars($ct['titulo']) ?></option>
<?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Precio (€)</label>
              <input type="number" name="precio" value="<?= $v['precio'] ?>" class="form-input" step="0.01" min="0">
            </div>
            <div class="form-group">
              <label class="form-label">Duración (días)</label>
              <input type="number" name="duracion_dias" value="<?= $v['duracion_dias'] ?>" class="form-input" min="1" max="30">
            </div>
            <div class="form-group">
              <label class="form-label">Plazas</label>
              <input type="number" name="plazas" value="<?= $v['plazas'] ?>" class="form-input" min="1">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label class="form-label">Fecha inicio</label>
              <input type="date" name="fecha_inicio" value="<?= $v['fecha_inicio'] ?>" class="form-input">
            </div>
            <div class="form-group">
              <label class="form-label">Fecha fin</label>
              <input type="date" name="fecha_fin" value="<?= $v['fecha_fin'] ?>" class="form-input">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group form-group--wide">
              <label class="form-label">Ubicación</label>
              <input type="text" name="ubicacion" value="<?= htmlspecialchars($v['ubicacion']) ?>" class="form-input">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group form-group--wide">
              <label class="form-label">Extracto *</label>
              <textarea name="extracto" class="form-input" rows="3" required><?= htmlspecialchars($v['extracto']) ?></textarea>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label style="display:flex; align-items:center; gap:0.5rem; cursor:pointer;">
                <input type="checkbox" name="destacado" value="1" <?= $v['destacado'] ? 'checked' : '' ?>>
                <strong>Curso destacado</strong> (se muestra primero)
              </label>
            </div>
          </div>
        </div>

        <!-- Tab: Ediciones -->
        <div class="curso-panel" id="tab-ediciones">
          <p style="margin-bottom:1rem; color:#64748B; font-size:0.9rem;">Cada curso puede tener varias ediciones con fechas y plazas independientes. Los alumnos se inscriben a una edición concreta.</p>
          <div id="ediciones-list">
<?php foreach ($ediciones as $i => $ed): ?>
            <div class="programa-item" data-ed-index="<?= $i ?>">
              <button type="button" class="programa-item__remove" onclick="this.parentElement.remove()" title="Eliminar">&times;</button>
              <div style="display:grid; grid-template-columns: 1fr 1fr 100px 140px auto; gap:0.5rem; align-items:end;">
                <div>
                  <label style="font-size:0.75rem; color:#64748B;">Fecha inicio</label>
                  <input type="date" name="ed_fecha_inicio[]" value="<?= $ed['fecha_inicio'] ?>" class="form-input" required>
                </div>
                <div>
                  <label style="font-size:0.75rem; color:#64748B;">Fecha fin</label>
                  <input type="date" name="ed_fecha_fin[]" value="<?= $ed['fecha_fin'] ?>" class="form-input" required>
                </div>
                <div>
                  <label style="font-size:0.75rem; color:#64748B;">Plazas</label>
                  <input type="number" name="ed_plazas[]" value="<?= $ed['plazas_totales'] ?>" class="form-input" min="1">
                </div>
                <div>
                  <label style="font-size:0.75rem; color:#64748B;">Estado</label>
                  <select name="ed_estado[]" class="form-input">
                    <option value="abierta" <?= $ed['estado'] === 'abierta' ? 'selected' : '' ?>>Abierta</option>
                    <option value="cerrada" <?= $ed['estado'] === 'cerrada' ? 'selected' : '' ?>>Cerrada</option>
                    <option value="cancelada" <?= $ed['estado'] === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
                    <option value="finalizada" <?= $ed['estado'] === 'finalizada' ? 'selected' : '' ?>>Finalizada</option>
                  </select>
                </div>
                <div>
                  <label style="font-size:0.75rem; color:#64748B; display:block;">Dest.</label>
                  <input type="checkbox" name="ed_destacada[]" value="<?= $i ?>" <?= $ed['destacada'] ? 'checked' : '' ?> title="Edición destacada">
                </div>
              </div>
            </div>
<?php endforeach; ?>
          </div>
          <button type="button" class="programa-add" onclick="addEdicion()">+ Añadir edición</button>
        </div>

        <!-- Tab: Descripción -->
        <div class="curso-panel" id="tab-contenido">
          <div class="form-group">
            <label class="form-label">Descripción completa (HTML)</label>
            <textarea name="descripcion" id="editor-descripcion" class="form-input" rows="20"><?= htmlspecialchars($v['descripcion']) ?></textarea>
          </div>
        </div>

        <!-- Tab: Programa -->
        <div class="curso-panel" id="tab-programa">
          <p style="margin-bottom:1rem; color:#64748B; font-size:0.9rem;">Añade las actividades del programa día a día. Puedes reordenar arrastrando los elementos.</p>
          <div id="programa-list">
<?php foreach ($programa as $i => $p): ?>
            <div class="programa-item" data-index="<?= $i ?>">
              <button type="button" class="programa-item__remove" onclick="this.parentElement.remove()" title="Eliminar">&times;</button>
              <div class="programa-item__header">
                <div>
                  <label style="font-size:0.75rem; color:#64748B;">Día</label>
                  <input type="number" name="prog_dia[]" value="<?= $p['dia'] ?>" class="form-input" min="1" max="30" style="width:60px;">
                </div>
                <div>
                  <label style="font-size:0.75rem; color:#64748B;">Título de la actividad</label>
                  <input type="text" name="prog_titulo[]" value="<?= htmlspecialchars($p['titulo']) ?>" class="form-input">
                </div>
                <div>
                  <label style="font-size:0.75rem; color:#64748B;">Horario</label>
                  <input type="text" name="prog_horario[]" value="<?= htmlspecialchars($p['horario']) ?>" class="form-input" placeholder="09:00 – 14:00">
                </div>
                <div>
                  <label style="font-size:0.75rem; color:#64748B;">Tipo</label>
                  <select name="prog_tipo[]" class="form-input">
                    <option value="sesion" <?= $p['tipo'] === 'sesion' ? 'selected' : '' ?>>Sesión</option>
                    <option value="actividad" <?= $p['tipo'] === 'actividad' ? 'selected' : '' ?>>Actividad</option>
                    <option value="excursion" <?= $p['tipo'] === 'excursion' ? 'selected' : '' ?>>Excursión</option>
                  </select>
                </div>
                <div></div>
              </div>
              <textarea name="prog_descripcion[]" class="form-input programa-item__desc" rows="2" placeholder="Descripción de la actividad…"><?= htmlspecialchars($p['descripcion']) ?></textarea>
            </div>
<?php endforeach; ?>
          </div>
          <button type="button" class="programa-add" onclick="addProgramaItem()">+ Añadir actividad</button>
        </div>

        <!-- Tab: Imagen -->
        <div class="curso-panel" id="tab-imagen">
          <div class="form-group">
            <label class="form-label">Imagen de portada</label>
            <input type="file" name="imagen" class="form-input" accept="image/jpeg,image/png,image/webp,image/avif">
            <small style="color:#64748B;">JPG, PNG, WebP o AVIF. Máximo 5 MB.</small>
<?php if ($v['imagen']): ?>
            <div style="margin-top:0.5rem;">
              <img src="<?= htmlspecialchars($v['imagen']) ?>" alt="Portada actual" class="img-preview">
              <p style="font-size:0.8rem; color:#64748B; margin-top:0.25rem;">Imagen actual: <?= htmlspecialchars(basename($v['imagen'])) ?></p>
            </div>
<?php endif; ?>
          </div>
        </div>

        <!-- Tab: SEO -->
        <div class="curso-panel" id="tab-seo">
          <div class="form-group">
            <label class="form-label">Meta título (máx 160 caracteres)</label>
            <input type="text" name="meta_title" value="<?= htmlspecialchars($v['meta_title']) ?>" class="form-input" maxlength="160">
          </div>
          <div class="form-group">
            <label class="form-label">Meta descripción (máx 300 caracteres)</label>
            <textarea name="meta_description" class="form-input" rows="2" maxlength="300"><?= htmlspecialchars($v['meta_description']) ?></textarea>
          </div>
        </div>

        <!-- Acciones -->
        <div class="editor-actions" style="margin-top:1.5rem;">
          <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
            <button type="submit" name="accion" value="borrador" class="btn-admin btn-admin--outline">Guardar borrador</button>
            <button type="submit" name="accion" value="publicar" class="btn-admin btn-admin--primary">Publicar</button>
<?php if ($es_edicion && $v['estado'] === 'publicado'): ?>
            <button type="submit" name="accion" value="cancelar" class="btn-admin btn-admin--danger" onclick="return confirm('¿Cancelar este curso?')">Cancelar curso</button>
<?php endif; ?>
          </div>
        </div>
      </form>

    </div>
  </div>

  <script>
  // Tabs
  document.querySelectorAll('.curso-tab').forEach(tab => {
    tab.addEventListener('click', () => {
      document.querySelectorAll('.curso-tab').forEach(t => t.classList.remove('active'));
      document.querySelectorAll('.curso-panel').forEach(p => p.classList.remove('active'));
      tab.classList.add('active');
      document.getElementById('tab-' + tab.dataset.tab).classList.add('active');
    });
  });

  // TinyMCE
  tinymce.init({
    selector: '#editor-descripcion',
    height: 500,
    menubar: false,
    plugins: 'lists link image table code',
    toolbar: 'undo redo | blocks | bold italic | alignleft aligncenter alignright | bullist numlist | link image table | code',
    content_style: 'body { font-family: system-ui, sans-serif; font-size: 14px; }'
  });

  // Ediciones: añadir edición
  function addEdicion() {
    const list = document.getElementById('ediciones-list');
    const idx = list.children.length;
    const html = `
      <div class="programa-item">
        <button type="button" class="programa-item__remove" onclick="this.parentElement.remove()" title="Eliminar">&times;</button>
        <div style="display:grid; grid-template-columns: 1fr 1fr 100px 140px auto; gap:0.5rem; align-items:end;">
          <div>
            <label style="font-size:0.75rem; color:#64748B;">Fecha inicio</label>
            <input type="date" name="ed_fecha_inicio[]" class="form-input" required>
          </div>
          <div>
            <label style="font-size:0.75rem; color:#64748B;">Fecha fin</label>
            <input type="date" name="ed_fecha_fin[]" class="form-input" required>
          </div>
          <div>
            <label style="font-size:0.75rem; color:#64748B;">Plazas</label>
            <input type="number" name="ed_plazas[]" value="25" class="form-input" min="1">
          </div>
          <div>
            <label style="font-size:0.75rem; color:#64748B;">Estado</label>
            <select name="ed_estado[]" class="form-input">
              <option value="abierta">Abierta</option>
              <option value="cerrada">Cerrada</option>
              <option value="cancelada">Cancelada</option>
              <option value="finalizada">Finalizada</option>
            </select>
          </div>
          <div>
            <label style="font-size:0.75rem; color:#64748B; display:block;">Dest.</label>
            <input type="checkbox" name="ed_destacada[]" value="${idx}" title="Edición destacada">
          </div>
        </div>
      </div>`;
    list.insertAdjacentHTML('beforeend', html);
  }

  // Programa: añadir actividad
  function addProgramaItem() {
    const list = document.getElementById('programa-list');
    const html = `
      <div class="programa-item">
        <button type="button" class="programa-item__remove" onclick="this.parentElement.remove()" title="Eliminar">&times;</button>
        <div class="programa-item__header">
          <div>
            <label style="font-size:0.75rem; color:#64748B;">Día</label>
            <input type="number" name="prog_dia[]" value="1" class="form-input" min="1" max="30" style="width:60px;">
          </div>
          <div>
            <label style="font-size:0.75rem; color:#64748B;">Título de la actividad</label>
            <input type="text" name="prog_titulo[]" class="form-input" placeholder="Título…">
          </div>
          <div>
            <label style="font-size:0.75rem; color:#64748B;">Horario</label>
            <input type="text" name="prog_horario[]" class="form-input" placeholder="09:00 – 14:00">
          </div>
          <div>
            <label style="font-size:0.75rem; color:#64748B;">Tipo</label>
            <select name="prog_tipo[]" class="form-input">
              <option value="sesion">Sesión</option>
              <option value="actividad">Actividad</option>
              <option value="excursion">Excursión</option>
            </select>
          </div>
          <div></div>
        </div>
        <textarea name="prog_descripcion[]" class="form-input programa-item__desc" rows="2" placeholder="Descripción de la actividad…"></textarea>
      </div>`;
    list.insertAdjacentHTML('beforeend', html);
  }
  </script>
</body>
</html>
