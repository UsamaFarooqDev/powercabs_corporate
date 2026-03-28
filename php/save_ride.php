<?php
session_start();
require_once __DIR__ . '/../auth/supabase.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$user = $_SESSION['user'];
$companyname = $user['name'];
$cid = $user['cid'];

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

    $employee_id    = $data['employee_id'];
    $employee       = $data['employee_name'];
    $pickup         = $data['pickup'];
    $destination    = $data['dropoff'];
    $carType        = $data['carType'];
    $pickupTime     = $data['pickupTime'];
    $paymentSource  = $data['paymentSource'];
    $fare           = floatval($data['fare']);
    $eta            = floatval($data['eta']);
    $distance       = floatval($data['distance']);

    // Other fields
    $status = 'Pending';
    $vehicle_number = '';
    $price = $fare;
    $date = date('Y-m-d H:i:s');

    try {
    $supabase = new SupabaseClient(true);
    $supabase->insert('corporate_rides', [
        'company' => $companyname,
        'employee' => $employee,
        'employee_id' => $employee_id,
        'pickup' => $pickup,
        'destination' => $destination,
        'payment_source' => $paymentSource,
        'pickupTime' => $pickupTime,
        'carType' => $carType,
        'price' => $price,
        'vehicle_number' => $vehicle_number,
        'status' => $status,
        'cid' => $cid,
        'fare' => $fare,
        'eta' => $eta,
        'distance' => $distance,
        'date' => $date
    ]);
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


    echo json_encode(['success' => true, 'message' => 'Ride booked successfully. Dispatcher notified via WhatsApp.']);
    } catch (Throwable $e) {
      echo json_encode(['success' => false, 'message' => 'Error saving ride.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
