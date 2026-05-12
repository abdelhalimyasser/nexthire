<?php
declare(strict_types=1);
/**
 * EmailService — Real Gmail SMTP via PHP sockets (STARTTLS on port 587)
 * No external libraries required.
 */

class EmailService {

    private static ?EmailService $instance = null;

    private PDO $db;

    private function __construct() { $this->db = Database::getInstance(); }

    public static function getInstance(): self {
        if (!self::$instance) self::$instance = new self();
        return self::$instance;
    }

    // Public API

    public function send(string $to, string $subject, string $bodyHtml,
                         ?string $entity = null, ?int $entityId = null): bool {
        $this->db->prepare(
            "INSERT INTO email_log (recipient,subject,body_html,status,related_entity,related_id)
             VALUES(:r,:s,:b,'queued',:e,:eid)"
        )->execute(["r"=>$to,"s"=>$subject,"b"=>$bodyHtml,"e"=>$entity,"eid"=>$entityId]);
        $logId = (int)$this->db->lastInsertId();

        try {
            $this->smtpSend($to, $subject, $bodyHtml);
            $this->db->prepare("UPDATE email_log SET status='sent', sent_at=NOW() WHERE id=:id")
                     ->execute(["id"=>$logId]);
            return true;
        } catch (\Throwable $e) {
            error_log("EmailService error: " . $e->getMessage());
            $this->db->prepare("UPDATE email_log SET status='failed' WHERE id=:id")
                     ->execute(["id"=>$logId]);
            return false;
        }
    }

    public function sendTemplate(string $to, string $subject, string $tpl, array $vars): bool {
        return $this->send($to, $subject, $this->renderTemplate($tpl, $vars));
    }

    /** Expose template rendering so controllers can build HTML for attachments */
    public function renderPublicTemplate(string $tpl, array $vars): string {
        return $this->renderTemplate($tpl, $vars);
    }

    public function sendWithAttachment(string $to, string $subject, string $bodyHtml,
                                       string $filePath, string $fileName): bool {
        $this->db->prepare(
            "INSERT INTO email_log (recipient,subject,body_html,status) VALUES(:r,:s,:b,'queued')"
        )->execute(["r"=>$to,"s"=>$subject,"b"=>$bodyHtml]);
        $logId = (int)$this->db->lastInsertId();

        try {
            $this->smtpSend($to, $subject, $bodyHtml, $filePath, $fileName);
            $this->db->prepare("UPDATE email_log SET status='sent', sent_at=NOW() WHERE id=:id")
                     ->execute(["id"=>$logId]);
            return true;
        } catch (\Throwable $e) {
            error_log("EmailService attachment error: " . $e->getMessage());
            $this->db->prepare("UPDATE email_log SET status='failed' WHERE id=:id")
                     ->execute(["id"=>$logId]);
            return false;
        }
    }

    // SMTP Client (Gmail STARTTLS port 587)    

