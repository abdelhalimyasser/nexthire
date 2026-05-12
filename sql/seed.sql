USE nexthire;

SET @ph = '$2y$12$FuYyj9uK4Gk17yST3/zPWe.xg5aH99KKGlLVZtHtt8MLTmETl98J2';

-- HR Admins
INSERT INTO users (name, email, password_hash, role, department, seniority, is_active) VALUES
('Sarah Johnson',  'hr1@nexthire.com', @ph, 'hr_admin', 'HR', 'lead',   1),
('Michael Chen',   'hr2@nexthire.com', @ph, 'hr_admin', 'HR', 'senior', 1),
('Emily Davis',    'hr3@nexthire.com', @ph, 'hr_admin', 'HR', 'senior', 1);

-- Department Managers
INSERT INTO users (name, email, password_hash, role, department, seniority, is_active) VALUES
('Diana Ross',     'dm1@nexthire.com', @ph, 'dept_manager', 'Engineering', 'lead', 1),
('Thomas Wright',  'dm2@nexthire.com', @ph, 'dept_manager', 'HR',          'lead', 1);

-- Interviewers
INSERT INTO users (name, email, password_hash, role, department, seniority, specializations, is_active) VALUES
('James Wilson',   'iv1@nexthire.com', @ph, 'interviewer', 'Engineering', 'senior', '["PHP","MySQL","JavaScript"]',          1),
('Lisa Park',      'iv2@nexthire.com', @ph, 'interviewer', 'Engineering', 'senior', '["Python","Docker","AWS"]',              1),
('Robert Taylor',  'iv3@nexthire.com', @ph, 'interviewer', 'Engineering', 'mid',    '["React","TypeScript","Node.js"]',       1),
('Amanda Foster',  'iv4@nexthire.com', @ph, 'interviewer', 'HR',          'senior', '["Communication","Leadership"]',         1),
('David Kim',      'iv5@nexthire.com', @ph, 'interviewer', 'Engineering', 'lead',   '["System Design","Architecture"]',       1);

-- Shadow Interviewers
INSERT INTO users (name, email, password_hash, role, department, seniority, is_active) VALUES
('Nina Patel',     'sh1@nexthire.com', @ph, 'shadow', 'Engineering', 'junior', 1);

