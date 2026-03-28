<?php
session_start();
require_once __DIR__ . '/../auth/supabase.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cid = htmlspecialchars(trim($_POST['cid']));
    $oldPassword = trim($_POST['old_password']);
    $newPassword = trim($_POST['new_password']);
    $confirmPassword = trim($_POST['confirm_password']);

    if (empty($cid) || empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }

    if ($newPassword !== $confirmPassword) {
        $_SESSION['error'] = "New passwords do not match.";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }

    try {
        $supabase = new SupabaseClient(true);
        $rows = $supabase->select('corporate', ['CID' => $cid], 'pass', null, 1);
        $dbPassword = $rows[0]['pass'] ?? '';
        if (!$dbPassword || !password_verify($oldPassword, $dbPassword)) {
            $_SESSION['error'] = "Old password is incorrect.";
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit();
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $supabase->update('corporate', ['CID' => $cid], ['pass' => $hashedPassword]);
        $_SESSION['success'] = "Password updated successfully.";
    } catch (Throwable $e) {
        $_SESSION['error'] = "Failed to update password.";
    }
}

header("Location: " . $_SERVER['HTTP_REFERER']);
exit();
?>