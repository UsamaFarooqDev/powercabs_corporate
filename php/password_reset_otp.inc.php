<?php
/**
 * Shared forgot-password OTP logic (included by send-otp.php and resend-otp.php only).
 */
require_once __DIR__ . '/send-email.php';

/**
 * Greeting name for emails: contact person, then account name, then email local-part.
 *
 * @param array<string, mixed> $row
 */
function pr_corporate_greeting_name(array $row, $email) {
    foreach (['appointed_person', 'name'] as $key) {
        $v = trim((string) ($row[$key] ?? ''));
        if ($v !== '') {
            return $v;
        }
    }
    $email = trim((string) $email);
    $at = strpos($email, '@');
    if ($at !== false && $at > 0) {
        return substr($email, 0, $at);
    }
    return 'User';
}

/**
 * Remove expired OTP rows (best-effort; ignores failures).
 */
function pr_cleanup_expired_password_resets(SupabaseClient $supabase) {
    $cutoff = gmdate('Y-m-d\TH:i:s\Z');
    try {
        $supabase->deleteWithOperators('password_resets', ['expiry' => 'lt.' . $cutoff]);
    } catch (Throwable $e) {
        // ignore
    }
}

/**
 * Issue a new OTP for a corporate email: validate account, cleanup, store row, send mail.
 *
 * @return array{ok:bool, otp?:string, reason?:string}
 */
function pr_issue_password_reset_otp(SupabaseClient $supabase, $email) {
    $email = trim((string) $email);
    if ($email === '') {
        return ['ok' => false, 'reason' => 'empty_email'];
    }

    $users = $supabase->select('corporate', ['email' => $email], '*', null, 1);
    if (empty($users)) {
        return ['ok' => false, 'reason' => 'not_found'];
    }

    $greetingName = pr_corporate_greeting_name($users[0], $email);

    pr_cleanup_expired_password_resets($supabase);

    $otp = sprintf('%06d', mt_rand(1, 999999));
    $expiry = gmdate('c', strtotime('+10 minutes'));

    $supabase->delete('password_resets', ['email' => $email]);
    $supabase->insert('password_resets', [
        'email' => $email,
        'otp' => $otp,
        'expiry' => $expiry,
    ]);

    $send = sendOTPEmail($email, $otp, $greetingName);
    if (empty($send['success'])) {
        $line = '[' . date('Y-m-d H:i:s') . "] OTP email not delivered to {$email}: " . ($send['message'] ?? '') . "\n";
        @file_put_contents(__DIR__ . '/../otp_debug.log', $line, FILE_APPEND);
    }

    return ['ok' => true, 'otp' => $otp];
}
