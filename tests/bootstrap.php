<?php declare(strict_types=1);
/**
 * PHPUnit Bootstrap — loads all application classes before tests run.
 * Uses a dedicated MySQL test database (nexthire_test) so tests never
 * touch the production DB. Each test case rebuilds the schema from scratch.
 */

// ── Vendor autoload ──────────────────────────────────────────────────────────
require_once __DIR__ . '/../vendor/autoload.php';

// ── Base test case (must be loaded before individual test files) ───────────────
require_once __DIR__ . '/NextHireTestCase.php';

// ── Application constants ─────────────────────────────────────────────────────
// To override PDF_OFFER_DIR for tests, we define it here, and config/app.php will skip it.
if (!defined('PDF_OFFER_DIR')) {
    define('PDF_OFFER_DIR', sys_get_temp_dir() . '/nexthire_test_offers');
}

// ── Load core files ───────────────────────────────────────────────────────────
// app.php must load FIRST — it defines all constants. The if(!defined) guards
// above are fallbacks only in case the test runner skips config loading.
$base = __DIR__ . '/..';
require_once $base . '/config/app.php';   // defines APP_STAGES, HIRE_THRESHOLDS, etc.
require_once $base . '/core/Database.php';
require_once $base . '/core/EventBus.php';
require_once $base . '/core/AuditLogger.php';
require_once $base . '/core/StateMachine.php';
require_once $base . '/core/BaseModel.php';
require_once $base . '/core/EmailService.php';

foreach (glob($base . '/models/*.php')     as $f) require_once $f;
foreach (glob($base . '/services/*/*.php') as $f) require_once $f;

