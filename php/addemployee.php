<?php
session_start();
require_once __DIR__ . '/../auth/supabase.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $companyName = htmlspecialchars(trim($_POST['companyname']));
    $cid         = htmlspecialchars(trim($_POST['cid']));
    $name        = htmlspecialchars(trim($_POST['name']));
    $email       = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $contact     = htmlspecialchars(trim($_POST['contact']));
    $department  = htmlspecialchars(trim($_POST['department'] ?? ''));

    if (empty($name) || empty($email) || empty($contact)) {
        $_SESSION['error'] = "Name, Email, and Contact are required.";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }

    $newID = null;
    $attempts = 0;
    try {
        $supabase = new SupabaseClient(true);
        do {
            $randomDigits = rand(1000, 9999);
            $newID = preg_replace('/\s+/', '', $companyName) . $randomDigits;
            $existing = $supabase->select('corporate_employees', ['id' => $newID], 'id', null, 1);
            if (empty($existing)) {
                break;
            }
            $attempts++;
        } while ($attempts < 10);

        if ($attempts >= 10) {
            $_SESSION['error'] = "Could not generate a unique ID for this employee.";
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit();
        }

        $supabase->insert('corporate_employees', [
            'id' => $newID,
            'name' => $name,
            'email' => $email,
            'phone' => $contact,
            'department' => $department,
            'cid' => $cid,
            'company' => $companyName
        ]);
        $_SESSION['success'] = "New employee added successfully with ID: $newID";
    } catch (Throwable $e) {
        $_SESSION['error'] = "Error adding employee.";
    }
}

header("Location: " . $_SERVER['HTTP_REFERER']);
exit();
?>