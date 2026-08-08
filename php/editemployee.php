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

$employee_id = htmlspecialchars(trim($_POST['employee_id'] ?? ''));
$name        = htmlspecialchars(trim($_POST['name'] ?? ''));
$email       = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$contact     = htmlspecialchars(trim($_POST['phone'] ?? $_POST['contact'] ?? ''));
$department  = htmlspecialchars(trim($_POST['department'] ?? ''));
if ($employee_id === '' || $name === '' || $email === '') {
    echo json_encode(['success' => false, 'message' => 'Name and email are required.']);
    exit;
}

$cid = $_SESSION['user']['cid'] ?? '';

try {
    $supabase = new SupabaseClient(true);
    // Confirm this employee belongs to the logged-in company before updating.
    $existing = $supabase->select('corporate_employees', ['corp_id' => $employee_id, 'cid' => $cid], 'corp_id', null, 1);
    if (empty($existing)) {
        echo json_encode(['success' => false, 'message' => 'Employee not found.']);
        exit;
    }
    $supabase->update('corporate_employees', ['corp_id' => $employee_id], [
        'name'       => $name,
        'email'      => $email,
        'phone'      => $contact,
        'department' => $department,
    ]);
    echo json_encode(['success' => true, 'message' => 'Employee updated successfully.']);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Error updating employee.']);
}
