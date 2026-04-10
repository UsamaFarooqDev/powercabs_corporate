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

$employee_id = htmlspecialchars(trim($_POST['id'] ?? ''));

if ($employee_id === '') {
    echo json_encode(['success' => false, 'message' => 'Employee ID is required.']);
    exit;
}

try {
    $supabase = new SupabaseClient(true);
    $supabase->delete('corporate_employees', ['id' => $employee_id]);
    echo json_encode(['success' => true, 'message' => 'Employee removed successfully.']);
} catch (Throwable $e) {
    error_log('deleteemployee.php: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error removing employee.']);
}