    private function smtpSend(string $to, string $subject, string $bodyHtml,
                               ?string $attachPath = null, ?string $attachName = null): void {
        $host    = defined('SMTP_HOST')       ? SMTP_HOST       : 'smtp.gmail.com';
        $port    = defined('SMTP_PORT')       ? SMTP_PORT       : 587;
        $user    = defined('SMTP_USER')       ? SMTP_USER       : '';
        $pass    = defined('SMTP_PASS')       ? SMTP_PASS       : '';
        $from    = defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : $user;
        $fromName= defined('SMTP_FROM_NAME')  ? SMTP_FROM_NAME  : 'NextHire';

        $sock = fsockopen($host, $port, $errno, $errstr, 15);
        if (!$sock) throw new \RuntimeException("SMTP connect failed: $errstr ($errno)");

        $read = fn() => fgets($sock, 515);
        $write = function(string $cmd) use ($sock) {
            fputs($sock, $cmd . "\r\n");
        };

        // Greeting
        $read();
        $write("EHLO localhost");
        while (($line = $read()) !== false) { if ($line[3] === ' ') break; }

        // STARTTLS
        $write("STARTTLS");
        $read();
        stream_socket_enable_crypto($sock, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT);

        // Re-EHLO after TLS
        $write("EHLO localhost");
        while (($line = $read()) !== false) { if ($line[3] === ' ') break; }

        // AUTH LOGIN
        $write("AUTH LOGIN");
        $read();
        $write(base64_encode($user));
        $read();
        $write(base64_encode($pass));
        $resp = $read();
        if (!str_starts_with($resp, '235')) {
            fclose($sock);
            throw new \RuntimeException("SMTP auth failed: $resp");
        }

        // MAIL FROM / RCPT TO
        $write("MAIL FROM:<$from>");
        $read();
        $write("RCPT TO:<$to>");
        $read();

        // DATA
        $write("DATA");
        $read();

        // Build message
        $boundary = 'NextHire_' . bin2hex(random_bytes(8));
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';

        $headers  = "From: $fromName <$from>\r\n";
        $headers .= "To: $to\r\n";
        $headers .= "Subject: $encodedSubject\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Date: " . date('r') . "\r\n";

        if ($attachPath && file_exists($attachPath)) {
            $headers .= "Content-Type: multipart/mixed; boundary=\"$boundary\"\r\n\r\n";
            $body  = "--$boundary\r\n";
            $body .= "Content-Type: text/html; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $body .= chunk_split(base64_encode($bodyHtml)) . "\r\n";
            $body .= "--$boundary\r\n";
            $body .= "Content-Type: application/pdf; name=\"$attachName\"\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n";
            $body .= "Content-Disposition: attachment; filename=\"$attachName\"\r\n\r\n";
            $body .= chunk_split(base64_encode(file_get_contents($attachPath))) . "\r\n";
            $body .= "--$boundary--\r\n";
        } else {
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $body = chunk_split(base64_encode($bodyHtml));
        }

        fputs($sock, $headers . $body . "\r\n.\r\n");
        $read();
        $write("QUIT");
        fclose($sock);
    }

    // ----------------------------------------------------------------
    // Templates
    // ----------------------------------------------------------------

