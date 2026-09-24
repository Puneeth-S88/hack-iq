// js/app.js - HackIQ Frontend Logic, Proctoring Engine & Real-Time Sync

// Global utility helper
const App = {
  async fetch(url, options = {}) {
    options.headers = options.headers || {};
    if (options.body && typeof options.body === 'object' && !(options.body instanceof FormData)) {
      options.headers['Content-Type'] = 'application/json';
      options.body = JSON.stringify(options.body);
    }
    const res = await fetch(url, options);
    const data = await res.json().catch(() => ({ success: false, error: 'Invalid server response' }));
    return data;
  },

  showToast(msg, isError = true) {
    let toast = document.getElementById('appToast');
    if (!toast) {
      toast = document.createElement('div');
      toast.id = 'appToast';
      toast.className = 'proctoring-toast';
      document.body.appendChild(toast);
    }
    toast.innerText = msg;
    toast.style.borderColor = isError ? 'var(--accent-red)' : 'var(--accent-green)';
    toast.style.color = isError ? '#ffb3c1' : 'var(--accent-green)';
    toast.style.display = 'block';
    clearTimeout(this._toastTimeout);
    this._toastTimeout = setTimeout(() => {
      toast.style.display = 'none';
    }, 3500);
  }
};

// ======================== DASHBOARD MODULE ========================
const Dashboard = {
  selectedRoundId: null,

  async init() {
    await this.loadDashboardData();
  },

  async loadDashboardData() {
    const container = document.getElementById('roundsContainer');
    if (!container) return;

    const data = await App.fetch('api/get_dashboard.php');
    if (!data.success) {
      if (data.error && data.error.includes('Unauthorized')) {
        window.location.href = 'login.php';
      }
      return;
    }

    // Update team stats
    const standing = data.standing;
    const team = data.team;

    const nameEl = document.getElementById('dashTeamName');
    if (nameEl) nameEl.innerText = team.team_name;

    const scoreEl = document.getElementById('dashTeamScore');
    if (scoreEl) scoreEl.innerText = standing.total_points + ' pts';

    const rankEl = document.getElementById('dashTeamRank');
    if (rankEl) rankEl.innerText = '#' + standing.rank;

    // Render Rounds
    container.innerHTML = '';
    data.rounds.forEach(r => {
      const card = document.createElement('div');
      card.className = `card round-card ${!r.is_eligible || !r.is_active ? 'locked' : ''} ${r.is_completed ? 'completed' : ''} ${r.round_number >= 6 ? 'special-round' : ''}`;
      
      let badgeHtml = '';
      if (!r.is_active) {
        badgeHtml = `<span class="status-badge locked">🔒 Inactive</span>`;
      } else if (!r.is_eligible) {
        badgeHtml = `<span class="status-badge locked">🔒 ${r.round_number === 6 ? 'Top 5 Only' : 'Top 3 Only'}</span>`;
      } else if (r.is_completed) {
        badgeHtml = `<span class="status-badge completed">✔ Completed (${r.score} pts)</span>`;
      } else if (r.is_unlocked) {
        badgeHtml = `<span class="status-badge unlocked">🔓 Unlocked</span>`;
      } else {
        badgeHtml = `<span class="status-badge locked">🔑 Passcode Required</span>`;
      }

      card.innerHTML = `
        <div>
          <div class="round-header">
            <span class="round-number-tag">Round ${r.round_number}</span>
            ${badgeHtml}
          </div>
          <h3 class="card-title">${r.round_name}</h3>
          <p class="card-text">${r.description}</p>
          <div class="round-meta-row">
            <span class="round-meta-item">⏱ ${r.time_limit_minutes} Mins</span>
            <span class="round-meta-item">🛡 Max Strikes: ${r.max_violations}</span>
            ${r.is_completed && r.flagged ? `<span class="round-meta-item" style="color:var(--accent-red);">⚠️ Violation Flagged</span>` : ''}
          </div>
        </div>
        <div style="margin-top: 1rem;">
          ${r.is_completed 
            ? `<button class="btn btn-secondary btn-block" onclick="Dashboard.viewCompletedRound(${r.round_number}, ${r.score})">View Round Summary</button>`
            : (!r.is_eligible || !r.is_active)
              ? `<button class="btn btn-secondary btn-block" disabled title="${r.lock_reason || 'Locked by admin'}">Locked</button>`
              : `<button class="btn btn-primary btn-block" onclick="Dashboard.openPasswordModal(${r.id}, ${r.round_number}, '${r.round_name.replace(/'/g, "\\'")}', ${r.is_unlocked})">
                  ${r.is_unlocked ? 'Enter Round' : 'Unlock & Start'}
                </button>`
          }
        </div>
      `;
      container.appendChild(card);
    });
  },

  openPasswordModal(roundId, roundNum, roundName, isUnlocked) {
    if (isUnlocked) {
      window.location.href = `round.php?round=${roundNum}`;
      return;
    }
    this.selectedRoundId = roundId;
    this.selectedRoundNum = roundNum;
    const modal = document.getElementById('passwordModal');
    document.getElementById('modalRoundTitle').innerText = roundName;
    document.getElementById('roundPasswordInput').value = '';
    document.getElementById('passwordError').style.display = 'none';
    modal.classList.add('active');
    setTimeout(() => document.getElementById('roundPasswordInput').focus(), 100);
  },

  closePasswordModal() {
    const modal = document.getElementById('passwordModal');
    if (modal) modal.classList.remove('active');
  },

  async verifyPassword() {
    const password = document.getElementById('roundPasswordInput').value.trim();
    const errorEl = document.getElementById('passwordError');
    if (!password) {
      errorEl.innerText = 'Please enter the round password.';
      errorEl.style.display = 'block';
      return;
    }

    const res = await App.fetch('api/verify_round_password.php', {
      method: 'POST',
      body: { round_id: this.selectedRoundId, password }
    });

    if (res.success) {
      window.location.href = `round.php?round=${this.selectedRoundNum}`;
    } else {
      errorEl.innerText = res.error || 'Incorrect password.';
      errorEl.style.display = 'block';
    }
  },

  viewCompletedRound(roundNum, score) {
    window.location.href = `round.php?round=${roundNum}&review=1`;
  }
};

