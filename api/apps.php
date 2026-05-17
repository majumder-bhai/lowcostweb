<?php
require_once __DIR__ . '/../../config.php';

// GET /api/apps - Get all apps
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $result = $conn->query("SELECT id, name, category, bonus, withdraw_text as withdraw, rating, url, logo, created_at as createdAt FROM apps ORDER BY id DESC");
    
    if (!$result) {
        send_json(['error' => 'Query failed'], 500);
    }
    
    $apps = [];
    while ($row = $result->fetch_assoc()) {
        $row['rating'] = floatval($row['rating']);
        $apps[] = $row;
    }
    
    send_json($apps);
}

// POST /api/apps - Create app (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = get_auth_token();
    if (!verify_token($token)) {
        send_json(['error' => 'Unauthorized'], 401);
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate
    $required = ['name', 'category', 'bonus', 'withdraw', 'rating', 'url', 'logo'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            send_json(['error' => "Field '$field' is required"], 400);
        }
    }
    
    if (!is_numeric($data['rating']) || $data['rating'] < 1 || $data['rating'] > 5) {
        send_json(['error' => 'Rating must be between 1 and 5'], 400);
    }
    
    if (!filter_var($data['url'], FILTER_VALIDATE_URL)) {
        send_json(['error' => 'Invalid URL'], 400);
    }
    
    // Insert
    $stmt = $conn->prepare("INSERT INTO apps (name, category, bonus, withdraw_text, rating, url, logo) VALUES (?, ?, ?, ?, ?, ?, ?)");
    
    $rating = floatval($data['rating']);
    $stmt->bind_param(
        "ssssds",
        $data['name'],
        $data['category'],
        $data['bonus'],
        $data['withdraw'],
        $rating,
        $data['url'],
        $data['logo']
    );
    
    if (!$stmt->execute()) {
        send_json(['error' => 'Insert failed'], 500);
    }
    
    send_json(['id' => $conn->insert_id, 'message' => 'App created'], 201);
}

// PUT /api/apps - Update app (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $token = get_auth_token();
    if (!verify_token($token)) {
        send_json(['error' => 'Unauthorized'], 401);
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['id'])) {
        send_json(['error' => 'ID is required'], 400);
    }
    
    $id = intval($data['id']);
    $updates = [];
    $types = '';
    $params = [];
    
    if (isset($data['name']) && !empty($data['name'])) {
        $updates[] = 'name = ?';
        $types .= 's';
        $params[] = $data['name'];
    }
    
    if (isset($data['category']) && !empty($data['category'])) {
        $updates[] = 'category = ?';
        $types .= 's';
        $params[] = $data['category'];
    }
    
    if (isset($data['bonus']) && !empty($data['bonus'])) {
        $updates[] = 'bonus = ?';
        $types .= 's';
        $params[] = $data['bonus'];
    }
    
    if (isset($data['withdraw']) && !empty($data['withdraw'])) {
        $updates[] = 'withdraw_text = ?';
        $types .= 's';
        $params[] = $data['withdraw'];
    }
    
    if (isset($data['rating'])) {
        $updates[] = 'rating = ?';
        $types .= 'd';
        $params[] = floatval($data['rating']);
    }
    
    if (isset($data['url']) && !empty($data['url'])) {
        $updates[] = 'url = ?';
        $types .= 's';
        $params[] = $data['url'];
    }
    
    if (isset($data['logo']) && !empty($data['logo'])) {
        $updates[] = 'logo = ?';
        $types .= 's';
        $params[] = $data['logo'];
    }
    
    if (empty($updates)) {
        send_json(['error' => 'No fields to update'], 400);
    }
    
    $sql = 'UPDATE apps SET ' . implode(', ', $updates) . ' WHERE id = ?';
    $types .= 'i';
    $params[] = $id;
    
    $stmt = $conn->prepare($sql);
    call_user_func_array([$stmt, 'bind_param'], array_merge([$types], $params));
    
    if (!$stmt->execute()) {
        send_json(['error' => 'Update failed'], 500);
    }
    
    if ($stmt->affected_rows === 0) {
        send_json(['error' => 'App not found'], 404);
    }
    
    send_json(['message' => 'App updated']);
}

// DELETE /api/apps - Delete app (admin only)
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $token = get_auth_token();
    if (!verify_token($token)) {
        send_json(['error' => 'Unauthorized'], 401);
    }
    
    parse_str(file_get_contents('php://input'), $_DELETE);
    $id = intval($_DELETE['id'] ?? 0);
    
    if ($id <= 0) {
        send_json(['error' => 'Invalid ID'], 400);
    }
    
    $stmt = $conn->prepare("DELETE FROM apps WHERE id = ?");
    $stmt->bind_param('i', $id);
    
    if (!$stmt->execute()) {
        send_json(['error' => 'Delete failed'], 500);
    }
    
    if ($stmt->affected_rows === 0) {
        send_json(['error' => 'App not found'], 404);
    }
    
    send_json(['message' => 'App deleted']);
}

send_json(['error' => 'Method not allowed'], 405);
?>
