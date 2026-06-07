<?php

/**
 * MediAlert outbound notifications (email + SMS) for emergency triage.
 */

function cs_medi_alert_config() {
    return [
        'email_from'   => 'medi-alert@sahealth.local',
        'email_from_name' => 'SA Health MediAlert',
        'sms_mode'     => 'log',
        'sms_log_path' => __DIR__ . '/logs/medi_alert_sms.log',
    ];
}

function cs_medi_alert_ensure_log_dir() {
    $dir = __DIR__ . '/logs';
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    return is_dir($dir) && is_writable($dir);
}

function cs_send_medi_alert_email($to, $subject, $body) {
    $to = trim((string)$to);
    if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Invalid email address'];
    }

    $cfg = cs_medi_alert_config();
    $from = $cfg['email_from'];
    $fromName = $cfg['email_from_name'];
    $headers = "From: $fromName <$from>\r\n";
    $headers .= "Reply-To: $from\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "X-Mailer: SA-Health-MediAlert\r\n";

    $sent = @mail($to, $subject, $body, $headers);
    if ($sent) {
        return ['ok' => true, 'channel' => 'email'];
    }

    if (cs_medi_alert_ensure_log_dir()) {
        $log = __DIR__ . '/logs/medi_alert_email.log';
        $entry = date('c') . " TO:$to SUBJECT:$subject BODY:" . str_replace("\n", ' ', $body) . "\n";
        @file_put_contents($log, $entry, FILE_APPEND);
        return ['ok' => true, 'channel' => 'email', 'mode' => 'logged'];
    }

    return ['ok' => false, 'error' => 'Email could not be sent'];
}

function cs_send_medi_alert_sms($phone, $message) {
    $phone = preg_replace('/[^\d+]/', '', trim((string)$phone));
    if ($phone === '' || strlen($phone) < 9) {
        return ['ok' => false, 'error' => 'Invalid phone number'];
    }

    $cfg = cs_medi_alert_config();
    if ($cfg['sms_mode'] === 'log' || !cs_medi_alert_ensure_log_dir()) {
        $log = $cfg['sms_log_path'];
        if (!cs_medi_alert_ensure_log_dir()) {
            return ['ok' => false, 'error' => 'SMS log directory unavailable'];
        }
        $entry = date('c') . " TO:$phone MSG:" . str_replace("\n", ' ', $message) . "\n";
        @file_put_contents($log, $entry, FILE_APPEND);
        return ['ok' => true, 'channel' => 'sms', 'mode' => 'logged'];
    }

    return ['ok' => false, 'error' => 'SMS API not configured'];
}

function cs_build_medi_alert_message(array $alert, $facility, $patientName = '') {
    $type = $alert['emergency_type'] ?? 'Emergency';
    $source = $alert['source_role'] ?? 'Staff';
    $poster = $alert['poster_name'] ?? 'Staff';
    $body = trim($alert['message'] ?? '');
    $time = $alert['created_at'] ?? date('Y-m-d H:i');

    $lines = [
        'MEDIALERT — URGENT',
        'Facility: ' . $facility,
        'Time: ' . $time,
        'Type: ' . $type,
        'Reported by: ' . $poster . ' (' . $source . ')',
    ];
    if ($patientName !== '') {
        $lines[] = 'Patient: ' . $patientName;
    }
    $lines[] = '';
    $lines[] = $body;
    $lines[] = '';
    $lines[] = 'Please respond immediately. Contact reception for details.';

    return implode("\n", $lines);
}
