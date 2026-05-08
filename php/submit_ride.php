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
        'pickup' => $pickup,
        'destination' => $dropoff,
        'pickup_addr' => $pickup,
        'dest_addr' => $dropoff,
        'carType' => $carType,
        'ride_type' => $carType,
        'pickupTime' => $pickupTime,
        'payment_source' => $paymentSource,
        'payment_method' => strtolower((string)$paymentSource),
        'company' => $companyName,
        'distance' => $distance,
        'distance_km' => $distance,
        'eta' => $duration,
        'fare' => $fare,
        'fare_eur' => $fare,
        'source' => 'corporate',
        'status' => 'Pending'
    ]);
        echo "Ride booked successfully.";
    } catch (Throwable $e) {
        echo "Error: Unable to book ride.";
    }
}
?>