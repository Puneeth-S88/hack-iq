<?php
// includes/db.php - Resilient Database Connection Engine (MySQL with Cloud/Vercel SQLite fallback)
// HackIQ Cybersecurity Quiz Platform

date_default_timezone_set('Asia/Kolkata');

$db_host = getenv('DB_HOST') ?: (getenv('MYSQL_HOST') ?: '127.0.0.1');
$db_port = getenv('DB_PORT') ?: (getenv('MYSQL_PORT') ?: '3306');
$db_name = getenv('DB_NAME') ?: (getenv('MYSQL_DATABASE') ?: 'hackiq');
$db_user = getenv('DB_USER') ?: (getenv('MYSQL_USER') ?: 'root');
$db_pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : (getenv('MYSQL_PASSWORD') !== false ? getenv('MYSQL_PASSWORD') : '');

$pdo = null;

// Try MySQL first (Primary for XAMPP)
try {
    $dsn = "mysql:host={$db_host};port={$db_port};dbname={$db_name};charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT            => 2, // 2-second timeout so cloud doesn't hang if localhost is unreachable
    ];
    $pdo = new PDO($dsn, $db_user, $db_pass, $options);
} catch (PDOException $e) {
    // If MySQL connection fails (e.g. running in Vercel serverless without local MySQL),
    // automatically initialize SQLite fallback database so Vercel deployment works seamlessly
    try {
        $sqliteDir = sys_get_temp_dir() . '/hackiq';
        if (!is_dir($sqliteDir)) {
            @mkdir($sqliteDir, 0777, true);
        }
        $sqliteFile = $sqliteDir . '/hackiq.sqlite';
        $isNewSqlite = !file_exists($sqliteFile);

        $pdo = new PDO("sqlite:" . $sqliteFile);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        if ($isNewSqlite) {
            initSqliteSchema($pdo);
        }
    } catch (Exception $ex) {
        $pdo = null;
    }
}

/**
 * Initialize SQLite schema & seed questions for cloud/Vercel environments
 */
function initSqliteSchema($db) {
    $db->exec("
        CREATE TABLE IF NOT EXISTS admins (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE,
            password_hash TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS teams (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            team_name TEXT UNIQUE,
            password_hash TEXT,
            members_info TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
        CREATE TABLE IF NOT EXISTS rounds (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            round_number INTEGER UNIQUE,
            round_name TEXT,
            description TEXT,
            round_password_hash TEXT,
            plain_password_hint TEXT,
            time_limit_minutes INTEGER DEFAULT 15,
            is_active INTEGER DEFAULT 1,
            max_violations INTEGER DEFAULT 3,
            current_question_index INTEGER DEFAULT 0,
            is_live INTEGER DEFAULT 0
        );
        CREATE TABLE IF NOT EXISTS questions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            round_id INTEGER,
            question_text TEXT,
            question_type TEXT DEFAULT 'mcq',
            option_a TEXT,
            option_b TEXT,
            option_c TEXT,
            option_d TEXT,
            correct_answer TEXT,
            points INTEGER DEFAULT 1,
            order_num INTEGER DEFAULT 1,
            meta_info TEXT
        );
        CREATE TABLE IF NOT EXISTS team_round_access (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            team_id INTEGER,
            round_id INTEGER,
            unlocked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            started_at DATETIME,
            UNIQUE(team_id, round_id)
        );
        CREATE TABLE IF NOT EXISTS answers (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            team_id INTEGER,
            question_id INTEGER,
            round_id INTEGER,
            selected_answer TEXT,
            is_correct INTEGER DEFAULT 0,
            points_awarded INTEGER DEFAULT 0,
            buzzed_at DATETIME,
            submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(team_id, question_id)
        );
        CREATE TABLE IF NOT EXISTS round_scores (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            team_id INTEGER,
            round_id INTEGER,
            total_correct INTEGER DEFAULT 0,
            total_points INTEGER DEFAULT 0,
            submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            flagged INTEGER DEFAULT 0,
            manual_override INTEGER DEFAULT 0,
            admin_notes TEXT,
            UNIQUE(team_id, round_id)
        );
        CREATE TABLE IF NOT EXISTS round_violations (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            team_id INTEGER,
            round_id INTEGER,
            violation_type TEXT,
            occurred_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // Insert default admin
    $adminHash = password_hash('admin123', PASSWORD_DEFAULT);
    $db->exec("INSERT OR IGNORE INTO admins (username, password_hash) VALUES ('admin', '{$adminHash}')");

    // Seed default rounds
    $rounds = [
        [1, 'Round 1: MCQ Warmup', 'Everyday cybersecurity MCQs covering passwords, public Wi-Fi, 2FA, scams, and safe browsing. 10 MCQs, 1 point each.', password_hash('WARMUP26', PASSWORD_DEFAULT), 'WARMUP26', 10],
        [2, 'Round 2: Rapid Fire True/False', 'Quick-fire True/False cybersecurity statements testing intuition and fundamentals. 10 statements, 1 point each.', password_hash('RAPID26', PASSWORD_DEFAULT), 'RAPID26', 10],
        [3, 'Round 3: Spot the Phishing', 'Inspect simulated real-world email, SMS, and login pages to identify red flags and phishing indicators. 10 items, 2 points each.', password_hash('PHISH26', PASSWORD_DEFAULT), 'PHISH26', 15],
        [4, 'Round 4: Decode the Message', 'Decrypt hidden messages encoded using Caesar Ciphers, ROT13, and Atbash substitution. 10 ciphers, 3 points each.', password_hash('CIPHER26', PASSWORD_DEFAULT), 'CIPHER26', 15],
        [5, 'Round 5: Case Study Pitch', 'Real-world incident response pitch. 2 minutes prep, 2 minutes pitch to the jury. 10 points max. Top 5 teams advance to Semifinals!', password_hash('PITCH26', PASSWORD_DEFAULT), 'PITCH26', 15],
        [6, 'Round 6: Semifinal Challenge', 'Part A: Speed Round (10 pts) + Part B: Explain to Jury (15 pts). Restricted to Top 5 teams. Top 3 teams advance to Finals!', password_hash('SEMI26', PASSWORD_DEFAULT), 'SEMI26', 20],
        [7, 'Round 7: Final Showdown (Live Breach)', 'The Live Breach Room: multi-stage college portal incident decision simulation + tiebreaker. Restricted to Top 3 teams.', password_hash('FINAL26', PASSWORD_DEFAULT), 'FINAL26', 15]
    ];

    $stmtR = $db->prepare("INSERT OR IGNORE INTO rounds (round_number, round_name, description, round_password_hash, plain_password_hint, time_limit_minutes, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
    foreach ($rounds as $r) {
        $stmtR->execute($r);
    }
}

function getDb() {
    global $pdo;
    return $pdo;
}

function sendJson($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}
