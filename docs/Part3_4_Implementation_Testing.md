# PART 3: Development Phase (Implementation Details)

---

## 10) Front-End Design Documentation

### Technology Stack
- **HTML5** semantic markup
- **Tailwind CSS** (CDN v3) for responsive styling
- **Chart.js v4** for analytics charts
- **Google Fonts** (Inter family, weights 300–800)
- **Vanilla JavaScript** for interactivity (no framework)

### Design System

| Token | Value | Usage |
|-------|-------|-------|
| Primary | `indigo-600` / `#4F46E5` | Buttons, links, active states |
| Secondary | `purple-600` / `#9333EA` | Gradients, badges |
| Success | `emerald-600` | Flash messages, status badges |
| Warning | `amber-600` | Alerts, orphan indicators |
| Error | `red-600` | Validation, destructive actions |
| Background | `slate-50` | Page background |
| Card | `white` with `border` | Content containers |
| Font | Inter, sans-serif | All text |
| Radius | `rounded-lg` (8px) / `rounded-xl` (12px) | Cards, buttons |
| Shadow Hover | `box-shadow: 0 8px 25px -5px rgba(0,0,0,0.1)` | Card hover effect |

### Layout Structure

```
┌──────────────────────────────────────────────┐
│  Sidebar (w-64, fixed, white)                │
│  ┌─────────────────────────────────┐         │
│  │ Logo: NextHire (gradient icon)  │         │
│  ├─────────────────────────────────┤         │
│  │ Navigation Links (role-based)   │  Main   │
│  │ - Dashboard                     │ Content │
│  │ - [Role-specific links]         │ (ml-64) │
│  ├─────────────────────────────────┤         │
│  │ User Profile Card               │         │
│  │ Sign Out                        │         │
│  └─────────────────────────────────┘         │
└──────────────────────────────────────────────┘
```

### Key UI Screens

1. **Login Page** — Email/password form with gradient background, NextHire branding
2. **HR Dashboard** — Stats grid (4 cards), quick actions panel, recent notifications
3. **Candidate Dashboard** — Application status cards, upcoming interviews, pending assessments
4. **Pipeline Kanban** — Drag-and-drop columns per stage, application cards with candidate info
5. **Assessment Page** — Full-screen timer, question navigation, proctoring warning modal
6. **Interview Room** — Full-screen code editor (Monaco-style textarea), participant list, timer, feedback panel
7. **Interview Management** — Table of all panels with status badges, orphan candidate alerts, manage/join actions
8. **Feedback Form** — Overall score slider, 4 dimension scoring, recommendation select, comments textarea
9. **Offer Calculator** — Level/tier selectors, salary/bonus/equity computed fields, PDF preview

---

## 11) Implementation Modules

### a) User Role Management Module

**Location:** `config/app.php` → `ROLE_PERMISSIONS` constant + `core/RBACMiddleware.php`

**Roles:** `hr_admin`, `dept_manager`, `interviewer`, `shadow`, `candidate`

**Implementation:**
```php
// config/app.php
const ROLE_PERMISSIONS = [
    'hr_admin' => ['jobs.create','jobs.edit','jobs.view','pipeline.view',
                    'pipeline.manage','interviews.view','interviews.manage',
                    'offers.create','analytics.view','admin.users',...],
    'candidate' => ['jobs.browse','applications.own','assessments.own',
                     'interviews.own_candidate','profile.edit'],
    'interviewer' => ['interviews.view','interviews.join','feedback.submit',
                       'schedule.view'],
    ...
];

// core/RBACMiddleware.php
public function handle(string $page): bool {
    $role = $_SESSION['user']['role'] ?? null;
    $permissions = ROLE_PERMISSIONS[$role] ?? [];
    // Check page-to-permission mapping
}
```

**Controller enforcement:** `$this->requireRole("hr_admin")` or `$this->requirePermission("interviews.manage")`

### b) User Manipulation Module

**Location:** `controllers/AuthController.php`, `controllers/hr/AdminController.php`, `models/UserModel.php`

