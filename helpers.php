
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('require_login')) {
    function require_login() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: login.php');
            exit;
        }
    }
}

if (!function_exists('require_role')) {
    function require_role($role) {
        $expected = strtolower(trim((string)$role));
        $actual   = strtolower(trim((string)($_SESSION['role'] ?? '')));
        if ($actual !== $expected) {
            header('Location: login.php');
            exit;
        }
    }
}
