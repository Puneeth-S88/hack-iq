<?php
// api/register.php - Team registration endpoint
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(['success' => false, 'error' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$team_name        = trim($input['team_name'] ?? '');
$password         = trim($input['password'] ?? '');
$confirm_password = trim($input['confirm_password'] ?? '');
$members_info     = trim($input['members_info'] ?? '');

if (empty($team_name) || empty($password)) {
    sendJson(['success' => false, 'error' => 'Team name and password are required.'], 400);
}

if (strlen($team_name) < 3 || strlen($team_name) > 50) {
    sendJson(['success' => false, 'error' => 'Team name must be between 3 and 50 characters.'], 400);
}

if (strlen($password) < 4) {
    sendJson(['success' => false, 'error' => 'Password must be at least 4 characters.'], 400);
}

if ($password !== $confirm_password) {
    sendJson(['success' => false, 'error' => 'Passwords do not match.'], 400);
}

$pdo = getDb();
if (!$pdo) {
    sendJson(['success' => false, 'error' => 'Database connection unavailable.'], 500);
}

try {
    // Check if team name already exists (case-insensitive)
    $stmt = $pdo->prepare("SELECT id FROM teams WHERE LOWER(team_name) = LOWER(?) LIMIT 1");
    $stmt->execute([$team_name]);
    if ($stmt->fetch()) {
        sendJson(['success' => false, 'error' => 'Team name already taken, please choose another.'], 409);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO teams (team_name, password_hash, members_info) VALUES (?, ?, ?)");
    $stmt->execute([$team_name, $hash, $members_info]);
    $teamId = (int)$pdo->lastInsertId();

    // Automatically log in team
    $_SESSION['team_id']   = $teamId;
    $_SESSION['team_name'] = $team_name;

    sendJson([
        'success' => true,
        'message' => 'Team registered successfully!',
        'team'    => [
            'id'        => $teamId,
            'team_name' => $team_name
        ]
    ]);
} catch (Exception $e) {
    sendJson(['success' => false, 'error' => 'Registration error: ' . $e->getMessage()], 500);
}