// ======================== PROCTORING & ACTIVE ROUND MODULE ========================
const ProctoringEngine = {
  roundId: null,
  roundNumber: null,
  maxStrikes: 3,
  currentStrikes: 0,
  isActive: false,
  timerInterval: null,
  remainingSeconds: 0,
  isReviewMode: false,

  async init(roundNum) {
    this.roundNumber = roundNum;
    const res = await App.fetch(`api/get_round.php?round=${roundNum}`);

    if (!res.success) {
      if (res.requires_password) {
        alert('Password required for this round. Redirecting to dashboard...');
        window.location.href = 'dashboard.php';
        return;
      }
      alert(res.error || 'Unable to access round.');
      window.location.href = 'dashboard.php';
      return;
    }

    this.roundId = res.round.id;
    this.maxStrikes = res.round.max_violations || 3;
    this.currentStrikes = res.current_strikes || 0;
    this.remainingSeconds = (res.round.time_limit_minutes || 15) * 60;
    this.isReviewMode = res.already_submitted;

    this.renderQuestions(res.questions, res.already_submitted, res.team_answers, res.score);
    this.updateStrikeDisplay();

    if (res.already_submitted) {
      // Review mode: hide start overlay, disable timers and strike listeners
      const overlay = document.getElementById('fullscreenPromptOverlay');
      if (overlay) overlay.style.display = 'none';
      const submitBtn = document.getElementById('submitRoundBtn');
      if (submitBtn) submitBtn.style.display = 'none';
      document.getElementById('roundTimer').innerText = 'COMPLETED';
      return;
    }

    // Active answering mode
    this.setupEventListeners();
  },

  renderQuestions(questions, alreadySubmitted, teamAnswers = {}, scoreData = null) {
    const container = document.getElementById('questionsList');
    if (!container) return;

    container.innerHTML = '';

    if (alreadySubmitted && scoreData) {
      const banner = document.createElement('div');
      banner.className = 'card';
      banner.style.border = '1px solid var(--accent-green)';
      banner.style.marginBottom = '2rem';
      banner.style.background = 'rgba(0, 255, 136, 0.08)';
      banner.innerHTML = `
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:1rem;">
          <div>
            <h3 style="color:var(--accent-green); margin-bottom:0.25rem;">Round Completed ✔</h3>
            <p style="color:var(--text-muted); font-size:0.9rem;">Submitted at ${scoreData.submitted_at} ${scoreData.flagged ? '<span style="color:var(--accent-red);">(Violations Flagged)</span>' : ''}</p>
          </div>
          <div style="font-size:1.5rem; font-weight:800; font-family:var(--font-mono); color:var(--accent-green);">
            Score: ${scoreData.total_points} pts (${scoreData.total_correct} Correct)
          </div>
        </div>
      `;
      container.appendChild(banner);
    }

    questions.forEach((q, idx) => {
      const qCard = document.createElement('div');
      qCard.className = 'question-card question-protected';
      qCard.dataset.qid = q.id;

      let metaHtml = '';
      if (q.question_type === 'phishing' && q.meta && typeof q.meta === 'object') {
        metaHtml = `
          <div class="phishing-preview-card">
            <div class="phishing-header">
              <div class="phishing-row"><span class="phishing-label">SOURCE:</span><span class="phishing-val">${q.meta.from || 'Unknown'}</span></div>
              <div class="phishing-row"><span class="phishing-label">SUBJECT:</span><span class="phishing-val">${q.meta.subject || 'Notice'}</span></div>
            </div>
            <div class="phishing-body">${q.meta.preview || ''}</div>
          </div>
        `;
      } else if (q.question_type === 'cipher') {
        const cipherLabel = typeof q.meta === 'string' ? q.meta : 'CIPHER';
        metaHtml = `
          <div class="cipher-box">
            <div class="cipher-type">${cipherLabel}</div>
            <div class="ciphertext">${q.question_text.split('\n')[1] || q.question_text}</div>
          </div>
        `;
      }

      // Build options
      const options = [
        { key: 'A', text: q.option_a },
        { key: 'B', text: q.option_b },
        { key: 'C', text: q.option_c },
        { key: 'D', text: q.option_d }
      ].filter(o => o.text !== null && o.text !== undefined && o.text !== '');

      const selected = teamAnswers[q.id]?.selected_answer || '';

      let optionsHtml = '<div class="options-list">';
      options.forEach(opt => {
        const isSel = selected === opt.key;
        optionsHtml += `
          <div class="option-item ${isSel ? 'selected' : ''}" data-qid="${q.id}" data-key="${opt.key}" onclick="ProctoringEngine.selectOption(${q.id}, '${opt.key}')">
            <div class="option-key">${opt.key}</div>
            <div class="option-text">${opt.text}</div>
          </div>
        `;
      });
      optionsHtml += '</div>';

      qCard.innerHTML = `
        <div class="question-number">Question ${idx + 1} of ${questions.length} • [${q.points} pt${q.points > 1 ? 's' : ''}]</div>
        <div class="question-prompt">${q.question_type === 'cipher' ? q.question_text.split('\n')[0] : q.question_text}</div>
        ${metaHtml}
        ${optionsHtml}
      `;
      container.appendChild(qCard);
    });
  },

  selectOption(qId, key) {
    if (this.isReviewMode) return;
    const card = document.querySelector(`.question-card[data-qid="${qId}"]`);
    if (!card) return;
    card.querySelectorAll('.option-item').forEach(el => el.classList.remove('selected'));
    const chosen = card.querySelector(`.option-item[data-key="${key}"]`);
    if (chosen) chosen.classList.add('selected');
  },

  startRoundWithFullscreen() {
    const elem = document.documentElement;
    const req = elem.requestFullscreen || elem.webkitRequestFullscreen || elem.msRequestFullscreen;
    if (req) {
      req.call(elem).catch(() => {
        console.warn('Fullscreen request blocked or unsupported');
      });
    }
    const overlay = document.getElementById('fullscreenPromptOverlay');
    if (overlay) overlay.style.display = 'none';

    this.isActive = true;
    this.startTimer();
  },

  startTimer() {
    const timerEl = document.getElementById('roundTimer');
    const update = () => {
      if (this.remainingSeconds <= 0) {
        clearInterval(this.timerInterval);
        this.submitRound(false, true); // Auto-submit when time expires
        return;
      }
      this.remainingSeconds--;
      const mins = Math.floor(this.remainingSeconds / 60);
      const secs = this.remainingSeconds % 60;
      timerEl.innerText = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;

      if (this.remainingSeconds < 60) {
        timerEl.parentElement.className = 'timer-box danger';
      } else if (this.remainingSeconds < 180) {
        timerEl.parentElement.className = 'timer-box warning';
      }
    };
    update();
    this.timerInterval = setInterval(update, 1000);
  },

  setupEventListeners() {
    // 1. Right Click Prevention
    document.addEventListener('contextmenu', (e) => {
      if (!this.isActive) return;
      e.preventDefault();
      App.showToast('Right-click is disabled during the round.');
      this.recordViolation('right_click');
    });

    // 2. Copy/Cut/Paste Prevention
    ['copy', 'cut', 'paste'].forEach(evt => {
      document.addEventListener(evt, (e) => {
        if (!this.isActive) return;
        e.preventDefault();
        App.showToast('Copy/Paste is disabled during active quiz rounds.');
        this.recordViolation('copy_attempt');
      });
    });

    // 3. Prohibited Keyboard Shortcuts
    document.addEventListener('keydown', (e) => {
      if (!this.isActive) return;

      // F12 or Inspect (Ctrl+Shift+I / Cmd+Opt+I)
      if (e.key === 'F12' || (e.ctrlKey && e.shiftKey && (e.key === 'I' || e.key === 'i' || e.key === 'J' || e.key === 'j' || e.key === 'C' || e.key === 'c'))) {
        e.preventDefault();
        App.showToast('Developer tools shortcuts are blocked.');
        this.recordViolation('devtools_key');
      }

      // Ctrl+U (View Source), Ctrl+S (Save), Ctrl+P (Print)
      if (e.ctrlKey && ['u', 's', 'p'].includes(e.key.toLowerCase())) {
        e.preventDefault();
        App.showToast('Shortcut disabled during the round.');
        this.recordViolation('devtools_key');
      }

      // Ctrl+C / Ctrl+V
      if (e.ctrlKey && ['c', 'v', 'x'].includes(e.key.toLowerCase())) {
        e.preventDefault();
        App.showToast('Copy/Paste shortcuts are disabled.');
        this.recordViolation('copy_attempt');
      }
    });

    // 4. Tab-switch / Window-blur Detection
    document.addEventListener('visibilitychange', () => {
      if (!this.isActive) return;
      if (document.hidden) {
        this.recordViolation('tab_switch');
      }
    });

    window.addEventListener('blur', () => {
      if (!this.isActive) return;
      this.recordViolation('tab_switch');
    });

    // 5. Fullscreen change enforcement
    document.addEventListener('fullscreenchange', () => {
      if (!this.isActive) return;
      if (!document.fullscreenElement) {
        this.recordViolation('fullscreen_exit');
        const overlay = document.getElementById('fullscreenResumeOverlay');
        if (overlay) overlay.style.display = 'flex';
      } else {
        const overlay = document.getElementById('fullscreenResumeOverlay');
        if (overlay) overlay.style.display = 'none';
      }
    });

    // 6. Native confirmation dialog on accidental tab close / refresh
    window.addEventListener('beforeunload', (e) => {
      if (this.isActive) {
        e.preventDefault();
        e.returnValue = 'Are you sure you want to leave? Your round answers may be lost.';
      }
    });
  },

  resumeFullscreen() {
    const elem = document.documentElement;
    const req = elem.requestFullscreen || elem.webkitRequestFullscreen || elem.msRequestFullscreen;
    if (req) {
      req.call(elem);
    }
    const overlay = document.getElementById('fullscreenResumeOverlay');
    if (overlay) overlay.style.display = 'none';
  },

  async recordViolation(violationType) {
    if (!this.isActive) return;

    const res = await App.fetch('api/log_violation.php', {
      method: 'POST',
      body: { round_id: this.roundId, violation_type: violationType }
    });

    if (res.success) {
      this.currentStrikes = res.strikes;
      this.updateStrikeDisplay();

      if (res.auto_submit) {
        this.isActive = false;
        clearInterval(this.timerInterval);
        alert(`Rule violation threshold reached (${res.strikes}/${res.max_strikes} strikes). Round is now auto-submitting.`);
        this.submitRound(true);
      } else {
        App.showToast(`⚠️ Warning: Strike ${res.strikes} of ${res.max_strikes} logged! (${violationType})`);
      }
    }
  },

  updateStrikeDisplay() {
    const container = document.getElementById('strikePills');
    if (!container) return;
    container.innerHTML = '';
    for (let i = 1; i <= this.maxStrikes; i++) {
      const dot = document.createElement('div');
      dot.className = `strike-dot ${i <= this.currentStrikes ? 'active' : ''}`;
      dot.title = `Strike ${i} of ${this.maxStrikes}`;
      container.appendChild(dot);
    }
  },

  collectAnswers() {
    const answers = [];
    document.querySelectorAll('.question-card').forEach(card => {
      const qId = parseInt(card.dataset.qid, 10);
      const selected = card.querySelector('.option-item.selected');
      if (qId && selected) {
        answers.push({
          question_id: qId,
          selected_answer: selected.dataset.key
        });
      }
    });
    return answers;
  },

  async submitRound(isViolationSubmit = false, isTimeOut = false) {
    if (!isViolationSubmit && !isTimeOut) {
      if (!confirm('Are you sure you want to submit your answers for this round? You cannot resubmit after confirmation.')) {
        return;
      }
    }

    this.isActive = false;
    clearInterval(this.timerInterval);

    const answers = this.collectAnswers();
    const res = await App.fetch('api/submit_round.php', {
      method: 'POST',
      body: {
        round_id: this.roundId,
        answers: answers,
        violation_auto_submit: isViolationSubmit
      }
    });

    if (res.success) {
      const resultsModal = document.getElementById('resultsModal');
      document.getElementById('resultScore').innerText = `${res.total_points} / ${res.max_points}`;
      document.getElementById('resultCorrect').innerText = `${res.total_correct} Correct`;
      document.getElementById('resultTitle').innerText = isViolationSubmit ? 'Auto-Submitted (Violations)' : 'Round Completed!';
      resultsModal.classList.add('active');
    } else {
      alert(res.error || 'Submission error.');
      window.location.href = 'dashboard.php';
    }
  }
};

