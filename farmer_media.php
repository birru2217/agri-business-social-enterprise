<?php
// farmer_media.php
require_once 'includes/db.php';
require_once 'includes/session.php';

checkLogin();
checkRole(['farmer']);

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Handle media upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_media'])) {
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $media_type = $_POST['media_type'] ?? '';
    $grain_type = $_POST['grain_type'] ?? '';
    $growth_stage = $_POST['growth_stage'] ?? '';
    $location = $_POST['location'] ?? '';
    $planting_date = $_POST['planting_date'] ?? '';
    $harvest_date = $_POST['harvest_date'] ?? '';
    $tags = $_POST['tags'] ?? '';
    $product_id = $_POST['product_id'] ?? '';
    
    // File upload handling
    $file_url = '';
    $file_size = 0;
    $thumbnail_url = '';
    $duration_seconds = 0;
    
    if (isset($_FILES['media_file']) && $_FILES['media_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/farmer_media/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES['media_file']['name'], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'avi', 'mov', 'wmv'];
        
        if (in_array($file_extension, $allowed_extensions)) {
            $file_name = time() . '_' . basename($_FILES['media_file']['name']);
            $target_path = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['media_file']['tmp_name'], $target_path)) {
                $file_url = $target_path;
                $file_size = $_FILES['media_file']['size'] / 1024 / 1024; // Convert to MB
                
                // For videos, get duration (simplified - in production you'd use ffmpeg)
                if ($media_type === 'video') {
                    $duration_seconds = (int)($_POST['duration_seconds'] ?? 0);
                }
                
                // Create thumbnail for videos (simplified)
                if ($media_type === 'video') {
                    $thumbnail_url = 'assets/images/video_thumb.jpg'; // Default thumbnail
                }
            }
        }
    }
    
    // Insert into database
    if (!empty($title) && !empty($media_type) && !empty($file_url)) {
        $stmt = $pdo->prepare("INSERT INTO farmer_media (farmer_id, product_id, title, description, media_type, file_url, file_size, thumbnail_url, duration_seconds, grain_type, growth_stage, location, planting_date, harvest_date, tags, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'published')");
        $stmt->execute([$user_id, $product_id ?: null, $title, $description, $media_type, $file_url, $file_size, $thumbnail_url, $duration_seconds, $grain_type, $growth_stage, $location, $planting_date ?: null, $harvest_date ?: null, $tags]);
        
        $success_message = "Media uploaded successfully!";
    }
}

// Get farmer's media
$media = $pdo->prepare("SELECT fm.*, p.name as product_name FROM farmer_media fm LEFT JOIN products p ON fm.product_id = p.id WHERE fm.farmer_id = ? ORDER BY fm.created_at DESC");
$media->execute([$user_id]);
$media_list = $media->fetchAll(PDO::FETCH_ASSOC);

// Get farmer's products for dropdown
$products = $pdo->prepare("SELECT * FROM products WHERE farmer_id = ?");
$products->execute([$user_id]);
$products_list = $products->fetchAll(PDO::FETCH_ASSOC);

