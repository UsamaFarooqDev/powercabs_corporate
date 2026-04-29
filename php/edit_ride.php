<?php
session_start();
require_once __DIR__ . '/../auth/supabase.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']) || empty($_SESSION['user']['cid'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$cid    = $_SESSION['user']['cid'];
$rideId = isset($_POST['ride_id']) ? trim((string)$_POST['ride_id']) : '';
$status = isset($_POST['status'])  ? trim((string)$_POST['status'])  : '';

$allowedStatuses = ['Pending', 'Assigned', 'In Progress', 'Completed', 'Cancelled'];

if ($rideId === '' || !ctype_digit($rideId)) {
    echo json_encode(['success' => false, 'message' => 'Ride ID is required']);
    exit;
}
if ($status === '' || !in_array($status, $allowedStatuses, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}

try {
    $supabase = new SupabaseClient(true);

    // Verify the ride belongs to this corporate account
    $existing = $supabase->select('corporate_rides',
        ['id' => (int)$rideId, 'cid' => $cid], '*', null, 1);
    if (empty($existing)) {
        echo json_encode(['success' => false, 'message' => 'Ride not found']);
        exit;
    }

    $supabase->update('corporate_rides',
        ['id' => (int)$rideId, 'cid' => $cid],
        ['status' => $status]
    );

    echo json_encode([
        'success' => true,
        'message' => 'Ride status updated to ' . $status,
        'status'  => $status,
    ]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to update ride status']);
}
