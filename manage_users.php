<?php
// manage_users.php
require_once 'includes/db.php';
require_once 'includes/session.php';

checkLogin();
checkRole(['admin']);

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Handle approve/reject/delete actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['user_id'])) {
    $action = $_POST['action'];
    $user_id_param = $_POST['user_id'];
    
    try {
        if ($action === 'approve') {
            $stmt = $pdo->prepare("UPDATE users SET approval_status = 'approved' WHERE id = ?");
            $stmt->execute([$user_id_param]);
        } elseif ($action === 'reject') {
            $stmt = $pdo->prepare("UPDATE users SET approval_status = 'rejected' WHERE id = ?");
            $stmt->execute([$user_id_param]);
        } elseif ($action === 'delete') {
            // Delete user and related records
            $pdo->prepare("DELETE FROM farmer_profiles WHERE user_id = ?")->execute([$user_id_param]);
            $pdo->prepare("DELETE FROM investor_profiles WHERE user_id = ?")->execute([$user_id_param]);
            $pdo->prepare("DELETE FROM email_verification WHERE email = (SELECT email FROM users WHERE id = ?)")->execute([$user_id_param]);
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id_param]);
        } elseif ($action === 'edit') {
            // Handle edit - redirect to edit page or handle inline
            $edit_name = $_POST['edit_name'] ?? '';
            $edit_email = $_POST['edit_email'] ?? '';
            $edit_role = $_POST['edit_role'] ?? '';
            
            if ($edit_name && $edit_email && $edit_role) {
                $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?");
                $stmt->execute([$edit_name, $edit_email, $edit_role, $user_id_param]);
            }
        }
        header("Location: manage_users.php");
        exit();
    } catch (PDOException $e) {
        $error = "Error processing action: " . $e->getMessage();
    }
}

