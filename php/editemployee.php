<?php
session_start();
require_once __DIR__ . '/../auth/supabase.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = $_POST['employee_id'];
    $name        = htmlspecialchars(trim($_POST['name']));
    $email       = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $contact     = htmlspecialchars(trim($_POST['contact']));
    $department  = htmlspecialchars(trim($_POST['department']));

    if (empty($name) || empty($email) || empty($contact)) {
        $_SESSION['error'] = "Name, Email, and Contact are required.";
        header("Location: ".$_SERVER['HTTP_REFERER']);
        exit();
    }

    try {
        $supabase = new SupabaseClient(true);
        $supabase->update('corporate_employees', ['id' => $employee_id], [
            'name' => $name,
            'email' => $email,
            'phone' => $contact,
            'department' => $department
        ]);
        $_SESSION['success'] = "Employee updated successfully.";
    } catch (Throwable $e) {
        $_SESSION['error'] = "Error updating employee.";
    }

    header("Location: ".$_SERVER['HTTP_REFERER']);
    exit();
}
?>