// Get statistics
$total_media = count($media_list);
$total_views = array_sum(array_column($media_list, 'view_count'));
$total_likes = array_sum(array_column($media_list, 'like_count'));
$video_count = count(array_filter($media_list, fn($m) => $m['media_type'] === 'video'));
$image_count = count(array_filter($media_list, fn($m) => $m['media_type'] === 'image'));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmer Media - Agri-Biz</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root { --farmer-primary: #8B4513; --farmer-secondary: #D2691E; --farmer-green: #228B22; }
        body { background-color: #f8f9fa; font-family: 'Inter', sans-serif; }
        .farmer-header { background: linear-gradient(135deg, var(--farmer-primary), var(--farmer-secondary)); color: white; padding: 2rem 0; }
        .stat-card { border: none; border-radius: 16px; transition: transform 0.2s; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .stat-card:hover { transform: translateY(-5px); }
        .media-card { border: none; border-radius: 12px; transition: all 0.3s; overflow: hidden; }
        .media-card:hover { box-shadow: 0 8px 25px rgba(0,0,0,0.15); transform: translateY(-5px); }
        .upload-area { border: 2px dashed #cbd5e0; border-radius: 12px; padding: 2rem; text-align: center; transition: all 0.3s; }
        .upload-area:hover { border-color: var(--farmer-primary); background-color: #fff8e1; }
        .media-preview { width: 100%; height: 200px; object-fit: cover; background: linear-gradient(45deg, #f0f0f0, #e0e0e0); }
        .media-type-badge { font-size: 0.75rem; padding: 0.5rem 1rem; border-radius: 25px; font-weight: 600; }
        .growth-stage-badge { font-size: 0.7rem; padding: 0.25rem 0.75rem; border-radius: 15px; }
    </style>
</head>
<body>
    <!-- Farmer Header -->
    <div class="farmer-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="fw-bold mb-2">Farmer Media Gallery</h1>
                    <p class="mb-0 opacity-90">Showcase your grain videos and pictures • Share your farming journey</p>
                </div>
                <div class="col-md-4 text-md-end">
                    <div class="d-flex gap-2 justify-content-md-end">
                        <a href="dashboard.php" class="btn btn-outline-light btn-sm">
                            <i class="bi bi-house me-2"></i>Dashboard
                        </a>
                        <a href="logout.php" class="btn btn-light btn-sm">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-4">
        <!-- Success Message -->
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle me-2"></i><?php echo $success_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card stat-card p-4 bg-white h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="bg-success bg-opacity-10 p-3 rounded-4">
                            <i class="bi bi-collection text-success fs-4"></i>
                        </div>
                        <span class="badge bg-success-subtle text-success">Total</span>
                    </div>
                    <h6 class="text-muted mb-1">Media Items</h6>
                    <h2 class="fw-bold mb-0"><?php echo $total_media; ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card p-4 bg-white h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-4">
                            <i class="bi bi-play-circle text-primary fs-4"></i>
                        </div>
                        <span class="badge bg-primary-subtle text-primary">Videos</span>
                    </div>
                    <h6 class="text-muted mb-1">Videos</h6>
                    <h2 class="fw-bold mb-0"><?php echo $video_count; ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card p-4 bg-white h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="bg-info bg-opacity-10 p-3 rounded-4">
                            <i class="bi bi-image text-info fs-4"></i>
                        </div>
                        <span class="badge bg-info-subtle text-info">Images</span>
                    </div>
                    <h6 class="text-muted mb-1">Pictures</h6>
                    <h2 class="fw-bold mb-0"><?php echo $image_count; ?></h2>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stat-card p-4 bg-white h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="bg-warning bg-opacity-10 p-3 rounded-4">
                            <i class="bi bi-heart text-warning fs-4"></i>
                        </div>
                        <span class="badge bg-warning-subtle text-warning">Engagement</span>
                    </div>
                    <h6 class="text-muted mb-1">Total Likes</h6>
                    <h2 class="fw-bold mb-0"><?php echo $total_likes; ?></h2>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- Upload Section -->
            <div class="col-lg-5">
                <div class="card border-0 rounded-4 shadow-sm p-4">
                    <h5 class="fw-bold mb-4">
                        <i class="bi bi-cloud-upload me-2 text-success"></i>Upload Grain Media
                    </h5>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Media Title</label>
                            <input type="text" name="title" class="form-control" required placeholder="e.g., My Wheat Harvest 2024">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Media Type</label>
                            <select name="media_type" class="form-select" required>
                                <option value="">Select Type</option>
                                <option value="video">Video</option>
                                <option value="image">Image/Picture</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Link to Product (Optional)</label>
                            <select name="product_id" class="form-select">
                                <option value="">No Product Link</option>
                                <?php foreach ($products_list as $product): ?>
                                    <option value="<?php echo $product['id']; ?>"><?php echo htmlspecialchars($product['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Grain Type</label>
                                    <input type="text" name="grain_type" class="form-control" placeholder="e.g., Wheat, Rice, Corn">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Growth Stage</label>
                                    <select name="growth_stage" class="form-select">
                                        <option value="">Select Stage</option>
                                        <option value="planting">Planting</option>
                                        <option value="growing">Growing</option>
                                        <option value="flowering">Flowering</option>
                                        <option value="harvesting">Harvesting</option>
                                        <option value="post-harvest">Post-Harvest</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Location</label>
                            <input type="text" name="location" class="form-control" placeholder="Farm location or field name">
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Planting Date</label>
                                    <input type="date" name="planting_date" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Harvest Date</label>
                                    <input type="date" name="harvest_date" class="form-control">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Describe your grain, farming methods, or story..."></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Tags</label>
                            <input type="text" name="tags" class="form-control" placeholder="e.g., organic, sustainable, wheat, irrigation">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Upload File</label>
                            <div class="upload-area">
                                <input type="file" name="media_file" class="form-control" accept="image/*,video/*" required>
                                <small class="text-muted mt-2 d-block">Supported: Images (JPG, PNG, GIF) and Videos (MP4, AVI, MOV)</small>
                            </div>
                        </div>
                        
                        <div class="mb-3" id="duration_field" style="display: none;">
                            <label class="form-label fw-semibold">Duration (seconds)</label>
                            <input type="number" name="duration_seconds" class="form-control" placeholder="Video duration in seconds">
                        </div>
                        
                        <button type="submit" name="upload_media" class="btn btn-success w-100 rounded-pill">
                            <i class="bi bi-upload me-2"></i>Upload Media
                        </button>
                    </form>
                </div>
            </div>

            <!-- Media Gallery -->
            <div class="col-lg-7">
                <div class="card border-0 rounded-4 shadow-sm p-4">
                    <h5 class="fw-bold mb-4">
                        <i class="bi bi-images me-2 text-primary"></i>Your Grain Gallery
                    </h5>
                    <div class="row g-3">
                        <?php if (empty($media_list)): ?>
                            <div class="col-12">
                                <div class="text-center py-5">
                                    <i class="bi bi-camera-video fs-1 text-muted"></i>
                                    <p class="text-muted mt-3">No media uploaded yet. Start sharing your grain journey!</p>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php foreach ($media_list as $media_item): ?>
                                <div class="col-md-6">
                                    <div class="card media-card">
                                        <div class="media-preview d-flex align-items-center justify-content-center">
                                            <?php if ($media_item['media_type'] === 'video'): ?>
                                                <i class="bi bi-play-circle fs-1 text-white"></i>
                                            <?php else: ?>
                                                <img src="<?php echo $media_item['file_url']; ?>" alt="<?php echo htmlspecialchars($media_item['title']); ?>" class="w-100 h-100 object-fit-cover">
                                            <?php endif; ?>
                                        </div>
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="card-title fw-bold mb-0"><?php echo htmlspecialchars($media_item['title']); ?></h6>
                                                <span class="media-type-badge bg-<?php echo $media_item['media_type'] === 'video' ? 'danger' : 'info'; ?> text-white">
                                                    <?php echo ucfirst($media_item['media_type']); ?>
                                                </span>
                                            </div>
                                            
                                            <?php if ($media_item['grain_type']): ?>
                                                <span class="badge bg-warning text-dark mb-2"><?php echo htmlspecialchars($media_item['grain_type']); ?></span>
                                            <?php endif; ?>
                                            
                                            <?php if ($media_item['growth_stage']): ?>
                                                <span class="growth-stage-badge bg-success text-white mb-2"><?php echo ucfirst($media_item['growth_stage']); ?></span>
                                            <?php endif; ?>
                                            
                                            <p class="card-text text-muted small mb-2"><?php echo htmlspecialchars(substr($media_item['description'], 0, 80)) . '...'; ?></p>
                                            
                                            <div class="d-flex justify-content-between align-items-center">
                                                <div class="text-muted small">
                                                    <i class="bi bi-eye me-1"></i><?php echo $media_item['view_count']; ?>
                                                    <i class="bi bi-heart ms-2 me-1 text-danger"></i><?php echo $media_item['like_count']; ?>
                                                </div>
                                                <div>
                                                    <a href="<?php echo $media_item['file_url']; ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-box-arrow-up-right"></i>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Show/hide duration field based on media type
        document.querySelector('select[name="media_type"]').addEventListener('change', function() {
            const durationField = document.getElementById('duration_field');
            if (this.value === 'video') {
                durationField.style.display = 'block';
            } else {
                durationField.style.display = 'none';
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
