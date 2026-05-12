# PART 1: Overview & Software Requirements Specification

---

## 1) Introduction

### a) Purpose

This Software Requirements Specification (SRS) document describes the functional and non-functional requirements for **NextHire — Smart Recruitment Platform**, an AI-driven, enterprise-grade Applicant Tracking System (ATS). It serves as the contractual basis between stakeholders and the development team, ensuring all parties share a common understanding of the system's capabilities, constraints, and quality attributes.

**Rationale:** A formal SRS reduces ambiguity, prevents scope creep, and provides a baseline for validation and verification throughout the SDLC.

### b) Project Scope

NextHire automates the end-to-end recruitment lifecycle:

| Phase | Features |
|-------|----------|
| Job Requisition | Create, approve (multi-level chain), publish to job boards |
| Application & Screening | Apply, AI skill-matching (≥80% threshold), duplicate detection, resume parsing |
| Assessment | Timed technical tests, MCQ/coding/text questions, proctoring, plagiarism detection |
| Interview | Panel scheduling, live coding rooms, real-time code sync, tab-switch detection, feedback with dimensional scoring |
| Offer Management | Salary calculator, equity/signing bonus, PDF generation, negotiation workflow |
| Onboarding | Document checklist, background checks |
| Analytics & Compliance | Pipeline throughput, diversity audits, GDPR data retention, audit trail |

**Out of Scope:** Payroll integration, third-party video conferencing (e.g., Zoom), mobile native apps.

### c) Glossary and Abbreviations

| Term | Definition |
|------|-----------|
| ATS | Applicant Tracking System |
| RBAC | Role-Based Access Control |
| CSRF | Cross-Site Request Forgery |
| CRUD | Create, Read, Update, Delete |
| SRS | Software Requirements Specification |
| MoSCoW | Must, Should, Could, Won't prioritization |
| LOC | Lines of Code |
| CCM | Cyclomatic Complexity Metric |
| Pipeline | The sequence of recruitment stages an application passes through |
| Panel | A group of interviewers assigned to evaluate a candidate |
| Proctoring | Monitoring candidate behavior during assessments (tab switches, copy attempts) |
| Match Score | AI-computed percentage (0–100) indicating candidate-job skill alignment |
| Candidate Token | A secure random string allowing a candidate to join an interview session |
| Stage Transition | Moving an application from one pipeline stage to the next (e.g., screening → technical_test) |
| Red Flag | A critical concern raised during feedback that may freeze an application |

### d) List of System Stakeholders

| Stakeholder | Role | Interest |
|-------------|------|----------|
| HR Administrator | Primary user | Manages entire recruitment lifecycle |
| Department Manager | Approver | Approves job requisitions for their department |
| Technical Interviewer | Evaluator | Conducts interviews, submits feedback |
| Shadow Observer | Trainee | Observes interviews in read-only mode |
| Candidate | Applicant | Applies, takes assessments, attends interviews |
| System Administrator | IT Support | Maintains infrastructure, database |
| Compliance Officer | Auditor | Ensures GDPR compliance, reviews audit trails |

### e) References

1. IEEE 830-1998 — Recommended Practice for SRS
2. Sommerville, I. — *Software Engineering*, 10th Edition
3. Larman, C. — *Applying UML and Patterns*
4. PHP 8.x Documentation — https://www.php.net/docs.php
5. MySQL 8.0 Reference Manual

---

## 2) Functional Requirements

### a) User Requirements Specification (Natural Language)

**UR-01:** HR Admins shall be able to create job requisitions with title, department, description, requirements, level, and location tier.
*Rationale:* Job requisitions are the entry point of the recruitment pipeline.

**UR-02:** Candidates shall be able to browse live jobs and submit applications with resume upload.
*Rationale:* Self-service application reduces HR workload.

**UR-03:** The system shall automatically compute a match score (0–100%) between candidate skills and job requirements.
*Rationale:* AI-driven screening reduces time-to-hire.

**UR-04:** HR Admins shall be able to move candidates through pipeline stages using a Kanban board with drag-and-drop.
*Rationale:* Visual pipeline management improves workflow efficiency.

**UR-05:** When a candidate reaches the Interview stage, the system shall automatically create an interview panel.
*Rationale:* Eliminates manual panel creation and prevents orphaned candidates.

**UR-06:** Interviewers, HR Admins, and Candidates shall be able to join a live interview room with real-time code synchronization.
*Rationale:* Technical interviews require collaborative coding evaluation.

**UR-07:** Interviewers shall submit dimensional feedback (coding, system design, communication, culture fit) with hiring recommendations.
*Rationale:* Structured feedback enables objective, comparable evaluations.

