<?php
// api/get_round.php - Fetch questions and state for a specific round
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireTeamAuth(true);

$teamId = (int)$_SESSION['team_id'];
$roundParam = isset($_GET['round']) ? (int)$_GET['round'] : (isset($_GET['round_id']) ? (int)$_GET['round_id'] : 1);

$pdo = getDb();
if (!$pdo) {
    sendJson(['success' => false, 'error' => 'Database connection unavailable.'], 500);
}

try {
    // 1. Fetch round by round_number or id
    $stmt = $pdo->prepare("SELECT id, round_number, round_name, description, time_limit_minutes, is_active, max_violations, is_live, current_question_index FROM rounds WHERE round_number = ? OR id = ? LIMIT 1");
    $stmt->execute([$roundParam, $roundParam]);
    $round = $stmt->fetch();

    if (!$round) {
        sendJson(['success' => false, 'error' => 'Round not found.'], 404);
    }

    $roundId = (int)$round['id'];
    $roundNumber = (int)$round['round_number'];

    // 2. Check if active
    if (!$round['is_active']) {
        sendJson(['success' => false, 'error' => 'This round is currently locked by the coordinators.'], 403);
    }

    // 3. Verify round password unlock status
    $stmtAccess = $pdo->prepare("SELECT unlocked_at, started_at FROM team_round_access WHERE team_id = ? AND round_id = ?");
    $stmtAccess->execute([$teamId, $roundId]);
    $access = $stmtAccess->fetch();

    if (!$access) {
        sendJson([
            'success'          => false,
            'requires_password'=> true,
            'round_id'         => $roundId,
            'round_number'     => $roundNumber,
            'round_name'       => $round['round_name'],
            'error'            => 'Round password entry required before accessing questions.'
        ], 403);
    }

    // 4. Check if already submitted
    $stmtScore = $pdo->prepare("SELECT total_correct, total_points, submitted_at, flagged, manual_override, admin_notes FROM round_scores WHERE team_id = ? AND round_id = ?");
    $stmtScore->execute([$teamId, $roundId]);
    $score = $stmtScore->fetch();

    // 5. Fetch team's current violations / strikes count
    $stmtViolations = $pdo->prepare("SELECT COUNT(*) AS violation_count FROM round_violations WHERE team_id = ? AND round_id = ?");
    $stmtViolations->execute([$teamId, $roundId]);
    $violationRow = $stmtViolations->fetch();
    $currentStrikes = $violationRow ? (int)$violationRow['violation_count'] : 0;

    // 6. Fetch questions
    $stmtQ = $pdo->prepare("
        SELECT id, round_id, question_text, question_type, option_a, option_b, option_c, option_d, points, order_num, meta_info
        FROM questions
        WHERE round_id = ?
        ORDER BY order_num ASC
    ");
    $stmtQ->execute([$roundId]);
    $rawQuestions = $stmtQ->fetchAll();

    $questions = [];
    $maxPossiblePoints = 0;

    foreach ($rawQuestions as $q) {
        $maxPossiblePoints += (int)$q['points'];
        $meta = null;
        if (!empty($q['meta_info'])) {
            $decoded = json_decode($q['meta_info'], true);
            $meta = $decoded !== null ? $decoded : $q['meta_info'];
        }

        $questions[] = [
            'id'            => (int)$q['id'],
            'round_id'      => (int)$q['round_id'],
            'question_text' => $q['question_text'],
            'question_type' => $q['question_type'],
            'option_a'      => $q['option_a'],
            'option_b'      => $q['option_b'],
            'option_c'      => $q['option_c'],
            'option_d'      => $q['option_d'],
            'points'        => (int)$q['points'],
            'order_num'     => (int)$q['order_num'],
            'meta'          => $meta
        ];
    }

    // If already submitted, include their answers for review
    $teamAnswers = [];
    if ($score) {
        $stmtAns = $pdo->prepare("SELECT question_id, selected_answer, is_correct, points_awarded FROM answers WHERE team_id = ? AND round_id = ?");
        $stmtAns->execute([$teamId, $roundId]);
        while ($a = $stmtAns->fetch()) {
            $teamAnswers[(int)$a['question_id']] = [
                'selected_answer' => $a['selected_answer'],
                'is_correct'      => (bool)$a['is_correct'],
                'points_awarded'  => (int)$a['points_awarded']
            ];
        }
    }

    sendJson([
        'success'           => true,
        'team_name'         => $_SESSION['team_name'] ?? 'Team',
        'round'             => [
            'id'                     => $roundId,
            'round_number'           => $roundNumber,
            'round_name'             => $round['round_name'],
            'description'            => $round['description'],
            'time_limit_minutes'     => (int)$round['time_limit_minutes'],
            'max_violations'         => (int)$round['max_violations'],
            'is_live'                => (bool)$round['is_live'],
            'current_question_index' => (int)$round['current_question_index'],
            'max_possible_points'    => $maxPossiblePoints
        ],
        'already_submitted' => $score !== false,
        'score'             => $score ? [
            'total_correct'   => (int)$score['total_correct'],
            'total_points'    => (int)$score['total_points'],
            'submitted_at'    => $score['submitted_at'],
            'flagged'         => (bool)$score['flagged'],
            'manual_override' => (bool)$score['manual_override'],
            'admin_notes'     => $score['admin_notes']
        ] : null,
        'current_strikes'   => $currentStrikes,
        'team_answers'      => $teamAnswers,
        'questions'         => $questions
    ]);

} catch (Exception $e) {
    sendJson(['success' => false, 'error' => 'Failed to load round: ' . $e->getMessage()], 500);
}