// ======================== LEADERBOARD MODULE ========================
const Leaderboard = {
  interval: null,
  isProjector: false,

  init() {
    this.fetchData();
    this.interval = setInterval(() => this.fetchData(), 10000);
  },

  async fetchData() {
    const data = await App.fetch('api/get_leaderboard.php');
    if (!data.success) return;
    this.renderTable(data.leaderboard);
  },

  renderTable(teams) {
    const tbody = document.getElementById('leaderboardBody');
    if (!tbody) return;
    const filterText = (document.getElementById('teamFilter')?.value || '').toLowerCase();

    tbody.innerHTML = '';
    teams.forEach(t => {
      if (filterText && !t.team_name.toLowerCase().includes(filterText)) {
        return;
      }

      const tr = document.createElement('tr');
      if (t.rank === 1) tr.className = 'podium-1';
      else if (t.rank === 2) tr.className = 'podium-2';
      else if (t.rank === 3) tr.className = 'podium-3';

      if (t.rank === 5) tr.classList.add('top-5-cutoff');

      let podiumIcon = '';
      if (t.rank === 1) podiumIcon = '🥇 ';
      else if (t.rank === 2) podiumIcon = '🥈 ';
      else if (t.rank === 3) podiumIcon = '🥉 ';

      let roundCells = '';
      for (let r = 1; r <= 7; r++) {
        const sc = t.round_scores[r];
        roundCells += `<td style="font-family:var(--font-mono); text-align:center;">${sc !== null && sc !== undefined ? sc : '<span style="color:var(--text-dim);">-</span>'}</td>`;
      }

      tr.innerHTML = `
        <td class="rank-cell">${podiumIcon}#${t.rank}</td>
        <td class="team-name-cell">
          <strong>${t.team_name}</strong>
          ${t.is_top_5 && t.completed_rounds >= 5 ? '<span class="status-badge" style="background:rgba(0,255,136,0.1); color:var(--accent-green);">Top 5</span>' : ''}
          ${t.is_top_3 && t.completed_rounds >= 6 ? '<span class="status-badge" style="background:rgba(255,215,0,0.1); color:#ffd700;">Finalist</span>' : ''}
        </td>
        ${roundCells}
        <td class="score-cell">${t.grand_total} pts</td>
      `;
      tbody.appendChild(tr);
    });

    const updateTimeEl = document.getElementById('lastUpdateTime');
    if (updateTimeEl) {
      updateTimeEl.innerText = new Date().toLocaleTimeString();
    }
  },

  toggleProjectorMode() {
    this.isProjector = !this.isProjector;
    document.body.classList.toggle('projector-mode', this.isProjector);
    const nav = document.querySelector('.navbar');
    const footer = document.querySelector('.footer');
    if (nav) nav.style.display = this.isProjector ? 'none' : 'flex';
    if (footer) footer.style.display = this.isProjector ? 'none' : 'block';
  }
};
