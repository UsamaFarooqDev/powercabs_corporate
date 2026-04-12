<?php
session_start();
require_once __DIR__ . '/../auth/supabase.php';
require_once __DIR__ . '/password_reset_otp.inc.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../forgot-password.php');
    exit();
}

$email = trim($_POST['email'] ?? '');

try {
    $supabase = new SupabaseClient(true);
    $result = pr_issue_password_reset_otp($supabase, $email);

    if (!$result['ok']) {
        if (($result['reason'] ?? '') === 'not_found') {
                $_SESSION['error'] = 'Email address not found in our records.';
            header('Location: ../forgot-password.php');
            exit();
        }
        $_SESSION['error'] = 'Could not resend code. Please try again.';
        header('Location: ../forgot-password.php?step=otp&email=' . urlencode($email));
        exit();
    }

    $_SESSION['otp'] = $result['otp'];
    $_SESSION['reset_email'] = $email;
    $_SESSION['success'] = 'A new verification code has been sent to your email.';

    header('Location: ../forgot-password.php?step=otp&email=' . urlencode($email) . '&rc=1');
    exit();
} catch (Throwable $e) {
    $_SESSION['error'] = 'Could not resend code. Please try again.';
    header('Location: ../forgot-password.php?step=otp&email=' . urlencode($email));
    exit();
}
