-- HackIQ Cybersecurity Quiz Platform Database Schema & Seed Data
-- Designed for XAMPP (MySQL/MariaDB)

CREATE DATABASE IF NOT EXISTS `hackiq` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `hackiq`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `round_violations`;
DROP TABLE IF EXISTS `answers`;
DROP TABLE IF EXISTS `round_scores`;
DROP TABLE IF EXISTS `team_round_access`;
DROP TABLE IF EXISTS `questions`;
DROP TABLE IF EXISTS `rounds`;
DROP TABLE IF EXISTS `teams`;
DROP TABLE IF EXISTS `admins`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. Admins Table
CREATE TABLE `admins` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `admins` (`username`, `password_hash`) VALUES
('admin', '$2y$10$7oZ.rYijiBnEwZTDLET2ruRvkOaNKuwSoINJqQJPe9wedtad7dKjG');

-- 2. Teams Table
CREATE TABLE `teams` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `team_name` VARCHAR(100) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `members_info` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Rounds Table
CREATE TABLE `rounds` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `round_number` INT NOT NULL UNIQUE,
  `round_name` VARCHAR(150) NOT NULL,
  `description` TEXT NULL,
  `round_password_hash` VARCHAR(255) NOT NULL,
  `plain_password_hint` VARCHAR(50) NOT NULL,
  `time_limit_minutes` INT NOT NULL DEFAULT 15,
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `max_violations` INT NOT NULL DEFAULT 3,
  `current_question_index` INT NOT NULL DEFAULT 0,
  `is_live` TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `rounds` (`id`, `round_number`, `round_name`, `description`, `round_password_hash`, `plain_password_hint`, `time_limit_minutes`, `is_active`, `max_violations`, `current_question_index`, `is_live`) VALUES
(1, 1, 'Round 1: MCQ Warmup', 'Everyday cybersecurity MCQs covering passwords, public Wi-Fi, 2FA, scams, and safe browsing. 10 MCQs, 1 point each.', '$2y$10$fqnvOY.eZWUxG/MEdvXtD.So2R7AeJ3nCziYL4TPI7mwyUpn9WfD6', 'WARMUP26', 10, 1, 3, 0, 0),
(2, 2, 'Round 2: Rapid Fire True/False', 'Quick-fire True/False cybersecurity statements testing intuition and fundamentals. 10 statements, 1 point each.', '$2y$10$eIQwDSihN3mrCofoGW9Ryu.jDprI0vYJPnqsH1/PbBDBhSCer2R1G', 'RAPID26', 10, 1, 3, 0, 0),
(3, 3, 'Round 3: Spot the Phishing', 'Inspect simulated real-world email, SMS, and login pages to identify red flags and phishing indicators. 10 items, 2 points each.', '$2y$10$BW/mC19W.uQ./d9MEdC0U.H78JGXvhn5kHeRqtBcYrIwMTj94ijY.', 'PHISH26', 15, 1, 3, 0, 0),
(4, 4, 'Round 4: Decode the Message', 'Decrypt hidden messages encoded using Caesar Ciphers, ROT13, and Atbash substitution. 10 ciphers, 3 points each.', '$2y$10$D7JV9hW0OtD19PxI2kMP3OZmS6sCJjB6hXOsEs0/3YzC1.CbGPMza', 'CIPHER26', 15, 1, 3, 0, 0),
(5, 5, 'Round 5: Case Study Pitch', 'Real-world incident response pitch. 2 minutes prep, 2 minutes pitch to the jury. 10 points max. Top 5 teams advance to Semifinals!', '$2y$10$/hhY.s0GWmnOtT2.EFreB.sju0oLrTy8GkgKTdV.1ARIgBJFk9OX2', 'PITCH26', 15, 1, 3, 0, 1),
(6, 6, 'Round 6: Semifinal Challenge', 'Part A: Speed Round (10 pts) + Part B: Explain to Jury (15 pts). Restricted to Top 5 teams. Top 3 teams advance to Finals!', '$2y$10$c743ZK7u/eM/VthSAIHEBuL88u7YyPrcHUUPKLGjJeqghEEV/I3Xa', 'SEMI26', 20, 1, 3, 0, 1),
(7, 7, 'Round 7: Final Showdown (Live Breach)', 'The Live Breach Room: multi-stage college portal incident decision simulation + tiebreaker. Restricted to Top 3 teams.', '$2y$10$4YSuR.obPtfiM287DNNr4OvGJis.pab0BYwtIFulBWF9YXe8rN/oq', 'FINAL26', 15, 1, 3, 0, 1);

