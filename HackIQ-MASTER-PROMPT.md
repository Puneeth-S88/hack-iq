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



Append this section to the main build prompt (paste it right after Section 4 "Validation & Security Requirements"). It adds exam-lockdown behavior to every round page.

**Important technical note (read before pasting):** No browser allows JavaScript to *literally prevent* a user from opening a new tab (Ctrl+T), switching tabs (Ctrl+Tab / Alt+Tab), or force-closing a tab — this is a deliberate browser security restriction that no website can override, and any tool claiming otherwise is misleading. What actually works, and what this spec builds, is **real-time detection + consequences**: the moment a team tries any of these, the app catches it instantly and acts (warning → strike → auto-submit/disqualify). This is the same approach real online exam-proctoring platforms use.

---

## PROCTORING MODULE — Add to Round Pages Only

Apply all of the below **only on `round.php`** (the live question-answering screen) — not on the welcome, login, dashboard, or leaderboard pages, so teams can navigate normally outside of active rounds.

### 1. Right-Click & Context Menu
- Disable the right-click context menu (`oncontextmenu return false`) on the round page.
- On attempted right-click, show a small on-screen toast: "Right-click is disabled during the round" and log 1 strike (see Section 5).

### 2. Text Selection & Copy/Paste
- Disable text selection via CSS (`user-select: none`) on all question/option content.
- Block `copy`, `cut`, and `paste` events via JS (`preventDefault()`), with the same toast warning.
- Do NOT disable selection/copy on the login, register, or leaderboard pages — only inside an active round.

### 3. Keyboard Shortcuts
- Block common shortcuts during a round: `F12`, `Ctrl+Shift+I` / `Cmd+Opt+I` (DevTools), `Ctrl+U` (view source), `Ctrl+P` (print), `Ctrl+S` (save page), `Ctrl+C` / `Ctrl+V` (copy/paste).
- Each blocked attempt shows the toast and logs 1 strike.
- Note honestly in code comments: a determined user can still open DevTools through the browser menu — this layer deters casual attempts, it isn't unbreakable, and that's fine for a college quiz event.

### 4. Tab-Switch / Window-Blur Detection
- Listen for the `visibilitychange` and `window.blur` events.
- The instant a team switches tabs, minimizes, or clicks outside the browser window during an active (started, not-yet-submitted) round, log 1 strike and show a warning banner: "⚠️ Tab switch detected — Strike 1 of 3."
- Do NOT try to block the switch (impossible) — just detect and penalize it.

### 5. Strike System (Server-Enforced)
- Add a `round_violations` table: `id, team_id, round_id, violation_type (ENUM: 'right_click','copy_attempt','devtools_key','tab_switch','fullscreen_exit'), occurred_at`.
- Every violation event fires a `fetch` call to `api/log_violation.php` which inserts the row — client-side counting alone isn't trustworthy, so the server keeps the authoritative strike count for that team+round.
- Configurable threshold (default: **3 strikes**) stored in the `rounds` table as `max_violations`.
- On the 3rd strike: auto-submit whatever answers are currently filled in (same logic as a normal submit) and lock the team out of that round with a message: "Round auto-submitted due to repeated rule violations." Show this on screen and record it in `round_scores` with a `flagged = 1` column so admins can see it was a violation-submit, not a normal one.
- Strikes 1 and 2 just show a warning toast/banner with the current count — don't submit yet.

### 6. Fullscreen Enforcement
- When a team clicks "Start Round," request fullscreen via the Fullscreen API (`element.requestFullscreen()`) before showing the first question.
- Listen for `fullscreenchange` — if the team exits fullscreen (Esc key, etc.) during an active round, treat it as a violation (same strike system) and show a "Re-enter fullscreen to continue" overlay with a button to resume.

### 7. Prevent Accidental Close/Refresh
- Add a `beforeunload` listener during an active round that shows the browser's native "Leave site? Changes may not be saved" confirmation dialog if the team tries to close the tab or refresh mid-round.
- This is a genuine browser-native warning (not blockable by us to be stronger than that) — it won't stop a determined team, but it does stop accidental refreshes, which are the more common real-world problem.

### 8. Admin Visibility
- On `admin.php`, in each team's per-round detail view, show a "Violations" panel: total strike count, and a timestamped list of each violation type — so admins can review any auto-submitted round and manually reinstate a team's attempt if it was a false positive (e.g. accidental Alt+Tab).
- Add a manual "Reset & allow retry" admin action that clears that team's `round_scores` and `round_violations` rows for a specific round, letting them re-enter with the round password if the admin judges it fair.

