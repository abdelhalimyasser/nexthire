# Untitled Diagram documentation
## Summary

- [Introduction](#introduction)
- [Database Type](#database-type)
- [Table Structure](#table-structure)
	- [users](#users)
	- [roles_permissions](#roles_permissions)
	- [invite_tokens](#invite_tokens)
	- [email_log](#email_log)
	- [job_requisitions](#job_requisitions)
	- [job_skills](#job_skills)
	- [applications](#applications)
	- [pipeline_stages_log](#pipeline_stages_log)
	- [assessments](#assessments)
	- [questions](#questions)
	- [candidate_sessions](#candidate_sessions)
	- [candidate_answers](#candidate_answers)
	- [proctoring_events](#proctoring_events)
	- [assessment_cooldowns](#assessment_cooldowns)
	- [interview_panels](#interview_panels)
	- [panel_members](#panel_members)
	- [interviewer_slots](#interviewer_slots)
	- [live_sessions](#live_sessions)
	- [session_extensions](#session_extensions)
	- [session_extension_requests](#session_extension_requests)
	- [feedback_submissions](#feedback_submissions)
	- [feedback_dimensions](#feedback_dimensions)
	- [red_flags](#red_flags)
	- [hiring_recommendations](#hiring_recommendations)
	- [offers](#offers)
	- [offer_negotiations](#offer_negotiations)
	- [background_checks](#background_checks)
	- [onboarding_checklist](#onboarding_checklist)
	- [referrals](#referrals)
	- [referral_invites](#referral_invites)
	- [audit_log](#audit_log)
	- [notifications](#notifications)
	- [job_templates](#job_templates)
	- [sentiment_logs](#sentiment_logs)
	- [job_board_syncs](#job_board_syncs)
- [Relationships](#relationships)
- [Database Diagram](#database-diagram)

## Introduction

## Database type

- **Database system:** MySQL
## Table structure

### users

| Name                    | Type         | Settings                             | References | Note                                           |
| ----------------------- | ------------ | ------------------------------------ | ---------- | ---------------------------------------------- |
| **id**                  | INTEGER      | 🔑 PK, null, autoincrement           |            |                                                |
| **name**                | VARCHAR(255) | not null                             |            |                                                |
| **email**               | VARCHAR(255) | not null, unique                     |            |                                                |
| **password_hash**       | VARCHAR(255) | not null                             |            |                                                |
| **role**                | ENUM         | not null, default: candidate         |            |                                                |
| **department**          | VARCHAR(100) | null, default: NULL                  |            |                                                |
| **seniority**           | ENUM         | null, default: NULL                  |            |                                                |
| **specializations**     | JSON         | null, default: NULL                  |            | Array of skill tags (interviewers)             |
| **cv_path**             | VARCHAR(500) | null, default: NULL                  |            | Uploaded CV file path (candidates)             |
| **document_links**      | JSON         | null, default: NULL                  |            | GitHub, LinkedIn, Google Drive, Portfolio URLs |
| **diversity_gender**    | VARCHAR(50)  | null, default: NULL                  |            | Voluntary                                      |
| **diversity_ethnicity** | VARCHAR(100) | null, default: NULL                  |            | Voluntary                                      |
| **is_active**           | TINYINT      | not null, default: 1                 |            |                                                |
| **last_login_at**       | DATETIME     | null, default: NULL                  |            |                                                |
| **created_at**          | DATETIME     | not null, default: CURRENT_TIMESTAMP |            |                                                |
| **updated_at**          | DATETIME     | not null, default: CURRENT_TIMESTAMP |            |                                                | 

#### Enums
##### role

- hr_admin
- interviewer
- candidate
- dept_manager
- shadow
##### seniority

- junior
- mid
- senior
- lead


### roles_permissions

| Name           | Type         | Settings                   | References | Note |
| -------------- | ------------ | -------------------------- | ---------- | ---- |
| **id**         | INTEGER      | 🔑 PK, null, autoincrement |            |      |
| **role**       | ENUM         | not null                   |            |      |
| **permission** | VARCHAR(100) | not null                   |            |      |
| **granted**    | TINYINT      | not null, default: 1       |            |      | 

#### Enums
##### role

- hr_admin
- interviewer
- candidate
- dept_manager
- shadow


### invite_tokens

| Name            | Type         | Settings                             | References                        | Note |
| --------------- | ------------ | ------------------------------------ | --------------------------------- | ---- |
| **id**          | INTEGER      | 🔑 PK, null, autoincrement           |                                   |      |
| **token**       | VARCHAR(128) | not null, unique                     |                                   |      |
| **target_role** | ENUM         | not null                             |                                   |      |
| **created_by**  | INTEGER      | not null                             | fk_invite_tokens_created_by_users |      |
| **used_by**     | INTEGER      | null, default: NULL                  | fk_invite_tokens_used_by_users    |      |
| **used_at**     | DATETIME     | null, default: NULL                  |                                   |      |
| **expires_at**  | DATETIME     | not null                             |                                   |      |
| **created_at**  | DATETIME     | not null, default: CURRENT_TIMESTAMP |                                   |      | 

#### Enums
##### target_role

- hr_admin
- interviewer
- dept_manager
- shadow


### email_log

| Name               | Type         | Settings                             | References | Note |
| ------------------ | ------------ | ------------------------------------ | ---------- | ---- |
| **id**             | INTEGER      | 🔑 PK, null, autoincrement           |            |      |
| **recipient**      | VARCHAR(255) | not null                             |            |      |
| **subject**        | VARCHAR(500) | not null                             |            |      |
| **body_html**      | LONGTEXT     | null, default: NULL                  |            |      |
| **status**         | ENUM         | not null, default: queued            |            |      |
| **related_entity** | VARCHAR(100) | null, default: NULL                  |            |      |
| **related_id**     | INTEGER      | null, default: NULL                  |            |      |
| **sent_at**        | DATETIME     | null, default: NULL                  |            |      |
| **created_at**     | DATETIME     | not null, default: CURRENT_TIMESTAMP |            |      | 

#### Enums
##### status

- queued
- sent
- failed


### job_requisitions

| Name                    | Type         | Settings                             | References                           | Note                                           |
| ----------------------- | ------------ | ------------------------------------ | ------------------------------------ | ---------------------------------------------- |
| **id**                  | INTEGER      | 🔑 PK, null, autoincrement           |                                      |                                                |
| **title**               | VARCHAR(255) | not null                             |                                      |                                                |
| **department**          | VARCHAR(100) | not null                             |                                      |                                                |
| **description**         | TEXT         | not null                             |                                      |                                                |
| **requirements**        | TEXT         | null, default: NULL                  |                                      |                                                |
| **location_tier**       | ENUM         | not null, default: tier1             |                                      |                                                |
| **level**               | ENUM         | not null, default: L3                |                                      |                                                |
| **role_type**           | VARCHAR(100) | null, default: NULL                  |                                      |                                                |
| **status**              | ENUM         | not null, default: draft             |                                      |                                                |
| **approval_chain_json** | JSON         | null, default: NULL                  |                                      | Ordered array of approver user_ids with status |
| **template_id**         | INTEGER      | null, default: NULL                  |                                      |                                                |
| **created_by**          | INTEGER      | not null                             | fk_job_requisitions_created_by_users |                                                |
| **version**             | INTEGER      | not null, default: 1                 |                                      |                                                |
| **created_at**          | DATETIME     | not null, default: CURRENT_TIMESTAMP |                                      |                                                |
| **updated_at**          | DATETIME     | not null, default: CURRENT_TIMESTAMP |                                      |                                                | 

#### Enums
##### location_tier

- tier1
- tier2
- tier3
##### level

- L1
- L2
- L3
- L4
- L5
- L6
##### status

- draft
- pending_approval
- live
- closed
- cancelled


### job_skills

| Name            | Type         | Settings                   | References                            | Note         |
| --------------- | ------------ | -------------------------- | ------------------------------------- | ------------ |
| **id**          | INTEGER      | 🔑 PK, null, autoincrement |                                       |              |
| **job_id**      | INTEGER      | not null                   | fk_job_skills_job_id_job_requisitions |              |
| **skill_name**  | VARCHAR(150) | not null                   |                                       |              |
| **weight**      | DECIMAL(5,2) | not null, default: 1.00    |                                       | 0.00 – 10.00 |
| **is_required** | TINYINT      | not null, default: 0       |                                       |              | 


### applications

| Name              | Type         | Settings                             | References                              | Note                                        |
| ----------------- | ------------ | ------------------------------------ | --------------------------------------- | ------------------------------------------- |
| **id**            | INTEGER      | 🔑 PK, null, autoincrement           |                                         |                                             |
| **job_id**        | INTEGER      | not null                             | fk_applications_job_id_job_requisitions |                                             |
| **candidate_id**  | INTEGER      | not null                             | fk_applications_candidate_id_users      |                                             |
| **stage**         | ENUM         | not null, default: applied           |                                         |                                             |
| **source**        | ENUM         | not null, default: direct            |                                         |                                             |
| **referral_code** | VARCHAR(64)  | null, default: NULL                  |                                         |                                             |
| **resume_text**   | LONGTEXT     | null, default: NULL                  |                                         | Parsed resume content for skill matching    |
| **match_score**   | DECIMAL(5,2) | null, default: NULL                  |                                         | 0 – 100 computed match score                |
| **is_frozen**     | TINYINT      | not null, default: 0                 |                                         | Frozen due to red flag                      |
| **duplicate_of**  | INTEGER      | null, default: NULL                  |                                         | Points to original application if duplicate |
| **applied_at**    | DATETIME     | not null, default: CURRENT_TIMESTAMP |                                         |                                             |
| **updated_at**    | DATETIME     | not null, default: CURRENT_TIMESTAMP |                                         |                                             | 

#### Enums
##### stage

- applied
- screening
- technical_test
- interview
- offer
- hired
- rejected
##### source

- direct
- linkedin
- indeed
- glassdoor
- referral
- other


### pipeline_stages_log

| Name               | Type        | Settings                             | References                                         | Note |
| ------------------ | ----------- | ------------------------------------ | -------------------------------------------------- | ---- |
| **id**             | INTEGER     | 🔑 PK, null, autoincrement           |                                                    |      |
| **application_id** | INTEGER     | not null                             | fk_pipeline_stages_log_application_id_applications |      |
| **from_stage**     | VARCHAR(50) | null, default: NULL                  |                                                    |      |
| **to_stage**       | VARCHAR(50) | not null                             |                                                    |      |
| **actor_id**       | INTEGER     | not null                             | fk_pipeline_stages_log_actor_id_users              |      |
| **reason**         | TEXT        | null, default: NULL                  |                                                    |      |
| **changed_at**     | DATETIME    | not null, default: CURRENT_TIMESTAMP |                                                    |      | 


### assessments

| Name                   | Type         | Settings                             | References                             | Note                                |
| ---------------------- | ------------ | ------------------------------------ | -------------------------------------- | ----------------------------------- |
| **id**                 | INTEGER      | 🔑 PK, null, autoincrement           |                                        |                                     |
| **job_id**             | INTEGER      | not null                             | fk_assessments_job_id_job_requisitions |                                     |
| **title**              | VARCHAR(255) | not null                             |                                        |                                     |
| **total_time_minutes** | INTEGER      | not null, default: 60                |                                        |                                     |
| **cooldown_months**    | INTEGER      | not null, default: 3                 |                                        |                                     |
| **cooldown_hours**     | INTEGER      | not null, default: 24                |                                        | Configurable hours between attempts |
| **num_easy**           | INTEGER      | not null, default: 5                 |                                        |                                     |
| **num_medium**         | INTEGER      | not null, default: 5                 |                                        |                                     |
| **num_hard**           | INTEGER      | not null, default: 5                 |                                        |                                     |
| **created_at**         | DATETIME     | not null, default: CURRENT_TIMESTAMP |                                        |                                     | 


### questions

| Name                | Type         | Settings                             | References                             | Note                              |
| ------------------- | ------------ | ------------------------------------ | -------------------------------------- | --------------------------------- |
| **id**              | INTEGER      | 🔑 PK, null, autoincrement           |                                        |                                   |
| **assessment_id**   | INTEGER      | null, default: NULL                  | fk_questions_assessment_id_assessments | NULL = global question bank       |
| **content**         | TEXT         | not null                             |                                        |                                   |
| **type**            | ENUM         | not null, default: mcq               |                                        |                                   |
| **difficulty**      | ENUM         | not null, default: medium            |                                        |                                   |
| **correct_answer**  | TEXT         | null, default: NULL                  |                                        |                                   |
| **options_json**    | JSON         | null, default: NULL                  |                                        | MCQ: array of option strings      |
| **tags**            | VARCHAR(500) | null, default: NULL                  |                                        | Comma-separated skill tags        |
| **language**        | VARCHAR(50)  | null, default: NULL                  |                                        | e.g. Python, Java, JavaScript     |
| **expected_output** | TEXT         | null, default: NULL                  |                                        | Coding questions: expected stdout |
| **common_answers**  | TEXT         | null, default: NULL                  |                                        | Plagiarism detection baseline     |
| **created_at**      | DATETIME     | not null, default: CURRENT_TIMESTAMP |                                        |                                   | 

#### Enums
##### type

- mcq
- coding
- text
##### difficulty

- easy
- medium
- hard


### candidate_sessions

| Name                | Type         | Settings                             | References                                      | Note                                           |
| ------------------- | ------------ | ------------------------------------ | ----------------------------------------------- | ---------------------------------------------- |
| **id**              | INTEGER      | 🔑 PK, null, autoincrement           |                                                 |                                                |
| **candidate_id**    | INTEGER      | not null                             | fk_candidate_sessions_candidate_id_users        |                                                |
| **assessment_id**   | INTEGER      | not null                             | fk_candidate_sessions_assessment_id_assessments |                                                |
| **questions_json**  | JSON         | null, default: NULL                  |                                                 | Ordered array of question IDs for this session |
| **current_code**    | LONGTEXT     | null, default: NULL                  |                                                 | Live coding session content                    |
| **started_at**      | DATETIME     | not null, default: CURRENT_TIMESTAMP |                                                 |                                                |
| **submitted_at**    | DATETIME     | null, default: NULL                  |                                                 |                                                |
| **status**          | ENUM         | not null, default: active            |                                                 |                                                |
| **integrity_score** | DECIMAL(5,2) | not null, default: 100.00            |                                                 |                                                |
| **is_flagged**      | TINYINT      | not null, default: 0                 |                                                 |                                                | 

#### Enums
##### status

- active
- submitted
- expired
- flagged


### candidate_answers

| Name               | Type         | Settings                   | References                                         | Note |
| ------------------ | ------------ | -------------------------- | -------------------------------------------------- | ---- |
| **id**             | INTEGER      | 🔑 PK, null, autoincrement |                                                    |      |
| **session_id**     | INTEGER      | not null                   | fk_candidate_answers_session_id_candidate_sessions |      |
| **question_id**    | INTEGER      | not null                   | fk_candidate_answers_question_id_questions         |      |
| **answer_text**    | LONGTEXT     | null, default: NULL        |                                                    |      |
| **score**          | DECIMAL(5,2) | null, default: NULL        |                                                    |      |
| **is_plagiarized** | TINYINT      | not null, default: 0       |                                                    |      | 


### proctoring_events

| Name            | Type     | Settings                             | References                                         | Note |
| --------------- | -------- | ------------------------------------ | -------------------------------------------------- | ---- |
| **id**          | INTEGER  | 🔑 PK, null, autoincrement           |                                                    |      |
| **session_id**  | INTEGER  | not null                             | fk_proctoring_events_session_id_candidate_sessions |      |
| **event_type**  | ENUM     | not null                             |                                                    |      |
| **occurred_at** | DATETIME | not null, default: CURRENT_TIMESTAMP |                                                    |      | 

#### Enums
##### event_type

- focus_loss
- tab_switch
- window_blur
- copy_attempt
- right_click


### assessment_cooldowns

| Name                | Type     | Settings                             | References                                        | Note |
| ------------------- | -------- | ------------------------------------ | ------------------------------------------------- | ---- |
| **id**              | INTEGER  | 🔑 PK, null, autoincrement           |                                                   |      |
| **assessment_id**   | INTEGER  | not null                             | fk_assessment_cooldowns_assessment_id_assessments |      |
| **candidate_id**    | INTEGER  | not null                             | fk_assessment_cooldowns_candidate_id_users        |      |
| **last_attempt_at** | DATETIME | not null, default: CURRENT_TIMESTAMP |                                                   |      |
| **overridden_by**   | INTEGER  | null, default: NULL                  | fk_assessment_cooldowns_overridden_by_users       |      |
| **overridden_at**   | DATETIME | null, default: NULL                  |                                                   |      | 


### interview_panels

| Name                    | Type         | Settings                             | References                                      | Note                               |
| ----------------------- | ------------ | ------------------------------------ | ----------------------------------------------- | ---------------------------------- |
| **id**                  | INTEGER      | 🔑 PK, null, autoincrement           |                                                 |                                    |
| **job_id**              | INTEGER      | not null                             | fk_interview_panels_job_id_job_requisitions     |                                    |
| **application_id**      | INTEGER      | not null                             | fk_interview_panels_application_id_applications |                                    |
| **scheduled_at**        | DATETIME     | not null                             |                                                 |                                    |
| **timezone**            | VARCHAR(64)  | not null, default: UTC               |                                                 |                                    |
| **duration_minutes**    | SMALLINT     | not null, default: 60                |                                                 |                                    |
| **extended_by_minutes** | SMALLINT     | not null, default: 0                 |                                                 |                                    |
| **status**              | ENUM         | not null, default: scheduled         |                                                 |                                    |
| **meeting_link**        | VARCHAR(500) | null, default: NULL                  |                                                 |                                    |
| **candidate_token**     | VARCHAR(128) | null, default: NULL                  |                                                 | Secure token for candidate to join |
| **coding_language**     | VARCHAR(50)  | not null, default: javascript        |                                                 |                                    |
| **notes**               | TEXT         | null, default: NULL                  |                                                 |                                    |
| **created_at**          | DATETIME     | not null, default: CURRENT_TIMESTAMP |                                                 |                                    | 

#### Enums
##### status

- scheduled
- active
- completed
- cancelled


### panel_members

| Name            | Type     | Settings                             | References                                 | Note |
| --------------- | -------- | ------------------------------------ | ------------------------------------------ | ---- |
| **id**          | INTEGER  | 🔑 PK, null, autoincrement           |                                            |      |
| **panel_id**    | INTEGER  | not null                             | fk_panel_members_panel_id_interview_panels |      |
| **user_id**     | INTEGER  | not null                             | fk_panel_members_user_id_users             |      |
| **role**        | ENUM     | not null, default: technical         |                                            |      |
| **assigned_at** | DATETIME | not null, default: CURRENT_TIMESTAMP |                                            |      | 

#### Enums
##### role

- lead
- technical
- hr
- shadow


### interviewer_slots

| Name           | Type    | Settings                   | References                         | Note |
| -------------- | ------- | -------------------------- | ---------------------------------- | ---- |
| **id**         | INTEGER | 🔑 PK, null, autoincrement |                                    |      |
| **user_id**    | INTEGER | not null                   | fk_interviewer_slots_user_id_users |      |
| **date**       | DATE    | not null                   |                                    |      |
| **start_time** | TIME    | not null                   |                                    |      |
| **end_time**   | TIME    | not null                   |                                    |      |
| **is_booked**  | TINYINT | not null, default: 0       |                                    |      | 


### live_sessions

| Name                | Type        | Settings                             | References                                 | Note |
| ------------------- | ----------- | ------------------------------------ | ------------------------------------------ | ---- |
| **id**              | INTEGER     | 🔑 PK, null, autoincrement           |                                            |      |
| **panel_id**        | INTEGER     | not null, unique                     | fk_live_sessions_panel_id_interview_panels |      |
| **current_code**    | LONGTEXT    | null, default: NULL                  |                                            |      |
| **language**        | VARCHAR(50) | not null, default: javascript        |                                            |      |
| **last_updated_at** | DATETIME    | not null, default: CURRENT_TIMESTAMP |                                            |      | 


### session_extensions

| Name             | Type     | Settings                             | References                                      | Note |
| ---------------- | -------- | ------------------------------------ | ----------------------------------------------- | ---- |
| **id**           | INTEGER  | 🔑 PK, null, autoincrement           |                                                 |      |
| **panel_id**     | INTEGER  | not null                             | fk_session_extensions_panel_id_interview_panels |      |
| **requested_by** | INTEGER  | not null                             | fk_session_extensions_requested_by_users        |      |
| **minutes**      | INTEGER  | not null                             |                                                 |      |
| **reason**       | TEXT     | null, default: NULL                  |                                                 |      |
| **status**       | ENUM     | not null, default: pending           |                                                 |      |
| **reviewed_by**  | INTEGER  | null, default: NULL                  | fk_session_extensions_reviewed_by_users         |      |
| **reviewed_at**  | DATETIME | null, default: NULL                  |                                                 |      |
| **created_at**   | DATETIME | not null, default: CURRENT_TIMESTAMP |                                                 |      | 

#### Enums
##### status

- pending
- approved
- denied


### session_extension_requests

| Name             | Type     | Settings                             | References                                              | Note                  |
| ---------------- | -------- | ------------------------------------ | ------------------------------------------------------- | --------------------- |
| **id**           | INTEGER  | 🔑 PK, null, autoincrement           |                                                         |                       |
| **panel_id**     | INTEGER  | not null                             | fk_session_extension_requests_panel_id_interview_panels |                       |
| **requested_by** | INTEGER  | not null                             | fk_session_extension_requests_requested_by_users        | Interviewer user id   |
| **minutes**      | TINYINT  | not null, default: 10                |                                                         |                       |
| **reason**       | TEXT     | not null                             |                                                         |                       |
| **status**       | ENUM     | not null, default: pending           |                                                         |                       |
| **decided_by**   | INTEGER  | null, default: NULL                  | fk_session_extension_requests_decided_by_users          | HR Admin who actioned |
| **decided_at**   | DATETIME | null, default: NULL                  |                                                         |                       |
| **created_at**   | DATETIME | not null, default: CURRENT_TIMESTAMP |                                                         |                       | 

#### Enums
##### status

- pending
- approved
- rejected


### feedback_submissions

| Name                 | Type         | Settings                       | References                                        | Note                     |
| -------------------- | ------------ | ------------------------------ | ------------------------------------------------- | ------------------------ |
| **id**               | INTEGER      | 🔑 PK, null, autoincrement     |                                                   |                          |
| **panel_id**         | INTEGER      | not null                       | fk_feedback_submissions_panel_id_interview_panels |                          |
| **interviewer_id**   | INTEGER      | not null                       | fk_feedback_submissions_interviewer_id_users      |                          |
| **candidate_id**     | INTEGER      | not null                       | fk_feedback_submissions_candidate_id_users        |                          |
| **submitted_at**     | DATETIME     | null, default: NULL            |                                                   |                          |
| **is_shadow**        | TINYINT      | not null, default: 0           |                                                   |                          |
| **overall_notes**    | TEXT         | null, default: NULL            |                                                   |                          |
| **score**            | DECIMAL(5,2) | null, default: NULL            |                                                   | 0 – 10 overall score     |
| **comments**         | TEXT         | null, default: NULL            |                                                   |                          |
| **submitter_role**   | ENUM         | not null, default: interviewer |                                                   |                          |
| **include_in_score** | TINYINT      | not null, default: 1           |                                                   | 0 for shadow submissions | 

#### Enums
##### submitter_role

- interviewer
- hr_admin
- shadow


### feedback_dimensions

| Name              | Type         | Settings                   | References                                                | Note         |
| ----------------- | ------------ | -------------------------- | --------------------------------------------------------- | ------------ |
| **id**            | INTEGER      | 🔑 PK, null, autoincrement |                                                           |              |
| **submission_id** | INTEGER      | not null                   | fk_feedback_dimensions_submission_id_feedback_submissions |              |
| **dimension**     | ENUM         | not null                   |                                                           |              |
| **score**         | DECIMAL(4,2) | not null                   |                                                           | 0.00 – 10.00 |
| **notes**         | TEXT         | null, default: NULL        |                                                           |              | 

#### Enums
##### dimension

- coding
- system_design
- communication
- culture_fit


### red_flags

| Name              | Type     | Settings                   | References                                      | Note |
| ----------------- | -------- | -------------------------- | ----------------------------------------------- | ---- |
| **id**            | INTEGER  | 🔑 PK, null, autoincrement |                                                 |      |
| **submission_id** | INTEGER  | not null                   | fk_red_flags_submission_id_feedback_submissions |      |
| **description**   | TEXT     | not null                   |                                                 |      |
| **severity**      | ENUM     | not null, default: medium  |                                                 |      |
| **escalated_to**  | INTEGER  | null, default: NULL        | fk_red_flags_escalated_to_users                 |      |
| **escalated_at**  | DATETIME | null, default: NULL        |                                                 |      |
| **resolved_at**   | DATETIME | null, default: NULL        |                                                 |      | 

#### Enums
##### severity

- low
- medium
- critical


### hiring_recommendations

| Name               | Type         | Settings                             | References                                            | Note |
| ------------------ | ------------ | ------------------------------------ | ----------------------------------------------------- | ---- |
| **id**             | INTEGER      | 🔑 PK, null, autoincrement           |                                                       |      |
| **application_id** | INTEGER      | not null, unique                     | fk_hiring_recommendations_application_id_applications |      |
| **recommendation** | ENUM         | not null                             |                                                       |      |
| **final_score**    | DECIMAL(5,2) | null, default: NULL                  |                                                       |      |
| **decided_at**     | DATETIME     | not null, default: CURRENT_TIMESTAMP |                                                       |      |
| **decided_by**     | INTEGER      | null, default: NULL                  | fk_hiring_recommendations_decided_by_users            |      | 

#### Enums
##### recommendation

- strong_hire
- hire
- no_hire
- strong_no_hire


### offers

| Name               | Type          | Settings                             | References                            | Note                     |
| ------------------ | ------------- | ------------------------------------ | ------------------------------------- | ------------------------ |
| **id**             | INTEGER       | 🔑 PK, null, autoincrement           |                                       |                          |
| **application_id** | INTEGER       | not null, unique                     | fk_offers_application_id_applications |                          |
| **salary**         | DECIMAL(12,2) | not null                             |                                       |                          |
| **signing_bonus**  | DECIMAL(12,2) | not null, default: 0.00              |                                       |                          |
| **equity**         | DECIMAL(8,4)  | not null, default: 0.0000            |                                       | Equity units/percentage  |
| **status**         | ENUM          | not null, default: pending           |                                       |                          |
| **pdf_path**       | VARCHAR(500)  | null, default: NULL                  |                                       | Generated offer PDF path |
| **email_sent**     | TINYINT       | not null, default: 0                 |                                       |                          |
| **expires_at**     | DATETIME      | null, default: NULL                  |                                       |                          |
| **created_by**     | INTEGER       | not null                             | fk_offers_created_by_users            |                          |
| **created_at**     | DATETIME      | not null, default: CURRENT_TIMESTAMP |                                       |                          |
| **updated_at**     | DATETIME      | not null, default: CURRENT_TIMESTAMP |                                       |                          | 

#### Enums
##### status

- pending
- sent
- accepted
- declined
- expired


### offer_negotiations

| Name                | Type          | Settings                             | References                              | Note |
| ------------------- | ------------- | ------------------------------------ | --------------------------------------- | ---- |
| **id**              | INTEGER       | 🔑 PK, null, autoincrement           |                                         |      |
| **offer_id**        | INTEGER       | not null                             | fk_offer_negotiations_offer_id_offers   |      |
| **revision_number** | INTEGER       | not null                             |                                         |      |
| **proposed_salary** | DECIMAL(12,2) | not null                             |                                         |      |
| **proposed_by**     | ENUM          | not null                             |                                         |      |
| **notes**           | TEXT          | null, default: NULL                  |                                         |      |
| **approved_by**     | INTEGER       | null, default: NULL                  | fk_offer_negotiations_approved_by_users |      |
| **created_at**      | DATETIME      | not null, default: CURRENT_TIMESTAMP |                                         |      | 

#### Enums
##### proposed_by

- company
- candidate


### background_checks

| Name               | Type         | Settings                             | References                                       | Note |
| ------------------ | ------------ | ------------------------------------ | ------------------------------------------------ | ---- |
| **id**             | INTEGER      | 🔑 PK, null, autoincrement           |                                                  |      |
| **application_id** | INTEGER      | not null, unique                     | fk_background_checks_application_id_applications |      |
| **status**         | ENUM         | not null, default: pending           |                                                  |      |
| **provider_ref**   | VARCHAR(255) | null, default: NULL                  |                                                  |      |
| **poll_count**     | INTEGER      | not null, default: 0                 |                                                  |      |
| **updated_at**     | DATETIME     | not null, default: CURRENT_TIMESTAMP |                                                  |      | 

#### Enums
##### status

- pending
- pass
- fail


### onboarding_checklist

| Name               | Type     | Settings                   | References                                          | Note |
| ------------------ | -------- | -------------------------- | --------------------------------------------------- | ---- |
| **id**             | INTEGER  | 🔑 PK, null, autoincrement |                                                     |      |
| **application_id** | INTEGER  | not null                   | fk_onboarding_checklist_application_id_applications |      |
| **document_type**  | ENUM     | not null                   |                                                     |      |
| **status**         | ENUM     | not null, default: pending |                                                     |      |
| **uploaded_at**    | DATETIME | null, default: NULL        |                                                     |      |
| **verified_at**    | DATETIME | null, default: NULL        |                                                     |      | 

#### Enums
##### document_type

- tax_form
- government_id
- bank_details
- emergency_contact
- signed_nda
##### status

- pending
- uploaded
- verified


### referrals

| Name                    | Type          | Settings                             | References                               | Note |
| ----------------------- | ------------- | ------------------------------------ | ---------------------------------------- | ---- |
| **id**                  | INTEGER       | 🔑 PK, null, autoincrement           |                                          |      |
| **referred_by_user_id** | INTEGER       | not null                             | fk_referrals_referred_by_user_id_users   |      |
| **candidate_id**        | INTEGER       | not null                             | fk_referrals_candidate_id_users          |      |
| **application_id**      | INTEGER       | null, default: NULL                  | fk_referrals_application_id_applications |      |
| **bonus_amount**        | DECIMAL(10,2) | not null, default: 0.00              |                                          |      |
| **bonus_trigger_date**  | DATE          | null, default: NULL                  |                                          |      |
| **bonus_triggered_at**  | DATETIME      | null, default: NULL                  |                                          |      |
| **status**              | ENUM          | not null, default: pending           |                                          |      |
| **created_at**          | DATETIME      | not null, default: CURRENT_TIMESTAMP |                                          |      | 

#### Enums
##### status

- pending
- due
- paid
- flagged


### referral_invites

| Name                | Type         | Settings                             | References                                  | Note |
| ------------------- | ------------ | ------------------------------------ | ------------------------------------------- | ---- |
| **id**              | INTEGER      | 🔑 PK, null, autoincrement           |                                             |      |
| **referred_by**     | INTEGER      | not null                             | fk_referral_invites_referred_by_users       |      |
| **candidate_email** | VARCHAR(255) | not null                             |                                             |      |
| **candidate_name**  | VARCHAR(255) | not null                             |                                             |      |
| **job_id**          | INTEGER      | null, default: NULL                  | fk_referral_invites_job_id_job_requisitions |      |
| **token**           | VARCHAR(128) | not null, unique                     |                                             |      |
| **status**          | ENUM         | not null, default: pending           |                                             |      |
| **created_at**      | DATETIME     | not null, default: CURRENT_TIMESTAMP |                                             |      |
| **expires_at**      | DATETIME     | not null                             |                                             |      | 

#### Enums
##### status

- pending
- registered
- expired


### audit_log

| Name                  | Type         | Settings                             | References | Note |
| --------------------- | ------------ | ------------------------------------ | ---------- | ---- |
| **id**                | BIGINT       | 🔑 PK, null, autoincrement           |            |      |
| **actor_id**          | INTEGER      | null, default: NULL                  |            |      |
| **entity_type**       | VARCHAR(100) | not null                             |            |      |
| **entity_id**         | INTEGER      | null, default: NULL                  |            |      |
| **action**            | VARCHAR(100) | not null                             |            |      |
| **before_state_json** | JSON         | null, default: NULL                  |            |      |
| **after_state_json**  | JSON         | null, default: NULL                  |            |      |
| **ip_address**        | VARCHAR(45)  | null, default: NULL                  |            |      |
| **created_at**        | DATETIME     | not null, default: CURRENT_TIMESTAMP |            |      | 


### notifications

| Name                    | Type         | Settings                             | References                     | Note |
| ----------------------- | ------------ | ------------------------------------ | ------------------------------ | ---- |
| **id**                  | INTEGER      | 🔑 PK, null, autoincrement           |                                |      |
| **user_id**             | INTEGER      | not null                             | fk_notifications_user_id_users |      |
| **type**                | VARCHAR(100) | not null                             |                                |      |
| **message**             | TEXT         | not null                             |                                |      |
| **is_read**             | TINYINT      | not null, default: 0                 |                                |      |
| **escalation_level**    | INTEGER      | not null, default: 0                 |                                |      |
| **related_entity_type** | VARCHAR(100) | null, default: NULL                  |                                |      |
| **related_entity_id**   | INTEGER      | null, default: NULL                  |                                |      |
| **created_at**          | DATETIME     | not null, default: CURRENT_TIMESTAMP |                                |      | 


### job_templates

| Name           | Type     | Settings                             | References                        | Note |
| -------------- | -------- | ------------------------------------ | --------------------------------- | ---- |
| **id**         | INTEGER  | 🔑 PK, null, autoincrement           |                                   |      |
| **type**       | ENUM     | not null                             |                                   |      |
| **content**    | LONGTEXT | not null                             |                                   |      |
| **version**    | INTEGER  | not null, default: 1                 |                                   |      |
| **is_active**  | TINYINT  | not null, default: 1                 |                                   |      |
| **created_by** | INTEGER  | not null                             | fk_job_templates_created_by_users |      |
| **created_at** | DATETIME | not null, default: CURRENT_TIMESTAMP |                                   |      | 

#### Enums
##### type

- description
- rubric


### sentiment_logs

| Name                   | Type     | Settings                             | References                                            | Note  |
| ---------------------- | -------- | ------------------------------------ | ----------------------------------------------------- | ----- |
| **id**                 | INTEGER  | 🔑 PK, null, autoincrement           |                                                       |       |
| **candidate_id**       | INTEGER  | not null                             | fk_sentiment_logs_candidate_id_users                  |       |
| **interview_panel_id** | INTEGER  | not null                             | fk_sentiment_logs_interview_panel_id_interview_panels |       |
| **score**              | TINYINT  | not null                             |                                                       | 1 – 5 |
| **comment**            | TEXT     | null, default: NULL                  |                                                       |       |
| **created_at**         | DATETIME | not null, default: CURRENT_TIMESTAMP |                                                       |       | 


### job_board_syncs

| Name            | Type         | Settings                             | References                                 | Note |
| --------------- | ------------ | ------------------------------------ | ------------------------------------------ | ---- |
| **id**          | INTEGER      | 🔑 PK, null, autoincrement           |                                            |      |
| **job_id**      | INTEGER      | not null                             | fk_job_board_syncs_job_id_job_requisitions |      |
| **platform**    | ENUM         | not null                             |                                            |      |
| **synced_at**   | DATETIME     | not null, default: CURRENT_TIMESTAMP |                                            |      |
| **external_id** | VARCHAR(255) | null, default: NULL                  |                                            |      |
| **status**      | ENUM         | not null, default: pending           |                                            |      | 

#### Enums
##### platform

- linkedin
- indeed
- glassdoor
##### status

- success
- failed
- pending


## Relationships

- **invite_tokens to users**: many_to_one
- **invite_tokens to users**: many_to_one
- **job_requisitions to users**: many_to_one
- **job_skills to job_requisitions**: many_to_one
- **applications to job_requisitions**: many_to_one
- **applications to users**: many_to_one
- **pipeline_stages_log to applications**: many_to_one
- **pipeline_stages_log to users**: many_to_one
- **assessments to job_requisitions**: many_to_one
- **questions to assessments**: many_to_one
- **candidate_sessions to users**: many_to_one
- **candidate_sessions to assessments**: many_to_one
- **candidate_answers to candidate_sessions**: many_to_one
- **candidate_answers to questions**: many_to_one
- **proctoring_events to candidate_sessions**: many_to_one
- **assessment_cooldowns to assessments**: many_to_one
- **assessment_cooldowns to users**: many_to_one
- **assessment_cooldowns to users**: many_to_one
- **interview_panels to job_requisitions**: many_to_one
- **interview_panels to applications**: many_to_one
- **panel_members to interview_panels**: many_to_one
- **panel_members to users**: many_to_one
- **interviewer_slots to users**: many_to_one
- **live_sessions to interview_panels**: one_to_one
- **session_extensions to interview_panels**: many_to_one
- **session_extensions to users**: many_to_one
- **session_extensions to users**: many_to_one
- **session_extension_requests to interview_panels**: many_to_one
- **session_extension_requests to users**: many_to_one
- **session_extension_requests to users**: many_to_one
- **feedback_submissions to interview_panels**: many_to_one
- **feedback_submissions to users**: many_to_one
- **feedback_submissions to users**: many_to_one
- **feedback_dimensions to feedback_submissions**: many_to_one
- **red_flags to feedback_submissions**: many_to_one
- **red_flags to users**: many_to_one
- **hiring_recommendations to applications**: one_to_one
- **hiring_recommendations to users**: many_to_one
- **offers to applications**: one_to_one
- **offers to users**: many_to_one
- **offer_negotiations to offers**: many_to_one
- **offer_negotiations to users**: many_to_one
- **background_checks to applications**: one_to_one
- **onboarding_checklist to applications**: many_to_one
- **referrals to users**: many_to_one
- **referrals to users**: many_to_one
- **referrals to applications**: many_to_one
- **referral_invites to users**: many_to_one
- **referral_invites to job_requisitions**: many_to_one
- **notifications to users**: many_to_one
- **job_templates to users**: many_to_one
- **sentiment_logs to users**: many_to_one
- **sentiment_logs to interview_panels**: many_to_one
- **job_board_syncs to job_requisitions**: many_to_one

## Database Diagram

```mermaid
erDiagram
	invite_tokens }o--|| users : references
	invite_tokens }o--|| users : references
	job_requisitions }o--|| users : references
	job_skills }o--|| job_requisitions : references
	applications }o--|| job_requisitions : references
	applications }o--|| users : references
	pipeline_stages_log }o--|| applications : references
	pipeline_stages_log }o--|| users : references
	assessments }o--|| job_requisitions : references
	questions }o--|| assessments : references
	candidate_sessions }o--|| users : references
	candidate_sessions }o--|| assessments : references
	candidate_answers }o--|| candidate_sessions : references
	candidate_answers }o--|| questions : references
	proctoring_events }o--|| candidate_sessions : references
	assessment_cooldowns }o--|| assessments : references
	assessment_cooldowns }o--|| users : references
	assessment_cooldowns }o--|| users : references
	interview_panels }o--|| job_requisitions : references
	interview_panels }o--|| applications : references
	panel_members }o--|| interview_panels : references
	panel_members }o--|| users : references
	interviewer_slots }o--|| users : references
	live_sessions ||--|| interview_panels : references
	session_extensions }o--|| interview_panels : references
	session_extensions }o--|| users : references
	session_extensions }o--|| users : references
	session_extension_requests }o--|| interview_panels : references
	session_extension_requests }o--|| users : references
	session_extension_requests }o--|| users : references
	feedback_submissions }o--|| interview_panels : references
	feedback_submissions }o--|| users : references
	feedback_submissions }o--|| users : references
	feedback_dimensions }o--|| feedback_submissions : references
	red_flags }o--|| feedback_submissions : references
	red_flags }o--|| users : references
	hiring_recommendations ||--|| applications : references
	hiring_recommendations }o--|| users : references
	offers ||--|| applications : references
	offers }o--|| users : references
	offer_negotiations }o--|| offers : references
	offer_negotiations }o--|| users : references
	background_checks ||--|| applications : references
	onboarding_checklist }o--|| applications : references
	referrals }o--|| users : references
	referrals }o--|| users : references
	referrals }o--|| applications : references
	referral_invites }o--|| users : references
	referral_invites }o--|| job_requisitions : references
	notifications }o--|| users : references
	job_templates }o--|| users : references
	sentiment_logs }o--|| users : references
	sentiment_logs }o--|| interview_panels : references
	job_board_syncs }o--|| job_requisitions : references

	users {
		INTEGER id
		VARCHAR(255) name
		VARCHAR(255) email
		VARCHAR(255) password_hash
		ENUM role
		VARCHAR(100) department
		ENUM seniority
		JSON specializations
		VARCHAR(500) cv_path
		JSON document_links
		VARCHAR(50) diversity_gender
		VARCHAR(100) diversity_ethnicity
		TINYINT is_active
		DATETIME last_login_at
		DATETIME created_at
		DATETIME updated_at
	}

	roles_permissions {
		INTEGER id
		ENUM role
		VARCHAR(100) permission
		TINYINT granted
	}

	invite_tokens {
		INTEGER id
		VARCHAR(128) token
		ENUM target_role
		INTEGER created_by
		INTEGER used_by
		DATETIME used_at
		DATETIME expires_at
		DATETIME created_at
	}

	email_log {
		INTEGER id
		VARCHAR(255) recipient
		VARCHAR(500) subject
		LONGTEXT body_html
		ENUM status
		VARCHAR(100) related_entity
		INTEGER related_id
		DATETIME sent_at
		DATETIME created_at
	}

	job_requisitions {
		INTEGER id
		VARCHAR(255) title
		VARCHAR(100) department
		TEXT description
		TEXT requirements
		ENUM location_tier
		ENUM level
		VARCHAR(100) role_type
		ENUM status
		JSON approval_chain_json
		INTEGER template_id
		INTEGER created_by
		INTEGER version
		DATETIME created_at
		DATETIME updated_at
	}

	job_skills {
		INTEGER id
		INTEGER job_id
		VARCHAR(150) skill_name
		DECIMAL(5,2) weight
		TINYINT is_required
	}

	applications {
		INTEGER id
		INTEGER job_id
		INTEGER candidate_id
		ENUM stage
		ENUM source
		VARCHAR(64) referral_code
		LONGTEXT resume_text
		DECIMAL(5,2) match_score
		TINYINT is_frozen
		INTEGER duplicate_of
		DATETIME applied_at
		DATETIME updated_at
	}

	pipeline_stages_log {
		INTEGER id
		INTEGER application_id
		VARCHAR(50) from_stage
		VARCHAR(50) to_stage
		INTEGER actor_id
		TEXT reason
		DATETIME changed_at
	}

	assessments {
		INTEGER id
		INTEGER job_id
		VARCHAR(255) title
		INTEGER total_time_minutes
		INTEGER cooldown_months
		INTEGER cooldown_hours
		INTEGER num_easy
		INTEGER num_medium
		INTEGER num_hard
		DATETIME created_at
	}

	questions {
		INTEGER id
		INTEGER assessment_id
		TEXT content
		ENUM type
		ENUM difficulty
		TEXT correct_answer
		JSON options_json
		VARCHAR(500) tags
		VARCHAR(50) language
		TEXT expected_output
		TEXT common_answers
		DATETIME created_at
	}

	candidate_sessions {
		INTEGER id
		INTEGER candidate_id
		INTEGER assessment_id
		JSON questions_json
		LONGTEXT current_code
		DATETIME started_at
		DATETIME submitted_at
		ENUM status
		DECIMAL(5,2) integrity_score
		TINYINT is_flagged
	}

	candidate_answers {
		INTEGER id
		INTEGER session_id
		INTEGER question_id
		LONGTEXT answer_text
		DECIMAL(5,2) score
		TINYINT is_plagiarized
	}

	proctoring_events {
		INTEGER id
		INTEGER session_id
		ENUM event_type
		DATETIME occurred_at
	}

	assessment_cooldowns {
		INTEGER id
		INTEGER assessment_id
		INTEGER candidate_id
		DATETIME last_attempt_at
		INTEGER overridden_by
		DATETIME overridden_at
	}

	interview_panels {
		INTEGER id
		INTEGER job_id
		INTEGER application_id
		DATETIME scheduled_at
		VARCHAR(64) timezone
		SMALLINT duration_minutes
		SMALLINT extended_by_minutes
		ENUM status
		VARCHAR(500) meeting_link
		VARCHAR(128) candidate_token
		VARCHAR(50) coding_language
		TEXT notes
		DATETIME created_at
	}

	panel_members {
		INTEGER id
		INTEGER panel_id
		INTEGER user_id
		ENUM role
		DATETIME assigned_at
	}

	interviewer_slots {
		INTEGER id
		INTEGER user_id
		DATE date
		TIME start_time
		TIME end_time
		TINYINT is_booked
	}

	live_sessions {
		INTEGER id
		INTEGER panel_id
		LONGTEXT current_code
		VARCHAR(50) language
		DATETIME last_updated_at
	}

	session_extensions {
		INTEGER id
		INTEGER panel_id
		INTEGER requested_by
		INTEGER minutes
		TEXT reason
		ENUM status
		INTEGER reviewed_by
		DATETIME reviewed_at
		DATETIME created_at
	}

	session_extension_requests {
		INTEGER id
		INTEGER panel_id
		INTEGER requested_by
		TINYINT minutes
		TEXT reason
		ENUM status
		INTEGER decided_by
		DATETIME decided_at
		DATETIME created_at
	}

	feedback_submissions {
		INTEGER id
		INTEGER panel_id
		INTEGER interviewer_id
		INTEGER candidate_id
		DATETIME submitted_at
		TINYINT is_shadow
		TEXT overall_notes
		DECIMAL(5,2) score
		TEXT comments
		ENUM submitter_role
		TINYINT include_in_score
	}

	feedback_dimensions {
		INTEGER id
		INTEGER submission_id
		ENUM dimension
		DECIMAL(4,2) score
		TEXT notes
	}

	red_flags {
		INTEGER id
		INTEGER submission_id
		TEXT description
		ENUM severity
		INTEGER escalated_to
		DATETIME escalated_at
		DATETIME resolved_at
	}

	hiring_recommendations {
		INTEGER id
		INTEGER application_id
		ENUM recommendation
		DECIMAL(5,2) final_score
		DATETIME decided_at
		INTEGER decided_by
	}

	offers {
		INTEGER id
		INTEGER application_id
		DECIMAL(12,2) salary
		DECIMAL(12,2) signing_bonus
		DECIMAL(8,4) equity
		ENUM status
		VARCHAR(500) pdf_path
		TINYINT email_sent
		DATETIME expires_at
		INTEGER created_by
		DATETIME created_at
		DATETIME updated_at
	}

	offer_negotiations {
		INTEGER id
		INTEGER offer_id
		INTEGER revision_number
		DECIMAL(12,2) proposed_salary
		ENUM proposed_by
		TEXT notes
		INTEGER approved_by
		DATETIME created_at
	}

	background_checks {
		INTEGER id
		INTEGER application_id
		ENUM status
		VARCHAR(255) provider_ref
		INTEGER poll_count
		DATETIME updated_at
	}

	onboarding_checklist {
		INTEGER id
		INTEGER application_id
		ENUM document_type
		ENUM status
		DATETIME uploaded_at
		DATETIME verified_at
	}

	referrals {
		INTEGER id
		INTEGER referred_by_user_id
		INTEGER candidate_id
		INTEGER application_id
		DECIMAL(10,2) bonus_amount
		DATE bonus_trigger_date
		DATETIME bonus_triggered_at
		ENUM status
		DATETIME created_at
	}

	referral_invites {
		INTEGER id
		INTEGER referred_by
		VARCHAR(255) candidate_email
		VARCHAR(255) candidate_name
		INTEGER job_id
		VARCHAR(128) token
		ENUM status
		DATETIME created_at
		DATETIME expires_at
	}

	audit_log {
		BIGINT id
		INTEGER actor_id
		VARCHAR(100) entity_type
		INTEGER entity_id
		VARCHAR(100) action
		JSON before_state_json
		JSON after_state_json
		VARCHAR(45) ip_address
		DATETIME created_at
	}

	notifications {
		INTEGER id
		INTEGER user_id
		VARCHAR(100) type
		TEXT message
		TINYINT is_read
		INTEGER escalation_level
		VARCHAR(100) related_entity_type
		INTEGER related_entity_id
		DATETIME created_at
	}

	job_templates {
		INTEGER id
		ENUM type
		LONGTEXT content
		INTEGER version
		TINYINT is_active
		INTEGER created_by
		DATETIME created_at
	}

	sentiment_logs {
		INTEGER id
		INTEGER candidate_id
		INTEGER interview_panel_id
		TINYINT score
		TEXT comment
		DATETIME created_at
	}

	job_board_syncs {
		INTEGER id
		INTEGER job_id
		ENUM platform
		DATETIME synced_at
		VARCHAR(255) external_id
		ENUM status
	}
```