<?php
// approve_users.php
require_once 'includes/db.php';
require_once 'includes/session.php';

checkLogin();
checkRole(['admin']);

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Handle approval/rejection actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_user'])) {
        $approve_user_id = $_POST['approve_user_id'];
        $stmt = $pdo->prepare("UPDATE users SET approval_status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
        if ($stmt->execute([$user_id, $approve_user_id])) {
            $success_message = "User approved successfully!";
        }
    } elseif (isset($_POST['reject_user'])) {
        $reject_user_id = $_POST['reject_user_id'];
        $rejection_reason = $_POST['rejection_reason'] ?? '';
        $stmt = $pdo->prepare("UPDATE users SET approval_status = 'rejected', approved_by = ?, approved_at = NOW(), rejection_reason = ? WHERE id = ?");
        if ($stmt->execute([$user_id, $rejection_reason, $reject_user_id])) {
            $success_message = "User rejected successfully!";
        }
    }
}

// Get pending users
$pending_users = $pdo->query("
    SELECT id, name, email, role, phone, location, created_at 
    FROM users 
    WHERE approval_status = 'pending' 
    ORDER BY created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Get approved users
$approved_users = $pdo->query("
    SELECT u.id, u.name, u.email, u.role, u.phone, u.location, u.approved_at, a.name as approved_by_name
    FROM users u 
    LEFT JOIN users a ON u.approved_by = a.id 
    WHERE u.approval_status = 'approved' 
    ORDER BY u.approved_at DESC
    LIMIT 10
")->fetchAll(PDO::FETCH_ASSOC);

// Get rejected users
$rejected_users = $pdo->query("
    SELECT id, name, email, role, phone, location, rejection_reason, approved_at
    FROM users 
    WHERE approval_status = 'rejected' 
    ORDER BY approved_at DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Statistics
$total_pending = count($pending_users);
$total_approved = $pdo->query("SELECT COUNT(*) FROM users WHERE approval_status = 'approved'")->fetchColumn();
$total_rejected = $pdo->query("SELECT COUNT(*) FROM users WHERE approval_status = 'rejected'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Approval - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root { --admin-primary: #2c3e50; --admin-secondary: #3498db; --success: #27ae60; --danger: #e74c3c; }
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
        .admin-header { background: linear-gradient(135deg, var(--admin-primary), var(--admin-secondary)); color: white; padding: 2rem 0; }
        .stat-card { border: none; border-radius: 16px; transition: transform 0.2s; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .stat-card:hover { transform: translateY(-5px); }
        .user-card { border: none; border-radius: 12px; transition: all 0.3s; margin-bottom: 1rem; }
        .user-card:hover { box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
        .status-badge { font-size: 0.75rem; padding: 0.5rem 1rem; border-radius: 25px; font-weight: 600; }
        .role-badge { font-size: 0.7rem; padding: 0.25rem 0.75rem; border-radius: 15px; }
        .action-buttons { display: flex; gap: 0.5rem; }
        .tab-content { padding: 2rem 0; }
        .nav-tabs .nav-link { border-radius: 10px 10px 0 0; font-weight: 600; }
        .rejection-text { background: #fff5f5; border-left: 4px solid #e74c3c; padding: 0.5rem; margin-top: 0.5rem; font-size: 0.85rem; }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <div class="admin-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="fw-bold mb-2">User Approval Management</h1>
                    <p class="mb-0 opacity-90">Review and approve user registrations • Manage access permissions</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="d-flex gap-2 justify-content-md-end">
                        <a href="admin_panel.php" class="btn btn-outline-light btn-sm">
                            <i class="bi bi-grid-1x2 me-2"></i>Admin Panel
                        </a>
                        <a href="dashboard.php" class="btn btn-light btn-sm">
                            <i class="bi bi-house me-2"></i>Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <!-- Success Message -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i><?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card stat-card p-4 bg-white h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="bg-warning bg-opacity-10 p-3 rounded-4">
                            <i class="bi bi-clock-history text-warning fs-4"></i>
                        </div>
                        <span class="badge bg-warning-subtle text-warning">Pending</span>
                    </div>
                    <h6 class="text-muted mb-1">Pending Approval</h6>
                    <h2 class="fw-bold mb-0"><?php echo $total_pending; ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card p-4 bg-white h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="bg-success bg-opacity-10 p-3 rounded-4">
                            <i class="bi bi-check-circle text-success fs-4"></i>
                        </div>
                        <span class="badge bg-success-subtle text-success">Approved</span>
                    </div>
                    <h6 class="text-muted mb-1">Approved Users</h6>
                    <h2 class="fw-bold mb-0"><?php echo $total_approved; ?></h2>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card p-4 bg-white h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="bg-danger bg-opacity-10 p-3 rounded-4">
                            <i class="bi bi-x-circle text-danger fs-4"></i>
                        </div>
                        <span class="badge bg-danger-subtle text-danger">Rejected</span>
                    </div>
                    <h6 class="text-muted mb-1">Rejected Users</h6>
                    <h2 class="fw-bold mb-0"><?php echo $total_rejected; ?></h2>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="card border-0 rounded-4 shadow-sm">
            <div class="card-header bg-white">
                <ul class="nav nav-tabs card-header-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#pending">
                            <i class="bi bi-clock-history me-2"></i>Pending (<?php echo $total_pending; ?>)
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#approved">
                            <i class="bi bi-check-circle me-2"></i>Recently Approved
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#rejected">
                            <i class="bi bi-x-circle me-2"></i>Recently Rejected
                        </a>
                    </li>
                </ul>
            </div>

            <div class="tab-content">
                <!-- Pending Users Tab -->
                <div class="tab-pane fade show active" id="pending">
                    <?php if (empty($pending_users)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-check-circle fs-1 text-success"></i>
                            <h5 class="text-success mt-3">All Caught Up!</h5>
                            <p class="text-muted">No pending user approvals at the moment.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($pending_users as $user): ?>
                            <div class="card user-card">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="bg-primary bg-opacity-10 p-3 rounded-4 me-3">
                                                    <i class="bi bi-person fs-4 text-primary"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($user['name']); ?></h6>
                                                    <p class="mb-0 text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                                                </div>
                                            </div>
                                            <div class="d-flex gap-2 mb-2">
                                                <span class="role-badge bg-info text-white"><?php echo ucfirst($user['role']); ?></span>
                                                <?php if ($user['phone']): ?>
                                                    <span class="badge bg-light text-dark"><i class="bi bi-telephone me-1"></i><?php echo htmlspecialchars($user['phone']); ?></span>
                                                <?php endif; ?>
                                                <?php if ($user['location']): ?>
                                                    <span class="badge bg-light text-dark"><i class="bi bi-geo-alt me-1"></i><?php echo htmlspecialchars($user['location']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <small class="text-muted">
                                                <i class="bi bi-calendar me-1"></i>Registered: <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                            </small>
                                        </div>
                                        <div class="col-md-4 text-md-end">
                                            <div class="action-buttons justify-content-md-end">
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="approve_user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" name="approve_user" class="btn btn-success btn-sm">
                                                        <i class="bi bi-check-circle me-1"></i>Approve
                                                    </button>
                                                </form>
                                                <button class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#rejectModal<?php echo $user['id']; ?>">
                                                    <i class="bi bi-x-circle me-1"></i>Reject
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Rejection Modal -->
                            <div class="modal fade" id="rejectModal<?php echo $user['id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Reject User Registration</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="reject_user_id" value="<?php echo $user['id']; ?>">
                                                <p>Are you sure you want to reject <strong><?php echo htmlspecialchars($user['name']); ?></strong>?</p>
                                                <div class="mb-3">
                                                    <label class="form-label">Rejection Reason (Optional)</label>
                                                    <textarea name="rejection_reason" class="form-control" rows="3" placeholder="Provide a reason for rejection..."></textarea>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" name="reject_user" class="btn btn-danger">Reject User</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Approved Users Tab -->
                <div class="tab-pane fade" id="approved">
                    <?php if (empty($approved_users)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-person-plus fs-1 text-muted"></i>
                            <h5 class="text-muted mt-3">No Approved Users Yet</h5>
                            <p class="text-muted">Approved users will appear here.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($approved_users as $user): ?>
                            <div class="card user-card">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="bg-success bg-opacity-10 p-3 rounded-4 me-3">
                                                    <i class="bi bi-check-circle fs-4 text-success"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($user['name']); ?></h6>
                                                    <p class="mb-0 text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                                                </div>
                                            </div>
                                            <div class="d-flex gap-2 mb-2">
                                                <span class="role-badge bg-success text-white"><?php echo ucfirst($user['role']); ?></span>
                                                <span class="badge bg-light text-dark">
                                                    <i class="bi bi-person-check me-1"></i>Approved by <?php echo htmlspecialchars($user['approved_by_name'] ?? 'Admin'); ?>
                                                </span>
                                            </div>
                                            <small class="text-muted">
                                                <i class="bi bi-calendar-check me-1"></i>Approved: <?php echo date('M d, Y H:i', strtotime($user['approved_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Rejected Users Tab -->
                <div class="tab-pane fade" id="rejected">
                    <?php if (empty($rejected_users)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-person-x fs-1 text-muted"></i>
                            <h5 class="text-muted mt-3">No Rejected Users</h5>
                            <p class="text-muted">Rejected users will appear here.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($rejected_users as $user): ?>
                            <div class="card user-card">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-8">
                                            <div class="d-flex align-items-center mb-2">
                                                <div class="bg-danger bg-opacity-10 p-3 rounded-4 me-3">
                                                    <i class="bi bi-x-circle fs-4 text-danger"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($user['name']); ?></h6>
                                                    <p class="mb-0 text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                                                </div>
                                            </div>
                                            <div class="d-flex gap-2 mb-2">
                                                <span class="role-badge bg-danger text-white"><?php echo ucfirst($user['role']); ?></span>
                                                <span class="badge bg-light text-dark">
                                                    <i class="bi bi-calendar-x me-1"></i>Rejected: <?php echo date('M d, Y', strtotime($user['approved_at'])); ?>
                                                </span>
                                            </div>
                                            <?php if ($user['rejection_reason']): ?>
                                                <div class="rejection-text">
                                                    <strong>Reason:</strong> <?php echo htmlspecialchars($user['rejection_reason']); ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
