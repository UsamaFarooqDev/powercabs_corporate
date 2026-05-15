<?php
session_start();
require_once __DIR__ . '/../auth/supabase.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user']) || empty($_SESSION['user']['cid'])) {
    echo json_encode(['success' => false]);
    exit;
}

$cid   = $_SESSION['user']['cid'];
$nowTs = time();

try {
    $supabase = new SupabaseClient(true);
    // Fetch pending/scheduled rides for this company (status + pickup time only)
    $rows = $supabase->select('rides', ['cid' => $cid], 'id,status,enroute_at', 'enroute_at.asc');

    $cancelled = 0;
    foreach ($rows as $r) {
        $status = strtolower(trim((string)($r['status'] ?? '')));
        if (!in_array($status, ['pending', 'scheduled'])) continue;

        $pt = $r['enroute_at'] ?? '';
        if (!$pt) continue;

        $ts = strtotime($pt);
        if ($ts === false || $ts >= $nowTs) continue;

        // Past ride stuck in pending/scheduled — mark cancelled
        $supabase->update('rides', ['id' => $r['id']], ['status' => 'Cancelled']);
        $cancelled++;
    }

    echo json_encode(['success' => true, 'cancelled' => $cancelled]);
} catch (Throwable $e) {
    echo json_encode(['success' => false]);
}
