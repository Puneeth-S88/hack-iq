<?php
// leaderboard.php - Public & Projector Live Tournament Leaderboard
require_once __DIR__ . '/includes/auth.php';
$isLoggedIn = isTeamLoggedIn();
$team = getLoggedInTeam();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Live Leaderboard — HackIQ 2026</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    body.projector-mode {
      background: #03060a !important;
    }
    body.projector-mode .container {
      max-width: 98% !important;
      padding: 1rem !important;
    }
    body.projector-mode .leaderboard-table th {
      font-size: 1rem !important;
      padding: 1.2rem !important;
    }
    body.projector-mode .leaderboard-table td {
      font-size: 1.3rem !important;
      padding: 1.2rem !important;
    }
    body.projector-mode .rank-cell {
      font-size: 1.5rem !important;
    }
  </style>
</head>
<body>

  <!-- Navigation Bar -->
  <nav class="navbar">
    <a href="index.php" class="nav-brand">
      <div class="brand-icon">HQ</div>
      <div>
        <div class="brand-title">HackIQ</div>
        <span class="brand-tag">Live Standings</span>
      </div>
    </a>
    <div class="nav-menu">
      <a href="index.php" class="nav-link">Home</a>
      <a href="leaderboard.php" class="nav-link active">Leaderboard</a>
      <?php if ($isLoggedIn): ?>
        <a href="dashboard.php" class="nav-link">Round Arena</a>
      <?php endif; ?>
    </div>
    <div class="nav-actions">
      <button class="btn btn-secondary btn-sm" onclick="Leaderboard.toggleProjectorMode()" title="Optimized high-contrast view for projector screens">
        📽 Projector View
      </button>
      <?php if ($isLoggedIn): ?>
        <a href="dashboard.php" class="btn btn-primary btn-sm">My Dashboard</a>
      <?php else: ?>
        <a href="login.php" class="btn btn-primary btn-sm">Team Login</a>
      <?php endif; ?>
    </div>
  </nav>

  <div class="container">
    <div style="display:flex; justify-content:space-between; align-items:flex-end; margin-bottom:1.5rem; flex-wrap:wrap; gap:1rem;">
      <div>
        <span style="font-family:var(--font-mono); font-size:0.8rem; color:var(--accent-green); text-transform:uppercase;">
          ● Live Tournament Feed (Auto-Refreshes every 10s)
        </span>
        <h1 style="font-size:2rem; font-weight:800; margin-top:0.25rem;">Tournament Standings</h1>
      </div>

      <div style="display:flex; gap:1rem; align-items:center;">
        <input type="text" id="teamFilter" class="form-input" placeholder="🔍 Search Team Name..." style="width:240px; padding:0.5rem 0.85rem;" oninput="Leaderboard.fetchData()">
        <span style="font-size:0.8rem; color:var(--text-dim); font-family:var(--font-mono);">
          Last Sync: <span id="lastUpdateTime">--:--:--</span>
        </span>
      </div>
    </div>

    <!-- Cutoff Guidelines Card -->
    <div style="display:flex; gap:1rem; flex-wrap:wrap; margin-bottom:1.5rem;">
      <div class="meta-box" style="flex:1; text-align:left; padding:0.75rem 1rem;">
        <span style="color:var(--accent-green); font-weight:700;">🟢 Top 5 Cutoff:</span> Qualify for Round 6 Semifinal Challenge.
      </div>
      <div class="meta-box" style="flex:1; text-align:left; padding:0.75rem 1rem;">
        <span style="color:#ffd700; font-weight:700;">🏆 Top 3 Cutoff:</span> Qualify for Round 7 Final Showdown & Podium.
      </div>
    </div>

    <!-- Leaderboard Table -->
    <div class="leaderboard-table-wrapper">
      <table class="leaderboard-table">
        <thead>
          <tr>
            <th style="width:90px;">Rank</th>
            <th>Team Name</th>
            <th style="text-align:center;">R1 (10)</th>
            <th style="text-align:center;">R2 (10)</th>
            <th style="text-align:center;">R3 (20)</th>
            <th style="text-align:center;">R4 (30)</th>
            <th style="text-align:center;">R5 (10)</th>
            <th style="text-align:center;">R6 (25)</th>
            <th style="text-align:center;">R7 (26)</th>
            <th style="text-align:right; width:130px;">Total Score</th>
          </tr>
        </thead>
        <tbody id="leaderboardBody">
          <tr>
            <td colspan="10" style="text-align:center; padding:3rem; color:var(--text-muted);">
              Connecting to live tournament server...
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Footer -->
  <footer class="footer">
    <div><strong>HackIQ 2026</strong> — Think Fast. Hack Smart.</div>
    <div class="footer-meta">Venue: LSL04 • 15.10.2026 • Live Projector Display Ready</div>
  </footer>

  <script src="js/app.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      Leaderboard.init();
    });
  </script>
</body>
</html>
