<?php
// dashboard.php - Team Dashboard & Round Arena
require_once __DIR__ . '/includes/auth.php';
requireTeamAuth(false);
$team = getLoggedInTeam();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tournament Arena — HackIQ</title>
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
        <span class="brand-tag">Round Arena</span>
      </div>
    </a>
    <div class="nav-menu">
      <a href="dashboard.php" class="nav-link active">Rounds</a>
      <a href="leaderboard.php" class="nav-link">Live Leaderboard</a>
    </div>
    <div class="nav-actions">
      <div class="team-badge">
        <span class="badge-dot"></span>
        <span id="dashTeamName"><?= htmlspecialchars($team['team_name']) ?></span>
      </div>
      <button class="btn btn-secondary btn-sm" onclick="logoutTeam()">Logout</button>
    </div>
  </nav>

  <!-- Dashboard Overview Banner -->
  <div class="container">
    <div class="card" style="margin-bottom:2rem; background: linear-gradient(135deg, rgba(16,24,40,0.95), rgba(13,26,45,0.95)); border-color:var(--accent-cyan);">
      <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1.5rem;">
        <div>
          <span style="font-family:var(--font-mono); font-size:0.8rem; color:var(--accent-cyan); text-transform:uppercase;">Battle Station</span>
          <h2 style="font-size:1.75rem; font-weight:800; margin-top:0.2rem;">Welcome, Team <span style="color:var(--accent-green);"><?= htmlspecialchars($team['team_name']) ?></span></h2>
          <p style="color:var(--text-muted); font-size:0.95rem; margin-top:0.35rem;">Enter the designated secret passcode announced by coordinators before initiating each round.</p>
        </div>
        <div style="display:flex; gap:1.5rem; align-items:center;">
          <div style="text-align:right;">
            <div style="font-size:0.8rem; color:var(--text-muted); font-family:var(--font-mono); text-transform:uppercase;">Current Rank</div>
            <div style="font-size:2rem; font-weight:800; font-family:var(--font-mono); color:var(--accent-green);" id="dashTeamRank">#-</div>
          </div>
          <div style="text-align:right; border-left:1px solid var(--border-color); padding-left:1.5rem;">
            <div style="font-size:0.8rem; color:var(--text-muted); font-family:var(--font-mono); text-transform:uppercase;">Grand Total</div>
            <div style="font-size:2rem; font-weight:800; font-family:var(--font-mono); color:var(--accent-cyan);" id="dashTeamScore">- pts</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Qualification Progression Alerts -->
    <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(260px, 1fr)); gap:1rem; margin-bottom:2rem;">
      <div class="meta-box" style="text-align:left; border-left:3px solid var(--accent-green);">
        <div class="meta-box-label">Stages 1 to 5</div>
        <div style="font-size:0.9rem; color:var(--text-main);"><strong>Open to All 15 Teams</strong> • Auto-graded & live pitch rounds.</div>
      </div>
      <div class="meta-box" style="text-align:left; border-left:3px solid var(--accent-purple);">
        <div class="meta-box-label">Stage 6: Semifinal</div>
        <div style="font-size:0.9rem; color:var(--text-main);"><strong>Top 5 Teams Only</strong> • Determined by cumulative score after Round 5.</div>
      </div>
      <div class="meta-box" style="text-align:left; border-left:3px solid #ffd700;">
        <div class="meta-box-label">Stage 7: Final Showdown</div>
        <div style="font-size:0.9rem; color:var(--text-main);"><strong>Top 3 Teams Only</strong> • Live Breach Room incident simulation for podium.</div>
      </div>
    </div>

    <!-- Rounds Grid -->
    <div class="rounds-grid" id="roundsContainer">
      <!-- Loaded dynamically via Dashboard.init() -->
      <div style="grid-column: 1 / -1; text-align:center; padding:3rem; color:var(--text-muted);">
        Loading tournament rounds...
      </div>
    </div>
  </div>

  <!-- Round Password Modal -->
  <div class="modal-overlay" id="passwordModal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 id="modalRoundTitle" style="font-weight:700;">Enter Round Password</h3>
        <button class="modal-close" onclick="Dashboard.closePasswordModal()">&times;</button>
      </div>
      <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:1.25rem;">
        Enter the unique per-round password announced by the event coordinators for this round:
      </p>

      <div id="passwordError" style="display:none; padding:0.6rem; background:rgba(255,51,102,0.15); border:1px solid var(--accent-red); color:#ff99aa; border-radius:var(--radius-sm); font-size:0.85rem; font-family:var(--font-mono); margin-bottom:1rem;"></div>

      <div class="form-group">
        <label class="form-label" for="roundPasswordInput">Round Passcode</label>
        <input type="text" id="roundPasswordInput" class="form-input" placeholder="e.g. WARMUP26" style="text-transform:uppercase; font-family:var(--font-mono); letter-spacing:2px; font-weight:700;" onkeydown="if(event.key==='Enter') Dashboard.verifyPassword()">
      </div>

      <div style="display:flex; justify-content:flex-end; gap:0.75rem; margin-top:1.5rem;">
        <button class="btn btn-secondary" onclick="Dashboard.closePasswordModal()">Cancel</button>
        <button class="btn btn-primary" onclick="Dashboard.verifyPassword()">Unlock & Proceed</button>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer class="footer">
    <div><strong>HackIQ 2026</strong> — Think Fast. Hack Smart.</div>
    <div class="footer-meta">Round Arena • Venue LSL04 • 15.10.2026</div>
  </footer>

  <script src="js/app.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      Dashboard.init();
    });

    async function logoutTeam() {
      if (confirm('Are you sure you want to log out of HackIQ?')) {
        await App.fetch('api/logout.php');
        window.location.href = 'index.php';
      }
    }
  </script>
</body>
</html>
