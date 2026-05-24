<?php
/**
 * Funciones de autenticación, sesión y CSRF para el back office.
 */

/**
 * Inicia sesión segura con cabeceras de seguridad.
 */
function iniciar_sesion_segura(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start([
            'cookie_httponly' => true,
            'cookie_secure'  => true,
            'cookie_samesite' => 'Strict',
            'use_strict_mode' => true,
        ]);
    }
    header_remove('X-Powered-By');
    header('X-Frame-Options: DENY');
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-XSS-Protection: 1; mode=block');
}

/**
 * Verifica que el usuario tiene sesión activa. Redirige a login si no.
 */
function requiere_login(): void {
    iniciar_sesion_segura();
    if (empty($_SESSION['admin_id'])) {
        header('Location: /admin/login.php');
        exit;
    }
}

/**
 * Genera un token CSRF y lo guarda en sesión.
 */
function generar_csrf(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifica el token CSRF enviado en POST.
 */
function verificar_csrf(): bool {
    $token = $_POST['csrf_token'] ?? '';
    return !empty($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

/**
 * Devuelve un campo hidden con el token CSRF para formularios.
 */
function campo_csrf(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generar_csrf()) . '">';
}

/**
 * Comprueba si la IP está bloqueada por demasiados intentos de login.
 * Bloquea tras 5 intentos fallidos en 15 minutos.
 */
function ip_bloqueada(): bool {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $intentos = $_SESSION['login_intentos'][$ip] ?? [];

    // Limpiar intentos de más de 15 minutos
    $limite = time() - 900;
    $intentos = array_filter($intentos, fn($t) => $t > $limite);
    $_SESSION['login_intentos'][$ip] = $intentos;

    return count($intentos) >= 5;
}

/**
 * Registra un intento de login fallido para la IP actual.
 */
function registrar_intento_fallido(): void {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    $_SESSION['login_intentos'][$ip][] = time();
}
