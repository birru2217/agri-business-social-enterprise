<?php
// register_user.php
require_once 'includes/db.php';
require_once 'includes/session.php';
require_once 'includes/email_service.php';

checkLogin();
checkRole(['admin', 'agri_expert']);

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['user_role'];

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];
    $phone = $_POST['phone'] ?? '';
    $location = $_POST['location'] ?? '';

    // Basic validation
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $error = "Please fill in all required fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // ... ragaa hash gootee booda (try block keessatti) ...

       try {
            // 1. Email duraan jiraachuu isaa mirkaneessi
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetch()) {
                $error = "Email kun kanaan dura galmaa'eera.";
            } else {
                // 2. Koodii lakkofsa 6 (OTP) uumi
                $verification_code = createVerificationRecord($pdo, $email);
                
                // 3. Insert user with approved_by set to current admin
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, phone, location, approval_status, approved_by, approved_at) VALUES (?, ?, ?, ?, ?, ?, 'approved', ?, NOW())");
                
                if ($stmt->execute([$name, $email, $hashed_password, $role, $phone, $location, $user_id])) {
                    // Get the newly created user ID
                    $new_user_id = $pdo->lastInsertId();
                    
                    // If role is agri_expert, create profile in agri_expert_profiles table
                    if ($role === 'agri_expert') {
                        $expert_stmt = $pdo->prepare("INSERT INTO agri_expert_profiles (user_id, expertise_area) VALUES (?, ?)");
                        $expert_stmt->execute([$new_user_id, 'General Agriculture']);
                    }

                    // Auto-verify email so user can login immediately
                    $pdo->prepare("INSERT IGNORE INTO email_verification (email, verification_code, expires_at, is_used) VALUES (?, 'AUTOVFY', DATE_ADD(NOW(), INTERVAL 1 YEAR), TRUE)")
                        ->execute([$email]);

                    // Send verification email (non-blocking)
                    @sendVerificationEmail($email, $verification_code);

                    $success = "User registered successfully as <strong>" . htmlspecialchars($role) . "</strong>! They can now log in immediately.";
                    
                } else {
                    $error = "Ragaan kee database-tti hin galle. Maaloo irra deebi'ii yaali.";
                }
            } 
        } catch (PDOException $e) {
            $error = "Database Error: " . $e->getMessage();
        }
    }
}