-- Candidates
INSERT INTO users (name, email, password_hash, role, department, specializations, diversity_gender, diversity_ethnicity, is_active) VALUES
('Alice Cooper',   'c1@nexthire.com',  @ph, 'candidate', 'Engineering', '["PHP","MySQL","Laravel"]',            'Female',     'Asian',    1),
('Bob Martinez',   'c2@nexthire.com',  @ph, 'candidate', 'Engineering', '["JavaScript","React","Node.js"]',     'Male',       'Hispanic', 1),
('Carol White',    'c3@nexthire.com',  @ph, 'candidate', 'Engineering', '["Python","Django","AWS"]',             'Female',     'White',    1),
('Dan Brown',      'c4@nexthire.com',  @ph, 'candidate', 'Engineering', '["PHP","JavaScript","Docker"]',         'Male',       'Black',    1),
('Eva Green',      'c5@nexthire.com',  @ph, 'candidate', 'Engineering', '["TypeScript","Angular","GCP"]',        'Female',     'White',    1),
('Frank Lee',      'c6@nexthire.com',  @ph, 'candidate', 'Engineering', '["Java","Spring","Kubernetes"]',        'Male',       'Asian',    1),
('Grace Hall',     'c7@nexthire.com',  @ph, 'candidate', 'Design',      '["Figma","CSS","UX"]',                  'Female',     'Asian',    1),
('Henry Adams',    'c8@nexthire.com',  @ph, 'candidate', 'Engineering', '["Go","Rust","Linux"]',                 'Male',       'White',    1),
('Iris Chang',     'c9@nexthire.com',  @ph, 'candidate', 'Engineering', '["PHP","MySQL","Redis"]',               'Female',     'Asian',    1),
('Jack Rivera',    'c10@nexthire.com', @ph, 'candidate', 'Engineering', '["Python","ML","TensorFlow"]',          'Male',       'Hispanic', 1),
('Kate Murphy',    'c11@nexthire.com', @ph, 'candidate', 'Engineering', '["JavaScript","Vue","MongoDB"]',        'Female',     'White',    1),
('Leo Torres',     'c12@nexthire.com', @ph, 'candidate', 'Engineering', '["C++","Embedded","IoT"]',              'Male',       'Hispanic', 1),
('Mia Scott',      'c13@nexthire.com', @ph, 'candidate', 'Data',        '["SQL","Python","Tableau"]',            'Female',     'Black',    1),
('Noah Clark',     'c14@nexthire.com', @ph, 'candidate', 'Engineering', '["PHP","Symfony","PostgreSQL"]',        'Male',       'White',    1),
('Olivia Young',   'c15@nexthire.com', @ph, 'candidate', 'Engineering', '["Ruby","Rails","Redis"]',              'Female',     'White',    1),
('Paul Wright',    'c16@nexthire.com', @ph, 'candidate', 'Engineering', '["JavaScript","React","GraphQL"]',      'Male',       'Asian',    1),
('Quinn Baker',    'c17@nexthire.com', @ph, 'candidate', 'Engineering', '["Python","FastAPI","Docker"]',         'Non-binary', 'White',    1),
('Rose Harris',    'c18@nexthire.com', @ph, 'candidate', 'DevOps',      '["AWS","Terraform","CI/CD"]',           'Female',     'Black',    1),
('Sam Nelson',     'c19@nexthire.com', @ph, 'candidate', 'Engineering', '["PHP","MySQL","JavaScript"]',          'Male',       'White',    1),
('Tina Patel',     'c20@nexthire.com', @ph, 'candidate', 'Engineering', '["Java","Spring Boot","MySQL"]',        'Female',     'Asian',    1);


-- JOB REQUISITIONS

INSERT INTO job_requisitions (title, department, description, requirements, level, location_tier, status, created_by) VALUES
('Senior PHP Developer',       'Engineering',    'We are looking for a senior PHP developer to join our backend team. You will work on building scalable APIs, microservices, and data pipelines. Strong MySQL and caching experience required.',  '5+ years PHP, MySQL, Redis, REST APIs',       'L4', 'tier1', 'live',             1),
('Frontend React Developer',   'Engineering',    'Join our frontend team to build beautiful, responsive UIs using React and TypeScript. Experience with state management and testing required.',                                                       '3+ years React, TypeScript, Jest',            'L3', 'tier1', 'live',             1),
('DevOps Engineer',            'Infrastructure', 'Lead our cloud infrastructure on AWS. Implement CI/CD pipelines, manage Kubernetes clusters, and ensure 99.99% uptime.',                                                                           '4+ years AWS, Docker, Kubernetes, Terraform', 'L4', 'tier2', 'live',             2),
('Data Scientist',             'Data',           'Apply ML models to recruitment data. Build predictive analytics for candidate success and pipeline optimization.',                                                                                  'MS/PhD, Python, TensorFlow, SQL',             'L3', 'tier1', 'pending_approval', 2),
('Junior Full-Stack Developer','Engineering',    'Entry-level position for graduates. Work across the stack with PHP, JavaScript, and MySQL.',                                                                                                        'CS degree, basic PHP/JS knowledge',           'L1', 'tier3', 'draft',            3);


-- JOB SKILLS

