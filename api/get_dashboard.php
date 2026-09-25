<?php
// api/get_dashboard.php - Dashboard details and round statuses
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

$pdo = getDb();
if (!$pdo) {
    sendJson(['success' => false, 'error' => 'Database connection failed'], 500);
}

$teamId = isTeamLoggedIn() ? (int)$_SESSION['team_id'] : null;

try {
    // 1. Fetch team info if logged in
    $team = null;
    $teamTotalPoints = 0;
    $teamTotalCorrect = 0;
    $currentRank = '-';

    if ($teamId) {
        $stmt = $pdo->prepare("SELECT id, team_name, members_info, created_at FROM teams WHERE id = ?");
        $stmt->execute([$teamId]);
        $team = $stmt->fetch();

        // Compute rankings
        $stmtRank = $pdo->query("
            SELECT t.id AS team_id, COALESCE(SUM(rs.total_points), 0) AS total_points, COALESCE(SUM(rs.total_correct), 0) AS total_correct
            FROM teams t
            LEFT JOIN round_scores rs ON t.id = rs.team_id
            GROUP BY t.id
            ORDER BY total_points DESC, total_correct DESC, t.id ASC
        ");
        $all = $stmtRank->fetchAll();
        foreach ($all as $idx => $row) {
            if ((int)$row['team_id'] === $teamId) {
                $currentRank = $idx + 1;
                $teamTotalPoints = (int)$row['total_points'];
                $teamTotalCorrect = (int)$row['total_correct'];
                break;
            }
        }
    }

    // 2. Fetch all 7 rounds
    $stmtR = $pdo->query("SELECT id, round_number, round_name, description, time_limit_minutes, is_active, max_violations, is_live FROM rounds ORDER BY round_number ASC");
    $rounds = $stmtR->fetchAll();

    // 3. Team access and scores if logged in
    $unlockedMap = [];
    $scoresMap = [];
    $violationsMap = [];

    if ($teamId) {
        $stmtAccess = $pdo->prepare("SELECT round_id, unlocked_at FROM team_round_access WHERE team_id = ?");
        $stmtAccess->execute([$teamId]);
        while ($row = $stmtAccess->fetch()) {
            $unlockedMap[(int)$row['round_id']] = $row['unlocked_at'];
        }

        $stmtScore = $pdo->prepare("SELECT round_id, total_correct, total_points, submitted_at, flagged FROM round_scores WHERE team_id = ?");
        $stmtScore->execute([$teamId]);
        while ($row = $stmtScore->fetch()) {
            $scoresMap[(int)$row['round_id']] = $row;
        }

        $stmtV = $pdo->prepare("SELECT round_id, COUNT(*) as cnt FROM round_violations WHERE team_id = ? GROUP BY round_id");
        $stmtV->execute([$teamId]);
        while ($row = $stmtV->fetch()) {
            $violationsMap[(int)$row['round_id']] = (int)$row['cnt'];
        }
    }

    $roundList = [];
    foreach ($rounds as $r) {
        $rNum = (int)$r['round_number'];
        $rId  = (int)$r['id'];

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
            'is_eligible'        => true, // All rounds accessible with password!
            'is_unlocked'        => $isUnlocked,
            'is_completed'       => $isCompleted,
            'score'              => $scoreData ? (int)$scoreData['total_points'] : null,
            'total_correct'      => $scoreData ? (int)$scoreData['total_correct'] : null,
            'flagged'            => $scoreData ? (bool)$scoreData['flagged'] : false,
            'violations_count'   => $violationsMap[$rId] ?? 0
        ];
    }

    sendJson([
        'success'  => true,
        'team'     => $team ? [
            'id'        => $team['id'],
            'team_name' => $team['team_name']
        ] : null,
        'standing' => [
            'rank'          => $currentRank,
            'total_points'  => $teamTotalPoints,
            'total_correct' => $teamTotalCorrect
        ],
        'rounds'   => $roundList
    ]);

} catch (Exception $e) {
    sendJson(['success' => false, 'error' => 'Dashboard error: ' . $e->getMessage()], 500);
}