-- 4. Questions Table
CREATE TABLE `questions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `round_id` INT NOT NULL,
  `question_text` TEXT NOT NULL,
  `question_type` ENUM('mcq','true_false','phishing','cipher','scenario','pitch','buzzer') NOT NULL DEFAULT 'mcq',
  `option_a` TEXT NOT NULL,
  `option_b` TEXT NOT NULL,
  `option_c` TEXT NULL,
  `option_d` TEXT NULL,
  `correct_answer` VARCHAR(50) NOT NULL,
  `points` INT NOT NULL DEFAULT 1,
  `order_num` INT NOT NULL DEFAULT 1,
  `meta_info` TEXT NULL,
  CONSTRAINT `fk_questions_round` FOREIGN KEY (`round_id`) REFERENCES `rounds` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Team Round Access Table (Logs per-round password unlock)
CREATE TABLE `team_round_access` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `team_id` INT NOT NULL,
  `round_id` INT NOT NULL,
  `unlocked_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `started_at` TIMESTAMP NULL,
  UNIQUE KEY `unique_team_access` (`team_id`, `round_id`),
  CONSTRAINT `fk_access_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_access_round` FOREIGN KEY (`round_id`) REFERENCES `rounds` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Answers Table
CREATE TABLE `answers` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `team_id` INT NOT NULL,
  `question_id` INT NOT NULL,
  `round_id` INT NOT NULL,
  `selected_answer` TEXT NOT NULL,
  `is_correct` TINYINT(1) NOT NULL DEFAULT 0,
  `points_awarded` INT NOT NULL DEFAULT 0,
  `buzzed_at` TIMESTAMP NULL,
  `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_team_question` (`team_id`, `question_id`),
  CONSTRAINT `fk_answers_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_answers_question` FOREIGN KEY (`question_id`) REFERENCES `questions` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_answers_round` FOREIGN KEY (`round_id`) REFERENCES `rounds` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. Round Scores Table
CREATE TABLE `round_scores` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `team_id` INT NOT NULL,
  `round_id` INT NOT NULL,
  `total_correct` INT NOT NULL DEFAULT 0,
  `total_points` INT NOT NULL DEFAULT 0,
  `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `flagged` TINYINT(1) NOT NULL DEFAULT 0,
  `manual_override` TINYINT(1) NOT NULL DEFAULT 0,
  `admin_notes` TEXT NULL,
  UNIQUE KEY `unique_team_round_score` (`team_id`, `round_id`),
  CONSTRAINT `fk_scores_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_scores_round` FOREIGN KEY (`round_id`) REFERENCES `rounds` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. Round Violations Table (Proctoring strike enforcement)
CREATE TABLE `round_violations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `team_id` INT NOT NULL,
  `round_id` INT NOT NULL,
  `violation_type` ENUM('right_click','copy_attempt','devtools_key','tab_switch','fullscreen_exit') NOT NULL,
  `occurred_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_violations_team` FOREIGN KEY (`team_id`) REFERENCES `teams` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_violations_round` FOREIGN KEY (`round_id`) REFERENCES `rounds` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Helper View for Total Scores across all submitted rounds
CREATE OR REPLACE VIEW `team_totals` AS
SELECT 
    t.id AS team_id,
    t.team_name,
    COALESCE(SUM(rs.total_points), 0) AS grand_total,
    COALESCE(SUM(rs.total_correct), 0) AS total_correct,
    MAX(rs.submitted_at) AS last_submitted_at
