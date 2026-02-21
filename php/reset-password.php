<?php
session_start();
@include 'connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Check if reset was verified
    if (!isset($_SESSION['reset_verified']) || $_SESSION['reset_verified'] !== true) {
        $_SESSION['error'] = "Please verify your OTP first.";
        header("Location: ../forgot-password.php");
        exit();
    }
    
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate password
    if ($new_password !== $confirm_password) {
        $_SESSION['error'] = "Passwords do not match.";
        header("Location: ../forgot-password.php?step=reset&email=" . urlencode($email));
        exit();
    }
    
    // Hash the new password
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    // Update password in database
    $update_query = "UPDATE corporate SET password = '$hashed_password' WHERE email = '$email'";
    
    if (mysqli_query($conn, $update_query)) {
        // Delete used OTP
        $delete_query = "DELETE FROM password_resets WHERE email = '$email'";
        mysqli_query($conn, $delete_query);
        
        // Clear session variables
        unset($_SESSION['reset_verified']);
        unset($_SESSION['reset_email']);
        
        $_SESSION['success'] = "Password reset successful! You can now login with your new password.";
        header("Location: ../index.php");
        exit();
    } else {
        $_SESSION['error'] = "Failed to reset password. Please try again.";
        header("Location: ../forgot-password.php?step=reset&email=" . urlencode($email));
        exit();
    }
} else {
    header("Location: ../forgot-password.php");
    exit();
}
?>