INSERT INTO job_skills (job_id, skill_name, weight, is_required) VALUES
-- Job 1: Senior PHP Developer
(1, 'PHP',        9.0, 1), (1, 'MySQL',      8.0, 1), (1, 'Redis',      5.0, 0),
(1, 'Docker',     4.0, 0), (1, 'JavaScript', 3.0, 0),
-- Job 2: Frontend React Developer
(2, 'React',      9.0, 1), (2, 'TypeScript', 8.0, 1), (2, 'JavaScript', 7.0, 1),
(2, 'Jest',       4.0, 0), (2, 'CSS',        3.0, 0),
-- Job 3: DevOps Engineer
(3, 'AWS',        9.0, 1), (3, 'Docker',     8.0, 1), (3, 'Kubernetes', 7.0, 1),
(3, 'Terraform',  6.0, 0), (3, 'CI/CD',      5.0, 0),
-- Job 4: Data Scientist
(4, 'Python',     9.0, 1), (4, 'TensorFlow', 7.0, 1), (4, 'SQL',        6.0, 1),
(4, 'ML',         8.0, 1),
-- Job 5: Junior Full-Stack Developer
(5, 'PHP',        7.0, 1), (5, 'JavaScript', 7.0, 1), (5, 'MySQL',      6.0, 1);


-- APPLICATIONS

INSERT INTO applications (job_id, candidate_id, stage, source, resume_text, match_score) VALUES
(1,  9,  'screening',     'direct',    'Experienced PHP developer with 6 years in MySQL and Laravel. Built REST APIs serving 1M+ requests/day. Redis caching expert.',   85.5),
(1,  12, 'applied',       'linkedin',  'Senior backend developer specializing in PHP and MySQL. Docker enthusiast.',                                                       72.3),
(1,  19, 'technical_test','direct',    'PHP developer with 4 years experience. MySQL, JavaScript, some Redis.',                                                            78.1),
(1,  14, 'interview',     'indeed',    'Symfony expert with PostgreSQL and PHP 8 experience.',                                                                             65.4),
(2,  10, 'screening',     'direct',    'React developer with 3 years TypeScript. Built dashboards and SPA apps.',                                                          45.2),
(2,  16, 'technical_test','linkedin',  'Frontend specialist in React, GraphQL, and modern JavaScript.',                                                                    82.7),
(2,  11, 'applied',       'direct',    'Vue.js developer looking to transition to React. Strong JavaScript fundamentals.',                                                 55.0),
(3,  18, 'interview',     'glassdoor', 'DevOps engineer with AWS, Terraform, Docker experience. Built CI/CD for 50+ services.',                                           91.3),
(3,  8,  'screening',     'direct',    'Linux systems admin moving to DevOps. Go and Rust experience.',                                                                    40.5),
(1,  4,  'offer',         'referral',  'Full-stack PHP/JS developer with Docker deployment experience.',                                                                   75.8),
(2,  5,  'hired',         'direct',    'Angular developer transitioning to React. TypeScript expert with GCP.',                                                            60.2),
(1,  1,  'interview',     'direct',    'Laravel specialist with strong MySQL and PHP skills.',                                                                             88.0);


-- PIPELINE STAGE LOGS

INSERT INTO pipeline_stages_log (application_id, from_stage, to_stage, actor_id, reason, changed_at) VALUES
(1,  NULL,             'applied',        9, 'Initial application',  '2026-04-01 10:00:00'),
(1,  'applied',        'screening',      1, 'Auto-screened',        '2026-04-02 09:00:00'),
(3,  NULL,             'applied',       19, 'Initial application',  '2026-04-03 14:00:00'),
(3,  'applied',        'screening',      1, 'Resume reviewed',      '2026-04-04 10:00:00'),
(3,  'screening',      'technical_test', 2, 'Passed screening',     '2026-04-05 11:00:00'),
(10, NULL,             'applied',        4, 'Initial application',  '2026-03-15 09:00:00'),
(10, 'applied',        'screening',      1, 'Auto-screened',        '2026-03-16 10:00:00'),
(10, 'screening',      'technical_test', 1, 'Skills match',         '2026-03-20 11:00:00'),
(10, 'technical_test', 'interview',      2, 'Passed assessment',    '2026-03-25 14:00:00'),
(10, 'interview',      'offer',          1, 'Strong candidate',     '2026-04-01 10:00:00');


-- ASSESSMENTS

INSERT INTO assessments (job_id, title, total_time_minutes, cooldown_months, num_easy, num_medium, num_hard) VALUES
(1, 'PHP Backend Assessment',    60, 3, 5, 5, 3),
(2, 'React Frontend Assessment', 45, 3, 4, 4, 2),
(3, 'DevOps Practical Test',     90, 6, 3, 5, 4),
(5, 'Junior Full-Stack Quiz',    30, 1, 8, 4, 1);