| Function | Route | Description |
|----------|-------|-------------|
| Login | `?page=auth&action=login` | Email/password with bcrypt verify, session creation |
| Register | `?page=auth&action=register` | Candidate self-registration with validation |
| Logout | `?page=auth&action=logout` | Session destroy, redirect to login |
| Add User | `?page=hr/admin&action=create` | HR creates users with any role |
| Update User | `?page=hr/admin&action=edit&id=X` | Edit name, email, role, department |
| Delete/Deactivate | `?page=hr/admin&action=deactivate&id=X` | Soft delete (is_active=0) |
| Search | `?page=hr/admin&action=search&q=X` | Search by name, email, role |
| List | `?page=hr/admin` | Paginated user list with role filter |

### c) Controlling Resources Module

**Resources managed:**

| Resource | Controller | Operations |
|----------|-----------|-----------|
| Job Requisitions | `JobRequisitionController` | CRUD, publish, archive |
| Applications | `ApplicationController` | Submit, withdraw, view status |
| Questions (Bank) | `QuestionBankController` | CRUD for MCQ/coding/text |
| Assessments | `AssessmentController` | Create, assign to job, configure |
| Interview Panels | `InterviewController` | Create, assign members, reschedule |
| Offers | `OfferController` | Generate, send, track acceptance |

### d) Reservation and Rescheduling Module

**Location:** `controllers/interviewer/SchedulingController.php`, `controllers/hr/InterviewController.php`

| Feature | Implementation |
|---------|---------------|
| Interviewer Availability | `interviewer_slots` table: user_id, date, start_time, end_time, is_booked |
| Schedule Interview | InterviewController::schedule() → select date/time, check against available slots |
| Reschedule | InterviewController::reschedule() → POST updates scheduled_at, notifies candidate |
| Cancel | Update panel status to 'cancelled', free booked slots |
| Auto-Schedule | ScreeningTriageService creates panel with +2 days at 10:00 UTC |
| Conflict Detection | AvailabilityResolverService checks for overlapping panels |

### e) Generating Reports Module

**Location:** `controllers/hr/AnalyticsController.php`, `services/pipeline/ThroughputAnalyticsService.php`

| Report | Format | Description |
|--------|--------|-------------|
| Pipeline Analytics | HTML Dashboard | Stage distribution chart (Chart.js), conversion rates, time-in-stage |
| Diversity Report | HTML Table | Gender/ethnicity breakdown per stage |
| Offer Letter | PDF (HTML-to-PDF) | Formatted offer with salary, equity, signing bonus |
| Compliance Audit | HTML Table | Recent audit trail, data retention pass results |
| Interview Summary | HTML Card | Panel feedback scores, dimensional breakdown, recommendation |
| Assessment Report | HTML Table | Candidate scores, integrity scores, flagged sessions |

### f) Sending Emails or Notifications Module

**Location:** `core/EmailService.php`, notification records in `notifications` table

| Trigger | Notification Type | Channel |
|---------|------------------|---------|
| Application submitted | `application` | In-app notification |
| Stage transition | `pipeline` | In-app + email |
| Interview scheduled | `interview` | In-app + email |
| Feedback submitted | `feedback` | In-app |
| Offer sent | `offer` | In-app + email (with PDF) |
| Assessment available | `assessment` | In-app |
| Data retention anonymized | `system` | In-app (to HR) |

**Implementation:** Notifications stored in `notifications` table, displayed on dashboard. Email via PHP `mail()` or SMTP configuration.

### g) File Uploaders

| Upload Type | Location | Validation |
|-------------|----------|-----------|
| Resume/CV | `ApplicationController::apply()` | PDF/DOC, max 5MB, stored in `uploads/resumes/` |
| Profile Photo | `ProfileController::upload()` | JPG/PNG, max 2MB, stored in `uploads/avatars/` |
| Offer PDF | Auto-generated | System generates PDF from template |

---

# PART 4: Complexity & Testing

---

## 12) Software Quality Factor Dependencies

| Factor Pair | Relationship | Example in NextHire |
|-------------|-------------|---------------------|
| **Security vs. Usability** | Inversely related | CSRF tokens + role checks add friction; candidate must be authenticated before browsing jobs. More security = more clicks. |
| **Performance vs. Reliability** | Tension | The 2-second polling interval for live code sync trades optimal latency for server reliability. Faster polling = more DB load. |
| **Flexibility vs. Integrity** | Tension | The StateMachine restricts pipeline transitions for data integrity, but limits HR's ability to skip stages for urgent hires. |
| **Reusability vs. Performance** | Tension | BaseModel generic CRUD methods are reusable across all models, but specific queries (JOINs with conditions) in child models are needed for performance. |

