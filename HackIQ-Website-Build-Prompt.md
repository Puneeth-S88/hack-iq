# HackIQ Event Website — Full Build Prompt

Copy-paste everything below into your AI coding tool (or hand to a developer) as a single spec. It covers all 7 rounds, team login, per-round passwords, database storage, and scoring — end to end.

---

## MASTER PROMPT (copy from here)

Build a complete, working web application for a college cybersecurity quiz event called **"HackIQ"**. Use **HTML, CSS, vanilla JavaScript** for the frontend and **PHP + MySQL** for the backend. No frameworks — plain PHP (mysqli or PDO) and plain JS (fetch API). Deliver every file needed to run this on a standard LAMP/XAMPP stack, including the full database schema as a `.sql` file.

### 1. Tech Stack & Structure

- Frontend: HTML5, CSS3, JavaScript (fetch for all API calls, no page reloads where possible)
- Backend: PHP (procedural or simple OOP, mysqli/PDO with prepared statements — no raw string SQL, to prevent SQL injection)
- Database: MySQL/MariaDB
- Sessions: PHP sessions to keep a team logged in across pages and rounds
- Folder structure:
  ```
  /hackiq
    /css/style.css
    /js/app.js
    /api/  (all PHP endpoints: login.php, register.php, get_round.php, submit_round.php, get_leaderboard.php, get_team_answers.php, admin_login.php, etc.)
    /includes/db.php (DB connection), /includes/auth.php (session check helper)
    index.php (welcome page)
    login.php
    dashboard.php
    round.php (generic round page, loads round N via ?round=1..7)
    leaderboard.php
    admin.php
    database/hackiq.sql
  ```

### 2. Database Schema

Create these tables (write full `CREATE TABLE` statements in `hackiq.sql`, with sample seed data for all 7 rounds' questions):

- **teams**: `id, team_name (UNIQUE, NOT NULL), password_hash, created_at`
  - Team name must be unique — reject registration/login attempts that duplicate an existing team name (case-insensitive check).
- **rounds**: `id, round_number (1-7), round_name, round_password_hash, time_limit_minutes, is_active (bool, admin can lock/unlock a round)`
- **questions**: `id, round_id (FK), question_text, question_type (ENUM: 'mcq','true_false','phishing','cipher','scenario','buzzer'), option_a, option_b, option_c, option_d, correct_answer, points (default 1)`
  - For phishing/cipher/scenario rounds, still store as structured options (A/B/C/D) so scoring can be automatic — avoid free-text answers so everything can be auto-graded.
- **team_round_access**: `id, team_id (FK), round_id (FK), unlocked_at` — logs when a team successfully entered a round's password, so they can't be asked twice and admins can see progress.
- **answers**: `id, team_id (FK), question_id (FK), round_id (FK), selected_answer, is_correct (bool), points_awarded, submitted_at`
  - One row per question per team. Enforce a UNIQUE constraint on `(team_id, question_id)` so a team can't submit the same question twice (use "insert or update" logic).
- **round_scores**: `id, team_id (FK), round_id (FK), total_correct, total_points, submitted_at` — one row per team per round, calculated the moment a round is submitted.
- **team_totals** (view or computed on the fly): sum of `round_scores.total_points` per team, used for the leaderboard and to determine Top 5 / Top 3 cutoffs after Rounds 5 and 6.

### 3. Page-by-Page Flow

**A. Welcome Page (`index.php`)**
- Full-screen hero: "Welcome to HackIQ" with event tagline and a "Get Started" / "Login / Register" button leading to `login.php`.

**B. Login / Register Page (`login.php`)**
- Two tabs or toggle: **Register** (team name + password, confirm password) and **Login** (team name + password).
- On register: check team name doesn't already exist (case-insensitive) before inserting; hash password with `password_hash()`.
- On login: verify with `password_verify()`, start PHP session storing `team_id` and `team_name`.
- On success, redirect to `dashboard.php`.

**C. Dashboard (`dashboard.php`)**
- Shows logged-in team name and a logout button.
- Displays all 7 rounds as cards/tiles: Round 1 through Round 7, each with its title and a short description (pull from your event flow).
- Round 6 tile only becomes clickable for teams that are in the current Top 5 by cumulative score (check `team_totals`); otherwise show it locked/greyed out with a tooltip like "Unlocks for top 5 teams after Round 5."
- Round 7 tile only becomes clickable for teams in the current Top 3 after Round 6, same locking logic.
- Rounds 1-5 are open to all teams whenever the admin has set `is_active = 1` for that round.
- Show a checkmark or "Completed ✔" badge on rounds the team has already submitted (from `round_scores`), and disable re-entry into a completed round (or allow view-only review — your choice, but state it clearly in the UI).

**D. Round Password Gate**
- Clicking a round tile opens a small modal/page asking for that round's password (separate from the team's login password).
- Submit to `api/verify_round_password.php`; compare against `rounds.round_password_hash` with `password_verify()`.
- On success: insert into `team_round_access`, then redirect into `round.php?round=N`.
- On failure: show inline error, no redirect.

