<?php
// payment.php
require_once 'includes/db.php';
require_once 'includes/session.php';
require_once 'includes/payment_gateway.php';

checkLogin();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['user_role'];

$paymentGateway = new PaymentGateway($pdo);

// Initialize payment methods if not exists
initializePaymentMethods($pdo);

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = $_POST['payment_method'];
    $amount = floatval($_POST['amount']);
    $order_id = $_POST['order_id'] ?? null;
    $phone_number = $_POST['phone_number'] ?? '';
    
    if (empty($payment_method) || $amount <= 0) {
        $error = "Please select payment method and enter valid amount.";
    } else {
        $paymentData = [
            'user_id' => $user_id,
            'order_id' => $order_id,
            'amount' => $amount,
            'phone_number' => $phone_number
        ];
        
        try {
            switch ($payment_method) {
                case 'commercial_bank':
                    $result = $paymentGateway->processCommercialBank($paymentData);
                    break;
                case 'telebirr':
                    $result = $paymentGateway->processTelebirr($paymentData);
                    break;
                case 'mpesa':
                    $result = $paymentGateway->processMpesa($paymentData);
                    break;
                default:
                    $result = ['success' => false, 'error' => 'Invalid payment method'];
            }
            
            if ($result['success']) {
                $success = "Payment initiated successfully! Transaction ID: " . $result['transaction_id'];
                $payment_result = $result;
            } else {
                $error = $result['error'];
            }
        } catch (Exception $e) {
            $error = "Payment processing error: " . $e->getMessage();
        }
    }
}

// Get available payment methods
$payment_methods = $paymentGateway->getAvailablePaymentMethods();

// Get user payment history
$payment_history = $paymentGateway->getUserPaymentHistory($user_id);