-- QUESTIONS

-- Assessment 1: PHP Backend
INSERT INTO questions (assessment_id, content, type, difficulty, correct_answer, options_json, tags, expected_output, common_answers) VALUES
(1, 'What is the output of: echo 1 + "1" + 1;',                                                   'mcq',    'easy',   '3',             '["1","2","3","Error"]',                                                                                               'PHP',                    NULL,                                                                                            NULL),
(1, 'Which function hashes passwords securely in PHP?',                                            'mcq',    'easy',   'password_hash()','["md5()","sha1()","password_hash()","crypt()"]',                                                                       'PHP,Security',           NULL,                                                                                            NULL),
(1, 'What does PDO stand for?',                                                                    'mcq',    'easy',   'PHP Data Objects','["PHP Data Objects","PHP Database Operations","Prepared Data Output","Primary Data Object"]',                          'PHP,Database',           NULL,                                                                                            NULL),
(1, 'Which HTTP method is idempotent?',                                                            'mcq',    'easy',   'GET',           '["POST","GET","PATCH","DELETE"]',                                                                                       'HTTP,REST',              NULL,                                                                                            NULL),
(1, 'What is the default port for MySQL?',                                                         'mcq',    'easy',   '3306',          '["3306","5432","27017","6379"]',                                                                                        'MySQL',                  NULL,                                                                                            NULL),
(1, 'Write a PHP function that reverses a string without using strrev().',                         'coding', 'medium', NULL,            NULL,                                                                                                                    'PHP',                    'dlrow olleh',                                                                                   'function reverseString'),
(1, 'Explain the difference between abstract classes and interfaces in PHP.',                      'text',   'medium', NULL,            NULL,                                                                                                                    'PHP,OOP',                NULL,                                                                                            NULL),
(1, 'Write a SQL query to find the second highest salary from an employees table.',                'coding', 'medium', NULL,            NULL,                                                                                                                    'SQL,MySQL',              'SELECT MAX(salary) FROM employees WHERE salary < (SELECT MAX(salary) FROM employees)',          NULL),
(1, 'What are the ACID properties in database transactions?',                                      'text',   'medium', NULL,            NULL,                                                                                                                    'Database',               NULL,                                                                                            NULL),
(1, 'Implement a simple LRU cache class in PHP.',                                                  'coding', 'medium', NULL,            NULL,                                                                                                                    'PHP,Data Structures',    NULL,                                                                                            NULL),
(1, 'Design a database schema for a social media feed with likes and comments.',                   'text',   'hard',   NULL,            NULL,                                                                                                                    'System Design,MySQL',    NULL,                                                                                            NULL),
(1, 'Write a PHP function to detect a cycle in a linked list.',                                    'coding', 'hard',   NULL,            NULL,                                                                                                                    'PHP,Algorithms',         NULL,                                                                                            NULL),
(1, 'Explain how PHP-FPM works and how to optimize it for high traffic.',                          'text',   'hard',   NULL,            NULL,                                                                                                                    'PHP,Performance',        NULL,                                                                                            NULL);

