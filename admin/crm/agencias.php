<?php
/**
 * CRM EuryGo — Listado de agencias europeas
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requiere_login();

$db = get_db();

// Filtros (persistentes en sesión)
$session_key = 'crm_filtros_agencias';
if (isset($_GET['clear'])) {
    unset($_SESSION[$session_key]);
    header('Location: /admin/crm/agencias.php');
    exit;
}
if (!empty($_GET) && !isset($_GET['pagina'])) {
    $_SESSION[$session_key] = $_GET;
}
$g = $_SESSION[$session_key] ?? [];

$f_busca   = trim($g['q'] ?? '');
$f_pais    = $g['pais'] ?? '';
$f_aero    = $g['aeropuerto'] ?? '';
$f_volumen = $g['volumen'] ?? '';
$f_fiab    = $g['fiabilidad'] ?? '';
$f_estado  = $g['estado'] ?? '';

$w = ["pata = 'agencias'"];
$p = [];
if ($f_busca) { $w[] = "(nombre_centro LIKE :q OR contacto_nombre LIKE :q OR pais LIKE :q)"; $p[':q'] = "%$f_busca%"; }
if ($f_pais) { $w[] = "pais = :pais"; $p[':pais'] = $f_pais; }
if ($f_aero) { $w[] = "aeropuerto_cercano = :aero"; $p[':aero'] = $f_aero; }
if ($f_volumen) { $w[] = "volumen_estimado = :vol"; $p[':vol'] = $f_volumen; }
if ($f_fiab) { $w[] = "fiabilidad = :fiab"; $p[':fiab'] = (int)$f_fiab; }
if ($f_estado) { $w[] = "estado = :estado"; $p[':estado'] = $f_estado; }

$where_sql = 'WHERE ' . implode(' AND ', $w);

$sql = "SELECT * FROM crm_contactos $where_sql
        ORDER BY FIELD(aeropuerto_cercano,'XRY','SVQ','AGP','OTHER'),
                 FIELD(volumen_estimado,'grande','medio','pequeño','nd'),
                 fiabilidad DESC,
                 nombre_centro";
$st = $db->prepare($sql);
$st->execute($p);
$agencias = $st->fetchAll();

$paises = $db->query("SELECT DISTINCT pais FROM crm_contactos WHERE pata='agencias' AND pais IS NOT NULL AND pais <> '' ORDER BY pais")->fetchAll(PDO::FETCH_COLUMN);
$total = count($agencias);

function estado_label2($e) {
    return match($e) {
        'sin_contactar' => 'Sin contactar', 'contactado_tel' => 'Tel contactado',
        'contactado_email' => 'Email enviado', 'reunion_programada' => 'Reunión programada',
        'reunion_realizada' => 'Reunión realizada', 'propuesta_enviada' => 'Propuesta enviada',
        'negociacion' => 'En negociación', 'cliente' => '✅ Cliente',
        'descartado' => 'Descartado', 'no_interesado' => 'No interesado',
        default => $e,
    };
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CRM Agencias — EuryGo Admin</title>
  <link rel="stylesheet" href="/admin/assets/admin.css">
  <link rel="stylesheet" href="/admin/crm/assets/crm.css">
</head>
<body class="admin-body">
  <?php include __DIR__ . '/../partials/sidebar.php'; ?>
  <div class="admin-main">
    <?php include __DIR__ . '/../partials/topbar.php'; ?>
    <div class="admin-content" style="max-width:1400px;">

      <div class="admin-content__header">
        <h1>CRM · Agencias europeas</h1>
        <a href="/admin/crm/ficha.php?nuevo=1&pata=agencias" class="btn-admin btn-admin--primary">+ Añadir agencia</a>
      </div>

      <form class="crm-filters" method="GET">
        <input type="text" name="q" placeholder="Buscar agencia, contacto, país…" value="<?= htmlspecialchars($f_busca) ?>">
        <select name="pais">
          <option value="">Todos los países</option>
          <?php foreach ($paises as $pa): ?>
          <option value="<?= htmlspecialchars($pa) ?>" <?= $f_pais === $pa ? 'selected' : '' ?>><?= htmlspecialchars($pa) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="aeropuerto">
          <option value="">Todo aeropuerto</option>
          <option value="XRY" <?= $f_aero === 'XRY' ? 'selected' : '' ?>>XRY (Jerez)</option>
          <option value="SVQ" <?= $f_aero === 'SVQ' ? 'selected' : '' ?>>SVQ (Sevilla)</option>
          <option value="AGP" <?= $f_aero === 'AGP' ? 'selected' : '' ?>>AGP (Málaga)</option>
          <option value="OTHER" <?= $f_aero === 'OTHER' ? 'selected' : '' ?>>Otros</option>
        </select>
        <select name="volumen">
          <option value="">Todo volumen</option>
          <option value="grande" <?= $f_volumen === 'grande' ? 'selected' : '' ?>>Grande</option>
          <option value="medio" <?= $f_volumen === 'medio' ? 'selected' : '' ?>>Medio</option>
          <option value="pequeño" <?= $f_volumen === 'pequeño' ? 'selected' : '' ?>>Pequeño</option>
        </select>
        <select name="fiabilidad">
          <option value="">Toda fiabilidad</option>
          <?php for ($i=5; $i>=1; $i--): ?>
          <option value="<?= $i ?>" <?= $f_fiab == $i ? 'selected' : '' ?>><?= str_repeat('★',$i) ?></option>
          <?php endfor; ?>
        </select>
        <select name="estado">
          <option value="">Todos los estados</option>
          <?php foreach (['sin_contactar','contactado_tel','contactado_email','reunion_programada','reunion_realizada','propuesta_enviada','negociacion','cliente','descartado','no_interesado'] as $est): ?>
          <option value="<?= $est ?>" <?= $f_estado === $est ? 'selected' : '' ?>><?= estado_label2($est) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-admin btn-admin--sm btn-admin--primary">Filtrar</button>
        <?php if ($f_busca || $f_pais || $f_aero || $f_volumen || $f_fiab || $f_estado): ?>
        <a href="?clear=1" class="btn-admin btn-admin--sm btn-admin--text">Limpiar</a>
        <?php endif; ?>
      </form>

      <div style="font-size:0.85rem; color:#666; margin-bottom:0.5rem;"><?= $total ?> agencias</div>

      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Agencia</th>
              <th>País</th>
              <th>Aeropuerto</th>
              <th>Volumen</th>
              <th>Fiabilidad</th>
              <th>Estado</th>
              <th>Próx. contacto</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
<?php if (empty($agencias)): ?>
            <tr><td colspan="8" style="text-align:center; padding:2rem; color:#999;">No hay agencias todavía. Pulsa <strong>+ Añadir agencia</strong> para empezar.</td></tr>
<?php endif; ?>
<?php foreach ($agencias as $a):
    $vencido = $a['fecha_proximo_contacto'] && $a['fecha_proximo_contacto'] < date('Y-m-d');
?>
            <tr>
              <td>
                <strong><?= htmlspecialchars($a['nombre_centro']) ?></strong>
                <?php if ($a['notas']): ?>
                <br><small style="color:#666;"><?= htmlspecialchars(mb_substr($a['notas'], 0, 80)) ?>…</small>
                <?php endif; ?>
              </td>
              <td><?= htmlspecialchars($a['pais'] ?: '—') ?></td>
              <td><?= htmlspecialchars($a['aeropuerto_cercano'] ?: '—') ?></td>
              <td>
                <?php if ($a['volumen_estimado'] && $a['volumen_estimado'] !== 'nd'): ?>
                <span class="badge badge--info"><?= ucfirst($a['volumen_estimado']) ?></span>
                <?php else: ?>—<?php endif; ?>
              </td>
              <td>
                <?php if ($a['fiabilidad']): ?>
                <span class="stars"><?= str_repeat('★', (int)$a['fiabilidad']) ?><span style="color:#e5e7eb;"><?= str_repeat('★', 5 - (int)$a['fiabilidad']) ?></span></span>
                <?php else: ?>—<?php endif; ?>
              </td>
              <td><span class="badge estado-<?= $a['estado'] ?>"><?= estado_label2($a['estado']) ?></span></td>
              <td>
                <?php if ($a['fecha_proximo_contacto']): ?>
                <?php if ($vencido): ?>
                <span class="badge badge--vencido"><?= date('d/m/Y', strtotime($a['fecha_proximo_contacto'])) ?></span>
                <?php else: ?>
                <?= date('d/m/Y', strtotime($a['fecha_proximo_contacto'])) ?>
                <?php endif; ?>
                <?php else: ?>—<?php endif; ?>
              </td>
              <td><a href="/admin/crm/ficha.php?id=<?= $a['id'] ?>" class="btn-admin btn-admin--sm btn-admin--primary">Ficha</a></td>
            </tr>
<?php endforeach; ?>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</body>
</html>
