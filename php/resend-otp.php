<?php
session_start();
require_once __DIR__ . '/../auth/supabase.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    
    // Generate new OTP
    $otp = sprintf("%06d", mt_rand(1, 999999));
    $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    try {
        $supabase = new SupabaseClient(true);
        $existing = $supabase->select('password_resets', ['email' => $email], 'email', null, 1);
        if (empty($existing)) {
            $supabase->insert('password_resets', ['email' => $email, 'otp' => $otp, 'expiry' => $expiry]);
        } else {
            $supabase->update('password_resets', ['email' => $email], ['otp' => $otp, 'expiry' => $expiry]);
        }
        $_SESSION['otp'] = $otp; // For demonstration
        echo json_encode(['success' => true]);
    } catch (Throwable $e) {
        echo json_encode(['success' => false]);
    }
} else {
    echo json_encode(['success' => false]);
}
?>