### 9. What This Module Deliberately Does NOT Attempt
State this plainly in the README so expectations are correct on event day:
- It cannot block a second device (phone) being used to look up answers.
- It cannot block screenshots taken with a separate camera.
- It cannot literally prevent opening a new tab or closing the browser — only detect and penalize it after the fact.
- For a stricter lockdown, dedicated exam-proctoring software (e.g. a lockdown browser) would be needed — out of scope for this web build, but worth mentioning to organizers as an option for future events with higher stakes.

---

*Paste this whole section into the master prompt after "Section 4" so the AI builder implements it alongside login, round-gating, and scoring.*



Real, ready-to-use content for every round. Correct answers are marked with **✔**. This replaces the "placeholder seed data" instruction in the build prompt — tell your AI builder to insert this exact content into `database/hackiq.sql` instead of generating its own placeholders.

Each question below is written in the A/B/C/D auto-gradable format the website engine expects, so nothing needs manual grading.

---

## ROUND 1 — MCQ Warmup (20 Questions, 1 point each)

1. What makes a password strong?
 A) Your pet's name ✔ No B) A mix of upper/lower case, numbers, and symbols ✔ C) Your birth year D) The word "password123"
 **Correct: B**

2. What does 2FA stand for?
 A) Two-Factor Authentication ✔ B) Two-File Access C) Firewall Authorization D) Fast Access
 **Correct: A**

3. Which of these is the safest password?
 A) `123456` B) `qwerty` C) `Tr@ck!ng_Sunset92` ✔ D) `password`
 **Correct: C**

4. Is it safe to log into your bank account on public airport WiFi?
 A) Yes, always B) Only if the WiFi has a password C) No, avoid it or use a VPN ✔ D) Yes, if the site has "http"
 **Correct: C**

5. Which of these is a sign of a phishing email?
 A) Comes from a known colleague B) Creates urgency and asks you to click a link immediately ✔ C) Has no attachments D) Is well-formatted
 **Correct: B**

6. What should you do if a website asks you to enable macros in a downloaded file?
 A) Enable them right away B) Be suspicious and avoid enabling unless you trust the source ✔ C) Forward it to a friend D) Ignore, macros are always safe
 **Correct: B**

7. What is "shoulder surfing"?
 A) A type of malware B) Someone watching you enter a password/PIN in person ✔ C) A social media trend D) A firewall setting
 **Correct: B**

8. Which is the best practice for passwords across different accounts?
 A) Use the same password everywhere for convenience B) Use a unique password for each account ✔ C) Write all passwords on a sticky note D) Share passwords with close friends
 **Correct: B**

9. What does HTTPS in a website URL indicate?
 A) The site is definitely safe B) The connection is encrypted ✔ C) The site is government-owned D) The site has no ads
 **Correct: B**

10. What is a common sign of a scam call/message?
 A) It thanks you for being a loyal customer B) It asks for OTP or bank details urgently ✔ C) It's from a saved contact D) It has correct grammar
 **Correct: B**

11. What should you do before clicking a shortened link (like bit.ly) from an unknown sender?
 A) Click immediately B) Hover/preview the link or use a link-expander to check the destination ✔ C) Reply asking "is this safe?" D) Forward it to 5 friends
 **Correct: B**

12. Which of these is the safest way to store passwords?
 A) In a notes app on your phone B) In a password manager ✔ C) In your email drafts D) Memorize one password for everything
 **Correct: B**

13. What is "phishing"?
 A) A method of catching fish online B) Tricking someone into revealing sensitive info via fake communication ✔ C) A type of firewall D) An antivirus feature
 **Correct: B**

14. Should you update your apps and OS regularly?
 A) No, updates slow down the device B) Yes, updates often patch security vulnerabilities ✔ C) Only once a year D) Only if the app crashes
 **Correct: B**

15. What's the risk of using the same password across multiple sites?
 A) None, if the password is strong B) If one site is breached, all your accounts become vulnerable ✔ C) It makes login faster only D) Websites prefer it
 **Correct: B**

16. A pop-up says "Your device is infected, call this number now!" What should you do?
 A) Call immediately B) Ignore/close it — it's almost certainly a scam ✔ C) Enter your card details to "fix" it D) Share your screen with the caller
 **Correct: B**

17. What is a VPN mainly used for?
 A) Making your internet faster always B) Encrypting your connection and hiding your IP/location ✔ C) Blocking all ads D) Increasing WiFi signal strength
 **Correct: B**

18. Which detail in a URL should make you suspicious?
 A) `amaz0n-support.com` instead of `amazon.com` ✔ B) `.com` domain C) A padlock icon D) A short URL length
 **Correct: A**

