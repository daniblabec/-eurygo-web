<?php
/**
 * Back Office — Listado de cursos de formación
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requiere_login();

$db = get_db();

// Acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verificar_csrf()) {
    $accion = $_POST['accion'] ?? '';
    $id = (int)($_POST['id'] ?? 0);

    if ($accion === 'borrar' && $id) {
        $db->prepare("DELETE FROM cursos WHERE id = :id")->execute([':id' => $id]);
        header('Location: /admin/cursos.php?msg=borrado');
        exit;
    }
    if ($accion === 'duplicar' && $id) {
        $orig = $db->prepare("SELECT * FROM cursos WHERE id = :id")->execute([':id' => $id]);
        $orig = $db->prepare("SELECT * FROM cursos WHERE id = :id LIMIT 1");
        $orig->execute([':id' => $id]);
        $orig = $orig->fetch();
        if ($orig) {
            $nuevo_slug = $orig['slug'] . '-copia-' . time();
            $stmt = $db->prepare("INSERT INTO cursos (titulo, slug, idioma, extracto, descripcion, imagen, precio, moneda, duracion_dias, plazas, inscritos, fecha_inicio, fecha_fin, ubicacion, estado, destacado, meta_title, meta_description)
                VALUES (:titulo, :slug, :idioma, :extracto, :descripcion, :imagen, :precio, :moneda, :duracion, :plazas, 0, :fi, :ff, :ubi, 'borrador', 0, :mt, :md)");
            $stmt->execute([
                ':titulo' => $orig['titulo'] . ' (copia)',
                ':slug' => $nuevo_slug,
                ':idioma' => $orig['idioma'],
                ':extracto' => $orig['extracto'],
                ':descripcion' => $orig['descripcion'],
                ':imagen' => $orig['imagen'],
                ':precio' => $orig['precio'],
                ':moneda' => $orig['moneda'],
                ':duracion' => $orig['duracion_dias'],
                ':plazas' => $orig['plazas'],
                ':fi' => $orig['fecha_inicio'],
                ':ff' => $orig['fecha_fin'],
                ':ubi' => $orig['ubicacion'],
                ':mt' => $orig['meta_title'],
                ':md' => $orig['meta_description'],
            ]);
            $nuevo_id = $db->lastInsertId();
            // Duplicar programa
            $progs = $db->prepare("SELECT * FROM cursos_programa WHERE curso_id = :id");
            $progs->execute([':id' => $id]);
            foreach ($progs->fetchAll() as $p) {
                $db->prepare("INSERT INTO cursos_programa (curso_id, dia, titulo, descripcion, horario, tipo, orden) VALUES (:cid, :dia, :tit, :desc, :hor, :tipo, :ord)")
                    ->execute([':cid' => $nuevo_id, ':dia' => $p['dia'], ':tit' => $p['titulo'], ':desc' => $p['descripcion'], ':hor' => $p['horario'], ':tipo' => $p['tipo'], ':ord' => $p['orden']]);
            }
            // Duplicar ediciones
            $eds = $db->prepare("SELECT * FROM cursos_ediciones WHERE curso_id = :id");
            $eds->execute([':id' => $id]);
            foreach ($eds->fetchAll() as $ed) {
                $db->prepare("INSERT INTO cursos_ediciones (curso_id, fecha_inicio, fecha_fin, plazas_totales, plazas_disponibles, estado, destacada) VALUES (:cid, :fi, :ff, :pt, :pd, :est, :dest)")
                    ->execute([':cid' => $nuevo_id, ':fi' => $ed['fecha_inicio'], ':ff' => $ed['fecha_fin'], ':pt' => $ed['plazas_totales'], ':pd' => $ed['plazas_disponibles'], ':est' => $ed['estado'], ':dest' => $ed['destacada']]);
            }
            header('Location: /admin/editor-curso.php?id=' . $nuevo_id . '&msg=duplicado');
            exit;
        }
    }
}

// Filtros
$filtro_idioma = $_GET['idioma'] ?? '';
$filtro_estado = $_GET['estado'] ?? '';
$filtro_busca = trim($_GET['q'] ?? '');

$where = [];
$params = [];

if ($filtro_idioma && in_array($filtro_idioma, ['es', 'en'])) {
    $where[] = "idioma = :idioma";
    $params[':idioma'] = $filtro_idioma;
}
if ($filtro_estado && in_array($filtro_estado, ['borrador','publicado','cancelado','finalizado'])) {
    $where[] = "estado = :estado";
    $params[':estado'] = $filtro_estado;
}
if ($filtro_busca) {
    $where[] = "titulo LIKE :q";
    $params[':q'] = '%' . $filtro_busca . '%';
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';
$stmt = $db->prepare("SELECT * FROM cursos $where_sql ORDER BY destacado DESC, fecha_inicio DESC");
$stmt->execute($params);
$cursos = $stmt->fetchAll();

// Cargar ediciones de todos los cursos
$ediciones_por_curso = [];
$stmt_ed = $db->query("SELECT * FROM cursos_ediciones ORDER BY fecha_inicio ASC");
foreach ($stmt_ed->fetchAll() as $ed) {
    $ediciones_por_curso[$ed['curso_id']][] = $ed;
}

// Cargar conteo de fotos por curso (la tabla puede no existir aún)
$fotos_por_curso = [];
try {
    $stmt_f = $db->query("SELECT curso_id, COUNT(*) AS n FROM curso_fotos GROUP BY curso_id");
    foreach ($stmt_f->fetchAll() as $row) {
        $fotos_por_curso[(int)$row['curso_id']] = (int)$row['n'];
    }
} catch (PDOException $e) {
    // tabla curso_fotos aún no migrada — ignorar
}

$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cursos — EuryGo Admin</title>
  <link rel="stylesheet" href="/admin/assets/admin.css">
  <meta name="robots" content="noindex, nofollow">
</head>
<body class="admin-body">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <div class="admin-main">
    <?php include __DIR__ . '/partials/topbar.php'; ?>

    <div class="admin-content">
      <div class="admin-content__header">
        <h1>Cursos de Formación</h1>
        <a href="/admin/editor-curso.php" class="btn-admin btn-admin--primary">+ Nuevo curso</a>
      </div>

<?php if ($msg === 'borrado'): ?>
      <div class="alert alert--success">Curso borrado correctamente.</div>
<?php elseif ($msg === 'guardado'): ?>
      <div class="alert alert--success">Curso guardado correctamente.</div>
<?php elseif ($msg === 'publicado'): ?>
      <div class="alert alert--success">Curso publicado correctamente.</div>
<?php endif; ?>

      <!-- Filtros -->
      <form class="admin-filters" method="GET" action="">
        <select name="idioma">
          <option value="">Todos los idiomas</option>
          <option value="es" <?= $filtro_idioma === 'es' ? 'selected' : '' ?>>ES</option>
          <option value="en" <?= $filtro_idioma === 'en' ? 'selected' : '' ?>>EN</option>
        </select>
        <select name="estado">
          <option value="">Todos los estados</option>
          <option value="borrador" <?= $filtro_estado === 'borrador' ? 'selected' : '' ?>>Borrador</option>
          <option value="publicado" <?= $filtro_estado === 'publicado' ? 'selected' : '' ?>>Publicado</option>
          <option value="cancelado" <?= $filtro_estado === 'cancelado' ? 'selected' : '' ?>>Cancelado</option>
          <option value="finalizado" <?= $filtro_estado === 'finalizado' ? 'selected' : '' ?>>Finalizado</option>
        </select>
        <input type="text" name="q" value="<?= htmlspecialchars($filtro_busca) ?>" placeholder="Buscar por título…">
        <button type="submit" class="btn-admin btn-admin--sm">Filtrar</button>
      </form>

      <!-- Tabla de cursos -->
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Portada</th>
              <th>Título</th>
              <th>Idioma</th>
              <th>Ediciones</th>
              <th>Precio</th>
              <th>Plazas</th>
              <th>Fotos</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
<?php if (empty($cursos)): ?>
            <tr><td colspan="9" style="text-align:center; padding:2rem; color:#999;">No hay cursos.</td></tr>
<?php else: ?>
<?php foreach ($cursos as $c): ?>
            <tr>
              <td>
<?php if (!empty($c['imagen'])): ?>
                <button type="button"
                        class="portada-thumb-btn"
                        data-curso-id="<?= $c['id'] ?>"
                        data-curso-titulo="<?= htmlspecialchars($c['titulo'], ENT_QUOTES) ?>"
                        data-imagen="<?= htmlspecialchars($c['imagen'], ENT_QUOTES) ?>"
                        title="Cambiar portada">
                  <img src="<?= htmlspecialchars($c['imagen']) ?>" alt="Portada de <?= htmlspecialchars($c['titulo']) ?>" class="portada-thumb">
                  <span class="portada-thumb-overlay">Cambiar</span>
                </button>
<?php else: ?>
                <button type="button"
                        class="portada-empty"
                        data-curso-id="<?= $c['id'] ?>"
                        data-curso-titulo="<?= htmlspecialchars($c['titulo'], ENT_QUOTES) ?>"
                        data-imagen=""
                        title="Subir portada">
                  + Subir portada
                </button>
<?php endif; ?>
              </td>
              <td>
                <strong><?= htmlspecialchars($c['titulo']) ?></strong>
                <?= $c['destacado'] ? '<span style="color:#D97706; font-size:0.75rem;">★ DESTACADO</span>' : '' ?>
              </td>
              <td><span class="badge"><?= strtoupper($c['idioma']) ?></span></td>
              <td style="font-size:0.82rem;">
<?php
    $eds = $ediciones_por_curso[$c['id']] ?? [];
    if (empty($eds)):
?>
                <span style="color:#999;">Sin ediciones</span>
<?php else: ?>
<?php   foreach ($eds as $ed):
            $ed_color = $ed['estado'] === 'abierta' ? '#16a34a' : ($ed['estado'] === 'cancelada' ? '#dc2626' : '#64748B');
?>
                <div style="margin-bottom:3px; white-space:nowrap;">
                  <span style="display:inline-block; width:8px; height:8px; border-radius:50%; background:<?= $ed_color ?>; margin-right:4px; vertical-align:middle;"></span>
                  <?= date('d/m', strtotime($ed['fecha_inicio'])) ?> — <?= date('d/m/Y', strtotime($ed['fecha_fin'])) ?>
                  <small style="color:#64748B;">(<?= $ed['plazas_disponibles'] ?>/<?= $ed['plazas_totales'] ?>)</small>
                </div>
<?php   endforeach; ?>
<?php endif; ?>
              </td>
              <td><strong><?= number_format($c['precio'], 0, ',', '.') ?>€</strong></td>
              <td>
<?php
    $total_plazas = 0;
    $total_ocupadas = 0;
    foreach ($eds as $ed) {
        $total_plazas += $ed['plazas_totales'];
        $total_ocupadas += ($ed['plazas_totales'] - $ed['plazas_disponibles']);
    }
    $pct = $total_plazas > 0 ? round($total_ocupadas / $total_plazas * 100) : 0;
?>
                <?= $total_ocupadas ?>/<?= $total_plazas ?>
                <div class="progress-bar" style="width:60px; display:inline-block; vertical-align:middle; margin-left:4px;">
                  <div class="progress-bar__fill" style="width:<?= $pct ?>%; <?= $pct >= 90 ? 'background:#dc3545;' : '' ?>"></div>
                </div>
              </td>
              <td>
<?php $nfotos = $fotos_por_curso[$c['id']] ?? 0; ?>
<?php if ($nfotos > 0): ?>
                <a href="/admin/curso-fotos.php?curso_id=<?= $c['id'] ?>" class="btn-admin btn-admin--sm" title="Gestionar slider">📷 <?= $nfotos ?> foto<?= $nfotos > 1 ? 's' : '' ?></a>
<?php else: ?>
                <a href="/admin/curso-fotos.php?curso_id=<?= $c['id'] ?>" class="btn-admin btn-admin--sm btn-admin--outline" style="color:#94a3b8;" title="Añadir fotos">📷 Sin fotos</a>
<?php endif; ?>
              </td>
              <td>
                <span class="badge badge--<?= $c['estado'] === 'publicado' ? 'success' : ($c['estado'] === 'borrador' ? 'warning' : ($c['estado'] === 'cancelado' ? 'error' : 'info')) ?>">
                  <?= ucfirst($c['estado']) ?>
                </span>
              </td>
              <td style="white-space:nowrap;">
                <a href="/admin/editor-curso.php?id=<?= $c['id'] ?>" class="btn-admin btn-admin--sm">Editar</a>
                <form method="POST" style="display:inline;" onsubmit="return confirm('¿Duplicar este curso?')">
                  <?= campo_csrf() ?>
                  <input type="hidden" name="accion" value="duplicar">
                  <input type="hidden" name="id" value="<?= $c['id'] ?>">
                  <button type="submit" class="btn-admin btn-admin--sm btn-admin--outline">Duplicar</button>
                </form>
                <form method="POST" style="display:inline;" onsubmit="return confirm('¿Borrar este curso? Esta acción no se puede deshacer.')">
                  <?= campo_csrf() ?>
                  <input type="hidden" name="accion" value="borrar">
                  <input type="hidden" name="id" value="<?= $c['id'] ?>">
                  <button type="submit" class="btn-admin btn-admin--sm btn-admin--danger">Borrar</button>
                </form>
              </td>
            </tr>
<?php endforeach; ?>
<?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>

  <!-- ─── Modal: subir / cambiar / eliminar portada ─── -->
  <div class="modal-overlay portada-modal" id="portada-modal" hidden>
    <div class="modal-box portada-modal__box" role="dialog" aria-labelledby="portada-modal-title" aria-modal="true">
      <button type="button" class="portada-modal__close" data-portada-close aria-label="Cerrar">&times;</button>
      <h3 id="portada-modal-title">Portada del curso</h3>
      <p style="color:#64748B; font-size:0.85rem; margin:-0.25rem 0 0.75rem;" id="portada-modal-curso">—</p>

      <div class="portada-modal__preview" id="portada-modal-preview">
        <span style="color:#94a3b8; font-size:0.85rem;">Sin imagen</span>
      </div>

      <form id="portada-form" enctype="multipart/form-data">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generar_csrf()) ?>">
        <input type="hidden" name="curso_id" id="portada-curso-id" value="">
        <input type="hidden" name="accion" id="portada-accion" value="subir">

        <label class="form-label" style="margin-top:0.75rem;">Nueva imagen (JPG, PNG o WEBP — máx 3 MB)</label>
        <input type="file" name="imagen" id="portada-file" class="form-input" accept="image/jpeg,image/png,image/webp">
        <small style="color:#64748B;">Se redimensiona automáticamente a 800×500 px (recorte centrado).</small>

        <div class="portada-modal__feedback" id="portada-feedback"></div>

        <div class="portada-modal__actions">
          <button type="button" class="btn-admin btn-admin--outline" data-portada-close>Cancelar</button>
          <button type="button" class="btn-admin btn-admin--danger" id="portada-eliminar" hidden>Eliminar portada</button>
          <button type="submit" class="btn-admin btn-admin--primary" id="portada-guardar">Guardar</button>
        </div>
      </form>
    </div>
  </div>

  <script>
  (function() {
    const modal     = document.getElementById('portada-modal');
    const preview   = document.getElementById('portada-modal-preview');
    const cursoLbl  = document.getElementById('portada-modal-curso');
    const idInput   = document.getElementById('portada-curso-id');
    const accInput  = document.getElementById('portada-accion');
    const fileInput = document.getElementById('portada-file');
    const feedback  = document.getElementById('portada-feedback');
    const form      = document.getElementById('portada-form');
    const btnSave   = document.getElementById('portada-guardar');
    const btnDel    = document.getElementById('portada-eliminar');

    function openModal(btn) {
      idInput.value  = btn.dataset.cursoId;
      accInput.value = 'subir';
      cursoLbl.textContent = btn.dataset.cursoTitulo;
      feedback.textContent = '';
      feedback.className = 'portada-modal__feedback';
      fileInput.value = '';
      const img = btn.dataset.imagen;
      if (img) {
        preview.innerHTML = '<img src="' + img + '" alt="Portada actual">';
        btnDel.hidden = false;
      } else {
        preview.innerHTML = '<span style="color:#94a3b8; font-size:0.85rem;">Sin imagen</span>';
        btnDel.hidden = true;
      }
      modal.hidden = false;
      document.body.style.overflow = 'hidden';
    }

    function closeModal() {
      modal.hidden = true;
      document.body.style.overflow = '';
    }

    function setFeedback(tipo, msg) {
      feedback.textContent = msg;
      feedback.className = 'portada-modal__feedback portada-modal__feedback--' + tipo;
    }

    // Bind apertura
    document.querySelectorAll('.portada-thumb-btn, .portada-empty').forEach(btn => {
      btn.addEventListener('click', () => openModal(btn));
    });

    // Cierre
    document.querySelectorAll('[data-portada-close]').forEach(b => b.addEventListener('click', closeModal));
    modal.addEventListener('click', e => { if (e.target === modal) closeModal(); });
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && !modal.hidden) closeModal(); });

    // Preview de la imagen al seleccionar
    fileInput.addEventListener('change', () => {
      const f = fileInput.files[0];
      if (!f) return;
      if (f.size > 3 * 1024 * 1024) {
        setFeedback('error', 'La imagen supera los 3 MB.');
        fileInput.value = '';
        return;
      }
      const reader = new FileReader();
      reader.onload = e => { preview.innerHTML = '<img src="' + e.target.result + '" alt="Previsualización">'; };
      reader.readAsDataURL(f);
    });

    // Guardar
    form.addEventListener('submit', async e => {
      e.preventDefault();
      if (!fileInput.files[0]) {
        setFeedback('error', 'Selecciona una imagen primero.');
        return;
      }
      accInput.value = 'subir';
      btnSave.disabled = true; btnSave.textContent = 'Subiendo…';
      try {
        const res = await fetch('/admin/api/portada-curso.php', { method: 'POST', body: new FormData(form) });
        const data = await res.json();
        if (data.ok) {
          setFeedback('ok', data.mensaje || 'Portada actualizada');
          setTimeout(() => location.reload(), 600);
        } else {
          setFeedback('error', data.error || 'Error al subir la imagen');
          btnSave.disabled = false; btnSave.textContent = 'Guardar';
        }
      } catch (err) {
        setFeedback('error', 'Error de conexión');
        btnSave.disabled = false; btnSave.textContent = 'Guardar';
      }
    });

    // Eliminar
    btnDel.addEventListener('click', async () => {
      if (!confirm('¿Eliminar la portada de este curso?')) return;
      accInput.value = 'eliminar';
      btnDel.disabled = true; btnDel.textContent = 'Eliminando…';
      const fd = new FormData(form);
      fd.delete('imagen'); // no enviar fichero
      try {
        const res = await fetch('/admin/api/portada-curso.php', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.ok) {
          setFeedback('ok', 'Portada eliminada');
          setTimeout(() => location.reload(), 600);
        } else {
          setFeedback('error', data.error || 'Error al eliminar');
          btnDel.disabled = false; btnDel.textContent = 'Eliminar portada';
        }
      } catch (err) {
        setFeedback('error', 'Error de conexión');
        btnDel.disabled = false; btnDel.textContent = 'Eliminar portada';
      }
    });
  })();
  </script>
</body>
</html>
