<?php
session_start();
require_once __DIR__ . '/../auth/supabase.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']) || empty($_SESSION['user']['cid'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$cid = $_SESSION['user']['cid'];

function normalizeCorporateRide(array $row): array {
    $statusRaw = (string)($row['status'] ?? '');
    $status = trim($statusRaw) === '' ? 'Pending' : ucwords(str_replace('_', ' ', strtolower($statusRaw)));
    $source = strtolower(trim((string)($row['source'] ?? '')));
    $category = $source === 'corporate meet_and_greet' ? 'Meet & Greet' : 'Corporate';
    return [
        'id'             => (string)($row['id'] ?? ''),
        'employee'       => $row['employee'] ?? '',
        'pickup'         => $row['pickup_addr'] ?? '',
        'destination'    => $row['dest_addr'] ?? '',
        'pickupTime'     => $row['enroute_at'] ?? ($row['created_at'] ?? ''),
        'vehicle_number' => $row['vehicle_number'] ?? 'N/A',
        'fare'           => $row['fare_eur'] ?? 0,
        'status'         => $status,
        'category'       => $category,
    ];
}
function isCorporateSource(array $row): bool {
    $source = strtolower(trim((string)($row['source'] ?? '')));
    return $source === 'corporate' || $source === 'corporate meet_and_greet';
}

try {
    $supabase = new SupabaseClient(true);
    $rows = $supabase->select('rides', ['cid' => $cid], '*', 'created_at.desc', 100);
    $rows = array_values(array_filter($rows, 'isCorporateSource'));
    $rides = array_map('normalizeCorporateRide', $rows);

    $totalRides = count($rides);
    $pendingRides = 0;
    $expense = 0.0;
    foreach ($rides as $ride) {
        if (in_array($ride['status'] ?? '', ['Pending', 'Scheduled'])) {
            $pendingRides++;
        }
        if (($ride['status'] ?? '') === 'Completed') {
            $expense += floatval($ride['fare'] ?? 0);
        }
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
