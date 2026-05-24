<?php
/**
 * CRM EuryGo — Listado de centros escolares
 */
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requiere_login();

$db = get_db();

// CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Re-build same WHERE as listing
    $f_busca = trim($_GET['q'] ?? '');
    $f_ccaa = $_GET['ccaa'] ?? '';
    $f_prov = $_GET['provincia'] ?? '';
    $f_estado = $_GET['estado'] ?? '';
    $f_prio = $_GET['prioridad'] ?? '';
    $f_accion = $_GET['accion'] ?? '';
    $f_reunion = $_GET['reunion'] ?? '';
    $f_proximo = $_GET['proximo'] ?? '';

    $w = ["pata = 'centros'"];
    $p = [];
    if ($f_busca) { $w[] = "(nombre_centro LIKE :q OR municipio LIKE :q OR contacto_nombre LIKE :q)"; $p[':q'] = "%$f_busca%"; }
    if ($f_ccaa) { $w[] = "comunidad = :ccaa"; $p[':ccaa'] = $f_ccaa; }
    if ($f_prov) { $w[] = "provincia = :prov"; $p[':prov'] = $f_prov; }
    if ($f_estado) { $w[] = "estado = :estado"; $p[':estado'] = $f_estado; }
    if ($f_prio) { $w[] = "prioridad = :prio"; $p[':prio'] = $f_prio; }
    if ($f_accion) { $w[] = "tipo_accion = :acc"; $p[':acc'] = $f_accion; }
    if ($f_reunion) { $w[] = "tipo_reunion = :reu"; $p[':reu'] = $f_reunion; }

    $where_sql = 'WHERE ' . implode(' AND ', $w);
    $stmt = $db->prepare("SELECT * FROM crm_contactos $where_sql ORDER BY FIELD(prioridad,'alta','media','baja'), distancia_jerez_km, nombre_centro");
    $stmt->execute($p);
    $rows = $stmt->fetchAll();

    $incluir_internas = ($_GET['internas'] ?? '') === '1';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="centros-crm-' . date('Y-m-d') . '.csv"');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));

    $headers = ['Nombre', 'Tipo', 'Acción', 'Municipio', 'Provincia', 'Comunidad', 'KM Jerez',
                'Tipo reunión', 'Estado', 'Prioridad', 'Contacto', 'Email', 'Teléfono',
                'Último contacto', 'Próximo contacto', 'Notas'];
    if ($incluir_internas) $headers[] = 'Notas internas';
    fputcsv($out, $headers, ';');

    foreach ($rows as $r) {
        $row = [
            $r['nombre_centro'], $r['tipo_centro'], $r['tipo_accion'],
            $r['municipio'], $r['provincia'], $r['comunidad'], $r['distancia_jerez_km'],
            $r['tipo_reunion'], $r['estado'], $r['prioridad'],
            $r['contacto_nombre'], $r['contacto_email'], $r['contacto_telefono'],
            $r['fecha_ultimo_contacto'], $r['fecha_proximo_contacto'], $r['notas'],
        ];
        if ($incluir_internas) $row[] = $r['notas_internas'];
        fputcsv($out, $row, ';');
    }
    fclose($out);
    exit;
}

// Filtros (persistentes en sesión)
$session_key = 'crm_filtros_centros';
if (isset($_GET['clear'])) {
    unset($_SESSION[$session_key]);
    header('Location: /admin/crm/centros.php');
    exit;
}

if (!empty($_GET) && !isset($_GET['pagina'])) {
    $_SESSION[$session_key] = $_GET;
}
$g = $_SESSION[$session_key] ?? [];

$f_busca   = trim($g['q'] ?? '');
$f_ccaa    = $g['ccaa'] ?? '';
$f_prov    = $g['provincia'] ?? '';
$f_estado  = $g['estado'] ?? '';
$f_prio    = $g['prioridad'] ?? '';
$f_accion  = $g['accion'] ?? '';
$f_reunion = $g['reunion'] ?? '';
$f_proximo = $g['proximo'] ?? '';
$f_datos   = $g['datos'] ?? ''; // contiene_tel | contiene_email | sin_datos
$f_etapa   = $g['etapa'] ?? ''; // CSV: secundaria,bachillerato,fp_medio
$f_titu    = $g['titularidad'] ?? '';
$pagina    = max(1, (int)($_GET['pagina'] ?? 1));
$por_pagina = 50;