// Get order details if order_id provided
$order_details = null;
if (isset($_GET['order_id'])) {
    $stmt = $this->pdo->prepare("SELECT * FROM orders WHERE id = ? AND customer_id = ?");
    $stmt->execute([$_GET['order_id'], $user_id]);
    $order_details = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment - Agri-Business</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root { --primary-green: #2c7a2c; --secondary-green: #48bb78; --telebirr: #FF6B35; --mpesa: #4CAF50; --bank: #1976D2; }
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
        .payment-header { background: linear-gradient(135deg, var(--primary-green), var(--secondary-green)); color: white; padding: 2rem 0; }
        .payment-card { border: none; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); transition: transform 0.3s; }
        .payment-card:hover { transform: translateY(-5px); }
        .method-card { border: 2px solid #e2e8f0; border-radius: 12px; padding: 20px; cursor: pointer; transition: all 0.3s; margin-bottom: 15px; }
        .method-card:hover { border-color: var(--primary-green); box-shadow: 0 4px 15px rgba(44, 122, 44, 0.1); }
        .method-card.selected { border-color: var(--primary-green); background: rgba(44, 122, 44, 0.05); }
        .method-icon { width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: white; margin-bottom: 15px; }
        .bank-icon { background: var(--bank); }
        .telebirr-icon { background: var(--telebirr); }
        .mpesa-icon { background: var(--mpesa); }
        .btn-primary { background: linear-gradient(135deg, var(--primary-green), var(--secondary-green)); border: none; border-radius: 10px; padding: 12px 24px; font-weight: 600; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(44, 122, 44, 0.3); }
        .form-control { border-radius: 10px; border: 2px solid #e2e8f0; transition: all 0.3s; }
        .form-control:focus { border-color: var(--primary-green); box-shadow: 0 0 0 3px rgba(44, 122, 44, 0.1); }
        .payment-result { background: linear-gradient(135deg, #667eea, #764ba2); color: white; border-radius: 16px; padding: 30px; margin-top: 20px; }
        .transaction-item { border-left: 4px solid var(--primary-green); padding: 15px; margin-bottom: 15px; background: #f8f9fa; border-radius: 0 8px 8px 0; }
        .status-badge { font-size: 0.75rem; padding: 0.5rem 1rem; border-radius: 25px; font-weight: 600; }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="payment-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="fw-bold mb-2">Payment Processing</h1>
                    <p class="mb-0 opacity-90">Secure payment processing with Commercial Bank, Telebirr, and M-Pesa</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="d-flex gap-2 justify-content-md-end">
                        <a href="dashboard.php" class="btn btn-outline-light btn-sm">
                            <i class="bi bi-house me-2"></i>Dashboard
                        </a>
                        <?php if ($user_role === 'customer'): ?>
                            <a href="marketplace.php" class="btn btn-outline-light btn-sm">
                                <i class="bi bi-shop me-2"></i>Marketplace
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <!-- Success/Error Messages -->
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($payment_result) && $payment_result['success']): ?>
            <!-- Payment Result -->
            <div class="payment-result">
                <div class="text-center">
                    <i class="bi bi-check-circle fs-1 mb-3"></i>
                    <h3 class="mb-3">Payment Initiated Successfully!</h3>
                    <p class="mb-4">Transaction ID: <strong><?php echo $payment_result['transaction_id']; ?></strong></p>
                    
                    <?php if (isset($payment_result['redirect_url'])): ?>
                        <div class="mb-3">
                            <a href="<?php echo $payment_result['redirect_url']; ?>" class="btn btn-light btn-lg" target="_blank">
                                <i class="bi bi-box-arrow-up-right me-2"></i>Complete Payment
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($payment_result['ussd_code'])): ?>
                        <div class="mb-3">
                            <p class="mb-2">Dial this USSD code to complete payment:</p>
                            <div class="bg-white text-dark p-3 rounded-3 fs-4 fw-bold">
                                <?php echo $payment_result['ussd_code']; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (isset($payment_result['stk_push'])): ?>
                        <div class="mb-3">
                            <p class="mb-2">STK Push sent to your phone. Check your phone to complete payment.</p>
                            <div class="bg-white text-dark p-3 rounded-3">
                                <i class="bi bi-phone me-2"></i><?php echo $payment_result['phone_number']; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <small class="opacity-75">You will be redirected back automatically after payment completion.</small>
                </div>
            </div>
        <?php else: ?>
            <!-- Payment Form -->
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card payment-card p-4">
                        <h5 class="fw-bold mb-4">
                            <i class="bi bi-credit-card me-2 text-success"></i>Make Payment
                        </h5>
                        
                        <?php if ($order_details): ?>
                            <div class="alert alert-info mb-4">
                                <h6 class="mb-2">Order Details</h6>
                                <p class="mb-1">Order #<?php echo $order_details['id']; ?> - <?php echo htmlspecialchars($order_details['status']); ?></p>
                                <p class="mb-0 fw-bold">Amount: ETB <?php echo number_format($order_details['total_amount'], 2); ?></p>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="payment.php">
                            <?php if ($order_details): ?>
                                <input type="hidden" name="order_id" value="<?php echo $order_details['id']; ?>">
                            <?php endif; ?>
                            
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Amount (ETB)</label>
                                <input type="number" name="amount" class="form-control" step="0.01" 
                                       value="<?php echo $order_details['total_amount'] ?? ''; ?>" 
                                       <?php echo $order_details ? 'readonly' : 'required'; ?>
                                       placeholder="Enter amount">
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label fw-semibold">Select Payment Method</label>
                                <?php foreach ($payment_methods as $method): ?>
                                    <div class="method-card" onclick="selectPaymentMethod('<?php echo $method['provider']; ?>', this)">
                                        <div class="d-flex align-items-center">
                                            <div class="method-icon <?php echo $method['provider'] ?>-icon">
                                                <?php
                                                $icon = $method['provider'] === 'commercial_bank' ? 'bi-bank' : 
                                                       ($method['provider'] === 'telebirr' ? 'bi-phone' : 'bi-phone-fill');
                                                ?>
                                                <i class="bi <?php echo $icon; ?>"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($method['name']); ?></h6>
                                                <small class="text-muted">
                                                    <?php 
                                                    echo $method['type'] === 'bank' ? 'Bank Transfer' : 
                                                         ($method['type'] === 'mobile_money' ? 'Mobile Money' : 'Card Payment'); 
                                                    ?>
                                                </small>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="payment_method" 
                                                       value="<?php echo $method['provider']; ?>" 
                                                       id="<?php echo $method['provider']; ?>" required>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            
                            <!-- Mobile Money Phone Number (shown when mobile money selected) -->
                            <div class="mb-4" id="phone_section" style="display: none;">
                                <label class="form-label fw-semibold">Phone Number</label>
                                <input type="tel" name="phone_number" class="form-control" 
                                       placeholder="+251912345678" pattern="[+][0-9]{12}">
                                <small class="text-muted">Enter your mobile money registered phone number</small>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 btn-lg">
                                <i class="bi bi-lock me-2"></i>Proceed with Payment
                            </button>
                        </form>
                    </div>
                </div>
                
                <div class="col-lg-4">
                    <!-- Payment History -->
                    <div class="card payment-card p-4">
                        <h5 class="fw-bold mb-4">
                            <i class="bi bi-clock-history me-2 text-primary"></i>Recent Payments
                        </h5>
                        
                        <?php if (empty($payment_history)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-inbox fs-1 text-muted"></i>
                                <p class="text-muted mt-3">No payment history</p>
                            </div>
                        <?php else: ?>
                            <?php foreach (array_slice($payment_history, 0, 5) as $payment): ?>
                                <div class="transaction-item">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h6 class="mb-1 fw-bold">ETB <?php echo number_format($payment['amount'], 2); ?></h6>
                                            <p class="mb-0 text-muted small"><?php echo htmlspecialchars($payment['payment_method_name']); ?></p>
                                        </div>
                                        <span class="status-badge bg-<?php 
                                            echo $payment['status'] === 'completed' ? 'success' : 
                                                 ($payment['status'] === 'pending' ? 'warning' : 'danger'); 
                                        ?> text-white">
                                            <?php echo ucfirst($payment['status']); ?>
                                        </span>
                                    </div>
                                    <small class="text-muted">
                                        <i class="bi bi-calendar me-1"></i>
                                        <?php echo date('M d, Y H:i', strtotime($payment['created_at'])); ?>
                                    </small>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function selectPaymentMethod(provider, element) {
            // Remove selected class from all method cards
            document.querySelectorAll('.method-card').forEach(card => {
                card.classList.remove('selected');
            });
            
            // Add selected class to clicked card
            element.classList.add('selected');
            
            // Check the radio button
            document.getElementById(provider).checked = true;
            
            // Show/hide phone number field for mobile money
            const phoneSection = document.getElementById('phone_section');
            if (provider === 'telebirr' || provider === 'mpesa') {
                phoneSection.style.display = 'block';
            } else {
                phoneSection.style.display = 'none';
            }
        }
        
        // Auto-select first payment method
        document.addEventListener('DOMContentLoaded', function() {
            const firstMethod = document.querySelector('.method-card');
            if (firstMethod) {
                firstMethod.click();
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
