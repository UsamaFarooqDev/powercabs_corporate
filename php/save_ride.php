<?php
session_start();
require_once __DIR__ . '/../auth/supabase.php';

header('Content-Type: application/json');

/**
 * Turn Supabase/Postgres API errors into short, user-facing messages (no raw JSON).
 */
function mapSupabaseErrorToUserMessage(Throwable $e): string
{
    $msg = $e->getMessage();
    $payload = null;
    if (preg_match('/Supabase error \(\d+\):\s*(\{.*\})\s*$/s', $msg, $m)) {
        $payload = json_decode($m[1], true);
    }

    if (is_array($payload)) {
        $code = isset($payload['code']) ? (string) $payload['code'] : '';
        $detail = isset($payload['details']) ? (string) $payload['details'] : '';
        $pgMsg = isset($payload['message']) ? (string) $payload['message'] : '';

        if ($code === '23505') {
            if (stripos($detail, 'corporate_rides_pkey') !== false
                || stripos($pgMsg, 'corporate_rides_pkey') !== false
                || preg_match('/Key \(id\)=\(/', $detail)) {
                return 'Your ride could not be booked: the booking ID sequence is out of sync with the database. Please try again once. If this keeps happening, your administrator needs to reset the auto-increment sequence for corporate rides.';
            }
            return 'Your ride could not be booked because it conflicts with an existing record. Please adjust your details and try again.';
        }
        if ($code === '23503') {
            return 'Your ride could not be booked because a linked record is missing or invalid. Refresh the page and try again.';
        }
        if ($code === '23514') {
            return 'Your ride could not be booked because some values failed validation. Check pickup time and other fields, then try again.';
        }
    }

    if (stripos($msg, '"23505"') !== false || stripos($msg, 'duplicate key') !== false) {
        return 'Your ride could not be booked because of a duplicate or conflict in the database. Please try again. If it continues, contact support.';
    }

    return 'We could not save your ride right now. Please try again in a moment. If the problem continues, contact support.';
}

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
        if (!array_key_exists($field, $data) || $data[$field] === '' || $data[$field] === null) {
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
    $pickupTimeIso = date('Y-m-d H:i:s', strtotime($pickupTime));

    try {
    $supabase = new SupabaseClient(true);
    $insertPayload = [
        'company' => $companyname,
        'employee' => $employee,
        'employee_id' => $employee_id,
        'pickup' => $pickup,
        'destination' => $destination,
        'payment_source' => $paymentSource,
        'pickupTime' => $pickupTimeIso,
        'carType' => $carType,
        'status' => $status,
        'cid' => $cid,
        'fare' => $fare,
        'eta' => $eta,
        'distance' => $distance
    ];
    $supabase->insert('corporate_rides', $insertPayload);
    // Compose WhatsApp message for Dispatcher
    $message = "New Ride Request\n\n"
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
      error_log('save_ride.php error: ' . $e->getMessage());
      echo json_encode([
        'success' => false,
        'message' => mapSupabaseErrorToUserMessage($e),
      ]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?>
