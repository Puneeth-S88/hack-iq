<?php
// api/get_dashboard.php - Dashboard details, team standings, and round statuses
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireTeamAuth(true);

$teamId = (int)$_SESSION['team_id'];
$pdo = getDb();
if (!$pdo) {
    sendJson(['success' => false, 'error' => 'Database connection failed'], 500);
}

try {
    // 1. Fetch team info
    $stmt = $pdo->prepare("SELECT id, team_name, members_info, created_at FROM teams WHERE id = ?");
    $stmt->execute([$teamId]);
    $team = $stmt->fetch();
    if (!$team) {
        session_destroy();
        sendJson(['success' => false, 'error' => 'Team not found'], 404);
    }

    // 2. Compute current leaderboard rankings to determine top 5 / top 3 cutoffs
    // Ordered by grand_total DESC, then total_correct DESC, then last_submitted_at ASC
    $stmt = $pdo->query("
        SELECT 
            t.id AS team_id,
            t.team_name,
            COALESCE(SUM(rs.total_points), 0) AS total_points,
            COALESCE(SUM(rs.total_correct), 0) AS total_correct,
            MIN(rs.submitted_at) AS first_submission,
            MAX(rs.submitted_at) AS last_submission
        FROM teams t
        LEFT JOIN round_scores rs ON t.id = rs.team_id
        GROUP BY t.id, t.team_name
        ORDER BY total_points DESC, total_correct DESC, last_submission ASC, t.id ASC
    ");
    $leaderboard = $stmt->fetchAll();

    $currentRank = 1;
    $teamTotalPoints = 0;
    $teamTotalCorrect = 0;
    $totalTeams = count($leaderboard);

    foreach ($leaderboard as $index => $row) {
        $rank = $index + 1;
        if ((int)$row['team_id'] === $teamId) {
            $currentRank = $rank;
            $teamTotalPoints = (int)$row['total_points'];
            $teamTotalCorrect = (int)$row['total_correct'];
            break;
        }
    }

    // Qualification flags
    // Top 5 qualify for Round 6
    $qualifiesForRound6 = ($currentRank <= 5);
    // Top 3 qualify for Round 7
    $qualifiesForRound7 = ($currentRank <= 3);

    // 3. Fetch all rounds
    $stmt = $pdo->query("SELECT id, round_number, round_name, description, time_limit_minutes, is_active, max_violations, is_live FROM rounds ORDER BY round_number ASC");
    $rounds = $stmt->fetchAll();

    // 4. Fetch team's unlocked access
    $stmt = $pdo->prepare("SELECT round_id, unlocked_at FROM team_round_access WHERE team_id = ?");
    $stmt->execute([$teamId]);
    $unlockedMap = [];
    while ($row = $stmt->fetch()) {
        $unlockedMap[(int)$row['round_id']] = $row['unlocked_at'];
    }

    // 5. Fetch team's completed scores
    $stmt = $pdo->prepare("SELECT round_id, total_correct, total_points, submitted_at, flagged, manual_override FROM round_scores WHERE team_id = ?");
    $stmt->execute([$teamId]);
    $scoresMap = [];
    while ($row = $stmt->fetch()) {
        $scoresMap[(int)$row['round_id']] = $row;
    }

    // 6. Fetch violation counts
    $stmt = $pdo->prepare("SELECT round_id, COUNT(*) as violation_count FROM round_violations WHERE team_id = ? GROUP BY round_id");
    $stmt->execute([$teamId]);
    $violationsMap = [];
    while ($row = $stmt->fetch()) {
        $violationsMap[(int)$row['round_id']] = (int)$row['violation_count'];
    }

    $roundList = [];
    foreach ($rounds as $r) {
        $rNum = (int)$r['round_number'];
        $rId  = (int)$r['id'];

        $isEligible = true;
        $lockReason = '';

        if ($rNum === 6 && !$qualifiesForRound6) {
            $isEligible = false;
            $lockReason = 'Round 6 unlocks strictly for the Top 5 teams based on cumulative score.';
        } elseif ($rNum === 7 && !$qualifiesForRound7) {
            $isEligible = false;
            $lockReason = 'Round 7 (Final Showdown) unlocks strictly for the Top 3 teams.';
        }

        $isCompleted = isset($scoresMap[$rId]);
        $scoreData   = $isCompleted ? $scoresMap[$rId] : null;
        $isUnlocked  = isset($unlockedMap[$rId]);

        $roundList[] = [
            'id'                 => $rId,
            'round_number'       => $rNum,
            'round_name'         => $r['round_name'],
            'description'        => $r['description'],
            'time_limit_minutes' => (int)$r['time_limit_minutes'],
            'is_active'          => (bool)$r['is_active'],
            'is_live'            => (bool)$r['is_live'],
            'max_violations'     => (int)$r['max_violations'],
            'is_eligible'        => $isEligible,
            'lock_reason'        => $lockReason,
            'is_unlocked'        => $isUnlocked,
            'is_completed'       => $isCompleted,
            'score'              => $scoreData ? (int)$scoreData['total_points'] : null,
            'total_correct'      => $scoreData ? (int)$scoreData['total_correct'] : null,
            'flagged'            => $scoreData ? (bool)$scoreData['flagged'] : false,
            'violations_count'   => $violationsMap[$rId] ?? 0
        ];
    }

    sendJson([
        'success'            => true,
        'team'               => [
            'id'           => $team['id'],
            'team_name'    => $team['team_name'],
            'members_info' => $team['members_info']
        ],
        'standing'           => [
            'rank'          => $currentRank,
            'total_teams'   => $totalTeams,
            'total_points'  => $teamTotalPoints,
            'total_correct' => $teamTotalCorrect,
            'in_top_5'      => $qualifiesForRound6,
            'in_top_3'      => $qualifiesForRound7
        ],
        'rounds'             => $roundList
    ]);

} catch (Exception $e) {
    sendJson(['success' => false, 'error' => 'Dashboard error: ' . $e->getMessage()], 500);
}
