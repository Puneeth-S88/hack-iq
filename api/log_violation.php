<?php
// api/log_violation.php - Server-enforced proctoring strike tracking
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

$roundId       = isset($input['round_id']) ? (int)$input['round_id'] : 0;
$violationType = trim($input['violation_type'] ?? '');
$teamId        = (int)$_SESSION['team_id'];

$allowedViolations = ['right_click', 'copy_attempt', 'devtools_key', 'tab_switch', 'fullscreen_exit'];

if ($roundId <= 0 || !in_array($violationType, $allowedViolations)) {
    sendJson(['success' => false, 'error' => 'Invalid violation parameters.'], 400);
}

$pdo = getDb();
if (!$pdo) {
    sendJson(['success' => false, 'error' => 'Database connection unavailable.'], 500);
}

try {
    // 1. Fetch round's max_violations
    $stmtR = $pdo->prepare("SELECT id, max_violations FROM rounds WHERE id = ?");
    $stmtR->execute([$roundId]);
    $round = $stmtR->fetch();

    if (!$round) {
        sendJson(['success' => false, 'error' => 'Round not found.'], 404);
    }

    $maxViolations = (int)($round['max_violations'] ?: 3);

    // 2. Check if round is already submitted
    $stmtScore = $pdo->prepare("SELECT id FROM round_scores WHERE team_id = ? AND round_id = ?");
    $stmtScore->execute([$teamId, $roundId]);
    if ($stmtScore->fetch()) {
        sendJson(['success' => true, 'already_submitted' => true, 'strikes' => $maxViolations, 'auto_submit' => false]);
    }

    // 3. Insert violation log
    $stmtInsert = $pdo->prepare("INSERT INTO round_violations (team_id, round_id, violation_type, occurred_at) VALUES (?, ?, ?, NOW())");
    $stmtInsert->execute([$teamId, $roundId, $violationType]);

    // 4. Query authoritative count
    $stmtCount = $pdo->prepare("SELECT COUNT(*) AS total_strikes FROM round_violations WHERE team_id = ? AND round_id = ?");
    $stmtCount->execute([$teamId, $roundId]);
    $countRow = $stmtCount->fetch();
    $totalStrikes = $countRow ? (int)$countRow['total_strikes'] : 1;

    $shouldAutoSubmit = ($totalStrikes >= $maxViolations);

    sendJson([
        'success'        => true,
        'violation_type' => $violationType,
        'strikes'        => $totalStrikes,
        'max_strikes'    => $maxViolations,
        'auto_submit'    => $shouldAutoSubmit,
        'message'        => $shouldAutoSubmit 
            ? "Strike {$totalStrikes} of {$maxViolations} reached. Round will be auto-submitted immediately."
            : "Rule violation detected! Strike {$totalStrikes} of {$maxViolations}."
    ]);

} catch (Exception $e) {
    sendJson(['success' => false, 'error' => 'Violation logging error: ' . $e->getMessage()], 500);
}
