<?php
// api/verify_round_password.php - Streamlined round passcode verification
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(['success' => false, 'error' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$roundId   = isset($input['round_id']) ? (int)$input['round_id'] : 0;
$password  = trim($input['password'] ?? '');
$teamName  = trim($input['team_name'] ?? '');

$pdo = getDb();
if (!$pdo) {
    sendJson(['success' => false, 'error' => 'Database connection unavailable.'], 500);
}

// 1. Establish Team Session (either from existing session or from submitted team_name)
if (!empty($teamName)) {
    // Look up or auto-create team
    $stmt = $pdo->prepare("SELECT id, team_name FROM teams WHERE LOWER(team_name) = LOWER(?) LIMIT 1");
    $stmt->execute([$teamName]);
    $team = $stmt->fetch();

    if (!$team) {
        $dummyHash = password_hash('hack123', PASSWORD_DEFAULT);
        $stmtInsert = $pdo->prepare("INSERT INTO teams (team_name, password_hash, members_info) VALUES (?, ?, 'Team Participants')");
        $stmtInsert->execute([$teamName, $dummyHash]);
        $teamId = (int)$pdo->lastInsertId();
    } else {
        $teamId = (int)$team['id'];
        $teamName = $team['team_name'];
    }

    $_SESSION['team_id']   = $teamId;
    $_SESSION['team_name'] = $teamName;
} elseif (isTeamLoggedIn()) {
    $teamId   = (int)$_SESSION['team_id'];
    $teamName = $_SESSION['team_name'];
} else {
    sendJson(['success' => false, 'error' => 'Please provide your Team Name.'], 400);
}

if ($roundId <= 0 || empty($password)) {
    sendJson(['success' => false, 'error' => 'Round ID and password are required.'], 400);
}

try {
    // 2. Fetch round details
    $stmt = $pdo->prepare("SELECT id, round_number, round_name, round_password_hash, plain_password_hint, is_active FROM rounds WHERE id = ?");
    $stmt->execute([$roundId]);
    $round = $stmt->fetch();

    if (!$round) {
        sendJson(['success' => false, 'error' => 'Invalid round specified.'], 404);
    }

    if (!$round['is_active']) {
        sendJson(['success' => false, 'error' => 'This round is currently locked by the event coordinators.'], 403);
    }

    // 3. Verify password (case-insensitive comparison with plain hint OR bcrypt hash)
    $inputUpper = strtoupper($password);
    $hintUpper  = strtoupper($round['plain_password_hint'] ?? '');

    $passwordMatches = ($inputUpper === $hintUpper) 
        || password_verify($password, $round['round_password_hash']) 
        || password_verify($inputUpper, $round['round_password_hash']);

    if (!$passwordMatches) {
        sendJson(['success' => false, 'error' => 'Incorrect password for ' . $round['round_name'] . '. Please check with coordinators.'], 401);
    }

    // 4. Record access in team_round_access
    $stmtAccess = $pdo->prepare("
        INSERT INTO team_round_access (team_id, round_id, unlocked_at, started_at)
        VALUES (?, ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE unlocked_at = NOW()
    ");
    $stmtAccess->execute([$teamId, $roundId]);

    sendJson([
        'success'      => true,
        'message'      => 'Password verified! Opening ' . $round['round_name'],
        'round_number' => (int)$round['round_number'],
        'round_id'     => $roundId,
        'team_name'    => $teamName
    ]);

} catch (Exception $e) {
    sendJson(['success' => false, 'error' => 'Verification error: ' . $e->getMessage()], 500);
}
