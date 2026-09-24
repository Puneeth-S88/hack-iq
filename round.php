<?php
// round.php - Generic Round Arena with Active Proctoring & Anti-Cheat Engine
require_once __DIR__ . '/includes/auth.php';
requireTeamAuth(false);

$team = getLoggedInTeam();
$roundNum = isset($_GET['round']) ? (int)$_GET['round'] : 1;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Round <?= $roundNum ?> — HackIQ Battleground</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="question-protected">

  <!-- Proctoring Alert Banner -->
  <div class="proctoring-banner" id="proctoringBanner">
    <div>
      🛡️ <strong>HackIQ Proctoring Active:</strong> Tab switching, right-click, devtools, and copy/paste are logged in real-time.
    </div>
    <div style="display:flex; align-items:center; gap:0.75rem;">
      <span>Strikes:</span>
      <div class="strike-pills" id="strikePills">
        <!-- Rendered by ProctoringEngine -->
      </div>
    </div>
  </div>

  <!-- Active Round Header Bar -->
  <div class="container" style="padding-top: 1rem;">
    <div class="round-topbar">
      <div>
        <div style="font-size:0.8rem; font-family:var(--font-mono); color:var(--accent-cyan); text-transform:uppercase;">
          Live Battle Arena • Round <?= $roundNum ?>
        </div>
        <h2 style="font-size:1.4rem; font-weight:800; margin-top:0.2rem;" id="roundTitleHeader">
          Loading Round...
        </h2>
      </div>

      <div style="display:flex; align-items:center; gap:1rem;">
        <div class="timer-box">
          <span>⏱</span>
          <span id="roundTimer">--:--</span>
        </div>
        <a href="dashboard.php" class="btn btn-secondary btn-sm" onclick="return confirm('Return to Dashboard? Unsubmitted answers will remain unrecorded.');">Dashboard</a>
      </div>
    </div>

    <!-- Questions Container -->
    <div id="questionsList">
      <div style="text-align:center; padding:3rem; color:var(--text-muted);">
        Initializing secure environment...
      </div>
    </div>

    <!-- Submit Section -->
    <div style="margin: 2.5rem 0 4rem; display:flex; justify-content:flex-end;" id="submitSection">
      <button class="btn btn-primary btn-lg" id="submitRoundBtn" onclick="ProctoringEngine.submitRound()">
        Submit Round Answers ✔
      </button>
    </div>
  </div>

  <!-- Fullscreen Initial Lock Overlay -->
  <div class="fullscreen-overlay" id="fullscreenPromptOverlay">
    <div class="fullscreen-box">
      <div style="font-size:2.5rem; margin-bottom:0.75rem;">🔒</div>
      <h2 style="font-size:1.75rem; font-weight:800; margin-bottom:0.75rem;">Entering Monitored Round Arena</h2>
      <p style="color:var(--text-muted); font-size:0.95rem; line-height:1.6; margin-bottom:1.5rem;">
        This round is governed by the <strong>HackIQ Proctoring System</strong>.<br>
        • Maximum of <strong>3 strikes</strong> permitted before automatic answer submission.<br>
        • Tab switching, exiting fullscreen, right-clicking, and copy-paste are strictly tracked.<br>
        • Keep this window maximized and focused for the duration of the round.
      </p>
      <button class="btn btn-primary btn-lg" onclick="ProctoringEngine.startRoundWithFullscreen()">
        Enter Fullscreen & Begin Round
      </button>
    </div>
  </div>

  <!-- Fullscreen Resume Overlay (if participant pressed Esc) -->
  <div class="fullscreen-overlay" id="fullscreenResumeOverlay" style="display:none;">
    <div class="fullscreen-box" style="border-color:var(--accent-red);">
      <div style="font-size:2.5rem; margin-bottom:0.75rem;">⚠️</div>
      <h2 style="font-size:1.75rem; font-weight:800; color:var(--accent-red); margin-bottom:0.75rem;">Fullscreen Exited!</h2>
      <p style="color:var(--text-muted); font-size:0.95rem; line-height:1.6; margin-bottom:1.5rem;">
        You have exited fullscreen mode. A proctoring violation strike has been logged on the event server.<br>
        Please re-enter fullscreen immediately to resume answering.
      </p>
      <button class="btn btn-danger btn-lg" onclick="ProctoringEngine.resumeFullscreen()">
        Re-enter Fullscreen Now
      </button>
    </div>
  </div>

  <!-- Results Modal -->
  <div class="modal-overlay" id="resultsModal">
    <div class="modal-content" style="text-align:center;">
      <div style="font-size:3rem; margin-bottom:0.5rem;" id="resultEmoji">🎯</div>
      <h2 id="resultTitle" style="font-size:1.6rem; font-weight:800; margin-bottom:0.5rem;">Round Completed!</h2>
      <p style="color:var(--text-muted); font-size:0.95rem; margin-bottom:1.5rem;">
        Your responses have been successfully recorded in the tournament database.
      </p>

      <div style="background:var(--bg-tertiary); border:1px solid var(--border-color); border-radius:var(--radius-md); padding:1.25rem; margin-bottom:1.75rem;">
        <div style="font-size:0.8rem; color:var(--text-muted); font-family:var(--font-mono); text-transform:uppercase;">Round Score</div>
        <div style="font-size:2.5rem; font-weight:900; font-family:var(--font-mono); color:var(--accent-green);" id="resultScore">0 / 0</div>
        <div style="font-size:0.9rem; color:var(--accent-cyan); font-family:var(--font-mono);" id="resultCorrect">- Correct</div>
      </div>

      <div style="display:flex; justify-content:center; gap:1rem;">
        <a href="dashboard.php" class="btn btn-primary">Return to Dashboard</a>
        <a href="leaderboard.php" class="btn btn-secondary">View Leaderboard</a>
      </div>
    </div>
  </div>

  <script src="js/app.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', () => {
      ProctoringEngine.init(<?= $roundNum ?>);
    });
  </script>
</body>
</html>