-- Assessment 2: React Frontend
INSERT INTO questions (assessment_id, content, type, difficulty, correct_answer, options_json, tags, expected_output, common_answers) VALUES
(2, 'What is the Virtual DOM in React?',                                                           'mcq',    'easy',   'A lightweight copy of the real DOM', '["The actual browser DOM","A lightweight copy of the real DOM","A CSS framework","A testing utility"]', 'React',              NULL, NULL),
(2, 'What hook replaces componentDidMount?',                                                       'mcq',    'easy',   'useEffect',                          '["useState","useEffect","useRef","useMemo"]',                                                          'React,Hooks',        NULL, NULL),
(2, 'What is JSX?',                                                                                'mcq',    'easy',   'JavaScript XML syntax extension',    '["A new language","JavaScript XML syntax extension","A CSS preprocessor","A testing framework"]',       'React',              NULL, NULL),
(2, 'How do you prevent re-renders in React?',                                                     'mcq',    'easy',   'React.memo',                         '["React.memo","React.stop","React.freeze","React.cache"]',                                            'React,Performance',  NULL, NULL),
(2, 'Build a counter component with increment/decrement buttons.',                                 'coding', 'medium', NULL,                                 NULL,                                                                                                  'React',              'Counter: 0\n[+] [-]', NULL),
(2, 'Create a custom hook that debounces a value.',                                                'coding', 'medium', NULL,                                 NULL,                                                                                                  'React,Hooks',        NULL, NULL),
(2, 'Implement a searchable dropdown component.',                                                  'coding', 'medium', NULL,                                 NULL,                                                                                                  'React,UI',           NULL, NULL),
(2, 'Explain React Fiber architecture and reconciliation.',                                        'text',   'medium', NULL,                                 NULL,                                                                                                  'React,Architecture', NULL, NULL),
(2, 'Build an infinite scroll component with virtualization.',                                     'coding', 'hard',   NULL,                                 NULL,                                                                                                  'React,Performance',  NULL, NULL),
(2, 'Design a state management solution without Redux.',                                           'text',   'hard',   NULL,                                 NULL,                                                                                                  'React,Architecture', NULL, NULL);

-- Assessment 3: DevOps Practical
INSERT INTO questions (assessment_id, content, type, difficulty, correct_answer, options_json, tags, expected_output, common_answers) VALUES
(3, 'What is a Docker container?',                                                                 'mcq',    'easy',   'A lightweight isolated process', '["A virtual machine","A lightweight isolated process","A network protocol","A file system"]', 'Docker',              NULL,                     NULL),
(3, 'What is the purpose of a Dockerfile?',                                                        'mcq',    'easy',   'Define how to build an image', '["Run containers","Define how to build an image","Monitor services","Configure networking"]', 'Docker',              NULL,                     NULL),
(3, 'What does kubectl get pods return?',                                                          'mcq',    'easy',   'List of running pods',         '["Docker images","List of running pods","Node status","Service endpoints"]',                   'Kubernetes',          NULL,                     NULL),
(3, 'Write a multi-stage Dockerfile for a Node.js app.',                                           'coding', 'medium', NULL,                           NULL,                                                                                         'Docker',              'FROM node:18 AS builder', NULL),
(3, 'Create a Kubernetes deployment YAML for 3 replicas.',                                         'coding', 'medium', NULL,                           NULL,                                                                                         'Kubernetes',          NULL,                     NULL),
(3, 'Write a Terraform module for an AWS VPC.',                                                    'coding', 'medium', NULL,                           NULL,                                                                                         'Terraform,AWS',       NULL,                     NULL),
(3, 'Design a CI/CD pipeline for a microservices architecture.',                                   'text',   'medium', NULL,                           NULL,                                                                                         'CI/CD,Architecture',  NULL,                     NULL),
(3, 'Set up auto-scaling based on custom metrics in Kubernetes.',                                  'text',   'medium', NULL,                           NULL,                                                                                         'Kubernetes,Scaling',  NULL,                     NULL),
(3, 'Design a blue-green deployment strategy with zero downtime.',                                 'text',   'hard',   NULL,                           NULL,                                                                                         'DevOps,Architecture', NULL,                     NULL),
(3, 'Implement a service mesh configuration with Istio.',                                          'text',   'hard',   NULL,                           NULL,                                                                                         'Kubernetes,Networking',NULL,                    NULL),
(3, 'Design disaster recovery for a multi-region AWS setup.',                                      'text',   'hard',   NULL,                           NULL,                                                                                         'AWS,Architecture',    NULL,                     NULL),
(3, 'Create a monitoring dashboard using Prometheus and Grafana.',                                 'text',   'hard',   NULL,                           NULL,                                                                                         'Monitoring',          NULL,                     NULL);

