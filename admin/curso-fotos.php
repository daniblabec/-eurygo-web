<?php
/**
 * Back Office — Gestión de fotos de un curso (slider)
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
requiere_login();

$db = get_db();
$curso_id = (int)($_GET['curso_id'] ?? 0);

if (!$curso_id) {
    header('Location: /admin/cursos.php');
    exit;
}

// Verificar que el curso existe
$stmt = $db->prepare("SELECT id, titulo, idioma FROM cursos WHERE id = ?");
$stmt->execute([$curso_id]);
$curso = $stmt->fetch();
if (!$curso) {
    header('Location: /admin/cursos.php?msg=no_existe');
    exit;
}

$max_fotos = 10;
$flash_msg = '';
$flash_tipo = 'ok';

// ─── POST: subida de fotos ───
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verificar_csrf()) {
    // Comprobar cuántas fotos tiene ya el curso
    $stmt = $db->prepare("SELECT COUNT(*) FROM curso_fotos WHERE curso_id = ?");
    $stmt->execute([$curso_id]);
    $total_existentes = (int)$stmt->fetchColumn();

    $files = $_FILES['fotos'] ?? null;
    if (!$files || empty($files['name'][0])) {
        $flash_msg = 'Selecciona al menos una foto.';
        $flash_tipo = 'error';
    } else {
        $disponibles = max(0, $max_fotos - $total_existentes);
        if ($disponibles === 0) {
            $flash_msg = "Este curso ya tiene el máximo de $max_fotos fotos. Elimina alguna antes de subir más.";
            $flash_tipo = 'error';
        } else {
            $subidas = 0;
            $errores = [];
            $orden_inicial = $total_existentes;

            for ($i = 0; $i < count($files['name']); $i++) {
                if ($subidas >= $disponibles) {
                    $errores[] = "Solo se pueden subir $disponibles más (máximo $max_fotos por curso).";
                    break;
                }
                if ($files['error'][$i] !== UPLOAD_ERR_OK) continue;

                $file = [
                    'name' => $files['name'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'size' => $files['size'][$i],
                    'type' => $files['type'][$i],
                ];

                $resultado = procesar_foto_curso($file, $curso_id);
                if ($resultado['ok']) {
                    $stmt = $db->prepare("
                        INSERT INTO curso_fotos (curso_id, nombre_archivo, orden, activa)
                        VALUES (?, ?, ?, 1)
                    ");
                    $stmt->execute([$curso_id, $resultado['nombre'], $orden_inicial + $subidas]);
                    $subidas++;
                } else {
                    $errores[] = $files['name'][$i] . ': ' . $resultado['error'];
                }
            }

            $flash_msg = "$subidas foto(s) subida(s) correctamente.";
            if (!empty($errores)) {
                $flash_msg .= ' Errores: ' . implode(' | ', $errores);
                $flash_tipo = 'warning';
            }
        }
    }
}

// ─── Cargar fotos del curso ───
$stmt = $db->prepare("SELECT * FROM curso_fotos WHERE curso_id = ? ORDER BY orden ASC, id ASC");
$stmt->execute([$curso_id]);
$fotos = $stmt->fetchAll();

/**
 * Procesa una foto: redimensiona + thumbnail
 */
