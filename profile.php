<?php
// profile.php
require_once 'includes/db.php';
require_once 'includes/session.php';

checkLogin();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['user_role'];

$error = '';
$success = '';

// Get current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user_data = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $location = trim($_POST['location']);
    $bio = trim($_POST['bio']);
    
    // Basic validation
    if (empty($name) || empty($email)) {
        $error = "Name and email are required fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        try {
            // Handle profile photo upload
            $profile_photo = $user_data['profile_photo']; // Keep existing photo by default
            
            if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES['profile_photo'];
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                $max_size = 5 * 1024 * 1024; // 5MB
                
                if (!in_array($file['type'], $allowed_types)) {
                    $error = "Only JPG, PNG, GIF, and WebP images are allowed.";
                } elseif ($file['size'] > $max_size) {
                    $error = "Profile photo must be less than 5MB.";
                } else {
                    // Create uploads directory if it doesn't exist
                    $upload_dir = 'uploads/profiles/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    // Generate unique filename
                    $filename = 'profile_' . $user_id . '_' . time() . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
                    $filepath = $upload_dir . $filename;
                    
                    // Move uploaded file
                    if (move_uploaded_file($file['tmp_name'], $filepath)) {
                        // Delete old photo if exists
                        if ($user_data['profile_photo'] && file_exists($user_data['profile_photo'])) {
                            unlink($user_data['profile_photo']);
                        }
                        $profile_photo = $filepath;
                    } else {
                        $error = "Failed to upload profile photo.";
                    }
                }
            }
            
            if (empty($error)) {
                // Update user profile
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET name = ?, email = ?, phone = ?, location = ?, bio = ?, profile_photo = ?
                    WHERE id = ?
                ");
                
                if ($stmt->execute([$name, $email, $phone, $location, $bio, $profile_photo, $user_id])) {
                    // Update session
                    $_SESSION['user_name'] = $name;
                    
                    // Refresh user data
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $success = "Profile updated successfully!";
                } else {
                    $error = "Failed to update profile. Please try again.";
                }
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Agri-Business</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root { --primary-green: #2c7a2c; --secondary-green: #48bb78; }
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
        .profile-header { background: linear-gradient(135deg, var(--primary-green), var(--secondary-green)); color: white; padding: 2rem 0; }
        .profile-card { border: none; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .photo-upload-area { border: 2px dashed #e2e8f0; border-radius: 12px; padding: 30px; text-align: center; cursor: pointer; transition: all 0.3s; }
        .photo-upload-area:hover { border-color: var(--primary-green); background: rgba(44, 122, 44, 0.05); }
        .current-photo { width: 120px; height: 120px; border-radius: 50%; object-fit: cover; border: 4px solid white; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .form-control, .form-select { border-radius: 10px; border: 2px solid #e2e8f0; transition: all 0.3s; }
        .form-control:focus, .form-select:focus { border-color: var(--primary-green); box-shadow: 0 0 0 3px rgba(44, 122, 44, 0.1); }
        .btn-primary { background: linear-gradient(135deg, var(--primary-green), var(--secondary-green)); border: none; border-radius: 10px; padding: 12px 24px; font-weight: 600; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(44, 122, 44, 0.3); }
        .btn-outline-secondary { border-radius: 10px; padding: 10px 16px; font-weight: 500; }
        .role-badge { font-size: 0.75rem; padding: 0.5rem 1rem; border-radius: 25px; font-weight: 600; }
        .stats-card { background: linear-gradient(135deg, #667eea, #764ba2); color: white; border-radius: 12px; padding: 20px; }
        .info-box { background: #f8f9fa; border-left: 4px solid var(--primary-green); padding: 15px; border-radius: 0 8px 8px 0; margin-bottom: 20px; }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="profile-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="fw-bold mb-2">My Profile</h1>
                    <p class="mb-0 opacity-90">Manage your personal information and profile photo</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="d-flex gap-2 justify-content-md-end">
                        <a href="dashboard.php" class="btn btn-outline-light btn-sm">
                            <i class="bi bi-house me-2"></i>Dashboard
                        </a>
                        <a href="logout.php" class="btn btn-outline-light btn-sm">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout
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
            <!-- Profile Form -->
            <div class="col-lg-8">
                <div class="card profile-card p-4">
                    <h5 class="fw-bold mb-4">
                        <i class="bi bi-person-gear me-2 text-success"></i>Profile Information
                    </h5>
                    <form method="POST" action="profile.php" enctype="multipart/form-data">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Full Name *</label>
                                    <input type="text" name="name" class="form-control" required 
                                           value="<?php echo htmlspecialchars($user_data['name']); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Email Address *</label>
                                    <input type="email" name="email" class="form-control" required 
                                           value="<?php echo htmlspecialchars($user_data['email']); ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Phone Number</label>
                                    <input type="tel" name="phone" class="form-control" 
                                           value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>" 
                                           placeholder="+251912345678">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Location</label>
                                    <input type="text" name="location" class="form-control" 
                                           value="<?php echo htmlspecialchars($user_data['location'] ?? ''); ?>" 
                                           placeholder="City, Country">
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Bio</label>
                            <textarea name="bio" class="form-control" rows="4" 
                                      placeholder="Tell us about yourself..."><?php echo htmlspecialchars($user_data['bio'] ?? ''); ?></textarea>
                            <small class="text-muted">Brief description about yourself (optional)</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Profile Photo</label>
                            <div class="photo-upload-area" onclick="document.getElementById('profile_photo').click()">
                                <?php if ($user_data['profile_photo']): ?>
                                    <img src="<?php echo htmlspecialchars($user_data['profile_photo']); ?>" 
                                         alt="Profile Photo" class="current-photo mb-3">
                                <?php else: ?>
                                    <div class="mb-3">
                                        <i class="bi bi-person-circle fs-1 text-muted"></i>
                                    </div>
                                <?php endif; ?>
                                <div>
                                    <i class="bi bi-camera me-2"></i>
                                    <span>Click to upload new photo</span>
                                    <small class="d-block text-muted mt-1">JPG, PNG, GIF, WebP (Max 5MB)</small>
                                </div>
                                <input type="file" name="profile_photo" id="profile_photo" class="d-none" accept="image/*">
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-2"></i>Update Profile
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">
                                <i class="bi bi-arrow-clockwise me-2"></i>Reset
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Profile Stats -->
            <div class="col-lg-4">
                <div class="card profile-card p-4 mb-4">
                    <h5 class="fw-bold mb-4">
                        <i class="bi bi-graph-up me-2 text-primary"></i>Account Stats
                    </h5>
                    
                    <?php
                    // Get user statistics based on role
                    $stats = [];
                    if ($user_role === 'farmer') {
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE farmer_id = ?");
                        $stmt->execute([$user_id]);
                        $stats['products'] = $stmt->fetchColumn();
                        
                       $stmt = $pdo->prepare("SELECT COUNT(*) 
                       FROM orders o 
                       JOIN order_items oi ON o.id = oi.order_id 
                       JOIN products p ON oi.product_id = p.id 
                       WHERE p.farmer_id = ?");
                        $stmt->execute([$user_id]);
                        $stats['orders'] = $stmt->fetchColumn();
                    } elseif ($user_role === 'customer') {
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE customer_id = ?");
                        $stmt->execute([$user_id]);
                        $stats['orders'] = $stmt->fetchColumn();
                        
                        $stmt = $pdo->prepare("SELECT SUM(total_amount) FROM orders WHERE customer_id = ? AND status = 'completed'");
                        $stmt->execute([$user_id]);
                        $stats['total_spent'] = $stmt->fetchColumn() ?: 0;
                    } elseif ($user_role === 'investor') {
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM contributions WHERE investor_id = ?");
                        $stmt->execute([$user_id]);
                        $stats['contributions'] = $stmt->fetchColumn();
                        
                        $stmt = $pdo->prepare("SELECT SUM(amount) FROM contributions WHERE investor_id = ?");
                        $stmt->execute([$user_id]);
                        $stats['total_contributed'] = $stmt->fetchColumn() ?: 0;
                    }
                    ?>
                    
                    <div class="stats-card mb-3">
                        <div class="text-center">
                            <i class="bi bi-person-check fs-1 mb-2"></i>
                            <h6 class="mb-1">Account Status</h6>
                            <span class="badge bg-white text-success">Active</span>
                        </div>
                    </div>
                    
                    <?php if ($user_role === 'farmer'): ?>
                        <div class="row text-center mb-3">
                            <div class="col-6">
                                <div class="bg-light rounded-3 p-3">
                                    <h4 class="fw-bold text-primary"><?php echo $stats['products'] ?? 0; ?></h4>
                                    <small class="text-muted">Products</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="bg-light rounded-3 p-3">
                                    <h4 class="fw-bold text-success"><?php echo $stats['orders'] ?? 0; ?></h4>
                                    <small class="text-muted">Orders</small>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($user_role === 'customer'): ?>
                        <div class="row text-center mb-3">
                            <div class="col-6">
                                <div class="bg-light rounded-3 p-3">
                                    <h4 class="fw-bold text-primary"><?php echo $stats['orders'] ?? 0; ?></h4>
                                    <small class="text-muted">Orders</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="bg-light rounded-3 p-3">
                                    <h4 class="fw-bold text-success">ETB <?php echo number_format($stats['total_spent'] ?? 0, 2); ?></h4>
                                    <small class="text-muted">Total Spent</small>
                                </div>
                            </div>
                        </div>
                    <?php elseif ($user_role === 'investor'): ?>
                        <div class="row text-center mb-3">
                            <div class="col-6">
                                <div class="bg-light rounded-3 p-3">
                                    <h4 class="fw-bold text-primary"><?php echo $stats['contributions'] ?? 0; ?></h4>
                                    <small class="text-muted">Contributions</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="bg-light rounded-3 p-3">
                                    <h4 class="fw-bold text-success">ETB <?php echo number_format($stats['total_contributed'] ?? 0, 2); ?></h4>
                                    <small class="text-muted">Total Contributed</small>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="info-box">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bi bi-shield-check me-2 text-success"></i>
                            <span class="fw-bold">Account Security</span>
                        </div>
                        <small class="text-muted">Your account is protected with email verification and secure login.</small>
                    </div>
                </div>

                <div class="card profile-card p-4">
                    <h5 class="fw-bold mb-4">
                        <i class="bi bi-info-circle me-2 text-info"></i>Account Info
                    </h5>
                    
                    <div class="mb-3">
                        <small class="text-muted">User ID</small>
                        <p class="mb-2 fw-bold">#<?php echo $user_data['id']; ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted">Role</small>
                        <p class="mb-2">
                            <span class="role-badge bg-primary text-white"><?php echo ucfirst($user_data['role']); ?></span>
                        </p>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted">Member Since</small>
                        <p class="mb-2"><?php echo date('F d, Y', strtotime($user_data['created_at'])); ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted">Email Status</small>
                        <p class="mb-2">
                            <span class="badge bg-success">Verified</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function resetForm() {
            if (confirm('Are you sure you want to reset the form? Any unsaved changes will be lost.')) {
                location.reload();
            }
        }
        
        // Preview profile photo before upload
        document.getElementById('profile_photo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const uploadArea = document.querySelector('.photo-upload-area');
                    const img = uploadArea.querySelector('img') || document.createElement('img');
                    img.src = e.target.result;
                    img.className = 'current-photo mb-3';
                    img.alt = 'Profile Photo Preview';
                    
                    if (!uploadArea.querySelector('img')) {
                        uploadArea.querySelector('.mb-3').replaceWith(img);
                    } else {
                        uploadArea.querySelector('img').src = e.target.result;
                    }
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
