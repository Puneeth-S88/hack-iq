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
    return isset($_SESSION['team_id']) && !empty($_SESSION['team_id']);
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
            header('Location: login.php');
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
