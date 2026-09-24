<?php
// admin.php - HackIQ Master Admin Control Panel & Jury Evaluation Portal
require_once __DIR__ . '/includes/auth.php';
$isAdmin = isAdminLoggedIn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Coordinator Control Center — HackIQ</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    .admin-tab-nav {
      display: flex;
      gap: 0.5rem;
      border-bottom: 1px solid var(--border-color);
      margin-bottom: 1.5rem;
      overflow-x: auto;
    }
    .admin-tab-btn {
      padding: 0.75rem 1.25rem;
      background: transparent;
      border: none;
      color: var(--text-muted);
      font-weight: 600;
      cursor: pointer;
      border-bottom: 2px solid transparent;
      font-size: 0.95rem;
      white-space: nowrap;
    }
    .admin-tab-btn.active {
      color: var(--accent-cyan);
      border-bottom-color: var(--accent-cyan);
    }
    .team-accordion {
      background: var(--bg-card);
      border: 1px solid var(--border-color);
      border-radius: var(--radius-md);
      margin-bottom: 0.75rem;
      overflow: hidden;
    }
    .team-accordion-header {
      padding: 1rem 1.25rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      cursor: pointer;
      user-select: none;
    }
    .team-accordion-header:hover {
      background: rgba(0, 212, 255, 0.05);
    }
    .team-accordion-body {
      padding: 1.25rem;
      border-top: 1px solid var(--border-color);
      display: none;
      background: var(--bg-tertiary);
    }
    .q-review-item {
      padding: 0.85rem;
      border-radius: var(--radius-sm);
      margin-bottom: 0.75rem;
      border: 1px solid var(--border-color);
      background: var(--bg-card);
    }
    .q-review-item.correct {
      border-left: 4px solid var(--accent-green);
    }
    .q-review-item.wrong {
      border-left: 4px solid var(--accent-red);
    }
  </style>
