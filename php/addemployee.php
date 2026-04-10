<?php
session_start();
require_once __DIR__ . '/../auth/supabase.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated.']);
    exit;
}

$user        = $_SESSION['user'];
$companyName = $user['name'];
$cid         = $user['cid'];

$name       = htmlspecialchars(trim($_POST['name'] ?? ''));
$email      = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$contact    = htmlspecialchars(trim($_POST['phone'] ?? $_POST['contact'] ?? ''));
$department = htmlspecialchars(trim($_POST['department'] ?? ''));

if ($name === '' || $email === '' || $contact === '') {
    echo json_encode(['success' => false, 'message' => 'Name, email, and phone are required.']);
    exit;
}

try {
    $supabase = new SupabaseClient(true);
    $newID = null;
    $attempts = 0;
    do {
        $newID = preg_replace('/\s+/', '', $companyName) . rand(1000, 9999);
        $existing = $supabase->select('corporate_employees', ['id' => $newID], 'id', null, 1);
        if (empty($existing)) break;
        $attempts++;
    } while ($attempts < 10);

    if ($attempts >= 10) {
        echo json_encode(['success' => false, 'message' => 'Could not generate a unique employee ID.']);
        exit;
    }

    $supabase->insert('corporate_employees', [
        'id'         => $newID,
        'name'       => $name,
        'email'      => $email,
        'phone'      => $contact,
        'department' => $department,
        'cid'        => $cid,
        'company'    => $companyName,
    ]);

    echo json_encode(['success' => true, 'message' => 'Employee added successfully.']);
} catch (Throwable $e) {
    error_log('addemployee.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error adding employee.']);
}