---

## 13) LOC and Cyclomatic Complexity

### Lines of Code (LOC)

| File | LOC | Description |
|------|-----|-------------|
| LiveSessionController.php | 749 | Interview room (join, push, poll, feedback) |
| InterviewController.php | 295 | Interview management CRUD |
| DashboardController.php | 111 | Role-based dashboards |
| PipelineController.php | ~180 | Kanban + stage transitions |
| ScreeningTriageService.php | 100 | Stage transition + auto-panel |
| AssessmentController.php | ~250 | Assessment taking + proctoring |
| **Total System** | **~6500** | All PHP files combined |

### Cyclomatic Complexity (CCM)

**Formula:** `M = E - N + 2P` where E = edges, N = nodes, P = connected components.
**Simplified:** `M = number of decision points + 1`

| Function | Decision Points | CCM | Risk |
|----------|----------------|-----|------|
| `LiveSessionController::join()` | 8 (role checks, panel exists, status checks, candidate ownership, member check) | 9 | High — needs refactoring |
| `LiveSessionController::feedback()` | 6 (auth, already submitted, role check, shadow, scores validation) | 7 | Moderate |
| `ScreeningTriageService::transition()` | 4 (app exists, is_frozen, valid transition, toStage===interview) | 5 | Low |
| `AuthController::login()` | 3 (method check, user exists, password valid) | 4 | Low |
| `DashboardController::index()` | 5 (role switch: hr_admin, dept_manager, interviewer, shadow, candidate) | 6 | Moderate |
| `InterviewController::save()` | 4 (csrf, required fields, create panel, loop interviewers) | 5 | Low |

---

## 14) OO Complexity Metrics

### a) WMC (Weighted Methods per Class)

**Formula:** `WMC = Σ c_i` where `c_i` = complexity of method `i`

| Class | Methods | WMC (sum of CCM) |
|-------|---------|-----------------|
| BaseModel | 8 (findById, findAll, create, update, delete, count, findWhere, findOneWhere) | 8 (all CCM=1) |
| UserModel | 5 (authenticate, findByEmail, findByRole, findById, anonymize) | 7 |
| ApplicationModel | 8 (findByJob, findByCandidate, findByStage, countByStage, findDuplicates, getWithDetails, getTopByMatchScore, logStageChange, getStageLog) | 10 |
| InterviewModel | 5 (findByApplication, findUpcoming, getMembers, addMember, getSession) | 6 |
| LiveSessionController | 7 (index, join, push, poll, feedback, updateTimer, endSession) | 35 |
| DashboardController | 5 (index, hrDashboard, candidateDashboard, interviewerDashboard, deptManagerDashboard) | 18 |

### b) DIT (Depth of Inheritance Tree)

| Class | DIT |
|-------|-----|
| BaseModel | 0 |
| UserModel | 1 (extends BaseModel) |
| ApplicationModel | 1 |
| InterviewModel | 1 |
| BaseController | 0 |
| LiveSessionController | 1 (extends BaseController) |
| InterviewController | 1 |

**Max DIT = 1.** Flat hierarchy — low coupling risk.

### c) NOC (Number of Children)

| Class | NOC |
|-------|-----|
| BaseModel | 6 (UserModel, ApplicationModel, InterviewModel, FeedbackModel, OfferModel, JobRequisitionModel) |
| BaseController | 12 (all controllers extend it) |

### d) CBO (Coupling Between Objects)

**Formula:** Count of distinct classes a class depends on.

| Class | Dependencies | CBO |
|-------|-------------|-----|
| LiveSessionController | BaseController, InterviewModel, UserModel, FeedbackModel, Database, AuditLogger, EventBus | 7 |
| ScreeningTriageService | ApplicationModel, EventBus, AuditLogger, Database, UserModel | 5 |
| InterviewController | BaseController, UserModel, ApplicationModel, InterviewModel, Database, AuditLogger | 6 |
| UserModel | BaseModel, Database | 2 |

