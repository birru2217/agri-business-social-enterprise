<?php
// all_transactions.php
require_once 'includes/db.php';
require_once 'includes/session.php';

checkLogin();
checkRole(['admin']);

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Fetch all transactions (orders and contributions)
try {
    // Orders
    $stmt = $pdo->query("SELECT o.id, u.name as user_name, o.total_amount as amount, 'Purchase' as type, o.created_at as date, o.status 
                         FROM orders o 
                         JOIN users u ON o.customer_id = u.id");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Contributions
    $stmt = $pdo->query("SELECT c.id, u.name as user_name, c.amount, 'Donation' as type, c.date, 'completed' as status 
                         FROM contributions c 
                         JOIN users u ON c.investor_id = u.id");
    $contributions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $transactions = array_merge($orders, $contributions);
    usort($transactions, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));
} catch (PDOException $e) {
    $transactions = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Transactions - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f7f6; }
        .sidebar { min-height: 100vh; background-color: #1a252f; color: white; padding: 20px; }
        .sidebar a { color: #95a5a6; text-decoration: none; display: block; padding: 12px; border-radius: 8px; margin-bottom: 5px; }
        .sidebar a:hover, .sidebar a.active { background-color: #2c3e50; color: white; }
        .main-content { padding: 40px; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 sidebar">
                <h3 class="text-center mb-5 fw-bold text-primary">Admin Panel</h3>
                <a href="index.php"><i class="bi bi-house me-2"></i> Home</a>
                <a href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
                <a href="manage_users.php"><i class="bi bi-people me-2"></i> Manage Users</a>
                <a href="verify_farmers.php"><i class="bi bi-shield-check me-2"></i> Verify Farmers</a>
                <a href="all_transactions.php" class="active"><i class="bi bi-cash-stack me-2"></i> Transactions</a>
                <hr class="my-4 opacity-25">
                <a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 col-lg-10 main-content">
                <header class="d-flex justify-content-between align-items-center mb-5">
                    <div>
                        <h2 class="fw-bold">Financial Overview</h2>
                        <p class="text-muted">Tracking all platform sales and social contributions.</p>
                    </div>
                    <button class="btn btn-outline-secondary rounded-pill px-4 shadow-sm"><i class="bi bi-download me-2"></i> Export Report</button>
                </header>

                <div class="row g-4 mb-5">
                    <div class="col-md-4">
                        <div class="card shadow-sm border-0 p-4 rounded-4 bg-white text-center">
                            <h6 class="text-muted mb-2">Total Volume</h6>
                            <h2 class="fw-bold">$<?php echo number_format(array_sum(array_column($transactions, 'amount')), 2); ?></h2>
                            <small class="text-success"><i class="bi bi-graph-up"></i> Lifetime Gross</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card shadow-sm border-0 p-4 rounded-4 bg-white text-center">
                            <h6 class="text-muted mb-2">Social Donations</h6>
                            <h2 class="fw-bold text-primary">$<?php 
                                echo number_format(array_sum(array_column(array_filter($transactions, fn($t) => $t['type'] === 'Donation'), 'amount')), 2); 
                            ?></h2>
                            <small class="text-muted">Total social capital</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card shadow-sm border-0 p-4 rounded-4 bg-white text-center">
                            <h6 class="text-muted mb-2">Marketplace Sales</h6>
                            <h2 class="fw-bold text-success">$<?php 
                                echo number_format(array_sum(array_column(array_filter($transactions, fn($t) => $t['type'] === 'Purchase'), 'amount')), 2); 
                            ?></h2>
                            <small class="text-muted">Total crop revenue</small>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="px-4 py-3">User</th>
                                    <th class="py-3">Type</th>
                                    <th class="py-3 text-center">Status</th>
                                    <th class="py-3">Amount</th>
                                    <th class="py-3 text-end px-4">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($transactions)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">No transactions found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($transactions as $t): ?>
                                        <tr>
                                            <td class="px-4 fw-bold"><?php echo htmlspecialchars($t['user_name']); ?></td>
                                            <td>
                                                <span class="badge rounded-pill <?php echo $t['type'] === 'Donation' ? 'bg-primary-subtle text-primary' : 'bg-success-subtle text-success'; ?> px-3">
                                                    <?php echo $t['type']; ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge rounded-pill <?php echo $t['status'] === 'completed' ? 'bg-success-subtle text-success' : 'bg-warning-subtle text-warning'; ?> px-2">
                                                    <?php echo ucfirst($t['status']); ?>
                                                </span>
                                            </td>
                                            <td class="fw-bold text-dark">$<?php echo number_format($t['amount'], 2); ?></td>
                                            <td class="text-muted text-end px-4 small"><?php echo date('M d, Y H:i', strtotime($t['date'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
