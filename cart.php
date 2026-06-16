<?php
// cart.php
require_once 'includes/db.php';
require_once 'includes/session.php';

checkLogin();

// Session keessaa cart dubbisuu (Yoo duwwaa ta'e array duwwaa uuma)
$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$total_price = 0;

// 1. Meeshaa cart keessaa balleessuuf (Remove)
if (isset($_GET['remove'])) {
    $id = $_GET['remove'];
    unset($_SESSION['cart'][$id]);
    header("Location: cart.php");
    exit();
}

// 2. Ragaa meeshaalee database keessaa fiduu
$products_in_cart = [];
if (!empty($cart)) {
    $ids = implode(',', array_keys($cart));
    try {
        $stmt = $pdo->query("SELECT * FROM products WHERE id IN ($ids)");
        $products_in_cart = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        $products_in_cart = [];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart - WALAL SYSTEM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .cart-card { border: none; border-radius: 15px; }
        .product-img-cart { width: 80px; height: 80px; object-fit: cover; border-radius: 10px; }
    </style>
</head>
<body>
    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold"><i class="bi bi-cart3 me-2"></i>Shopping Cart</h2>
            <a href="marketplace.php" class="btn btn-outline-primary rounded-pill">
                <i class="bi bi-arrow-left me-2"></i>Back to Shop
            </a>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <?php if (empty($products_in_cart)): ?>
                    <div class="card p-5 text-center cart-card shadow-sm">
                        <i class="bi bi-cart-x fs-1 text-muted"></i>
                        <h4 class="mt-3 text-muted">Cart kee duwwaadha.</h4>
                        <p>Mee gara gabaatti deebi'ii waan haaraa filadhu!</p>
                        <a href="marketplace.php" class="btn btn-primary mt-2 rounded-pill px-4">Go to Market</a>
                    </div>
                <?php else: ?>
                    <div class="card shadow-sm cart-card p-3">
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th class="text-center">Quantity</th>
                                        <th>Subtotal</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($products_in_cart as $product): 
                                        $quantity = $cart[$product['id']];
                                        $subtotal = $product['price'] * $quantity;
                                        $total_price += $subtotal;
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="bg-light p-2 rounded me-3">
                                                    <i class="bi bi-box-seam text-primary"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($product['name']); ?></h6>
                                                    <small class="text-muted"><?php echo $product['unit']; ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>$<?php echo number_format($product['price'], 2); ?></td>
                                        <td class="text-center"><?php echo $quantity; ?></td>
                                        <td class="fw-bold">$<?php echo number_format($subtotal, 2); ?></td>
                                        <td>
                                            <a href="cart.php?remove=<?php echo $product['id']; ?>" class="text-danger">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-lg-4">
                <div class="card shadow-sm cart-card p-4">
                    <h5 class="fw-bold mb-4">Order Summary</h5>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal</span>
                        <span>$<?php echo number_format($total_price, 2); ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Tax (0%)</span>
                        <span>$0.00</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-4">
                        <span class="fw-bold">Total</span>
                        <span class="fw-bold fs-4 text-primary">$<?php echo number_format($total_price, 2); ?></span>
                    </div>
                    <a href="checkout.php" class="btn btn-primary w-100 btn-lg rounded-pill <?php echo empty($products_in_cart) ? 'disabled' : ''; ?>">
                        Proceed to Checkout
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>