-- Global Question Bank (assessment_id = NULL)
INSERT INTO questions (assessment_id, content, type, difficulty, correct_answer, options_json, tags, expected_output, common_answers) VALUES
(NULL, 'What is Big O notation?',                                    'mcq',    'easy',   'A way to describe algorithm efficiency', '["A programming language","A way to describe algorithm efficiency","A data structure","A design pattern"]',                    'Algorithms',            NULL, NULL),
(NULL, 'What is REST?',                                              'mcq',    'easy',   'Representational State Transfer',        '["Remote Execution Standard","Representational State Transfer","Real-time Event System","Resource Extraction Service"]',       'API',                   NULL, NULL),
(NULL, 'What is CORS?',                                              'mcq',    'easy',   'Cross-Origin Resource Sharing',          '["Cross-Origin Resource Sharing","Client-Only Request System","Central Object Registry","Content Origin Resolver"]',           'Web,Security',          NULL, NULL),
(NULL, 'What is the difference between TCP and UDP?',                'mcq',    'easy',   'TCP is reliable, UDP is not',            '["No difference","TCP is faster","TCP is reliable, UDP is not","UDP is encrypted"]',                                           'Networking',            NULL, NULL),
(NULL, 'What is a load balancer?',                                   'mcq',    'easy',   'Distributes traffic across servers',     '["A database","Distributes traffic across servers","A caching layer","A firewall"]',                                           'Infrastructure',        NULL, NULL),
(NULL, 'What is the difference between SQL and NoSQL?',              'text',   'easy',   NULL, NULL,                                                                                                                                                               'Database',              NULL, NULL),
(NULL, 'What is dependency injection?',                              'text',   'easy',   NULL, NULL,                                                                                                                                                               'Design Patterns',       NULL, NULL),
(NULL, 'What is the difference between threads and processes?',      'text',   'easy',   NULL, NULL,                                                                                                                                                               'OS',                    NULL, NULL),
(NULL, 'What is container orchestration?',                           'text',   'easy',   NULL, NULL,                                                                                                                                                               'DevOps',                NULL, NULL),
(NULL, 'Write a function to validate balanced parentheses.',         'coding', 'easy',   NULL, NULL,                                                                                                                                                               'Algorithms',            'true', NULL),
(NULL, 'Write a function to detect anagrams.',                       'coding', 'easy',   NULL, NULL,                                                                                                                                                               'Algorithms',            NULL, NULL),
(NULL, 'Write unit tests for a calculator class.',                   'coding', 'easy',   NULL, NULL,                                                                                                                                                               'Testing',               NULL, NULL),
(NULL, 'Explain the CAP theorem.',                                   'text',   'medium', NULL, NULL,                                                                                                                                                               'Distributed Systems',   NULL, NULL),
(NULL, 'What is database sharding?',                                 'text',   'medium', NULL, NULL,                                                                                                                                                               'Database,Scaling',      NULL, NULL),
(NULL, 'Explain event-driven architecture.',                         'text',   'medium', NULL, NULL,                                                                                                                                                               'Architecture',          NULL, NULL),
(NULL, 'What is microservices architecture?',                        'text',   'medium', NULL, NULL,                                                                                                                                                               'Architecture',          NULL, NULL),
(NULL, 'What is eventual consistency?',                              'text',   'medium', NULL, NULL,                                                                                                                                                               'Distributed Systems',   NULL, NULL),
(NULL, 'Explain SOLID principles with examples.',                    'text',   'medium', NULL, NULL,                                                                                                                                                               'OOP,Design',            NULL, NULL),
(NULL, 'What is a deadlock and how to prevent it?',                  'text',   'medium', NULL, NULL,                                                                                                                                                               'OS,Concurrency',        NULL, NULL),
(NULL, 'Explain the Observer pattern.',                              'text',   'medium', NULL, NULL,                                                                                                                                                               'Design Patterns',       NULL, NULL),
(NULL, 'What is the Strategy pattern?',                              'text',   'medium', NULL, NULL,                                                                                                                                                               'Design Patterns',       NULL, NULL),
(NULL, 'What is GraphQL and how does it compare to REST?',           'text',   'medium', NULL, NULL,                                                                                                                                                               'API',                   NULL, NULL),
(NULL, 'Implement binary search in any language.',                   'coding', 'medium', NULL, NULL,                                                                                                                                                               'Algorithms',            'Found at index 3', NULL),
(NULL, 'Write a function to find all permutations of a string.',     'coding', 'medium', NULL, NULL,                                                                                                                                                               'Algorithms',            NULL, NULL),
(NULL, 'Implement a priority queue.',                                'coding', 'medium', NULL, NULL,                                                                                                                                                               'Data Structures',       NULL, NULL),
(NULL, 'Implement merge sort.',                                      'coding', 'medium', NULL, NULL,                                                                                                                                                               'Algorithms',            NULL, NULL),
(NULL, 'Implement depth-first search on a graph.',                   'coding', 'medium', NULL, NULL,                                                                                                                                                               'Algorithms,Graphs',     NULL, NULL),
(NULL, 'Implement breadth-first search.',                            'coding', 'medium', NULL, NULL,                                                                                                                                                               'Algorithms',            NULL, NULL),
(NULL, 'Implement quicksort.',                                       'coding', 'medium', NULL, NULL,                                                                                                                                                               'Algorithms',            NULL, NULL),
(NULL, 'Design a URL shortener system.',                             'text',   'hard',   NULL, NULL,                                                                                                                                                               'System Design',         NULL, NULL),
(NULL, 'Design a real-time chat application architecture.',          'text',   'hard',   NULL, NULL,                                                                                                                                                               'System Design',         NULL, NULL),
(NULL, 'Implement a rate limiter using the token bucket algorithm.', 'coding', 'hard',   NULL, NULL,                                                                                                                                                               'Algorithms,System Design',NULL, NULL),
(NULL, 'What is CQRS and when should you use it?',                   'text',   'hard',   NULL, NULL,                                                                                                                                                               'Architecture',          NULL, NULL),
(NULL, 'Explain consensus algorithms like Raft or Paxos.',           'text',   'hard',   NULL, NULL,                                                                                                                                                               'Distributed Systems',   NULL, NULL),
(NULL, 'Implement a simple hash map from scratch.',                  'coding', 'hard',   NULL, NULL,                                                                                                                                                               'Data Structures',       NULL, NULL),
(NULL, 'Implement a trie data structure.',                           'coding', 'hard',   NULL, NULL,                                                                                                                                                               'Data Structures',       NULL, NULL),
(NULL, 'Design a distributed cache system.',                         'text',   'hard',   NULL, NULL,                                                                                                                                                               'System Design,Caching', NULL, NULL),
(NULL, 'Design a notification system for millions of users.',        'text',   'hard',   NULL, NULL,                                                                                                                                                               'System Design',         NULL, NULL),
(NULL, 'What is a B-tree and where is it used?',                     'text',   'hard',   NULL, NULL,                                                                                                                                                               'Data Structures,Database',NULL, NULL),
(NULL, 'Design an API rate limiting system.',                        'text',   'hard',   NULL, NULL,                                                                                                                                                               'System Design,API',     NULL, NULL);


