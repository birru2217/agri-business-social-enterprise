<?php
// payment_callback.php - Webhook endpoint for payment providers
require_once 'includes/db.php';
require_once 'includes/payment_gateway.php';

// Get JSON data from webhook
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

// Log webhook for debugging
error_log("Payment webhook received: " . $json_data);

$paymentGateway = new PaymentGateway($pdo);

// Determine provider from headers or data
$provider = $_SERVER['HTTP_X_PROVIDER'] ?? $data['provider'] ?? 'unknown';
$transactionId = $data['transaction_id'] ?? null;
$status = $data['status'] ?? 'unknown';

if ($transactionId && $provider !== 'unknown') {
    // Process the callback
    $success = $paymentGateway->processCallback($provider, $transactionId, $status, $data);
    
    if ($success) {
        // Return success response
        header('HTTP/1.1 200 OK');
        echo json_encode(['status' => 'success', 'message' => 'Callback processed']);
    } else {
        // Return error response
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(['status' => 'error', 'message' => 'Callback processing failed']);
    }
} else {
    // Invalid webhook data
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['status' => 'error', 'message' => 'Invalid webhook data']);
}
?>
