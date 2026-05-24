<aside class="admin-sidebar">
  <div class="admin-sidebar__logo">
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 360 100" width="140" height="38">
      <defs><linearGradient id="sgt" x1="0%" y1="0%" x2="100%" y2="0%"><stop offset="0%" stop-color="#D97706"/><stop offset="100%" stop-color="#F59E0B"/></linearGradient></defs>
      <text x="105" y="58" font-family="system-ui,sans-serif" font-weight="800" font-size="46" fill="#fff" letter-spacing="-1">Eury<tspan fill="url(#sgt)">Go</tspan></text>
    </svg>
  </div>
  <nav class="admin-sidebar__nav">
    <a href="/admin/index.php" class="admin-sidebar__link<?= basename($_SERVER['PHP_SELF']) === 'index.php' ? ' active' : '' ?>">
      <span class="admin-sidebar__icon">&#127968;</span> Dashboard
    </a>
    <a href="/admin/articulos.php" class="admin-sidebar__link<?= basename($_SERVER['PHP_SELF']) === 'articulos.php' ? ' active' : '' ?>">
      <span class="admin-sidebar__icon">&#128221;</span> Artículos
    </a>
    <a href="/admin/editor.php" class="admin-sidebar__link<?= basename($_SERVER['PHP_SELF']) === 'editor.php' && empty($_GET['id']) ? ' active' : '' ?>">
      <span class="admin-sidebar__icon">&#10133;</span> Nuevo artículo
    </a>
    <a href="/admin/cursos.php" class="admin-sidebar__link<?= basename($_SERVER['PHP_SELF']) === 'cursos.php' || basename($_SERVER['PHP_SELF']) === 'editor-curso.php' ? ' active' : '' ?>">
      <span class="admin-sidebar__icon">&#127891;</span> Cursos
    </a>
    <a href="/admin/inscripciones.php" class="admin-sidebar__link<?= basename($_SERVER['PHP_SELF']) === 'inscripciones.php' ? ' active' : '' ?>">
      <span class="admin-sidebar__icon">&#128203;</span> Inscripciones
    </a>
    <?php
    $crm_files = ['centros.php', 'agencias.php', 'ficha.php', 'agenda.php', 'importar.php', 'stats.php', 'plantillas.php'];
    $crm_active = in_array(basename($_SERVER['PHP_SELF']), $crm_files) ? ' active' : '';
    ?>
    <a href="/admin/crm/centros.php" class="admin-sidebar__link<?= $crm_active ?>">
      <span class="admin-sidebar__icon">&#128203;</span> CRM Captación
    </a>
    <?php if ($crm_active): ?>
    <div style="background:rgba(0,0,0,0.15); padding:0.25rem 0;">
      <a href="/admin/crm/centros.php" class="admin-sidebar__link<?= basename($_SERVER['PHP_SELF']) === 'centros.php' ? ' active' : '' ?>" style="padding-left:3rem; font-size:0.82rem;">Centros escolares</a>
      <a href="/admin/crm/agencias.php" class="admin-sidebar__link<?= basename($_SERVER['PHP_SELF']) === 'agencias.php' ? ' active' : '' ?>" style="padding-left:3rem; font-size:0.82rem;">Agencias europeas</a>
      <a href="/admin/crm/agenda.php" class="admin-sidebar__link<?= basename($_SERVER['PHP_SELF']) === 'agenda.php' ? ' active' : '' ?>" style="padding-left:3rem; font-size:0.82rem;">Agenda seguimiento</a>
      <a href="/admin/crm/plantillas.php" class="admin-sidebar__link<?= basename($_SERVER['PHP_SELF']) === 'plantillas.php' ? ' active' : '' ?>" style="padding-left:3rem; font-size:0.82rem;">Plantillas</a>
      <a href="/admin/crm/importar.php" class="admin-sidebar__link<?= basename($_SERVER['PHP_SELF']) === 'importar.php' ? ' active' : '' ?>" style="padding-left:3rem; font-size:0.82rem;">Importar SEPIE</a>
      <a href="/admin/crm/stats.php" class="admin-sidebar__link<?= basename($_SERVER['PHP_SELF']) === 'stats.php' ? ' active' : '' ?>" style="padding-left:3rem; font-size:0.82rem;">Estadísticas</a>
    </div>
    <?php endif; ?>
    <a href="/admin/imagenes-home.php" class="admin-sidebar__link<?= basename($_SERVER['PHP_SELF']) === 'imagenes-home.php' ? ' active' : '' ?>">
      <span class="admin-sidebar__icon">&#128444;</span> Imágenes inicio
    </a>
    <a href="/admin/newsletter.php" class="admin-sidebar__link<?= basename($_SERVER['PHP_SELF']) === 'newsletter.php' || basename($_SERVER['PHP_SELF']) === 'nueva-campana.php' ? ' active' : '' ?>">
      <span class="admin-sidebar__icon">&#9993;</span> Newsletter
    </a>
    <a href="/admin/estadisticas.php" class="admin-sidebar__link<?= basename($_SERVER['PHP_SELF']) === 'estadisticas.php' ? ' active' : '' ?>">
      <span class="admin-sidebar__icon">&#128202;</span> Estadísticas
    </a>
    <a href="/admin/cambiar-password.php" class="admin-sidebar__link<?= basename($_SERVER['PHP_SELF']) === 'cambiar-password.php' ? ' active' : '' ?>">
      <span class="admin-sidebar__icon">&#128273;</span> Cambiar contraseña
    </a>
    <a href="/admin/cerrar-sesion.php" class="admin-sidebar__link">
      <span class="admin-sidebar__icon">&#128682;</span> Cerrar sesión
    </a>
  </nav>
</aside>