</head>
<body>

  <!-- Navigation Bar -->
  <nav class="navbar">
    <a href="admin.php" class="nav-brand">
      <div class="brand-icon" style="background:linear-gradient(135deg, var(--accent-purple), var(--accent-cyan));">AD</div>
      <div>
        <div class="brand-title">HackIQ Control</div>
        <span class="brand-tag">Coordinator & Jury Panel</span>
      </div>
    </a>
    <div class="nav-actions">
      <a href="leaderboard.php" target="_blank" class="btn btn-secondary btn-sm">Open Live Leaderboard ↗</a>
      <?php if ($isAdmin): ?>
        <button class="btn btn-danger btn-sm" onclick="adminLogout()">Logout Admin</button>
      <?php endif; ?>
    </div>
  </nav>

  <div class="container">
    <?php if (!$isAdmin): ?>
      <!-- Admin Login Gate -->
      <div class="auth-wrapper" style="margin-top:4rem;">
        <div class="card">
          <div style="text-align:center; margin-bottom:1.5rem;">
            <div style="font-size:2.5rem; margin-bottom:0.5rem;">🛡️</div>
            <h2 style="font-size:1.5rem; font-weight:800;">Coordinators & Jury Access</h2>
            <p style="color:var(--text-muted); font-size:0.85rem;">Authorized student coordinators & jury members only.</p>
          </div>

          <div id="loginAlert" style="display:none; padding:0.75rem; margin-bottom:1rem; border-radius:var(--radius-sm); font-size:0.85rem; font-family:var(--font-mono); background:rgba(255,51,102,0.15); border:1px solid var(--accent-red); color:#ff99aa;"></div>

          <form onsubmit="handleAdminLogin(event)">
            <div class="form-group">
              <label class="form-label">Username</label>
              <input type="text" id="adminUser" class="form-input" placeholder="admin" required autofocus>
            </div>
            <div class="form-group">
              <label class="form-label">Password</label>
              <input type="password" id="adminPass" class="form-input" placeholder="••••••••" required>
            </div>
            <button type="submit" class="btn btn-cyan btn-block btn-lg" style="margin-top:1.25rem;">
              Unlock Master Console
            </button>
          </form>
          <div style="margin-top:1rem; text-align:center; font-size:0.75rem; color:var(--text-dim); font-family:var(--font-mono);">
            Default: admin / admin123
          </div>
        </div>
      </div>
    <?php else: ?>
      <!-- Master Control Console -->
      <div class="admin-metrics-grid" id="metricsGrid">
        <div class="metric-card">
          <div>
            <div style="font-size:0.8rem; color:var(--text-muted); font-family:var(--font-mono);">TEAMS REGISTERED</div>
            <div class="metric-number" id="mTotalTeams">0</div>
          </div>
          <div style="font-size:2rem;">👥</div>
        </div>
        <div class="metric-card">
          <div>
            <div style="font-size:0.8rem; color:var(--text-muted); font-family:var(--font-mono);">ACTIVE ROUNDS</div>
            <div class="metric-number" id="mActiveRounds" style="color:var(--accent-green);">0 / 7</div>
          </div>
          <div style="font-size:2rem;">⚡</div>
        </div>
        <div class="metric-card">
          <div>
            <div style="font-size:0.8rem; color:var(--text-muted); font-family:var(--font-mono);">ROUND SUBMISSIONS</div>
            <div class="metric-number" id="mTotalSubs">0</div>
          </div>
          <div style="font-size:2rem;">📝</div>
        </div>
        <div class="metric-card">
          <div>
            <div style="font-size:0.8rem; color:var(--text-muted); font-family:var(--font-mono);">PROCTORING STRIKES</div>
            <div class="metric-number" id="mViolations" style="color:var(--accent-red);">0</div>
          </div>
          <div style="font-size:2rem;">⚠️</div>
        </div>
      </div>

      <!-- Navigation Tabs -->
      <div class="admin-tab-nav">
        <button class="admin-tab-btn active" onclick="switchAdminTab('rounds')">⚙️ Rounds & Passwords</button>
        <button class="admin-tab-btn" onclick="switchAdminTab('teams')">📋 Teams & Answer Sheets</button>
        <button class="admin-tab-btn" onclick="switchAdminTab('jury')">⚖️ Live Jury Scoring Portal</button>
        <button class="admin-tab-btn" onclick="switchAdminTab('violations')">🚨 Violation Logs</button>
      </div>

      <!-- TAB 1: Rounds Management -->
      <div id="tabContentRounds">
        <div class="card" style="margin-bottom:1.5rem;">
          <h3 style="margin-bottom:1rem; font-weight:700;">Tournament Rounds Control & Passwords</h3>
          <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:1.5rem;">
            Control round visibility, change passwords, and configure time limits in real-time.
          </p>

          <div style="overflow-x:auto;">
            <table class="leaderboard-table" style="font-size:0.9rem;">
              <thead>
                <tr>
                  <th>Round</th>
                  <th>Name</th>
                  <th>Current Password Hint</th>
                  <th>Time Limit</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="adminRoundsTable">
                <tr><td colspan="6" style="text-align:center; padding:2rem;">Loading rounds data...</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- TAB 2: Teams & Answer Sheets -->
      <div id="tabContentTeams" style="display:none;">
        <div class="card" style="margin-bottom:1.5rem;">
          <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:1rem;">
            <div>
              <h3 style="font-weight:700;">Team Performance & Answer Sheets</h3>
              <p style="color:var(--text-muted); font-size:0.9rem;">Click any team to inspect their question-by-question responses, scores, and resets.</p>
            </div>
            <input type="text" id="adminTeamSearch" class="form-input" placeholder="Filter team name..." style="width:240px;" oninput="renderAdminTeams()">
          </div>

          <div id="adminTeamsList">
            <!-- Accordion items loaded dynamically -->
          </div>
        </div>
      </div>

      <!-- TAB 3: Live Jury Scoring Portal -->
      <div id="tabContentJury" style="display:none;">
        <div class="card">
          <h3 style="font-weight:700; margin-bottom:0.5rem;">Live Jury & Coordinator Scoring Console</h3>
          <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:1.5rem;">
            Use this portal during <strong>Round 5 Case Study Pitches (0-10 pts)</strong>, <strong>Round 6 Part B Jury Explain (0-15 pts)</strong>, and <strong>Round 7 Tiebreakers (0-10 pts)</strong>.
          </p>

          <form onsubmit="handleManualScoreSubmit(event)" style="max-width:600px; background:var(--bg-tertiary); padding:1.5rem; border-radius:var(--radius-md); border:1px solid var(--border-color);">
            <div class="form-group">
              <label class="form-label">Select Team</label>
              <select id="juryTeamSelect" class="form-select" required>
                <!-- Populated dynamically -->
              </select>
            </div>

            <div class="form-group">
              <label class="form-label">Target Round</label>
              <select id="juryRoundSelect" class="form-select" required onchange="updateJuryRubricHint()">
                <option value="5">Round 5: Case Study Pitch (Max 10 pts)</option>
                <option value="6">Round 6: Part B Explain to Jury (Max 15 pts)</option>
                <option value="7">Round 7: Stage 5 Incident Tiebreaker (Max 10 pts)</option>
                <option value="1">Round 1 (Manual Override)</option>
                <option value="2">Round 2 (Manual Override)</option>
                <option value="3">Round 3 (Manual Override)</option>
                <option value="4">Round 4 (Manual Override)</option>
              </select>
            </div>

            <div id="juryRubricHint" style="padding:0.6rem 0.85rem; background:rgba(0,212,255,0.08); border-left:3px solid var(--accent-cyan); font-size:0.85rem; margin-bottom:1.25rem;">
              <strong>Round 5 Rubric:</strong> Risk Understanding (0-4 pts) | Solution Practicality (0-4 pts) | Communication (0-2 pts)
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:1rem;">
              <div class="form-group">
                <label class="form-label">Points Awarded</label>
                <input type="number" id="juryPoints" class="form-input" min="0" max="100" placeholder="e.g. 8" required>
              </div>
              <div class="form-group">
                <label class="form-label">Correct Count</label>
                <input type="number" id="juryCorrect" class="form-input" min="0" max="20" value="1">
              </div>
            </div>

            <div class="form-group">
              <label class="form-label">Jury / Coordinator Remarks</label>
              <input type="text" id="juryNotes" class="form-input" placeholder="e.g. Sharp incident containment plan, well communicated">
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:1rem;">
              Commit Jury Score to Database
            </button>
          </form>
        </div>
      </div>

      <!-- TAB 4: Violations Log -->
      <div id="tabContentViolations" style="display:none;">
        <div class="card">
          <h3 style="font-weight:700; margin-bottom:1rem;">Real-Time Proctoring Audit Log</h3>
          <p style="color:var(--text-muted); font-size:0.9rem; margin-bottom:1.5rem;">
            Review recorded proctoring strikes (tab switches, right clicks, shortcut attempts) for all teams.
          </p>

          <div style="overflow-x:auto;">
            <table class="leaderboard-table" style="font-size:0.88rem;">
              <thead>
                <tr>
                  <th>Timestamp</th>
                  <th>Team</th>
                  <th>Round</th>
                  <th>Violation Type</th>
                </tr>
              </thead>
              <tbody id="violationsTableBody">
                <tr><td colspan="4" style="text-align:center; padding:2rem;">No violations logged yet.</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    <?php endif; ?>
  </div>

  <!-- Detailed Team Round Inspector Modal -->
  <div class="modal-overlay" id="sheetModal">
    <div class="modal-content" style="max-width:850px; max-height:85vh; overflow-y:auto;">
      <div class="modal-header">
        <h3 id="sheetModalTitle" style="font-weight:700;">Team Answer Sheet</h3>
        <button class="modal-close" onclick="closeSheetModal()">&times;</button>
      </div>
      <div id="sheetModalContent">
        Loading answer sheet...
      </div>
    </div>
  </div>

  <script src="js/app.js"></script>
  <script>
    let globalAdminData = null;

    async function handleAdminLogin(e) {
      e.preventDefault();
      const username = document.getElementById('adminUser').value.trim();
      const password = document.getElementById('adminPass').value.trim();
      const alertBox = document.getElementById('loginAlert');

      const res = await App.fetch('api/admin_login.php', {
        method: 'POST',
        body: { username, password }
      });

      if (res.success) {
        window.location.reload();
      } else {
        alertBox.innerText = res.error || 'Authentication rejected.';
        alertBox.style.display = 'block';
      }
    }

    async function adminLogout() {
      await App.fetch('api/logout.php');
      window.location.reload();
    }

    function switchAdminTab(tab) {
      document.querySelectorAll('.admin-tab-btn').forEach(b => b.classList.remove('active'));
      event.target.classList.add('active');

      document.getElementById('tabContentRounds').style.display = tab === 'rounds' ? 'block' : 'none';
      document.getElementById('tabContentTeams').style.display = tab === 'teams' ? 'block' : 'none';
      document.getElementById('tabContentJury').style.display = tab === 'jury' ? 'block' : 'none';
      document.getElementById('tabContentViolations').style.display = tab === 'violations' ? 'block' : 'none';
    }

    <?php if ($isAdmin): ?>
    document.addEventListener('DOMContentLoaded', () => {
      loadAdminDashboard();
    });

    async function loadAdminDashboard() {
      const res = await App.fetch('api/admin_actions.php', {
        method: 'POST',
        body: { action: 'get_admin_data' }
      });

      if (!res.success) return;
      globalAdminData = res;

      // Update metrics
      document.getElementById('mTotalTeams').innerText = res.teams.length;
      let activeCount = res.rounds.filter(r => r.is_active == 1).length;
      document.getElementById('mActiveRounds').innerText = `${activeCount} / 7`;

      let totalSubs = 0;
      res.teams.forEach(t => { totalSubs += parseInt(t.submitted_rounds_count || 0); });
      document.getElementById('mTotalSubs').innerText = totalSubs;
      document.getElementById('mViolations').innerText = res.violations.length;

      // Render rounds table
      renderAdminRounds(res.rounds);

      // Render teams
      renderAdminTeams();

      // Populate jury team select
      const jurySelect = document.getElementById('juryTeamSelect');
      jurySelect.innerHTML = '<option value="">-- Choose Team --</option>';
      res.teams.forEach(t => {
        jurySelect.innerHTML += `<option value="${t.id}">${t.team_name} (Total: ${t.grand_total} pts)</option>`;
      });

      // Render violations table
      const vBody = document.getElementById('violationsTableBody');
      vBody.innerHTML = '';
      if (res.violations.length === 0) {
        vBody.innerHTML = '<tr><td colspan="4" style="text-align:center; padding:2rem; color:var(--text-muted);">No violations logged.</td></tr>';
      } else {
        res.violations.forEach(v => {
          vBody.innerHTML += `
            <tr>
              <td style="font-family:var(--font-mono); font-size:0.8rem;">${v.occurred_at}</td>
              <td><strong>${v.team_name}</strong></td>
              <td>${v.round_name}</td>
              <td><span class="status-badge flagged">${v.violation_type}</span></td>
            </tr>
          `;
        });
      }
    }

    function renderAdminRounds(rounds) {
      const tbody = document.getElementById('adminRoundsTable');
      tbody.innerHTML = '';

      rounds.forEach(r => {
        tbody.innerHTML += `
          <tr>
            <td style="font-family:var(--font-mono); font-weight:700;">Round ${r.round_number}</td>
            <td><strong>${r.round_name}</strong></td>
            <td>
              <code style="color:var(--accent-green); background:rgba(0,255,136,0.1); padding:2px 6px; border-radius:4px;">
                ${r.plain_password_hint || '******'}
              </code>
            </td>
            <td>${r.time_limit_minutes} Mins</td>
            <td>
              <span class="status-badge ${r.is_active == 1 ? 'completed' : 'locked'}">
                ${r.is_active == 1 ? 'Active' : 'Locked'}
              </span>
            </td>
            <td>
              <div style="display:flex; gap:0.5rem; flex-wrap:wrap;">
                <button class="btn btn-sm ${r.is_active == 1 ? 'btn-secondary' : 'btn-primary'}" onclick="toggleRoundStatus(${r.id}, ${r.is_active == 1 ? 0 : 1})">
                  ${r.is_active == 1 ? 'Lock' : 'Unlock'}
                </button>
                <button class="btn btn-sm btn-secondary" onclick="promptChangePassword(${r.id}, '${r.round_name.replace(/'/g, "\\'")}')">
                  Passcode
                </button>
                <button class="btn btn-sm btn-secondary" onclick="promptChangeTime(${r.id}, ${r.time_limit_minutes})">
                  Timer
                </button>
              </div>
            </td>
          </tr>
        `;
      });
    }

    function renderAdminTeams() {
      const container = document.getElementById('adminTeamsList');
      if (!container || !globalAdminData) return;

      const query = (document.getElementById('adminTeamSearch')?.value || '').toLowerCase();
      container.innerHTML = '';

      globalAdminData.teams.forEach(t => {
        if (query && !t.team_name.toLowerCase().includes(query)) return;

        const div = document.createElement('div');
        div.className = 'team-accordion';
        div.innerHTML = `
          <div class="team-accordion-header" onclick="toggleTeamAccordion(${t.id})">
            <div>
              <strong style="font-size:1.05rem;">${t.team_name}</strong>
              <span style="font-size:0.8rem; color:var(--text-muted); margin-left:0.5rem;">[${t.members_info || '4 Members'}]</span>
            </div>
            <div style="display:flex; align-items:center; gap:1.25rem;">
              <span style="font-family:var(--font-mono); font-size:0.85rem; color:var(--text-muted);">${t.submitted_rounds_count} / 7 Rounds</span>
              <span style="font-family:var(--font-mono); font-weight:800; font-size:1.15rem; color:var(--accent-green);">${t.grand_total} pts</span>
              <span>▼</span>
            </div>
          </div>
          <div class="team-accordion-body" id="teamBody_${t.id}">
            <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(130px, 1fr)); gap:0.75rem; margin-bottom:1rem;">
              ${[1,2,3,4,5,6,7].map(rNum => {
                const sc = globalAdminData.scores_matrix[t.id] && globalAdminData.scores_matrix[t.id][rNum];
                return `
                  <div style="background:var(--bg-card); border:1px solid var(--border-color); border-radius:var(--radius-sm); padding:0.6rem; text-align:center;">
                    <div style="font-size:0.7rem; font-family:var(--font-mono); color:var(--text-muted);">ROUND ${rNum}</div>
                    <div style="font-size:1rem; font-weight:700; color:${sc ? 'var(--accent-green)' : 'var(--text-dim)'}; margin:0.25rem 0;">
                      ${sc ? sc.total_points + ' pts' : 'Pending'}
                    </div>
                    ${sc ? `
                      <div style="display:flex; gap:0.25rem; justify-content:center; margin-top:0.4rem;">
                        <button class="btn btn-sm btn-secondary" style="font-size:0.65rem; padding:2px 5px;" onclick="viewTeamRoundSheet(${t.id}, ${rNum})">Review</button>
                        <button class="btn btn-sm btn-danger" style="font-size:0.65rem; padding:2px 5px;" onclick="resetTeamRoundAttempt(${t.id}, ${rNum}, '${t.team_name.replace(/'/g, "\\'")}')">Reset</button>
                      </div>
                    ` : ''}
                  </div>
                `;
              }).join('')}
            </div>
          </div>
        `;
        container.appendChild(div);
      });
    }

    function toggleTeamAccordion(teamId) {
      const body = document.getElementById(`teamBody_${teamId}`);
      if (body) {
        body.style.display = body.style.display === 'block' ? 'none' : 'block';
      }
    }

    async function toggleRoundStatus(roundId, newStatus) {
      const res = await App.fetch('api/admin_actions.php', {
        method: 'POST',
        body: { action: 'toggle_round', round_id: roundId, is_active: newStatus }
      });
      if (res.success) loadAdminDashboard();
    }

    async function promptChangePassword(roundId, roundName) {
      const pass = prompt(`Enter new password for ${roundName}:`);
      if (!pass || !pass.trim()) return;
      const res = await App.fetch('api/admin_actions.php', {
        method: 'POST',
        body: { action: 'update_round_password', round_id: roundId, password: pass.trim() }
      });
      if (res.success) {
        alert(res.message);
        loadAdminDashboard();
      }
    }

    async function promptChangeTime(roundId, currentMinutes) {
      const mins = prompt(`Enter new time limit in minutes:`, currentMinutes);
      if (!mins || isNaN(mins)) return;
      const res = await App.fetch('api/admin_actions.php', {
        method: 'POST',
        body: { action: 'update_round_time', round_id: roundId, time_limit_minutes: parseInt(mins) }
      });
      if (res.success) loadAdminDashboard();
    }

    function updateJuryRubricHint() {
      const round = document.getElementById('juryRoundSelect').value;
      const hint = document.getElementById('juryRubricHint');
      if (round === '5') {
        hint.innerHTML = '<strong>Round 5 Rubric (Max 10 pts):</strong> Risk Understanding (0-4 pts) | Solution Practicality (0-4 pts) | Communication (0-2 pts)';
      } else if (round === '6') {
        hint.innerHTML = '<strong>Round 6 Part B Rubric (Max 15 pts):</strong> Suspicion Analysis (0-5 pts) | Risk Explanation (0-5 pts) | Action Plan (0-5 pts)';
      } else if (round === '7') {
        hint.innerHTML = '<strong>Round 7 Stage 5 Tiebreaker (Max 10 pts):</strong> Single Sentence Lesson insight, clarity, and structural maturity';
      } else {
        hint.innerHTML = '<strong>Manual Override:</strong> Manually adjusts total score recorded for this round.';
      }
    }

    async function handleManualScoreSubmit(e) {
      e.preventDefault();
      const team_id = document.getElementById('juryTeamSelect').value;
      const round_id = document.getElementById('juryRoundSelect').value;
      const points = document.getElementById('juryPoints').value;
      const correct = document.getElementById('juryCorrect').value;
      const notes = document.getElementById('juryNotes').value;

      if (!team_id) {
        alert('Please choose a team.');
        return;
      }

      const res = await App.fetch('api/admin_actions.php', {
        method: 'POST',
        body: {
          action: 'save_manual_score',
          team_id, round_id, points, correct, notes
        }
      });

      if (res.success) {
        alert('Jury evaluation saved successfully!');
        loadAdminDashboard();
      } else {
        alert(res.error || 'Failed to save score.');
      }
    }

    async function resetTeamRoundAttempt(teamId, roundId, teamName) {
      if (!confirm(`Are you sure you want to reset Round ${roundId} for team "${teamName}"? This clears their score and strikes, allowing them to re-enter.`)) {
        return;
      }

      const res = await App.fetch('api/admin_actions.php', {
        method: 'POST',
        body: { action: 'reset_team_round', team_id: teamId, round_id: roundId }
      });

      if (res.success) {
        alert(res.message);
        loadAdminDashboard();
      } else {
        alert(res.error || 'Reset failed');
      }
    }

    async function viewTeamRoundSheet(teamId, roundId) {
      const modal = document.getElementById('sheetModal');
      const content = document.getElementById('sheetModalContent');
      modal.classList.add('active');
      content.innerHTML = 'Loading answer sheet...';

      const res = await App.fetch(`api/get_team_answers.php?team_id=${teamId}&round_id=${roundId}`);
      if (!res.success) {
        content.innerHTML = `<div style="color:var(--accent-red);">${res.error}</div>`;
        return;
      }

      document.getElementById('sheetModalTitle').innerText = `${res.team.team_name} — ${res.round.round_name}`;

      let html = `
        <div style="background:var(--bg-tertiary); padding:1rem; border-radius:var(--radius-sm); margin-bottom:1.25rem; display:flex; justify-content:space-between; align-items:center;">
          <div>
            <strong>Score: ${res.score ? res.score.total_points : 0} pts</strong> (${res.score ? res.score.total_correct : 0} Correct)
            ${res.score && res.score.flagged ? '<span class="status-badge flagged" style="margin-left:0.5rem;">Flagged for Violations</span>' : ''}
          </div>
          <div>Submitted: ${res.score ? res.score.submitted_at : 'Not yet'}</div>
        </div>
      `;

      if (res.violations && res.violations.length > 0) {
        html += `<h4 style="color:var(--accent-red); margin-bottom:0.5rem;">Violations Logged (${res.violations.length}):</h4><ul style="margin-bottom:1.5rem; font-size:0.85rem; font-family:var(--font-mono); color:#ff99aa;">`;
        res.violations.forEach(v => {
          html += `<li>[${v.occurred_at}] ${v.violation_type}</li>`;
        });
        html += `</ul>`;
      }

      html += `<h4 style="margin-bottom:0.75rem;">Question Breakdown:</h4>`;
      res.questions.forEach((q, idx) => {
        const isCor = q.is_correct == 1;
        html += `
          <div class="q-review-item ${isCor ? 'correct' : 'wrong'}">
            <div style="font-size:0.8rem; color:var(--text-muted); font-family:var(--font-mono);">Question ${idx + 1} • [${q.points} pt]</div>
            <div style="font-weight:600; margin:0.35rem 0;">${q.question_text.replace(/\n/g, '<br>')}</div>
            <div style="font-size:0.9rem; margin-top:0.4rem;">
              <span>Team Selected: <strong style="color:${isCor ? 'var(--accent-green)' : 'var(--accent-red)'}">${q.selected_answer || '(Unanswered)'}</strong></span>
              <span style="margin-left:1.5rem; color:var(--text-muted);">Correct Answer: <strong style="color:var(--accent-green);">${q.correct_answer}</strong></span>
              <span style="float:right; font-family:var(--font-mono); font-weight:700;">+${q.points_awarded || 0} pts</span>
            </div>
          </div>
        `;
      });

      content.innerHTML = html;
    }

    function closeSheetModal() {
      document.getElementById('sheetModal').classList.remove('active');
    }
    <?php endif; ?>
  </script>
</body>
</html>
