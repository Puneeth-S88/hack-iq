<?php
// api/get_leaderboard.php - Real-time leaderboard rankings with R1-R7 breakdown
require_once __DIR__ . '/../includes/db.php';

$pdo = getDb();
if (!$pdo) {
    sendJson(['success' => false, 'error' => 'Database connection failed'], 500);
}

try {
    // 1. Fetch rounds summary
    $stmtR = $pdo->query("SELECT id, round_number, round_name, is_active FROM rounds ORDER BY round_number ASC");
    $rounds = $stmtR->fetchAll();

    // 2. Fetch all teams and their round scores
    $stmtTeams = $pdo->query("SELECT id, team_name, created_at FROM teams ORDER BY id ASC");
    $teams = $stmtTeams->fetchAll();

    $stmtScores = $pdo->query("
        SELECT rs.team_id, rs.round_id, r.round_number, rs.total_points, rs.total_correct, rs.flagged, rs.submitted_at
        FROM round_scores rs
        JOIN rounds r ON rs.round_id = r.id
    ");
    $scores = $stmtScores->fetchAll();

    $scoresByTeam = [];
    foreach ($scores as $s) {
        $tId = (int)$s['team_id'];
        $rNum = (int)$s['round_number'];
        if (!isset($scoresByTeam[$tId])) {
            $scoresByTeam[$tId] = [];
        }
        $scoresByTeam[$tId][$rNum] = [
            'points'       => (int)$s['total_points'],
            'correct'      => (int)$s['total_correct'],
            'flagged'      => (bool)$s['flagged'],
            'submitted_at' => $s['submitted_at']
        ];
    }

    $leaderboard = [];

    foreach ($teams as $t) {
        $tId = (int)$t['id'];
        $tScores = $scoresByTeam[$tId] ?? [];

        $totalPoints  = 0;
        $totalCorrect = 0;
        $lastSubmitted = null;
        $completedRounds = 0;

        $roundBreakdown = [];
        for ($r = 1; $r <= 7; $r++) {
            if (isset($tScores[$r])) {
                $roundBreakdown[$r] = $tScores[$r]['points'];
                $totalPoints += $tScores[$r]['points'];
                $totalCorrect += $tScores[$r]['correct'];
                $completedRounds++;
                if (!$lastSubmitted || strtotime($tScores[$r]['submitted_at']) > strtotime($lastSubmitted)) {
                    $lastSubmitted = $tScores[$r]['submitted_at'];
                }
            } else {
                $roundBreakdown[$r] = null;
            }
        }

        $leaderboard[] = [
            'team_id'          => $tId,
            'team_name'        => $t['team_name'],
            'round_scores'     => $roundBreakdown,
            'grand_total'      => $totalPoints,
            'total_correct'    => $totalCorrect,
            'completed_rounds' => $completedRounds,
            'last_submitted'   => $lastSubmitted
        ];
    }

    // Sort leaderboard: grand_total DESC, total_correct DESC, last_submitted ASC, team_id ASC
    usort($leaderboard, function($a, $b) {
        if ($a['grand_total'] !== $b['grand_total']) {
            return $b['grand_total'] <=> $a['grand_total'];
        }
        if ($a['total_correct'] !== $b['total_correct']) {
            return $b['total_correct'] <=> $a['total_correct'];
        }
        if ($a['last_submitted'] && $b['last_submitted']) {
            return strtotime($a['last_submitted']) <=> strtotime($b['last_submitted']);
        }
        return $a['team_id'] <=> $b['team_id'];
    });

    // Add rank and qualification tags
    $rankedList = [];
    foreach ($leaderboard as $index => $row) {
        $rank = $index + 1;
        $row['rank'] = $rank;
        $row['is_top_5'] = ($rank <= 5);
        $row['is_top_3'] = ($rank <= 3);
        $rankedList[] = $row;
    }

    sendJson([
        'success'      => true,
        'timestamp'    => date('Y-m-d H:i:s'),
        'total_teams'  => count($rankedList),
        'rounds'       => array_map(function($r) {
            return [
                'round_number' => (int)$r['round_number'],
                'round_name'   => $r['round_name'],
                'is_active'    => (bool)$r['is_active']
            ];
        }, $rounds),
        'leaderboard'  => $rankedList
    ]);

} catch (Exception $e) {
    sendJson(['success' => false, 'error' => 'Leaderboard error: ' . $e->getMessage()], 500);
}
