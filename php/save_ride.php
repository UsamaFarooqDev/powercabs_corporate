<?php
session_start();
require_once('connection.php'); // Make sure this defines $conn properly

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user = $_SESSION['user'];
$companyname = $conn->real_escape_string($user['name']);
$cid = intval($user['cid']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate required fields
    $requiredFields = ['employee_id', 'employee_name', 'pickup', 'dropoff', 'carType', 'pickupTime', 'paymentSource', 'distance', 'eta', 'fare'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            echo json_encode(['success' => false, 'message' => "Missing or empty field: $field"]);
            exit;
        }
    }

    // Sanitize values
    $employee_id    = $conn->real_escape_string($data['employee_id']);
    $employee       = $conn->real_escape_string($data['employee_name']);
    $pickup         = $conn->real_escape_string($data['pickup']);
    $destination    = $conn->real_escape_string($data['dropoff']);
    $carType        = $conn->real_escape_string($data['carType']);
    $pickupTime     = $conn->real_escape_string($data['pickupTime']);
    $paymentSource  = $conn->real_escape_string($data['paymentSource']);
    $fare           = floatval($data['fare']);
    $eta            = floatval($data['eta']);
    $distance       = floatval($data['distance']);

    // Other fields
    $status = 'Pending';
    $vehicle_number = '';
    $price = $fare;
    $date = date('Y-m-d H:i:s');

    // Insert query with prepared statement
    $stmt = $conn->prepare("INSERT INTO corporate_rides (
        company, employee, employee_id, pickup, destination, 
        payment_source, pickupTime, carType, price, vehicle_number, 
        status, cid, fare, eta, distance, date
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if ($stmt === false) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param(
        "sssssssssssiddds",
        $companyname, $employee, $employee_id, $pickup, $destination,
        $paymentSource, $pickupTime, $carType, $price, $vehicle_number,
        $status, $cid, $fare, $eta, $distance, $date
    );

    if ($stmt->execute()) {
    // Compose WhatsApp message for Dispatcher
    $message = "ðŸš• New Ride Request\n\n"
        . "Company: $companyname\n"
        . "Passenger: $employee (ID: $employee_id)\n"
        . "Pickup: $pickup\n"
        . "Drop-off: $destination\n"
        . "Pickup Time: $pickupTime\n"
        . "Car Type: $carType\n"
        . "Payment Source: $paymentSource\n"
        . "Estimated Fare: $fare €\n"
        . "Distance: $distance km\n"
        . "ETA: $eta minutes\n\n"
        . "Please arrange vehicle dispatch promptly.";

    // Telesign credentials
    $customer_id = '45A2501D-9CFF-49AE-AA4E-FB8368B30873';
    $api_key = '3Hm4qrSCPzLeX+32dM4fPQIrrGy2mWremcNzQi6K5wBbGoSGvqFqlHn6XlRgx8fp+BUPgY1xrA9ACI//qEPNhA==';

    // Dispatcher WhatsApp number (E.164 format without '+')
    $dispatcher_phone = '353899654467'; // Replace with actual dispatcher number

    // Encode credentials
    $encoded_auth = base64_encode("$customer_id:$api_key");

    // Prepare POST payload
    $post_data = [
        'phone_number' => $dispatcher_phone,
        'message' => $message,
        'message_type' => 'ARN' // Application to Person Notification
    ];

    // Initialize cURL
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, "https://rest-api.telesign.com/v1/messaging");
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "Authorization: Basic $encoded_auth",
        "Content-Type: application/x-www-form-urlencoded"
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));

    // Execute cURL
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        error_log('Telesign cURL error: ' . curl_error($ch));
    }

    curl_close($ch);

    echo json_encode(['success' => true, 'message' => 'Ride booked successfully. Dispatcher notified via WhatsApp.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
}


    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
