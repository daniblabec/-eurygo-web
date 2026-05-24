<?php
/**
 * Conexión PDO singleton a la base de datos MySQL.
 *
 * Soporta dos modos de conexión:
 * - TCP: DB_HOST contiene un hostname o IP (con port=3306 en el DSN)
 * - Socket: DB_HOST empieza con '/' → se usa como unix_socket
 */

require_once __DIR__ . '/../config.php';

function get_db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $host = DB_HOST;

        // Si el host contiene ':' con puerto, separarlo
        if (strpos($host, ':') !== false && $host[0] !== '/') {
            $parts = explode(':', $host);
            $host = $parts[0];
            $port = (int)$parts[1];
        } else {
            $port = defined('DB_PORT') ? DB_PORT : 3306;
        }

        // Socket Unix o TCP
        if ($host[0] === '/') {
            $dsn = 'mysql:unix_socket=' . $host . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        } else {
            $dsn = 'mysql:host=' . $host . ';port=' . $port . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
        }

        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}
