# NextHire — AI-Driven Smart Recruitment & Interview Management System

> Full-stack PHP 8.1+ / MySQL 8.0 / Tailwind CSS recruitment platform implementing **42 functional requirements** across 6 modules with **5 role portals**.

## Quick Start

```bash
# 1. Create database
mysql -u root -p -e "CREATE DATABASE nexthire CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Run schema
mysql -u root -p nexthire < sql/schema.sql

# 3. Run migration (adds dept_manager, shadow roles, invite tokens, email log)
mysql -u root -p nexthire < sql/migration_v2.sql

# 4. Seed demo data
mysql -u root -p nexthire < sql/seed_data.sql

# 5. Update database config
# Edit config/database.php with your credentials

# 6. Start PHP server
php -S localhost:8000

# 7. Open http://localhost:8000
```

## Demo Credentials

| Role | Email | Password |
|------|-------|----------|
| HR Admin | hr1@nexthire.com | password123 |
| Dept Manager | dm1@nexthire.com | password123 |
| Interviewer | iv1@nexthire.com | password123 |
| Shadow Observer | sh1@nexthire.com | password123 |
| Candidate | c1@nexthire.com | password123 |

## Architecture (97 files)

```
recruitment-system/
├── config/            # Database + app constants (5 roles, RBAC permissions)
├── core/              # Database, Router, BaseModel, BaseController, EventBus,
│                        AuditLogger, RBAC, StateMachine, EmailService
├── models/            # 11 data models
├── services/          # 35 service classes across 5 domains
│   ├── pipeline/      # Screening, Skill-Weighting, Approval, Dedup, Shortlisting, Analytics, Job Board Sync
│   ├── assessment/    # Proctoring, Question Bank, Heartbeat, Validator, Plagiarism, Difficulty, Cooldown
│   ├── interviewer/   # Availability, Panel Builder, Briefing, Live Coding, Shadowing, Extension, Load Balancer
│   ├── feedback/      # Aggregator, Normalization, Red Flags, Consensus, Sentiment, Competency Gap, Hiring Rec
│   ├── offer/         # Calculator, Letter Gen, Validity Timer, Negotiation, Referral, Background Check, Onboarding
│   └── admin/         # RBAC, Data Retention, Diversity, Audit Trail, Versioning, DB Integrity, Escalator
├── controllers/       # 17 controllers
│   ├── AuthController           # Login, Register (public), Invite (private), Logout
│   ├── DashboardController      # Role-based dashboards for all 5 roles
│   ├── hr/                      # Jobs, Pipeline, Shortlisting, Analytics, Offers, Onboarding, Compliance, Admin, DeptManager
│   ├── interviewer/             # Scheduling, LiveSession, Feedback, Panel
│   └── candidate/               # Profile, Application, Assessment
├── views/             # Layouts + partials (Tailwind CSS, SVG icons)
├── sql/               # schema.sql + migration_v2.sql + seed_data.sql
└── index.php          # Single entry point
```

## 5 Role Portals

| Role | Registration | Dashboard |
|------|-------------|-----------|
| **HR Admin** | Private invite link only | Full recruitment pipeline management |
| **Dept Manager** | Private invite link only | Approve/reject job requisitions |
| **Interviewer** | Private invite link only | Schedule, live sessions, feedback |
| **Shadow Observer** | Private invite link only | Read-only interview observation |
| **Candidate** | Public registration | Job browsing, applications, assessments |

## Key Features

### Registration & Authentication
- Public candidate registration at `/index.php?page=auth&action=register`
- Private invite-based registration for staff roles
- HR Admin generates invite links from Admin > Invite Links
- Token-based, time-limited (7 days) invite URLs
- CSRF + session regeneration on login

### Interview System
- **Candidate**: Read + write access to code editor
- **Interviewer**: Read-only view with real-time sync (polls every 2s)
- **Shadow**: Read-only observation, no extension requests
- Candidate joins via `candidate/interview` route
- Session extension requests by interviewers only

### Assessment Proctoring
- Tab-switch detection with warning modal popup
- Strike system (3 strikes = auto-flag)
- Integrity score deduction per violation
- Real-time strike counter in assessment header
- Server-side logging of all proctoring events

### Email Notifications
- HTML email templates for: job creation, interview scheduling, offer letters, account creation, password reset
- Offer letters sent via email with one-click from Offers page
- Email log stored in database for audit

## Security

- PDO prepared statements (no raw SQL)
- CSRF token validation on all POST requests
- RBAC middleware enforcement per role
- Password hashing via `password_hash()`
- Session regeneration on login
- Private invite tokens for staff registration
- k-anonymity for diversity reports
- Append-only audit log
