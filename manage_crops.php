<?php
// manage_crops.php
require_once 'includes/db.php';
require_once 'includes/session.php';

checkLogin();
checkRole(['farmer', 'admin']);

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Fetch crops belonging to this farmer
try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE farmer_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    $crops = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $crops = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage My Crops - Agri-Biz</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f4f7f6; }
        .sidebar { min-height: 100vh; background-color: #2c3e50; color: white; padding: 20px; }
        .sidebar a { color: #bdc3c7; text-decoration: none; display: block; padding: 10px; border-radius: 5px; }
        .sidebar a:hover, .sidebar a.active { background-color: #34495e; color: white; }
        .main-content { padding: 30px; }
        .crop-img { width: 60px; height: 60px; object-fit: cover; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar (Consistent with Dashboard) -->
            <nav class="col-md-3 col-lg-2 sidebar">
                <h3 class="text-center mb-4">Agri-Biz</h3>
                <a href="index.php"><i class="bi bi-house me-2"></i> Home</a>
                <a href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
                <a href="manage_crops.php" class="active"><i class="bi bi-tree me-2"></i> My Crops</a>
                <a href="yields.php"><i class="bi bi-graph-up me-2"></i> Track Yields</a>
                <a href="resource_library.php"><i class="bi bi-book me-2"></i> Resource Library</a>
                <hr>
                <a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 col-lg-10 main-content">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Manage My Crops</h2>
                    <a href="add_product.php" class="btn btn-success rounded-pill shadow-sm">
                        <i class="bi bi-plus-circle me-2"></i> Add New Crop
                    </a>
                </div>

                <div class="card shadow-sm border-0 rounded-3 overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="px-4 py-3">Crop Info</th>
                                    <th class="py-3">Price</th>
                                    <th class="py-3">Stock Level</th>
                                    <th class="py-3">Status</th>
                                    <th class="py-3 text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($crops)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-5">
                                            <p class="text-muted mb-0">No crops listed yet. Start by adding your first product!</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($crops as $crop): ?>
                                        <tr>
                                            <td class="px-4">
                                                <div class="d-flex align-items-center">
                                                    <?php if ($crop['image_url']): ?>
                                                        <img src="<?php echo htmlspecialchars($crop['image_url']); ?>" class="crop-img me-3" alt="">
                                                    <?php else: ?>
                                                        <div class="crop-img bg-secondary-subtle d-flex align-items-center justify-content-center me-3">
                                                            <i class="bi bi-image text-muted"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div>
                                                        <div class="fw-bold"><?php echo htmlspecialchars($crop['name']); ?></div>
                                                        <small class="text-muted"><?php echo htmlspecialchars(substr($crop['description'], 0, 40)); ?>...</small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>$<?php echo number_format($crop['price'], 2); ?> / <?php echo $crop['unit']; ?></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <span class="me-2"><?php echo $crop['quantity']; ?></span>
                                                    <div class="progress flex-grow-1" style="height: 6px; max-width: 100px;">
                                                        <div class="progress-bar <?php echo $crop['quantity'] < 10 ? 'bg-danger' : 'bg-success'; ?>" 
                                                             style="width: <?php echo min(100, $crop['quantity']); ?>%"></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge rounded-pill <?php echo $crop['quantity'] > 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'; ?>">
                                                    <?php echo $crop['quantity'] > 0 ? 'Active' : 'Out of Stock'; ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group shadow-sm rounded-pill">
                                                    <a href="edit_product.php?id=<?php echo $crop['id']; ?>" class="btn btn-white btn-sm"><i class="bi bi-pencil text-primary"></i></a>
                                                    <button class="btn btn-white btn-sm"><i class="bi bi-trash text-danger"></i></button>
                                                </div>
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
