<?php
// ====================================================================
// config.php — database connection (PDO) and app settings
// ====================================================================

// Start session safely — prevents "session already active" warning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// --------------------------------------------------------------------
// 🛒 SANITIZE & INITIALIZE SESSION CART
// --------------------------------------------------------------------
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
} else {
    // Clean invalid entries (fixes ghost “Cart (1)” bug)
    foreach ($_SESSION['cart'] as $k => $v) {
        if (
            !is_numeric($k) || intval($k) <= 0 ||
            !is_numeric($v) || intval($v) <= 0
        ) {
            unset($_SESSION['cart'][$k]);
        } else {
            $cleanKey = intval($k);
            $cleanVal = intval($v);
            if ($cleanKey !== (int)$k) {
                unset($_SESSION['cart'][$k]);
            }
            $_SESSION['cart'][$cleanKey] = $cleanVal;
        }
    }
}

// --------------------------------------------------------------------
// DATABASE CONNECTION SETTINGS
// --------------------------------------------------------------------
$DB_HOST = 'localhost';
$DB_NAME = 'sweepxpress_db';
$DB_USER = 'root';
$DB_PASS = ''; // default XAMPP has empty password

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// --------------------------------------------------------------------
// USER ROLE CHECK FUNCTIONS
// --------------------------------------------------------------------
function is_logged_in() {
    // I-check lang kung may 'user' array sa session
    return isset($_SESSION['user']);
}

// *** Layo 65: Inayos upang iwasan ang Undefined Array Key Warning ***
function is_admin() {
    // I-check kung naka-set ang $_SESSION['user'] AT kung may 'role' key AT kung ang value ay 'admin'
    return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin';
}

/**
 * Checks if the currently logged-in user has the 'supplier' role.
 * @return bool
 */
// *** Layo 73: Inayos upang iwasan ang Undefined Array Key Warning ***
function is_supplier() {
    // I-check kung naka-set ang $_SESSION['user'] AT kung may 'role' key AT kung ang value ay 'supplier'
    return isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'supplier';
}

// --------------------------------------------------------------------
// SANITIZATION HELPER
// --------------------------------------------------------------------
function h($s) {
    return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8');
}

// --------------------------------------------------------------------
// SOCIAL LOGIN (GOOGLE & FACEBOOK)
// --------------------------------------------------------------------

// Make sure Composer dependencies exist (for Google/Facebook SDK)
require_once __DIR__ . '/vendor/autoload.php';


// Include OAuth credentials if file exists
if (file_exists(__DIR__ . '/config_oauth.php')) {
    require_once __DIR__ . '/config_oauth.php';
}

if (!function_exists('generateSKU')) {
    function generateSKU($productName) {
        $prefix = strtoupper(substr(preg_replace('/\s+/', '', $productName), 0, 3));
        $randomNumber = rand(1000, 9999);
        return $prefix . $randomNumber;
    }
}



// --------------------------------------------------------------------
// END OF CONFIG
// --------------------------------------------------------------------
?>