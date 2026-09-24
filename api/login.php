<?php
// api/login.php - Team login endpoint
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(['success' => false, 'error' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$team_name = trim($input['team_name'] ?? '');
$password  = trim($input['password'] ?? '');

if (empty($team_name) || empty($password)) {
    sendJson(['success' => false, 'error' => 'Please provide both team name and password.'], 400);
}

$pdo = getDb();
if (!$pdo) {
    sendJson(['success' => false, 'error' => 'Database connection unavailable.'], 500);
}

try {
    // Case-insensitive team name lookup
    $stmt = $pdo->prepare("SELECT id, team_name, password_hash FROM teams WHERE LOWER(team_name) = LOWER(?) LIMIT 1");
    $stmt->execute([$team_name]);
    $team = $stmt->fetch();

    if (!$team || !password_verify($password, $team['password_hash'])) {
        sendJson(['success' => false, 'error' => 'Invalid team name or password.'], 401);
    }

    // Set session
    $_SESSION['team_id']   = (int)$team['id'];
    $_SESSION['team_name'] = $team['team_name'];

    sendJson([
        'success' => true,
        'message' => 'Login successful',
        'team'    => [
            'id'        => $team['id'],
            'team_name' => $team['team_name']
        ]
    ]);
} catch (Exception $e) {
    sendJson(['success' => false, 'error' => 'Login error: ' . $e->getMessage()], 500);
}
