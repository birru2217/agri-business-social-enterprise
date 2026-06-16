<?php
// dashboard.php
require_once 'includes/db.php';
require_once 'includes/session.php';

checkLogin();

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['user_role'];

// Agri experts have their own dedicated dashboard
if ($user_role === 'agri_expert') {
    header("Location: expert_dashboard.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo ucfirst($user_role); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f7f6; }
        .sidebar { min-height: 100vh; background: linear-gradient(135deg, #2c3e50, #34495e); color: white; padding: 20px; }
        .sidebar a { color: #bdc3c7; text-decoration: none; display: block; padding: 12px 15px; border-radius: 8px; margin-bottom: 5px; transition: all 0.3s ease; }
        .sidebar a:hover, .sidebar a.active { background-color: rgba(52, 152, 219, 0.2); color: #3498db; transform: translateX(5px); }
        .main-content { padding: 30px; }
        .card-stat { border: none; border-radius: 10px; transition: transform 0.2s; }
        .card-stat:hover { transform: translateY(-5px); }
        
        /* Enhanced Profile Styling */
        .profile-section { 
            background: rgba(255, 255, 255, 0.1); 
            border-radius: 15px; 
            padding: 20px; 
            margin-bottom: 25px; 
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            text-align: center;
        }
        .profile-avatar { 
            width: 80px; 
            height: 80px; 
            border-radius: 50%; 
            background: linear-gradient(135deg, #3498db, #2ecc71); 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            margin: 0 auto 15px; 
            font-size: 32px; 
            font-weight: bold; 
            color: white;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            transition: transform 0.3s ease;
        }
        .profile-avatar:hover { transform: scale(1.05); }
        .profile-name { 
            font-size: 18px; 
            font-weight: 600; 
            margin-bottom: 5px; 
            color: #fff;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }
        .profile-role { 
            display: inline-block; 
            padding: 4px 12px; 
            background: rgba(255, 255, 255, 0.2); 
            border-radius: 20px; 
            font-size: 12px; 
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .profile-status {
            display: flex;
            align-items: center;
            justify-content: center;
            margin-top: 10px;
            font-size: 12px;
            color: #2ecc71;
        }
        .profile-status .status-dot {
            width: 8px;
            height: 8px;
            background: #2ecc71;
            border-radius: 50%;
            margin-right: 5px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 sidebar">
                <h3 class="text-center mb-4">Agri-Biz</h3>
                <?php
                // Get user profile photo
                $stmt = $pdo->prepare("SELECT profile_photo FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
                $profile_photo = $user_data['profile_photo'] ?? null;
                ?>
                <div class="profile-section">
                    <div class="profile-avatar" style="background-image: url('<?php echo $profile_photo ? htmlspecialchars($profile_photo) : ''; ?>'); background-size: cover; background-position: center;">
                        <?php if (!$profile_photo): ?>
                            <?php echo strtoupper(substr($user_name, 0, 2)); ?>
                        <?php endif; ?>
                    </div>
                    <div class="profile-name"><?php echo htmlspecialchars($user_name); ?></div>
                    <div class="profile-role"><?php echo ucfirst($user_role); ?></div>
                    <div class="profile-status">
                        <span class="status-dot"></span>
                        <span>Online</span>
                    </div>
                </div>
                <a href="index.php"><i class="bi bi-house me-2"></i> Home</a>
                <a href="dashboard.php" class="active"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
                
                <?php if ($user_role === 'admin'): ?>
                    <a href="admin_panel.php" class="text-primary fw-bold"><i class="bi bi-shield-lock-fill me-2"></i> Admin Hub</a>
                <?php endif; ?>
                
                <?php if ($user_role === 'farmer'): ?>
                    <a href="profile.php"><i class="bi bi-person me-2"></i> My Profile</a>
                    <a href="manage_crops.php"><i class="bi bi-tree me-2"></i> My Crops</a>
                    <a href="yields.php"><i class="bi bi-graph-up me-2"></i> Track Yields</a>
                    <a href="farmer_media.php"><i class="bi bi-camera-video me-2"></i> Grain Media</a>
                    <a href="resource_library.php"><i class="bi bi-book me-2"></i> Resource Library</a>
                <?php elseif ($user_role === 'investor'): ?>
                    <a href="profile.php"><i class="bi bi-person me-2"></i> My Profile</a>
                    <a href="impact_reports.php"><i class="bi bi-globe me-2"></i> Social Impact</a>
                    <a href="contributions.php"><i class="bi bi-heart me-2"></i> My Donations</a>
                <?php elseif ($user_role === 'customer'): ?>
                    <a href="profile.php"><i class="bi bi-person me-2"></i> My Profile</a>
                    <a href="marketplace.php"><i class="bi bi-shop me-2"></i> Marketplace</a>
                    <a href="my_orders.php"><i class="bi bi-cart-check me-2"></i> My Orders</a>
                    <a href="payment.php"><i class="bi bi-credit-card me-2"></i> Make Payment</a>
                <?php elseif ($user_role === 'admin'): ?>
                    <a href="profile.php"><i class="bi bi-person me-2"></i> My Profile</a>
                    <a href="register_user.php"><i class="bi bi-person-plus me-2"></i> Register Users</a>
                    <a href="approve_users.php"><i class="bi bi-person-check me-2"></i> Approve Users</a>
                    <a href="manage_users.php"><i class="bi bi-people me-2"></i> Manage Users</a>
                    <a href="verify_farmers.php"><i class="bi bi-shield-check me-2"></i> Verify Farmers</a>
                    <a href="all_transactions.php"><i class="bi bi-cash-stack me-2"></i> Transactions</a>
                <?php elseif ($user_role === 'agri_expert'): ?>
                    <a href="profile.php"><i class="bi bi-person me-2"></i> My Profile</a>
                    <a href="register_user.php"><i class="bi bi-person-plus me-2"></i> Register Users</a>
                    <a href="expert_dashboard.php"><i class="bi bi-mortarboard me-2"></i> Expert Dashboard</a>
                    <a href="expert_resources.php"><i class="bi bi-collection me-2"></i> My Resources</a>
                <?php endif; ?>
                
                <!-- Common links for all users -->
                <a href="grain_gallery.php"><i class="bi bi-images me-2"></i> Grain Gallery</a>
                
                <hr>
                <a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 col-lg-10 main-content">
                <header class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="fw-bold mb-1">Welcome back, <?php echo htmlspecialchars($user_name); ?>! 👋</h2>
                        <p class="text-muted mb-0">Here's what's happening with your <?php echo ucfirst($user_role); ?> dashboard today.</p>
                    </div>
                    <div class="text-end">
                        <div class="badge bg-primary px-3 py-2"><?php echo date('F d, Y'); ?></div>
                        <div class="small text-muted mt-1"><?php echo date('l'); ?></div>
                    </div>
                </header>

                <div class="row g-4">
                    <!-- Role-Specific Quick Stats -->
                    <?php if ($user_role === 'farmer'): ?>
                        <div class="col-md-4">
                            <div class="card card-stat shadow-sm p-4 bg-white">
                                <h5 class="text-muted">Active Crops</h5>
                                <h2 class="mb-0">5</h2>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card card-stat shadow-sm p-4 bg-white">
                                <h5 class="text-muted">Total Sales</h5>
                                <h2 class="mb-0">$1,250</h2>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card card-stat shadow-sm p-4 bg-white">
                                <h5 class="text-muted">Yield Growth</h5>
                                <h2 class="mb-0 text-success">+15%</h2>
                            </div>
                        </div>
                    <?php elseif ($user_role === 'investor'): ?>
                        <div class="col-md-4">
                            <div class="card card-stat shadow-sm p-4 bg-white">
                                <h5 class="text-muted">Total Contributions</h5>
                                <h2 class="mb-0">$5,000</h2>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card card-stat shadow-sm p-4 bg-white">
                                <h5 class="text-muted">Farmers Supported</h5>
                                <h2 class="mb-0">50</h2>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card card-stat shadow-sm p-4 bg-white">
                                <h5 class="text-muted">Carbon Offset</h5>
                                <h2 class="mb-0 text-success">120kg</h2>
                            </div>
                        </div>
                    <?php elseif ($user_role === 'customer'): ?>
                        <div class="col-md-4">
                            <div class="card card-stat shadow-sm p-4 bg-white">
                                <h5 class="text-muted">Active Orders</h5>
                                <h2 class="mb-0">2</h2>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card card-stat shadow-sm p-4 bg-white">
                                <h5 class="text-muted">Wishlist Items</h5>
                                <h2 class="mb-0">12</h2>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card card-stat shadow-sm p-4 bg-white">
                                <h5 class="text-muted">Reward Points</h5>
                                <h2 class="mb-0 text-info">450</h2>
                            </div>
                        </div>
                    <?php elseif ($user_role === 'admin'): ?>
                        <div class="col-md-4">
                            <div class="card card-stat shadow-sm p-4 bg-white">
                                <h5 class="text-muted">Pending Verifications</h5>
                                <h2 class="mb-0">8</h2>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card card-stat shadow-sm p-4 bg-white">
                                <h5 class="text-muted">Total Users</h5>
                                <h2 class="mb-0">150</h2>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card card-stat shadow-sm p-4 bg-white">
                                <h5 class="text-muted">Daily Revenue</h5>
                                <h2 class="mb-0 text-primary">$4,300</h2>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="row mt-5">
                    <div class="col-12">
                        <div class="card shadow-sm">
                            <div class="card-header bg-white">
                                <h5 class="mb-0">Recent Activities</h5>
                            </div>
                            <div class="card-body">
                                <p class="text-muted">No recent activities to show.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
