<?php
// api/admin_actions.php - Admin operations & event management
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireAdminAuth(true);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJson(['success' => false, 'error' => 'Method not allowed'], 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$action = trim($input['action'] ?? '');
$pdo = getDb();
if (!$pdo) {
    sendJson(['success' => false, 'error' => 'Database connection failed'], 500);
}

try {
    switch ($action) {
        // 1. Toggle Round Active State
        case 'toggle_round':
            $roundId = (int)($input['round_id'] ?? 0);
            $isActive = !empty($input['is_active']) ? 1 : 0;
            $stmt = $pdo->prepare("UPDATE rounds SET is_active = ? WHERE id = ?");
            $stmt->execute([$isActive, $roundId]);
            sendJson(['success' => true, 'message' => "Round status updated to " . ($isActive ? 'Active' : 'Locked')]);
            break;

        // 2. Change Round Password
        case 'update_round_password':
            $roundId = (int)($input['round_id'] ?? 0);
            $newPassword = trim($input['password'] ?? '');
            if (empty($newPassword)) {
                sendJson(['success' => false, 'error' => 'New password cannot be empty'], 400);
            }
            $hash = password_hash($newPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE rounds SET round_password_hash = ?, plain_password_hint = ? WHERE id = ?");
            $stmt->execute([$hash, $newPassword, $roundId]);
            sendJson(['success' => true, 'message' => "Password updated to '{$newPassword}'"]);
            break;

        // 3. Update Round Duration
        case 'update_round_time':
            $roundId = (int)($input['round_id'] ?? 0);
            $minutes = max(1, (int)($input['time_limit_minutes'] ?? 15));
            $stmt = $pdo->prepare("UPDATE rounds SET time_limit_minutes = ? WHERE id = ?");
            $stmt->execute([$minutes, $roundId]);
            sendJson(['success' => true, 'message' => "Time limit updated to {$minutes} minutes"]);
            break;

        // 4. Update Live Question Index (for projector / stage reveal)
        case 'set_live_question':
            $roundId = (int)($input['round_id'] ?? 0);
            $qIndex  = max(0, (int)($input['current_question_index'] ?? 0));
            $stmt = $pdo->prepare("UPDATE rounds SET current_question_index = ? WHERE id = ?");
            $stmt->execute([$qIndex, $roundId]);
            sendJson(['success' => true, 'message' => "Live question index set to {$qIndex}"]);
            break;

        // 5. Manual Score Entry / Override (For Jury Rounds 5, 6B, 7 tiebreaker, or score fixes)
        case 'save_manual_score':
            $teamId   = (int)($input['team_id'] ?? 0);
            $roundId  = (int)($input['round_id'] ?? 0);
            $points   = (int)($input['points'] ?? 0);
            $correct  = (int)($input['correct'] ?? 0);
            $notes    = trim($input['notes'] ?? 'Manual Jury Evaluation / Score Adjustment');

            if ($teamId <= 0 || $roundId <= 0) {
                sendJson(['success' => false, 'error' => 'Invalid team or round ID.'], 400);
            }

            // Insert or update round_scores
            $stmt = $pdo->prepare("
                INSERT INTO round_scores (team_id, round_id, total_correct, total_points, submitted_at, manual_override, admin_notes)
                VALUES (?, ?, ?, ?, NOW(), 1, ?)
                ON DUPLICATE KEY UPDATE
                    total_points    = VALUES(total_points),
                    total_correct   = VALUES(total_correct),
                    manual_override = 1,
                    admin_notes     = VALUES(admin_notes),
                    submitted_at    = NOW()
            ");
            $stmt->execute([$teamId, $roundId, $correct, $points, $notes]);

            sendJson(['success' => true, 'message' => "Score saved successfully ({$points} pts)."]);
            break;

        // 6. Reset Team Attempt & Allow Retry (Clears violation strikes and answers)
        case 'reset_team_round':
            $teamId  = (int)($input['team_id'] ?? 0);
            $roundId = (int)($input['round_id'] ?? 0);

            if ($teamId <= 0 || $roundId <= 0) {
                sendJson(['success' => false, 'error' => 'Invalid team or round ID.'], 400);
            }

            $pdo->beginTransaction();

            // Delete round_scores
            $stmt1 = $pdo->prepare("DELETE FROM round_scores WHERE team_id = ? AND round_id = ?");
            $stmt1->execute([$teamId, $roundId]);

            // Delete violations
            $stmt2 = $pdo->prepare("DELETE FROM round_violations WHERE team_id = ? AND round_id = ?");
            $stmt2->execute([$teamId, $roundId]);

            // Delete answers
            $stmt3 = $pdo->prepare("DELETE FROM answers WHERE team_id = ? AND round_id = ?");
            $stmt3->execute([$teamId, $roundId]);

            $pdo->commit();

            sendJson(['success' => true, 'message' => 'Team attempt reset. The team may re-enter and attempt the round again.']);
            break;

        // 7. Get Overview Data for Admin Dashboard
        case 'get_admin_data':
            // Rounds
            $stmtR = $pdo->query("SELECT * FROM rounds ORDER BY round_number ASC");
            $rounds = $stmtR->fetchAll();

            // Teams with submission count & grand total
            $stmtT = $pdo->query("
                SELECT 
                    t.id, 
                    t.team_name, 
                    t.members_info, 
                    t.created_at,
                    COALESCE(SUM(rs.total_points), 0) AS grand_total,
                    COALESCE(SUM(rs.total_correct), 0) AS grand_correct,
                    COUNT(rs.id) AS submitted_rounds_count
                FROM teams t
                LEFT JOIN round_scores rs ON t.id = rs.team_id
                GROUP BY t.id, t.team_name, t.members_info, t.created_at
                ORDER BY grand_total DESC, grand_correct DESC, t.id ASC
            ");
            $teams = $stmtT->fetchAll();

            // Violations summary
            $stmtV = $pdo->query("
                SELECT rv.id, rv.team_id, t.team_name, rv.round_id, r.round_name, rv.violation_type, rv.occurred_at
                FROM round_violations rv
                JOIN teams t ON rv.team_id = t.id
                JOIN rounds r ON rv.round_id = r.id
                ORDER BY rv.occurred_at DESC
                LIMIT 50
            ");
            $violations = $stmtV->fetchAll();

            // Round scores matrix
            $stmtAllScores = $pdo->query("SELECT team_id, round_id, total_correct, total_points, flagged, manual_override, admin_notes, submitted_at FROM round_scores");
            $allScores = $stmtAllScores->fetchAll();
            $scoresMatrix = [];
            foreach ($allScores as $sc) {
                $scoresMatrix[$sc['team_id']][$sc['round_id']] = $sc;
            }

            sendJson([
                'success'       => true,
                'rounds'        => $rounds,
                'teams'         => $teams,
                'violations'    => $violations,
                'scores_matrix' => $scoresMatrix
            ]);
            break;

        default:
            sendJson(['success' => false, 'error' => 'Unknown action.'], 400);
    }
} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    sendJson(['success' => false, 'error' => 'Admin operation failed: ' . $e->getMessage()], 500);
}
