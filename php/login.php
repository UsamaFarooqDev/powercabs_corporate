<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Start the session
session_start();

// Include the database connection file
if (!@include('connection.php')) {
    $_SESSION['error'] = "Failed to include the database connection file.";
    header("Location: ../index.php");
    exit;
}

// Ensure the request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request method.";
    header("Location: ../index.php");
    exit;
}

// Validate and sanitize input data
$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$password = $_POST['pass'] ?? null;

if (empty($email) || empty($password)) {
    $_SESSION['error'] = "Email and password are required.";
    header("Location: ../index.php");
    exit;
}

// Prepare and execute the SQL query
$sql = "SELECT id, email, pass, name, CID FROM corporate WHERE email = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    $_SESSION['error'] = "Database error: " . $conn->error;
    header("Location: ../index.php");
    exit;
}

$stmt->bind_param("s", $email);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    $_SESSION['error'] = "Invalid credentials.";
    $stmt->close();
    $conn->close();
    header("Location: ../index.php");
    exit;
}

// Bind result variables
$stmt->bind_result($id, $emailDb, $hashed_pass, $name, $cid);
$stmt->fetch();

// Verify the password
if (!password_verify($password, $hashed_pass)) {
    $_SESSION['error'] = "Invalid credentials.";
    $stmt->close();
    $conn->close();
    header("Location: ../index.php");
    exit;
}

// Store user data in session
$_SESSION['user'] = [
    "id" => $id,
    "email" => $emailDb,
    "name" => $name,
    "cid" => $cid,
];

// Close resources
$stmt->close();
$conn->close();

// Redirect to dashboard
header("Location: ../home.php");
exit;
?>