### e) RFC (Response for Class)

**Formula:** `RFC = |M| + |R|` where M = methods in class, R = methods called by M.

| Class | Own Methods | External Calls | RFC |
|-------|------------|---------------|-----|
| LiveSessionController | 7 | 22 (model queries, audit, event, session) | 29 |
| ScreeningTriageService | 3 | 12 (model, audit, event, db) | 15 |
| UserModel | 5 | 5 (PDO prepare, execute, fetch) | 10 |

### f) LCOM (Lack of Cohesion of Methods)

**Formula:** `LCOM = |P| - |Q|` where P = pairs of methods not sharing attributes, Q = pairs sharing attributes. LCOM = max(0, P-Q).

| Class | Shared Attributes | LCOM | Assessment |
|-------|------------------|------|-----------|
| BaseModel | db, table (all methods use both) | 0 | Highly cohesive |
| UserModel | db, table | 0 | Highly cohesive |
| LiveSessionController | currentUser (all methods), model refs | 2 | Acceptable |
| DashboardController | currentUser | 0 | Cohesive (each sub-method uses currentUser) |

---

## 15) White-Box Testing (Unit Testing — Path Coverage)

### Test 1: `UserModel::authenticate()`

```
Path 1: User not found → return null
Path 2: User found, wrong password → return null
Path 3: User found, correct password → return user array
```

| Test Case | email | password | Expected | Path |
|-----------|-------|----------|----------|------|
| TC-1.1 | "nonexistent@test.com" | "any" | null | P1 |
| TC-1.2 | "hr1@nexthire.com" | "wrongpassword" | null | P2 |
| TC-1.3 | "hr1@nexthire.com" | "password" | array with id=1 | P3 |

### Test 2: `ScreeningTriageService::transition()`

```
Path 1: Application not found → DomainException
Path 2: Application frozen → DomainException  
Path 3: Invalid transition → StateMachine throws
Path 4: Valid transition, toStage ≠ 'interview' → return true
Path 5: Valid transition, toStage = 'interview' → create panel, return true
```

| Test Case | appId | toStage | Expected | Path |
|-----------|-------|---------|----------|------|
| TC-2.1 | 99999 | "screening" | Exception | P1 |
| TC-2.2 | (frozen app) | "screening" | Exception | P2 |
| TC-2.3 | 1 (stage=screening) | "offer" | Exception | P3 |
| TC-2.4 | 1 (stage=screening) | "technical_test" | true | P4 |
| TC-2.5 | 3 (stage=technical_test) | "interview" | true + panel created | P5 |

### Test 3: `LiveSessionController::join()`

```
Path 1: No panel_id → redirect
Path 2: Panel not found → 404
Path 3: Candidate not owner → redirect with error
Path 4: Interviewer not panel member → redirect with error
Path 5: Valid candidate → activate panel, render room
Path 6: Valid interviewer → render room (read-only code)
```

| Test Case | role | panel_id | Expected | Path |
|-----------|------|----------|----------|------|
| TC-3.1 | candidate | null | redirect to schedule | P1 |
| TC-3.2 | candidate | 99999 | 404 | P2 |
| TC-3.3 | candidate (id=12) | 1 (app owner=14) | error redirect | P3 |
| TC-3.4 | interviewer (id=8) | 1 (not member) | error redirect | P4 |
| TC-3.5 | candidate (id=14) | 1 | render interview room | P5 |
| TC-3.6 | interviewer (id=6) | 1 | render room read-only | P6 |

### Test 4: `ApplicationModel::findDuplicates()`

```
Path 1: No duplicates → empty array
Path 2: Duplicate found → array with matches
Path 3: Same app excluded → empty (excludeId filters self)
```

| Test Case | email | jobId | excludeId | Expected | Path |
|-----------|-------|-------|-----------|----------|------|
| TC-4.1 | "new@test.com" | 1 | 0 | [] | P1 |
| TC-4.2 | "c1@nexthire.com" | 1 | 0 | [app record] | P2 |
| TC-4.3 | "c1@nexthire.com" | 1 | 12 | [] | P3 |

### Test 5: `InterviewController::save()`

