<?php
// includes/auth.php - Session and authentication management
// HackIQ Cybersecurity Quiz Platform

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if a team is logged in
 */
function isTeamLoggedIn() {
    if (isset($_SESSION['team_id']) && !empty($_SESSION['team_id'])) {
        return true;
    }
    // Auto-restore from persistent cookie if available
    if (!empty($_COOKIE['hackiq_team_name'])) {
        require_once __DIR__ . '/db.php';
        $pdo = getDb();
        if ($pdo) {
            $cookieTeam = trim($_COOKIE['hackiq_team_name']);
            try {
                $stmt = $pdo->prepare("SELECT id, team_name FROM teams WHERE LOWER(team_name) = LOWER(?) LIMIT 1");
                $stmt->execute([$cookieTeam]);
                $team = $stmt->fetch();
                if (!$team) {
                    $dummyHash = password_hash('hack123', PASSWORD_DEFAULT);
                    $stmtInsert = $pdo->prepare("INSERT INTO teams (team_name, password_hash, members_info) VALUES (?, ?, 'Team Participants')");
                    $stmtInsert->execute([$cookieTeam, $dummyHash]);
                    $_SESSION['team_id'] = (int)$pdo->lastInsertId();
                    $_SESSION['team_name'] = $cookieTeam;
                } else {
                    $_SESSION['team_id'] = (int)$team['id'];
                    $_SESSION['team_name'] = $team['team_name'];
                }
                return true;
            } catch (Exception $e) {
                // Ignore DB error here
            }
        }
    }
    return false;
}

/**
 * Require team authentication, otherwise redirect or return JSON error
 */
function requireTeamAuth($isApi = false) {
    if (!isTeamLoggedIn()) {
        if ($isApi) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error'   => 'Unauthorized. Please log in as a team.'
            ]);
            exit;
        } else {
            header('Location: index.php');
            exit;
        }
    }
}

/**
 * Get logged-in team info
 */
function getLoggedInTeam() {
    if (!isTeamLoggedIn()) {
        return null;
    }
    return [
        'id'        => $_SESSION['team_id'],
        'team_name' => $_SESSION['team_name'] ?? 'Team'
    ];
}

/**
 * Check if admin is logged in
 */
function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Require admin authentication
 */
function requireAdminAuth($isApi = false) {
    if (!isAdminLoggedIn()) {
        if ($isApi) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error'   => 'Admin authentication required.'
            ]);
            exit;
        } else {
            header('Location: admin.php');
            exit;
        }
    }
}
