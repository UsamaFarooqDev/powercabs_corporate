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

    // --- DEBUG START (remove after diagnosing) ---
    error_log('=== LOGIN DEBUG ===');
    error_log('Email entered: ' . $email);
    error_log('Password entered length: ' . strlen($password));
    error_log('Rows returned: ' . count($rows));
    if (!empty($rows)) {
        $dbCols = array_keys($rows[0]);
        error_log('DB columns: ' . implode(', ', $dbCols));
        error_log('pass column exists: ' . (isset($rows[0]['pass']) ? 'YES' : 'NO'));
        error_log('password column exists: ' . (isset($rows[0]['password']) ? 'YES' : 'NO'));
        $dbHash = $rows[0]['pass'] ?? ($rows[0]['password'] ?? 'MISSING');
        error_log('Stored hash (first 20 chars): ' . substr($dbHash, 0, 20));
        error_log('Stored hash length: ' . strlen($dbHash));
        error_log('password_verify result: ' . (password_verify($password, $dbHash) ? 'TRUE' : 'FALSE'));
    }
    error_log('=== END DEBUG ===');
    // --- DEBUG END ---

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
    error_log('Corporate auth/login.php error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Login failed. Please try again.']);
}

