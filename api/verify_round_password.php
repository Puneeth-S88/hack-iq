<?php
// api/verify_round_password.php - Verify per-round password gate
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireTeamAuth(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(['success' => false, 'error' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$roundId  = isset($input['round_id']) ? (int)$input['round_id'] : 0;
$password = trim($input['password'] ?? '');
$teamId   = (int)$_SESSION['team_id'];

if ($roundId <= 0 || empty($password)) {
    sendJson(['success' => false, 'error' => 'Round ID and password are required.'], 400);
}

$pdo = getDb();
if (!$pdo) {
    sendJson(['success' => false, 'error' => 'Database connection unavailable.'], 500);
}

try {
    // 1. Fetch round details
    $stmt = $pdo->prepare("SELECT id, round_number, round_name, round_password_hash, is_active FROM rounds WHERE id = ?");
    $stmt->execute([$roundId]);
    $round = $stmt->fetch();

    if (!$round) {
        sendJson(['success' => false, 'error' => 'Invalid round specified.'], 404);
    }

    if (!$round['is_active']) {
        sendJson(['success' => false, 'error' => 'This round is currently locked by the event coordinators.'], 403);
    }

    $roundNumber = (int)$round['round_number'];

    // 2. Enforce qualification cutoffs for Semifinal (R6) and Final (R7)
    if ($roundNumber >= 6) {
        $stmtRank = $pdo->query("
            SELECT t.id, COALESCE(SUM(rs.total_points), 0) AS pts, COALESCE(SUM(rs.total_correct), 0) AS cor
            FROM teams t
            LEFT JOIN round_scores rs ON t.id = rs.team_id
            GROUP BY t.id
            ORDER BY pts DESC, cor DESC, t.id ASC
        ");
        $allRankings = $stmtRank->fetchAll();
        $teamRank = 999;
        foreach ($allRankings as $idx => $r) {
            if ((int)$r['id'] === $teamId) {
                $teamRank = $idx + 1;
                break;
            }
        }

        if ($roundNumber === 6 && $teamRank > 5) {
            sendJson(['success' => false, 'error' => 'Access Denied: Only Top 5 teams are qualified for Round 6 Semifinals.'], 403);
        }
        if ($roundNumber === 7 && $teamRank > 3) {
            sendJson(['success' => false, 'error' => 'Access Denied: Only Top 3 teams are qualified for Round 7 Final Showdown.'], 403);
        }
    }

    // 3. Verify password
    if (!password_verify($password, $round['round_password_hash'])) {
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
        'message'      => 'Password verified! Proceeding to ' . $round['round_name'],
        'round_number' => $roundNumber,
        'round_id'     => $roundId
    ]);

} catch (Exception $e) {
    sendJson(['success' => false, 'error' => 'Verification error: ' . $e->getMessage()], 500);
}
