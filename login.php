<?php
// login.php - Streamlined Team Entry (No complex password needed - Team Name can be anything!)
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

// If team name submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $teamName = trim($_POST['team_name'] ?? '');
    if (!empty($teamName)) {
        $pdo = getDb();
        if ($pdo) {
            $stmt = $pdo->prepare("SELECT id, team_name FROM teams WHERE LOWER(team_name) = LOWER(?) LIMIT 1");
            $stmt->execute([$teamName]);
            $team = $stmt->fetch();
            if (!$team) {
                $dummy = password_hash('hack123', PASSWORD_DEFAULT);
                $stmtInsert = $pdo->prepare("INSERT INTO teams (team_name, password_hash, members_info) VALUES (?, ?, 'Team')");
                $stmtInsert->execute([$teamName, $dummy]);
                $teamId = (int)$pdo->lastInsertId();
            } else {
                $teamId = (int)$team['id'];
                $teamName = $team['team_name'];
            }
            $_SESSION['team_id'] = $teamId;
            $_SESSION['team_name'] = $teamName;
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Enter Team Name — HackIQ</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700;800&display=swap" rel="stylesheet">
</head>
<body>

  <!-- Navigation Bar -->
  <nav class="navbar">
    <a href="index.php" class="nav-brand">
      <div class="brand-icon">HQ</div>
      <div>
        <div class="brand-title">HackIQ</div>
        <span class="brand-tag">Team Entry</span>
      </div>
    </a>
    <div class="nav-actions">
      <a href="index.php" class="nav-link">← Go to Rounds 1–7</a>
    </div>
  </nav>

  <div class="container">
    <div class="auth-wrapper" style="margin-top:4rem;">
      <div class="card" style="text-align:center;">
        <div style="font-size:3rem; margin-bottom:0.75rem;">🛡️</div>
        <h2 style="font-size:1.75rem; font-weight:800; margin-bottom:0.5rem;">Enter Your Team Name</h2>
        <p style="color:var(--text-muted); font-size:0.95rem; margin-bottom:1.75rem;">
          Type any team name you want. Passwords are only required when opening each round.
        </p>

        <form method="POST" action="login.php">
          <div class="form-group" style="text-align:left;">
            <label class="form-label" for="team_name">Team Name</label>
            <input type="text" id="team_name" name="team_name" class="form-input" placeholder="e.g. CyberKnights" required autofocus style="font-size:1.1rem; padding:0.85rem 1rem;">
            <div class="form-help">Enter any team name for your group.</div>
          </div>
          <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:1.5rem;">
            Continue to Rounds Arena →
          </button>
        </form>

        <div style="margin-top:1.5rem; font-size:0.85rem; color:var(--text-dim);">
          Venue: <strong>LSL04</strong> • Coordinators: <strong>Puneeth S & Prajwal BU</strong>
        </div>
      </div>
    </div>
  </div>

</body>
</html>