**UR-08:** HR Admins shall generate and send offer letters with salary, signing bonus, and equity calculations.
*Rationale:* Automated offers reduce errors and speed up the hiring process.

### b) System Requirements Specification (Structured / Tabular)

| Req ID | Description | Input | Processing | Output | Actor |
|--------|------------|-------|-----------|--------|-------|
| SR-01 | Create Job Requisition | Title, dept, desc, level, tier | Validate fields, insert into `job_requisitions` | Job record with status='draft' | HR Admin |
| SR-02 | Submit Application | Job ID, resume file | Parse resume, compute match_score via SkillWeightingService, check duplicates | Application record with stage='applied' | Candidate |
| SR-03 | Pipeline Stage Transition | Application ID, target stage | Validate via StateMachine against STAGE_TRANSITIONS config, log change | Updated stage, pipeline_stages_log entry | HR Admin |
| SR-04 | Auto-Create Interview Panel | Application ID (on transition to 'interview') | Generate candidate_token, create panel with 60min/+2days defaults, add HR as member | interview_panels + panel_members rows | System |
| SR-05 | Join Interview Room | Panel ID, user role | Verify panel exists, check role membership, activate panel, create live_session | Full-screen coding environment with real-time sync | All roles |
| SR-06 | Submit Feedback | Panel ID, scores (0–10), dimensions, recommendation | Insert feedback_submissions + feedback_dimensions, compute normalized score, upsert hiring_recommendations | Feedback record, updated recommendation | Interviewer/HR/Shadow |
| SR-07 | Generate Offer | Application ID, salary, bonus, equity | Compute from SALARY_BANDS config, generate PDF | Offer record, PDF file, email to candidate | HR Admin |
| SR-08 | Proctoring Detection | Tab switch / focus loss event | Increment strikes, log proctoring_event, if strikes≥3 terminate session | Strike count, possible session termination | System |

### c) Requirements Priorities (MoSCoW)

| Priority | Requirements |
|----------|-------------|
| **Must Have** | User auth (login/register), RBAC, job CRUD, application submission, pipeline management, interview room, feedback submission, offer generation |
| **Should Have** | AI match scoring, proctoring, email notifications, audit trail, data retention, referral system |
| **Could Have** | Diversity analytics, shadow observer role, offer negotiation workflow, job board sync |
| **Won't Have (this release)** | Video conferencing integration, mobile app, payroll sync, third-party calendar integration |

---

## 3) Non-Functional Requirements

### a) Categories

1. **Performance** — Response time, throughput
2. **Security** — Authentication, authorization, data protection
3. **Usability** — Ease of use, accessibility
4. **Reliability** — Availability, fault tolerance
5. **Scalability** — Load handling, data growth
6. **Compliance** — GDPR, data retention

### b) & c) Specification with Fit Criteria

| NFR ID | Category | Requirement | Fit Criteria (Testable) |
|--------|----------|-------------|------------------------|
| NFR-01 | Performance | Page load time ≤ 2 seconds for dashboard | Measured via browser DevTools; 95th percentile < 2s |
| NFR-02 | Performance | Live code sync latency ≤ 600ms | Polling interval is 2000ms; push debounce is 600ms |
| NFR-03 | Security | All passwords hashed with bcrypt (cost ≥ 12) | `password_verify()` succeeds; hash starts with `$2y$12$` |
| NFR-04 | Security | CSRF tokens on all POST forms | Every form contains `_csrf_token` hidden field; server rejects without it |
| NFR-05 | Security | Role-based access enforced on every controller action | Unauthorized access returns HTTP 403 |
| NFR-06 | Usability | Maximum 3 clicks to reach any primary function | Navigation audit from dashboard to each feature |
| NFR-07 | Reliability | System handles graceful error recovery | Global exception handler renders user-friendly error page |
| NFR-08 | Compliance | Candidate data anonymized after 24 months | DataRetentionService replaces PII with `[Anonymized]` |
| NFR-09 | Scalability | Support ≥ 1000 concurrent applications | Database indexes on foreign keys; PDO connection pooling |

### d) Impact on Architecture

- **NFR-01/02 (Performance):** Drove the choice of server-side polling (2s interval) over WebSockets for simplicity while maintaining acceptable latency. Database queries use indexed JOINs.
- **NFR-03/04/05 (Security):** Led to the `RBACMiddleware` + `BaseController::requireRole()` layered security pattern. CSRF tokens are generated per-session.
- **NFR-08 (Compliance):** Required the `DataRetentionService` as a separate service layer and the `AuditLogger` recording all state changes.

---

## 4) Design & Implementation Constraints

