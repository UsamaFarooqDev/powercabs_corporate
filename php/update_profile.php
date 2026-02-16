<?php
session_start();
@include 'connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cid = htmlspecialchars(trim($_POST['cid']));
    $name = htmlspecialchars(trim($_POST['name']));
    $email = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $phone = htmlspecialchars(trim($_POST['phone']));
    $address = htmlspecialchars(trim($_POST['address']));

    if (empty($cid) || empty($name) || empty($email) || empty($phone)) {
        $_SESSION['error'] = "CID, Name, Email, and Phone are required.";
    } else {
        $stmt = $conn->prepare("UPDATE corporate SET name = ?, email = ?, phone = ?, address = ? WHERE CID = ?");
        if ($stmt === false) {
            $_SESSION['error'] = "Database error.";
        } else {
            $stmt->bind_param("sssss", $name, $email, $phone, $address, $cid);
            if ($stmt->execute()) {
                $_SESSION['success'] = "Profile updated successfully.";
            } else {
                $_SESSION['error'] = "Error updating profile.";
            }
            $stmt->close();
        }
    }
}

header("Location: " . $_SERVER['HTTP_REFERER']);
exit();
?>