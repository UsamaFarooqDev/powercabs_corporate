<?php
session_start();
require_once __DIR__ . '/../auth/supabase.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

try {
    $supabase = new SupabaseClient(true);
    $billing  = corporate_billing_config($supabase, $_SESSION['user']);
    $result   = compute_credit_balance($supabase, $_SESSION['user'], $billing);
    echo json_encode(['success' => true] + $result);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'balance' => 0, 'is_revenue' => false]);
}
