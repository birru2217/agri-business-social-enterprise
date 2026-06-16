<?php
// verify_farmers.php
require_once 'includes/db.php';
require_once 'includes/session.php';

checkLogin();
checkRole(['admin']);

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['farmer_id'])) {
    $action = $_POST['action'];
    $farmer_id = $_POST['farmer_id'];
    
    try {
        // Check if farmer profile exists
        $check = $pdo->prepare("SELECT id FROM farmer_profiles WHERE user_id = ?");
        $check->execute([$farmer_id]);
        $profile = $check->fetch();
        
        if (!$profile) {
            // Create profile if it doesn't exist
            $stmt = $pdo->prepare("INSERT INTO farmer_profiles (user_id, verification_status) VALUES (?, ?)");
            $status = ($action === 'approve') ? 'verified' : 'rejected';
            $stmt->execute([$farmer_id, $status]);
        } else {
            // Update existing profile
            if ($action === 'approve') {
                $stmt = $pdo->prepare("UPDATE farmer_profiles SET verification_status = 'verified' WHERE user_id = ?");
                $stmt->execute([$farmer_id]);
            } elseif ($action === 'reject') {
                $stmt = $pdo->prepare("UPDATE farmer_profiles SET verification_status = 'rejected' WHERE user_id = ?");
                $stmt->execute([$farmer_id]);
            }
        }
        header("Location: verify_farmers.php");
        exit();
    } catch (PDOException $e) {
        $error = "Error updating verification status: " . $e->getMessage();
        error_log("Verification Error: " . $e->getMessage());
    }
}

// Fetch farmers with verification status
try {
    $stmt = $pdo->query("SELECT u.id, u.name, u.email, fp.farm_name, fp.verification_status 
                         FROM users u 
                         LEFT JOIN farmer_profiles fp ON u.id = fp.user_id 
                         WHERE u.role = 'farmer' 
                         ORDER BY fp.verification_status ASC, u.created_at DESC");
    $farmers = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $farmers = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Farmers - Admin Panel</title>
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
                <a href="verify_farmers.php" class="active"><i class="bi bi-shield-check me-2"></i> Verify Farmers</a>
                <a href="all_transactions.php"><i class="bi bi-cash-stack me-2"></i> Transactions</a>
                <hr class="my-4 opacity-25">
                <a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 col-lg-10 main-content">
                <header class="d-flex justify-content-between align-items-center mb-5">
                    <div>
                        <h2 class="fw-bold">Farmer Verification</h2>
                        <p class="text-muted">Review and approve farmer credentials for platform security.</p>
                    </div>
                </header>

                <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="px-4 py-3">Farmer Name</th>
                                    <th class="py-3">Email</th>
                                    <th class="py-3">Farm Name</th>
                                    <th class="py-3">Status</th>
                                    <th class="py-3 text-center">Verification</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($farmers)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5 text-muted">No farmers registered yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($farmers as $farmer): ?>
                                        <tr>
                                            <td class="px-4 fw-bold"><?php echo htmlspecialchars($farmer['name']); ?></td>
                                            <td class="text-muted"><?php echo htmlspecialchars($farmer['email']); ?></td>
                                            <td class="text-muted"><?php echo htmlspecialchars($farmer['farm_name'] ?: 'Not Provided'); ?></td>
                                            <td>
                                                <span class="badge rounded-pill <?php 
                                                    echo $farmer['verification_status'] === 'verified' ? 'bg-success-subtle text-success' : 
                                                        ($farmer['verification_status'] === 'rejected' ? 'bg-danger-subtle text-danger' : 'bg-warning-subtle text-warning'); 
                                                ?> px-3">
                                                    <?php echo ucfirst($farmer['verification_status'] ?: 'Pending'); ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <?php if ($farmer['verification_status'] !== 'verified'): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="approve">
                                                        <input type="hidden" name="farmer_id" value="<?php echo $farmer['id']; ?>">
                                                        <button type="submit" class="btn btn-success btn-sm rounded-pill px-3 me-2 shadow-sm">Approve</button>
                                                    </form>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="reject">
                                                        <input type="hidden" name="farmer_id" value="<?php echo $farmer['id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm rounded-pill px-3 shadow-sm">Reject</button>
                                                    </form>
                                                <?php else: ?>
                                                    <button class="btn btn-outline-secondary btn-sm rounded-pill px-3" disabled>Verified</button>
                                                <?php endif; ?>
                                            </td>
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
