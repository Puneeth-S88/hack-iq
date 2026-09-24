<?php
// api/submit_round.php - Evaluate and record answers for a round
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
$answers  = isset($input['answers']) && is_array($input['answers']) ? $input['answers'] : [];
$isViolationSubmit = !empty($input['violation_auto_submit']);
$teamId   = (int)$_SESSION['team_id'];

if ($roundId <= 0) {
    sendJson(['success' => false, 'error' => 'Invalid round ID.'], 400);
}

$pdo = getDb();
if (!$pdo) {
    sendJson(['success' => false, 'error' => 'Database connection unavailable.'], 500);
}

try {
    // 1. Verify that round exists
    $stmtR = $pdo->prepare("SELECT id, round_number, round_name, max_violations FROM rounds WHERE id = ?");
    $stmtR->execute([$roundId]);
    $round = $stmtR->fetch();
    if (!$round) {
        sendJson(['success' => false, 'error' => 'Round not found.'], 404);
    }

    // 2. Verify team unlocked this round
    $stmtAccess = $pdo->prepare("SELECT id FROM team_round_access WHERE team_id = ? AND round_id = ?");
    $stmtAccess->execute([$teamId, $roundId]);
    if (!$stmtAccess->fetch()) {
        sendJson(['success' => false, 'error' => 'You have not unlocked access to this round.'], 403);
    }

    // 3. Prevent duplicate submission
    $stmtExisting = $pdo->prepare("SELECT id, total_points, total_correct FROM round_scores WHERE team_id = ? AND round_id = ?");
    $stmtExisting->execute([$teamId, $roundId]);
    $existingScore = $stmtExisting->fetch();

    if ($existingScore) {
        sendJson([
            'success'       => false,
            'error'         => 'Resubmission not allowed. Your answers for this round have already been recorded.',
            'total_points'  => (int)$existingScore['total_points'],
            'total_correct' => (int)$existingScore['total_correct']
        ], 409);
    }

    // 4. Fetch all questions for this round to evaluate answers safely on server
    $stmtQ = $pdo->prepare("SELECT id, correct_answer, points, question_type FROM questions WHERE round_id = ?");
    $stmtQ->execute([$roundId]);
    $questionsMap = [];
    $maxPoints = 0;
    while ($q = $stmtQ->fetch()) {
        $questionsMap[(int)$q['id']] = $q;
        $maxPoints += (int)$q['points'];
    }

    // 5. Begin transaction
    $pdo->beginTransaction();

    $totalCorrect = 0;
    $totalPoints  = 0;

    $stmtInsertAns = $pdo->prepare("
        INSERT INTO answers (team_id, question_id, round_id, selected_answer, is_correct, points_awarded, submitted_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE 
            selected_answer = VALUES(selected_answer),
            is_correct      = VALUES(is_correct),
            points_awarded  = VALUES(points_awarded),
            submitted_at    = NOW()
    ");

    foreach ($answers as $item) {
        $qId = isset($item['question_id']) ? (int)$item['question_id'] : 0;
        $selected = isset($item['selected_answer']) ? trim((string)$item['selected_answer']) : '';

        if (!isset($questionsMap[$qId])) {
            continue;
        }

        $qData = $questionsMap[$qId];
        $correctAnswer = trim((string)$qData['correct_answer']);
        $points = (int)$qData['points'];

        // Evaluation
        $isCorrect = false;
        // Pitch rounds or jury rounds can be scored manually by jury
        if ($qData['question_type'] === 'pitch' || $correctAnswer === 'JURY') {
            $isCorrect = true; // Recorded, jury assigns score in admin panel
            $awardedPoints = 0; // Starts at 0 until jury enters points
        } else {
            // Compare answers case-insensitively
            if (strcasecmp($selected, $correctAnswer) === 0) {
                $isCorrect = true;
                $awardedPoints = $points;
                $totalCorrect++;
                $totalPoints += $awardedPoints;
            } else {
                $isCorrect = false;
                $awardedPoints = 0;
            }
        }

        $stmtInsertAns->execute([
            $teamId,
            $qId,
            $roundId,
            $selected,
            $isCorrect ? 1 : 0,
            $awardedPoints
        ]);
    }

    // 6. Record in round_scores
    $flaggedVal = $isViolationSubmit ? 1 : 0;
    $notes = $isViolationSubmit ? 'Auto-submitted due to proctoring violation strike threshold.' : null;

    $stmtScore = $pdo->prepare("
        INSERT INTO round_scores (team_id, round_id, total_correct, total_points, submitted_at, flagged, admin_notes)
        VALUES (?, ?, ?, ?, NOW(), ?, ?)
        ON DUPLICATE KEY UPDATE
            total_correct = VALUES(total_correct),
            total_points  = VALUES(total_points),
            flagged       = VALUES(flagged),
            admin_notes   = VALUES(admin_notes),
            submitted_at  = NOW()
    ");
    $stmtScore->execute([
        $teamId,
        $roundId,
        $totalCorrect,
        $totalPoints,
        $flaggedVal,
        $notes
    ]);

    $pdo->commit();

    sendJson([
        'success'       => true,
        'message'       => $isViolationSubmit ? 'Round auto-submitted due to strike threshold.' : 'Round submitted successfully!',
        'round_number'  => (int)$round['round_number'],
        'round_name'    => $round['round_name'],
        'total_correct' => $totalCorrect,
        'total_points'  => $totalPoints,
        'max_points'    => $maxPoints,
        'flagged'       => (bool)$flaggedVal
    ]);

} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    sendJson(['success' => false, 'error' => 'Submission error: ' . $e->getMessage()], 500);
}
