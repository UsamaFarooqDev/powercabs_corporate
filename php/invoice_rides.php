<?php
session_start();
require_once __DIR__ . '/../auth/supabase.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']) || empty($_SESSION['user']['cid'])) {
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$cid         = $_SESSION['user']['cid'];
$employeeId  = isset($_GET['employee_id']) ? trim((string)$_GET['employee_id']) : '';
$fromDate    = isset($_GET['from']) ? trim((string)$_GET['from']) : '';
$toDate      = isset($_GET['to'])   ? trim((string)$_GET['to'])   : '';

if ($employeeId === '') {
    echo json_encode(['success' => false, 'message' => 'Employee is required']);
    exit;
}

try {
    $supabase = new SupabaseClient(true);
    $rows = $supabase->select('rides',
        ['cid' => $cid, 'employee_id' => $employeeId],
        '*',
        'pickupTime.desc'
    );
    $rides = array_values(array_filter($rows, function ($r) {
        $source = strtolower(trim((string)($r['source'] ?? '')));
        $isCorporate = ($source === 'corporate' || $source === 'corporate meet_and_greet');
        return $isCorporate && strtolower((string)($r['status'] ?? '')) === 'completed';
    }));

    // Optional date range filter on pickupTime (client supplies YYYY-MM-DD)
    if ($fromDate !== '' || $toDate !== '') {
        $fromTs = $fromDate !== '' ? strtotime($fromDate . ' 00:00:00') : null;
        $toTs   = $toDate   !== '' ? strtotime($toDate   . ' 23:59:59') : null;
        $rides = array_values(array_filter($rides, function ($r) use ($fromTs, $toTs) {
            $pt = $r['pickupTime'] ?? '';
            $ts = $pt ? strtotime($pt) : false;
            if (!$ts) return false;
            if ($fromTs !== null && $ts < $fromTs) return false;
            if ($toTs   !== null && $ts > $toTs)   return false;
            return true;
        }));
    }

    // Normalise the charge field — prefer total_charge, fall back to fare
    $out = [];
    foreach ($rides as $r) {
        $charge = $r['total_charge'] ?? null;
        if ($charge === null || $charge === '') $charge = $r['fare'] ?? 0;
        $source = strtolower(trim((string)($r['source'] ?? '')));
        $isMeetGreet = ($source === 'corporate meet_and_greet');
        $out[] = [
            'id'             => $r['id']             ?? null,
            'pickup'         => $r['pickup']         ?? ($r['pickup_addr'] ?? ''),
            'destination'    => $r['destination']    ?? ($r['dest_addr'] ?? ''),
            'pickupTime'     => $r['pickupTime']     ?? ($r['created_at'] ?? ''),
            'vehicle_number' => $r['vehicle_number'] ?? '',
            'distance'       => $r['distance']       ?? ($r['distance_km'] ?? ''),
            'charge'         => floatval($charge),
            'source'         => $r['source']         ?? '',
            'ride_kind'      => $isMeetGreet ? 'meet_and_greet' : 'corporate_ride',
            'ride_kind_label'=> $isMeetGreet ? 'Meet & Greet' : 'Corporate Ride',
        ];
    }

    echo json_encode(['success' => true, 'rides' => $out]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to load rides']);
}
