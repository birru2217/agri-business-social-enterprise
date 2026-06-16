<?php
// my_orders.php
require_once 'includes/db.php';
require_once 'includes/session.php';

// Mirkaneessa: Namni seene jiraachuu fi 'customer' ta'uu isaa
checkLogin();
// Yoo 'customer' qofaan ta'e kan itti dabali, yoo kaan dhiisu dandeessa
// checkRole('customer'); 

$user_id = $_SESSION['user_id'];

// Database keessaa ragaa fiduu
try {
    // Database kee keessatti column-ni 'customer_id' waan jiruuf isaan filtr godha
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE customer_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $orders = [];
    $error = "Error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - WALAL SYSTEM</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .sidebar { min-height: 100vh; background-color: #2c3e50; color: white; padding: 20px; }
        .sidebar a { color: #bdc3c7; text-decoration: none; display: block; padding: 12px; border-radius: 8px; margin-bottom: 5px; }
        .sidebar a:hover, .sidebar a.active { background-color: #34495e; color: white; }
        .main-content { padding: 30px; }
        .order-card { border: none; border-radius: 12px; transition: transform 0.2s; }
        .order-card:hover { transform: translateY(-5px); }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-3 col-lg-2 sidebar">
                <h3 class="text-center mb-4 fw-bold text-primary">WALAL</h3>
                <a href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
                <a href="marketplace.php"><i class="bi bi-shop me-2"></i> Marketplace</a>
                <a href="my_orders.php" class="active"><i class="bi bi-bag-check me-2"></i> My Orders</a>
                <hr>
                <a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
            </nav>

            <main class="col-md-9 col-lg-10 main-content">
                <header class="d-flex justify-content-between align-items-center mb-5">
                    <div>
                        <h2 class="fw-bold">My Orders</h2>
                        <p class="text-muted small">Ajaja kee hunda asitti hordofuu dandeessa.</p>
                    </div>
                    <a href="marketplace.php" class="btn btn-primary rounded-pill px-4 shadow-sm">
                        <i class="bi bi-cart-plus me-2"></i> Shop More
                    </a>
                </header>

                <?php if (empty($orders)): ?>
                    <div class="text-center py-5 shadow-sm bg-white rounded-4">
                        <i class="bi bi-cart-x fs-1 text-muted"></i>
                        <h4 class="mt-3 text-muted">Hanga ammaatti waan ajajje hin qabdu.</h4>
                        <a href="marketplace.php" class="btn btn-outline-primary mt-3">Gabaa Ilaali</a>
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($orders as $order): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card h-100 shadow-sm order-card">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between mb-3">
                                            <span class="badge bg-light text-dark border">#ORD-<?php echo $order['id']; ?></span>
                                            <span class="badge <?php echo $order['status'] == 'completed' ? 'bg-success' : 'bg-warning'; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </div>
                                        <h4 class="fw-bold text-success mb-1"><?php echo number_format($order['total_amount'], 2); ?> ETB</h4>
                                        <p class="text-muted small mb-3">
                                            <i class="bi bi-calendar3 me-1"></i> 
                                            <?php echo date('M d, Y', strtotime($order['created_at'])); ?>
                                        </p>
                                        <div class="d-grid">
                                            <button class="btn btn-outline-secondary btn-sm rounded-pill">View Details</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
</body>
</html>
