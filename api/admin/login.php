<?php
require_once __DIR__ . '/../../config.php';

// POST /api/admin/login - Admin login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $password = $data['password'] ?? '';
    
    if (empty($password)) {
        send_json(['error' => 'Password is required'], 400);
    }
    
    if ($password !== ADMIN_PASSWORD) {
        send_json(['error' => 'Invalid password'], 401);
    }
    
    $token = build_token('admin');
    
    send_json([
        'token' => $token,
        'expiresIn' => TOKEN_TTL_SECONDS
    ]);
}

// GET /api/admin/session - Check session
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $token = get_auth_token();
    $payload = verify_token($token);
    
    if (!$payload) {
        send_json(['error' => 'Unauthorized'], 401);
    }
    
    send_json([
        'role' => $payload['role'],
        'expiresIn' => $payload['exp'] - time()
    ]);
}

send_json(['error' => 'Method not allowed'], 405);
?>
