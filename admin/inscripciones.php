<?php
/**
 * Back Office — Gestión de inscripciones en cursos
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requiere_login();

$db = get_db();

// CSV Export (antes de cualquier output HTML)
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $filtro_curso_csv = (int)($_GET['curso'] ?? 0);
    $filtro_estado_csv = $_GET['estado'] ?? '';

    $where_csv = [];
    $params_csv = [];
    if ($filtro_curso_csv) {
        $where_csv[] = "i.curso_id = :curso_id";
        $params_csv[':curso_id'] = $filtro_curso_csv;
    }
    if ($filtro_estado_csv && in_array($filtro_estado_csv, ['pendiente','confirmada','cancelada','lista_espera'])) {
        $where_csv[] = "i.estado = :estado";
        $params_csv[':estado'] = $filtro_estado_csv;
    }
    $where_csv_sql = $where_csv ? 'WHERE ' . implode(' AND ', $where_csv) : '';

    $stmt_csv = $db->prepare("
        SELECT i.*, c.titulo AS curso_titulo, c.idioma AS curso_idioma
        FROM cursos_inscripciones i
        JOIN cursos c ON c.id = i.curso_id
        $where_csv_sql
        ORDER BY i.created_at DESC
    ");
    $stmt_csv->execute($params_csv);
    $rows = $stmt_csv->fetchAll();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="inscripciones-cursos-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($out, ['Nombre', 'Email', 'Teléfono', 'País', 'Organización', 'Curso', 'Idioma', 'Estado', 'Mensaje', 'Fecha inscripción'], ';');
    foreach ($rows as $row) {
        fputcsv($out, [$row['nombre'], $row['email'], $row['telefono'], $row['pais'], $row['organizacion'], $row['curso_titulo'], strtoupper($row['curso_idioma']), ucfirst($row['estado']), $row['mensaje'], $row['created_at']], ';');
    }
    fclose($out);
    exit;
}

// Acciones POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verificar_csrf()) {
    $accion = $_POST['accion'] ?? '';
    $insc_id = (int)($_POST['id'] ?? 0);

    if ($insc_id) {
        if ($accion === 'confirmar') {
            $db->prepare("UPDATE cursos_inscripciones SET estado = 'confirmada' WHERE id = :id")->execute([':id' => $insc_id]);
        } elseif ($accion === 'cancelar') {
            $insc = $db->prepare("SELECT curso_id, estado FROM cursos_inscripciones WHERE id = :id")->execute([':id' => $insc_id]);
            $insc = $db->prepare("SELECT curso_id, estado FROM cursos_inscripciones WHERE id = :id LIMIT 1");
            $insc->execute([':id' => $insc_id]);
            $insc = $insc->fetch();
            $db->prepare("UPDATE cursos_inscripciones SET estado = 'cancelada' WHERE id = :id")->execute([':id' => $insc_id]);
            if ($insc && $insc['estado'] !== 'cancelada' && $insc['estado'] !== 'lista_espera') {
                $db->prepare("UPDATE cursos SET inscritos = GREATEST(inscritos - 1, 0) WHERE id = :id")->execute([':id' => $insc['curso_id']]);
            }
        } elseif ($accion === 'eliminar') {
            $insc = $db->prepare("SELECT curso_id, estado FROM cursos_inscripciones WHERE id = :id LIMIT 1");
            $insc->execute([':id' => $insc_id]);
            $insc = $insc->fetch();
            $db->prepare("DELETE FROM cursos_inscripciones WHERE id = :id")->execute([':id' => $insc_id]);
            if ($insc && $insc['estado'] !== 'cancelada' && $insc['estado'] !== 'lista_espera') {
                $db->prepare("UPDATE cursos SET inscritos = GREATEST(inscritos - 1, 0) WHERE id = :id")->execute([':id' => $insc['curso_id']]);
            }
        } elseif ($accion === 'notas') {
            $notas = trim($_POST['notas_admin'] ?? '');
            $db->prepare("UPDATE cursos_inscripciones SET notas_admin = :notas WHERE id = :id")->execute([':notas' => $notas, ':id' => $insc_id]);
        }
    }

    header('Location: /admin/inscripciones.php?' . http_build_query(array_filter([
        'curso' => $_GET['curso'] ?? '',
        'estado' => $_GET['estado'] ?? '',
        'msg' => 'ok'
    ])));
    exit;
}

// Filtros
$filtro_curso = (int)($_GET['curso'] ?? 0);
$filtro_estado = $_GET['estado'] ?? '';
$filtro_busca = trim($_GET['q'] ?? '');

// Obtener cursos para filtro
$cursos = $db->query("SELECT id, titulo, idioma FROM cursos ORDER BY fecha_inicio DESC")->fetchAll();

// Query inscripciones
$where = [];
$params = [];

if ($filtro_curso) {
    $where[] = "i.curso_id = :curso_id";
    $params[':curso_id'] = $filtro_curso;
}
if ($filtro_estado && in_array($filtro_estado, ['pendiente','confirmada','cancelada','lista_espera'])) {
    $where[] = "i.estado = :estado";
    $params[':estado'] = $filtro_estado;
}
if ($filtro_busca) {
    $where[] = "(i.nombre LIKE :q OR i.email LIKE :q OR i.organizacion LIKE :q)";
    $params[':q'] = '%' . $filtro_busca . '%';
}

$where_sql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $db->prepare("
    SELECT i.*, c.titulo AS curso_titulo, c.idioma AS curso_idioma
    FROM cursos_inscripciones i
    JOIN cursos c ON c.id = i.curso_id
    $where_sql
    ORDER BY i.created_at DESC
");
$stmt->execute($params);
$inscripciones = $stmt->fetchAll();

// Stats
$total = count($inscripciones);
$pendientes = count(array_filter($inscripciones, fn($i) => $i['estado'] === 'pendiente'));
$confirmadas = count(array_filter($inscripciones, fn($i) => $i['estado'] === 'confirmada'));

$msg = $_GET['msg'] ?? '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Inscripciones — EuryGo Admin</title>
  <link rel="stylesheet" href="/admin/assets/admin.css">
  <meta name="robots" content="noindex, nofollow">
</head>
<body class="admin-body">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>

  <div class="admin-main">
    <?php include __DIR__ . '/partials/topbar.php'; ?>

    <div class="admin-content">
      <div class="admin-content__header">
        <h1>Inscripciones en cursos</h1>
        <a href="/admin/inscripciones.php?export=csv<?= $filtro_curso ? '&curso=' . $filtro_curso : '' ?><?= $filtro_estado ? '&estado=' . $filtro_estado : '' ?>" class="btn-admin btn-admin--outline">Exportar CSV</a>
      </div>

<?php if ($msg === 'ok'): ?>
      <div class="alert alert--success">Acción realizada correctamente.</div>
<?php endif; ?>

      <!-- Stats -->
      <div class="stats-grid" style="margin-bottom:1.5rem;">
        <div class="stat-card">
          <div class="stat-card__number"><?= $total ?></div>
          <div class="stat-card__label">Total inscripciones</div>
        </div>
        <div class="stat-card">
          <div class="stat-card__number" style="color:#D97706;"><?= $pendientes ?></div>
          <div class="stat-card__label">Pendientes</div>
        </div>
        <div class="stat-card">
          <div class="stat-card__number" style="color:#16a34a;"><?= $confirmadas ?></div>
          <div class="stat-card__label">Confirmadas</div>
        </div>
      </div>

      <!-- Filtros -->
      <form class="admin-filters" method="GET" action="">
        <select name="curso">
          <option value="">Todos los cursos</option>
<?php foreach ($cursos as $c): ?>
          <option value="<?= $c['id'] ?>" <?= $filtro_curso == $c['id'] ? 'selected' : '' ?>><?= htmlspecialchars($c['titulo']) ?> (<?= strtoupper($c['idioma']) ?>)</option>
<?php endforeach; ?>
        </select>
        <select name="estado">
          <option value="">Todos los estados</option>
          <option value="pendiente" <?= $filtro_estado === 'pendiente' ? 'selected' : '' ?>>Pendiente</option>
          <option value="confirmada" <?= $filtro_estado === 'confirmada' ? 'selected' : '' ?>>Confirmada</option>
          <option value="cancelada" <?= $filtro_estado === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
          <option value="lista_espera" <?= $filtro_estado === 'lista_espera' ? 'selected' : '' ?>>Lista de espera</option>
        </select>
        <input type="text" name="q" value="<?= htmlspecialchars($filtro_busca) ?>" placeholder="Buscar nombre, email, organización…">
        <button type="submit" class="btn-admin btn-admin--sm">Filtrar</button>
      </form>

      <!-- Tabla -->
      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Nombre</th>
              <th>Email</th>
              <th>País</th>
              <th>Organización</th>
              <th>Curso</th>
              <th>Estado</th>
              <th>Fecha</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
<?php if (empty($inscripciones)): ?>
            <tr><td colspan="8" style="text-align:center; padding:2rem; color:#999;">No hay inscripciones.</td></tr>
<?php else: ?>
<?php foreach ($inscripciones as $insc): ?>
            <tr>
              <td><strong><?= htmlspecialchars($insc['nombre']) ?></strong></td>
              <td><a href="mailto:<?= htmlspecialchars($insc['email']) ?>"><?= htmlspecialchars($insc['email']) ?></a></td>
              <td><?= htmlspecialchars($insc['pais']) ?></td>
              <td><?= htmlspecialchars($insc['organizacion'] ?: '—') ?></td>
              <td style="font-size:0.85rem;"><?= htmlspecialchars(mb_substr($insc['curso_titulo'], 0, 40)) ?>… <span class="badge"><?= strtoupper($insc['curso_idioma']) ?></span></td>
              <td>
                <span class="badge badge--<?= $insc['estado'] === 'confirmada' ? 'success' : ($insc['estado'] === 'pendiente' ? 'warning' : ($insc['estado'] === 'cancelada' ? 'error' : 'info')) ?>">
                  <?= ucfirst(str_replace('_', ' ', $insc['estado'])) ?>
                </span>
              </td>
              <td style="font-size:0.85rem; white-space:nowrap;"><?= date('d/m/Y H:i', strtotime($insc['created_at'])) ?></td>
              <td style="white-space:nowrap;">
<?php if ($insc['estado'] === 'pendiente'): ?>
                <form method="POST" style="display:inline;">
                  <?= campo_csrf() ?>
                  <input type="hidden" name="accion" value="confirmar">
                  <input type="hidden" name="id" value="<?= $insc['id'] ?>">
                  <button type="submit" class="btn-admin btn-admin--sm btn-admin--success">Confirmar</button>
                </form>
<?php endif; ?>
<?php if ($insc['estado'] !== 'cancelada'): ?>
                <form method="POST" style="display:inline;" onsubmit="return confirm('¿Cancelar esta inscripción?')">
                  <?= campo_csrf() ?>
                  <input type="hidden" name="accion" value="cancelar">
                  <input type="hidden" name="id" value="<?= $insc['id'] ?>">
                  <button type="submit" class="btn-admin btn-admin--sm btn-admin--outline">Cancelar</button>
                </form>
<?php endif; ?>
                <form method="POST" style="display:inline;" onsubmit="return confirm('¿Eliminar esta inscripción? Esta acción no se puede deshacer.')">
                  <?= campo_csrf() ?>
                  <input type="hidden" name="accion" value="eliminar">
                  <input type="hidden" name="id" value="<?= $insc['id'] ?>">
                  <button type="submit" class="btn-admin btn-admin--sm btn-admin--danger">Eliminar</button>
                </form>
              </td>
            </tr>
<?php if ($insc['mensaje']): ?>
            <tr>
              <td colspan="8" style="background:#f8f9fa; font-size:0.85rem; padding:0.5rem 1rem;">
                <strong>Mensaje:</strong> <?= htmlspecialchars($insc['mensaje']) ?>
<?php if ($insc['notas_admin']): ?>
                <br><strong style="color:#0284C7;">Notas admin:</strong> <?= htmlspecialchars($insc['notas_admin']) ?>
<?php endif; ?>
              </td>
            </tr>
<?php endif; ?>
<?php endforeach; ?>
<?php endif; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</body>
</html>
