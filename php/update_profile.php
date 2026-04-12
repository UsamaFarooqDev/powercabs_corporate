<?php
session_start();
require_once __DIR__ . '/../auth/supabase.php';

$redirect = '../profile.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redirect);
    exit;
}

$user = $_SESSION['user'] ?? null;
$cid = is_array($user) ? trim((string)($user['cid'] ?? '')) : '';
if ($cid === '') {
    $_SESSION['error'] = 'You must be logged in to update your profile.';
    header('Location: ../login.php');
    exit;
}

$name = htmlspecialchars(trim($_POST['name'] ?? ''));
$phone = htmlspecialchars(trim($_POST['phone'] ?? ''));
$address = htmlspecialchars(trim($_POST['address'] ?? ''));

if ($name === '' || $phone === '') {
    $_SESSION['error'] = 'Name and phone are required.';
    header('Location: ' . $redirect);
    exit;
}

try {
    $supabase = new SupabaseClient(true);
    $data = [
        'name' => $name,
        'phone' => $phone,
        'address' => $address,
    ];
    $updated = false;
    $lastEx = null;
    foreach (corporate_row_filters_try($user) as $filter) {
        try {
            $supabase->update('corporate', $filter, $data);
            $updated = true;
            break;
        } catch (Throwable $e) {
            $lastEx = $e;
        }
    }
    if (!$updated) {
        throw $lastEx ?? new Exception('No matching corporate row to update.');
    }
    $_SESSION['user']['name'] = $name;
    $_SESSION['success'] = 'Profile updated successfully.';
} catch (Throwable $e) {
    $_SESSION['error'] = 'Error updating profile.';
}

header('Location: ' . $redirect);
exit;