<?php
/**
 * Back Office — Editor de artículos (crear y editar)
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/funciones.php';

requiere_login();

$db = get_db();
$id = (int)($_GET['id'] ?? 0);
$es_edicion = $id > 0;
$articulo = null;
$msg = '';
$msg_tipo = '';

// Cargar artículo si es edición
if ($es_edicion) {
    $stmt = $db->prepare("SELECT * FROM articulos WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $id]);
    $articulo = $stmt->fetch();
    if (!$articulo) {
        header('Location: /admin/articulos.php');
        exit;
    }
}

// Procesar POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificar_csrf()) {
        $msg = 'Error de seguridad (CSRF). Recarga e intenta de nuevo.';
        $msg_tipo = 'error';
    } else {
        $datos = [
            ':titulo'       => trim($_POST['titulo'] ?? ''),
            ':subtitulo'    => trim($_POST['subtitulo'] ?? '') ?: null,
            ':extracto'     => trim($_POST['extracto'] ?? ''),
            ':contenido'    => $_POST['contenido'] ?? '',
            ':idioma'       => $_POST['idioma'] ?? 'es',
            ':categoria'    => $_POST['categoria'] ?? 'centros',
            ':autor'        => trim($_POST['autor'] ?? '') ?: 'Equipo EuryGo',
            ':slug'         => trim($_POST['slug'] ?? ''),
            ':meta_title'   => trim($_POST['meta_title'] ?? '') ?: null,
            ':meta_description' => trim($_POST['meta_description'] ?? '') ?: null,
            ':tiempo_lectura'   => max(1, (int)($_POST['tiempo_lectura'] ?? 5)),
            ':alt_imagen'   => trim($_POST['alt_imagen'] ?? '') ?: null,
            ':traduccion_id' => ((int)($_POST['traduccion_id'] ?? 0)) ?: null,
        ];

        // Validación
        if (empty($datos[':titulo']) || empty($datos[':extracto']) || empty($datos[':contenido'])) {
            $msg = 'Título, extracto y contenido son obligatorios.';
            $msg_tipo = 'error';
        } elseif (empty($datos[':slug'])) {
            $datos[':slug'] = generar_slug($datos[':titulo']);
        }

        if (empty($msg)) {
            // Verificar slug único
            $check_sql = "SELECT id FROM articulos WHERE slug = :s AND idioma = :i";
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

        // Imagen de portada
        $imagen_portada = $articulo['imagen_portada'] ?? null;
        if (!empty($_FILES['imagen_portada']['name']) && $_FILES['imagen_portada']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['imagen_portada'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mime, ALLOWED_TYPES)) {
                $msg = 'Tipo de imagen no permitido. Usa JPG, PNG o WebP.';
                $msg_tipo = 'error';
            } elseif ($file['size'] > MAX_FILE_SIZE) {
                $msg = 'La imagen supera los 5MB.';
                $msg_tipo = 'error';
            } else {
                $slug_file = $datos[':slug'] ?: generar_slug($datos[':titulo']);
                $ext = 'webp';
                $nombre_archivo = $slug_file . '-portada.' . $ext;
                $nombre_thumb = $slug_file . '-thumb.' . $ext;
                $destino = UPLOAD_DIR . $nombre_archivo;
                $destino_thumb = UPLOAD_DIR . $nombre_thumb;

                if (!is_dir(UPLOAD_DIR)) {
                    mkdir(UPLOAD_DIR, 0755, true);
                }

                // Redimensionar con GD
                $img_src = null;
                if ($mime === 'image/jpeg') $img_src = imagecreatefromjpeg($file['tmp_name']);
                elseif ($mime === 'image/png') $img_src = imagecreatefrompng($file['tmp_name']);
                elseif ($mime === 'image/webp') $img_src = imagecreatefromwebp($file['tmp_name']);

                if ($img_src) {
                    $w = imagesx($img_src);
                    $h = imagesy($img_src);

                    // Portada: máx 1200px ancho
                    if ($w > 1200) {
                        $new_h = (int)round($h * (1200 / $w));
                        $resized = imagecreatetruecolor(1200, $new_h);
                        imagecopyresampled($resized, $img_src, 0, 0, 0, 0, 1200, $new_h, $w, $h);
                        imagewebp($resized, $destino, 85);
                        imagedestroy($resized);
                    } else {
                        imagewebp($img_src, $destino, 85);
                    }

                    // Thumbnail: 400px ancho
                    $thumb_w = min(400, $w);
                    $thumb_h = (int)round($h * ($thumb_w / $w));
                    $thumb = imagecreatetruecolor($thumb_w, $thumb_h);
                    imagecopyresampled($thumb, $img_src, 0, 0, 0, 0, $thumb_w, $thumb_h, $w, $h);
                    imagewebp($thumb, $destino_thumb, 80);
                    imagedestroy($thumb);
                    imagedestroy($img_src);

                    $imagen_portada = UPLOAD_URL . $nombre_archivo;
                }
            }
        }

        // Eliminar imagen si se pidió
        if (!empty($_POST['eliminar_imagen']) && $imagen_portada) {
            $imagen_portada = null;
        }

        if (empty($msg)) {
            $accion = $_POST['accion'] ?? 'borrador';
            $publicado = ($accion === 'publicar') ? 1 : ($es_edicion ? $articulo['publicado'] : 0);
            $fecha_pub = null;

            if ($publicado && $accion === 'publicar' && !($es_edicion && $articulo['publicado'])) {
                $fecha_pub = date('Y-m-d H:i:s');
            } elseif ($es_edicion) {
                $fecha_pub = $articulo['fecha_publicacion'];
            }

            if (!empty($_POST['fecha_publicacion'])) {
                $fecha_pub = $_POST['fecha_publicacion'];
            }

            $datos[':publicado'] = $publicado;
            $datos[':fecha_publicacion'] = $fecha_pub;
            $datos[':imagen_portada'] = $imagen_portada;

            if ($es_edicion) {
                $datos[':id'] = $id;
                $sql = "UPDATE articulos SET titulo=:titulo, subtitulo=:subtitulo, extracto=:extracto, contenido=:contenido,
                        idioma=:idioma, categoria=:categoria, autor=:autor, slug=:slug, meta_title=:meta_title,
                        meta_description=:meta_description, tiempo_lectura=:tiempo_lectura, alt_imagen=:alt_imagen,
                        publicado=:publicado, fecha_publicacion=:fecha_publicacion, imagen_portada=:imagen_portada,
                        traduccion_id=:traduccion_id
                        WHERE id=:id";
            } else {
                $sql = "INSERT INTO articulos (titulo, subtitulo, extracto, contenido, idioma, categoria, autor, slug,
                        meta_title, meta_description, tiempo_lectura, alt_imagen, publicado, fecha_publicacion, imagen_portada, traduccion_id)
                        VALUES (:titulo, :subtitulo, :extracto, :contenido, :idioma, :categoria, :autor, :slug,
                        :meta_title, :meta_description, :tiempo_lectura, :alt_imagen, :publicado, :fecha_publicacion, :imagen_portada, :traduccion_id)";
            }

            $stmt = $db->prepare($sql);
            $stmt->execute($datos);

            if (!$es_edicion) {
                $id = (int)$db->lastInsertId();
                $es_edicion = true;
            }

            // Recargar artículo
            $stmt = $db->prepare("SELECT * FROM articulos WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $articulo = $stmt->fetch();

            $msg = $publicado ? 'Artículo publicado correctamente.' : 'Borrador guardado.';
            $msg_tipo = $publicado ? 'success' : 'info';
        }
    }
}

// Defaults para el formulario
$a = $articulo ?: [
    'titulo' => '', 'subtitulo' => '', 'extracto' => '', 'contenido' => '',
    'idioma' => 'es', 'categoria' => 'centros', 'autor' => 'Equipo EuryGo',
    'slug' => '', 'meta_title' => '', 'meta_description' => '', 'tiempo_lectura' => 5,
    'alt_imagen' => '', 'publicado' => 0, 'fecha_publicacion' => '', 'imagen_portada' => '',
    'traduccion_id' => null,
];

// Cargar artículos del otro idioma para selector de traducción
$otro_idioma = ($a['idioma'] === 'es') ? 'en' : 'es';
$arts_traduccion = $db->prepare("SELECT id, titulo, idioma FROM articulos WHERE idioma = :idioma ORDER BY titulo ASC");
$arts_traduccion->execute([':idioma' => $otro_idioma]);
$arts_traduccion = $arts_traduccion->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $es_edicion ? 'Editar' : 'Nuevo' ?> artículo — EuryGo Admin</title>
  <link rel="stylesheet" href="/admin/assets/admin.css">
  <meta name="robots" content="noindex, nofollow">
  <!-- Quill.js — Editor WYSIWYG gratuito -->
  <link href="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.snow.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/quill@2.0.3/dist/quill.js"></script>
  <style>
    #quill-editor { height: 500px; background: #fff; }
    #quill-editor .ql-editor { font-family: inherit; max-width: 720px; margin: 0 auto; padding: 20px; min-height: 400px; }
    .ql-toolbar.ql-snow { background: #f8f9fa; border-bottom: 1px solid #ccc; position: sticky; top: 0; z-index: 10; }
  </style>
</head>
<body class="admin-body">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <div class="admin-main">
    <?php include __DIR__ . '/partials/topbar.php'; ?>

    <div class="admin-content">
<?php if ($msg): ?>
      <div class="alert alert--<?= $msg_tipo ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>

      <form method="POST" action="?id=<?= $id ?>" enctype="multipart/form-data" id="editor-form">
        <?= campo_csrf() ?>

        <!-- Sticky action bar -->
        <div class="editor-actions">
          <h1><?= $es_edicion ? 'Editar artículo' : 'Nuevo artículo' ?></h1>
          <div class="editor-actions__buttons">
            <button type="submit" name="accion" value="borrador" class="btn-admin btn-admin--outline">Guardar borrador</button>
<?php if ($es_edicion): ?>
            <a href="/blog/<?= htmlspecialchars($a['slug']) ?>/?preview=1" target="_blank" class="btn-admin btn-admin--outline">Vista previa</a>
<?php endif; ?>
            <button type="submit" name="accion" value="publicar" class="btn-admin btn-admin--primary">Publicar</button>
          </div>
          <div class="editor-actions__autosave" id="autosave-status"></div>
        </div>

        <!-- SECCIÓN 1: Contenido principal -->
        <div class="editor-section">
          <h2>Contenido principal</h2>
          <div class="form-group">
            <label for="titulo">Título *</label>
            <input type="text" id="titulo" name="titulo" required class="input-lg"
                   value="<?= htmlspecialchars($a['titulo']) ?>">
          </div>
          <div class="form-group">
            <label for="subtitulo">Subtítulo</label>
            <input type="text" id="subtitulo" name="subtitulo"
                   value="<?= htmlspecialchars($a['subtitulo'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label for="extracto">Extracto * <span class="char-count" id="extracto-count">0/300</span></label>
            <textarea id="extracto" name="extracto" required maxlength="300" rows="3"><?= htmlspecialchars($a['extracto']) ?></textarea>
          </div>
          <div class="form-group">
            <label>Contenido *</label>
            <div id="quill-editor"><?= $a['contenido'] ?></div>
            <input type="hidden" id="contenido" name="contenido" value="<?= htmlspecialchars($a['contenido']) ?>">
          </div>
        </div>

        <!-- SECCIÓN 2: Imagen de portada -->
        <div class="editor-section">
          <h2>Imagen de portada</h2>
<?php if ($a['imagen_portada']): ?>
          <div class="editor-image-preview">
            <img src="<?= htmlspecialchars($a['imagen_portada']) ?>" alt="Portada actual">
            <label><input type="checkbox" name="eliminar_imagen" value="1"> Eliminar imagen actual</label>
          </div>
<?php endif; ?>
          <div class="form-group">
            <label for="imagen_portada">Subir imagen (JPG, PNG, WebP — máx 5MB)</label>
            <input type="file" id="imagen_portada" name="imagen_portada" accept="image/jpeg,image/png,image/webp">
            <div class="editor-image-preview" id="preview-nueva" style="display:none;">
              <img id="preview-img" src="" alt="Vista previa">
            </div>
          </div>
          <div class="form-group">
            <label for="alt_imagen">Texto alternativo (alt) *</label>
            <input type="text" id="alt_imagen" name="alt_imagen"
                   value="<?= htmlspecialchars($a['alt_imagen'] ?? '') ?>">
          </div>
        </div>

        <!-- SECCIÓN 3: Clasificación -->
        <div class="editor-section">
          <h2>Clasificación</h2>
          <div class="form-row">
            <div class="form-group">
              <label for="idioma">Idioma</label>
              <select id="idioma" name="idioma">
                <option value="es" <?= $a['idioma'] === 'es' ? 'selected' : '' ?>>Español</option>
                <option value="en" <?= $a['idioma'] === 'en' ? 'selected' : '' ?>>English</option>
              </select>
            </div>
            <div class="form-group">
              <label for="categoria">Categoría *</label>
              <select id="categoria" name="categoria" required>
                <?php foreach (['centros','agencias','erasmus','novedades','casos-exito'] as $c): ?>
                <option value="<?= $c ?>" <?= $a['categoria'] === $c ? 'selected' : '' ?>><?= nombre_categoria($c, 'es') ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="autor">Autor</label>
              <input type="text" id="autor" name="autor" value="<?= htmlspecialchars($a['autor']) ?>">
            </div>
            <div class="form-group">
              <label for="traduccion_id">Traducción vinculada (<?= $otro_idioma === 'en' ? 'EN' : 'ES' ?>)</label>
              <select id="traduccion_id" name="traduccion_id">
                <option value="0">— Ninguna —</option>
                <?php foreach ($arts_traduccion as $at): ?>
                <option value="<?= $at['id'] ?>" <?= (int)$a['traduccion_id'] === (int)$at['id'] ? 'selected' : '' ?>>[<?= strtoupper($at['idioma']) ?>] <?= htmlspecialchars($at['titulo']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label for="tiempo_lectura">Tiempo de lectura (min)</label>
              <input type="number" id="tiempo_lectura" name="tiempo_lectura" min="1" max="60"
                     value="<?= (int)$a['tiempo_lectura'] ?>">
            </div>
          </div>
        </div>

        <!-- SECCIÓN 4: Publicación -->
        <div class="editor-section">
          <h2>Publicación</h2>
          <div class="form-row">
            <div class="form-group">
              <label for="slug">Slug * <small class="slug-preview">eurygo.com/blog/<span id="slug-display"><?= htmlspecialchars($a['slug']) ?></span>/</small></label>
              <div class="input-with-btn">
                <input type="text" id="slug" name="slug" required
                       value="<?= htmlspecialchars($a['slug']) ?>" pattern="[a-z0-9-]+">
                <button type="button" id="check-slug" class="btn-admin btn-admin--sm btn-admin--outline">Comprobar</button>
              </div>
              <div id="slug-result"></div>
            </div>
            <div class="form-group">
              <label for="fecha_publicacion">Fecha de publicación</label>
              <input type="datetime-local" id="fecha_publicacion" name="fecha_publicacion"
                     value="<?= $a['fecha_publicacion'] ? date('Y-m-d\TH:i', strtotime($a['fecha_publicacion'])) : '' ?>">
            </div>
          </div>
        </div>

        <!-- SECCIÓN 5: SEO -->
        <div class="editor-section">
          <h2>SEO</h2>
          <div class="form-group">
            <label for="meta_title">Meta title <span class="char-count" id="meta-title-count">0/60</span></label>
            <input type="text" id="meta_title" name="meta_title" maxlength="60"
                   value="<?= htmlspecialchars($a['meta_title'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label for="meta_description">Meta description <span class="char-count" id="meta-desc-count">0/155</span></label>
            <textarea id="meta_description" name="meta_description" maxlength="155" rows="2"><?= htmlspecialchars($a['meta_description'] ?? '') ?></textarea>
          </div>
          <!-- SERP Preview -->
          <div class="serp-preview">
            <div class="serp-preview__url">www.eurygo.com › blog › <span id="serp-slug"><?= htmlspecialchars($a['slug']) ?></span></div>
            <div class="serp-preview__title" id="serp-title"><?= htmlspecialchars($a['meta_title'] ?: $a['titulo']) ?></div>
            <div class="serp-preview__desc" id="serp-desc"><?= htmlspecialchars($a['meta_description'] ?: $a['extracto']) ?></div>
          </div>
        </div>
      </form>
    </div>
  </div>

<script>
// Quill.js — Image upload handler
function quillImageHandler() {
  var input = document.createElement('input');
  input.setAttribute('type', 'file');
  input.setAttribute('accept', 'image/jpeg,image/png,image/webp');
  input.click();
  input.onchange = function() {
    var file = input.files[0];
    if (!file) return;
    var fd = new FormData();
    fd.append('file', file);
    fetch('/admin/upload-imagen.php', { method: 'POST', body: fd, credentials: 'same-origin' })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.location) {
          var range = quill.getSelection(true);
          quill.insertEmbed(range.index, 'image', data.location);
          quill.setSelection(range.index + 1);
        } else {
          alert('Error al subir imagen: ' + (data.error || 'desconocido'));
        }
      })
      .catch(function() { alert('Error de conexión al subir imagen.'); });
  };
}

// Quill init
var quill = new Quill('#quill-editor', {
  theme: 'snow',
  modules: {
    toolbar: {
      container: [
        [{ 'header': [2, 3, 4, false] }],
        ['bold', 'italic', 'underline', 'strike'],
        [{ 'align': [] }],
        [{ 'list': 'ordered' }, { 'list': 'bullet' }],
        [{ 'indent': '-1' }, { 'indent': '+1' }],
        ['link', 'image', 'blockquote', 'code-block'],
        ['clean']
      ],
      handlers: { image: quillImageHandler }
    }
  },
  placeholder: 'Escribe el contenido del artículo...'
});

// Sync Quill content to hidden input on text change
quill.on('text-change', function() {
  document.getElementById('contenido').value = quill.root.innerHTML;
  // Auto-update reading time
  var text = quill.getText();
  var words = text.trim().split(/\s+/).filter(function(w) { return w.length > 0; }).length;
  var mins = Math.max(1, Math.ceil(words / 200));
  document.getElementById('tiempo_lectura').value = mins;
});

// Sync before form submit
document.getElementById('editor-form').addEventListener('submit', function() {
  document.getElementById('contenido').value = quill.root.innerHTML;
});

// Slug automático desde título
document.getElementById('titulo').addEventListener('input', function() {
  var slug = this.value.toLowerCase()
    .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
    .replace(/[^a-z0-9\s-]/g, '')
    .replace(/[\s-]+/g, '-')
    .replace(/^-+|-+$/g, '');
  document.getElementById('slug').value = slug;
  document.getElementById('slug-display').textContent = slug;
  document.getElementById('serp-slug').textContent = slug;
  // Auto-fill meta title
  if (!document.getElementById('meta_title').value) {
    document.getElementById('serp-title').textContent = this.value;
  }
});

// Slug display en tiempo real
document.getElementById('slug').addEventListener('input', function() {
  document.getElementById('slug-display').textContent = this.value;
  document.getElementById('serp-slug').textContent = this.value;
});

// Contadores de caracteres
function setupCounter(inputId, countId, max) {
  var el = document.getElementById(inputId);
  var counter = document.getElementById(countId);
  function update() { counter.textContent = el.value.length + '/' + max; }
  el.addEventListener('input', update);
  update();
}
setupCounter('extracto', 'extracto-count', 300);
setupCounter('meta_title', 'meta-title-count', 60);
setupCounter('meta_description', 'meta-desc-count', 155);

// SERP preview en tiempo real
document.getElementById('meta_title').addEventListener('input', function() {
  document.getElementById('serp-title').textContent = this.value || document.getElementById('titulo').value;
});
document.getElementById('meta_description').addEventListener('input', function() {
  document.getElementById('serp-desc').textContent = this.value || document.getElementById('extracto').value;
});

// Vista previa de imagen
document.getElementById('imagen_portada').addEventListener('change', function() {
  var preview = document.getElementById('preview-nueva');
  var img = document.getElementById('preview-img');
  if (this.files && this.files[0]) {
    var reader = new FileReader();
    reader.onload = function(e) { img.src = e.target.result; preview.style.display = 'block'; };
    reader.readAsDataURL(this.files[0]);
  } else {
    preview.style.display = 'none';
  }
});

// Comprobar slug AJAX
document.getElementById('check-slug').addEventListener('click', function() {
  var slug = document.getElementById('slug').value;
  var idioma = document.getElementById('idioma').value;
  var result = document.getElementById('slug-result');
  if (!slug) { result.innerHTML = '<span style="color:#dc3545">Escribe un slug primero.</span>'; return; }
  fetch('/admin/check-slug.php?slug=' + encodeURIComponent(slug) + '&idioma=' + idioma + '&id=<?= $id ?>')
    .then(r => r.json())
    .then(d => {
      result.innerHTML = d.disponible
        ? '<span style="color:#28a745">&#10003; Slug disponible</span>'
        : '<span style="color:#dc3545">&#10007; Slug ya en uso</span>';
    });
});

// Autoguardado cada 60 segundos
<?php if ($es_edicion): ?>
var autoguardadoTimer = setInterval(function() {
  var form = document.getElementById('editor-form');
  var fd = new FormData(form);
  fd.set('accion', 'borrador');
  // Sync Quill content
  fd.set('contenido', quill.root.innerHTML);
  fetch('?id=<?= $id ?>', { method: 'POST', body: fd })
    .then(function() {
      document.getElementById('autosave-status').textContent = 'Guardado automáticamente hace unos segundos';
      setTimeout(function() {
        document.getElementById('autosave-status').textContent = '';
      }, 10000);
    });
}, 60000);
<?php endif; ?>

// Auto-dismiss alerts
document.querySelectorAll('.alert--success, .alert--info').forEach(function(el) {
  setTimeout(function() { el.style.transition = 'opacity 0.3s'; el.style.opacity = '0'; setTimeout(function() { el.remove(); }, 300); }, 4000);
});
</script>
</body>
</html>