// ── Build test-database PDO (nexthire_test) ───────────────────────────────────
function createTestPdo(): \PDO {
    $config = require __DIR__ . '/../config/database.php';
    $dsn    = sprintf('mysql:host=%s;port=%s;charset=%s', $config['host'], $config['port'], $config['charset']);

    $pdo = new \PDO($dsn, $config['username'], $config['password'], [
        \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    ]);

    $pdo->exec("CREATE DATABASE IF NOT EXISTS nexthire_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE nexthire_test");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

    // Drop and recreate the tables we test against
    $drops = [
        'session_extension_requests','hiring_recommendations','feedback_dimensions',
        'feedback_submissions','offers','pipeline_stages_log','applications',
        'interview_panels','job_skills','job_requisitions','audit_log','users',
        'assessments','candidate_sessions',
    ];
    foreach ($drops as $t) {
        $pdo->exec("DROP TABLE IF EXISTS `$t`");
    }
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    // Create tables
    $pdo->exec("
        CREATE TABLE users (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            role ENUM('hr_admin','interviewer','candidate','dept_manager','shadow') NOT NULL DEFAULT 'candidate',
            department VARCHAR(100) DEFAULT NULL,
            seniority ENUM('junior','mid','senior','lead') DEFAULT NULL,
            specializations JSON DEFAULT NULL,
            cv_path VARCHAR(500) DEFAULT NULL,
            document_links JSON DEFAULT NULL,
            diversity_gender VARCHAR(50) DEFAULT NULL,
            diversity_ethnicity VARCHAR(100) DEFAULT NULL,
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            last_login_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $pdo->exec("
        CREATE TABLE job_requisitions (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            department VARCHAR(100) NOT NULL,
            description TEXT NOT NULL,
            requirements TEXT DEFAULT NULL,
            location_tier ENUM('tier1','tier2','tier3') NOT NULL DEFAULT 'tier1',
            level ENUM('L1','L2','L3','L4','L5','L6') NOT NULL DEFAULT 'L3',
            role_type VARCHAR(100) DEFAULT NULL,
            status ENUM('draft','pending_approval','live','closed','cancelled') NOT NULL DEFAULT 'draft',
            approval_chain_json JSON DEFAULT NULL,
            template_id INT UNSIGNED DEFAULT NULL,
            created_by INT UNSIGNED NOT NULL,
            version INT UNSIGNED NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $pdo->exec("
        CREATE TABLE job_skills (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            job_id INT UNSIGNED NOT NULL,
            skill_name VARCHAR(150) NOT NULL,
            weight DECIMAL(5,2) NOT NULL DEFAULT 1.00,
            is_required TINYINT(1) NOT NULL DEFAULT 0,
            FOREIGN KEY (job_id) REFERENCES job_requisitions(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $pdo->exec("
        CREATE TABLE applications (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            job_id INT UNSIGNED NOT NULL,
            candidate_id INT UNSIGNED NOT NULL,
            stage ENUM('applied','screening','technical_test','interview','offer','hired','rejected') NOT NULL DEFAULT 'applied',
            source ENUM('direct','linkedin','indeed','glassdoor','referral','other') NOT NULL DEFAULT 'direct',
            referral_code VARCHAR(64) DEFAULT NULL,
            resume_text LONGTEXT DEFAULT NULL,
            match_score DECIMAL(5,2) DEFAULT NULL,
            is_frozen TINYINT(1) NOT NULL DEFAULT 0,
            duplicate_of INT UNSIGNED DEFAULT NULL,
            applied_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (job_id) REFERENCES job_requisitions(id) ON DELETE RESTRICT,
            FOREIGN KEY (candidate_id) REFERENCES users(id) ON DELETE RESTRICT,
            FOREIGN KEY (duplicate_of) REFERENCES applications(id) ON DELETE SET NULL,
            UNIQUE KEY uq_job_candidate (job_id, candidate_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $pdo->exec("
        CREATE TABLE pipeline_stages_log (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            application_id INT UNSIGNED NOT NULL,
            from_stage VARCHAR(50) DEFAULT NULL,
            to_stage VARCHAR(50) NOT NULL,
            actor_id INT UNSIGNED NOT NULL,
            reason TEXT DEFAULT NULL,
            changed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
            FOREIGN KEY (actor_id) REFERENCES users(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $pdo->exec("
        CREATE TABLE interview_panels (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            job_id INT UNSIGNED NOT NULL,
            application_id INT UNSIGNED NOT NULL,
            scheduled_at DATETIME NOT NULL,
            timezone VARCHAR(64) NOT NULL DEFAULT 'UTC',
            duration_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 60,
            extended_by_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            status ENUM('scheduled','active','completed','cancelled') NOT NULL DEFAULT 'scheduled',
            meeting_link VARCHAR(500) DEFAULT NULL,
            candidate_token VARCHAR(128) DEFAULT NULL,
            coding_language VARCHAR(50) NOT NULL DEFAULT 'javascript',
            notes TEXT DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (job_id) REFERENCES job_requisitions(id) ON DELETE RESTRICT,
            FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $pdo->exec("
        CREATE TABLE feedback_submissions (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            panel_id INT UNSIGNED NOT NULL,
            interviewer_id INT UNSIGNED NOT NULL,
            candidate_id INT UNSIGNED NOT NULL,
            submitted_at DATETIME DEFAULT NULL,
            is_shadow TINYINT(1) NOT NULL DEFAULT 0,
            overall_notes TEXT DEFAULT NULL,
            score DECIMAL(5,2) DEFAULT NULL,
            comments TEXT DEFAULT NULL,
            submitter_role ENUM('interviewer','hr_admin','shadow') NOT NULL DEFAULT 'interviewer',
            include_in_score TINYINT(1) NOT NULL DEFAULT 1,
            UNIQUE KEY uq_panel_interviewer (panel_id, interviewer_id),
            FOREIGN KEY (panel_id) REFERENCES interview_panels(id) ON DELETE CASCADE,
            FOREIGN KEY (interviewer_id) REFERENCES users(id) ON DELETE RESTRICT,
            FOREIGN KEY (candidate_id) REFERENCES users(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $pdo->exec("
        CREATE TABLE feedback_dimensions (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            submission_id INT UNSIGNED NOT NULL,
            dimension ENUM('coding','system_design','communication','culture_fit') NOT NULL,
            score DECIMAL(4,2) NOT NULL,
            notes TEXT DEFAULT NULL,
            UNIQUE KEY uq_submission_dimension (submission_id, dimension),
            FOREIGN KEY (submission_id) REFERENCES feedback_submissions(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $pdo->exec("
        CREATE TABLE hiring_recommendations (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            application_id INT UNSIGNED NOT NULL UNIQUE,
            recommendation ENUM('strong_hire','hire','no_hire','strong_no_hire') NOT NULL,
            final_score DECIMAL(5,2) DEFAULT NULL,
            decided_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            decided_by INT UNSIGNED DEFAULT NULL,
            FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
            FOREIGN KEY (decided_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $pdo->exec("
        CREATE TABLE offers (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            application_id INT UNSIGNED NOT NULL UNIQUE,
            salary DECIMAL(12,2) NOT NULL,
            signing_bonus DECIMAL(12,2) NOT NULL DEFAULT 0.00,
            equity DECIMAL(8,4) NOT NULL DEFAULT 0.0000,
            status ENUM('pending','sent','accepted','declined','expired') NOT NULL DEFAULT 'pending',
            pdf_path VARCHAR(500) DEFAULT NULL,
            email_sent TINYINT(1) NOT NULL DEFAULT 0,
            expires_at DATETIME DEFAULT NULL,
            created_by INT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $pdo->exec("
        CREATE TABLE assessments (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            job_id INT UNSIGNED NOT NULL,
            title VARCHAR(255) NOT NULL,
            total_time_minutes INT UNSIGNED NOT NULL DEFAULT 60,
            cooldown_months INT UNSIGNED NOT NULL DEFAULT 3,
            cooldown_hours INT UNSIGNED NOT NULL DEFAULT 24,
            num_easy INT UNSIGNED NOT NULL DEFAULT 5,
            num_medium INT UNSIGNED NOT NULL DEFAULT 5,
            num_hard INT UNSIGNED NOT NULL DEFAULT 5,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (job_id) REFERENCES job_requisitions(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $pdo->exec("
        CREATE TABLE candidate_sessions (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            candidate_id INT UNSIGNED NOT NULL,
            assessment_id INT UNSIGNED NOT NULL,
            questions_json JSON DEFAULT NULL,
            current_code LONGTEXT DEFAULT NULL,
            started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            submitted_at DATETIME DEFAULT NULL,
            status ENUM('active','submitted','expired','flagged') NOT NULL DEFAULT 'active',
            integrity_score DECIMAL(5,2) NOT NULL DEFAULT 100.00,
            is_flagged TINYINT(1) NOT NULL DEFAULT 0,
            FOREIGN KEY (candidate_id) REFERENCES users(id) ON DELETE RESTRICT,
            FOREIGN KEY (assessment_id) REFERENCES assessments(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $pdo->exec("
        CREATE TABLE session_extension_requests (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            panel_id INT UNSIGNED NOT NULL,
            requested_by INT UNSIGNED NOT NULL,
            minutes TINYINT UNSIGNED NOT NULL DEFAULT 10,
            reason TEXT NOT NULL,
            status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
            decided_by INT UNSIGNED DEFAULT NULL,
            decided_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (panel_id) REFERENCES interview_panels(id) ON DELETE CASCADE,
            FOREIGN KEY (requested_by) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (decided_by) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $pdo->exec("
        CREATE TABLE audit_log (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            actor_id INT UNSIGNED DEFAULT NULL,
            entity_type VARCHAR(100) NOT NULL,
            entity_id INT UNSIGNED DEFAULT NULL,
            action VARCHAR(100) NOT NULL,
            before_state_json JSON DEFAULT NULL,
            after_state_json JSON DEFAULT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    return $pdo;
}