function procesar_foto_curso(array $file, int $curso_id): array {
    $max_size = 5 * 1024 * 1024;
    $tipos_ok = ['image/jpeg', 'image/png', 'image/webp'];

    if ($file['size'] > $max_size) {
        return ['ok' => false, 'error' => 'Imagen > 5MB'];
    }

    // Validar tipo MIME real (no solo la cabecera de $_FILES)
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_real = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime_real, $tipos_ok)) {
        return ['ok' => false, 'error' => 'Formato no válido (' . $mime_real . ')'];
    }

    $dir = __DIR__ . "/../uploads/cursos/{$curso_id}/";
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $ts   = time();
    $rand = bin2hex(random_bytes(4));
    $nombre = "foto_{$ts}_{$rand}.jpg";
    $thumb  = "thumb_{$ts}_{$rand}.jpg";

    // Cargar imagen
    $img = @imagecreatefromstring(file_get_contents($file['tmp_name']));
    if (!$img) return ['ok' => false, 'error' => 'No se pudo procesar la imagen'];

    $w = imagesx($img);
    $h = imagesy($img);

    // Redimensionar si supera 1400 px de ancho
    if ($w > 1400) {
        $h_new = (int)($h * 1400 / $w);
        $img2 = imagecreatetruecolor(1400, $h_new);
        imagecopyresampled($img2, $img, 0, 0, 0, 0, 1400, $h_new, $w, $h);
        imagedestroy($img);
        $img = $img2;
        $w = 1400;
        $h = $h_new;
    }
    imagejpeg($img, $dir . $nombre, 85);

    // Thumbnail 400×280 con crop centrado
    $ratio_src = $w / $h;
    $ratio_dst = 400 / 280;
    if ($ratio_src > $ratio_dst) {
        $src_h = $h;
        $src_w = (int)($h * $ratio_dst);
        $src_x = (int)(($w - $src_w) / 2);
        $src_y = 0;
    } else {
        $src_w = $w;
        $src_h = (int)($w / $ratio_dst);
        $src_x = 0;
        $src_y = (int)(($h - $src_h) / 2);
    }
    $th = imagecreatetruecolor(400, 280);
    imagecopyresampled($th, $img, 0, 0, $src_x, $src_y, 400, 280, $src_w, $src_h);
    imagejpeg($th, $dir . $thumb, 80);

    imagedestroy($img);
    imagedestroy($th);

    return ['ok' => true, 'nombre' => $nombre];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Fotos · <?= htmlspecialchars($curso['titulo']) ?></title>
  <link rel="stylesheet" href="/admin/assets/admin.css">
  <style>
    .fotos-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 16px; margin-top: 1rem; }
    .foto-item {
      border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;
      background: #fff; transition: opacity 0.2s, transform 0.2s;
    }
    .foto-item.dragging { opacity: 0.4; }
    .foto-item.drop-target { border-color: #0284C7; box-shadow: 0 0 0 2px rgba(2,132,199,0.15); }
    .foto-drag-handle { cursor: grab; padding: 6px 10px; background: #f1f5f9; font-size: 18px; user-select: none; color: #94a3b8; }
    .foto-drag-handle:active { cursor: grabbing; }
    .foto-img { width: 100%; height: 140px; object-fit: cover; display: block; }
    .foto-body { padding: 10px; }
    .foto-alt {
      width: 100%; font-size: 13px; padding: 4px 8px;
      border: 1px solid #d1d5db; border-radius: 4px; box-sizing: border-box; font-family: inherit;
    }
    .foto-alt:focus { outline: none; border-color: #0284C7; box-shadow: 0 0 0 2px rgba(2,132,199,0.15); }
    .foto-orden { font-size: 11px; color: #64748B; margin-bottom: 4px; }
    .upload-zone {
      border: 2px dashed #d1d5db; border-radius: 10px; padding: 2rem;
      text-align: center; transition: all 0.2s; background: #f8f9fa;
    }
    .upload-zone:hover { border-color: #0284C7; background: #f0f7ff; }
    .upload-zone input[type="file"] { display: none; }
    .upload-zone label { cursor: pointer; color: #0284C7; font-weight: 600; }
  </style>
</head>
<body class="admin-body">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <div class="admin-main">
    <?php include __DIR__ . '/partials/topbar.php'; ?>
    <div class="admin-content" style="max-width:1100px;">

      <a href="/admin/cursos.php" style="color:#0284C7; font-size:0.85rem;">&larr; Volver al listado de cursos</a>

      <div class="admin-content__header" style="margin-top:0.75rem;">
        <h1>📷 Fotos: <?= htmlspecialchars($curso['titulo']) ?></h1>
        <a href="/admin/editor-curso.php?id=<?= $curso_id ?>" class="btn-admin btn-admin--outline">Editar curso</a>
      </div>

      <?php if ($flash_msg): ?>
      <div class="alert alert--<?= $flash_tipo === 'error' ? 'error' : ($flash_tipo === 'warning' ? 'warning' : 'success') ?>">
        <?= htmlspecialchars($flash_msg) ?>
      </div>
      <?php endif; ?>

      <?php $espacio_disponible = $max_fotos - count($fotos); ?>

      <!-- Subida -->
      <div class="editor-section">
        <h2>Subir nuevas fotos</h2>
        <p style="font-size:0.85rem; color:#64748B; margin-bottom:1rem;">
          <?= count($fotos) ?>/<?= $max_fotos ?> fotos · JPG, PNG o WEBP · máx. 5 MB por foto · se redimensionan a 1400 px de ancho.
        </p>

        <?php if ($espacio_disponible > 0): ?>
        <form method="POST" enctype="multipart/form-data" class="upload-zone">
          <?= campo_csrf() ?>
          <input type="file" name="fotos[]" id="fotos-input" accept="image/jpeg,image/png,image/webp" multiple>
          <label for="fotos-input">
            <div style="font-size:2rem;">📸</div>
            <div style="margin-top:0.5rem;">Haz clic o arrastra imágenes</div>
            <div style="font-size:0.8rem; color:#94a3b8; margin-top:0.25rem;">Quedan <?= $espacio_disponible ?> huecos disponibles</div>
          </label>
          <div id="files-preview" style="margin-top:1rem; font-size:0.85rem;"></div>
          <button type="submit" class="btn-admin btn-admin--primary" id="btn-submit" style="display:none; margin-top:1rem;">Subir fotos</button>
        </form>
        <?php else: ?>
        <div class="alert alert--warning">
          Este curso tiene el máximo de <?= $max_fotos ?> fotos. Elimina alguna antes de subir más.
        </div>
        <?php endif; ?>
      </div>

      <!-- Grid de fotos existentes -->
      <div class="editor-section">
        <h2>Fotos actuales <span style="color:#64748B; font-weight:400;">— arrastra para reordenar</span></h2>

        <?php if (empty($fotos)): ?>
        <p style="color:#94a3b8; padding:1rem 0;">Aún no hay fotos. Sube la primera arriba.</p>
        <?php else: ?>
        <div class="fotos-grid" id="fotos-grid">
          <?php foreach ($fotos as $i => $f):
            $thumb = str_replace('foto_', 'thumb_', $f['nombre_archivo']);
            $url_thumb = "/uploads/cursos/{$curso_id}/{$thumb}";
            $url_full  = "/uploads/cursos/{$curso_id}/" . $f['nombre_archivo'];
          ?>
          <div class="foto-item" data-id="<?= $f['id'] ?>" draggable="true">
            <div class="foto-drag-handle">⠿ Posición #<?= $i + 1 ?></div>
            <a href="<?= htmlspecialchars($url_full) ?>" target="_blank" title="Ver original">
              <img src="<?= htmlspecialchars($url_thumb) ?>" alt="" class="foto-img" loading="lazy">
            </a>
            <div class="foto-body">
              <input type="text" class="foto-alt" data-foto-id="<?= $f['id'] ?>"
                     value="<?= htmlspecialchars($f['alt_text']) ?>"
                     placeholder="Texto alternativo (SEO)" maxlength="255">
              <button type="button" class="btn-admin btn-admin--sm btn-admin--danger" style="margin-top:8px; width:100%;"
                      onclick="eliminarFoto(<?= $f['id'] ?>, this)">🗑️ Eliminar</button>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>

    </div>
  </div>

<script>
const CSRF = '<?= htmlspecialchars(generar_csrf()) ?>';

// ─── Preview archivos antes de subir ───
const filesInput = document.getElementById('fotos-input');
if (filesInput) {
  filesInput.addEventListener('change', function() {
    const preview = document.getElementById('files-preview');
    const btn = document.getElementById('btn-submit');
    if (this.files.length === 0) {
      preview.innerHTML = '';
      btn.style.display = 'none';
      return;
    }
    let html = '<strong>' + this.files.length + ' archivo(s) seleccionado(s):</strong><ul style="text-align:left; max-width:400px; margin:0.5rem auto;">';
    for (const f of this.files) {
      html += '<li>' + f.name + ' <span style="color:#94a3b8;">(' + (f.size/1024/1024).toFixed(2) + ' MB)</span></li>';
    }
    html += '</ul>';
    preview.innerHTML = html;
    btn.style.display = 'inline-flex';
  });
}

// ─── Drag & Drop ───
let dragSrc = null;

document.querySelectorAll('.foto-item').forEach(item => {
  item.addEventListener('dragstart', e => {
    dragSrc = item;
    e.dataTransfer.effectAllowed = 'move';
    setTimeout(() => item.classList.add('dragging'), 0);
  });
  item.addEventListener('dragend', () => {
    item.classList.remove('dragging');
    document.querySelectorAll('.foto-item').forEach(el => el.classList.remove('drop-target'));
    guardarOrden();
  });
  item.addEventListener('dragover', e => {
    e.preventDefault();
    e.dataTransfer.dropEffect = 'move';
    if (dragSrc !== item) item.classList.add('drop-target');
  });
  item.addEventListener('dragleave', () => {
    item.classList.remove('drop-target');
  });
  item.addEventListener('drop', e => {
    e.preventDefault();
    item.classList.remove('drop-target');
    if (dragSrc && dragSrc !== item) {
      const grid = document.getElementById('fotos-grid');
      const items = [...grid.querySelectorAll('.foto-item')];
      const srcIdx = items.indexOf(dragSrc);
      const dstIdx = items.indexOf(item);
      if (srcIdx < dstIdx) item.after(dragSrc);
      else item.before(dragSrc);
    }
  });

  // Guardar alt text al perder foco
  const altInput = item.querySelector('.foto-alt');
  if (altInput) {
    altInput.addEventListener('blur', function() {
      const orig = this.style.borderColor;
      fetch('/admin/api/actualizar-foto.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: this.dataset.fotoId, alt: this.value, csrf_token: CSRF })
      })
        .then(r => r.json())
        .then(d => {
          if (d.ok) {
            this.style.borderColor = '#16a34a';
            setTimeout(() => { this.style.borderColor = orig; }, 1000);
          }
        });
    });
  }
});

function guardarOrden() {
  const orden = [...document.querySelectorAll('.foto-item')].map((el, i) => ({
    id: parseInt(el.dataset.id, 10),
    orden: i,
  }));
  // Actualizar visualmente los números
  document.querySelectorAll('.foto-item').forEach((el, i) => {
    const handle = el.querySelector('.foto-drag-handle');
    if (handle) handle.textContent = '⠿ Posición #' + (i + 1);
  });
  fetch('/admin/api/reordenar-fotos.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ orden, csrf_token: CSRF })
  });
}

function eliminarFoto(id, btn) {
  if (!confirm('¿Eliminar esta foto? Se borrarán los archivos físicos.')) return;
  btn.disabled = true;
  btn.textContent = 'Eliminando...';
  fetch('/admin/api/eliminar-foto.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ id, csrf_token: CSRF })
  })
    .then(r => r.json())
    .then(d => {
      if (d.ok) {
        const item = document.querySelector('[data-id="' + id + '"]');
        if (item) item.remove();
        // Renumerar
        document.querySelectorAll('.foto-item').forEach((el, i) => {
          const h = el.querySelector('.foto-drag-handle');
          if (h) h.textContent = '⠿ Posición #' + (i + 1);
        });
      } else {
        alert('Error: ' + (d.error || 'No se pudo eliminar'));
        btn.disabled = false;
        btn.textContent = '🗑️ Eliminar';
      }
    });
}
</script>
</body>
</html>
