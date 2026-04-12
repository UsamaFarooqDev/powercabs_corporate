<?php
require_once __DIR__ . '/../auth/supabase.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../login.php');
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['pass'] ?? ($_POST['password'] ?? '');

if ($email === '' || $password === '') {
    $_SESSION['error'] = 'Email and password are required.';
    header('Location: ../login.php');
    exit;
}

try {
    $supabase = new SupabaseClient(true);
    $rows = $supabase->select('corporate', ['email' => $email], '*', null, 1);
    if (empty($rows) || !password_verify($password, $rows[0]['pass'] ?? '')) {
        $_SESSION['error'] = 'Invalid credentials.';
        header('Location: ../login.php');
        exit;
    }
    $cid = $rows[0]['CID'] ?? ($rows[0]['cid'] ?? ($rows[0]['company_id'] ?? ''));
    if ($cid === '') {
        $_SESSION['error'] = 'Account is missing corporate company ID.';
        header('Location: ../login.php');
        exit;
    }
    $_SESSION['user'] = [
        'id' => $rows[0]['id'] ?? null,
        'email' => $rows[0]['email'] ?? $email,
        'name' => $rows[0]['name'] ?? '',
        'cid' => $cid
    ];
    header('Location: ../home.php');
    exit;
} catch (Throwable $e) {
    $_SESSION['error'] = 'Login failed.';
    header('Location: ../login.php');
    exit;
}
?>
