<?php
session_start();
@include 'connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $cid = htmlspecialchars(trim($_POST['cid']));
    $oldPassword = trim($_POST['old_password']);
    $newPassword = trim($_POST['new_password']);
    $confirmPassword = trim($_POST['confirm_password']);

    if (empty($cid) || empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }

    if ($newPassword !== $confirmPassword) {
        $_SESSION['error'] = "New passwords do not match.";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }

    // Fetch current user
    $stmt = $conn->prepare("SELECT pass FROM corporate WHERE CID = ?");
    $stmt->bind_param("s", $cid);
    $stmt->execute();
    $stmt->bind_result($dbPassword);
    $stmt->fetch();
    $stmt->close();

    if (!$dbPassword || !password_verify($oldPassword, $dbPassword)) {
        $_SESSION['error'] = "Old password is incorrect.";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }

    // Hash new password
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

    // Update password
    $updateStmt = $conn->prepare("UPDATE corporate SET pass = ? WHERE CID = ?");
    $updateStmt->bind_param("ss", $hashedPassword, $cid);

    if ($updateStmt->execute()) {
        $_SESSION['success'] = "Password updated successfully.";
    } else {
        $_SESSION['error'] = "Failed to update password.";
    }

    $updateStmt->close();
}

header("Location: " . $_SERVER['HTTP_REFERER']);
exit();
?>