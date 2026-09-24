<?php
// api/admin_login.php - Admin authentication endpoint
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(['success' => false, 'error' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$username = trim($input['username'] ?? '');
$password = trim($input['password'] ?? '');

if (empty($username) || empty($password)) {
    sendJson(['success' => false, 'error' => 'Username and password required.'], 400);
}

$pdo = getDb();
if (!$pdo) {
    sendJson(['success' => false, 'error' => 'Database connection failed.'], 500);
}

try {
    $stmt = $pdo->prepare("SELECT id, username, password_hash FROM admins WHERE username = ? LIMIT 1");
    $stmt->execute([$username]);
    $admin = $stmt->fetch();

    if (!$admin || !password_verify($password, $admin['password_hash'])) {
        // Fallback for default admin/admin123 if not matched
        if ($username === 'admin' && $password === 'admin123') {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username']  = 'admin';
            sendJson(['success' => true, 'message' => 'Admin authenticated']);
        }
        sendJson(['success' => false, 'error' => 'Invalid admin credentials.'], 401);
    }

    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_username']  = $admin['username'];

    sendJson([
        'success'  => true,
        'message'  => 'Admin authenticated successfully',
        'username' => $admin['username']
    ]);
} catch (Exception $e) {
    sendJson(['success' => false, 'error' => 'Admin login error: ' . $e->getMessage()], 500);
}
