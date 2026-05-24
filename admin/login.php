<?php
/**
 * Back Office — Login
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

iniciar_sesion_segura();

// Si ya está logueado, ir al dashboard
if (!empty($_SESSION['admin_id'])) {
    header('Location: /admin/index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        empty($_POST['csrf_token']) ||
        empty($_SESSION['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])
    ) {
        $error = 'Solicitud no válida.';
    } elseif (ip_bloqueada()) {
        $error = 'Demasiados intentos. Espera 15 minutos antes de volver a intentarlo.';
    } else {
        $usuario = trim($_POST['usuario'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($usuario && $password) {
            $db = get_db();
            $stmt = $db->prepare("SELECT * FROM admin_usuarios WHERE usuario = :u LIMIT 1");
            $stmt->execute([':u' => $usuario]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                session_regenerate_id(true);
                $_SESSION['admin_id'] = $admin['id'];
                $_SESSION['admin_nombre'] = $admin['nombre'];
                $_SESSION['admin_usuario'] = $admin['usuario'];
                header('Location: /admin/index.php');
                exit;
            } else {
                registrar_intento_fallido();
                $error = 'Usuario o contraseña incorrectos.';
            }
        } else {
            $error = 'Rellena todos los campos.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Acceso — EuryGo Admin</title>
  <link rel="stylesheet" href="/admin/assets/admin.css">
  <meta name="robots" content="noindex, nofollow">
</head>
<body class="login-page">
  <div class="login-box">
    <div class="login-logo">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 360 100" width="180" height="50">
        <defs><linearGradient id="gt" x1="0%" y1="0%" x2="100%" y2="0%"><stop offset="0%" stop-color="#D97706"/><stop offset="100%" stop-color="#F59E0B"/></linearGradient></defs>
        <text x="105" y="58" font-family="system-ui,sans-serif" font-weight="800" font-size="46" fill="#0C4A6E" letter-spacing="-1">Eury<tspan fill="url(#gt)">Go</tspan></text>
        <text x="108" y="78" font-family="system-ui,sans-serif" font-weight="600" font-size="11.5" fill="#64748B" letter-spacing="3.5">MOBILITY EXPERIENCE</text>
      </svg>
      <p class="login-subtitle">Panel de Administración</p>
    </div>

<?php if ($error): ?>
    <div class="alert alert--error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

    <form method="POST" action="">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(generar_csrf()) ?>">
      <div class="form-group">
        <label for="usuario">Usuario</label>
        <input type="text" id="usuario" name="usuario" required autofocus autocomplete="username"
               value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" required autocomplete="current-password">
      </div>
      <button type="submit" class="btn-admin btn-admin--primary btn-admin--full">Entrar</button>
    </form>
    <p class="login-forgot">¿Olvidaste tu contraseña? Contacta con tu administrador técnico para resetearla.</p>
  </div>
</body>
</html>
