<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('e')) {
    function e($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('requirePost')) {
    function requirePost() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            exit('Method not allowed.');
        }
    }
}

if (!function_exists('secureRandomToken')) {
    function secureRandomToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
}

if (!function_exists('ensureCsrfToken')) {
    function ensureCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = secureRandomToken(32);
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrfToken')) {
    function csrfToken() {
        return ensureCsrfToken();
    }
}

if (!function_exists('csrfField')) {
    function csrfField() {
        $token = ensureCsrfToken();
        return '<input type="hidden" name="csrf_token" value="' . e($token) . '">';
    }
}

if (!function_exists('verifyCsrf')) {
    function verifyCsrf() {
        requirePost();

        $sessionToken = $_SESSION['csrf_token'] ?? '';
        $postedToken = $_POST['csrf_token'] ?? '';

        if (!$sessionToken || !$postedToken || !hash_equals($sessionToken, $postedToken)) {
            http_response_code(403);
            exit('Invalid CSRF token.');
        }
    }
}

if (!function_exists('regenerateCsrfToken')) {
    function regenerateCsrfToken() {
        $_SESSION['csrf_token'] = secureRandomToken(32);
    }
}

if (!function_exists('isValidEmail')) {
    function isValidEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

if (!function_exists('normalizeEmail')) {
    function normalizeEmail($email) {
        return strtolower(trim((string)$email));
    }
}

if (!function_exists('strongPassword')) {
    function strongPassword($password) {
        return strlen($password) >= 8;
    }
}

if (!function_exists('secureSessionStart')) {
    function secureSessionStart() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            if (!isset($_SESSION['last_regenerated'])) {
                $_SESSION['last_regenerated'] = time();
            }
            ensureCsrfToken();
            return;
        }

        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $https,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        ini_set('session.use_only_cookies', '1');
        ini_set('session.use_strict_mode', '1');
        ini_set('session.cookie_httponly', '1');

        session_start();

        if (!isset($_SESSION['initiated'])) {
            session_regenerate_id(true);
            $_SESSION['initiated'] = time();
        }

        if (!isset($_SESSION['last_regenerated'])) {
            $_SESSION['last_regenerated'] = time();
        }

        if (time() - $_SESSION['last_regenerated'] > 900) {
            session_regenerate_id(true);
            $_SESSION['last_regenerated'] = time();
        }

        ensureCsrfToken();
    }
}

if (!function_exists('setFlash')) {
    function setFlash($key, $message) {
        $_SESSION['flash'][$key] = $message;
    }
}

if (!function_exists('getFlash')) {
    function getFlash($key) {
        if (!isset($_SESSION['flash'][$key])) {
            return null;
        }
        $msg = $_SESSION['flash'][$key];
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
}
?>