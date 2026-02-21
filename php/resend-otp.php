<?php
session_start();
@include 'connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    
    // Generate new OTP
    $otp = sprintf("%06d", mt_rand(1, 999999));
    $expiry = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    
    // Update OTP in database
    $update_query = "UPDATE password_resets SET otp = '$otp', expiry = '$expiry' WHERE email = '$email'";
    
    if (mysqli_query($conn, $update_query)) {
        // Here you would send the new OTP via email
        $_SESSION['otp'] = $otp; // For demonstration
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
} else {
    echo json_encode(['success' => false]);
}
?>