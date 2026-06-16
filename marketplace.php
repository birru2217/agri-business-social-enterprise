<?php
// marketplace.php
require_once 'includes/db.php';
require_once 'includes/session.php';

checkLogin();

$user_role = $_SESSION['user_role'];
$user_id = $_SESSION['user_id'];

// Fetch all products
try {
    $stmt = $pdo->query("SELECT p.*, u.name as farmer_name FROM products p JOIN users u ON p.farmer_id = u.id ORDER BY p.created_at DESC");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $products = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Marketplace - Agri-Business</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .product-card { border: none; transition: box-shadow 0.3s; border-radius: 15px; overflow: hidden; }
        .product-card:hover { box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .product-img { height: 200px; object-fit: cover; background-color: #e9ecef; }
        .navbar-custom { background-color: #2c3e50; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark navbar-custom shadow-sm sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php">Agri-Biz Marketplace</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <?php if ($user_role === 'farmer'): ?>
                        <li class="nav-item"><a class="btn btn-success btn-sm ms-2" href="add_product.php">Add Crop</a></li>
                    <?php endif; ?>
                    <li class="nav-item ms-3">
                        <a href="cart.php" class="btn btn-outline-light btn-sm position-relative">
                            <i class="bi bi-cart"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">0</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row mb-4">
            <div class="col-md-6">
                <h2>Fresh Produce Marketplace</h2>
                <p class="text-muted">Directly from smallholder farmers to you.</p>
            </div>
            <div class="col-md-6">
                <form class="d-flex">
                    <input class="form-control me-2" type="search" placeholder="Search crops..." aria-label="Search">
                    <button class="btn btn-primary" type="submit">Search</button>
                </form>
            </div>
        </div>

        <div class="row g-4">
            <?php if (empty($products)): ?>
                <div class="col-12 text-center py-5">
                    <i class="bi bi-box-seam fs-1 text-muted"></i>
                    <p class="mt-3">No products available at the moment.</p>
                </div>
            <?php else: ?>
                <?php foreach ($products as $product): ?>
                    <div class="col-md-4 col-lg-3">
                        <div class="card h-100 product-card shadow-sm">
                            <?php if ($product['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" class="card-img-top product-img" alt="<?php echo htmlspecialchars($product['name']); ?>">
                            <?php else: ?>
                                <div class="product-img d-flex align-items-center justify-content-center">
                                    <i class="bi bi-image text-muted fs-1"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                <p class="card-text text-muted small"><?php echo htmlspecialchars(substr($product['description'], 0, 100)) . '...'; ?></p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold fs-5">$<?php echo number_format($product['price'], 2); ?>/<?php echo $product['unit']; ?></span>
                                    <span class="badge bg-light text-dark">Stock: <?php echo $product['quantity']; ?></span>
                                </div>
                                <div class="mt-2 small text-muted">
                                    <i class="bi bi-person"></i> Farmer: <?php echo htmlspecialchars($product['farmer_name']); ?>
                                </div>
                            </div>
                            <div class="card-footer bg-white border-top-0 p-3">
                                <?php if ($user_role === 'customer'): ?>
                                    <button class="btn btn-primary w-100 rounded-pill add-to-cart" data-id="<?php echo $product['id']; ?>">
                                        <i class="bi bi-cart-plus me-2"></i> Add to Cart
                                    </button>
                                <?php elseif ($user_role === 'farmer' && $product['farmer_id'] == $user_id): ?>
                                    <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-outline-primary w-100 rounded-pill">
                                        <i class="bi bi-pencil me-2"></i> Edit Listing
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
