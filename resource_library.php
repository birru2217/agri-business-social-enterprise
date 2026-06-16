<?php
// resource_library.php
require_once 'includes/db.php';
require_once 'includes/session.php';

checkLogin();
checkRole(['farmer', 'admin']);

// Fetch resources
try {
    $stmt = $pdo->query("SELECT * FROM resources ORDER BY created_at DESC");
    $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $resources = [];
}

// Mock resources if empty
if (empty($resources)) {
    $resources = [
        ['title' => 'Climate-Smart Farming Guide', 'type' => 'pdf', 'content_url' => '#', 'description' => 'A comprehensive guide on adapting your farm to changing climate conditions.'],
        ['title' => 'Organic Pest Control Methods', 'type' => 'video', 'content_url' => '#', 'description' => 'Learn natural ways to protect your crops without harmful chemicals.'],
        ['title' => 'Soil Health Management', 'type' => 'pdf', 'content_url' => '#', 'description' => 'Best practices for maintaining soil fertility and yield.'],
        ['title' => 'Efficient Water Irrigation', 'type' => 'link', 'content_url' => '#', 'description' => 'Case studies on low-cost drip irrigation systems.']
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resource Library - Agri-Business</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .resource-card { border-radius: 15px; border: none; transition: transform 0.2s; background: white; }
        .resource-card:hover { transform: translateY(-5px); }
        .icon-box { width: 50px; height: 50px; display: flex; align-items: center; justify-content: center; border-radius: 12px; }
        .bg-pdf { background-color: #ffebee; color: #f44336; }
        .bg-video { background-color: #e3f2fd; color: #2196f3; }
        .bg-link { background-color: #f1f8e9; color: #4caf50; }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-success shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php">Farmer Resources</a>
            <div class="ms-auto">
                <a href="index.php" class="btn btn-outline-light btn-sm me-2">Home</a>
                <a href="dashboard.php" class="btn btn-outline-light btn-sm">Back to Dashboard</a>
            </div>
        </div>
    </nav>

    <div class="container my-5">
        <div class="row mb-5 text-center">
            <div class="col-md-8 offset-md-2">
                <h1 class="fw-bold text-success">Learn & Grow</h1>
                <p class="lead text-muted">Access best practices, guides, and tools to improve your farm productivity and sustainability.</p>
            </div>
        </div>

        <div class="row g-4">
            <?php foreach ($resources as $resource): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card resource-card shadow-sm p-4 h-100">
                        <div class="d-flex align-items-center mb-3">
                            <div class="icon-box me-3 <?php echo 'bg-' . $resource['type']; ?>">
                                <?php if ($resource['type'] === 'pdf'): ?>
                                    <i class="bi bi-file-earmark-pdf fs-4"></i>
                                <?php elseif ($resource['type'] === 'video'): ?>
                                    <i class="bi bi-play-circle fs-4"></i>
                                <?php else: ?>
                                    <i class="bi bi-link-45deg fs-4"></i>
                                <?php endif; ?>
                            </div>
                            <h5 class="mb-0 fw-bold"><?php echo htmlspecialchars($resource['title']); ?></h5>
                        </div>
                        <p class="text-muted small mb-4"><?php echo htmlspecialchars($resource['description']); ?></p>
                        <div class="mt-auto">
                            <a href="<?php echo htmlspecialchars($resource['content_url']); ?>" class="btn btn-outline-success w-100 rounded-pill">
                                <?php echo ($resource['type'] === 'video') ? 'Watch Now' : 'Download / View'; ?>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
