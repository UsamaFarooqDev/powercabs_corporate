<?php
require_once __DIR__ . '/../auth/supabase.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employeeId = $_POST['employee'];
    $pickup = $_POST['pickup'];
    $dropoff = $_POST['dropoff'];
    $carType = $_POST['carType'];
    $pickupTime = $_POST['pickupTime'];
    $paymentSource = $_POST['paymentSource'];
    $companyName = $_POST['companyName'];
    $distance = $_POST['distance'];
    $duration = $_POST['duration'];
    $fare = $_POST['fare'];

    try {
    $supabase = new SupabaseClient(true);
    $supabase->insert('rides', [
        'employee_id' => $employeeId,
        'pickup_addr' => $pickup,
        'dest_addr' => $dropoff,
        'ride_type' => $carType,
        'payment_method' => strtolower((string)$paymentSource),
        'company' => $companyName,
        'distance_km' => $distance,
        'fare_eur' => $fare,
        'duration_min' => $duration,
        'enroute_at' => date('Y-m-d H:i:s', strtotime((string)$pickupTime)),
        'source' => 'corporate',
        'status' => 'Pending'
    ]);
        echo "Ride booked successfully.";
    } catch (Throwable $e) {
        echo "Error: Unable to book ride.";
    }
}
?>