<?php
session_start();
require_once __DIR__ . '/supabase.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => 'Please enter email and password.']);
    exit;
}

try {
    $supabase = new SupabaseClient(true);
    // Use '*' to avoid schema-case issues (CID vs cid).
    $rows = $supabase->select('corporate', ['email' => $email], '*', null, 1);

    if (empty($rows)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
        exit;
    }

    $corp = $rows[0];
    $hash = $corp['pass'] ?? '';
    if (!$hash || !password_verify($password, $hash)) {
        echo json_encode(['success' => false, 'message' => 'Invalid email or password.']);
        exit;
    }

    session_regenerate_id(true);
    $_SESSION['user'] = [
        'id' => $corp['id'] ?? null,
        'email' => $corp['email'] ?? $email,
        'name' => $corp['name'] ?? '',
        'cid' => $corp['CID'] ?? ($corp['cid'] ?? ($corp['company_id'] ?? ''))
    ];
    if ($_SESSION['user']['cid'] === '') {
        echo json_encode(['success' => false, 'message' => 'Account is missing corporate company ID.']);
        exit;
    }
    echo json_encode(['success' => true, 'message' => 'Login successful.']);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Login failed. Please try again.']);
}

