<?php
// Database Configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'your_cpanel_username');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_NAME', getenv('DB_NAME') ?: 'your_database_name');

// Admin credentials
define('ADMIN_PASSWORD', getenv('ADMIN_PASSWORD') ?: 'default_password_change_me');
define('ADMIN_SECRET', getenv('ADMIN_SECRET') ?: 'your_secret_key_min_16_chars');
define('TOKEN_TTL_SECONDS', intval(getenv('TOKEN_TTL_SECONDS') ?: 28800));

// Settings
define('LOGIN_WINDOW_SECONDS', 600);
define('LOGIN_MAX_ATTEMPTS', 5);
define('BASE_DIR', dirname(__FILE__));

// Database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    http_response_code(500);
    die(json_encode(['error' => 'Database connection failed']));
}

$conn->set_charset("utf8mb4");

// Initialize database
function initialize_database() {
    global $conn;
    
    $sql = "CREATE TABLE IF NOT EXISTS apps (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(500) NOT NULL,
        category VARCHAR(100) NOT NULL,
        bonus VARCHAR(500) NOT NULL,
        withdraw_text VARCHAR(500) NOT NULL,
        rating DECIMAL(3,1) NOT NULL,
        url VARCHAR(2000) NOT NULL,
        logo LONGTEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if (!$conn->query($sql)) {
        error_log("Table creation failed: " . $conn->error);
    }
}

// Seed default apps from apps.json if table is empty
function seed_defaults() {
    global $conn;
    
    $result = $conn->query("SELECT COUNT(*) as count FROM apps");
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        $json_file = BASE_DIR . '/apps.json';
        if (file_exists($json_file)) {
            $apps_data = json_decode(file_get_contents($json_file), true);
            if (is_array($apps_data)) {
                foreach ($apps_data as $app) {
                    insert_app($conn, $app);
                }
            }
        }
    }
}

function insert_app($conn, $app) {
    $stmt = $conn->prepare("INSERT INTO apps (name, category, bonus, withdraw_text, rating, url, logo) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    if (!$stmt) {
        error_log("Prepare failed: " . $conn->error);
        return false;
    }
    
    $rating = floatval($app['rating'] ?? 0);
    $stmt->bind_param(
        "ssssds",
        $app['name'],
        $app['category'],
        $app['bonus'],
        $app['withdraw'],
        $rating,
        $app['url'],
        $app['logo']
    );
    
    return $stmt->execute();
}

// Initialize on first load
initialize_database();
seed_defaults();

// CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// JSON response helper
function send_json($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    die(json_encode($data));
}

// Token functions
function build_token($role) {
    $exp = time() + TOKEN_TTL_SECONDS;
    $payload = json_encode(['exp' => $exp, 'role' => $role]);
    $payload_b64 = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
    
    $signature = hash_hmac('sha256', $payload_b64, ADMIN_SECRET, true);
    $signature_b64 = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');
    
    return $payload_b64 . '.' . $signature_b64;
}

function verify_token($token) {
    if (empty($token)) return false;
    
    list($payload_b64, $signature_b64) = explode('.', $token, 2);
    
    $expected_signature = hash_hmac('sha256', $payload_b64, ADMIN_SECRET, true);
    $expected_sig_b64 = rtrim(strtr(base64_encode($expected_signature), '+/', '-_'), '=');
    
    if (!hash_equals($signature_b64, $expected_sig_b64)) {
        return false;
    }
    
    $payload_padded = $payload_b64 . str_repeat('=', (4 - strlen($payload_b64) % 4) % 4);
    $payload_json = base64_decode(strtr($payload_padded, '-_', '+/'));
    $payload = json_decode($payload_json, true);
    
    if (empty($payload) || empty($payload['role']) || $payload['role'] !== 'admin') {
        return false;
    }
    
    if (time() >= $payload['exp']) {
        return false;
    }
    
    return $payload;
}

function get_auth_token() {
    $headers = getallheaders();
    $auth_header = $headers['Authorization'] ?? '';
    
    if (preg_match('/Bearer\s+(.+)/i', $auth_header, $matches)) {
        return $matches[1];
    }
    
    return $_COOKIE['admin_token'] ?? '';
}
?>
