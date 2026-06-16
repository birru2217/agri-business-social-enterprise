<?php
// admin_panel.php
require_once 'includes/db.php';
require_once 'includes/session.php';

checkLogin();
checkRole(['admin']);

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// System Stats
try {
    // Total Users
    $userCount = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    // Pending Farmer Verifications
    $pendingVerifications = $pdo->query("SELECT COUNT(*) FROM farmer_profiles WHERE verification_status = 'pending'")->fetchColumn();
    // Total Revenue (Orders + Contributions)
    $orderTotal = $pdo->query("SELECT SUM(total_amount) FROM orders")->fetchColumn() ?: 0;
    $contributionTotal = $pdo->query("SELECT SUM(amount) FROM contributions")->fetchColumn() ?: 0;
    $totalRevenue = $orderTotal + $contributionTotal;
    
    // Agri Expert Stats
    $agriExpertCount = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'agri_expert'")->fetchColumn();
    $availableExperts = $pdo->query("SELECT COUNT(*) FROM agri_expert_profiles aep JOIN users u ON aep.user_id = u.id WHERE u.role = 'agri_expert' AND aep.availability_status = 'available'")->fetchColumn();
    
    // User Approval Stats
    $pendingApprovals = $pdo->query("SELECT COUNT(*) FROM users WHERE approval_status = 'pending'")->fetchColumn();
    $approvedUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE approval_status = 'approved'")->fetchColumn();
    $rejectedUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE approval_status = 'rejected'")->fetchColumn();
    
    // Recent Activity Feed (Mix of everything)
    $activities = [];
    
    // Recent Users
    $recentUsers = $pdo->query("SELECT name, role, created_at as date, 'User Registered' as type FROM users ORDER BY created_at DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
    // Recent Orders
    $recentOrders = $pdo->query("SELECT u.name, 'New Order' as type, o.total_amount as detail, o.created_at as date FROM orders o JOIN users u ON o.customer_id = u.id ORDER BY o.created_at DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
    // Recent Contributions
    $recentContributions = $pdo->query("SELECT u.name, 'New Contribution' as type, c.amount as detail, c.date FROM contributions c JOIN users u ON c.investor_id = u.id ORDER BY c.date DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);

    $activities = array_merge($recentUsers, $recentOrders, $recentContributions);
    usort($activities, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));
    $activities = array_slice($activities, 0, 6);

} catch (PDOException $e) {
    $userCount = $pendingVerifications = $totalRevenue = 0;
    $activities = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Control Center - Agri-Biz</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --admin-bg: #1a1d21; --admin-sidebar: #212529; --accent: #3d8bfd; }
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
        .sidebar { min-height: 100vh; background-color: var(--admin-sidebar); color: #adb5bd; padding: 1.5rem; }
        .sidebar .nav-link { color: #adb5bd; border-radius: 8px; margin-bottom: 0.5rem; transition: all 0.2s; }
        .sidebar .nav-link:hover, .sidebar .nav-link.active { background-color: rgba(61, 139, 253, 0.1); color: var(--accent); }
        .main-content { padding: 2rem; }
        .stat-card { border: none; border-radius: 16px; transition: transform 0.2s; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .stat-card:hover { transform: translateY(-5px); }
        .activity-item { border-left: 3px solid var(--accent); padding-left: 1.5rem; position: relative; margin-bottom: 1.5rem; }
        .activity-item::before { content: ''; width: 12px; height: 12px; background: var(--accent); border-radius: 50%; position: absolute; left: -7.5px; top: 0; }
        .btn-action { border-radius: 12px; padding: 0.8rem 1.5rem; font-weight: 600; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 sidebar sticky-top">
                <div class="d-flex align-items-center mb-4 px-2">
                    <i class="bi bi-shield-lock-fill fs-3 text-primary me-2"></i>
                    <h4 class="mb-0 text-white fw-bold">Admin Hub</h4>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item"><a href="index.php" class="nav-link"><i class="bi bi-house-fill me-2"></i> Home</a></li>
                    <li class="nav-item"><a href="admin_panel.php" class="nav-link active"><i class="bi bi-grid-1x2-fill me-2"></i> Command Center</a></li>
                    <li class="nav-item"><a href="approve_users.php" class="nav-link">
                        <i class="bi bi-person-check-fill me-2"></i> User Approvals
                        <?php if ($pendingApprovals > 0): ?>
                            <span class="badge bg-danger ms-auto"><?php echo $pendingApprovals; ?></span>
                        <?php endif; ?>
                    </a></li>
                    <li class="nav-item"><a href="manage_users.php" class="nav-link"><i class="bi bi-people-fill me-2"></i> User Directory</a></li>
                    <li class="nav-item"><a href="verify_farmers.php" class="nav-link"><i class="bi bi-patch-check-fill me-2"></i> Farmer Verifications</a></li>
                    <li class="nav-item"><a href="#" class="nav-link"><i class="bi bi-mortarboard-fill me-2"></i> Agri Experts</a></li>
                    <li class="nav-item"><a href="all_transactions.php" class="nav-link"><i class="bi bi-cash-stack me-2"></i> Financial Logs</a></li>
                    <li class="nav-item"><a href="impact_reports.php" class="nav-link"><i class="bi bi-graph-up-arrow me-2"></i> Impact Analytics</a></li>
                    <li class="nav-item mt-4"><hr class="opacity-10"></li>
                    <li class="nav-item"><a href="logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-right me-2"></i> Sign Out</a></li>
                </ul>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 col-lg-10 main-content">
                <header class="d-flex justify-content-between align-items-center mb-5">
                    <div>
                        <h1 class="h3 fw-bold mb-1">System Command Center</h1>
                        <p class="text-muted mb-0">Operational overview of the Agri-Biz ecosystem.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-white shadow-sm border rounded-pill px-3"><i class="bi bi-arrow-clockwise me-2"></i> Refresh Data</button>
                        <button class="btn btn-primary rounded-pill px-4 shadow-sm"><i class="bi bi-cloud-download me-2"></i> Backup DB</button>
                    </div>
                </header>

                <!-- Stats Grid -->
                <div class="row g-4 mb-5">
                    <div class="col-md-3">
                        <div class="card stat-card p-4 bg-white h-100">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="bg-primary bg-opacity-10 p-3 rounded-4"><i class="bi bi-people text-primary fs-4"></i></div>
                                <span class="badge bg-success-subtle text-success">+12%</span>
                            </div>
                            <h6 class="text-muted mb-1">Total Users</h6>
                            <h2 class="fw-bold mb-0"><?php echo $userCount; ?></h2>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card p-4 bg-white h-100">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="bg-warning bg-opacity-10 p-3 rounded-4"><i class="bi bi-shield-exclamation text-warning fs-4"></i></div>
                                <span class="badge bg-danger-subtle text-danger">Action Required</span>
                            </div>
                            <h6 class="text-muted mb-1">Pending Farmers</h6>
                            <h2 class="fw-bold mb-0"><?php echo $pendingVerifications; ?></h2>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card p-4 bg-white h-100">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="bg-success bg-opacity-10 p-3 rounded-4"><i class="bi bi-currency-dollar text-success fs-4"></i></div>
                                <span class="badge bg-primary-subtle text-primary">Target: 80%</span>
                            </div>
                            <h6 class="text-muted mb-1">Total Revenue</h6>
                            <h2 class="fw-bold mb-0">$<?php echo number_format($totalRevenue, 2); ?></h2>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card p-4 bg-white h-100">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="bg-info bg-opacity-10 p-3 rounded-4"><i class="bi bi-mortarboard-fill text-info fs-4"></i></div>
                                <span class="badge bg-info-subtle text-info"><?php echo $availableExperts; ?> Available</span>
                            </div>
                            <h6 class="text-muted mb-1">Agri Experts</h6>
                            <h2 class="fw-bold mb-0"><?php echo $agriExpertCount; ?></h2>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Quick Actions -->
                    <div class="col-lg-8">
                        <div class="card border-0 rounded-4 shadow-sm p-4 mb-4">
                            <h5 class="fw-bold mb-4">Core Management Tools</h5>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <a href="manage_users.php" class="btn btn-light w-100 text-start btn-action border-0 bg-light-subtle">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-person-gear fs-3 text-primary me-3"></i>
                                            <div>
                                                <div class="fw-bold">User Access Control</div>
                                                <small class="text-muted">Manage roles and permissions.</small>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <a href="verify_farmers.php" class="btn btn-light w-100 text-start btn-action border-0 bg-light-subtle">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-shield-check fs-3 text-success me-3"></i>
                                            <div>
                                                <div class="fw-bold">Verification Queue</div>
                                                <small class="text-muted">Review <?php echo $pendingVerifications; ?> farmer profiles.</small>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <a href="approve_users.php" class="btn btn-light w-100 text-start btn-action border-0 bg-light-subtle">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-person-check fs-3 text-warning me-3"></i>
                                            <div>
                                                <div class="fw-bold">User Approvals</div>
                                                <small class="text-muted">
                                                    <?php if ($pendingApprovals > 0): ?>
                                                        <span class="badge bg-danger"><?php echo $pendingApprovals; ?></span> pending approvals
                                                    <?php else: ?>
                                                        No pending approvals
                                                    <?php endif; ?>
                                                </small>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <a href="#" class="btn btn-light w-100 text-start btn-action border-0 bg-light-subtle">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-mortarboard-fill fs-3 text-info me-3"></i>
                                            <div>
                                                <div class="fw-bold">Expert Management</div>
                                                <small class="text-muted">Manage <?php echo $agriExpertCount; ?> agricultural experts.</small>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <a href="all_transactions.php" class="btn btn-light w-100 text-start btn-action border-0 bg-light-subtle">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-file-earmark-bar-graph fs-3 text-warning me-3"></i>
                                            <div>
                                                <div class="fw-bold">Financial Audit</div>
                                                <small class="text-muted">Review all sales and donations.</small>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-md-6">
                                    <button class="btn btn-light w-100 text-start btn-action border-0 bg-light-subtle">
                                        <div class="d-flex align-items-center">
                                            <i class="bi bi-gear fs-3 text-secondary me-3"></i>
                                            <div>
                                                <div class="fw-bold">System Settings</div>
                                                <small class="text-muted">Configure platform parameters.</small>
                                            </div>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- System Performance Chart -->
                        <div class="card border-0 rounded-4 shadow-sm p-4">
                            <h5 class="fw-bold mb-4">Ecosystem Performance (7 Days)</h5>
                            <canvas id="performanceChart" height="150"></canvas>
                        </div>
                    </div>

                    <!-- Activity Feed -->
                    <div class="col-lg-4">
                        <div class="card border-0 rounded-4 shadow-sm p-4 h-100">
                            <h5 class="fw-bold mb-4">Real-time Activity</h5>
                            <div class="activity-feed">
                                <?php if (empty($activities)): ?>
                                    <p class="text-muted text-center py-5">No recent activity found.</p>
                                <?php else: ?>
                                    <?php foreach ($activities as $act): ?>
                                        <div class="activity-item">
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="fw-bold small"><?php echo $act['type']; ?></span>
                                                <small class="text-muted"><?php echo date('H:i', strtotime($act['date'])); ?></small>
                                            </div>
                                            <p class="small mb-0 text-muted">
                                                <strong><?php echo htmlspecialchars($act['name']); ?></strong> 
                                                <?php echo is_numeric($act['detail'] ?? null) ? "($".number_format($act['detail'], 2).")" : ($act['role'] ?? ''); ?>
                                            </p>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <div class="mt-auto text-center pt-3 border-top">
                                <a href="#" class="text-decoration-none small fw-bold">View System Logs <i class="bi bi-arrow-right"></i></a>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('performanceChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Marketplace Volume',
                    data: [450, 600, 550, 800, 950, 1200, 1100],
                    borderColor: '#3d8bfd',
                    tension: 0.4,
                    fill: true,
                    backgroundColor: 'rgba(61, 139, 253, 0.05)'
                }, {
                    label: 'Donation Volume',
                    data: [200, 150, 300, 250, 400, 600, 500],
                    borderColor: '#198754',
                    tension: 0.4,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } },
                scales: { y: { beginAtZero: true }, x: { grid: { display: false } } }
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
