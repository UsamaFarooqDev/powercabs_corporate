<?php
session_start();
require_once __DIR__ . '/../auth/supabase.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cid = htmlspecialchars(trim($_POST['cid']));
    $name = htmlspecialchars(trim($_POST['name']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $phone = htmlspecialchars(trim($_POST['phone']));
    $address = htmlspecialchars(trim($_POST['address']));

    if (empty($cid) || empty($name) || empty($email) || empty($phone)) {
        $_SESSION['error'] = "CID, Name, Email, and Phone are required.";
    } else {
        try {
            $supabase = new SupabaseClient(true);
            $supabase->update('corporate', ['CID' => $cid], [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'address' => $address
            ]);
            if (!empty($_SESSION['user']) && $_SESSION['user']['cid'] === $cid) {
                $_SESSION['user']['name'] = $name;
                $_SESSION['user']['email'] = $email;
            }
            $_SESSION['success'] = "Profile updated successfully.";
        } catch (Throwable $e) {
            $_SESSION['error'] = "Error updating profile.";
        }
    }
}

header("Location: " . $_SERVER['HTTP_REFERER']);
exit();
?>