19. What should you do if you accidentally click a suspicious link?
 A) Ignore it, nothing happens B) Disconnect from the internet, run a scan, and change passwords if info was entered ✔ C) Restart your phone once D) Nothing needed if no popup appeared
 **Correct: B**

20. Why is 2FA more secure than a password alone?
 A) It requires a second, independent proof of identity even if the password is stolen ✔ B) It makes the password longer C) It's faster to log in D) It removes the need for a password
 **Correct: A**

---

## ROUND 2 — Rapid Fire True/False (18 Statements, 1 point each)

1. Using the same password everywhere is safe. → **False**
2. Public WiFi is fine for online banking. → **False**
3. Enabling 2FA makes your account more secure. → **True**
4. A padlock icon in the browser guarantees a site is 100% safe. → **False**
5. You should never share your OTP with anyone, even someone claiming to be from your bank. → **True**
6. Updating your software regularly helps protect against known vulnerabilities. → **True**
7. It's fine to click links in emails claiming you won a lottery you never entered. → **False**
8. A strong password should include a mix of letters, numbers, and symbols. → **True**
9. Logging out of shared/public computers after use is unnecessary. → **False**
10. Free public charging stations (USB) can be a security risk ("juice jacking"). → **True**
11. It's safe to download apps only from official app stores. → **True**
12. Screenshots of your OTP or PIN are safe to share in group chats. → **False**
13. A website with "https" and a padlock can still be a phishing site. → **True**
14. You should back up important data regularly. → **True**
15. Antivirus software makes you 100% immune to all cyber threats. → **False**
16. Using a password manager is a good security practice. → **True**
17. It's okay to use your work email/password to sign up for random third-party websites. → **False**
18. Checking a sender's actual email address (not just the display name) helps spot phishing. → **True**

---

## ROUND 3 — Spot the Phishing (9 Items, 2 points each)

*Design note: since real screenshots aren't included here, each item below is written as a text description of the email/SMS/login page. Render these as styled "message cards" in the UI to simulate the screenshot experience. Each has 4 options: exactly one is correct.*

1. **Email from:** `support@paypa1-secure.com` — Subject: "Your account has been LIMITED — verify within 24 hours or lose access!" Contains a link `bit.ly/paypal-verify-now`.
 A) Genuine — PayPal often limits accounts B) Phishing — misspelled domain + urgency + shortened link ✔ C) Genuine — has PayPal logo D) Can't tell
 **Correct: B**

2. **SMS from "BANK":** "Dear customer, your a/c will be blocked today. Update KYC immediately: hxxp://kyc-verify-bank.ml/update"
 A) Genuine B) Phishing — suspicious `.ml` domain, urgency, generic greeting ✔ C) Genuine — banks send SMS often D) Can't tell
 **Correct: B**

3. **Email from:** `hr@yourcompany.com` — Subject: "Updated Leave Policy — attached PDF, please review by Friday." Signed by your actual HR head's name, no links, plain attachment.
 A) Genuine — internal domain, no urgency, no suspicious link ✔ B) Phishing C) Genuine but only because of the PDF D) Can't tell
 **Correct: A**

4. **Login page** that looks exactly like Instagram but the URL is `instagram-login-secure.net`.
 A) Genuine — it looks identical to Instagram B) Phishing — fake domain hosting a cloned login page ✔ C) Genuine — Instagram uses multiple domains D) Can't tell
 **Correct: B**

5. **Email from:** `no-reply@netflix.com` — Subject: "Your payment failed" with a normal, professionally worded message and a link to `netflix.com/account/billing` (real domain, hover-confirmed).
 A) Genuine — real domain, professional tone, no pressure tactics ✔ B) Phishing — all payment emails are scams C) Genuine only because of the logo D) Can't tell
 **Correct: A**

6. **WhatsApp message** from an unknown number: "Hi, I'm from your college placement cell. Click this link to register for the exclusive drive: `tinyurl.com/placement-2026-urgent`"
 A) Genuine — placement cells do this B) Phishing — unknown number, urgency, shortened link, unofficial channel ✔ C) Genuine, since it mentions college D) Can't tell
 **Correct: B**

7. **Email from:** `security@google.com` (domain verified genuine) — "New sign-in detected on your Google Account from Chennai, India. If this wasn't you, secure your account." Link points to `myaccount.google.com`.
 A) Genuine — verified domain, real Google security alert format, link matches ✔ B) Phishing — all "new sign-in" emails are fake C) Genuine only if you're actually in Chennai D) Can't tell
 **Correct: A**

