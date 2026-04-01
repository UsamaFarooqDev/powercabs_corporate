<?php
session_start();
require_once __DIR__ . '/../auth/supabase.php';

$redirect = '../profile.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redirect);
    exit;
}

$user = $_SESSION['user'] ?? null;
$cid = is_array($user) ? trim((string)($user['cid'] ?? '')) : '';
if ($cid === '') {
    $_SESSION['error'] = 'You must be logged in to change your password.';
    header('Location: ../login.php');
    exit;
}

$oldPassword = trim($_POST['old_password'] ?? '');
$newPassword = trim($_POST['new_password'] ?? '');
$confirmPassword = trim($_POST['confirm_password'] ?? '');

if ($oldPassword === '' || $newPassword === '' || $confirmPassword === '') {
    $_SESSION['error'] = 'All fields are required.';
    header('Location: ' . $redirect);
    exit;
}

if ($newPassword !== $confirmPassword) {
    $_SESSION['error'] = 'New passwords do not match.';
    header('Location: ' . $redirect);
    exit;
}

if (strlen($newPassword) < 8) {
    $_SESSION['error'] = 'New password must be at least 8 characters.';
    header('Location: ' . $redirect);
    exit;
}

try {
    $supabase = new SupabaseClient(true);
    $dbPassword = '';
    $rowFilter = null;
    foreach (corporate_row_filters_try($user) as $filter) {
        $rows = $supabase->select('corporate', $filter, 'pass', null, 1);
        if (!empty($rows)) {
            $dbPassword = $rows[0]['pass'] ?? '';
            $rowFilter = $filter;
            break;
        }
    }
    if ($rowFilter === null || !$dbPassword || !password_verify($oldPassword, $dbPassword)) {
        $_SESSION['error'] = 'Current password is incorrect.';
        header('Location: ' . $redirect);
        exit;
    }

    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    $supabase->update('corporate', $rowFilter, ['pass' => $hashedPassword]);
    $_SESSION['success'] = 'Password updated successfully.';
} catch (Throwable $e) {
    error_log('change_password.php: ' . $e->getMessage());
    $_SESSION['error'] = 'Failed to update password.';
}

header('Location: ' . $redirect);
exit;