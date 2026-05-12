CREATE DATABASE IF NOT EXISTS nexthire
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;


USE nexthire;


SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = 'STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';


DROP TABLE IF EXISTS session_extension_requests;
DROP TABLE IF EXISTS assessment_cooldowns;
DROP TABLE IF EXISTS referral_invites;
DROP TABLE IF EXISTS job_board_syncs;
DROP TABLE IF EXISTS sentiment_logs;
DROP TABLE IF EXISTS job_templates;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS audit_log;
DROP TABLE IF EXISTS referrals;
DROP TABLE IF EXISTS onboarding_checklist;
DROP TABLE IF EXISTS background_checks;
DROP TABLE IF EXISTS offer_negotiations;
DROP TABLE IF EXISTS offers;
DROP TABLE IF EXISTS hiring_recommendations;
DROP TABLE IF EXISTS red_flags;
DROP TABLE IF EXISTS feedback_dimensions;
DROP TABLE IF EXISTS feedback_submissions;
DROP TABLE IF EXISTS interviewer_slots;
DROP TABLE IF EXISTS panel_members;
DROP TABLE IF EXISTS live_sessions;
DROP TABLE IF EXISTS session_extensions;
DROP TABLE IF EXISTS interview_panels;
DROP TABLE IF EXISTS proctoring_events;
DROP TABLE IF EXISTS candidate_answers;
DROP TABLE IF EXISTS candidate_sessions;
DROP TABLE IF EXISTS questions;
DROP TABLE IF EXISTS assessments;
DROP TABLE IF EXISTS pipeline_stages_log;
DROP TABLE IF EXISTS applications;
DROP TABLE IF EXISTS job_skills;
DROP TABLE IF EXISTS job_requisitions;
DROP TABLE IF EXISTS roles_permissions;
DROP TABLE IF EXISTS email_log;
DROP TABLE IF EXISTS invite_tokens;
DROP TABLE IF EXISTS users;