```
Path 1: CSRF invalid → redirect with error
Path 2: Missing required fields → redirect with error
Path 3: Valid data, no interviewers selected → panel created, HR as only member
Path 4: Valid data, interviewers selected → panel + members created
```

| Test Case | csrf | appId | interviewers | Expected | Path |
|-----------|------|-------|-------------|----------|------|
| TC-5.1 | invalid | 4 | [] | error redirect | P1 |
| TC-5.2 | valid | 0 | [] | error redirect | P2 |
| TC-5.3 | valid | 4 | [] | panel created, 1 member | P3 |
| TC-5.4 | valid | 4 | [6,7] | panel created, 3 members | P4 |

### Test 6: `BaseModel::update()`

```
Path 1: Valid ID and data → returns true
Path 2: Non-existent ID → returns true (no rows affected but PDO succeeds)
```

| Test Case | id | data | Expected | Path |
|-----------|-----|------|----------|------|
| TC-6.1 | 1 | ["name"=>"Updated"] | true | P1 |
| TC-6.2 | 99999 | ["name"=>"Test"] | true (0 rows) | P2 |

---

## 16) Black-Box Testing (Boundary Testing)

### Test 1: Match Score Computation

**Input partitions:** skill weights (0.0–10.0), candidate skills (0–N matches)

| Test Case | Candidate Skills Match | Job Skills | Expected Score | Boundary |
|-----------|----------------------|-----------|---------------|----------|
| BB-1.1 | 0 of 5 | 5 skills | 0% | Lower bound |
| BB-1.2 | 1 of 5 | 5 skills (lowest weight) | ~6% | Near lower |
| BB-1.3 | 3 of 5 | 5 skills | ~60% | Middle |
| BB-1.4 | 5 of 5 | 5 skills | 100% | Upper bound |
| BB-1.5 | 0 of 0 | No skills | 0% (div by zero guard) | Edge case |

### Test 2: Assessment Timer

**Input partitions:** time remaining (0 to total_time_minutes)

| Test Case | Time Action | Expected | Boundary |
|-----------|------------|----------|----------|
| BB-2.1 | Start at 0 elapsed | Timer shows full time | Lower bound |
| BB-2.2 | At total_time - 1 second | Timer shows 00:01 | Near upper |
| BB-2.3 | At total_time exactly | Auto-submit triggered | Upper bound |
| BB-2.4 | Manual submit at 50% time | Score computed normally | Middle |

### Test 3: Feedback Score (0–10 range)

| Test Case | Score Value | Expected | Boundary |
|-----------|-----------|----------|----------|
| BB-3.1 | -1 | Rejected (validation error) | Below lower |
| BB-3.2 | 0 | Accepted | Lower bound |
| BB-3.3 | 10 | Accepted | Upper bound |
| BB-3.4 | 10.1 | Rejected | Above upper |
| BB-3.5 | 5.5 | Accepted | Middle |

### Test 4: Proctoring Strikes

| Test Case | Tab Switches | Expected | Boundary |
|-----------|-------------|----------|----------|
| BB-4.1 | 0 | No warning | Lower bound |
| BB-4.2 | 1 | Warning overlay shown | First strike |
| BB-4.3 | 2 | Second warning | Near threshold |
| BB-4.4 | 3 | Session auto-terminated | Upper bound / threshold |

### Test 5: CSRF Token Validation

| Test Case | Token | Expected | Boundary |
|-----------|-------|----------|----------|
| BB-5.1 | Valid session token | Form accepted | Valid |
| BB-5.2 | Empty string | 403 rejected | Edge case |
| BB-5.3 | Expired/wrong token | 403 rejected | Invalid |
| BB-5.4 | No token field | 403 rejected | Missing |

### Test 6: Interview Panel Duration

| Test Case | Duration (minutes) | Expected | Boundary |
|-----------|-------------------|----------|----------|
| BB-6.1 | 30 | Accepted (minimum) | Lower bound |
| BB-6.2 | 60 | Accepted (default) | Typical |
| BB-6.3 | 120 | Accepted (maximum) | Upper bound |
| BB-6.4 | 0 | Rejected | Below lower |
| BB-6.5 | 180 | Accepted but flagged | Above typical |