| Constraint | Description | Rationale |
|-----------|-------------|-----------|
| Language | PHP 8.x (strict_types) | University project requirement |
| Database | MySQL 8.0 with InnoDB | ACID compliance, foreign key support |
| Frontend | Vanilla HTML + Tailwind CSS (CDN) | No build step required |
| Architecture | Custom MVC (no framework) | Demonstrates understanding of patterns |
| Authentication | Session-based (PHP sessions) | Simplest secure approach for server-rendered app |
| Hosting | Apache/Nginx with PHP-FPM or `php -S` | Local development environment |

---

## 5) System Evolution

### a) Anticipated Changes

1. **WebSocket Integration:** Replace polling-based code sync with true WebSocket for sub-100ms latency.
2. **Video Conferencing:** Integrate WebRTC or third-party API (Zoom/Teams) for face-to-face interviews.
3. **AI Resume Parser:** Use NLP/ML models to extract skills from uploaded CVs automatically.
4. **Mobile Responsive:** Progressive Web App (PWA) for candidate mobile experience.
5. **Multi-tenancy:** Support multiple organizations on a single deployment.

### b) How Anticipated Changes Affect Design

- **Strategy Pattern** used for skill matching (SkillWeightingService) allows swapping algorithms without changing callers.
- **Observer Pattern** (EventBus) enables adding new side-effects (e.g., Slack notifications) without modifying core logic.
- **State Machine** for pipeline transitions makes adding new stages a configuration change, not a code change.
- **Service Layer** separation means replacing polling with WebSockets only changes LiveSessionController + frontend JS — no model changes needed.

---

## 6) Requirements Discovery Approaches

| Approach | Example |
|----------|---------|
| **Domain Analysis** | Studied existing ATS platforms (Greenhouse, Lever, Workday) to identify standard recruitment workflows and the 7-stage pipeline model |
| **Use Case Analysis** | Wrote detailed use cases for each actor (e.g., "Candidate Takes Assessment" with pre/post conditions) before implementation |
| **Prototyping** | Built HTML mockups of the Kanban pipeline board and interview room to validate UX before backend implementation |
| **Document Analysis** | Reviewed IEEE 830 SRS standards and GDPR Article 17 (Right to Erasure) to derive compliance requirements |
| **Brainstorming** | Team sessions identified edge cases: What if a candidate switches tabs during assessment? → Led to proctoring system with 3-strike rule |

---

## 7) Requirements Validation Techniques

| Technique | Example |
|-----------|---------|
| **Requirements Review** | Each requirement cross-checked against the MoSCoW list to ensure completeness and no gold-plating |
| **Prototyping** | Interview room prototype validated that candidates need read-only view of language selector while interviewers need full control |
| **Test-Case Generation** | For SR-03 (Stage Transition): Test that `applied→screening` succeeds but `applied→offer` fails per STAGE_TRANSITIONS config |
| **Consistency Analysis** | Verified that ROLE_PERMISSIONS in `app.php` matches every `requireRole()` call in controllers — found and fixed missing `interviews.own_candidate` for candidates |
| **Traceability Matrix** | Every SR maps to at least one use case, one test case, and one UI element |

---

## Mathematical Specification

**Match Score Computation:**

$$S_{match} = \frac{\sum_{i=1}^{n} w_i \cdot m_i}{\sum_{i=1}^{n} w_i} \times 100$$

Where:
- $w_i$ = weight of skill $i$ from `job_skills.weight`
- $m_i \in \{0, 1\}$ = 1 if candidate possesses skill $i$, 0 otherwise
- $n$ = total number of skills for the job

**Normalized Feedback Score:**

$$\bar{F} = \frac{\sum_{j=1}^{k} f_j}{k}, \quad f_j \in [0, 10]$$

Where $k$ = number of non-shadow feedback submissions for a panel.

**Weighted Dimension Score:**

$$D_{weighted} = \sum_{d \in \{coding, design, comm, culture\}} w_d \cdot s_d$$

Where weights: $w_{coding}=0.35, w_{design}=0.25, w_{comm}=0.20, w_{culture}=0.20$ and $\sum w_d = 1.0$

**Hiring Recommendation Thresholds (Set Notation):**

$$R = \begin{cases} \text{strong\_hire} & \text{if } \bar{F} \geq 8.5 \\ \text{hire} & \text{if } 6.5 \leq \bar{F} < 8.5 \\ \text{no\_hire} & \text{if } 4.0 \leq \bar{F} < 6.5 \\ \text{strong\_no\_hire} & \text{if } \bar{F} < 4.0 \end{cases}$$

**Pipeline Stage Transition (Set):**

$$T: S \rightarrow \mathcal{P}(S) \text{ where } S = \{applied, screening, technical\_test, interview, offer, hired, rejected\}$$

$$T(applied) = \{screening, rejected\}, \quad T(screening) = \{technical\_test, rejected\}, \quad \ldots$$
