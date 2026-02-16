<?php
@include('connection.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get POST data
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

    // Prepare and bind
    $stmt = $conn->prepare("INSERT INTO corporate_rides (employee_id, pickup, dropoff, car_type, pickup_time, payment_source, company_name, distance, duration, fare)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param("ssssssssss", 
        $employeeId, $pickup, $dropoff, $carType, $pickupTime, $paymentSource, $companyName, $distance, $duration, $fare
    );

    // Execute and check result
    if ($stmt->execute()) {
        echo "Ride booked successfully.";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
}
?>