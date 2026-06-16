<?php
// add_product.php
require_once 'includes/db.php';
require_once 'includes/session.php';

checkLogin();
checkRole(['farmer', 'admin']);

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $unit = $_POST['unit'];
    $image_url = '';

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/products/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        $filename = time() . '_' . basename($_FILES['image']['name']);
        $target_file = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $image_url = $target_file;
        }
    }

    if (empty($name) || empty($price) || empty($quantity)) {
        $error = "Please fill in all required fields.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO products (farmer_id, name, description, price, quantity, unit, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$user_id, $name, $description, $price, $quantity, $unit, $image_url])) {
                $success = "Product listed successfully! <a href='marketplace.php'>View Marketplace</a>";
            } else {
                $error = "Error listing product.";
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
    <title>Add Product - Agri-Business</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body { background-color: #f8f9fa; }
        .form-card { max-width: 600px; margin-top: 50px; border-radius: 15px; border: none; }
    </style>
</head>
<body>
    <div class="container d-flex justify-content-center">
        <div class="card form-card shadow p-4 w-100 mb-5">
            <h2 class="text-center mb-4"><i class="bi bi-plus-circle-fill text-success"></i> List New Crop</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" action="add_product.php" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Crop Name *</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Organic Tomatoes" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3" placeholder="Describe the crop, variety, and quality..."></textarea>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Price per Unit *</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" name="price" step="0.01" class="form-control" required>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Quantity Available *</label>
                        <input type="number" name="quantity" class="form-control" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Unit of Measure</label>
                    <select name="unit" class="form-select">
                        <option value="kg">Kilogram (kg)</option>
                        <option value="ton">Ton</option>
                        <option value="box">Box</option>
                        <option value="bunch">Bunch</option>
                        <option value="liter">Liter (L)</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Crop Image</label>
                    <input type="file" name="image" class="form-control" accept="image/*">
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-success w-100 rounded-pill">Post Listing</button>
                    <a href="dashboard.php" class="btn btn-outline-secondary w-100 rounded-pill">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