8. **Email:** "CONGRATULATIONS!!! You have WON an iPhone 16!! Claim NOW before it EXPIRES in 10 mins!! Enter your card details to pay ₹99 shipping."
 A) Genuine — free gifts happen B) Phishing — excessive urgency, unearned prize, asks for payment card info ✔ C) Genuine, only the "10 mins" part is fake D) Can't tell
 **Correct: B**

9. **LinkedIn message** from a recruiter with a real-looking profile, no links, asking to schedule a call through LinkedIn's own messaging to discuss an internship — no payment or personal info requested.
 A) Genuine — normal recruiting behavior, no red flags present ✔ B) Phishing — all unsolicited recruiter messages are scams C) Genuine only if you already applied D) Can't tell
 **Correct: A**

---

## ROUND 4 — Decode the Message (5 Ciphers, 3 points each)

*Each is shown as encoded text with 4 possible decoded options — auto-gradable.*

1. **Caesar cipher, shift +3:** `KDFN LT LV IXQ`
 A) HACK IQ IS FUN ✔ B) HACK ID IS COOL C) TEAM WORK WINS D) CYBER IS SAFE
 **Correct: A** *(Decode: shift each letter back by 3)*

2. **ROT13:** `Fgnl fnsr bayvar`
 A) Stay safe online ✔ B) Play fair offline C) Stay away online D) Stay calm inline
 **Correct: A**

3. **Caesar cipher, shift +5:** `VJKPM DghqtG [qw Enkem`
 *(Simplify — use this cleaner version instead):* `YMNSP GJKTWJ DTZ HQNHP` → shift +5
 A) THINK BEFORE YOU CLICK ✔ B) THINK TWICE YOU CLICK C) LOOK BEFORE YOU LEAP D) NEVER CLICK LINKS
 **Correct: A**

4. **Simple letter-substitution (A=Z, B=Y, C=X... reverse alphabet, "Atbash"):** `KZHHDLIW HZUVGB`
 A) PASSWORD SAFETY ✔ B) PASSWORD SECURE C) PROTECT SAFETY D) PASSCODE SAFETY
 **Correct: A**

5. **ROT13:** `Arire funer lbhe BGC`
 A) Never share your OTP ✔ B) Always share your OTP C) Never trust your OTP D) Never forget your OTP
 **Correct: A**

---

## ROUND 5 — What Would You Do? Scenarios (7 Scenarios, 3 points each)

1. Your friend sends a link on Instagram saying "OMG you won a free iPhone, claim here!" What do you do?
 A) Click immediately, it's from a friend B) Message your friend separately to check if their account was hacked, don't click ✔ C) Enter your details to check if it's real D) Forward it to other friends first
 **Correct: B**

2. You receive a call from someone claiming to be your bank, asking you to share the OTP you just received to "verify your identity." What do you do?
 A) Share the OTP since they already know your account details B) Refuse — banks never ask for OTP over a call, hang up and call the bank's official number ✔ C) Share only the last 2 digits D) Ask them to call back later
 **Correct: B**

3. You're about to connect to a WiFi network at a cafe called "Free_Cafe_WiFi" with no password. What's the safest approach?
 A) Connect and do online banking immediately B) Connect only for light browsing, avoid logging into sensitive accounts, or use a VPN ✔ C) Connect and share the network with others D) Avoid using your phone entirely
 **Correct: B**

4. A website asks you to "Enable Macros" to view a document sent by an unknown sender. What do you do?
 A) Enable macros to see the content B) Don't enable macros; treat the file as suspicious and avoid opening it further ✔ C) Forward the file to IT without warning them D) Open it in incognito mode instead
 **Correct: B**

5. You find a USB drive lying in the college parking lot labeled "Salary Details 2026." What do you do?
 A) Plug it into your laptop to see what's on it B) Don't plug it in — hand it to security/IT, as unknown USB drives can carry malware ✔ C) Plug it into a friend's laptop instead D) Take it home to check safely
 **Correct: B**

6. You get an email from your "college admin" asking you to reset your password using a link, but the sender's email address looks slightly off from the usual college domain. What do you do?
 A) Reset your password immediately using the link B) Don't click the link — go directly to the college portal by typing the URL yourself, or verify with admin in person ✔ C) Reply to the email asking if it's genuine D) Ignore it completely and do nothing
 **Correct: B**

7. Your teammate wants to share a shared document but sends you a login prompt page from a domain you don't recognize, asking for your email and password. What do you do?
 A) Enter your credentials since your teammate sent it B) Don't enter your credentials — verify the link with your teammate directly and check the domain first ✔ C) Enter a fake password to test it D) Assume it's fine since it looks professional
 **Correct: B**