// Get recently registered users by this admin/expert
$recent_registrations = $pdo->prepare("
    SELECT id, name, email, role, phone, location, approval_status, created_at 
    FROM users 
    WHERE approved_by = ? 
    ORDER BY created_at DESC 
    LIMIT 5
");
$recent_registrations->execute([$user_id]);
$recent_list = $recent_registrations->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register User - Agri-Business</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root { --primary-green: #2c7a2c; --secondary-green: #48bb78; }
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
        .register-header { background: linear-gradient(135deg, var(--primary-green), var(--secondary-green)); color: white; padding: 2rem 0; }
        .form-card { border: none; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .form-control, .form-select { border-radius: 10px; border: 2px solid #e2e8f0; transition: all 0.3s; }
        .form-control:focus, .form-select:focus { border-color: var(--primary-green); box-shadow: 0 0 0 3px rgba(44, 122, 44, 0.1); }
        .btn-primary { background: linear-gradient(135deg, var(--primary-green), var(--secondary-green)); border: none; border-radius: 10px; padding: 12px 24px; font-weight: 600; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(44, 122, 44, 0.3); }
        .registration-item { border-left: 4px solid var(--primary-green); padding: 1rem 1.5rem; margin-bottom: 1rem; background: #f8f9fa; border-radius: 0 8px 8px 0; }
        .role-badge { font-size: 0.75rem; padding: 0.25rem 0.75rem; border-radius: 15px; }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="register-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="fw-bold mb-2">User Registration</h1>
                    <p class="mb-0 opacity-90">Register new users for the Agri-Business platform • Email verification required</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="d-flex gap-2 justify-content-md-end">
                        <?php if ($user_role === 'admin'): ?>
                            <a href="admin_panel.php" class="btn btn-outline-light btn-sm">
                                <i class="bi bi-grid-1x2 me-2"></i>Admin Panel
                            </a>
                        <?php endif; ?>
                        <a href="dashboard.php" class="btn btn-light btn-sm">
                            <i class="bi bi-house me-2"></i>Dashboard
                        </a>
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

        <div class="row g-4">
            <!-- Registration Form -->
            <div class="col-lg-7">
                <div class="card form-card p-4">
                    <h5 class="fw-bold mb-4">
                        <i class="bi bi-person-plus-fill me-2 text-success"></i>Register New User
                    </h5>
                    <form method="POST" action="register_user.php">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Full Name *</label>
                                    <input type="text" name="name" class="form-control" required value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" placeholder="Enter full name">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Email Address *</label>
                                    <input type="email" name="email" class="form-control" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" placeholder="user@example.com">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Password *</label>
                                    <input type="password" name="password" class="form-control" required placeholder="Min 6 characters">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Confirm Password *</label>
                                    <input type="password" name="confirm_password" class="form-control" required placeholder="Re-enter password">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">User Role *</label>
                            <select name="role" class="form-select" required>
                                <option value="">Select role...</option>
                                <option value="farmer" <?php echo (isset($role) && $role === 'farmer') ? 'selected' : ''; ?>>Farmer/Producer</option>
                                <option value="customer" <?php echo (isset($role) && $role === 'customer') ? 'selected' : ''; ?>>Customer/Buyer</option>
                                <option value="investor" <?php echo (isset($role) && $role === 'investor') ? 'selected' : ''; ?>>Social Investor/Donor</option>
                                
                                <?php if ($user_role === 'admin'): ?>
                                    <option value="agri_expert" <?php echo (isset($role) && $role === 'agri_expert') ? 'selected' : ''; ?>>Agricultural Expert</option>
                                <?php endif; ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Phone Number</label>
                                    <input type="tel" name="phone" class="form-control" value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>" placeholder="+1234567890">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Location</label>
                                    <input type="text" name="location" class="form-control" value="<?php echo isset($location) ? htmlspecialchars($location) : ''; ?>" placeholder="City, Country">
                                </div>
                            </div>
                        </div>

                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>
                            <strong>Note:</strong> A 6-digit verification code will be sent to the user's email address. 
                            The user must verify their email before they can log in.
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-person-plus me-2"></i>Register User
                        </button>
                    </form>
                </div>
            </div>

            <!-- Recent Registrations -->
            <div class="col-lg-5">
                <div class="card form-card p-4">
                    <h5 class="fw-bold mb-4">
                        <i class="bi bi-clock-history me-2 text-primary"></i>Recent Registrations
                    </h5>
                    
                    <?php if (empty($recent_list)): ?>
                        <div class="text-center py-4">
                            <i class="bi bi-inbox fs-1 text-muted"></i>
                            <p class="text-muted mt-3">No recent registrations</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recent_list as $user): ?>
                            <div class="registration-item">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($user['name']); ?></h6>
                                        <p class="mb-0 text-muted small"><?php echo htmlspecialchars($user['email']); ?></p>
                                    </div>
                                    <span class="role-badge bg-<?php echo $user['approval_status'] === 'approved' ? 'success' : ($user['approval_status'] === 'rejected' ? 'danger' : 'warning'); ?> text-white">
                                        <?php echo ucfirst($user['approval_status']); ?>
                                    </span>
                                </div>
                                <div class="d-flex gap-2 mb-2">
                                    <span class="badge bg-light text-dark"><?php echo ucfirst($user['role']); ?></span>
                                    <?php if ($user['phone']): ?>
                                        <span class="badge bg-light text-dark"><i class="bi bi-telephone"></i></span>
                                    <?php endif; ?>
                                </div>
                                <small class="text-muted">
                                    <i class="bi bi-calendar me-1"></i><?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                </small>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Registration Stats -->
                <div class="card form-card p-4 mt-4">
                    <h5 class="fw-bold mb-4">
                        <i class="bi bi-graph-up me-2 text-info"></i>Registration Statistics
                    </h5>
                    
                    <?php
                    $stats = $pdo->prepare("
                        SELECT 
                            COUNT(*) as total,
                            SUM(CASE WHEN approval_status = 'pending' THEN 1 ELSE 0 END) as pending,
                            SUM(CASE WHEN approval_status = 'approved' THEN 1 ELSE 0 END) as approved,
                            SUM(CASE WHEN approval_status = 'rejected' THEN 1 ELSE 0 END) as rejected
                        FROM users WHERE approved_by = ?
                    ");
                    $stats->execute([$user_id]);
                    $stats_data = $stats->fetch(PDO::FETCH_ASSOC);
                    ?>
                    
                    <div class="row text-center">
                        <div class="col-6">
                            <div class="bg-light rounded-3 p-3 mb-3">
                                <h4 class="fw-bold text-primary"><?php echo $stats_data['total']; ?></h4>
                                <small class="text-muted">Total Registered</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-light rounded-3 p-3 mb-3">
                                <h4 class="fw-bold text-warning"><?php echo $stats_data['pending']; ?></h4>
                                <small class="text-muted">Pending Approval</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-light rounded-3 p-3">
                                <h4 class="fw-bold text-success"><?php echo $stats_data['approved']; ?></h4>
                                <small class="text-muted">Approved</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="bg-light rounded-3 p-3">
                                <h4 class="fw-bold text-danger"><?php echo $stats_data['rejected']; ?></h4>
                                <small class="text-muted">Rejected</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
