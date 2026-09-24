<?php
// index.php - Welcome Page
require_once __DIR__ . '/includes/auth.php';
$isLoggedIn = isTeamLoggedIn();
$team = getLoggedInTeam();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>HackIQ — Think Fast. Hack Smart.</title>
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
        <span class="brand-tag">Cyber Battleground</span>
      </div>
    </a>
    <div class="nav-menu">
      <a href="index.php" class="nav-link active">Home</a>
      <a href="leaderboard.php" class="nav-link">Live Leaderboard</a>
      <?php if ($isLoggedIn): ?>
        <a href="dashboard.php" class="nav-link">Round Arena</a>
      <?php endif; ?>
    </div>
    <div class="nav-actions">
      <?php if ($isLoggedIn): ?>
        <div class="team-badge">
          <span class="badge-dot"></span>
          <span><?= htmlspecialchars($team['team_name']) ?></span>
        </div>
        <a href="dashboard.php" class="btn btn-primary btn-sm">Enter Arena</a>
      <?php else: ?>
        <a href="login.php" class="btn btn-primary btn-sm">Team Login / Register</a>
      <?php endif; ?>
    </div>
  </nav>

  <!-- Hero Section -->
  <div class="hero">
    <div class="hero-glow"></div>
    <div class="hero-content">
      <div class="event-meta-pill">
        <span>📅 15.10.2026</span>
        <span>•</span>
        <span>⏰ 2:00 PM – 4:00 PM</span>
        <span>•</span>
        <span>📍 Venue: LSL04</span>
      </div>
      <h1 class="hero-title">Think Fast.<br><span>Hack Smart.</span></h1>
      <p class="hero-subtitle">
        A premier 7-round cybersecurity awareness and tactical decision tournament. 
        Test your digital defense instincts, crack cryptic ciphers, expose phishing red flags, and navigate live cybersecurity breaches.
      </p>

      <div class="hero-meta-grid">
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
          <div class="meta-box-label">Registration</div>
          <div class="meta-box-value">₹150 / Team</div>
        </div>
      </div>

      <div style="display:flex; justify-content:center; gap:1rem; flex-wrap:wrap; margin-top:2rem;">
        <?php if ($isLoggedIn): ?>
          <a href="dashboard.php" class="btn btn-primary btn-lg">Go to Team Dashboard →</a>
        <?php else: ?>
          <a href="login.php" class="btn btn-primary btn-lg">Register / Login Team</a>
        <?php endif; ?>
        <a href="leaderboard.php" class="btn btn-secondary btn-lg">View Live Leaderboard</a>
      </div>
    </div>
  </div>

  <!-- Tournament Overview & Rules -->
  <div class="container">
    <div style="text-align:center; margin-bottom:3rem;">
      <h2 style="font-size:2rem; font-weight:800; margin-bottom:0.5rem;">Tournament Progression Flow</h2>
      <p style="color:var(--text-muted);">From foundational cybersecurity triage to high-stakes live breach containment.</p>
    </div>

    <div class="rounds-grid">
      <div class="card">
        <div class="round-header">
          <span class="round-number-tag">Round 1</span>
          <span class="status-badge unlocked">All Teams</span>
        </div>
        <h3 class="card-title">MCQ Warmup</h3>
        <p class="card-text">Everyday cybersecurity questions covering password hygiene, public Wi-Fi risks, 2FA mechanics, and safe browsing essentials.</p>
        <div class="round-meta-row">
          <span>⏱ 10 Mins</span>
          <span>•</span>
          <span>10 Questions (1 pt each)</span>
        </div>
      </div>

      <div class="card">
        <div class="round-header">
          <span class="round-number-tag">Round 2</span>
          <span class="status-badge unlocked">All Teams</span>
        </div>
        <h3 class="card-title">Rapid Fire True/False</h3>
        <p class="card-text">Split-second cybersecurity statements testing intuitive security fundamentals and myth busting.</p>
        <div class="round-meta-row">
          <span>⏱ 10 Mins</span>
          <span>•</span>
          <span>10 Statements (1 pt each)</span>
        </div>
      </div>

      <div class="card">
        <div class="round-header">
          <span class="round-number-tag">Round 3</span>
          <span class="status-badge unlocked">All Teams</span>
        </div>
        <h3 class="card-title">Spot the Phishing</h3>
        <p class="card-text">Inspect realistic simulated email, SMS, and login pages to identify subtle indicators of social engineering.</p>
        <div class="round-meta-row">
          <span>⏱ 15 Mins</span>
          <span>•</span>
          <span>10 Samples (2 pts each)</span>
        </div>
      </div>

      <div class="card">
        <div class="round-header">
          <span class="round-number-tag">Round 4</span>
          <span class="status-badge unlocked">All Teams</span>
        </div>
        <h3 class="card-title">Decode the Message</h3>
        <p class="card-text">Decrypt hidden messages scrambled with classic cryptographic ciphers: Caesar shift, ROT13, and Atbash.</p>
        <div class="round-meta-row">
          <span>⏱ 15 Mins</span>
          <span>•</span>
          <span>10 Ciphers (3 pts each)</span>
        </div>
      </div>

      <div class="card">
        <div class="round-header">
          <span class="round-number-tag">Round 5</span>
          <span class="status-badge" style="color:var(--accent-purple); border-color:var(--accent-purple);">Jury Pitch</span>
        </div>
        <h3 class="card-title">Case Study Pitch</h3>
        <p class="card-text">2 minutes preparation, 2 minutes live verbal pitch to the jury. Defend against rogue QR codes, stolen devices, and credential spills.</p>
        <div class="round-meta-row">
          <span>⏱ 15 Mins</span>
          <span>•</span>
          <span>10 Pts Max (Top 5 Qualify)</span>
        </div>
      </div>

      <div class="card special-round">
        <div class="round-header">
          <span class="round-number-tag">Round 6</span>
          <span class="status-badge" style="color:var(--accent-green); border-color:var(--accent-green);">Top 5 Semifinal</span>
        </div>
        <h3 class="card-title">Semifinal Challenge</h3>
        <p class="card-text">Part A: Speed Round (10 pts) + Part B: Explain to Jury (15 pts). Only the Top 5 cumulative scorers participate.</p>
        <div class="round-meta-row">
          <span>⏱ 20 Mins</span>
          <span>•</span>
          <span>25 Pts (Top 3 Qualify)</span>
        </div>
      </div>

      <div class="card special-round">
        <div class="round-header">
          <span class="round-number-tag">Round 7</span>
          <span class="status-badge" style="color:#ffd700; border-color:#ffd700;">Top 3 Final</span>
        </div>
        <h3 class="card-title">The Live Breach Room</h3>
        <p class="card-text">Live stage unfolding multi-stage incident simulation: 'The College Portal Under Attack' + decisive tiebreaker briefing.</p>
        <div class="round-meta-row">
          <span>⏱ 15 Mins</span>
          <span>•</span>
          <span>26 Pts Max (Podium Ranking)</span>
        </div>
      </div>
    </div>

    <!-- Prize & Coordinators Card -->
    <div class="card" style="margin-top:3rem; background: linear-gradient(135deg, rgba(16,24,40,0.9), rgba(20,30,50,0.8));">
      <div style="display:grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap:2rem; align-items:center;">
        <div>
          <h3 style="font-size:1.5rem; font-weight:800; color:#ffd700; margin-bottom:0.75rem;">🏆 Prize Pool & Honor</h3>
          <ul style="list-style:none; line-height:2.2; font-family:var(--font-mono); font-size:1rem;">
            <li>🥇 1st Place: <strong>₹3,000 Cash + Certificate of Excellence</strong></li>
            <li>🥈 2nd Place: <strong>₹2,000 Cash + Certificate of Merit</strong></li>
            <li>🥉 3rd Place: <strong>₹1,000 Cash + Certificate of Merit</strong></li>
            <li style="color:var(--text-muted); font-size:0.85rem;">Certificates for all participating teams.</li>
          </ul>
        </div>
        <div>
          <h4 style="font-size:1.1rem; font-weight:700; color:var(--accent-cyan); margin-bottom:0.5rem;">Event Coordinators</h4>
          <p style="font-size:0.95rem; margin-bottom:0.5rem;"><strong>Student Coordinators:</strong> Puneeth S, Prajwal BU</p>
          <p style="font-size:0.95rem; margin-bottom:0.5rem;"><strong>Venue:</strong> LSL04 • <strong>Time:</strong> 2:00PM – 4:00PM</p>
          <p style="font-size:0.85rem; color:var(--text-muted);">For questions or scoring inquiries, consult student coordinators at the registration desk.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer class="footer">
    <div><strong>HackIQ 2026</strong> — Think Fast. Hack Smart.</div>
    <div class="footer-meta">Powered by HackIQ Secured Tournament Portal • Designed for LSL04 Campus Battle</div>
  </footer>

</body>
</html>
