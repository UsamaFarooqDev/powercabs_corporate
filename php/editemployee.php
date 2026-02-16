<?php
session_start(); // Start session at the top

@include('connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $employee_id = $_POST['employee_id']; // Make sure to pass Employee_id from form
    $name        = htmlspecialchars(trim($_POST['name']));
    $email       = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $contact     = htmlspecialchars(trim($_POST['contact']));
    $department  = htmlspecialchars(trim($_POST['department']));

    // Validate required fields
    if (empty($name) || empty($email) || empty($contact)) {
        $_SESSION['error'] = "Name, Email, and Contact are required.";
        header("Location: ".$_SERVER['HTTP_REFERER']);
        exit();
    }

    // Prepare SQL query
    $stmt = $conn->prepare("
        UPDATE corporate_employees 
        SET name = ?, email = ?, phone = ?, department = ? 
        WHERE id = ?
    ");

    if ($stmt === false) {
        $_SESSION['error'] = "MySQL prepare error: " . $conn->error;
        header("Location: ".$_SERVER['HTTP_REFERER']);
        exit();
    }

    // Bind parameters
    $stmt->bind_param("sssss", $name, $email, $contact, $department, $employee_id);

    // Execute the query
    if ($stmt->execute()) {
        $_SESSION['success'] = "Employee updated successfully.";
    } else {
        $_SESSION['error'] = "Error updating employee: " . $stmt->error;
    }

    // Close the statement
    $stmt->close();

    // Redirect back to the referring page
    header("Location: ".$_SERVER['HTTP_REFERER']);
    exit();
}
?>