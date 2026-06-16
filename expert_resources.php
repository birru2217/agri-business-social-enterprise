<?php
// expert_resources.php
require_once 'includes/db.php';
require_once 'includes/session.php';

checkLogin();

// Get all published expert resources
$resources = $pdo->query("
    SELECT er.*, u.name as expert_name, aep.expertise_area, aep.rating as expert_rating 
    FROM expert_resources er 
    JOIN users u ON er.expert_id = u.id 
    LEFT JOIN agri_expert_profiles aep ON er.expert_id = aep.user_id 
    WHERE er.status = 'published' 
    ORDER BY er.created_at DESC
");
$resources_list = $resources->fetchAll(PDO::FETCH_ASSOC);

// Filter by content type if specified
$content_type_filter = $_GET['type'] ?? '';
$category_filter = $_GET['category'] ?? '';

if ($content_type_filter) {
    $filtered_resources = array_filter($resources_list, function($resource) use ($content_type_filter) {
        return $resource['content_type'] === $content_type_filter;
    });
} else {
    $filtered_resources = $resources_list;
}

if ($category_filter) {
    $filtered_resources = array_filter($filtered_resources, function($resource) use ($category_filter) {
        return stripos($resource['category'], $category_filter) !== false;
    });
}

// Get unique categories for filter
$categories = [];
foreach ($resources_list as $resource) {
    if ($resource['category']) {
        $categories[] = $resource['category'];
    }
}
$categories = array_unique($categories);
sort($categories);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expert Resources - Agri-Biz</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root { --primary-green: #2c7a2c; --light-green: #48bb78; }
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
        .hero-section { background: linear-gradient(135deg, var(--primary-green), var(--light-green)); color: white; padding: 3rem 0; }
        .resource-card { border: none; border-radius: 16px; transition: all 0.3s; height: 100%; overflow: hidden; }
        .resource-card:hover { transform: translateY(-8px); box-shadow: 0 12px 30px rgba(0,0,0,0.15); }
        .content-type-badge { font-size: 0.75rem; padding: 0.5rem 1rem; border-radius: 25px; font-weight: 600; }
        .expert-info { display: flex; align-items: center; gap: 0.75rem; }
        .expert-avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--light-green); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; }
        .filter-section { background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .stats-bar { background: white; border-radius: 12px; padding: 1rem; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .preview-image { width: 100%; height: 200px; object-fit: cover; background: linear-gradient(45deg, #f0f0f0, #e0e0e0); }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold text-success" href="index.php">
                <i class="bi bi-tree-fill me-2"></i>Agri-Biz
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">Dashboard</a>
                <a class="nav-link active" href="expert_resources.php">Expert Resources</a>
                
                <a class="nav-link" href="marketplace.php">Marketplace</a>
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container text-center">
            <h1 class="fw-bold mb-3">Expert Agricultural Resources</h1>
            <p class="lead mb-4">Access high-quality videos, PDFs, and climate guides from agricultural experts</p>
               <div class="row justify-content-center">   
                <div class="col-md-3">
                    <div class="bg-white bg-opacity-10 rounded-3 p-3">
                        <i class="bi bi-play-circle fs-1 mb-2"></i>
                        <h6>Video Tutorials</h6>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="bg-white bg-opacity-10 rounded-3 p-3">
                        <i class="bi bi-file-pdf fs-1 mb-2"></i>
                        <h6>PDF Guides</h6>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="bg-white bg-opacity-10 rounded-3 p-3">
                        <i class="bi bi-cloud-sun fs-1 mb-2"></i>
                        <h6>Climate Content</h6>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="bg-white bg-opacity-10 rounded-3 p-3">
                        <i class="bi bi-journal-text fs-1 mb-2"></i>
                        <h6>Research Papers</h6>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container my-4">
        <!-- Statistics Bar -->
        <div class="stats-bar">
            <div class="row text-center">
                <div class="col-md-3">
                    <h5 class="fw-bold text-success"><?php echo count($resources_list); ?></h5>
                    <small class="text-muted">Total Resources</small>
                </div>
                <div class="col-md-3">
                    <h5 class="fw-bold text-primary"><?php echo count(array_filter($resources_list, fn($r) => $r['content_type'] === 'video')); ?></h5>
                    <small class="text-muted">Videos</small>
                </div>
                <div class="col-md-3">
                    <h5 class="fw-bold text-info"><?php echo count(array_filter($resources_list, fn($r) => $r['content_type'] === 'pdf')); ?></h5>
                    <small class="text-muted">PDFs</small>
                </div>
                <div class="col-md-3">
                    <h5 class="fw-bold text-warning"><?php echo count(array_filter($resources_list, fn($r) => $r['content_type'] === 'climate_guide')); ?></h5>
                    <small class="text-muted">Climate Guides</small>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-section mb-4">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <h6 class="fw-bold mb-3">Filter by Type:</h6>
                    <div class="btn-group" role="group">
                        <a href="expert_resources.php" class="btn <?php echo !$content_type_filter ? 'btn-success' : 'btn-outline-success'; ?>">All</a>
                        <a href="?type=video" class="btn <?php echo $content_type_filter === 'video' ? 'btn-success' : 'btn-outline-success'; ?>">Videos</a>
                        <a href="?type=pdf" class="btn <?php echo $content_type_filter === 'pdf' ? 'btn-success' : 'btn-outline-success'; ?>">PDFs</a>
                        <a href="?type=climate_guide" class="btn <?php echo $content_type_filter === 'climate_guide' ? 'btn-success' : 'btn-outline-success'; ?>">Climate</a>
                        <a href="?type=research_paper" class="btn <?php echo $content_type_filter === 'research_paper' ? 'btn-success' : 'btn-outline-success'; ?>">Research</a>
                    </div>
                </div>
                <div class="col-md-8">
                    <h6 class="fw-bold mb-3">Search by Category:</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="expert_resources.php" class="btn btn-sm <?php echo !$category_filter ? 'btn-primary' : 'btn-outline-primary'; ?>">All Categories</a>
                        <?php foreach ($categories as $category): ?>
                            <a href="?category=<?php echo urlencode($category); ?>" class="btn btn-sm <?php echo $category_filter === $category ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                <?php echo htmlspecialchars($category); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Resources Grid -->
        <div class="row g-4">
            <?php if (empty($filtered_resources)): ?>
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="bi bi-search fs-1 text-muted"></i>
                        <h5 class="text-muted mt-3">No resources found</h5>
                        <p class="text-muted">Try adjusting your filters or check back later for new content.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($filtered_resources as $resource): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card resource-card">
                            <div class="preview-image d-flex align-items-center justify-content-center">
                                <?php if ($resource['content_type'] === 'video'): ?>
                                    <i class="bi bi-play-circle fs-1 text-white"></i>
                                <?php elseif ($resource['content_type'] === 'pdf'): ?>
                                    <i class="bi bi-file-pdf fs-1 text-white"></i>
                                <?php elseif ($resource['content_type'] === 'climate_guide'): ?>
                                    <i class="bi bi-cloud-sun fs-1 text-white"></i>
                                <?php else: ?>
                                    <i class="bi bi-journal-text fs-1 text-white"></i>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="content-type-badge bg-<?php echo $resource['content_type'] === 'video' ? 'danger' : ($resource['content_type'] === 'pdf' ? 'primary' : ($resource['content_type'] === 'climate_guide' ? 'success' : 'warning')); ?> text-white">
                                        <?php echo ucfirst(str_replace('_', ' ', $resource['content_type'])); ?>
                                    </span>
                                    <?php if ($resource['rating'] > 0): ?>
                                        <span class="badge bg-warning">
                                            <i class="bi bi-star-fill"></i> <?php echo number_format($resource['rating'], 1); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <h5 class="card-title fw-bold mb-2"><?php echo htmlspecialchars($resource['title']); ?></h5>
                                <p class="card-text text-muted small mb-3"><?php echo htmlspecialchars(substr($resource['description'], 0, 120)) . '...'; ?></p>
                                
                                <?php if ($resource['category']): ?>
                                    <span class="badge bg-light text-dark mb-2"><?php echo htmlspecialchars($resource['category']); ?></span>
                                <?php endif; ?>
                                
                                <div class="expert-info mt-3 pt-3 border-top">
                                    <div class="expert-avatar">
                                        <?php echo strtoupper(substr($resource['expert_name'], 0, 2)); ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold small"><?php echo htmlspecialchars($resource['expert_name']); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($resource['expertise_area'] ?? 'Agricultural Expert'); ?></div>
                                    </div>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <div class="text-muted small">
                                        <i class="bi bi-eye me-1"></i><?php echo $resource['view_count']; ?>
                                        <i class="bi bi-download ms-2 me-1"></i><?php echo $resource['download_count']; ?>
                                    </div>
                                    <div>
                                        <?php if ($resource['file_url']): ?>
                                            <a href="<?php echo $resource['file_url']; ?>" target="_blank" class="btn btn-sm btn-primary">
                                                <i class="bi bi-box-arrow-up-right me-1"></i>View
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