// Build WHERE
$w = ["pata = 'centros'"];
$p = [];
if ($f_busca) { $w[] = "(nombre_centro LIKE :q OR municipio LIKE :q OR contacto_nombre LIKE :q)"; $p[':q'] = "%$f_busca%"; }
if ($f_ccaa) { $w[] = "comunidad = :ccaa"; $p[':ccaa'] = $f_ccaa; }
if ($f_prov) { $w[] = "provincia = :prov"; $p[':prov'] = $f_prov; }
if ($f_estado) { $w[] = "estado = :estado"; $p[':estado'] = $f_estado; }
if ($f_prio) { $w[] = "prioridad = :prio"; $p[':prio'] = $f_prio; }
if ($f_accion) { $w[] = "tipo_accion = :acc"; $p[':acc'] = $f_accion; }
if ($f_reunion) { $w[] = "tipo_reunion = :reu"; $p[':reu'] = $f_reunion; }
if ($f_etapa) {
    $etapas_validas = ['infantil_0_3','infantil','primaria','secundaria','bachillerato','fp_medio','fp_superior','adultos','especial','otro'];
    $etapas_in = array_filter(array_map('trim', explode(',', $f_etapa)), fn($e) => in_array($e, $etapas_validas));
    if ($etapas_in) {
        $ph_e = [];
        foreach ($etapas_in as $idx => $e) {
            $key = ":etapa$idx";
            $ph_e[] = $key;
            $p[$key] = $e;
        }
        $w[] = "etapa_educativa IN (" . implode(',', $ph_e) . ")";
    }
}
if ($f_titu && in_array($f_titu, ['publico','concertado','privado','nd'])) {
    $w[] = "titularidad = :titu";
    $p[':titu'] = $f_titu;
}
if ($f_datos === 'con_tel') { $w[] = "contacto_telefono IS NOT NULL AND contacto_telefono <> ''"; }
elseif ($f_datos === 'con_email') { $w[] = "contacto_email IS NOT NULL AND contacto_email <> ''"; }
elseif ($f_datos === 'sin_datos') { $w[] = "(contacto_telefono IS NULL OR contacto_telefono = '') AND (contacto_email IS NULL OR contacto_email = '')"; }
if ($f_proximo === 'vencido') { $w[] = "fecha_proximo_contacto < CURDATE() AND fecha_proximo_contacto IS NOT NULL"; }
elseif ($f_proximo === 'hoy') { $w[] = "fecha_proximo_contacto = CURDATE()"; }
elseif ($f_proximo === 'semana') { $w[] = "fecha_proximo_contacto BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)"; }
elseif ($f_proximo === 'mes') { $w[] = "fecha_proximo_contacto BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)"; }

$where_sql = 'WHERE ' . implode(' AND ', $w);

// Total
$st = $db->prepare("SELECT COUNT(*) FROM crm_contactos $where_sql");
$st->execute($p);
$total = (int)$st->fetchColumn();
$total_paginas = max(1, (int)ceil($total / $por_pagina));
$offset = ($pagina - 1) * $por_pagina;

// Listar
$sql = "SELECT * FROM crm_contactos $where_sql
        ORDER BY FIELD(prioridad,'alta','media','baja'), distancia_jerez_km IS NULL, distancia_jerez_km, nombre_centro
        LIMIT $por_pagina OFFSET $offset";
$st = $db->prepare($sql);
$st->execute($p);
$centros = $st->fetchAll();

// Para filtros: CCAA y provincias disponibles
$ccaas = $db->query("SELECT DISTINCT comunidad FROM crm_contactos WHERE pata='centros' AND comunidad IS NOT NULL AND comunidad <> '' ORDER BY comunidad")->fetchAll(PDO::FETCH_COLUMN);
$provincias = $db->query("SELECT DISTINCT provincia FROM crm_contactos WHERE pata='centros' AND provincia IS NOT NULL AND provincia <> '' ORDER BY provincia")->fetchAll(PDO::FETCH_COLUMN);