CREATE TABLE users (
    id                  INT UNSIGNED        AUTO_INCREMENT PRIMARY KEY,
    name                VARCHAR(255)        NOT NULL,
    email               VARCHAR(255)        NOT NULL UNIQUE,
    password_hash       VARCHAR(255)        NOT NULL,
    role                ENUM('hr_admin','interviewer','candidate','dept_manager','shadow')
                                            NOT NULL DEFAULT 'candidate',
    department          VARCHAR(100)        DEFAULT NULL,
    seniority           ENUM('junior','mid','senior','lead')
                                            DEFAULT NULL,
    specializations     JSON                DEFAULT NULL
                            COMMENT 'Array of skill tags (interviewers)',
    cv_path             VARCHAR(500)        DEFAULT NULL
                            COMMENT 'Uploaded CV file path (candidates)',
    document_links      JSON                DEFAULT NULL
                            COMMENT 'GitHub, LinkedIn, Google Drive, Portfolio URLs',
    diversity_gender    VARCHAR(50)         DEFAULT NULL COMMENT 'Voluntary',
    diversity_ethnicity VARCHAR(100)        DEFAULT NULL COMMENT 'Voluntary',
    is_active           TINYINT(1)          NOT NULL DEFAULT 1,
    last_login_at       DATETIME            DEFAULT NULL,
    created_at          DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP
                            ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE roles_permissions (
    id          INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    role        ENUM('hr_admin','interviewer','candidate','dept_manager','shadow')
                                NOT NULL,
    permission  VARCHAR(100)    NOT NULL,
    granted     TINYINT(1)      NOT NULL DEFAULT 1,
    UNIQUE KEY uq_role_permission (role, permission)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE invite_tokens (
    id          INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    token       VARCHAR(128)    NOT NULL UNIQUE,
    target_role ENUM('hr_admin','interviewer','dept_manager','shadow')
                                NOT NULL,
    created_by  INT UNSIGNED    NOT NULL,
    used_by     INT UNSIGNED    DEFAULT NULL,
    used_at     DATETIME        DEFAULT NULL,
    expires_at  DATETIME        NOT NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT,
    FOREIGN KEY (used_by)    REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE email_log (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    recipient       VARCHAR(255)    NOT NULL,
    subject         VARCHAR(500)    NOT NULL,
    body_html       LONGTEXT        DEFAULT NULL,
    status          ENUM('queued','sent','failed')
                                    NOT NULL DEFAULT 'queued',
    related_entity  VARCHAR(100)    DEFAULT NULL,
    related_id      INT UNSIGNED    DEFAULT NULL,
    sent_at         DATETIME        DEFAULT NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE job_requisitions (
    id                  INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    title               VARCHAR(255)    NOT NULL,
    department          VARCHAR(100)    NOT NULL,
    description         TEXT            NOT NULL,
    requirements        TEXT            DEFAULT NULL,
    location_tier       ENUM('tier1','tier2','tier3')
                                        NOT NULL DEFAULT 'tier1',
    level               ENUM('L1','L2','L3','L4','L5','L6')
                                        NOT NULL DEFAULT 'L3',
    role_type           VARCHAR(100)    DEFAULT NULL,
    status              ENUM('draft','pending_approval','live','closed','cancelled')
                                        NOT NULL DEFAULT 'draft',
    approval_chain_json JSON            DEFAULT NULL
                            COMMENT 'Ordered array of approver user_ids with status',
    template_id         INT UNSIGNED    DEFAULT NULL,
    created_by          INT UNSIGNED    NOT NULL,
    version             INT UNSIGNED    NOT NULL DEFAULT 1,
    created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                            ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE job_skills (
    id          INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    job_id      INT UNSIGNED    NOT NULL,
    skill_name  VARCHAR(150)    NOT NULL,
    weight      DECIMAL(5,2)    NOT NULL DEFAULT 1.00 COMMENT '0.00 – 10.00',
    is_required TINYINT(1)      NOT NULL DEFAULT 0,
    FOREIGN KEY (job_id) REFERENCES job_requisitions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE applications (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    job_id          INT UNSIGNED    NOT NULL,
    candidate_id    INT UNSIGNED    NOT NULL,
    stage           ENUM('applied','screening','technical_test','interview','offer','hired','rejected')
                                    NOT NULL DEFAULT 'applied',
    source          ENUM('direct','linkedin','indeed','glassdoor','referral','other')
                                    NOT NULL DEFAULT 'direct',
    referral_code   VARCHAR(64)     DEFAULT NULL,
    resume_text     LONGTEXT        DEFAULT NULL
                        COMMENT 'Parsed resume content for skill matching',
    match_score     DECIMAL(5,2)    DEFAULT NULL COMMENT '0 – 100 computed match score',
    is_frozen       TINYINT(1)      NOT NULL DEFAULT 0
                        COMMENT 'Frozen due to red flag',
    duplicate_of    INT UNSIGNED    DEFAULT NULL
                        COMMENT 'Points to original application if duplicate',
    applied_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                        ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id)       REFERENCES job_requisitions(id) ON DELETE RESTRICT,
    FOREIGN KEY (candidate_id) REFERENCES users(id)            ON DELETE RESTRICT,
    FOREIGN KEY (duplicate_of) REFERENCES applications(id)     ON DELETE SET NULL,
    UNIQUE KEY uq_job_candidate (job_id, candidate_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE pipeline_stages_log (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    application_id  INT UNSIGNED    NOT NULL,
    from_stage      VARCHAR(50)     DEFAULT NULL,
    to_stage        VARCHAR(50)     NOT NULL,
    actor_id        INT UNSIGNED    NOT NULL,
    reason          TEXT            DEFAULT NULL,
    changed_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (actor_id)       REFERENCES users(id)        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE assessments (
    id                  INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    job_id              INT UNSIGNED    NOT NULL,
    title               VARCHAR(255)    NOT NULL,
    total_time_minutes  INT UNSIGNED    NOT NULL DEFAULT 60,
    cooldown_months     INT UNSIGNED    NOT NULL DEFAULT 3,
    cooldown_hours      INT UNSIGNED    NOT NULL DEFAULT 24
                            COMMENT 'Configurable hours between attempts',
    num_easy            INT UNSIGNED    NOT NULL DEFAULT 5,
    num_medium          INT UNSIGNED    NOT NULL DEFAULT 5,
    num_hard            INT UNSIGNED    NOT NULL DEFAULT 5,
    created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES job_requisitions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE questions (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    assessment_id   INT UNSIGNED    DEFAULT NULL
                        COMMENT 'NULL = global question bank',
    content         TEXT            NOT NULL,
    type            ENUM('mcq','coding','text')
                                    NOT NULL DEFAULT 'mcq',
    difficulty      ENUM('easy','medium','hard')
                                    NOT NULL DEFAULT 'medium',
    correct_answer  TEXT            DEFAULT NULL,
    options_json    JSON            DEFAULT NULL
                        COMMENT 'MCQ: array of option strings',
    tags            VARCHAR(500)    DEFAULT NULL
                        COMMENT 'Comma-separated skill tags',
    language        VARCHAR(50)     DEFAULT NULL
                        COMMENT 'e.g. Python, Java, JavaScript',
    expected_output TEXT            DEFAULT NULL
                        COMMENT 'Coding questions: expected stdout',
    common_answers  TEXT            DEFAULT NULL
                        COMMENT 'Plagiarism detection baseline',
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assessment_id) REFERENCES assessments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE candidate_sessions (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    candidate_id    INT UNSIGNED    NOT NULL,
    assessment_id   INT UNSIGNED    NOT NULL,
    questions_json  JSON            DEFAULT NULL
                        COMMENT 'Ordered array of question IDs for this session',
    current_code    LONGTEXT        DEFAULT NULL
                        COMMENT 'Live coding session content',
    started_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    submitted_at    DATETIME        DEFAULT NULL,
    status          ENUM('active','submitted','expired','flagged')
                                    NOT NULL DEFAULT 'active',
    integrity_score DECIMAL(5,2)    NOT NULL DEFAULT 100.00,
    is_flagged      TINYINT(1)      NOT NULL DEFAULT 0,
    FOREIGN KEY (candidate_id)  REFERENCES users(id)       ON DELETE RESTRICT,
    FOREIGN KEY (assessment_id) REFERENCES assessments(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE candidate_answers (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    session_id      INT UNSIGNED    NOT NULL,
    question_id     INT UNSIGNED    NOT NULL,
    answer_text     LONGTEXT        DEFAULT NULL,
    score           DECIMAL(5,2)    DEFAULT NULL,
    is_plagiarized  TINYINT(1)      NOT NULL DEFAULT 0,
    FOREIGN KEY (session_id)  REFERENCES candidate_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id)           ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE proctoring_events (
    id          INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    session_id  INT UNSIGNED    NOT NULL,
    event_type  ENUM('focus_loss','tab_switch','window_blur','copy_attempt','right_click')
                                NOT NULL,
    occurred_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES candidate_sessions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE assessment_cooldowns (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    assessment_id   INT UNSIGNED    NOT NULL,
    candidate_id    INT UNSIGNED    NOT NULL,
    last_attempt_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    overridden_by   INT UNSIGNED    DEFAULT NULL,
    overridden_at   DATETIME        DEFAULT NULL,
    UNIQUE KEY uq_assessment_candidate (assessment_id, candidate_id),
    FOREIGN KEY (assessment_id) REFERENCES assessments(id) ON DELETE CASCADE,
    FOREIGN KEY (candidate_id)  REFERENCES users(id)       ON DELETE CASCADE,
    FOREIGN KEY (overridden_by) REFERENCES users(id)       ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE interview_panels (
    id                   INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    job_id               INT UNSIGNED    NOT NULL,
    application_id       INT UNSIGNED    NOT NULL,
    scheduled_at         DATETIME        NOT NULL,
    timezone             VARCHAR(64)     NOT NULL DEFAULT 'UTC',
    duration_minutes     SMALLINT UNSIGNED NOT NULL DEFAULT 60,
    extended_by_minutes  SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    status               ENUM('scheduled','active','completed','cancelled')
                                         NOT NULL DEFAULT 'scheduled',
    meeting_link         VARCHAR(500)    DEFAULT NULL,
    candidate_token      VARCHAR(128)    DEFAULT NULL
                             COMMENT 'Secure token for candidate to join',
    coding_language      VARCHAR(50)     NOT NULL DEFAULT 'javascript',
    notes                TEXT            DEFAULT NULL,
    created_at           DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id)         REFERENCES job_requisitions(id) ON DELETE RESTRICT,
    FOREIGN KEY (application_id) REFERENCES applications(id)     ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE panel_members (
    id          INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    panel_id    INT UNSIGNED    NOT NULL,
    user_id     INT UNSIGNED    NOT NULL,
    role        ENUM('lead','technical','hr','shadow')
                                NOT NULL DEFAULT 'technical',
    assigned_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_panel_user (panel_id, user_id),
    FOREIGN KEY (panel_id) REFERENCES interview_panels(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)  REFERENCES users(id)            ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE interviewer_slots (
    id          INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED    NOT NULL,
    date        DATE            NOT NULL,
    start_time  TIME            NOT NULL,
    end_time    TIME            NOT NULL,
    is_booked   TINYINT(1)      NOT NULL DEFAULT 0,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE live_sessions (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    panel_id        INT UNSIGNED    NOT NULL UNIQUE,
    current_code    LONGTEXT        DEFAULT NULL,
    language        VARCHAR(50)     NOT NULL DEFAULT 'javascript',
    last_updated_at DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                        ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (panel_id) REFERENCES interview_panels(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE session_extensions (
    id           INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    panel_id     INT UNSIGNED    NOT NULL,
    requested_by INT UNSIGNED    NOT NULL,
    minutes      INT UNSIGNED    NOT NULL,
    reason       TEXT            DEFAULT NULL,
    status       ENUM('pending','approved','denied')
                                 NOT NULL DEFAULT 'pending',
    reviewed_by  INT UNSIGNED    DEFAULT NULL,
    reviewed_at  DATETIME        DEFAULT NULL,
    created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (panel_id)     REFERENCES interview_panels(id) ON DELETE CASCADE,
    FOREIGN KEY (requested_by) REFERENCES users(id)            ON DELETE RESTRICT,
    FOREIGN KEY (reviewed_by)  REFERENCES users(id)            ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE session_extension_requests (
    id           INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    panel_id     INT UNSIGNED    NOT NULL,
    requested_by INT UNSIGNED    NOT NULL COMMENT 'Interviewer user id',
    minutes      TINYINT UNSIGNED NOT NULL DEFAULT 10,
    reason       TEXT            NOT NULL,
    status       ENUM('pending','approved','rejected')
                                 NOT NULL DEFAULT 'pending',
    decided_by   INT UNSIGNED    DEFAULT NULL COMMENT 'HR Admin who actioned',
    decided_at   DATETIME        DEFAULT NULL,
    created_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_panel_pending (panel_id, status),
    FOREIGN KEY (panel_id)     REFERENCES interview_panels(id) ON DELETE CASCADE,
    FOREIGN KEY (requested_by) REFERENCES users(id)            ON DELETE CASCADE,
    FOREIGN KEY (decided_by)   REFERENCES users(id)            ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE feedback_submissions (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    panel_id        INT UNSIGNED    NOT NULL,
    interviewer_id  INT UNSIGNED    NOT NULL,
    candidate_id    INT UNSIGNED    NOT NULL,
    submitted_at    DATETIME        DEFAULT NULL,
    is_shadow       TINYINT(1)      NOT NULL DEFAULT 0,
    overall_notes   TEXT            DEFAULT NULL,
    score           DECIMAL(5,2)    DEFAULT NULL COMMENT '0 – 10 overall score',
    comments        TEXT            DEFAULT NULL,
    submitter_role  ENUM('interviewer','hr_admin','shadow')
                                    NOT NULL DEFAULT 'interviewer',
    include_in_score TINYINT(1)     NOT NULL DEFAULT 1
                        COMMENT '0 for shadow submissions',
    UNIQUE KEY uq_panel_interviewer (panel_id, interviewer_id),
    FOREIGN KEY (panel_id)       REFERENCES interview_panels(id) ON DELETE CASCADE,
    FOREIGN KEY (interviewer_id) REFERENCES users(id)            ON DELETE RESTRICT,
    FOREIGN KEY (candidate_id)   REFERENCES users(id)            ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE feedback_dimensions (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    submission_id   INT UNSIGNED    NOT NULL,
    dimension       ENUM('coding','system_design','communication','culture_fit')
                                    NOT NULL,
    score           DECIMAL(4,2)    NOT NULL COMMENT '0.00 – 10.00',
    notes           TEXT            DEFAULT NULL,
    UNIQUE KEY uq_submission_dimension (submission_id, dimension),
    FOREIGN KEY (submission_id) REFERENCES feedback_submissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE red_flags (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    submission_id   INT UNSIGNED    NOT NULL,
    description     TEXT            NOT NULL,
    severity        ENUM('low','medium','critical')
                                    NOT NULL DEFAULT 'medium',
    escalated_to    INT UNSIGNED    DEFAULT NULL,
    escalated_at    DATETIME        DEFAULT NULL,
    resolved_at     DATETIME        DEFAULT NULL,
    FOREIGN KEY (submission_id) REFERENCES feedback_submissions(id) ON DELETE CASCADE,
    FOREIGN KEY (escalated_to)  REFERENCES users(id)                ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE hiring_recommendations (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    application_id  INT UNSIGNED    NOT NULL UNIQUE,
    recommendation  ENUM('strong_hire','hire','no_hire','strong_no_hire')
                                    NOT NULL,
    final_score     DECIMAL(5,2)    DEFAULT NULL,
    decided_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    decided_by      INT UNSIGNED    DEFAULT NULL,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (decided_by)     REFERENCES users(id)        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE offers (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    application_id  INT UNSIGNED    NOT NULL UNIQUE,
    salary          DECIMAL(12,2)   NOT NULL,
    signing_bonus   DECIMAL(12,2)   NOT NULL DEFAULT 0.00,
    equity          DECIMAL(8,4)    NOT NULL DEFAULT 0.0000
                        COMMENT 'Equity units/percentage',
    status          ENUM('pending','sent','accepted','declined','expired')
                                    NOT NULL DEFAULT 'pending',
    pdf_path        VARCHAR(500)    DEFAULT NULL
                        COMMENT 'Generated offer PDF path',
    email_sent      TINYINT(1)      NOT NULL DEFAULT 0,
    expires_at      DATETIME        DEFAULT NULL,
    created_by      INT UNSIGNED    NOT NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                        ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by)     REFERENCES users(id)        ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE offer_negotiations (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    offer_id        INT UNSIGNED    NOT NULL,
    revision_number INT UNSIGNED    NOT NULL,
    proposed_salary DECIMAL(12,2)   NOT NULL,
    proposed_by     ENUM('company','candidate')
                                    NOT NULL,
    notes           TEXT            DEFAULT NULL,
    approved_by     INT UNSIGNED    DEFAULT NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (offer_id)    REFERENCES offers(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE background_checks (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    application_id  INT UNSIGNED    NOT NULL UNIQUE,
    status          ENUM('pending','pass','fail')
                                    NOT NULL DEFAULT 'pending',
    provider_ref    VARCHAR(255)    DEFAULT NULL,
    poll_count      INT UNSIGNED    NOT NULL DEFAULT 0,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                        ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE onboarding_checklist (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    application_id  INT UNSIGNED    NOT NULL,
    document_type   ENUM('tax_form','government_id','bank_details','emergency_contact','signed_nda')
                                    NOT NULL,
    status          ENUM('pending','uploaded','verified')
                                    NOT NULL DEFAULT 'pending',
    uploaded_at     DATETIME        DEFAULT NULL,
    verified_at     DATETIME        DEFAULT NULL,
    UNIQUE KEY uq_application_doctype (application_id, document_type),
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE referrals (
    id                  INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    referred_by_user_id INT UNSIGNED    NOT NULL,
    candidate_id        INT UNSIGNED    NOT NULL,
    application_id      INT UNSIGNED    DEFAULT NULL,
    bonus_amount        DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    bonus_trigger_date  DATE            DEFAULT NULL,
    bonus_triggered_at  DATETIME        DEFAULT NULL,
    status              ENUM('pending','due','paid','flagged')
                                        NOT NULL DEFAULT 'pending',
    created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (referred_by_user_id) REFERENCES users(id)        ON DELETE RESTRICT,
    FOREIGN KEY (candidate_id)        REFERENCES users(id)        ON DELETE RESTRICT,
    FOREIGN KEY (application_id)      REFERENCES applications(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE referral_invites (
    id              INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    referred_by     INT UNSIGNED    NOT NULL,
    candidate_email VARCHAR(255)    NOT NULL,
    candidate_name  VARCHAR(255)    NOT NULL,
    job_id          INT UNSIGNED    DEFAULT NULL,
    token           VARCHAR(128)    NOT NULL UNIQUE,
    status          ENUM('pending','registered','expired')
                                    NOT NULL DEFAULT 'pending',
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    expires_at      DATETIME        NOT NULL,
    FOREIGN KEY (referred_by) REFERENCES users(id)             ON DELETE RESTRICT,
    FOREIGN KEY (job_id)      REFERENCES job_requisitions(id)  ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE audit_log (
    id                  BIGINT UNSIGNED     AUTO_INCREMENT PRIMARY KEY,
    actor_id            INT UNSIGNED        DEFAULT NULL,
    entity_type         VARCHAR(100)        NOT NULL,
    entity_id           INT UNSIGNED        DEFAULT NULL,
    action              VARCHAR(100)        NOT NULL,
    before_state_json   JSON                DEFAULT NULL,
    after_state_json    JSON                DEFAULT NULL,
    ip_address          VARCHAR(45)         DEFAULT NULL,
    created_at          DATETIME            NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE notifications (
    id                  INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    user_id             INT UNSIGNED    NOT NULL,
    type                VARCHAR(100)    NOT NULL,
    message             TEXT            NOT NULL,
    is_read             TINYINT(1)      NOT NULL DEFAULT 0,
    escalation_level    INT UNSIGNED    NOT NULL DEFAULT 0,
    related_entity_type VARCHAR(100)    DEFAULT NULL,
    related_entity_id   INT UNSIGNED    DEFAULT NULL,
    created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE job_templates (
    id          INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    type        ENUM('description','rubric')
                                NOT NULL,
    content     LONGTEXT        NOT NULL,
    version     INT UNSIGNED    NOT NULL DEFAULT 1,
    is_active   TINYINT(1)      NOT NULL DEFAULT 1,
    created_by  INT UNSIGNED    NOT NULL,
    created_at  DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE sentiment_logs (
    id                  INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    candidate_id        INT UNSIGNED    NOT NULL,
    interview_panel_id  INT UNSIGNED    NOT NULL,
    score               TINYINT         NOT NULL COMMENT '1 – 5',
    comment             TEXT            DEFAULT NULL,
    created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (candidate_id)       REFERENCES users(id)            ON DELETE RESTRICT,
    FOREIGN KEY (interview_panel_id) REFERENCES interview_panels(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE job_board_syncs (
    id          INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    job_id      INT UNSIGNED    NOT NULL,
    platform    ENUM('linkedin','indeed','glassdoor')
                                NOT NULL,
    synced_at   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    external_id VARCHAR(255)    DEFAULT NULL,
    status      ENUM('success','failed','pending')
                                NOT NULL DEFAULT 'pending',
    FOREIGN KEY (job_id) REFERENCES job_requisitions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


SET FOREIGN_KEY_CHECKS = 1;