-- INTERVIEW PANELS

INSERT INTO interview_panels (job_id, application_id, scheduled_at, timezone, duration_minutes, status) VALUES
(1, 4,  '2026-05-15 14:00:00', 'UTC', 60, 'scheduled'),
(1, 12, '2026-05-16 10:00:00', 'UTC', 60, 'scheduled'),
(3, 8,  '2026-05-14 09:00:00', 'UTC', 90, 'completed');


-- PANEL MEMBERS

INSERT INTO panel_members (panel_id, user_id, role) VALUES
(1, 6, 'lead'),    (1, 7, 'technical'), (1, 1, 'hr'),
(2, 6, 'technical'),(2, 9, 'lead'),     (2, 1, 'hr'),  (2, 11, 'shadow'),
(3, 9, 'lead'),    (3, 10, 'technical'), (3, 2, 'hr');


-- FEEDBACK

INSERT INTO feedback_submissions (panel_id, interviewer_id, candidate_id, submitted_at, is_shadow) VALUES
(3, 9, 18, '2026-05-14 11:00:00', 0),
(3, 10, 18, '2026-05-14 11:30:00', 0),
(3, 2, 18, '2026-05-14 12:00:00', 0);

INSERT INTO feedback_dimensions (submission_id, dimension, score, notes) VALUES
(1, 'coding',        8.5, 'Strong coding skills'),
(1, 'system_design', 9.0, 'Excellent architecture knowledge'),
(1, 'communication', 7.5, 'Clear explanations'),
(1, 'culture_fit',   8.0, 'Great team player'),
(2, 'coding',        7.0, 'Good but needs improvement'),
(2, 'system_design', 8.5, 'Solid understanding'),
(2, 'communication', 8.0, 'Very articulate'),
(2, 'culture_fit',   7.5, 'Good fit'),
(3, 'coding',        6.0, 'N/A for HR'),
(3, 'system_design', 6.0, 'N/A'),
(3, 'communication', 9.0, 'Excellent communicator'),
(3, 'culture_fit',   8.5, 'Perfect culture fit');


