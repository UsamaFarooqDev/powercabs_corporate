<?php
// Load Stripe PHP SDK
require_once __DIR__ . '/../assets/stripe-php/init.php';

// Use your secret key
\Stripe\Stripe::setApiKey("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");

header('Content-Type: application/json');

try {
    $intent = \Stripe\PaymentIntent::create([
        'amount' => 60, // 10 cents = €0.10
        'currency' => 'eur',
        'payment_method_types' => ['card'], // ✅ force card only
    ]);

    echo json_encode(['clientSecret' => $intent->client_secret]);
} catch (\Stripe\Exception\ApiErrorException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