FROM `teams` t
LEFT JOIN `round_scores` rs ON t.id = rs.team_id
GROUP BY t.id, t.team_name;

-- Insert Questions for All 7 Rounds
INSERT INTO `questions` (`round_id`, `question_text`, `question_type`, `option_a`, `option_b`, `option_c`, `option_d`, `correct_answer`, `points`, `order_num`, `meta_info`) VALUES
(1, 'What makes a password strong?', 'mcq', 'Your pet\'s name', 'A mix of upper/lower case, numbers, and symbols', 'Your birth year', 'The word \"password123\"', 'B', 1, 1, NULL),
(1, 'What does 2FA stand for?', 'mcq', 'Two-Factor Authentication', 'Two-File Access', 'Firewall Authorization', 'Fast Access', 'A', 1, 2, NULL),
(1, 'Which of these is the safest password?', 'mcq', '123456', 'qwerty', 'Tr@ck!ng_Sunset92', 'password', 'C', 1, 3, NULL),
(1, 'Is it safe to log into your bank account on public airport WiFi?', 'mcq', 'Yes, always', 'Only if the WiFi has a password', 'No, avoid it or use a VPN', 'Yes, if the site has \"http\"', 'C', 1, 4, NULL),
(1, 'Which of these is a sign of a phishing email?', 'mcq', 'Comes from a known colleague', 'Creates urgency and asks you to click a link immediately', 'Has no attachments', 'Is well-formatted', 'B', 1, 5, NULL),
(1, 'What is \"shoulder surfing\"?', 'mcq', 'A type of malware', 'Someone watching you enter a password/PIN in person', 'A social media trend', 'A firewall setting', 'B', 1, 6, NULL),
(1, 'Which is the best practice for passwords across different accounts?', 'mcq', 'Use the same password everywhere for convenience', 'Use a unique password for each account', 'Write all passwords on a sticky note', 'Share passwords with close friends', 'B', 1, 7, NULL),
(1, 'What does HTTPS in a website URL indicate?', 'mcq', 'The site is definitely safe', 'The connection is encrypted', 'The site is government-owned', 'The site has no ads', 'B', 1, 8, NULL),
(1, 'What is a common sign of a scam call/message?', 'mcq', 'It thanks you for being a loyal customer', 'It asks for OTP or bank details urgently', 'It\'s from a saved contact', 'It has correct grammar', 'B', 1, 9, NULL),
(1, 'What is \"phishing\"?', 'mcq', 'A method of catching fish online', 'Tricking someone into revealing sensitive info via fake communication', 'A type of firewall', 'An antivirus feature', 'B', 1, 10, NULL),
(2, 'Using the same password everywhere is safe.', 'true_false', 'True', 'False', NULL, NULL, 'B', 1, 1, NULL),
(2, 'Public WiFi is fine for online banking.', 'true_false', 'True', 'False', NULL, NULL, 'B', 1, 2, NULL),
(2, 'Enabling 2FA makes your account more secure.', 'true_false', 'True', 'False', NULL, NULL, 'A', 1, 3, NULL),
(2, 'A padlock icon in the browser guarantees a site is 100% safe.', 'true_false', 'True', 'False', NULL, NULL, 'B', 1, 4, NULL),
(2, 'You should never share your OTP with anyone, even someone claiming to be from your bank.', 'true_false', 'True', 'False', NULL, NULL, 'A', 1, 5, NULL),
(2, 'Updating your software regularly helps protect against known vulnerabilities.', 'true_false', 'True', 'False', NULL, NULL, 'A', 1, 6, NULL),
(2, 'It\'s fine to click links in emails claiming you won a lottery you never entered.', 'true_false', 'True', 'False', NULL, NULL, 'B', 1, 7, NULL),
(2, 'A strong password should include a mix of letters, numbers, and symbols.', 'true_false', 'True', 'False', NULL, NULL, 'A', 1, 8, NULL),
(2, 'Logging out of shared/public computers after use is unnecessary.', 'true_false', 'True', 'False', NULL, NULL, 'B', 1, 9, NULL),
(2, 'Free public charging stations (USB) can be a security risk (\"juice jacking\").', 'true_false', 'True', 'False', NULL, NULL, 'A', 1, 10, NULL),
(3, 'Email from: support@paypa1-secure.com
Subject: \"Your account has been LIMITED — verify within 24 hours!\"
Contains link: bit.ly/paypal-verify-now', 'phishing', 'Genuine', 'Phishing — misspelled domain, urgency, shortened link', 'Genuine, has PayPal logo', 'Can\'t tell', 'B', 2, 1, '{\"type\":\"email\",\"from\":\"support@paypa1-secure.com\",\"subject\":\"Your account has been LIMITED\",\"preview\":\"Verify within 24 hours or lose access! Click: bit.ly\\/paypal-verify-now\"}'),
(3, 'SMS from \"BANK\": \"Your a/c will be blocked today. Update KYC immediately: hxxp://kyc-verify-bank.ml/update\"', 'phishing', 'Genuine', 'Phishing — suspicious domain, urgency, generic greeting', 'Genuine, banks send SMS often', 'Can\'t tell', 'B', 2, 2, '{\"type\":\"sms\",\"from\":\"BANK-ALERT\",\"subject\":\"KYC Alert\",\"preview\":\"Dear customer, your a\\/c will be blocked today. Update KYC immediately: hxxp:\\/\\/kyc-verify-bank.ml\\/update\"}'),
(3, 'Email from: hr@yourcompany.com
Subject: \"Updated Leave Policy — attached PDF, review by Friday.\"
No external links, verified internal HR sender.', 'phishing', 'Genuine — internal domain, no urgency, no suspicious link', 'Phishing', 'Genuine only because of the PDF', 'Can\'t tell', 'A', 2, 3, '{\"type\":\"email\",\"from\":\"hr@yourcompany.com\",\"subject\":\"Updated Leave Policy 2026\",\"preview\":\"Please review the attached leave policy PDF by Friday. Reach out with questions.\"}'),
(3, 'Login page that looks exactly like Instagram, but the URL in the address bar is: instagram-login-secure.net', 'phishing', 'Genuine', 'Phishing — fake domain hosting a cloned login page', 'Genuine, Instagram uses many domains', 'Can\'t tell', 'B', 2, 4, '{\"type\":\"web\",\"from\":\"Browser Address Bar\",\"subject\":\"Login Screen\",\"preview\":\"URL: https:\\/\\/instagram-login-secure.net\\/login - Username & Password form identical to Instagram\"}'),
(3, 'Email from: no-reply@netflix.com
Subject: \"Your payment failed\"
Professional tone, hover link confirms destination is netflix.com/account/billing', 'phishing', 'Genuine — real domain, professional tone, no pressure', 'Phishing, all payment emails are scams', 'Genuine only because of logo', 'Can\'t tell', 'A', 2, 5, '{\"type\":\"email\",\"from\":\"no-reply@netflix.com\",\"subject\":\"Your payment method could not be charged\",\"preview\":\"Please update your payment info at netflix.com\\/account\\/billing to prevent service interruption.\"}'),
(3, 'WhatsApp from unknown number: \"Hi, I\'m from your college placement cell. Register here: tinyurl.com/placement-2026-urgent\"', 'phishing', 'Genuine', 'Phishing — unknown number, urgency, shortened link, unofficial channel', 'Genuine, mentions college', 'Can\'t tell', 'B', 2, 6, '{\"type\":\"chat\",\"from\":\"+91 98765 43210 (Unknown)\",\"subject\":\"WhatsApp Message\",\"preview\":\"Hi, Placement cell exclusive drive for final years! Register immediately: tinyurl.com\\/placement-2026-urgent\"}'),
(3, 'Email from: security@google.com (domain authenticated)
\"New sign-in detected from Chennai. If not you, secure your account.\" Link: myaccount.google.com', 'phishing', 'Genuine — verified domain, real format, link matches', 'Phishing, all such emails are fake', 'Genuine only if you\'re in Chennai', 'Can\'t tell', 'A', 2, 7, '{\"type\":\"email\",\"from\":\"security@google.com\",\"subject\":\"Security alert: New sign-in on Windows device\",\"preview\":\"We detected a sign-in from Chennai, India. If this was you, ignore. If not: myaccount.google.com\"}'),
(3, 'Email: \"CONGRATULATIONS!!! You WON an iPhone 16!! Claim before it EXPIRES in 10 mins!! Pay ₹99 shipping with your card.\"', 'phishing', 'Genuine', 'Phishing — urgency, unearned prize, asks for card details', 'Genuine, only the timer is fake', 'Can\'t tell', 'B', 2, 8, '{\"type\":\"email\",\"from\":\"prize-winner-dept@free-claims-fast.top\",\"subject\":\"CONGRATULATIONS: You WON an iPhone 16!!\",\"preview\":\"Claim NOW in 10 minutes! Enter card details to pay \\u20b999 express delivery fee.\"}'),
(3, 'Delivery SMS: \"Your parcel is on hold due to unpaid customs fee of ₹49. Pay here: delivery-fee-pay.xyz\"', 'phishing', 'Genuine, couriers do this', 'Phishing — odd domain, small urgent payment request, unsolicited', 'Genuine if you\'re expecting a parcel', 'Can\'t tell', 'B', 2, 9, '{\"type\":\"sms\",\"from\":\"IN-POST-SRV\",\"subject\":\"Package Pending\",\"preview\":\"Your parcel #92812 is on hold due to unpaid customs duty \\u20b949. Clear fee: delivery-fee-pay.xyz\"}'),
(3, 'LinkedIn message from a real-looking recruiter profile, no links, asking to schedule a call via LinkedIn itself to discuss an internship.', 'phishing', 'Genuine — normal recruiting behavior, no red flags', 'Phishing, all unsolicited messages are scams', 'Genuine only if you already applied', 'Can\'t tell', 'A', 2, 10, '{\"type\":\"chat\",\"from\":\"Priya Sharma (Tech Recruiter)\",\"subject\":\"LinkedIn InMail\",\"preview\":\"Hi, I reviewed your profile and wanted to discuss our upcoming summer internship. Let me know if you are open for a brief call via LinkedIn.\"}'),
(4, 'Caesar Cipher (+3 shift):
Ciphertext: VWDB DOHUW', 'cipher', 'STAY ALERT', 'STAY AWAKE', 'BE ALERT', 'ACT SMART', 'A', 3, 1, 'Caesar +3'),
(4, 'ROT13 Cipher:
Ciphertext: OR FNSR', 'cipher', 'BE SAFE', 'BE SURE', 'BE SMART', 'BE SAFE NOW', 'A', 3, 2, 'ROT13'),
(4, 'Caesar Cipher (+5 shift):
Ciphertext: YMNSP YBNHJ', 'cipher', 'THINK TWICE', 'THINK ONCE', 'ACT TWICE', 'THINK FAST', 'A', 3, 3, 'Caesar +5'),
(4, 'Atbash Cipher (A=Z, B=Y, C=X...):
Ciphertext: KZHHDLIW HZUVGB', 'cipher', 'PASSWORD SAFETY', 'PASSWORD SECURE', 'PROTECT SAFETY', 'PASSCODE SAFETY', 'A', 3, 4, 'Atbash'),
(4, 'ROT13 Cipher:
Ciphertext: ARIRE FUNER BGC', 'cipher', 'NEVER SHARE OTP', 'ALWAYS SHARE OTP', 'NEVER TRUST OTP', 'NEVER FORGET OTP', 'A', 3, 5, 'ROT13'),
(4, 'Caesar Cipher (+2 shift):
Ciphertext: NQEM AQWT UETGGP', 'cipher', 'LOCK YOUR SCREEN', 'LOCK YOUR PHONE', 'HIDE YOUR SCREEN', 'CHECK YOUR SCREEN', 'A', 3, 6, 'Caesar +2'),
(4, 'ROT13 Cipher:
Ciphertext: GEHFG AB YVAX', 'cipher', 'TRUST NO LINK', 'CLICK NO LINK', 'TRUST ALL LINKS', 'AVOID ALL LINKS', 'A', 3, 7, 'ROT13'),
(4, 'Caesar Cipher (+4 shift):
Ciphertext: WXEC WLEVT', 'cipher', 'STAY SHARP', 'STAY SAFE', 'BE SHARP', 'ACT SHARP', 'A', 3, 8, 'Caesar +4'),
(4, 'Atbash Cipher (A=Z, B=Y, C=X...):
Ciphertext: GIFHG YFG EVIRUB', 'cipher', 'TRUST BUT VERIFY', 'TRUST AND VERIFY', 'DOUBT AND VERIFY', 'TRUST BUT IGNORE', 'A', 3, 9, 'Atbash'),
(4, 'Caesar Cipher (+6 shift):
Ciphertext: INKIQ ZNK YKTJKX', 'cipher', 'CHECK THE SENDER', 'CHECK THE SUBJECT', 'TRUST THE SENDER', 'BLOCK THE SENDER', 'A', 3, 10, 'Caesar +6'),
(5, 'Case 1: Compromised Club Social Media:
A college club\'s Instagram account got hacked and is now posting spam links to followers. What should they do immediately to recover it and prevent a repeat occurrence?', 'pitch', 'Pitch Prepared', 'Live Jury Evaluation', 'Whiteboard/Verbal Pitch', 'Rubric: 10 Points Max', 'JURY', 10, 1, 'Rubric: Risk Understanding (0-4 pts) | Realistic Solution (0-4 pts) | Communication (0-2 pts)'),
(5, 'Case 2: Fake Job Offer Scam:
A student received a \'job offer\' demanding an advance deposit of ₹2,500 to \'confirm the seat and interview slot\'. Design a 3-step awareness action to protect other students from falling prey.', 'pitch', 'Pitch Prepared', 'Live Jury Evaluation', 'Whiteboard/Verbal Pitch', 'Rubric: 10 Points Max', 'JURY', 10, 2, 'Rubric: Risk Understanding (0-4 pts) | Realistic Solution (0-4 pts) | Communication (0-2 pts)'),
(5, 'Case 3: Unsecured Campus WiFi:
Your college campus WiFi has no login portal or device isolation — anyone nearby can connect and sniff traffic. Propose a practical, phased policy to secure it without disrupting students.', 'pitch', 'Pitch Prepared', 'Live Jury Evaluation', 'Whiteboard/Verbal Pitch', 'Rubric: 10 Points Max', 'JURY', 10, 3, 'Rubric: Risk Understanding (0-4 pts) | Realistic Solution (0-4 pts) | Communication (0-2 pts)'),
(5, 'Case 4: Stolen Phone with Banking Apps:
A friend\'s phone was stolen at a festival. It had active UPI/banking apps and no lock screen PIN. What urgent actions must be taken in the first 60 minutes, and over the following week?', 'pitch', 'Pitch Prepared', 'Live Jury Evaluation', 'Whiteboard/Verbal Pitch', 'Rubric: 10 Points Max', 'JURY', 10, 4, 'Rubric: Risk Understanding (0-4 pts) | Realistic Solution (0-4 pts) | Communication (0-2 pts)'),
(5, 'Case 5: Tampered Payment QR Code:
Fraudsters pasted fake payment QR stickers over genuine merchant stickers at the college canteen, routing student money to their account. How should the college help the canteen prevent and detect this?', 'pitch', 'Pitch Prepared', 'Live Jury Evaluation', 'Whiteboard/Verbal Pitch', 'Rubric: 10 Points Max', 'JURY', 10, 5, 'Rubric: Risk Understanding (0-4 pts) | Realistic Solution (0-4 pts) | Communication (0-2 pts)'),
(5, 'Case 6: Family WhatsApp Scam Prevention:
An elderly family member frequently clicks and forwards \'You won ₹50 Lakhs from RBI\' WhatsApp forward links. Design a gentle, effective 3-step guide to teach them how to identify scams independently.', 'pitch', 'Pitch Prepared', 'Live Jury Evaluation', 'Whiteboard/Verbal Pitch', 'Rubric: 10 Points Max', 'JURY', 10, 6, 'Rubric: Risk Understanding (0-4 pts) | Realistic Solution (0-4 pts) | Communication (0-2 pts)'),
(5, 'Case 7: Student Fest Data Protection:
The college fest committee is creating an online registration website collecting names, phone numbers, college IDs, and payment screenshots. What privacy and cybersecurity practices must they implement?', 'pitch', 'Pitch Prepared', 'Live Jury Evaluation', 'Whiteboard/Verbal Pitch', 'Rubric: 10 Points Max', 'JURY', 10, 7, 'Rubric: Risk Understanding (0-4 pts) | Realistic Solution (0-4 pts) | Communication (0-2 pts)'),
(5, 'Case 8: Leaked Internal Credentials:
A classmate accidentally posted the hostel internal network management password in a public WhatsApp group with 500+ unknown members. What incident response steps must be executed immediately?', 'pitch', 'Pitch Prepared', 'Live Jury Evaluation', 'Whiteboard/Verbal Pitch', 'Rubric: 10 Points Max', 'JURY', 10, 8, 'Rubric: Risk Understanding (0-4 pts) | Realistic Solution (0-4 pts) | Communication (0-2 pts)'),
(5, 'Case 9: Suspicious Malware Infection:
A student clicked an urgent link in a spoofed email pretending to be from the exam department. Their laptop now exhibits pop-up ads, disabled antivirus, and high CPU usage. Detail step-by-step mitigation.', 'pitch', 'Pitch Prepared', 'Live Jury Evaluation', 'Whiteboard/Verbal Pitch', 'Rubric: 10 Points Max', 'JURY', 10, 9, 'Rubric: Risk Understanding (0-4 pts) | Realistic Solution (0-4 pts) | Communication (0-2 pts)'),
(5, 'Case 10: Small Business UPI Fraud Defense:
A campus stationery shop owner wants to accept UPI payments but is terrified of fake transaction screenshots and soundbox spoofing. Provide realistic safeguards for the merchant.', 'pitch', 'Pitch Prepared', 'Live Jury Evaluation', 'Whiteboard/Verbal Pitch', 'Rubric: 10 Points Max', 'JURY', 10, 10, 'Rubric: Risk Understanding (0-4 pts) | Realistic Solution (0-4 pts) | Communication (0-2 pts)'),
(6, 'What is \"spear phishing\"?', 'mcq', 'Phishing targeted at a specific person using info about them', 'Phishing sent using emojis', 'A firewall technique', 'A type of antivirus', 'A', 2, 1, 'Part A: Speed Round'),
(6, 'What is the safest way to verify a bank\'s urgent notification?', 'mcq', 'Call the phone number provided directly inside their message', 'Call the official number printed on your card or the official website', 'Reply directly to the SMS asking for verification', 'Trust the message if it displays the bank\'s official logo', 'B', 2, 2, 'Part A: Speed Round'),
(6, 'What is a common giveaway of a fake login page?', 'mcq', 'The URL does not exactly match the legitimate site\'s domain', 'The page uses colorful backgrounds', 'The page loads quickly', 'It includes a login button', 'A', 2, 3, 'Part A: Speed Round'),
(6, 'A \"friend\" urgently messages on social media asking for money for an emergency. What is the best first step?', 'mcq', 'Send the requested amount right away', 'Verify their situation by calling them directly on their known phone number', 'Ask them to send their bank account details first', 'Ignore the message permanently', 'B', 2, 4, 'Part A: Speed Round'),
(6, 'Why is reusing passwords across different accounts dangerous?', 'mcq', 'It causes slower login speeds', 'A breach in one service can compromise all linked accounts', 'Websites block repeated passwords automatically', 'It carries no risk if the password is long', 'B', 2, 5, 'Part A: Speed Round'),
(6, 'A padlock icon in the browser always means the site is 100% legitimate.', 'true_false', 'True', 'False', NULL, NULL, 'B', 1, 6, 'Part A: Speed Round'),
(6, 'Two-factor authentication can still be bypassed if you\'re tricked into approving a fake prompt.', 'true_false', 'True', 'False', NULL, NULL, 'A', 1, 7, 'Part A: Speed Round'),
(6, 'Deleting a phishing email is enough — there is no need to report it to security teams.', 'true_false', 'True', 'False', NULL, NULL, 'B', 1, 8, 'Part A: Speed Round'),
(6, 'Attackers often research a target\'s public social media before launching targeted social engineering.', 'true_false', 'True', 'False', NULL, NULL, 'A', 1, 9, 'Part A: Speed Round'),
(6, 'It\'s fine to reuse your college academic email password for personal gaming/social media accounts.', 'true_false', 'True', 'False', NULL, NULL, 'B', 1, 10, 'Part A: Speed Round'),
(7, 'Stage 1: The Initial Report
Students report they cannot log in to the student portal — they see a login screen that looks like the portal but feels slightly off (different font, slight typo in URL). What does your team do first?', 'scenario', 'Ignore it and wait for IT to notice', 'Immediately alert IT/admin and broadcast an emergency alert warning students not to enter credentials', 'Post screenshots publicly on social media to complain', 'Try logging in yourself using your real credentials to test if it works', 'B', 4, 1, 'scenario'),
(7, 'Stage 2: Breach Containment
IT confirms it is a cloned phishing site hosted on an external server. Several students have already entered their credentials. What is the immediate next step?', 'scenario', 'Nothing, it is exclusively IT\'s problem now', 'Advise all affected students to immediately change passwords on verified systems and revoke active sessions', 'Blame and publicly name the students who fell for the scam', 'Shut down all campus internet access entirely for the week', 'B', 4, 2, 'scenario'),
(7, 'Stage 3: Secondary Wave
The attacker sends an emergency mass email posing as \'College Chief IT Officer\', urging all students to immediately download a \'security patch utility\' to protect their computers. What do you do?', 'scenario', 'Forward the email to student group chats just in case', 'Issue an immediate verified counter-notice through official channels warning that IT never sends exe patches via email, and block the sender domain', 'Wait and see if anyone\'s laptop crashes first', 'Download and execute the file to analyze what it does', 'B', 4, 3, 'scenario'),
(7, 'Stage 4: Public & Media Inquiry
A local tech journalist calls student representatives asking for details about \'the massive cyber breach on campus\' for a breaking news headline. How should you respond?', 'scenario', 'Share all internal chat logs, speculation, and technical conjectures immediately', 'Direct the inquiry strictly to the designated college media spokesperson and official administration team', 'Deny that any cyber incident occurred at all', 'Give personal, unverified statements on the record', 'B', 4, 4, 'scenario'),
(7, 'Stage 5: Final Tiebreaker & Executive Briefing
In one sentence, what is the single most critical structural lesson from this unfolding incident to safeguard the college ecosystem against future attacks?', 'scenario', 'Implement mandatory Multi-Factor Authentication (MFA), domain security controls, and proactive student security awareness', 'Ban student portal access permanently from mobile phones', 'Switch back to paper registers for all student records', 'Block all external internet access for students', 'A', 10, 5, 'scenario');
