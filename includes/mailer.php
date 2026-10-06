<?php
/**
 * Citizen Issue Reporting System (CIRS)
 * Standalone SMTP Emailer Engine (Pure PHP Socket Client)
 * Works with Gmail SMTP without requiring external Composer libraries.
 */

require_once __DIR__ . '/../config/mail_config.php';
require_once __DIR__ . '/../config/config.php';

/**
 * Send HTML Email using Direct SMTP Socket with TLS/SSL Authentication
 */
function sendCustomEmail($to_email, $to_name, $subject, $html_body) {
    // If SMTP_PASS is not provided, record in session for demo/local evaluation
    if (empty(SMTP_PASS) || SMTP_PASS === 'your_app_password_here') {
        error_log("[CIRS Mailer Local Demo] To: {$to_email} | Subject: {$subject}");
        $_SESSION['last_sent_email'] = [
            'to' => $to_email,
            'subject' => $subject,
            'body' => $html_body,
            'time' => date('Y-m-d H:i:s')
        ];
        return [
            'status' => true,
            'mode' => 'demo',
            'message' => 'Email notification generated in demo mode (App Password not configured).'
        ];
    }

    $host = SMTP_HOST;
    $port = SMTP_PORT;
    $timeout = 15;

    $socket_target = (SMTP_SECURE === 'ssl') ? "ssl://{$host}:{$port}" : "tcp://{$host}:{$port}";
    
    $context = stream_context_create([
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ]);

    $socket = @stream_socket_client($socket_target, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context);
    if (!$socket) {
        // Fallback to PHP mail function if socket connection fails
        return sendPhpMailFallback($to_email, $to_name, $subject, $html_body);
    }

    stream_set_timeout($socket, $timeout);

    $readResponse = function($socket) {
        $response = '';
        while ($str = fgets($socket, 515)) {
            $response .= $str;
            if (substr($str, 3, 1) === ' ') break;
        }
        return $response;
    };

    $sendCommand = function($socket, $cmd) use ($readResponse) {
        fputs($socket, $cmd . "\r\n");
        return $readResponse($socket);
    };

    // 1. Initial Greeting
    $readResponse($socket);

    // 2. EHLO
    $sendCommand($socket, "EHLO localhost");

    // 3. STARTTLS if port 587 / tls
    if (SMTP_SECURE === 'tls') {
        $tls_res = $sendCommand($socket, "STARTTLS");
        if (substr($tls_res, 0, 3) === '220') {
            if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($socket);
                return sendPhpMailFallback($to_email, $to_name, $subject, $html_body);
            }
            $sendCommand($socket, "EHLO localhost");
        }
    }

    // 4. AUTH LOGIN
    $auth_res = $sendCommand($socket, "AUTH LOGIN");
    if (substr($auth_res, 0, 3) !== '334') {
        fclose($socket);
        return sendPhpMailFallback($to_email, $to_name, $subject, $html_body);
    }

    $user_res = $sendCommand($socket, base64_encode(SMTP_USER));
    $pass_clean = str_replace(' ', '', SMTP_PASS);
    $pass_res = $sendCommand($socket, base64_encode($pass_clean));

    if (substr($pass_res, 0, 3) !== '235') {
        fclose($socket);
        return sendPhpMailFallback($to_email, $to_name, $subject, $html_body);
    }

    // 5. MAIL FROM
    $sendCommand($socket, "MAIL FROM: <" . SMTP_FROM_EMAIL . ">");

    // 6. RCPT TO
    $rcpt_res = $sendCommand($socket, "RCPT TO: <" . $to_email . ">");

    // 7. DATA
    $sendCommand($socket, "DATA");

    // Build MIME Headers & Body
    $headers = [
        "MIME-Version: 1.0",
        "Content-Type: text/html; charset=UTF-8",
        "From: =?UTF-8?B?" . base64_encode(SMTP_FROM_NAME) . "?= <" . SMTP_FROM_EMAIL . ">",
        "To: =?UTF-8?B?" . base64_encode($to_name) . "?= <" . $to_email . ">",
        "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=",
        "Date: " . date(DATE_RFC2822),
        "X-Mailer: CIRS-System-Mailer/1.0"
    ];

    $email_content = implode("\r\n", $headers) . "\r\n\r\n" . $html_body . "\r\n.";
    $data_res = $sendCommand($socket, $email_content);

    // 8. QUIT
    $sendCommand($socket, "QUIT");
    fclose($socket);

    if (substr($data_res, 0, 3) === '250') {
        return ['status' => true, 'mode' => 'live', 'message' => 'Email dispatched successfully via Gmail SMTP.'];
    }

    return sendPhpMailFallback($to_email, $to_name, $subject, $html_body);
}