**E. Round Page (`round.php?round=N`)**
- Fetches all questions for that round via `api/get_round.php?round=N`.
- Renders questions per type:
  - MCQ / True-False: radio buttons for options A-D (or True/False).
  - Spot the Phishing: show the screenshot/image (use an `<img>` placeholder path, admin can upload images later) with A/B/C/D style options describing the red flag or genuine/fake choice.
  - Decode the Message: show the encoded text, team selects the correct decoded answer from 4 options (auto-gradable) OR types the answer into a text box that gets matched case-insensitively against the stored correct answer.
  - What Would You Do (scenario): show the scenario text with 3-4 response options.
  - Round 6 / 7 (Semifinal/Final): same rendering engine reused, just pulling harder questions tagged to `round_id = 6` or `7`, plus an optional live timer/buzzer flag if you want a "first correct" mode (see Section 5 note).
- A visible countdown timer per round (`time_limit_minutes` from DB) using JS `setInterval`; auto-submits when it hits zero.
- "Submit Round" button sends all answers in one JSON payload to `api/submit_round.php`.

**F. Submit & Scoring Logic (`api/submit_round.php`)**
- Receives `{team_id, round_id, answers: [{question_id, selected_answer}, ...]}`.
- For each answer: look up the question's `correct_answer`, compare, insert into `answers` table with `is_correct` and `points_awarded` (0 or the question's `points` value).
- Sum up total correct and total points, insert/update `round_scores` for that team+round.
- Return the team's score for that round immediately so the UI can show "You scored 8/10!" on a results screen.
- Prevent duplicate submission: if a `round_scores` row already exists for that team+round, reject further submissions (or clearly define it as "resubmission not allowed").

**G. Leaderboard (`leaderboard.php`)**
- Public-facing (or admin-only, your choice — recommend public for event excitement) live leaderboard.
- Table: Rank | Team Name | Round 1-7 scores (columns) | Total Score, sorted descending by total.
- Auto-refresh every 10-15 seconds via JS `fetch` polling `api/get_leaderboard.php`.
- Highlight Top 5 after Round 5 closes, and Top 3 after Round 6 closes.

**H. Admin Panel (`admin.php`)**
- Separate admin login (hardcoded or a simple `admins` table with its own username/password).
- Admin can: view every team's full answer sheet per round (which question, what they selected, correct/wrong, points), toggle `rounds.is_active`, edit/add questions, set/change round passwords, and manually override a team's score if needed (e.g., for judged scenario justifications given live).
- Admin dashboard should show, per team: Team Name → click → expands to show all 7 rounds → click a round → shows every question, the team's selected answer, correct answer, and points awarded, plus the round's total and the team's grand total.

### 4. Validation & Security Requirements

- All passwords (team login + round passwords + admin) stored as hashes via `password_hash()`, verified via `password_verify()`. Never store plaintext.
- Use prepared statements (mysqli or PDO) everywhere — no string-concatenated SQL.
- Server-side session checks on every protected page/API (`includes/auth.php`) — redirect to `login.php` if no valid session.
- Enforce team name uniqueness at the database level (`UNIQUE` constraint) AND with a friendly JS/PHP error message on the register form ("Team name already taken, please choose another").
- Sanitize/validate all incoming JSON in every `api/*.php` endpoint before touching the database.
- CSRF-light protection: at minimum, only accept POST for state-changing actions (login, register, submit).

### 5. Notes on Rounds 6 & 7 (Live/Buzzer Rounds)

- Since these are meant to be run live in front of the room, build them using the same question-and-scoring engine as Rounds 1-5, but add an admin-controlled "reveal next question" trigger (simple button on `admin.php` that flips a `current_question_index` for that round) so the room sees questions appear one at a time on a shared screen.
- For true "first team to buzz" scoring, add a `buzzed_at` timestamp column on `answers` for Round 7 questions, and award points only to the fastest correct submission — this is optional polish; a simpler fallback is standard per-question scoring like the other rounds if buzzer logic is too complex for a first build.

### 6. Deliverables Expected From the AI/Developer

1. `database/hackiq.sql` — full schema + seed data for all 7 rounds (use realistic placeholder questions for MCQ, True/False, Phishing, Cipher, Scenario types so the app is testable immediately).
2. All PHP files listed in the folder structure above, fully working.
3. `css/style.css` — clean, modern, event-branded look (dark theme with a "cyber/hacker" accent color like neon green or electric blue works well thematically).
4. `js/app.js` — all fetch calls, timers, and dynamic rendering logic.
5. A short `README.md` explaining how to import the SQL file and configure `includes/db.php` with database credentials.

Build this as a complete, runnable project — don't leave placeholder TODOs in the core login, round-gating, answer-submission, or scoring logic, since those are the core of the event.

---

*End of prompt. Paste the section between "MASTER PROMPT" and this line into your AI builder or send to your developer as-is.*
