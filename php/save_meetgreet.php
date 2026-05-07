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

$user        = $_SESSION['user'];
$cid         = $user['cid'];
$companyName = trim((string)($user['name'] ?? ''));

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    echo json_encode(['success' => false, 'message' => 'Invalid payload']);
    exit;
}

// ── Validate required fields ──
$required = ['employee_id', 'employee_name', 'pickup', 'dropoff', 'flight_no', 'flight_time', 'fare', 'distance', 'carType'];
foreach ($required as $f) {
    if (!array_key_exists($f, $data) || $data[$f] === '' || $data[$f] === null) {
        echo json_encode(['success' => false, 'message' => "Missing field: $f"]);
        exit;
    }
}

$pickupTimeIso = date('Y-m-d H:i:s', strtotime((string)$data['flight_time']));
$serviceType   = (string)($data['service_type'] ?? 'Arrival');

// Defence-in-depth: only accept terminals from the curated list. Anything
// else is silently treated as "Driver decides" so the dispatcher never sees
// arbitrary strings stored as a meeting point.
$allowedTerminals = [
    'Terminal 1 — Arrivals',
    'Terminal 2 — Arrivals',
    'Terminal 3 — Departures',
    'Terminal 4 — Departures',
    'Terminal 5 — Limousine',
];
$terminalRaw = trim((string)($data['terminal'] ?? ''));
$terminal    = in_array($terminalRaw, $allowedTerminals, true) ? $terminalRaw : '';

// Compose a rich M&G notes block so the dispatcher can see the airport/
// flight context inside the same notes column the regular rides use.
$notesParts = [];
$notesParts[] = sprintf('[Meet & Greet · %s]', $serviceType);
if (!empty($data['airport_name']) || !empty($data['airport_code'])) {
    $notesParts[] = 'Airport: ' . trim(($data['airport_name'] ?? '') . ' (' . ($data['airport_code'] ?? '') . ')');
}
if ($terminal !== '')           $notesParts[] = 'Terminal: ' . $terminal;
if (!empty($data['airline']))   $notesParts[] = 'Airline: '  . $data['airline'];
if (!empty($data['flight_no'])) $notesParts[] = 'Flight: '   . $data['flight_no'];
$notesParts[] = sprintf('Pax: %d · Bags: %d',
    intval($data['passengers'] ?? 1),
    intval($data['luggage']    ?? 0)
);
if (!empty($data['placard'])) $notesParts[] = 'Placard: ' . $data['placard'];
if (!empty($data['notes']))   $notesParts[] = 'Note: '    . $data['notes'];
$mgNotes = implode(' · ', $notesParts);

// ── Same exact payload shape as save_ride.php (which is proven to work) ──
$insertPayload = [
    'company'        => $companyName,
    'employee'       => (string)$data['employee_name'],
    'employee_id'    => (string)$data['employee_id'],
    'pickup'         => (string)$data['pickup'],
    'destination'    => (string)$data['dropoff'],
    'payment_source' => 'Stripe',
    'pickupTime'     => $pickupTimeIso,
    'carType'        => (string)$data['carType'],
    'status'         => 'Pending',
    'cid'            => $cid,
    'fare'           => floatval($data['fare']),
    'eta'            => intval($data['eta'] ?? 0),
    'distance'       => floatval($data['distance']),
];

// Optional fields used to *distinguish* a Meet & Greet booking from a
// regular ride. If the column doesn't exist on `corporate_rides`, the
// retry loop below silently drops it.
$optional = [
    'source'           => 'corporate-meetgreet',
    'notes'            => $mgNotes,
    'description'      => $mgNotes,
    'special_requests' => $mgNotes,
    'comments'         => $mgNotes,
    // Terminal / meeting point — try these column names in order; whichever
    // exists on the schema gets the value, the rest are stripped.
    'terminal'         => $terminal,
    'meeting_point'    => $terminal,
    'terminal_name'    => $terminal,
    // Geo coords from the Google route — saved when a column exists.
    'pickup_lat'       => isset($data['pickup_lat'])  ? floatval($data['pickup_lat'])  : null,
    'pickup_lng'       => isset($data['pickup_lng'])  ? floatval($data['pickup_lng'])  : null,
    'dropoff_lat'      => isset($data['dropoff_lat']) ? floatval($data['dropoff_lat']) : null,
    'dropoff_lng'      => isset($data['dropoff_lng']) ? floatval($data['dropoff_lng']) : null,
];
foreach ($optional as $k => $v) {
    if ($v !== null && $v !== '') {
        $insertPayload[$k] = $v;
    }
}

try {
    $supabase = new SupabaseClient(true);

    // Drop any optional column the schema doesn't have, then retry.
    // Required columns (the original save_ride.php shape) always stay.
    $optionalKeys = array_keys($optional);
    $maxAttempts  = 12;
    while ($maxAttempts-- > 0) {
        try {
            $result = $supabase->insert('corporate_rides', $insertPayload);
            if (!is_array($result) || empty($result)) {
                throw new Exception('Insert returned no row.');
            }
            $newId = $result[0]['id'] ?? null;
            break;
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            $col = null;
            if (preg_match("/Could not find the '([^']+)' column/", $msg, $m)) $col = $m[1];
            else if (preg_match("/column \"([^\"]+)\" of relation/i", $msg, $m)) $col = $m[1];

            // Only drop the column if it's one of our *optional* tags.
            // Anything else (a NOT NULL on a required column) is a real
            // problem — surface it.
            if ($col !== null && array_key_exists($col, $insertPayload) && in_array($col, $optionalKeys, true)) {
                unset($insertPayload[$col]);
                continue;
            }
            throw $e;
        }
    }

    echo json_encode([
        'success' => true,
        'message' => 'Your ride has been confirmed. Based on driver availability it will be assigned shortly.',
        'ride_id' => $newId ?? null,
    ]);
} catch (Throwable $e) {
    error_log('[save_meetgreet] ' . $e->getMessage()
        . ' | payload_keys=' . json_encode(array_keys($insertPayload ?? [])));
    echo json_encode([
        'success' => false,
        'message' => 'Could not save booking. ' . $e->getMessage(),
    ]);
}
