<?php
// grain_gallery.php
require_once 'includes/db.php';
require_once 'includes/session.php';

checkLogin();

// Get all published farmer media
$media = $pdo->query("
    SELECT fm.*, u.name as farmer_name, fp.farm_name, p.name as product_name 
    FROM farmer_media fm 
    JOIN users u ON fm.farmer_id = u.id 
    LEFT JOIN farmer_profiles fp ON fm.farmer_id = fp.user_id 
    LEFT JOIN products p ON fm.product_id = p.id 
    WHERE fm.status = 'published' 
    ORDER BY fm.created_at DESC
");
$media_list = $media->fetchAll(PDO::FETCH_ASSOC);

// Filter by media type if specified
$media_type_filter = $_GET['type'] ?? '';
$grain_type_filter = $_GET['grain'] ?? '';
$growth_stage_filter = $_GET['stage'] ?? '';

if ($media_type_filter) {
    $filtered_media = array_filter($media_list, function($media) use ($media_type_filter) {
        return $media['media_type'] === $media_type_filter;
    });
} else {
    $filtered_media = $media_list;
}

if ($grain_type_filter) {
    $filtered_media = array_filter($filtered_media, function($media) use ($grain_type_filter) {
        return stripos($media['grain_type'], $grain_type_filter) !== false;
    });
}

if ($growth_stage_filter) {
    $filtered_media = array_filter($filtered_media, function($media) use ($growth_stage_filter) {
        return $media['growth_stage'] === $growth_stage_filter;
    });
}

// Get unique grain types and growth stages for filters
$grain_types = [];
$growth_stages = [];
foreach ($media_list as $media) {
    if ($media['grain_type']) {
        $grain_types[] = $media['grain_type'];
    }
    if ($media['growth_stage']) {
        $growth_stages[] = $media['growth_stage'];
    }
}
$grain_types = array_unique($grain_types);
sort($grain_types);
$growth_stages = array_unique($growth_stages);
sort($growth_stages);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grain Gallery - Agri-Biz</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root { --primary-green: #228B22; --earth-brown: #8B4513; --golden: #FFD700; }
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
        .hero-section { background: linear-gradient(135deg, var(--primary-green), var(--earth-brown)); color: white; padding: 3rem 0; }
        .media-card { border: none; border-radius: 16px; transition: all 0.3s; overflow: hidden; height: 100%; }
        .media-card:hover { transform: translateY(-8px); box-shadow: 0 12px 30px rgba(0,0,0,0.15); }
        .media-preview { width: 100%; height: 250px; object-fit: cover; background: linear-gradient(45deg, #f0f0f0, #e0e0e0); }
        .media-type-badge { font-size: 0.75rem; padding: 0.5rem 1rem; border-radius: 25px; font-weight: 600; }
        .farmer-info { display: flex; align-items: center; gap: 0.75rem; }
        .farmer-avatar { width: 40px; height: 40px; border-radius: 50%; background: var(--primary-green); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; }
        .filter-section { background: white; border-radius: 12px; padding: 1.5rem; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .stats-bar { background: white; border-radius: 12px; padding: 1rem; margin-bottom: 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .grain-type-badge { font-size: 0.7rem; padding: 0.25rem 0.75rem; border-radius: 15px; }
        .growth-stage-badge { font-size: 0.7rem; padding: 0.25rem 0.75rem; border-radius: 15px; }
        .modal-body video { max-width: 100%; height: auto; }
        .modal-body img { max-width: 100%; height: auto; }
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
                <a class="nav-link active" href="grain_gallery.php">Grain Gallery</a>
                <a class="nav-link" href="marketplace.php">Marketplace</a>
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container text-center">
            <h1 class="fw-bold mb-3">Farmer Grain Gallery</h1>
            <p class="lead mb-4">Discover real grain farming stories through videos and pictures from our farmers</p>
            <div class="row justify-content-center">
                <div class="col-md-3">
                    <div class="bg-white bg-opacity-10 rounded-3 p-3">
                        <i class="bi bi-play-circle fs-1 mb-2"></i>
                        <h6>Farming Videos</h6>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="bg-white bg-opacity-10 rounded-3 p-3">
                        <i class="bi bi-image fs-1 mb-2"></i>
                        <h6>Field Pictures</h6>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="bg-white bg-opacity-10 rounded-3 p-3">
                        <i class="bi bi-flower1 fs-1 mb-2"></i>
                        <h6>Growth Stages</h6>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="bg-white bg-opacity-10 rounded-3 p-3">
                        <i class="bi bi-people fs-1 mb-2"></i>
                        <h6>Farmer Stories</h6>
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
                    <h5 class="fw-bold text-success"><?php echo count($media_list); ?></h5>
                    <small class="text-muted">Total Media</small>
                </div>
                <div class="col-md-3">
                    <h5 class="fw-bold text-danger"><?php echo count(array_filter($media_list, fn($m) => $m['media_type'] === 'video')); ?></h5>
                    <small class="text-muted">Videos</small>
                </div>
                <div class="col-md-3">
                    <h5 class="fw-bold text-info"><?php echo count(array_filter($media_list, fn($m) => $m['media_type'] === 'image')); ?></h5>
                    <small class="text-muted">Images</small>
                </div>
                <div class="col-md-3">
                    <h5 class="fw-bold text-warning"><?php echo count(array_unique(array_column($media_list, 'farmer_id'))); ?></h5>
                    <small class="text-muted">Active Farmers</small>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filter-section mb-4">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <h6 class="fw-bold mb-3">Filter by Type:</h6>
                    <div class="btn-group" role="group">
                        <a href="grain_gallery.php" class="btn <?php echo !$media_type_filter ? 'btn-success' : 'btn-outline-success'; ?>">All</a>
                        <a href="?type=video" class="btn <?php echo $media_type_filter === 'video' ? 'btn-success' : 'btn-outline-success'; ?>">Videos</a>
                        <a href="?type=image" class="btn <?php echo $media_type_filter === 'image' ? 'btn-success' : 'btn-outline-success'; ?>">Images</a>
                    </div>
                </div>
                <div class="col-md-4">
                    <h6 class="fw-bold mb-3">Filter by Grain:</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="grain_gallery.php" class="btn btn-sm <?php echo !$grain_type_filter ? 'btn-warning' : 'btn-outline-warning'; ?>">All Grains</a>
                        <?php foreach ($grain_types as $grain_type): ?>
                            <a href="?grain=<?php echo urlencode($grain_type); ?>" class="btn btn-sm <?php echo $grain_type_filter === $grain_type ? 'btn-warning' : 'btn-outline-warning'; ?>">
                                <?php echo htmlspecialchars($grain_type); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-md-4">
                    <h6 class="fw-bold mb-3">Filter by Stage:</h6>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="grain_gallery.php" class="btn btn-sm <?php echo !$growth_stage_filter ? 'btn-primary' : 'btn-outline-primary'; ?>">All Stages</a>
                        <?php foreach ($growth_stages as $stage): ?>
                            <a href="?stage=<?php echo urlencode($stage); ?>" class="btn btn-sm <?php echo $growth_stage_filter === $stage ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                <?php echo ucfirst($stage); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Media Gallery -->
        <div class="row g-4">
            <?php if (empty($filtered_media)): ?>
                <div class="col-12">
                    <div class="text-center py-5">
                        <i class="bi bi-search fs-1 text-muted"></i>
                        <h5 class="text-muted mt-3">No media found</h5>
                        <p class="text-muted">Try adjusting your filters or check back later for new content.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($filtered_media as $media_item): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card media-card">
                            <div class="media-preview position-relative">
                                <?php if ($media_item['media_type'] === 'video'): ?>
                                    <div class="d-flex align-items-center justify-content-center h-100">
                                        <i class="bi bi-play-circle fs-1 text-white"></i>
                                    </div>
                                <?php else: ?>
                                    <img src="<?php echo $media_item['file_url']; ?>" alt="<?php echo htmlspecialchars($media_item['title']); ?>" class="w-100 h-100 object-fit-cover">
                                <?php endif; ?>
                                
                                <!-- Media Type Badge -->
                                <div class="position-absolute top-0 end-0 m-2">
                                    <span class="media-type-badge bg-<?php echo $media_item['media_type'] === 'video' ? 'danger' : 'info'; ?> text-white">
                                        <?php echo ucfirst($media_item['media_type']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="card-body">
                                <h5 class="card-title fw-bold mb-2"><?php echo htmlspecialchars($media_item['title']); ?></h5>
                                <p class="card-text text-muted small mb-3"><?php echo htmlspecialchars(substr($media_item['description'], 0, 120)) . '...'; ?></p>
                                
                                <!-- Badges -->
                                <div class="mb-3">
                                    <?php if ($media_item['grain_type']): ?>
                                        <span class="grain-type-badge bg-warning text-dark me-1"><?php echo htmlspecialchars($media_item['grain_type']); ?></span>
                                    <?php endif; ?>
                                    
                                    <?php if ($media_item['growth_stage']): ?>
                                        <span class="growth-stage-badge bg-success text-white me-1"><?php echo ucfirst($media_item['growth_stage']); ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Farmer Info -->
                                <div class="farmer-info mb-3 pt-3 border-top">
                                    <div class="farmer-avatar">
                                        <?php echo strtoupper(substr($media_item['farmer_name'], 0, 2)); ?>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold small"><?php echo htmlspecialchars($media_item['farmer_name']); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($media_item['farm_name'] ?? 'Local Farmer'); ?></div>
                                    </div>
                                </div>
                                
                                <!-- Product Link -->
                                <?php if ($media_item['product_name']): ?>
                                    <div class="mb-2">
                                        <small class="text-muted">
                                            <i class="bi bi-basket me-1"></i>
                                            <a href="marketplace.php" class="text-decoration-none"><?php echo htmlspecialchars($media_item['product_name']); ?></a>
                                        </small>
                                    </div>
                                <?php endif; ?>
                                
                                <!-- Engagement Stats -->
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="text-muted small">
                                        <i class="bi bi-eye me-1"></i><?php echo $media_item['view_count']; ?>
                                        <i class="bi bi-heart ms-2 me-1 text-danger"></i><?php echo $media_item['like_count']; ?>
                                    </div>
                                    <div>
                                        <button class="btn btn-sm btn-primary" onclick="viewMedia('<?php echo $media_item['id']; ?>', '<?php echo $media_item['media_type']; ?>', '<?php echo $media_item['file_url']; ?>', '<?php echo htmlspecialchars($media_item['title']); ?>')">
                                            <i class="bi bi-box-arrow-up-right me-1"></i>View
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Media Modal -->
    <div class="modal fade" id="mediaModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center" id="modalBody">
                    <!-- Media content will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function viewMedia(mediaId, mediaType, fileUrl, title) {
            const modal = new bootstrap.Modal(document.getElementById('mediaModal'));
            const modalTitle = document.getElementById('modalTitle');
            const modalBody = document.getElementById('modalBody');
            
            modalTitle.textContent = title;
            
            if (mediaType === 'video') {
                modalBody.innerHTML = `<video controls autoplay><source src="${fileUrl}" type="video/mp4">Your browser does not support the video tag.</video>`;
            } else {
                modalBody.innerHTML = `<img src="${fileUrl}" alt="${title}" class="img-fluid">`;
            }
            
            // Increment view count (in production, this would be an AJAX call)
            modal.show();
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
