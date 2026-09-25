<?php
// api/login.php - Direct team login (Team name can be anything!)
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

if (empty($team_name)) {
    sendJson(['success' => false, 'error' => 'Please enter a team name.'], 400);
}

$pdo = getDb();
if (!$pdo) {
    sendJson(['success' => false, 'error' => 'Database connection unavailable.'], 500);
}

try {
    // Find or automatically create team with this name
    $stmt = $pdo->prepare("SELECT id, team_name FROM teams WHERE LOWER(team_name) = LOWER(?) LIMIT 1");
    $stmt->execute([$team_name]);
    $team = $stmt->fetch();

    if (!$team) {
        $dummy = password_hash('hack123', PASSWORD_DEFAULT);
        $stmtInsert = $pdo->prepare("INSERT INTO teams (team_name, password_hash, members_info) VALUES (?, ?, 'Team Participants')");
        $stmtInsert->execute([$team_name, $dummy]);
        $teamId = (int)$pdo->lastInsertId();
    } else {
        $teamId = (int)$team['id'];
        $team_name = $team['team_name'];
    }

    // Set session and persistent cookie
    $_SESSION['team_id']   = $teamId;
    $_SESSION['team_name'] = $team_name;
    setcookie('hackiq_team_name', $team_name, time() + 86400 * 7, '/');

    sendJson([
        'success' => true,
        'message' => 'Logged in successfully',
        'team'    => [
            'id'        => $teamId,
            'team_name' => $team_name
        ]
    ]);
} catch (Exception $e) {
    sendJson(['success' => false, 'error' => 'Login error: ' . $e->getMessage()], 500);
}
