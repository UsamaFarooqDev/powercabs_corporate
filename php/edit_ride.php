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

$cid          = $_SESSION['user']['cid'];
$rideId       = isset($_POST['ride_id'])      ? trim((string)$_POST['ride_id'])       : '';
$status       = isset($_POST['status'])       ? trim((string)$_POST['status'])        : '';
$cancelReason = isset($_POST['cancel_reason']) ? trim((string)$_POST['cancel_reason']) : '';

$allowedStatuses = ['Pending', 'Assigned', 'In Progress', 'Completed', 'Cancelled'];
$allowedReasons  = [
    'Waiting for long time',
    'Unable to contact driver',
    'Driver denied to go to destination',
    'Driver denied to come to pickup',
    'Wrong address shown',
    'The price is not reasonable',
];

if ($rideId === '' || !ctype_digit($rideId)) {
    echo json_encode(['success' => false, 'message' => 'Ride ID is required']);
    exit;
}
if ($status === '' || !in_array($status, $allowedStatuses, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit;
}
// Reason is mandatory when cancelling — and must be one of the curated set
if ($status === 'Cancelled') {
    if ($cancelReason === '' || !in_array($cancelReason, $allowedReasons, true)) {
        echo json_encode(['success' => false, 'message' => 'A valid cancellation reason is required']);
        exit;
    }
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

    $update = ['status' => $status];

    if ($status === 'Cancelled') {
        // Try a few reasonable column names for the reason. If none of them
        // exist we fall back to appending the reason to the row's notes
        // column so it's never lost.
        $reasonCols = ['cancel_reason', 'cancelled_reason', 'cancellation_reason', 'reason'];
        $rowCols    = !empty($existing[0]) ? array_keys($existing[0]) : [];
        $reasonHit  = null;
        foreach ($reasonCols as $c) {
            if (in_array($c, $rowCols, true)) { $reasonHit = $c; break; }
        }
        if ($reasonHit !== null) {
            $update[$reasonHit] = $cancelReason;
        } else {
            // Fall back to a notes-style column.
            foreach (['notes', 'description', 'comments', 'comment', 'remarks'] as $nc) {
                if (in_array($nc, $rowCols, true)) {
                    $existingNote = (string)($existing[0][$nc] ?? '');
                    $update[$nc] = trim($existingNote
                        . (strlen($existingNote) ? "\n" : '')
                        . '[Cancellation reason] ' . $cancelReason);
                    break;
                }
            }
        }
    }

    // Adaptive update: if PostgREST rejects an unknown column (PGRST204),
    // drop it and retry. Required `status` always stays.
    $maxAttempts = 6;
    while ($maxAttempts-- > 0) {
        try {
            $supabase->update('corporate_rides',
                ['id' => (int)$rideId, 'cid' => $cid],
                $update
            );
            break;
        } catch (Throwable $e) {
            $msg = $e->getMessage();
            $col = null;
            if (preg_match("/Could not find the '([^']+)' column/", $msg, $m)) $col = $m[1];
            else if (preg_match("/column \"([^\"]+)\" of relation/i", $msg, $m)) $col = $m[1];
            if ($col !== null && $col !== 'status' && array_key_exists($col, $update)) {
                unset($update[$col]);
                continue;
            }
            throw $e;
        }
    }

    echo json_encode([
        'success' => true,
        'message' => $status === 'Cancelled'
            ? 'Ride cancelled — dispatch notified.'
            : ('Ride status updated to ' . $status),
        'status'  => $status,
    ]);
} catch (Throwable $e) {
    error_log('[edit_ride] ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to update ride status']);
}
