<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requiere_login();

$msg = '';
$msg_tipo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verificar_csrf()) {
        $msg = 'Error de seguridad. Recarga e intenta de nuevo.';
        $msg_tipo = 'error';
    } else {
        $actual = $_POST['password_actual'] ?? '';
        $nueva = $_POST['password_nueva'] ?? '';
        $confirmar = $_POST['password_confirmar'] ?? '';

        $db = get_db();
        $stmt = $db->prepare("SELECT password FROM admin_usuarios WHERE id = :id");
        $stmt->execute([':id' => $_SESSION['admin_id']]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($actual, $admin['password'])) {
            $msg = 'La contraseña actual es incorrecta.';
            $msg_tipo = 'error';
        } elseif (strlen($nueva) < 8 || !preg_match('/[A-Z]/', $nueva) || !preg_match('/[0-9]/', $nueva)) {
            $msg = 'La nueva contraseña debe tener mínimo 8 caracteres, 1 mayúscula y 1 número.';
            $msg_tipo = 'error';
        } elseif ($nueva !== $confirmar) {
            $msg = 'Las contraseñas no coinciden.';
            $msg_tipo = 'error';
        } else {
            $hash = password_hash($nueva, PASSWORD_DEFAULT);
            $upd = $db->prepare("UPDATE admin_usuarios SET password = :p WHERE id = :id");
            $upd->execute([':p' => $hash, ':id' => $_SESSION['admin_id']]);
            $msg = 'Contraseña cambiada correctamente.';
            $msg_tipo = 'success';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Cambiar contraseña — EuryGo Admin</title>
  <link rel="stylesheet" href="/admin/assets/admin.css">
  <meta name="robots" content="noindex, nofollow">
</head>
<body class="admin-body">
  <?php include __DIR__ . '/partials/sidebar.php'; ?>
  <div class="admin-main">
    <?php include __DIR__ . '/partials/topbar.php'; ?>
    <div class="admin-content">
      <h1>Cambiar contraseña</h1>
<?php if ($msg): ?>
      <div class="alert alert--<?= $msg_tipo ?>"><?= htmlspecialchars($msg) ?></div>
<?php endif; ?>
      <form method="POST" class="form-narrow">
        <?= campo_csrf() ?>
        <div class="form-group">
          <label for="password_actual">Contraseña actual</label>
          <input type="password" id="password_actual" name="password_actual" required autocomplete="current-password">
        </div>
        <div class="form-group">
          <label for="password_nueva">Nueva contraseña <small>(mín 8 caracteres, 1 mayúscula, 1 número)</small></label>
          <input type="password" id="password_nueva" name="password_nueva" required minlength="8" autocomplete="new-password">
        </div>
        <div class="form-group">
          <label for="password_confirmar">Confirmar nueva contraseña</label>
          <input type="password" id="password_confirmar" name="password_confirmar" required autocomplete="new-password">
        </div>
        <button type="submit" class="btn-admin btn-admin--primary">Cambiar contraseña</button>
      </form>
    </div>
  </div>
</body>
</html>
