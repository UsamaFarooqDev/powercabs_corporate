<?php
session_start();
require_once __DIR__ . '/../auth/supabase.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $employee_id = htmlspecialchars(trim($_POST['id']));

    if (empty($employee_id)) {
        $_SESSION['error'] = "Employee ID is required.";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }

    try {
        $supabase = new SupabaseClient(true);
        $supabase->delete('corporate_employees', ['id' => $employee_id]);
        $_SESSION['success'] = "Employee deleted successfully.";
    } catch (Throwable $e) {
        $_SESSION['error'] = "Error deleting employee.";
    }
} else {
    $_SESSION['error'] = "Invalid request method.";
}

header("Location: " . $_SERVER['HTTP_REFERER']);
exit();
?>