-- OFFERS

INSERT INTO offers (application_id, salary, signing_bonus, equity, status, created_by, expires_at) VALUES
(10, 135000.00, 13500.00, 1000.0000, 'sent', 1, '2026-05-20 00:00:00');


-- NOTIFICATIONS

INSERT INTO notifications (user_id, type, message) VALUES
(1, 'system',      'Welcome to NextHire! Your HR Admin account is ready.'),
(4, 'interview',   'You have an upcoming interview scheduled for May 15.'),
(9, 'application', 'Your application for Senior PHP Developer is being reviewed.');


-- AUDIT LOG

INSERT INTO audit_log (actor_id, entity_type, entity_id, action, ip_address) VALUES
(1, 'user',            1, 'login',            '127.0.0.1'),
(1, 'job_requisition', 1, 'created',          '127.0.0.1'),
(1, 'job_requisition', 2, 'created',          '127.0.0.1'),
(2, 'job_requisition', 3, 'created',          '127.0.0.1'),
(1, 'application',    10, 'stage_transition', '127.0.0.1');


-- INTERVIEWER SLOTS

INSERT INTO interviewer_slots (user_id, date, start_time, end_time, is_booked) VALUES
(4, '2026-05-15', '09:00:00', '12:00:00', 0), (4, '2026-05-15', '14:00:00', '17:00:00', 1),
(4, '2026-05-16', '09:00:00', '12:00:00', 0), (4, '2026-05-16', '14:00:00', '17:00:00', 0),
(5, '2026-05-15', '10:00:00', '13:00:00', 0), (5, '2026-05-15', '14:00:00', '16:00:00', 1),
(5, '2026-05-16', '09:00:00', '11:00:00', 1), (5, '2026-05-16', '13:00:00', '17:00:00', 0),
(6, '2026-05-15', '09:00:00', '17:00:00', 0), (6, '2026-05-16', '09:00:00', '17:00:00', 0);


-- SENTIMENT LOGS

INSERT INTO sentiment_logs (candidate_id, interview_panel_id, score, comment) VALUES
(18, 3, 5, 'Great experience, interviewers were very professional and welcoming.');


-- JOB TEMPLATES

INSERT INTO job_templates (type, content, version, is_active, created_by) VALUES
('description',
 '## About the Role\n\n[Job Title] at [Department]\n\n## Responsibilities\n- [Responsibility 1]\n- [Responsibility 2]\n\n## Requirements\n- [Requirement 1]\n- [Requirement 2]\n\n## Benefits\n- Competitive salary\n- Equity package\n- Health insurance',
 1, 1, 1),
('rubric',
 '## Evaluation Rubric\n\n### Coding (0-10)\n- Code quality and readability\n- Problem-solving approach\n\n### System Design (0-10)\n- Architecture decisions\n- Scalability considerations\n\n### Communication (0-10)\n- Clarity of explanation\n- Active listening\n\n### Culture Fit (0-10)\n- Team collaboration\n- Growth mindset',
 1, 1, 1);