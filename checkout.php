<?php
// checkout.php
require_once 'includes/db.php';
require_once 'includes/session.php';

checkLogin();
checkRole(['customer', 'admin']);

$user_id = $_SESSION['user_id'];
$error = '';

// Simulation: Normally you'd get this from a cart session
$product_id = isset($_GET['product_id']) ? (int)$_GET['product_id'] : 0;
$product = null;

if ($product_id > 0) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $error = "Error fetching product.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $product) {
    // Process "payment"
    try {
        $pdo->beginTransaction();

        // Create Order
        $stmt = $pdo->prepare("INSERT INTO orders (customer_id, total_amount, status) VALUES (?, ?, 'completed')");
        $stmt->execute([$user_id, $product['price']]);
        $order_id = $pdo->lastInsertId();

        // Add Order Item
        $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, 1, ?)");
        $stmt->execute([$order_id, $product['id'], $product['price']]);

        // Update Inventory
        $stmt = $pdo->prepare("UPDATE products SET quantity = quantity - 1 WHERE id = ?");
        $stmt->execute([$product['id']]);

        $pdo->commit();

        // Simulated Notification (e.g., SMS/Email)
        // In a real app, this would use a service like Twilio or SendGrid
        // mail($farmer_email, "New Order Received", "A buyer has purchased your crop...");

        header("Location: payment_success.php?order_id=" . $order_id);
        exit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Payment processing failed: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Agri-Business</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .checkout-card { border-radius: 20px; border: none; }
        .payment-method { border: 2px solid #e9ecef; border-radius: 12px; padding: 15px; cursor: pointer; transition: all 0.2s; }
        .payment-method.active { border-color: #0d6efd; background-color: #f8fbff; }
    </style>
</head>
<body class="bg-light">
    <div class="container my-5">
        <div class="row g-5">
            <div class="col-md-5 order-md-last">
                <h4 class="d-flex justify-content-between align-items-center mb-3">
                    <span class="text-primary">Your cart</span>
                    <span class="badge bg-primary rounded-pill">1</span>
                </h4>
                <ul class="list-group mb-3 shadow-sm">
                    <?php if ($product): ?>
                        <li class="list-group-item d-flex justify-content-between lh-sm p-3">
                            <div>
                                <h6 class="my-0"><?php echo htmlspecialchars($product['name']); ?></h6>
                                <small class="text-muted">Direct from Farmer</small>
                            </div>
                            <span class="text-muted">$<?php echo number_format($product['price'], 2); ?></span>
                        </li>
                    <?php else: ?>
                        <li class="list-group-item text-center py-4">No product selected.</li>
                    <?php endif; ?>
                    <li class="list-group-item d-flex justify-content-between p-3 bg-light fw-bold">
                        <span>Total (USD)</span>
                        <strong>$<?php echo $product ? number_format($product['price'], 2) : '0.00'; ?></strong>
                    </li>
                </ul>
            </div>

            <div class="col-md-7">
                <div class="card checkout-card shadow p-4">
                    <h4 class="mb-4 fw-bold">Secure Checkout</h4>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label">First Name</label>
                                <input type="text" class="form-control rounded-pill" required>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Last Name</label>
                                <input type="text" class="form-control rounded-pill" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Shipping Address</label>
                                <input type="text" class="form-control rounded-pill" placeholder="1234 Main St" required>
                            </div>
                        </div>

                        <hr class="my-4">

                        <h5 class="mb-3">Payment Method</h5>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="payment-method active">
                                    <input class="form-check-input d-none" type="radio" name="paymentMethod" id="credit" checked>
                                    <label class="form-check-label d-flex align-items-center" for="credit">
                                        <i class="bi bi-credit-card fs-3 me-3 text-primary"></i>
                                        <div>
                                            <div class="fw-bold">Credit Card</div>
                                            <small class="text-muted">Secure transaction</small>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="payment-method">
                                    <input class="form-check-input d-none" type="radio" name="paymentMethod" id="mobile">
                                    <label class="form-check-label d-flex align-items-center" for="mobile">
                                        <i class="bi bi-phone fs-3 me-3 text-success"></i>
                                        <div>
                                            <div class="fw-bold">Mobile Money</div>
                                            <small class="text-muted">Popular in rural zones</small>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="mt-4">
                            <button class="w-100 btn btn-primary btn-lg rounded-pill" type="submit" <?php echo !$product ? 'disabled' : ''; ?>>
                                <i class="bi bi-shield-lock me-2"></i> Pay and Complete Order
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