// Fetch all users
try {
    $stmt = $pdo->query("SELECT id, name, email, role, created_at, approval_status FROM users ORDER BY created_at DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $users = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f7f6; }
        .sidebar { min-height: 100vh; background-color: #1a252f; color: white; padding: 20px; }
        .sidebar a { color: #95a5a6; text-decoration: none; display: block; padding: 12px; border-radius: 8px; margin-bottom: 5px; }
        .sidebar a:hover, .sidebar a.active { background-color: #2c3e50; color: white; }
        .main-content { padding: 40px; }
        .user-avatar { width: 40px; height: 40px; border-radius: 50%; background-color: #eee; display: flex; align-items: center; justify-content: center; }
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
                <a href="manage_users.php" class="active"><i class="bi bi-people me-2"></i> Manage Users</a>
                <a href="verify_farmers.php"><i class="bi bi-shield-check me-2"></i> Verify Farmers</a>
                <a href="all_transactions.php"><i class="bi bi-cash-stack me-2"></i> Transactions</a>
                <hr class="my-4 opacity-25">
                <a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 col-lg-10 main-content">
                <header class="d-flex justify-content-between align-items-center mb-5">
                    <div>
                        <h2 class="fw-bold">User Management</h2>
                        <p class="text-muted">Overview of all registered platform participants.</p>
                    </div>
                   <a href="register_user.php" class="btn btn-primary rounded-pill px-4 shadow-sm">
        <i class="bi bi-person-plus me-2"></i> Add New User
    </a>
                </header>

                <div class="row g-4 mb-5">
                    <div class="col-md-2">
                        <div class="card shadow-sm border-0 p-3 rounded-4 bg-white text-center">
                            <h6 class="text-muted mb-2">Total Users</h6>
                            <h3 class="fw-bold mb-0"><?php echo count($users); ?></h3>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card shadow-sm border-0 p-3 rounded-4 bg-white text-center">
                            <h6 class="text-muted mb-2">Farmers</h6>
                            <h3 class="fw-bold mb-0 text-success">
                                <?php echo count(array_filter($users, fn($u) => $u['role'] === 'farmer')); ?>
                            </h3>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card shadow-sm border-0 p-3 rounded-4 bg-white text-center">
                            <h6 class="text-muted mb-2">Investors</h6>
                            <h3 class="fw-bold mb-0 text-primary">
                                <?php echo count(array_filter($users, fn($u) => $u['role'] === 'investor')); ?>
                            </h3>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card shadow-sm border-0 p-3 rounded-4 bg-white text-center">
                            <h6 class="text-muted mb-2">Customers</h6>
                            <h3 class="fw-bold mb-0 text-warning">
                                <?php echo count(array_filter($users, fn($u) => $u['role'] === 'customer')); ?>
                            </h3>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card shadow-sm border-0 p-3 rounded-4 bg-white text-center">
                            <h6 class="text-muted mb-2">Agri Experts</h6>
                            <h3 class="fw-bold mb-0 text-info">
                                <?php echo count(array_filter($users, fn($u) => $u['role'] === 'agri_expert')); ?>
                            </h3>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm border-0 rounded-4 overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="px-4 py-3">User</th>
                                    <th class="py-3">Email</th>
                                    <th class="py-3">Role</th>
                                    <th class="py-3">Joined</th>
                                    <th class="py-3 text-center">Approval</th>
                                    <th class="py-3 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td class="px-4">
                                            <div class="d-flex align-items-center">
                                                <div class="user-avatar me-3">
                                                    <i class="bi bi-person text-muted"></i>
                                                </div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($user['name']); ?></div>
                                            </div>
                                        </td>
                                        <td class="text-muted"><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <span class="badge rounded-pill <?php 
                                                echo $user['role'] === 'farmer'     ? 'bg-success-subtle text-success' : 
                                                    ($user['role'] === 'investor'   ? 'bg-primary-subtle text-primary' : 
                                                    ($user['role'] === 'admin'      ? 'bg-danger-subtle text-danger' :
                                                    ($user['role'] === 'agri_expert'? 'bg-info-subtle text-info' :
                                                                                      'bg-warning-subtle text-warning'))); 
                                            ?> px-3">
                                                <?php echo $user['role'] === 'agri_expert' ? 'Agri Expert' : ucfirst($user['role'] ?: 'Unknown'); ?>
                                            </span>
                                        </td>
                                        <td class="small text-muted"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                        <td class="text-center">
                                            <span class="badge rounded-pill <?php 
                                                echo $user['approval_status'] === 'approved' ? 'bg-success-subtle text-success' : 
                                                    ($user['approval_status'] === 'rejected' ? 'bg-danger-subtle text-danger' : 'bg-warning-subtle text-warning'); 
                                            ?> px-3">
                                                <?php echo ucfirst($user['approval_status'] ?: 'Pending'); ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="btn-group shadow-sm rounded-pill">
                                                <?php if ($user['approval_status'] !== 'approved'): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="approve">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" class="btn btn-white btn-sm" title="Approve"><i class="bi bi-check-circle text-success"></i></button>
                                                    </form>
                                                <?php endif; ?>
                                                <?php if ($user['approval_status'] !== 'rejected'): ?>
                                                    <form method="POST" style="display: inline;">
                                                        <input type="hidden" name="action" value="reject">
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <button type="submit" class="btn btn-white btn-sm" title="Reject"><i class="bi bi-x-circle text-danger"></i></button>
                                                    </form>
                                                <?php endif; ?>
                                                <button class="btn btn-white btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $user['id']; ?>" title="Edit"><i class="bi bi-pencil text-primary"></i></button>
                                                <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <button type="submit" class="btn btn-white btn-sm" title="Delete"><i class="bi bi-trash text-danger"></i></button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Edit Modals for each user -->
    <?php foreach ($users as $user): ?>
        <div class="modal fade" id="editModal<?php echo $user['id']; ?>" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Edit User</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="action" value="edit">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            <div class="mb-3">
                                <label class="form-label">Name</label>
                                <input type="text" name="edit_name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="edit_email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Role</label>
                                <select name="edit_role" class="form-select" required>
                                    <option value="farmer" <?php echo $user['role'] === 'farmer' ? 'selected' : ''; ?>>Farmer</option>
                                    <option value="customer" <?php echo $user['role'] === 'customer' ? 'selected' : ''; ?>>Customer</option>
                                    <option value="investor" <?php echo $user['role'] === 'investor' ? 'selected' : ''; ?>>Investor</option>
                                    <option value="agri_expert" <?php echo $user['role'] === 'agri_expert' ? 'selected' : ''; ?>>Agri Expert</option>
                                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
