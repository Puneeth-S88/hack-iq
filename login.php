<?php
// login.php - Team Authentication (Login & Register)
require_once __DIR__ . '/includes/auth.php';

if (isTeamLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Team Access — HackIQ</title>
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
        <span class="brand-tag">Terminal Auth</span>
      </div>
    </a>
    <div class="nav-actions">
      <a href="index.php" class="nav-link">← Return Home</a>
      <a href="leaderboard.php" class="nav-link">Live Leaderboard</a>
    </div>
  </nav>

  <div class="container">
    <div class="auth-wrapper">
      <div class="card">
        <div class="auth-tabs">
          <div class="auth-tab active" id="tabLogin" onclick="switchTab('login')">Team Login</div>
          <div class="auth-tab" id="tabRegister" onclick="switchTab('register')">New Team Register</div>
        </div>

        <div id="authAlert" style="display:none; padding:0.75rem 1rem; border-radius:var(--radius-sm); margin-bottom:1.25rem; font-size:0.9rem; font-family:var(--font-mono);"></div>

        <!-- Login Form -->
        <form id="loginForm" onsubmit="handleLogin(event)">
          <div class="form-group">
            <label class="form-label" for="loginTeamName">Team Name</label>
            <input type="text" id="loginTeamName" class="form-input" placeholder="e.g. CyberKnights" required autofocus>
          </div>
          <div class="form-group">
            <label class="form-label" for="loginPassword">Team Password</label>
            <input type="password" id="loginPassword" class="form-input" placeholder="••••••••" required>
          </div>
          <button type="submit" class="btn btn-primary btn-block btn-lg" id="loginBtn" style="margin-top:1.5rem;">
            Authenticate & Enter Dashboard
          </button>
        </form>

        <!-- Register Form -->
        <form id="registerForm" onsubmit="handleRegister(event)" style="display:none;">
          <div class="form-group">
            <label class="form-label" for="regTeamName">Team Name (Unique)</label>
            <input type="text" id="regTeamName" class="form-input" placeholder="e.g. ByteBrigade" required>
            <div class="form-help">Must be unique across all 15 competing teams.</div>
          </div>
          <div class="form-group">
            <label class="form-label" for="regMembers">Team Members (4 Members)</label>
            <input type="text" id="regMembers" class="form-input" placeholder="Names: Member 1, Member 2, Member 3, Member 4" required>
            <div class="form-help">Each team must consist of 4 participants.</div>
          </div>
          <div class="form-group">
            <label class="form-label" for="regPassword">Team Password</label>
            <input type="password" id="regPassword" class="form-input" placeholder="Create strong team password" required minlength="4">
          </div>
          <div class="form-group">
            <label class="form-label" for="regConfirmPassword">Confirm Password</label>
            <input type="password" id="regConfirmPassword" class="form-input" placeholder="Re-type password" required minlength="4">
          </div>
          <button type="submit" class="btn btn-primary btn-block btn-lg" id="regBtn" style="margin-top:1.5rem;">
            Register Team & Start
          </button>
        </form>
      </div>

      <div style="text-align:center; margin-top:1.5rem; font-size:0.85rem; color:var(--text-muted);">
        Event Venue: <strong>LSL04</strong> • Fee: <strong>₹150 / Team</strong><br>
        Organizers: Puneeth S & Prajwal BU
      </div>
    </div>
  </div>

  <script src="js/app.js"></script>
  <script>
    function switchTab(tab) {
      const alertBox = document.getElementById('authAlert');
      alertBox.style.display = 'none';

      if (tab === 'login') {
        document.getElementById('tabLogin').classList.add('active');
        document.getElementById('tabRegister').classList.remove('active');
        document.getElementById('loginForm').style.display = 'block';
        document.getElementById('registerForm').style.display = 'none';
        document.getElementById('loginTeamName').focus();
      } else {
        document.getElementById('tabLogin').classList.remove('active');
        document.getElementById('tabRegister').classList.add('active');
        document.getElementById('loginForm').style.display = 'none';
        document.getElementById('registerForm').style.display = 'block';
        document.getElementById('regTeamName').focus();
      }
    }

    function showAlert(msg, isSuccess = false) {
      const alertBox = document.getElementById('authAlert');
      alertBox.innerText = msg;
      alertBox.style.display = 'block';
      if (isSuccess) {
        alertBox.style.background = 'rgba(0, 255, 136, 0.12)';
        alertBox.style.color = 'var(--accent-green)';
        alertBox.style.border = '1px solid var(--accent-green)';
      } else {
        alertBox.style.background = 'rgba(255, 51, 102, 0.12)';
        alertBox.style.color = '#ff99aa';
        alertBox.style.border = '1px solid var(--accent-red)';
      }
    }

    async function handleLogin(e) {
      e.preventDefault();
      const team_name = document.getElementById('loginTeamName').value.trim();
      const password = document.getElementById('loginPassword').value.trim();
      const btn = document.getElementById('loginBtn');

      btn.disabled = true;
      btn.innerText = 'Authenticating...';

      const res = await App.fetch('api/login.php', {
        method: 'POST',
        body: { team_name, password }
      });

      btn.disabled = false;
      btn.innerText = 'Authenticate & Enter Dashboard';

      if (res.success) {
        showAlert('Authentication verified! Redirecting to Arena...', true);
        setTimeout(() => { window.location.href = 'dashboard.php'; }, 700);
      } else {
        showAlert(res.error || 'Login failed.');
      }
    }

    async function handleRegister(e) {
      e.preventDefault();
      const team_name = document.getElementById('regTeamName').value.trim();
      const members_info = document.getElementById('regMembers').value.trim();
      const password = document.getElementById('regPassword').value.trim();
      const confirm_password = document.getElementById('regConfirmPassword').value.trim();
      const btn = document.getElementById('regBtn');

      if (password !== confirm_password) {
        showAlert('Passwords do not match.');
        return;
      }

      btn.disabled = true;
      btn.innerText = 'Registering Team...';

      const res = await App.fetch('api/register.php', {
        method: 'POST',
        body: { team_name, members_info, password, confirm_password }
      });

      btn.disabled = false;
      btn.innerText = 'Register Team & Start';

      if (res.success) {
        showAlert('Team successfully registered! Redirecting to Arena...', true);
        setTimeout(() => { window.location.href = 'dashboard.php'; }, 700);
      } else {
        showAlert(res.error || 'Registration failed.');
      }
    }
  </script>
</body>
</html>
