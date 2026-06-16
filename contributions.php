<?php
// contributions.php
require_once 'includes/db.php';
require_once 'includes/session.php';

checkLogin();
// checkRole(['investor', 'admin']); // Tooftaa kanaan yoo fayyadamte mirkaneessi

$user_id = $_SESSION['user_id'];
$success_msg = isset($_GET['success']) ? "Buusiin kee milkaa'inaan galmaa'eera!" : '';

try {
    // Database keessaa ragaa fiduu
    $stmt = $pdo->prepare("SELECT * FROM contributions WHERE investor_id = ? ORDER BY date DESC");
    $stmt->execute([$user_id]);
    $contributions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $contributions = [];
    $error = "Error: " . $e->getMessage();
}

// Waliigala qarshii
$total_amount = array_sum(array_column($contributions, 'amount'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Contributions - WALAL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f7f6; }
        .sidebar { min-height: 100vh; background-color: #2c3e50; color: white; padding: 20px; }
        .sidebar a { color: #bdc3c7; text-decoration: none; display: block; padding: 12px; border-radius: 8px; margin-bottom: 5px; }
        .sidebar a:hover, .sidebar a.active { background-color: #34495e; color: white; }
        .main-content { padding: 30px; }
        .table-card { border-radius: 15px; border: none; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-3 col-lg-2 sidebar">
                <h3 class="text-center mb-4 fw-bold">WALAL</h3>
                <a href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
                <a href="contributions.php" class="active"><i class="bi bi-heart-fill me-2"></i> My Donations</a>
                <hr>
                <a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
            </nav>

            <main class="col-md-9 col-lg-10 main-content">
                <?php if ($success_msg): ?>
                    <div class="alert alert-success alert-dismissible fade show"><?php echo $success_msg; ?></div>
                <?php endif; ?>

                <header class="d-flex justify-content-between align-items-center mb-5">
                    <div>
                        <h2 class="fw-bold text-dark">My Contributions</h2>
                        <p class="text-muted small">Waliigala Buusii: <strong>$<?php echo number_format($total_amount, 2); ?></strong></p>
                    </div>
                    <a href="add_contribution.php" class="btn btn-primary rounded-pill px-4 shadow-sm">
                        <i class="bi bi-plus-lg me-2"></i> New Contribution
                    </a>
                </header>

                <div class="card table-card shadow-sm overflow-hidden">
                    <table class="table table-hover align-middle mb-0 bg-white">
                        <thead class="table-light">
                            <tr>
                                <th class="px-4">Project Name</th>
                                <th class="text-center">Amount</th>
                                <th class="text-center">Date</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($contributions)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-5 text-muted">Hanga ammaatti buusiin galmaa'e hin jiru.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($contributions as $item): ?>
                                    <tr>
                                        <td class="px-4 fw-semibold"><?php echo htmlspecialchars($item['project_name']); ?></td>
                                        <td class="text-center text-success fw-bold">$<?php echo number_format($item['amount'], 2); ?></td>
                                        <td class="text-center text-muted"><?php echo date('M d, Y', strtotime($item['date'])); ?></td>
                                        <td class="text-center">
                                            <span class="badge bg-light text-success border border-success">Completed</span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>
</body>
</html>