    protected function renderTemplate(string $name, array $vars): string {
        $v = array_map('htmlspecialchars', $vars);

        $wrapper = '<!DOCTYPE html><html><head><meta charset="UTF-8"><style>
            body{font-family:Inter,Arial,sans-serif;margin:0;padding:0;background:#f1f5f9;}
            .container{max-width:600px;margin:20px auto;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,.08);}
            .header{background:linear-gradient(135deg,#4f46e5,#7c3aed);padding:30px;text-align:center;color:#fff;}
            .header h1{margin:0;font-size:24px;}
            .body{padding:30px;color:#334155;line-height:1.6;}
            .footer{background:#f8fafc;padding:20px;text-align:center;font-size:12px;color:#94a3b8;}
            .btn{display:inline-block;padding:12px 30px;background:#4f46e5;color:#fff!important;text-decoration:none;border-radius:8px;font-weight:600;margin-top:16px;}
            .info-box{background:#f1f5f9;border-radius:8px;padding:16px;margin:16px 0;font-size:14px;}
            .tag{display:inline-block;padding:2px 10px;border-radius:20px;font-size:12px;font-weight:600;}
            .tag-green{background:#d1fae5;color:#065f46;} .tag-red{background:#fee2e2;color:#991b1b;}
            .tag-blue{background:#dbeafe;color:#1e40af;} .tag-amber{background:#fef3c7;color:#92400e;}
        </style></head><body><div class="container">
        <div class="header"><h1>NextHire</h1><p style="margin:4px 0 0;opacity:.8;">Smart Recruitment Platform</p></div>
        <div class="body">{{CONTENT}}</div>
        <div class="footer">This is an automated message. Please do not reply directly to this email.</div>
        </div></body></html>';

        $content = match($name) {
            "account_created" =>
                "<h2>Welcome to NextHire!</h2>
                 <p>Hello <strong>{$v['name']}</strong>,</p>
                 <p>Your account has been created successfully.</p>
                 <div class='info-box'><strong>Role:</strong> {$v['role']}<br><strong>Login Email:</strong> {$v['name']}</div>
                 <a href='{$vars['link']}' class='btn'>Sign In Now</a>",

            "interview_scheduled" =>
                "<h2>Interview Scheduled</h2>
                 <p>Hello <strong>{$v['name']}</strong>,</p>
                 <p>Your interview has been confirmed for:</p>
                 <div class='info-box'>
                    <strong>Position:</strong> {$v['job_title']}<br>
                    <strong>Date &amp; Time:</strong> {$v['date']}<br>
                    <strong>Duration:</strong> {$v['duration']} minutes<br>
                    " . (!empty($vars['meeting_link']) ? "<strong>Meeting Link:</strong> <a href='{$vars['meeting_link']}'>{$vars['meeting_link']}</a>" : "") . "
                 </div>
                 <p>Please be ready 5 minutes before your scheduled time.</p>",

            "interview_reminder" =>
                "<h2>Interview Reminder</h2>
                 <p>Hello <strong>{$v['name']}</strong>,</p>
                 <p>This is a reminder that your interview is in <strong>24 hours</strong>.</p>
                 <div class='info-box'>
                    <strong>Position:</strong> {$v['job_title']}<br>
                    <strong>Date &amp; Time:</strong> {$v['date']}
                 </div>",

            "offer_created" =>
                "<h2>You Have Received an Offer!</h2>
                 <p>Dear <strong>{$v['name']}</strong>,</p>
                 <p>We are delighted to extend you an offer for the position of <strong>{$v['job_title']}</strong>.</p>
                 <div class='info-box'>
                    <strong>Base Salary:</strong> \${$v['salary']}/year<br>
                    <strong>Signing Bonus:</strong> \${$v['bonus']}<br>
                 </div>
                 <a href='{$vars['link']}' class='btn'>View &amp; Respond to Offer</a>
                 <p style='font-size:12px;color:#94a3b8;margin-top:16px;'>This offer expires in 7 days.</p>",

            "offer_accepted" =>
                "<h2>Offer Accepted</h2>
                 <p><strong>{$v['name']}</strong> has accepted the offer for <strong>{$v['job_title']}</strong>.</p>
                 <div class='info-box'><span class='tag tag-green'>Accepted</span></div>",

            "offer_declined" =>
                "<h2>Offer Declined</h2>
                 <p><strong>{$v['name']}</strong> has declined the offer for <strong>{$v['job_title']}</strong>.</p>
                 <div class='info-box'><span class='tag tag-red'>Declined</span></div>",

            "job_created" =>
                "<h2>New Job Requisition</h2>
                 <p>A new job requisition requires your attention:</p>
                 <div class='info-box'>
                    <strong>{$v['title']}</strong><br>
                    Department: {$v['department']}<br>
                    Level: {$v['level']}
                 </div>",

            "requisition_approved" =>
                "<h2>Job Requisition Approved</h2>
                 <p>The requisition <strong>{$v['title']}</strong> has been <span class='tag tag-green'>Approved</span> by {$v['approver']}.</p>
                 " . (!empty($vars['comments']) ? "<div class='info-box'>{$v['comments']}</div>" : ""),

            "requisition_rejected" =>
                "<h2>Job Requisition Rejected</h2>
                 <p>The requisition <strong>{$v['title']}</strong> has been <span class='tag tag-red'>Rejected</span> by {$v['approver']}.</p>
                 " . (!empty($vars['reason']) ? "<div class='info-box'><strong>Reason:</strong> {$v['reason']}</div>" : ""),

            "assessment_violation" =>
                "<h2>Assessment Integrity Alert</h2>
                 <p>A candidate has been <strong>auto-flagged</strong> for proctoring violations:</p>
                 <div class='info-box'>
                    <strong>Candidate:</strong> {$v['candidate_name']}<br>
                    <strong>Assessment:</strong> {$v['assessment_title']}<br>
                    <strong>Violations:</strong> {$v['strikes']} tab switches<br>
                    <strong>Integrity Score:</strong> {$v['integrity_score']}%
                 </div>
                 <span class='tag tag-red'>Auto-Flagged</span>",

            "referral_invite" =>
                "<h2>You Have Been Referred!</h2>
                 <p>Hello <strong>{$v['name']}</strong>,</p>
                 <p><strong>{$v['referrer']}</strong> has referred you to apply for a position at NextHire.</p>
                 " . (!empty($vars['job_title']) ? "<div class='info-box'><strong>Position:</strong> {$v['job_title']}</div>" : "") . "
                 <a href='{$vars['link']}' class='btn'>Create Your Account</a>
                 <p style='font-size:12px;color:#94a3b8;margin-top:16px;'>This invitation expires in 7 days.</p>",

            "password_reset" =>
                "<h2>Reset Your Password</h2>
                 <p>Click the button below to reset your password:</p>
                 <a href='{$vars['link']}' class='btn'>Reset Password</a>
                 <p style='font-size:12px;color:#94a3b8;margin-top:16px;'>This link expires in 1 hour.</p>",

            default =>
                "<p>" . ($vars['message'] ?? '') . "</p>",
        };

        return str_replace('{{CONTENT}}', $content, $wrapper);
    }
}
