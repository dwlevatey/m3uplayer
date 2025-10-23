<?php
// General configuration
define('SITE_URL', 'http://localhost');
define('ADMIN_PATH', '/admin');
define('SESSION_TIMEOUT', 3600); // 1 hour

// M3U URL parameters
define('M3U_TYPE', 'm3u_plus');
define('M3U_OUTPUT', 'mpegts');

// Security
define('JWT_SECRET', 'your-secret-key-change-this');
define('PASSWORD_HASH_ALGO', PASSWORD_BCRYPT);

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
