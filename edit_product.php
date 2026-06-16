<?php
// edit_product.php
require_once 'includes/db.php';
require_once 'includes/session.php';

checkLogin();
checkRole(['farmer', 'admin']);

$user_id = $_SESSION['user_id'];
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$success = '';

// Fetch product details
try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND (farmer_id = ? OR ? = 'admin')");
    $stmt->execute([$product_id, $user_id, $_SESSION['user_role']]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        header("Location: manage_crops.php?error=NotFound");
        exit();
    }
} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];
    $unit = $_POST['unit'];

    if (empty($name) || empty($price) || empty($quantity)) {
        $error = "Please fill in all required fields.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, quantity = ?, unit = ? WHERE id = ?");
            if ($stmt->execute([$name, $description, $price, $quantity, $unit, $product_id])) {
                $success = "Product updated successfully! <a href='manage_crops.php'>Back to list</a>";
                // Refresh product data
                $product['name'] = $name;
                $product['description'] = $description;
                $product['price'] = $price;
                $product['quantity'] = $quantity;
                $product['unit'] = $unit;
            } else {
                $error = "Error updating product.";
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
    <title>Edit Product - Agri-Biz</title>
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
            <h2 class="text-center mb-4"><i class="bi bi-pencil-square text-primary"></i> Edit Listing</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Crop Name *</label>
                    <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($product['description']); ?></textarea>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Price per Unit *</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" name="price" step="0.01" class="form-control" value="<?php echo $product['price']; ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Quantity Available *</label>
                        <input type="number" name="quantity" class="form-control" value="<?php echo $product['quantity']; ?>" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Unit of Measure</label>
                    <select name="unit" class="form-select">
                        <option value="kg" <?php echo $product['unit'] === 'kg' ? 'selected' : ''; ?>>Kilogram (kg)</option>
                        <option value="ton" <?php echo $product['unit'] === 'ton' ? 'selected' : ''; ?>>Ton</option>
                        <option value="box" <?php echo $product['unit'] === 'box' ? 'selected' : ''; ?>>Box</option>
                        <option value="bunch" <?php echo $product['unit'] === 'bunch' ? 'selected' : ''; ?>>Bunch</option>
                        <option value="liter" <?php echo $product['unit'] === 'liter' ? 'selected' : ''; ?>>Liter (L)</option>
                    </select>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill">Update Listing</button>
                    <a href="manage_crops.php" class="btn btn-outline-secondary w-100 rounded-pill">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
