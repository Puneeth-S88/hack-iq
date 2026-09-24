# HackIQ — Cybersecurity Tournament & Quiz Platform

> **"Think Fast. Hack Smart."**  
> **Date:** 15.10.2026 | **Time:** 2:00 PM – 4:00 PM | **Venue:** Auditorium LSL04  
> **Student Coordinators:** Puneeth S, Prajwal BU  
> **Prize Pool:** ₹6,000 (🥇 ₹3,000 | 🥈 ₹2,000 | 🥉 ₹1,000)

---

## 1. System Overview

**HackIQ** is a full-featured tournament quiz web platform engineered for a 7-round cybersecurity tournament for mixed-year participants. It includes:
- **Round-by-round access gating** via per-round passcodes announced by event coordinators.
- **Strict Anti-Cheating & Proctoring Engine** on live round pages (fullscreen lockdown, strike system, tab-switch & shortcut detection).
- **Auto-grading** for MCQs, True/False, Phishing cards, Cryptographic ciphers, and tactical scenarios.
- **Live Jury Evaluation Portal** for Round 5 Case Study Pitches, Round 6 Part B Jury Explain, and Round 7 Tiebreakers.
- **Top 5 & Top 3 Cutoff Logic**:
  - Rounds 1 to 5: Open to all 15 competing teams.
  - Round 6 (Semifinal): Automatically unlocks only for the **Top 5** cumulative scorers.
  - Round 7 (Final Showdown): Automatically unlocks only for the **Top 3** finalists.
- **Real-Time Live Leaderboard** with auto-refresh every 10 seconds and Projector View mode for auditorium screens.

---

## 2. Directory Structure

```
hackiq/
├── api/
│   ├── admin_actions.php          # Toggles, jury scoring, reset attempts
│   ├── admin_login.php            # Coordinator authentication
│   ├── get_dashboard.php          # Team standings, rank & round gating
│   ├── get_leaderboard.php        # Live rankings & R1-R7 matrix
│   ├── get_round.php              # Secure question loading (hides answer key)
│   ├── get_team_answers.php       # Admin answer sheet review
│   ├── log_violation.php          # Strike counter & auto-submit trigger
│   ├── login.php                  # Team login
│   ├── logout.php                 # Session termination
│   ├── register.php               # Team registration (case-insensitive unique check)
│   ├── submit_round.php           # Server-side grading & score recording
│   └── verify_round_password.php  # Per-round passcode verification
├── css/
│   └── style.css                  # Cyberpunk dark theme with emerald & electric blue
├── database/
│   └── hackiq.sql                 # Complete MySQL schema & 7-round seed question bank
├── includes/
│   ├── auth.php                   # Session verification & route guards
│   └── db.php                     # PDO MySQL connection
├── js/
│   └── app.js                     # Proctoring engine, timers & live AJAX
├── admin.php                      # Admin control center & Jury portal
├── dashboard.php                  # Team round arena & stage selection
├── index.php                      # Tournament landing & guidelines
├── leaderboard.php                # Live leaderboard & projector display
├── login.php                      # Team registration & authentication
└── round.php                      # Monitored round answering screen
```

---

## 3. Database & XAMPP Setup

### Step 1: Ensure Apache and MySQL are running
Open **XAMPP Control Panel** and ensure **Apache** and **MySQL** modules are started (showing green).

### Step 2: Database Import
The application database name is `hackiq`.
Import `database/hackiq.sql` into MySQL:
```bash
# Using Command Prompt:
mysql -u root < database/hackiq.sql
```
Or open **phpMyAdmin** (`http://localhost/phpmyadmin/`):
1. Click **Import**.
2. Choose file: `database/hackiq.sql`.
3. Click **Go**.

### Step 3: Run on Localhost / LAN
Place the `hackiq` folder inside `C:\xampp\htdocs\`:
- Local URL: **`http://localhost/hackiq/`**
- In-Venue LAN URL (for all 15 teams on campus Wi-Fi): **`http://<YOUR-LAPTOP-IP>/hackiq/`**  
  *(Find your IP by running `ipconfig` in Command Prompt, e.g. `http://192.168.1.45/hackiq/`)*

---

## 4. Credentials & Passcode Reference

### Admin Portal (`admin.php`)
- **Username:** `admin`
- **Password:** `admin123`

### Secret Per-Round Passcodes (Announced by Coordinators Live)
| Round | Round Name | Format | Duration | Secret Passcode | Points |
|---|---|---|---|---|---|
| **Round 1** | MCQ Warmup | 10 MCQs | 10 min | `WARMUP26` | 10 pts |
| **Round 2** | Rapid Fire True/False | 10 Statements | 10 min | `RAPID26` | 10 pts |
| **Round 3** | Spot the Phishing | 10 Message Cards | 15 min | `PHISH26` | 20 pts |
| **Round 4** | Decode the Message | 10 Cryptic Ciphers | 15 min | `CIPHER26` | 30 pts |
| **Round 5** | Case Study Pitch | 10 Incident Cases | 15 min | `PITCH26` | 10 pts (Jury) |
| **Round 6** | Semifinal Challenge | Speed + Jury Explain | 20 min | `SEMI26` | 25 pts (Top 5) |
| **Round 7** | Final Showdown | Live Breach Room | 15 min | `FINAL26` | 26 pts (Top 3) |

*Note: Coordinators can change any round passcode or time limit anytime from `admin.php`.*

---

## 5. Proctoring Module (Anti-Cheating Specifications)

Active during round execution on `round.php`:
1. **Right-Click Disabled:** Context menu is blocked with toast notification + strike logged.
2. **Text Selection & Copy/Paste Blocked:** Question text cannot be selected or copied.
3. **Key Combination Blocker:** `F12`, `Ctrl+Shift+I` (DevTools), `Ctrl+U` (Source), `Ctrl+P`, `Ctrl+S`, `Ctrl+C`, `Ctrl+V` are intercepted.
4. **Tab Switch & Window Blur Detection:** Switching tabs or minimizing triggers a warning banner and server strike log.
5. **Fullscreen Lockdown:** Round requires entering fullscreen. Exiting fullscreen triggers a blocking overlay.
6. **3-Strike Server Threshold:** Upon 3 strikes, answers are automatically finalized and submitted with `flagged = 1`.
7. **Coordinator Visibility:** `admin.php` provides full violation audit logs and a **"Reset & Allow Retry"** button in case of false alarms.

---

## 6. Jury & Live Rounds Scoring Workflow

- **Round 5 (Case Study Pitch):** Teams prepare for 2 minutes and pitch for 2 minutes. The jury scores each team (0 to 10 points) directly in `admin.php` under **Live Jury Scoring Portal**.
- **Round 6 Part B (Explain to Jury):** Jury enters 0 to 15 points per Top 5 team.
- **Round 7 Stage 5 (Tiebreaker):** Open-ended single-sentence mitigation scored by the jury out of 10 points to determine 1st, 2nd, and 3rd place podium.
