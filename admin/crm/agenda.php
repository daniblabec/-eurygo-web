<?php
/**
 * CRM EuryGo — Agenda de seguimiento
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requiere_login();

$db = get_db();

// Acciones rápidas
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verificar_csrf()) {
    $accion = $_POST['accion'] ?? '';
    $cid = (int)($_POST['contacto_id'] ?? 0);

    if ($accion === 'posponer' && $cid) {
        $dias = max(1, (int)($_POST['dias'] ?? 7));
        $db->prepare("UPDATE crm_contactos SET fecha_proximo_contacto = DATE_ADD(IFNULL(fecha_proximo_contacto, CURDATE()), INTERVAL ? DAY) WHERE id = ?")
           ->execute([$dias, $cid]);
    } elseif ($accion === 'realizado' && $cid) {
        $db->prepare("UPDATE crm_contactos SET fecha_ultimo_contacto = CURDATE(), fecha_proximo_contacto = NULL WHERE id = ?")
           ->execute([$cid]);
    }
    header('Location: /admin/crm/agenda.php');
    exit;
}

$f_pata = $_GET['pata'] ?? '';
$f_estado = $_GET['estado'] ?? '';

$w = ["fecha_proximo_contacto IS NOT NULL"];
$p = [];
if ($f_pata) { $w[] = "pata = :pata"; $p[':pata'] = $f_pata; }
if ($f_estado) { $w[] = "estado = :estado"; $p[':estado'] = $f_estado; }
$where_sql = 'WHERE ' . implode(' AND ', $w);

$st = $db->prepare("SELECT * FROM crm_contactos $where_sql ORDER BY fecha_proximo_contacto ASC, prioridad LIMIT 100");
$st->execute($p);
$pendientes = $st->fetchAll();

// Conteos para alertas
$hoy = date('Y-m-d');
$semana = date('Y-m-d', strtotime('+7 days'));

$vencidos = (int)$db->query("SELECT COUNT(*) FROM crm_contactos WHERE fecha_proximo_contacto < CURDATE() AND fecha_proximo_contacto IS NOT NULL")->fetchColumn();
$de_hoy = (int)$db->query("SELECT COUNT(*) FROM crm_contactos WHERE fecha_proximo_contacto = CURDATE()")->fetchColumn();
$reuniones_semana = (int)$db->query("SELECT COUNT(*) FROM crm_contactos WHERE fecha_proximo_contacto BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) AND estado='reunion_programada'")->fetchColumn();

function estado_label3($e) {
    return match($e) {
        'sin_contactar'=>'Sin contactar','contactado_tel'=>'Tel','contactado_email'=>'Email',
        'reunion_programada'=>'Reunión prog.','reunion_realizada'=>'Reunión real.',
        'propuesta_enviada'=>'Propuesta','negociacion'=>'Negociación','cliente'=>'Cliente',
        'descartado'=>'Descartado','no_interesado'=>'No interesado', default=>$e,
    };
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Agenda · CRM EuryGo</title>
  <link rel="stylesheet" href="/admin/assets/admin.css">
  <link rel="stylesheet" href="/admin/crm/assets/crm.css">
</head>
<body class="admin-body">
  <?php include __DIR__ . '/../partials/sidebar.php'; ?>
  <div class="admin-main">
    <?php include __DIR__ . '/../partials/topbar.php'; ?>
    <div class="admin-content" style="max-width:1200px;">
      <h1>Agenda de seguimiento</h1>

      <div class="crm-alerts">
        <div class="crm-alert-card crm-alert-card--danger">
          <div class="crm-alert-card__number" style="color:#dc2626;">🔴 <?= $vencidos ?></div>
          <div class="crm-alert-card__label">Seguimientos vencidos</div>
        </div>
        <div class="crm-alert-card crm-alert-card--warning">
          <div class="crm-alert-card__number" style="color:#d97706;">🟡 <?= $de_hoy ?></div>
          <div class="crm-alert-card__label">Para hoy</div>
        </div>
        <div class="crm-alert-card crm-alert-card--success">
          <div class="crm-alert-card__number" style="color:#16a34a;">🟢 <?= $reuniones_semana ?></div>
          <div class="crm-alert-card__label">Reuniones esta semana</div>
        </div>
      </div>

      <form class="crm-filters" method="GET">
        <select name="pata">
          <option value="">Todos</option>
          <option value="centros" <?= $f_pata === 'centros' ? 'selected' : '' ?>>Solo centros</option>
          <option value="agencias" <?= $f_pata === 'agencias' ? 'selected' : '' ?>>Solo agencias</option>
        </select>
        <select name="estado">
          <option value="">Todos los estados</option>
          <?php foreach (['sin_contactar','contactado_tel','contactado_email','reunion_programada','propuesta_enviada','negociacion'] as $e): ?>
          <option value="<?= $e ?>" <?= $f_estado === $e ? 'selected' : '' ?>><?= estado_label3($e) ?></option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-admin btn-admin--sm">Filtrar</button>
      </form>

      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Contacto</th>
              <th>Pata</th>
              <th>Estado</th>
              <th>Próximo paso</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
<?php if (empty($pendientes)): ?>
            <tr><td colspan="6" style="text-align:center; padding:2rem; color:#999;">¡Todo al día! No hay seguimientos pendientes.</td></tr>
<?php endif; ?>
<?php foreach ($pendientes as $c):
    $vencido = $c['fecha_proximo_contacto'] < $hoy;
    $hoy_es = $c['fecha_proximo_contacto'] === $hoy;
    // Buscar último próximo paso
    $ultima = $db->prepare("SELECT proximo_paso FROM crm_actividad WHERE contacto_id = ? ORDER BY fecha DESC LIMIT 1");
    $ultima->execute([$c['id']]);
    $ultProxPaso = $ultima->fetchColumn() ?: '';
?>
            <tr class="crm-row--<?= $c['prioridad'] ?>">
              <td>
                <?php if ($vencido): ?>
                <span class="badge badge--vencido"><?= date('d/m/Y', strtotime($c['fecha_proximo_contacto'])) ?></span>
                <?php elseif ($hoy_es): ?>
                <span class="badge badge--hoy">HOY</span>
                <?php else: ?>
                <strong><?= date('d/m', strtotime($c['fecha_proximo_contacto'])) ?></strong>
                <?php endif; ?>
              </td>
              <td>
                <strong><?= htmlspecialchars($c['nombre_centro']) ?></strong>
                <?php if ($c['municipio']): ?>
                <br><small style="color:#999;"><?= htmlspecialchars($c['municipio']) ?></small>
                <?php endif; ?>
              </td>
              <td><span class="badge"><?= $c['pata'] === 'centros' ? '🏫 Centro' : '🌍 Agencia' ?></span></td>
              <td><span class="badge estado-<?= $c['estado'] ?>"><?= estado_label3($c['estado']) ?></span></td>
              <td style="font-size:0.85rem;"><?= htmlspecialchars(mb_substr($ultProxPaso, 0, 80)) ?><?= strlen($ultProxPaso) > 80 ? '…' : '' ?></td>
              <td style="white-space:nowrap;">
                <a href="/admin/crm/ficha.php?id=<?= $c['id'] ?>" class="btn-admin btn-admin--sm btn-admin--primary">Ficha</a>
                <form method="POST" style="display:inline;">
                  <?= campo_csrf() ?>
                  <input type="hidden" name="accion" value="realizado">
                  <input type="hidden" name="contacto_id" value="<?= $c['id'] ?>">
                  <button type="submit" class="btn-admin btn-admin--sm btn-admin--success" title="Marcar contacto como realizado">✓</button>
                </form>
                <form method="POST" style="display:inline;">
                  <?= campo_csrf() ?>
                  <input type="hidden" name="accion" value="posponer">
                  <input type="hidden" name="contacto_id" value="<?= $c['id'] ?>">
                  <input type="hidden" name="dias" value="7">
                  <button type="submit" class="btn-admin btn-admin--sm btn-admin--outline" title="Posponer 7 días">+7d</button>
                </form>
              </td>
            </tr>
<?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</body>
</html>
