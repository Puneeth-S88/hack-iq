<?php
// index.php - Direct Battle Arena: All 7 Rounds Accessible via Round Passwords
require_once __DIR__ . '/includes/auth.php';
$team = getLoggedInTeam();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>HackIQ — Think Fast. Hack Smart.</title>
  <link rel="stylesheet" href="css/style.css?v=<?= time() ?>">
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
        <span class="brand-tag">Cyber Arena</span>
      </div>
    </a>
    <div class="nav-menu">
      <a href="index.php" class="nav-link active">Rounds 1–7</a>
      <a href="leaderboard.php" class="nav-link">Live Leaderboard</a>
      <a href="admin.php" class="nav-link">Coordinators</a>
    </div>
    <div class="nav-actions">
      <div class="team-badge" id="teamBadgeContainer" style="<?= $team ? 'display:flex;' : 'display:none;' ?>">
        <span class="badge-dot"></span>
        <span id="currentTeamBadge"><?= htmlspecialchars($team['team_name'] ?? '') ?></span>
      </div>
      <a href="leaderboard.php" class="btn btn-secondary btn-sm">Leaderboard ↗</a>
    </div>
  </nav>

  <!-- Hero Section -->
  <div class="hero" style="padding: 2.5rem 1rem 1.5rem;">
    <div class="hero-glow"></div>
    <div class="hero-content">
      <div class="event-meta-pill">
        <span>📅 15.10.2026</span>
        <span>•</span>
        <span>⏰ 2:00 PM – 4:00 PM</span>
        <span>•</span>
        <span>📍 Venue: LSL04</span>
      </div>
      <h1 class="hero-title" style="font-size:2.8rem; margin-bottom:0.75rem;">Think Fast.<br><span>Hack Smart.</span></h1>
      <p class="hero-subtitle" style="font-size:1.1rem; margin-bottom:1rem;">
        Welcome to HackIQ. Enter your Team Name and the per-round password announced by coordinators to unlock and start each round.
      </p>

      <div class="hero-meta-grid" style="margin: 1.5rem 0;">
        <div class="meta-box">
          <div class="meta-box-label">Prize Pool</div>
          <div class="meta-box-value">₹6,000</div>
        </div>
        <div class="meta-box">
          <div class="meta-box-label">Tournament Flow</div>
          <div class="meta-box-value">7 Tactical Rounds</div>
        </div>
        <div class="meta-box">
          <div class="meta-box-label">Team Size</div>
          <div class="meta-box-value">4 Members</div>
        </div>
        <div class="meta-box">
          <div class="meta-box-label">Student Coordinators</div>
          <div class="meta-box-value" style="font-size:1rem;">Puneeth S & Prajwal BU</div>
        </div>
      </div>
    </div>
  </div>

  <!-- All 7 Rounds Direct Grid -->
  <div class="container" style="padding-top:0;">

    <!-- Quick Team Name Setup Card -->
    <div class="card" style="margin-bottom:1.5rem; background:linear-gradient(135deg, rgba(0, 240, 255, 0.08), rgba(15, 23, 42, 0.7)); border:1px solid rgba(0, 240, 255, 0.35);">
      <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
        <div>
          <div style="font-weight:700; font-size:1.1rem; color:var(--accent-cyan); display:flex; align-items:center; gap:0.5rem;">
            <span>🛡️</span> Your Team: <span id="displayTeamName" style="color:#fff; font-family:var(--font-mono); font-weight:800;"><?= htmlspecialchars($team['team_name'] ?? 'Not set') ?></span>
          </div>
          <div style="font-size:0.85rem; color:var(--text-muted); margin-top:0.25rem;">
            Enter your team name once. Only secret round passwords are required to unlock each round!
          </div>
        </div>
        <div style="display:flex; align-items:center; gap:0.5rem; flex:1; max-width:420px; min-width:260px;">
          <input type="text" id="globalTeamNameInput" class="form-input" placeholder="Type Team Name (e.g. CyberKnights)" value="<?= htmlspecialchars($team['team_name'] ?? '') ?>" oninput="Dashboard.onGlobalTeamNameChange(this.value)">
          <button class="btn btn-cyan btn-sm" onclick="Dashboard.saveGlobalTeamName()" style="white-space:nowrap;">Save Team</button>
        </div>
      </div>
    </div>

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.25rem; flex-wrap:wrap; gap:1rem;">
      <div>
        <h2 style="font-size:1.6rem; font-weight:800;">Tournament Rounds (1 to 7)</h2>
        <p style="color:var(--text-muted); font-size:0.9rem;">Click any round below and enter the round password announced by coordinators.</p>
      </div>
      <a href="leaderboard.php" class="btn btn-cyan btn-sm">View Live Rankings →</a>
    </div>

    <!-- 7 Rounds Rendered Dynamically -->
    <div class="rounds-grid" id="roundsContainer">
      <div style="grid-column: 1 / -1; text-align:center; padding:3rem; color:var(--text-muted);">
        Loading tournament arena...
      </div>
    </div>
  </div>

  <!-- Password & Team Entry Modal -->
  <div class="modal-overlay" id="passwordModal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 id="modalRoundTitle" style="font-weight:700;">Enter Round Password</h3>
        <button class="modal-close" onclick="Dashboard.closePasswordModal()">&times;</button>
      </div>
      <p style="color:var(--text-muted); font-size:0.88rem; margin-bottom:1.25rem;">
        Enter your team name and the secret round password announced by coordinators:
      </p>

      <div id="passwordError" style="display:none; padding:0.65rem 0.85rem; background:rgba(255,51,102,0.15); border:1px solid var(--accent-red); color:#ff99aa; border-radius:var(--radius-sm); font-size:0.85rem; font-family:var(--font-mono); margin-bottom:1rem;"></div>

      <div class="form-group">
        <label class="form-label" for="teamNameInput">Team Name</label>
        <input type="text" id="teamNameInput" class="form-input" placeholder="e.g. CyberKnights" required>
        <div class="form-help">Any name for your group (no login password needed).</div>
      </div>

      <div class="form-group">
        <label class="form-label" for="roundPasswordInput">Round Password</label>
        <input type="text" id="roundPasswordInput" class="form-input" placeholder="e.g. WARMUP26" style="text-transform:uppercase; font-family:var(--font-mono); letter-spacing:2px; font-weight:700;" onkeydown="if(event.key==='Enter') Dashboard.verifyPassword()" required>
        <div class="form-help">Secret passcode announced by coordinators for this round.</div>
      </div>

      <div style="display:flex; justify-content:flex-end; gap:0.75rem; margin-top:1.5rem;">
        <button class="btn btn-secondary" onclick="Dashboard.closePasswordModal()">Cancel</button>
        <button class="btn btn-primary" onclick="Dashboard.verifyPassword()">Unlock & Start Round →</button>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer class="footer">
    <div><strong>HackIQ 2026</strong> — Think Fast. Hack Smart.</div>
    <div class="footer-meta">Audit LSL04 • 15.10.2026 • Student Coordinators: Puneeth S & Prajwal BU</div>
  </footer>

  <script src="js/app.js?v=<?= time() ?>"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      Dashboard.init();
    });
  </script>
</body>
</html>
