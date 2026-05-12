<?php
declare(strict_types=1);

// Roles
define("ROLE_HR_ADMIN",    "hr_admin");
define("ROLE_INTERVIEWER", "interviewer");
define("ROLE_CANDIDATE",   "candidate");
define("ROLE_DEPT_MANAGER","dept_manager");
define("ROLE_SHADOW",      "shadow");

//
define("APP_STAGES", ["applied","screening","technical_test","interview","offer","hired","rejected"]);

// Allowed stage transitions
define("STAGE_TRANSITIONS", [
            "applied"        => ["screening","rejected"],
            "screening"      => ["technical_test","rejected"],
            "technical_test" => ["interview","rejected"],
            "interview"      => ["offer","rejected"],
            "offer"          => ["hired","rejected"],
            "hired"          => [],
            "rejected"       => [],
        ]);

// Hiring recommendation thresholds 
define("HIRE_THRESHOLDS", ["strong_hire"=>8.5,"hire"=>6.5,"no_hire"=>4.0]);

// Salary bands per level and location tier 
define("SALARY_BANDS", [
            "L1"=>["tier1"=>55000,"tier2"=>45000,"tier3"=>38000],
            "L2"=>["tier1"=>75000,"tier2"=>62000,"tier3"=>52000],
            "L3"=>["tier1"=>100000,"tier2"=>82000,"tier3"=>68000],
            "L4"=>["tier1"=>135000,"tier2"=>110000,"tier3"=>92000],
            "L5"=>["tier1"=>180000,"tier2"=>148000,"tier3"=>125000],
            "L6"=>["tier1"=>250000,"tier2"=>205000,"tier3"=>170000],
        ]);

define("SIGNING_BONUS_PCT", 0.10);
define("EQUITY_UNITS", ["L1"=>100,"L2"=>250,"L3"=>500,"L4"=>1000,"L5"=>2500,"L6"=>5000]);
define("OFFER_DECISION_WINDOW_HOURS", 168);
define("DEFAULT_COOLDOWN_MONTHS", 3);
define("PROCTORING_PENALTY", 5);
define("PROCTORING_FLAG_THRESHOLD", 70);
define("PROCTORING_MAX_STRIKES", 3);
define("FEEDBACK_DIMENSIONS", ["coding","system_design","communication","culture_fit"]);
define("DIMENSION_WEIGHTS", ["coding"=>0.35,"system_design"=>0.25,"communication"=>0.20,"culture_fit"=>0.20]);
define("RETENTION_MONTHS", 24);
define("K_ANONYMITY_THRESHOLD", 5);
define("FEEDBACK_REMINDER_HOURS", 24);
define("FEEDBACK_ESCALATION_HOURS", 48);
define("REFERRAL_BONUS_AMOUNT", 2500.00);
define("REFERRAL_BONUS_DELAY_DAYS", 90);
define("CSRF_TOKEN_NAME", "_csrf_token");
define("APP_NAME", "NextHire -- Smart Recruitment Platform");
define("BASE_URL", (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"]==="on" ? "https" : "http") . "://" . ($_SERVER["HTTP_HOST"] ?? "localhost") . dirname($_SERVER["SCRIPT_NAME"] ?? "/"));

// Permissions map per role
define("ROLE_PERMISSIONS", [
            "hr_admin" => [
                "dashboard.view","jobs.view","jobs.create","jobs.edit","jobs.delete","jobs.approve",
                "applications.view","applications.manage","pipeline.manage",
                "interviews.view","interviews.schedule","interviews.manage",
                "feedback.view","feedback.manage",
                "offers.view","offers.create","offers.manage",
                "candidates.view","candidates.manage",
                "analytics.view","compliance.view","admin.manage",
                "audit.view","templates.manage","onboarding.manage","invites.manage",
            ],
            "dept_manager" => [
                "dashboard.view","jobs.view","jobs.approve",
                "applications.view","pipeline.view",
                "interviews.view","feedback.view",
                "candidates.view","analytics.view",
            ],
            "interviewer" => [
                "dashboard.view","interviews.view","interviews.own",
                "feedback.submit","feedback.view_own",
                "candidates.view_assigned",
            ],
            "shadow" => [
                "dashboard.view","interviews.view","interviews.observe",
                "feedback.submit_shadow",
            ],
            "candidate" => [
                "dashboard.view","profile.own","applications.own",
                "assessments.own","interviews.own_candidate",
                "offers.view_own","onboarding.own",
            ],
        ]);

// Gmail SMTP
define("SMTP_HOST", "smtp.gmail.com");
define("SMTP_PORT", 587);
define("SMTP_USER", "nexthire.dev@gmail.com");
define("SMTP_PASS", "lfwj bhbe pfva wcdu");
define("SMTP_FROM_NAME", "NextHire");
define("SMTP_FROM_EMAIL", "nexthire.dev@gmail.com");

// File Uploads
define("UPLOAD_DIR",    __DIR__ . "/../uploads");
define("CV_UPLOAD_DIR", __DIR__ . "/../uploads/cvs");
if (!defined("PDF_OFFER_DIR")) define("PDF_OFFER_DIR", __DIR__ . "/../uploads/offers");
define("MAX_CV_SIZE",   5 * 1024 * 1024); // 5MB

// Cooldown
define("COOLDOWN_DEFAULT_HOURS", 24);

// Supported coding languages
define("CODING_LANGUAGES", ["javascript","python","java","cpp","php","go","ruby","rust","typescript","csharp"]);
define("CODING_LANGUAGE_LABELS", [
            "javascript" => "JavaScript",
            "python"     => "Python",
            "java"       => "Java",
            "cpp"        => "C++",
            "php"        => "PHP",
            "go"         => "Go",
            "ruby"       => "Ruby",
            "rust"       => "Rust",
            "typescript" => "TypeScript",
            "csharp"     => "C#",
        ]);

