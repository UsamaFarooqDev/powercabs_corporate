<?php
session_start();
@include 'connection.php'; // Adjust path if needed

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get and sanitize input
    $employee_id = htmlspecialchars(trim($_POST['id'])); // Assuming it's a string

    if (empty($employee_id)) {
        $_SESSION['error'] = "Employee ID is required.";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }

    // Prepare DELETE query
    $stmt = $conn->prepare("DELETE FROM corporate_employees WHERE id = ?");
    if (!$stmt) {
        $_SESSION['error'] = "Database error: Unable to prepare delete statement.";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }

    // Bind parameter (assuming Employee_id is a string)
    $stmt->bind_param("s", $employee_id);

    // Execute the query
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $_SESSION['success'] = "Employee deleted successfully.";
        } else {
            $_SESSION['error'] = "No employee found with that ID.";
        }
    } else {
        $_SESSION['error'] = "Error deleting employee: " . $stmt->error;
    }

    // Close statement
    $stmt->close();
} else {
    $_SESSION['error'] = "Invalid request method.";
}

// Redirect back
header("Location: " . $_SERVER['HTTP_REFERER']);
exit();
?>