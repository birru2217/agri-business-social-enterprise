<?php
// add_yield.php
require_once 'includes/db.php';
require_once 'includes/session.php';
checkLogin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $crop = $_POST['crop'];
    $month = $_POST['month'];
    $yield = $_POST['yield'];
    $target = $_POST['target'];
    $user_id = $_SESSION['user_id'];

    $stmt = $pdo->prepare("INSERT INTO yields (user_id, crop, month, yield, target) VALUES (?, ?, ?, ?, ?)");
    if ($stmt->execute([$user_id, $crop, $month, $yield, $target])) {
        // Erga galmeessee booda gara yields.php tti deebisa
        header("Location: yields.php?success=1");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add New Harvest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light p-5">
    <div class="container">
        <div class="card shadow p-4 mx-auto" style="max-width: 500px;">
            <h3>Record New Harvest</h3>
            <form method="POST">
                <div class="mb-3">
                    <label>Crop Name</label>
                    <input type="text" name="crop" class="form-control" required placeholder="e.g. Tomatoes">
                </div>
                <div class="mb-3">
                    <label>Month</label>
                    <select name="month" class="form-control">
                        <option>Jan</option><option>Feb</option><option>Mar</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label>Yield (kg)</label>
                    <input type="number" name="yield" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Target (kg)</label>
                    <input type="number" name="target" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-primary w-100">Save Record</button>
                <a href="yields.php" class="btn btn-link w-100 mt-2">Cancel</a>
            </form>
        </div>
    </div>
</body>
</html>