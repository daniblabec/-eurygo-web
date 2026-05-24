<?php
/**
 * Back Office — Gestor de imágenes del inicio
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requiere_login();

$db = get_db();
$csrf = generar_csrf();

$labels = [
    'hero'     => ['nombre' => 'Hero (cabecera principal)', 'desc' => 'Fondo de la sección principal. Recomendado: 1920×800px mínimo, horizontal.', 'max' => '1920px'],
    'about'    => ['nombre' => 'Sección ¿Qué es EuryGo?',  'desc' => 'Ilustración de la sección sobre nosotros. Recomendado: 1200×800px.', 'max' => '1200px'],
    'schools'  => ['nombre' => 'Sección Centros Escolares', 'desc' => 'Imagen de la sección de centros. Recomendado: 1200×800px.', 'max' => '1200px'],
    'agencies' => ['nombre' => 'Sección Agencias',          'desc' => 'Imagen de la sección de agencias. Recomendado: 1200×800px.', 'max' => '1200px'],
];

// Asegurar que existen los 4 registros en la BD
$stmt = $db->query("SELECT posicion FROM home_imagenes");
$existentes = $stmt->fetchAll(PDO::FETCH_COLUMN);
$orden_map = ['hero' => 1, 'about' => 2, 'schools' => 3, 'agencies' => 4];
foreach (array_keys($labels) as $pos) {
    if (!in_array($pos, $existentes)) {
        $db->prepare("INSERT INTO home_imagenes (posicion, titulo, alt_texto, activa, orden) VALUES (?, ?, '', 1, ?)")
           ->execute([$pos, $labels[$pos]['nombre'], $orden_map[$pos]]);
    }
}

// Obtener las 4 posiciones
$stmt = $db->query("SELECT * FROM home_imagenes WHERE activa = 1 ORDER BY orden ASC");
$imagenes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Imágenes del inicio — EuryGo Admin</title>
  <link rel="stylesheet" href="/admin/assets/admin.css">
  <meta name="robots" content="noindex, nofollow">
  <style>
    .img-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 1.5rem;
      margin-top: 1.5rem;
    }
    @media (max-width: 900px) {
      .img-grid { grid-template-columns: 1fr; }
    }
    .img-card {
      background: #fff;
      border-radius: 8px;
      box-shadow: 0 1px 3px rgba(0,0,0,0.08);
      overflow: hidden;
    }
    .img-card__preview {
      width: 100%;
      aspect-ratio: 16/9;
      background: linear-gradient(135deg, #e2e8f0, #f1f5f9);
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      position: relative;
    }
    .img-card__preview img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    .img-card__preview .placeholder-svg {
      width: 64px;
      height: 64px;
      opacity: 0.3;
    }
    .img-card__body {
      padding: 1.25rem;
    }
    .img-card__position {
      font-size: 0.75rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: #0284C7;
      margin-bottom: 0.25rem;
    }
    .img-card__title {
      font-size: 1rem;
      font-weight: 600;
      margin-bottom: 0.25rem;
    }
    .img-card__desc {
      font-size: 0.82rem;
      color: #64748B;
      margin-bottom: 0.75rem;
    }
    .img-card__alt {
      font-size: 0.8rem;
      color: #64748B;
      background: #f8fafc;
      padding: 0.4rem 0.6rem;
      border-radius: 4px;
      margin-bottom: 1rem;
      word-break: break-word;
    }
    .img-card__alt strong {
      color: #1a1a1a;
    }

    /* Form dentro de la tarjeta */
    .img-form { display: none; padding: 1.25rem; border-top: 1px solid #e2e8f0; background: #f8fafc; }
    .img-form.active { display: block; }
    .img-form .form-group { margin-bottom: 1rem; }
    .img-form label { display: block; font-weight: 600; font-size: 0.82rem; margin-bottom: 0.3rem; }
    .img-form input[type="text"],
    .img-form textarea {
      width: 100%;
      padding: 0.5rem 0.75rem;
      border: 1px solid #d1d5db;
      border-radius: 6px;
      font-size: 0.88rem;
      font-family: inherit;
    }
    .img-form input:focus,
    .img-form textarea:focus {
      outline: none;
      border-color: #0284C7;
      box-shadow: 0 0 0 3px rgba(2,132,199,0.15);
    }

    .file-drop {
      border: 2px dashed #d1d5db;
      border-radius: 8px;
      padding: 1.5rem;
      text-align: center;
      cursor: pointer;
      transition: border-color 0.2s, background 0.2s;
      position: relative;
    }
    .file-drop:hover,
    .file-drop.dragover {
      border-color: #0284C7;
      background: #f0f9ff;
    }
    .file-drop input[type="file"] {
      position: absolute;
      inset: 0;
      opacity: 0;
      cursor: pointer;
    }
    .file-drop__text {
      font-size: 0.88rem;
      color: #64748B;
    }
    .file-drop__text strong {
      color: #0284C7;
    }
    .file-drop__preview {
      max-height: 160px;
      border-radius: 6px;
      margin-top: 0.75rem;
      display: none;
    }

    .img-form__actions {
      display: flex;
      gap: 0.5rem;
      margin-top: 1rem;
    }

    .img-feedback {
      padding: 0.6rem 1rem;
      border-radius: 6px;
      font-size: 0.88rem;
      margin-top: 0.75rem;
      display: none;
    }
    .img-feedback--ok { background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; }
    .img-feedback--error { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
  </style>
</head>
<body class="admin-body">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <div class="admin-main">
    <?php include __DIR__ . '/partials/topbar.php'; ?>

    <div class="admin-content">
      <h1>Imágenes del inicio</h1>
      <p style="color:#64748B;margin-bottom:0.5rem;">Gestiona las fotos que aparecen en la página principal de eurygo.com. Haz clic en «Cambiar imagen» para subir una nueva foto.</p>

      <div class="img-grid">
<?php foreach ($imagenes as $img):
    $pos = $img['posicion'];
    $info = $labels[$pos] ?? ['nombre' => $pos, 'desc' => '', 'max' => '1200px'];
    $tiene_foto = !empty($img['ruta_imagen']);
?>
        <div class="img-card" id="card-<?= $pos ?>">
          <div class="img-card__preview" id="preview-<?= $pos ?>">
<?php if ($tiene_foto): ?>
            <img src="<?= htmlspecialchars($img['ruta_imagen']) ?>?v=<?= time() ?>" alt="<?= htmlspecialchars($img['alt_texto']) ?>">
<?php else: ?>
            <svg class="placeholder-svg" viewBox="0 0 24 24" fill="#94a3b8"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
<?php endif; ?>
          </div>
          <div class="img-card__body">
            <div class="img-card__position"><?= htmlspecialchars($info['nombre']) ?></div>
            <div class="img-card__desc"><?= htmlspecialchars($info['desc']) ?></div>
<?php if ($img['alt_texto']): ?>
            <div class="img-card__alt"><strong>Alt:</strong> <?= htmlspecialchars($img['alt_texto']) ?></div>
<?php endif; ?>
            <button type="button" class="btn-admin btn-admin--primary btn-admin--sm" onclick="toggleForm('<?= $pos ?>')">
              <?= $tiene_foto ? '&#9998; Cambiar imagen' : '&#10133; Añadir imagen' ?>
            </button>
          </div>

          <!-- Formulario inline -->
          <div class="img-form" id="form-<?= $pos ?>">
            <form onsubmit="return subirImagen(event, '<?= $pos ?>')">
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
              <input type="hidden" name="posicion" value="<?= $pos ?>">

              <div class="form-group">
                <label>Nueva imagen <small>(JPG, PNG o WEBP — máx 5MB)</small></label>
                <div class="file-drop" id="drop-<?= $pos ?>">
                  <input type="file" name="file" accept="image/jpeg,image/png,image/webp" onchange="previewFile(this, '<?= $pos ?>')">
                  <div class="file-drop__text">
                    <strong>Haz clic</strong> o arrastra una imagen aquí
                  </div>
                  <img class="file-drop__preview" id="file-preview-<?= $pos ?>" alt="Vista previa">
                </div>
              </div>

              <div class="form-group">
                <label>Texto alternativo (alt) * <small>Obligatorio para SEO</small></label>
                <input type="text" name="alt_texto" value="<?= htmlspecialchars($img['alt_texto']) ?>" required
                       placeholder="Ej: Docentes europeos en el centro histórico de Jerez">
              </div>

              <div class="form-group">
                <label>Título <small>(uso interno)</small></label>
                <input type="text" name="titulo" value="<?= htmlspecialchars($img['titulo']) ?>"
                       placeholder="Ej: Foto hero principal">
              </div>

              <div class="img-form__actions">
                <button type="submit" class="btn-admin btn-admin--primary btn-admin--sm" id="btn-<?= $pos ?>">Guardar</button>
                <button type="button" class="btn-admin btn-admin--outline btn-admin--sm" onclick="toggleForm('<?= $pos ?>')">Cancelar</button>
              </div>

              <div class="img-feedback" id="feedback-<?= $pos ?>"></div>
            </form>
          </div>
        </div>
<?php endforeach; ?>
      </div>
    </div>
  </div>

  <script>
  function toggleForm(pos) {
    const form = document.getElementById('form-' + pos);
    form.classList.toggle('active');
  }

  function previewFile(input, pos) {
    const preview = document.getElementById('file-preview-' + pos);
    if (input.files && input.files[0]) {
      const reader = new FileReader();
      reader.onload = function(e) {
        preview.src = e.target.result;
        preview.style.display = 'block';
      };
      reader.readAsDataURL(input.files[0]);
    }
  }

  // Drag & drop
  document.querySelectorAll('.file-drop').forEach(function(drop) {
    drop.addEventListener('dragover', function(e) {
      e.preventDefault();
      drop.classList.add('dragover');
    });
    drop.addEventListener('dragleave', function() {
      drop.classList.remove('dragover');
    });
    drop.addEventListener('drop', function(e) {
      e.preventDefault();
      drop.classList.remove('dragover');
      var input = drop.querySelector('input[type="file"]');
      if (e.dataTransfer.files.length) {
        input.files = e.dataTransfer.files;
        input.dispatchEvent(new Event('change'));
      }
    });
  });

  function subirImagen(e, pos) {
    e.preventDefault();
    var form = e.target;
    var btn = document.getElementById('btn-' + pos);
    var feedback = document.getElementById('feedback-' + pos);
    var data = new FormData(form);

    btn.disabled = true;
    btn.textContent = 'Subiendo...';
    feedback.style.display = 'none';

    fetch('/admin/upload-imagen-home.php', {
      method: 'POST',
      body: data
    })
    .then(function(res) { return res.json(); })
    .then(function(json) {
      if (json.ok) {
        feedback.className = 'img-feedback img-feedback--ok';
        feedback.textContent = json.mensaje;
        feedback.style.display = 'block';

        // Actualizar vista previa de la tarjeta sin recargar
        if (json.ruta) {
          var previewDiv = document.getElementById('preview-' + pos);
          previewDiv.innerHTML = '<img src="' + json.ruta + '?v=' + Date.now() + '" alt="Vista previa">';
        }

        // Actualizar alt text visible
        var card = document.getElementById('card-' + pos);
        var altDiv = card.querySelector('.img-card__alt');
        var altVal = form.querySelector('[name="alt_texto"]').value;
        if (altDiv) {
          altDiv.innerHTML = '<strong>Alt:</strong> ' + altVal;
        } else {
          var body = card.querySelector('.img-card__body');
          var btn = body.querySelector('button');
          var newAlt = document.createElement('div');
          newAlt.className = 'img-card__alt';
          newAlt.innerHTML = '<strong>Alt:</strong> ' + altVal;
          body.insertBefore(newAlt, btn);
        }

        // Cambiar botón a "Cambiar imagen" si era "Añadir"
        var mainBtn = card.querySelector('.img-card__body button');
        mainBtn.innerHTML = '&#9998; Cambiar imagen';

        // Cerrar formulario tras 1.5s
        setTimeout(function() {
          toggleForm(pos);
          feedback.style.display = 'none';
        }, 1500);
      } else {
        feedback.className = 'img-feedback img-feedback--error';
        feedback.textContent = json.error || 'Error desconocido';
        feedback.style.display = 'block';
      }
    })
    .catch(function(err) {
      feedback.className = 'img-feedback img-feedback--error';
      feedback.textContent = 'Error de conexión: ' + err.message;
      feedback.style.display = 'block';
    })
    .finally(function() {
      btn.disabled = false;
      btn.textContent = 'Guardar';
    });

    return false;
  }
  </script>
</body>
</html>