---

## ROUND 6 — Semifinal Challenge (Top 5 Teams Only)

*Harder ciphers, trickier phishing set, and 2-3 judgment scenarios — same auto-graded A/B/C/D format, higher points (4-5 each).*

**Harder Ciphers (2, 5 points each):**

1. **Caesar shift +7:** `CHFDYL HL RRA` *(use cleaner version)* → `BLHFYL LZML HGSPUL` shift +7
 A) SECURITY MATTERS ONLINE B) STAY SAFE ONLINE ✔ C) SECURE YOUR ACCOUNT D) SAFETY FIRST ONLINE
 **Correct: B**

2. **Vigenère-style hint cipher (keyword: CYBER), or simplified ROT13-double:** `Anzr guerr snpgbef bs frphevgl`
 A) Name three factors of security ✔ B) Name two features of security C) Name three types of malware D) Name three phases of hacking
 **Correct: A**

**Trickier Phishing Set (2, 4 points each):**

3. **Email from:** `admin@college-portal-help.com` (not the real college domain), Subject: "URGENT: Exam form submission failing, click to fix now" — professionally formatted, uses your real name.
 A) Genuine, since it uses your real name B) Phishing — wrong domain despite personalization and urgency ✔ C) Genuine, colleges use third-party tools D) Can't tell without more info
 **Correct: B**

4. A job offer email from a real-sounding company domain, offering a "work from home" role with "no interview needed," asking you to pay a ₹500 "registration fee" to get started.
 A) Genuine — many WFH jobs are legit B) Phishing/scam — legitimate jobs don't ask candidates to pay upfront ✔ C) Genuine, since a fee this small is normal D) Can't tell
 **Correct: B**

**Judgment Scenarios (2-3, 4 points each):**

5. During a group project, a teammate suggests using the same shared password for a tool across the whole team, posted in an open WhatsApp group. What's the best call?
 A) Agree, it's convenient B) Suggest each member use their own account/access instead of one shared password in an open group ✔ C) Post the password anyway, it's a small group D) Change it every day but keep posting it
 **Correct: B**

6. You notice a classmate's college account posting spam links to everyone. What's the safest, most responsible action?
 A) Click the link to see what happens B) Don't click; alert the classmate through another channel and report it to IT/admin ✔ C) Share the link further as a joke D) Ignore it completely
 **Correct: B**

---

## ROUND 7 — Final Showdown (Top 3 Teams — Live Buzzer Round)

*Rapid-fire mix pulling from all previous themes. First correct answer scores. 10 questions, 2 points each, designed to be read aloud/displayed one at a time.*

1. True or False: HTTPS alone guarantees a website is safe. → **False**
2. Decode (ROT13): `Ybtva` → **Login**
3. Is `P@ssw0rd!2026` a strong password? → **Yes, relatively (mixed case, numbers, symbol) — though avoid predictable patterns**
4. What should you do if you receive an urgent OTP-sharing request by phone? → **Refuse and hang up**
5. True or False: Public WiFi is safe for banking if the network has a password. → **False**
6. What's the term for tricking someone into revealing sensitive info via fake messages? → **Phishing**
7. Decode (Caesar +1): `IBRLNH` → shift back 1 → **HAQKMG** *(use simpler: `TFDN` shift +1 → SECURE-style word)* — replace with: `UIF LFZ JT TBGF` shift +1 → **THE KEY IS SAFE**
8. True or False: You should update your apps regularly for security patches. → **True**
9. What does 2FA add on top of a password? → **A second, independent verification step**
10. Spot the flag: An email urges "Act now or lose access in 10 minutes!" — what's this an example of? → **Urgency/pressure tactic used in phishing**

---

## Scoring Summary (for reference)

| Round | Questions | Points Each | Round Max |
|---|---|---|---|
| 1 — MCQ Warmup | 20 | 1 | 20 |
| 2 — True/False | 18 | 1 | 18 |
| 3 — Spot the Phishing | 9 | 2 | 18 |
| 4 — Decode the Message | 5 | 3 | 15 |
| 5 — Scenarios | 7 | 3 | 21 |
| 6 — Semifinal (Top 5) | 6 | 4-5 | ~26 |
| 7 — Final (Top 3) | 10 | 2 | 20 |

Feed this whole file to your AI builder alongside the main build prompt, with the instruction: *"Use this exact question bank as the seed data in hackiq.sql instead of generating your own placeholder questions."*
