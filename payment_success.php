<?php
// payment_success.php
require_once 'includes/db.php';
require_once 'includes/session.php';

checkLogin();

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Successful - Agri-Business</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .success-card { max-width: 500px; margin-top: 100px; border-radius: 20px; border: none; }
        .checkmark { width: 100px; height: 100px; display: flex; align-items: center; justify-content: center; border-radius: 50%; background-color: #d1e7dd; color: #198754; font-size: 3rem; margin: 0 auto 30px; }
    </style>
</head>
<body class="bg-light">
    <div class="container d-flex justify-content-center">
        <div class="card success-card shadow p-5 text-center">
            <div class="checkmark">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <h1 class="fw-bold mb-3">Order Confirmed!</h1>
            <p class="text-muted mb-4 lead">Your order #<?php echo $order_id; ?> was successfully processed. We've notified the farmer to prepare your fresh produce for delivery.</p>
            
            <div class="alert alert-info border-0 rounded-4 p-3 mb-4 text-start">
                <h6 class="fw-bold"><i class="bi bi-info-circle me-2"></i> What happens next?</h6>
                <p class="small mb-0">The farmer will receive an automated SMS/Email alert. You'll be notified once your order is shipped.</p>
            </div>

            <div class="d-grid gap-2">
                <a href="dashboard.php" class="btn btn-primary rounded-pill btn-lg">Back to Dashboard</a>
                <a href="marketplace.php" class="btn btn-outline-secondary rounded-pill">Continue Shopping</a>
            </div>
        </div>
    </div>
</body>
</html>