// Conteo rápido total
$total_centros = (int)$db->query("SELECT COUNT(*) FROM crm_contactos WHERE pata='centros'")->fetchColumn();
$total_alta = (int)$db->query("SELECT COUNT(*) FROM crm_contactos WHERE pata='centros' AND prioridad='alta'")->fetchColumn();
$total_sin_contactar = (int)$db->query("SELECT COUNT(*) FROM crm_contactos WHERE pata='centros' AND estado='sin_contactar'")->fetchColumn();
$total_vencidos = (int)$db->query("SELECT COUNT(*) FROM crm_contactos WHERE pata='centros' AND fecha_proximo_contacto < CURDATE() AND fecha_proximo_contacto IS NOT NULL")->fetchColumn();
$total_con_tel = (int)$db->query("SELECT COUNT(*) FROM crm_contactos WHERE pata='centros' AND contacto_telefono IS NOT NULL AND contacto_telefono <> ''")->fetchColumn();
$total_con_email = (int)$db->query("SELECT COUNT(*) FROM crm_contactos WHERE pata='centros' AND contacto_email IS NOT NULL AND contacto_email <> ''")->fetchColumn();

function estado_label($e) {
    return match($e) {
        'sin_contactar' => 'Sin contactar',
        'contactado_tel' => 'Tel contactado',
        'contactado_email' => 'Email enviado',
        'reunion_programada' => 'Reunión programada',
        'reunion_realizada' => 'Reunión realizada',
        'propuesta_enviada' => 'Propuesta enviada',
        'negociacion' => 'En negociación',
        'cliente' => '✅ Cliente',
        'descartado' => 'Descartado',
        'no_interesado' => 'No interesado',
        default => $e,
    };
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CRM Centros — EuryGo Admin</title>
  <link rel="stylesheet" href="/admin/assets/admin.css">
  <link rel="stylesheet" href="/admin/crm/assets/crm.css">
</head>
<body class="admin-body">
  <?php include __DIR__ . '/../partials/sidebar.php'; ?>
  <div class="admin-main">
    <?php include __DIR__ . '/../partials/topbar.php'; ?>
    <div class="admin-content" style="max-width:1400px;">

      <div class="admin-content__header">
        <h1>CRM · Centros escolares</h1>
        <div style="display:flex;gap:0.5rem;">
          <a href="?export=csv&<?= http_build_query($g) ?>" class="btn-admin btn-admin--outline">Exportar CSV</a>
          <a href="/admin/crm/importar.php" class="btn-admin btn-admin--outline">Importar SEPIE</a>
          <a href="/admin/crm/ficha.php?nuevo=1&pata=centros" class="btn-admin btn-admin--primary">+ Añadir</a>
        </div>
      </div>

      <div class="stats-grid" style="margin-bottom:1.5rem;">
        <div class="stat-card">
          <div class="stat-card__number"><?= $total_centros ?></div>
          <div class="stat-card__label">Total centros</div>
        </div>
        <div class="stat-card">
          <div class="stat-card__number" style="color:#16a34a;"><?= $total_alta ?></div>
          <div class="stat-card__label">Prioridad alta</div>
        </div>
        <div class="stat-card">
          <div class="stat-card__number" style="color:#6b7280;"><?= $total_sin_contactar ?></div>
          <div class="stat-card__label">Sin contactar</div>
        </div>
        <div class="stat-card">
          <div class="stat-card__number" style="color:#dc2626;"><?= $total_vencidos ?></div>
          <div class="stat-card__label">Seguimientos vencidos</div>
        </div>
      </div>

      <form class="crm-filters" method="GET">
        <input type="text" name="q" placeholder="Buscar nombre, municipio, contacto…" value="<?= htmlspecialchars($f_busca) ?>">
        <select name="ccaa">
          <option value="">Todas las CCAA</option>
          <?php foreach ($ccaas as $c): ?>
          <option value="<?= htmlspecialchars($c) ?>" <?= $f_ccaa === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="provincia">
          <option value="">Todas las provincias</option>
          <?php foreach ($provincias as $pv): ?>
          <option value="<?= htmlspecialchars($pv) ?>" <?= $f_prov === $pv ? 'selected' : '' ?>><?= htmlspecialchars($pv) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="prioridad">
          <option value="">Toda prioridad</option>
          <option value="alta" <?= $f_prio === 'alta' ? 'selected' : '' ?>>Alta</option>
          <option value="media" <?= $f_prio === 'media' ? 'selected' : '' ?>>Media</option>
          <option value="baja" <?= $f_prio === 'baja' ? 'selected' : '' ?>>Baja</option>
        </select>
        <select name="estado">
          <option value="">Todos los estados</option>
          <?php foreach (['sin_contactar','contactado_tel','contactado_email','reunion_programada','reunion_realizada','propuesta_enviada','negociacion','cliente','descartado','no_interesado'] as $est): ?>
          <option value="<?= $est ?>" <?= $f_estado === $est ? 'selected' : '' ?>><?= estado_label($est) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="accion">
          <option value="">KA121 + KA122</option>
          <option value="KA121-SCH" <?= $f_accion === 'KA121-SCH' ? 'selected' : '' ?>>KA121-SCH</option>
          <option value="KA122-SCH" <?= $f_accion === 'KA122-SCH' ? 'selected' : '' ?>>KA122-SCH</option>
        </select>
        <select name="reunion">
          <option value="">Toda reunión</option>
          <option value="presencial" <?= $f_reunion === 'presencial' ? 'selected' : '' ?>>🏢 Presencial</option>
          <option value="telematica" <?= $f_reunion === 'telematica' ? 'selected' : '' ?>>💻 Telemática</option>
        </select>
        <select name="proximo">
          <option value="">Próximo contacto</option>
          <option value="vencido" <?= $f_proximo === 'vencido' ? 'selected' : '' ?>>🔴 Vencidos</option>
          <option value="hoy" <?= $f_proximo === 'hoy' ? 'selected' : '' ?>>🟡 Hoy</option>
          <option value="semana" <?= $f_proximo === 'semana' ? 'selected' : '' ?>>Esta semana</option>
          <option value="mes" <?= $f_proximo === 'mes' ? 'selected' : '' ?>>Este mes</option>
        </select>
        <button type="submit" class="btn-admin btn-admin--sm btn-admin--primary">Filtrar</button>
        <?php if ($f_busca || $f_ccaa || $f_prov || $f_estado || $f_prio || $f_accion || $f_reunion || $f_proximo || $f_datos): ?>
        <a href="?clear=1" class="btn-admin btn-admin--sm btn-admin--text">Limpiar</a>
        <?php endif; ?>
      </form>

      <!-- Filtros rápidos visuales -->
      <div class="quick-filters" style="margin-bottom:1rem; display:flex; gap:0.5rem; flex-wrap:wrap;">
        <a href="?clear=1" class="quick-filter <?= !$f_datos && !$f_accion && !$f_etapa && !$f_titu ? 'quick-filter--active' : '' ?>">Todos (<?= number_format($total_centros, 0, ',', '.') ?>)</a>
        <a href="?datos=con_tel" class="quick-filter <?= $f_datos === 'con_tel' ? 'quick-filter--active' : '' ?>">📞 Con teléfono (<?= number_format($total_con_tel, 0, ',', '.') ?>)</a>
        <a href="?datos=con_email" class="quick-filter <?= $f_datos === 'con_email' ? 'quick-filter--active' : '' ?>">✉️ Con email (<?= number_format($total_con_email, 0, ',', '.') ?>)</a>
        <a href="?datos=sin_datos" class="quick-filter <?= $f_datos === 'sin_datos' ? 'quick-filter--active' : '' ?>">⚠️ Sin datos</a>
        <a href="?accion=KA121-SCH" class="quick-filter <?= $f_accion === 'KA121-SCH' ? 'quick-filter--active' : '' ?>"><span class="badge-ka121">KA121</span> Acreditados</a>
        <a href="?accion=KA122-SCH" class="quick-filter <?= $f_accion === 'KA122-SCH' ? 'quick-filter--active' : '' ?>"><span class="badge-ka122">KA122</span> Corta duración</a>
      </div>

      <!-- Filtros por etapa educativa -->
      <div style="margin-bottom:0.5rem; font-size:0.8rem; color:#666; font-weight:600;">Etapa educativa:</div>
      <div class="filtros-pills" style="margin-bottom:1rem;">
        <a href="?etapa=" class="filtro-pill <?= !$f_etapa ? 'activo' : '' ?>">Todas</a>
        <a href="?etapa=secundaria" class="filtro-pill prioritario <?= $f_etapa === 'secundaria' ? 'activo' : '' ?>">⭐ Secundaria</a>
        <a href="?etapa=bachillerato" class="filtro-pill <?= $f_etapa === 'bachillerato' ? 'activo' : '' ?>">Bachillerato</a>
        <a href="?etapa=fp_medio,fp_superior" class="filtro-pill <?= $f_etapa === 'fp_medio,fp_superior' ? 'activo' : '' ?>">FP</a>
        <a href="?etapa=secundaria,bachillerato,fp_medio,fp_superior" class="filtro-pill prioritario <?= $f_etapa === 'secundaria,bachillerato,fp_medio,fp_superior' ? 'activo' : '' ?>">⭐ Sec + Bach + FP</a>
        <a href="?etapa=primaria" class="filtro-pill <?= $f_etapa === 'primaria' ? 'activo' : '' ?>">Primaria</a>
        <a href="?etapa=infantil,infantil_0_3" class="filtro-pill <?= $f_etapa === 'infantil,infantil_0_3' ? 'activo' : '' ?>">Infantil</a>
        <a href="?etapa=adultos" class="filtro-pill <?= $f_etapa === 'adultos' ? 'activo' : '' ?>">Adultos</a>
        <a href="?etapa=especial" class="filtro-pill <?= $f_etapa === 'especial' ? 'activo' : '' ?>">Especial</a>
      </div>

      <!-- Filtros por titularidad -->
      <div style="margin-bottom:0.5rem; font-size:0.8rem; color:#666; font-weight:600;">Titularidad:</div>
      <div class="filtros-pills" style="margin-bottom:1rem;">
        <a href="?titularidad=" class="filtro-pill <?= !$f_titu ? 'activo' : '' ?>">Todas</a>
        <a href="?titularidad=publico" class="filtro-pill <?= $f_titu === 'publico' ? 'activo' : '' ?>">🏛️ Pública</a>
        <a href="?titularidad=concertado" class="filtro-pill <?= $f_titu === 'concertado' ? 'activo' : '' ?>">🤝 Concertada</a>
        <a href="?titularidad=privado" class="filtro-pill <?= $f_titu === 'privado' ? 'activo' : '' ?>">🏫 Privada</a>
      </div>

      <div style="font-size:0.85rem; color:#666; margin-bottom:0.5rem;"><?= $total ?> resultados</div>

      <div class="admin-table-wrap">
        <table class="admin-table">
          <thead>
            <tr>
              <th>Centro</th>
              <th>Municipio</th>
              <th>KM</th>
              <th>Reunión</th>
              <th>Contacto</th>
              <th>Estado</th>
              <th>Próx.</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
<?php if (empty($centros)): ?>
            <tr><td colspan="8" style="text-align:center; padding:2rem; color:#999;">No se encontraron centros.</td></tr>
<?php endif; ?>
<?php foreach ($centros as $c):
    $vencido = $c['fecha_proximo_contacto'] && $c['fecha_proximo_contacto'] < date('Y-m-d');
    $tiene_tel = !empty($c['contacto_telefono']);
    $tiene_email = !empty($c['contacto_email']);
?>
            <tr class="crm-row--<?= $c['prioridad'] ?>">
              <td>
                <strong><?= htmlspecialchars($c['nombre_centro']) ?></strong>
                <?php
                  // Badges de etapa
                  $etapa_badges = [
                    'secundaria'   => ['SEC', '#1E2761', '#fff'],
                    'bachillerato' => ['BACH', '#003399', '#fff'],
                    'fp_medio'     => ['FP-M', '#4B0082', '#fff'],
                    'fp_superior'  => ['FP-S', '#4B0082', '#fff'],
                    'primaria'     => ['PRI', '#28a745', '#fff'],
                    'infantil'     => ['INF', '#fd7e14', '#fff'],
                    'infantil_0_3' => ['INF', '#fd7e14', '#fff'],
                    'adultos'      => ['ADU', '#6c757d', '#fff'],
                    'especial'     => ['ESP', '#9b59b6', '#fff'],
                  ];
                  if (isset($etapa_badges[$c['etapa_educativa'] ?? ''])):
                    [$lbl, $bg, $fg] = $etapa_badges[$c['etapa_educativa']];
                ?>
                <span class="badge-etapa" style="background:<?= $bg ?>; color:<?= $fg ?>;"><?= $lbl ?></span>
                <?php endif; ?>
                <?php if (($c['titularidad'] ?? '') === 'concertado'): ?>
                <span class="badge-etapa" style="background:#ffc107; color:#212529;">CONC</span>
                <?php elseif (($c['titularidad'] ?? '') === 'privado'): ?>
                <span class="badge-etapa" style="background:#dc3545; color:#fff;">PRIV</span>
                <?php endif; ?>
                <?php if ($c['tipo_accion'] === 'KA121-SCH'): ?>
                <span class="badge-ka121">● KA121 Acreditado</span>
                <?php elseif ($c['tipo_accion'] === 'KA122-SCH'): ?>
                <span class="badge-ka122">● KA122 Corta duración</span>
                <?php endif; ?>
                <?php if ($c['estado'] === 'sin_contactar'): ?>
                <span class="badge" style="background:#f3f4f6;color:#6b7280;">Sin contactar</span>
                <?php endif; ?>
              </td>
              <td>
                <?= htmlspecialchars($c['municipio'] ?: '—') ?>
                <?php if ($c['provincia']): ?>
                <br><small style="color:#999;"><?= htmlspecialchars($c['provincia']) ?></small>
                <?php endif; ?>
              </td>
              <td style="text-align:center;">
                <?php if ($c['distancia_jerez_km']): ?>
                <strong><?= number_format($c['distancia_jerez_km'], 0, ',', '.') ?></strong>
                <?php else: ?>
                <span style="color:#ccc;">—</span>
                <?php endif; ?>
              </td>
              <td><?= $c['tipo_reunion'] === 'presencial' ? '🏢' : '💻' ?></td>
              <td style="font-size:0.85rem; line-height:1.4;">
                <?php if ($tiene_tel): ?>
                <div class="contact-yes contact-tel">📞 <a href="tel:<?= htmlspecialchars($c['contacto_telefono']) ?>"><?= htmlspecialchars($c['contacto_telefono']) ?></a></div>
                <?php else: ?>
                <div class="contact-no">📞 Sin tel</div>
                <?php endif; ?>
                <?php if ($tiene_email): ?>
                <div class="contact-yes contact-email">✉️ <a href="mailto:<?= htmlspecialchars($c['contacto_email']) ?>"><?= htmlspecialchars(mb_substr($c['contacto_email'], 0, 25)) ?><?= mb_strlen($c['contacto_email']) > 25 ? '…' : '' ?></a></div>
                <?php else: ?>
                <div class="contact-no">✉️ Sin email</div>
                <?php endif; ?>
              </td>
              <td>
                <span class="badge estado-<?= $c['estado'] ?>"><?= estado_label($c['estado']) ?></span>
              </td>
              <td>
                <?php if ($c['fecha_proximo_contacto']): ?>
                <?php if ($vencido): ?>
                <span class="badge badge--vencido"><?= date('d/m/Y', strtotime($c['fecha_proximo_contacto'])) ?></span>
                <?php else: ?>
                <?= date('d/m/Y', strtotime($c['fecha_proximo_contacto'])) ?>
                <?php endif; ?>
                <?php else: ?>
                <span style="color:#ccc;">—</span>
                <?php endif; ?>
              </td>
              <td class="admin-table__actions">
                <a href="/admin/crm/ficha.php?id=<?= $c['id'] ?>" class="btn-admin btn-admin--sm btn-admin--primary">Ficha</a>
              </td>
            </tr>
<?php endforeach; ?>
          </tbody>
        </table>
      </div>

<?php if ($total_paginas > 1): ?>
      <div class="admin-pagination">
        <?php if ($pagina > 1): ?>
        <a href="?pagina=<?= $pagina - 1 ?>">&larr; Anterior</a>
        <?php endif; ?>
        <span>Página <?= $pagina ?> de <?= $total_paginas ?></span>
        <?php if ($pagina < $total_paginas): ?>
        <a href="?pagina=<?= $pagina + 1 ?>">Siguiente &rarr;</a>
        <?php endif; ?>
      </div>
<?php endif; ?>
    </div>
  </div>
</body>
</html>