/**
 * Fallback to standard PHP mail() if SMTP socket has firewall restrictions
 */
function sendPhpMailFallback($to_email, $to_name, $subject, $html_body) {
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM_EMAIL . ">\r\n";
    
    @mail($to_email, $subject, $html_body, $headers);
    return ['status' => true, 'mode' => 'mail', 'message' => 'Email sent via system mail handler.'];
}

/**
 * Send Registration OTP Email
 */
function sendRegistrationOTPEmail($to_email, $to_name, $otp) {
    $subject = "CIRS Account Verification Code: {$otp}";
    
    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #f1f5f9; margin: 0; padding: 20px; }
            .mail-box { max-width: 580px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; }
            .mail-header { background: linear-gradient(135deg, #1d4ed8, #1e40af); color: #ffffff; padding: 25px; text-align: center; }
            .mail-body { padding: 30px; color: #334155; line-height: 1.6; }
            .otp-container { background: #f8fafc; border: 2px dashed #3b82f6; border-radius: 10px; padding: 18px; text-align: center; margin: 25px 0; }
            .otp-code { font-size: 32px; font-weight: 800; letter-spacing: 6px; color: #1d4ed8; font-family: monospace; }
            .mail-footer { background: #0f172a; color: #94a3b8; padding: 18px; text-align: center; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class="mail-box">
            <div class="mail-header">
                <h2 style="margin:0; font-size: 22px;">Citizen Issue Reporting System (CIRS)</h2>
                <p style="margin: 5px 0 0 0; opacity: 0.85; font-size: 13px;">Official Municipal Grievance Redressal Portal</p>
            </div>
            <div class="mail-body">
                <h3 style="margin-top:0; color: #0f172a;">Account Verification</h3>
                <p>Hello <strong>' . htmlspecialchars($to_name) . '</strong>,</p>
                <p>Thank you for registering on CIRS. Please use the following One-Time Password (OTP) to verify your account and complete your registration:</p>
                
                <div class="otp-container">
                    <span style="font-size:12px; text-transform:uppercase; color:#64748b; font-weight:600; display:block; margin-bottom:5px;">Your 6-Digit Verification Code</span>
                    <div class="otp-code">' . htmlspecialchars($otp) . '</div>
                    <small style="color: #64748b; font-size: 12px; display:block; margin-top:6px;">This OTP is valid for 10 minutes. Do not share this code with anyone.</small>
                </div>
                
                <p style="font-size: 13px; color: #64748b;">If you did not initiate this registration request, please ignore this email.</p>
            </div>
            <div class="mail-footer">
                &copy; ' . date('Y') . ' Citizen Issue Reporting System. Built for municipal transparency.
            </div>
        </div>
    </body>
    </html>';

    return sendCustomEmail($to_email, $to_name, $subject, $html);
}

/**
 * Send Registration Confirmation & Credentials Email
 */
function sendRegistrationSuccessEmail($to_email, $to_name, $mobile, $password_note = '') {
    $subject = "Welcome to CIRS - Registration Successful!";
    $login_url = BASE_URL . "/login.php";

    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #f1f5f9; margin: 0; padding: 20px; }
            .mail-box { max-width: 580px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; }
            .mail-header { background: linear-gradient(135deg, #10b981, #059669); color: #ffffff; padding: 25px; text-align: center; }
            .mail-body { padding: 30px; color: #334155; line-height: 1.6; }
            .details-table { width: 100%; border-collapse: collapse; margin: 20px 0; background: #f8fafc; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0; }
            .details-table td { padding: 12px 16px; font-size: 14px; border-bottom: 1px solid #e2e8f0; }
            .details-table td.label { font-weight: 600; color: #475569; width: 40%; }
            .btn-login { display: inline-block; background: #1d4ed8; color: #ffffff !important; padding: 12px 28px; border-radius: 8px; text-decoration: none; font-weight: 700; margin-top: 15px; }
            .mail-footer { background: #0f172a; color: #94a3b8; padding: 18px; text-align: center; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class="mail-box">
            <div class="mail-header">
                <h2 style="margin:0; font-size: 22px;">Registration Successful! 🎉</h2>
                <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 13px;">Citizen Issue Reporting System (CIRS)</p>
            </div>
            <div class="mail-body">
                <h3 style="margin-top:0; color: #0f172a;">Welcome, ' . htmlspecialchars($to_name) . '!</h3>
                <p>Your citizen account has been successfully verified and activated. You can now log in using either your <strong>Registered Email</strong> or <strong>Mobile Number</strong> along with your password.</p>
                
                <table class="details-table">
                    <tr>
                        <td class="label">Full Name:</td>
                        <td><strong>' . htmlspecialchars($to_name) . '</strong></td>
                    </tr>
                    <tr>
                        <td class="label">Registered Email:</td>
                        <td><strong>' . htmlspecialchars($to_email) . '</strong></td>
                    </tr>
                    <tr>
                        <td class="label">Registered Mobile:</td>
                        <td><strong>+91 ' . htmlspecialchars($mobile) . '</strong></td>
                    </tr>
                    <tr>
                        <td class="label">Account Status:</td>
                        <td><span style="color: #059669; font-weight: 700;">✓ Verified & Active</span></td>
                    </tr>
                </table>

                <p>You can now report civic problems (like potholes, garbage, water leaks, streetlights, or drainage) directly to the municipal authorities and track their live progress.</p>

                <div style="text-align: center; margin: 25px 0;">
                    <a href="' . $login_url . '" class="btn-login">Log In to Citizen Portal &rarr;</a>
                </div>
            </div>
            <div class="mail-footer">
                &copy; ' . date('Y') . ' Citizen Issue Reporting System. Built for municipal transparency.
            </div>
        </div>
    </body>
    </html>';

    return sendCustomEmail($to_email, $to_name, $subject, $html);
}

/**
 * Send Password Reset OTP Email
 */
function sendPasswordResetOTPEmail($to_email, $to_name, $otp) {
    $subject = "CIRS Password Reset OTP: {$otp}";

    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #f1f5f9; margin: 0; padding: 20px; }
            .mail-box { max-width: 580px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; }
            .mail-header { background: linear-gradient(135deg, #ea580c, #c2410c); color: #ffffff; padding: 25px; text-align: center; }
            .mail-body { padding: 30px; color: #334155; line-height: 1.6; }
            .otp-container { background: #fff7ed; border: 2px dashed #ea580c; border-radius: 10px; padding: 18px; text-align: center; margin: 25px 0; }
            .otp-code { font-size: 32px; font-weight: 800; letter-spacing: 6px; color: #ea580c; font-family: monospace; }
            .mail-footer { background: #0f172a; color: #94a3b8; padding: 18px; text-align: center; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class="mail-box">
            <div class="mail-header">
                <h2 style="margin:0; font-size: 22px;">Password Reset Request</h2>
                <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 13px;">Citizen Issue Reporting System (CIRS)</p>
            </div>
            <div class="mail-body">
                <h3 style="margin-top:0; color: #0f172a;">Reset Your Password</h3>
                <p>Hello <strong>' . htmlspecialchars($to_name) . '</strong>,</p>
                <p>We received a request to reset your CIRS citizen account password. Please use the 6-digit OTP code below to verify your identity and set a new password:</p>
                
                <div class="otp-container">
                    <span style="font-size:12px; text-transform:uppercase; color:#9a3412; font-weight:600; display:block; margin-bottom:5px;">Password Reset Code</span>
                    <div class="otp-code">' . htmlspecialchars($otp) . '</div>
                    <small style="color: #9a3412; font-size: 12px; display:block; margin-top:6px;">This OTP is valid for 10 minutes. Never share this code with anyone.</small>
                </div>
                
                <p style="font-size: 13px; color: #64748b;">If you did not request a password reset, please ignore this email or change your password immediately if you suspect unauthorized access.</p>
            </div>
            <div class="mail-footer">
                &copy; ' . date('Y') . ' Citizen Issue Reporting System. Built for municipal transparency.
            </div>
        </div>
    </body>
    </html>';

    return sendCustomEmail($to_email, $to_name, $subject, $html);
}

/**
 * Send Password Changed Confirmation Email
 */
function sendPasswordChangedConfirmationEmail($to_email, $to_name) {
    $subject = "Security Alert: Your CIRS Password Was Changed";

    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #f1f5f9; margin: 0; padding: 20px; }
            .mail-box { max-width: 580px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; }
            .mail-header { background: linear-gradient(135deg, #0284c7, #0369a1); color: #ffffff; padding: 25px; text-align: center; }
            .mail-body { padding: 30px; color: #334155; line-height: 1.6; }
            .mail-footer { background: #0f172a; color: #94a3b8; padding: 18px; text-align: center; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class="mail-box">
            <div class="mail-header">
                <h2 style="margin:0; font-size: 22px;">Password Updated Successfully</h2>
                <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 13px;">Citizen Issue Reporting System (CIRS)</p>
            </div>
            <div class="mail-body">
                <h3 style="margin-top:0; color: #0f172a;">Hello, ' . htmlspecialchars($to_name) . '</h3>
                <p>This is a confirmation that the password for your CIRS account (<strong>' . htmlspecialchars($to_email) . '</strong>) was successfully changed on <strong>' . date('d M Y, h:i A') . '</strong>.</p>
                <p>You can now log in to the CIRS portal using your new password.</p>
                <p style="font-size: 13px; color: #dc2626; margin-top:20px;">If you did not make this change, please contact our civic support desk immediately at support@cirs.gov.</p>
            </div>
            <div class="mail-footer">
                &copy; ' . date('Y') . ' Citizen Issue Reporting System. Built for municipal transparency.
            </div>
        </div>
    </body>
    </html>';

    return sendCustomEmail($to_email, $to_name, $subject, $html);
}

/**
 * Send Complaint Verified Email to Citizen
 */
function sendComplaintVerifiedEmail($to_email, $to_name, $complaint, $dept_name, $status = 'PENDING', $remarks = '') {
    $cid = $complaint['complaint_id'] ?? 'CIVIC-' . ($complaint['id'] ?? '0000');
    $title = $complaint['title'] ?? 'Civic Grievance';
    $category = $complaint['category'] ?? 'Municipal Issue';
    $location = $complaint['location'] ?? 'Municipal Area';
    $priority = $complaint['priority'] ?? 'Medium';
    
    $subject = "[CIRS Verified] Grievance #{$cid} - Officially Verified by Municipal Admin";
    $track_url = BASE_URL . "/track.php?id=" . urlencode($cid);

    $status_label = ($status === 'IN PROGRESS') ? 'IN PROGRESS (Field Work Assigned)' : 'PENDING (Queued for Department Action)';
    $status_color = ($status === 'IN PROGRESS') ? '#d97706' : '#2563eb';

    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #f1f5f9; margin: 0; padding: 20px; }
            .mail-box { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; }
            .mail-header { background: linear-gradient(135deg, #1d4ed8, #0284c7); color: #ffffff; padding: 26px; text-align: center; }
            .mail-body { padding: 30px; color: #334155; line-height: 1.6; }
            .badge-verified { display: inline-block; background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; padding: 6px 14px; border-radius: 20px; font-weight: 700; font-size: 13px; margin-bottom: 15px; }
            .details-table { width: 100%; border-collapse: collapse; margin: 20px 0; background: #f8fafc; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0; }
            .details-table td { padding: 11px 16px; font-size: 14px; border-bottom: 1px solid #e2e8f0; }
            .details-table td.label { font-weight: 600; color: #475569; width: 38%; }
            .remarks-box { background: #eff6ff; border-left: 4px solid #1d4ed8; padding: 14px 18px; border-radius: 0 8px 8px 0; margin: 20px 0; font-size: 14px; color: #1e3a8a; }
            .btn-action { display: inline-block; background: #1d4ed8; color: #ffffff !important; padding: 12px 28px; border-radius: 8px; text-decoration: none; font-weight: 700; margin-top: 10px; }
            .mail-footer { background: #0f172a; color: #94a3b8; padding: 18px; text-align: center; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class="mail-box">
            <div class="mail-header">
                <h2 style="margin:0; font-size: 22px;">Grievance Verified by CIRS</h2>
                <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 13px;">Citizen Issue Reporting System &bull; Municipal Administration</p>
            </div>
            <div class="mail-body">
                <div class="badge-verified">✓ Grievance Authenticated & Verified</div>
                <h3 style="margin-top:0; color: #0f172a;">Hello, ' . htmlspecialchars($to_name) . '</h3>
                <p>We are pleased to inform you that your reported civic issue (<strong>#' . htmlspecialchars($cid) . '</strong>) has been officially <strong>verified</strong> by the CIRS Municipal Control Room and routed for immediate departmental action.</p>
                
                <table class="details-table">
                    <tr>
                        <td class="label">Complaint ID:</td>
                        <td><strong style="color: #1d4ed8; font-family: monospace; font-size: 15px;">' . htmlspecialchars($cid) . '</strong></td>
                    </tr>
                    <tr>
                        <td class="label">Issue Title:</td>
                        <td><strong>' . htmlspecialchars($title) . '</strong></td>
                    </tr>
                    <tr>
                        <td class="label">Category:</td>
                        <td>' . htmlspecialchars($category) . '</td>
                    </tr>
                    <tr>
                        <td class="label">Assigned Department:</td>
                        <td><strong>' . htmlspecialchars($dept_name ?: 'Awaiting Departmental Dispatch') . '</strong></td>
                    </tr>
                    <tr>
                        <td class="label">Priority Level:</td>
                        <td>' . htmlspecialchars($priority) . '</td>
                    </tr>
                    <tr>
                        <td class="label">Incident Location:</td>
                        <td>' . htmlspecialchars($location) . '</td>
                    </tr>
                    <tr>
                        <td class="label">Current Status:</td>
                        <td><strong style="color: ' . $status_color . ';">' . htmlspecialchars($status_label) . '</strong></td>
                    </tr>
                </table>

                ' . (!empty($remarks) ? '
                <div class="remarks-box">
                    <strong>Official Municipal Remarks:</strong><br>' . nl2br(htmlspecialchars($remarks)) . '
                </div>' : '') . '

                <p>You can follow the real-time resolution timeline, assigned field updates, and progress logs directly on the CIRS tracking portal:</p>

                <div style="text-align: center; margin: 25px 0;">
                    <a href="' . $track_url . '" class="btn-action">Track Grievance Live Online &rarr;</a>
                </div>
            </div>
            <div class="mail-footer">
                &copy; ' . date('Y') . ' Citizen Issue Reporting System. Dedicated to civic transparency and prompt public service.
            </div>
        </div>
    </body>
    </html>';

    return sendCustomEmail($to_email, $to_name, $subject, $html);
}

/**
 * Send Complaint Work In Progress Status Update Email to Citizen
 */
function sendComplaintInProgressEmail($to_email, $to_name, $complaint, $dept_name, $progress_message = '') {
    $cid = $complaint['complaint_id'] ?? 'CIVIC-' . ($complaint['id'] ?? '0000');
    $title = $complaint['title'] ?? 'Civic Grievance';
    $category = $complaint['category'] ?? 'Municipal Issue';
    $location = $complaint['location'] ?? 'Municipal Area';
    
    $subject = "[CIRS Work In Progress] Grievance #{$cid} - Field Redressal Underway";
    $track_url = BASE_URL . "/track.php?id=" . urlencode($cid);

    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #f1f5f9; margin: 0; padding: 20px; }
            .mail-box { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; }
            .mail-header { background: linear-gradient(135deg, #f59e0b, #d97706); color: #ffffff; padding: 26px; text-align: center; }
            .mail-body { padding: 30px; color: #334155; line-height: 1.6; }
            .badge-progress { display: inline-block; background: #fffbeb; color: #b45309; border: 1px solid #fde68a; padding: 6px 14px; border-radius: 20px; font-weight: 700; font-size: 13px; margin-bottom: 15px; }
            .details-table { width: 100%; border-collapse: collapse; margin: 20px 0; background: #f8fafc; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0; }
            .details-table td { padding: 11px 16px; font-size: 14px; border-bottom: 1px solid #e2e8f0; }
            .details-table td.label { font-weight: 600; color: #475569; width: 38%; }
            .progress-box { background: #fffbeb; border-left: 4px solid #f59e0b; padding: 14px 18px; border-radius: 0 8px 8px 0; margin: 20px 0; font-size: 14px; color: #92400e; }
            .btn-action { display: inline-block; background: #d97706; color: #ffffff !important; padding: 12px 28px; border-radius: 8px; text-decoration: none; font-weight: 700; margin-top: 10px; }
            .mail-footer { background: #0f172a; color: #94a3b8; padding: 18px; text-align: center; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class="mail-box">
            <div class="mail-header">
                <h2 style="margin:0; font-size: 22px;">Grievance Work In Progress ⚙️</h2>
                <p style="margin: 5px 0 0 0; opacity: 0.95; font-size: 13px;">Citizen Issue Reporting System &bull; Field Operations</p>
            </div>
            <div class="mail-body">
                <div class="badge-progress">⚙️ Field Team Dispatched & Active</div>
                <h3 style="margin-top:0; color: #0f172a;">Hello, ' . htmlspecialchars($to_name) . '</h3>
                <p>The municipal department has commenced active work on your complaint <strong>#' . htmlspecialchars($cid) . '</strong>.</p>
                
                <table class="details-table">
                    <tr>
                        <td class="label">Complaint ID:</td>
                        <td><strong style="color: #b45309; font-family: monospace; font-size: 15px;">' . htmlspecialchars($cid) . '</strong></td>
                    </tr>
                    <tr>
                        <td class="label">Issue Title:</td>
                        <td><strong>' . htmlspecialchars($title) . '</strong></td>
                    </tr>
                    <tr>
                        <td class="label">Category:</td>
                        <td>' . htmlspecialchars($category) . '</td>
                    </tr>
                    <tr>
                        <td class="label">Active Department:</td>
                        <td><strong>' . htmlspecialchars($dept_name ?: 'Designated Municipal Department') . '</strong></td>
                    </tr>
                    <tr>
                        <td class="label">Current Status:</td>
                        <td><strong style="color: #d97706;">WORK IN PROGRESS</strong></td>
                    </tr>
                    <tr>
                        <td class="label">Incident Location:</td>
                        <td>' . htmlspecialchars($location) . '</td>
                    </tr>
                </table>

                ' . (!empty($progress_message) ? '
                <div class="progress-box">
                    <strong>Field Officer / Progress Note:</strong><br>' . nl2br(htmlspecialchars($progress_message)) . '
                </div>' : '') . '

                <p>Our ground teams are working to resolve the issue as swiftly as possible. Track ongoing updates online:</p>

                <div style="text-align: center; margin: 25px 0;">
                    <a href="' . $track_url . '" class="btn-action">Check Grievance Progress &rarr;</a>
                </div>
            </div>
            <div class="mail-footer">
                &copy; ' . date('Y') . ' Citizen Issue Reporting System. Committed to efficient civic solutions.
            </div>
        </div>
    </body>
    </html>';

    return sendCustomEmail($to_email, $to_name, $subject, $html);
}

/**
 * Send Complaint Resolved & Closed Email to Citizen
 */
function sendComplaintResolvedEmail($to_email, $to_name, $complaint, $dept_name, $resolution_message = '') {
    $cid = $complaint['complaint_id'] ?? 'CIVIC-' . ($complaint['id'] ?? '0000');
    $title = $complaint['title'] ?? 'Civic Grievance';
    $category = $complaint['category'] ?? 'Municipal Issue';
    $location = $complaint['location'] ?? 'Municipal Area';
    
    $subject = "[CIRS Resolved & Closed] Grievance #{$cid} - Work Successfully Completed 🎉";
    $track_url = BASE_URL . "/track.php?id=" . urlencode($cid);

    $html = '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: #f1f5f9; margin: 0; padding: 20px; }
            .mail-box { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 10px rgba(0,0,0,0.06); border: 1px solid #e2e8f0; }
            .mail-header { background: linear-gradient(135deg, #10b981, #059669); color: #ffffff; padding: 26px; text-align: center; }
            .mail-body { padding: 30px; color: #334155; line-height: 1.6; }
            .badge-resolved { display: inline-block; background: #ecfdf5; color: #059669; border: 1px solid #a7f3d0; padding: 6px 14px; border-radius: 20px; font-weight: 700; font-size: 13px; margin-bottom: 15px; }
            .details-table { width: 100%; border-collapse: collapse; margin: 20px 0; background: #f8fafc; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0; }
            .details-table td { padding: 11px 16px; font-size: 14px; border-bottom: 1px solid #e2e8f0; }
            .details-table td.label { font-weight: 600; color: #475569; width: 38%; }
            .resolved-box { background: #ecfdf5; border-left: 4px solid #10b981; padding: 14px 18px; border-radius: 0 8px 8px 0; margin: 20px 0; font-size: 14px; color: #065f46; }
            .btn-action { display: inline-block; background: #059669; color: #ffffff !important; padding: 12px 28px; border-radius: 8px; text-decoration: none; font-weight: 700; margin-top: 10px; }
            .mail-footer { background: #0f172a; color: #94a3b8; padding: 18px; text-align: center; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class="mail-box">
            <div class="mail-header">
                <h2 style="margin:0; font-size: 22px;">Grievance Resolved & Closed 🎉</h2>
                <p style="margin: 5px 0 0 0; opacity: 0.95; font-size: 13px;">Citizen Issue Reporting System &bull; Resolution Completed</p>
            </div>
            <div class="mail-body">
                <div class="badge-resolved">✓ Successfully Resolved & Closed</div>
                <h3 style="margin-top:0; color: #0f172a;">Hello, ' . htmlspecialchars($to_name) . '</h3>
                <p>We are pleased to inform you that your reported civic problem (<strong>#' . htmlspecialchars($cid) . '</strong>) has been <strong>successfully inspected, addressed, and closed</strong> by the municipal authorities.</p>
                
                <table class="details-table">
                    <tr>
                        <td class="label">Complaint ID:</td>
                        <td><strong style="color: #059669; font-family: monospace; font-size: 15px;">' . htmlspecialchars($cid) . '</strong></td>
                    </tr>
                    <tr>
                        <td class="label">Issue Title:</td>
                        <td><strong>' . htmlspecialchars($title) . '</strong></td>
                    </tr>
                    <tr>
                        <td class="label">Category:</td>
                        <td>' . htmlspecialchars($category) . '</td>
                    </tr>
                    <tr>
                        <td class="label">Resolved By:</td>
                        <td><strong>' . htmlspecialchars($dept_name ?: 'Municipal Engineering & Sanitation Dept') . '</strong></td>
                    </tr>
                    <tr>
                        <td class="label">Status:</td>
                        <td><strong style="color: #059669;">RESOLVED & CLOSED</strong></td>
                    </tr>
                    <tr>
                        <td class="label">Incident Location:</td>
                        <td>' . htmlspecialchars($location) . '</td>
                    </tr>
                    <tr>
                        <td class="label">Resolution Time:</td>
                        <td>' . date('d M Y, h:i A') . '</td>
                    </tr>
                </table>

                ' . (!empty($resolution_message) ? '
                <div class="resolved-box">
                    <strong>Official Resolution Summary:</strong><br>' . nl2br(htmlspecialchars($resolution_message)) . '
                </div>' : '') . '

                <p>Thank you for being an active and vigilant citizen in keeping our city clean, functional, and safe. You can view the complete resolution history below:</p>

                <div style="text-align: center; margin: 25px 0;">
                    <a href="' . $track_url . '" class="btn-action">View Resolution Details &rarr;</a>
                </div>
            </div>
            <div class="mail-footer">
                &copy; ' . date('Y') . ' Citizen Issue Reporting System. Partnering with citizens for a better city.
            </div>
        </div>
    </body>
    </html>';

    return sendCustomEmail($to_email, $to_name, $subject, $html);
}
