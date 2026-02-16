<?php
session_start();
@include 'connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve form data
    $companyName = htmlspecialchars(trim($_POST['companyname']));
    $cid         = htmlspecialchars(trim($_POST['cid'])); // Now treated as string
    $name        = htmlspecialchars(trim($_POST['name']));
    $email       = filter_var(trim($_POST['email']), FILTER_SANITIZE_EMAIL);
    $contact     = htmlspecialchars(trim($_POST['contact']));
    $department  = htmlspecialchars(trim($_POST['department'] ?? ''));

    // Validate required inputs
    if (empty($name) || empty($email) || empty($contact)) {
        $_SESSION['error'] = "Name, Email, and Contact are required.";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }

    // Generate company short name (first 3 letters, uppercase)
    // $shortName = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $companyName), 0, 3));

    // Generate unique ID
    $newID = null;
    $attempts = 0;

    do {
        $randomDigits = rand(1000, 9999);
        $newID = $companyName . $randomDigits;

        $checkStmt = $conn->prepare("SELECT id FROM corporate_employees WHERE id = ?");
        $checkStmt->bind_param("s", $newID);
        $checkStmt->execute();
        $checkStmt->store_result();

        if ($checkStmt->num_rows === 0) {
            break; // Unique ID found
        }

        $attempts++;
    } while ($attempts < 10);

    if ($attempts >= 10) {
        $_SESSION['error'] = "Could not generate a unique ID for this employee.";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }

    // Prepare insert statement
    $stmt = $conn->prepare("
        INSERT INTO corporate_employees (id, name, email, phone, department, cid, company)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        $_SESSION['error'] = "Database error: Unable to prepare SQL statement.";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }

    // Bind parameters (all are strings now except maybe phone)
    $stmt->bind_param(
        "sssssss",
        $newID,
        $name,
        $email,
        $contact,
        $department,
        $cid,
        $companyName
    );

    // Execute query
    if ($stmt->execute()) {
        $_SESSION['success'] = "New employee added successfully with ID: $newID";
    } else {
        $_SESSION['error'] = "Error adding employee: " . $stmt->error;
    }

    // Close statements
    $stmt->close();
}

// Redirect back
header("Location: " . $_SERVER['HTTP_REFERER']);
exit();
?>