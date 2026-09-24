<?php
// api/get_team_answers.php - Detailed answer sheet per team and round for Admin review
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdminAuth(true);

$teamId  = isset($_GET['team_id']) ? (int)$_GET['team_id'] : 0;
$roundId = isset($_GET['round_id']) ? (int)$_GET['round_id'] : 0;

if ($teamId <= 0) {
    sendJson(['success' => false, 'error' => 'Team ID required'], 400);
}

$pdo = getDb();
if (!$pdo) {
    sendJson(['success' => false, 'error' => 'Database connection failed'], 500);
}

try {
    // Team info
    $stmtT = $pdo->prepare("SELECT id, team_name, members_info, created_at FROM teams WHERE id = ?");
    $stmtT->execute([$teamId]);
    $team = $stmtT->fetch();
    if (!$team) {
        sendJson(['success' => false, 'error' => 'Team not found'], 404);
    }

    // If specific round requested
    if ($roundId > 0) {
        $stmtR = $pdo->prepare("SELECT id, round_number, round_name FROM rounds WHERE id = ?");
        $stmtR->execute([$roundId]);
        $round = $stmtR->fetch();

        $stmtScore = $pdo->prepare("SELECT * FROM round_scores WHERE team_id = ? AND round_id = ?");
        $stmtScore->execute([$teamId, $roundId]);
        $score = $stmtScore->fetch();

        $stmtQ = $pdo->prepare("
            SELECT 
                q.id, q.order_num, q.question_text, q.question_type, 
                q.option_a, q.option_b, q.option_c, q.option_d, 
                q.correct_answer, q.points, q.meta_info,
                a.selected_answer, a.is_correct, a.points_awarded, a.submitted_at
            FROM questions q
            LEFT JOIN answers a ON q.id = a.question_id AND a.team_id = ?
            WHERE q.round_id = ?
            ORDER BY q.order_num ASC
        ");
        $stmtQ->execute([$teamId, $roundId]);
        $questions = $stmtQ->fetchAll();

        $stmtV = $pdo->prepare("SELECT * FROM round_violations WHERE team_id = ? AND round_id = ? ORDER BY occurred_at ASC");
        $stmtV->execute([$teamId, $roundId]);
        $violations = $stmtV->fetchAll();

        sendJson([
            'success'    => true,
            'team'       => $team,
            'round'      => $round,
            'score'      => $score,
            'questions'  => $questions,
            'violations' => $violations
        ]);
    } else {
        // Summary for all 7 rounds for this team
        $stmtAll = $pdo->prepare("
            SELECT 
                r.id AS round_id,
                r.round_number,
                r.round_name,
                rs.total_correct,
                rs.total_points,
                rs.submitted_at,
                rs.flagged,
                rs.manual_override,
                rs.admin_notes,
                COUNT(rv.id) AS violation_count
            FROM rounds r
            LEFT JOIN round_scores rs ON r.id = rs.round_id AND rs.team_id = ?
            LEFT JOIN round_violations rv ON r.id = rv.round_id AND rv.team_id = ?
            GROUP BY r.id, r.round_number, r.round_name, rs.total_correct, rs.total_points, rs.submitted_at, rs.flagged, rs.manual_override, rs.admin_notes
            ORDER BY r.round_number ASC
        ");
        $stmtAll->execute([$teamId, $teamId]);
        $roundSummaries = $stmtAll->fetchAll();

        sendJson([
            'success'         => true,
            'team'            => $team,
            'round_summaries' => $roundSummaries
        ]);
    }

} catch (Exception $e) {
    sendJson(['success' => false, 'error' => 'Error: ' . $e->getMessage()], 500);
}
