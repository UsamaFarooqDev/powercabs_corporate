<?php
session_start();
require_once __DIR__ . '/../auth/supabase.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']) || empty($_SESSION['user']['cid'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$cid = $_SESSION['user']['cid'];

try {
    $supabase = new SupabaseClient(true);
    $rides = $supabase->select('corporate_rides', ['cid' => $cid], '*', 'date.desc', 100);

    $totalRides = count($rides);
    $pendingRides = 0;
    $expense = 0.0;
    foreach ($rides as $ride) {
        if (($ride['status'] ?? '') === 'Pending') {
            $pendingRides++;
        }
        $expense += floatval($ride['fare'] ?? 0);
    }

    echo json_encode([
        'success' => true,
        'rides' => $rides,
        'stats' => [
            'totalRides' => $totalRides,
            'pendingRides' => $pendingRides,
            'expense' => round($expense, 2),
        ],
    ]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to load rides']);
}
?>
