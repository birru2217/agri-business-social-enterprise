<?php
// upload_resource.php
require_once 'includes/db.php';
require_once 'includes/session.php';

// Ogeessa qofa akka ta'e mirkaneessuuf
checkLogin();
checkRole(['agri_expert']);

$user_id = $_SESSION['user_id'];
$message = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_resource'])) {
    $title = htmlspecialchars($_POST['title']);
    $description = htmlspecialchars($_POST['description']);
    $content_type = $_POST['content_type'];
    $category = htmlspecialchars($_POST['category']);
    $tags = htmlspecialchars($_POST['tags']);
    
    // File handling
    $file_url = '';
    $file_size = 0;
    $duration_minutes = 0;
    
    if (isset($_FILES['resource_file']) && $_FILES['resource_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/expert_resources/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_ext = strtolower(pathinfo($_FILES['resource_file']['name'], PATHINFO_EXTENSION));
        $file_name = "agri_" . time() . '_' . uniqid() . '.' . $file_ext;
        $target_path = $upload_dir . $file_name;
        
        // Gosa faayilaa hayyamaman
        $allowed_ext = ['pdf', 'mp4', 'avi', 'mov', 'jpg', 'png', 'docx'];
        
        if (in_array($file_ext, $allowed_ext)) {
            if (move_uploaded_file($_FILES['resource_file']['tmp_name'], $target_path)) {
                $file_url = $target_path;
                
                // MB-tti jijjiiruuf (1024 * 1024)
                $file_size = round($_FILES['resource_file']['size'] / 1048576, 2); 
                
                // Video yoo ta'e duration fudhachuu
                if ($content_type === 'video') {
                    $duration_minutes = (int)($_POST['duration_minutes'] ?? 0);
                }

                try {
                    // SQL Query akka expert_resources.php irratti hundaa'etti
                    $sql = "INSERT INTO expert_resources 
                            (expert_id, title, description, content_type, file_url, file_size, duration_minutes, category, tags, status, view_count, download_count) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'published', 0, 0)";
                    
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([
                        $user_id, $title, $description, $content_type, 
                        $file_url, $file_size, $duration_minutes, 
                        $category, $tags
                    ]);
                    
                    $message = "Resource successfully published! Qonnaan bultoonni amma fuula Expert Resources irratti arguun ni danda'u.";
                } catch (PDOException $e) {
                    $error = "Database Error: " . $e->getMessage();
                    // Yoo database irratti kufaatii uumame faayila fe'ame san haquu
                    if(file_exists($file_url)) unlink($file_url);
                }
            } else {
                $error = "Faayila gara serveritti fe'uun hin danda'amne. Maaloo folder 'uploads' uumamuun isaa mirkaneessi.";
            }
        } else {
            $error = "Gosti faayilaa kun hin hayyamamu. PDF, MP4, ykn DOCX qofa fayyadami.";
        }
    } else {
        $error = "Maaloo faayila barumsaa filadhu.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload New Resource - Agri-Biz</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root { --primary-green: #2c7a2c; --light-green: #48bb78; }
        body { background-color: #f0f2f5; font-family: 'Inter', sans-serif; }
        .hero-mini { background: linear-gradient(135deg, var(--primary-green), var(--light-green)); color: white; padding: 2rem 0; border-radius: 0 0 30px 30px; margin-bottom: 2rem; }
        .upload-card { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); }
        .form-label { font-weight: 600; color: #4a5568; }
        .drop-zone { border: 2px dashed var(--light-green); border-radius: 15px; padding: 30px; text-align: center; background: #f9fff9; transition: 0.3s; cursor: pointer; }
        .drop-zone:hover { background: #ecfdf5; border-color: var(--primary-green); }
    </style>
</head>
<body>

<div class="hero-mini text-center shadow">
    <div class="container">
        <h2 class="fw-bold"><i class="bi bi-file-earmark-arrow-up me-2"></i>Publish New Resource</h2>
        <p class="mb-0 text-white-50">Knowledge sharing is the key to agricultural growth.</p>
    </div>
</div>

<div class="container mb-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card upload-card p-4">
                
                <?php if($message): ?>
                    <div class="alert alert-success alert-dismissible fade show rounded-pill px-4" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i> <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show rounded-pill px-4" role="alert">
                        <i class="bi bi-exclamation-octagon-fill me-2"></i> <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-12 mb-4">
                            <label class="form-label">Resource Title</label>
                            <input type="text" name="title" class="form-control form-control-lg border-2" placeholder="e.g., Guide to Sustainable Coffee Farming" required>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <label class="form-label">Content Type</label>
                            <select name="content_type" id="content_type" class="form-select border-2" required onchange="toggleDuration()">
                                <option value="pdf">PDF Document</option>
                                <option value="video">Video Tutorial</option>
                                <option value="climate_guide">Climate Guide</option>
                                <option value="research_paper">Research Paper</option>
                            </select>
                        </div>

                        <div class="col-md-6 mb-4">
                            <label class="form-label">Category</label>
                            <input type="text" name="category" class="form-control border-2" placeholder="e.g., Crop Management, Soil Science">
                        </div>

                        <div class="col-md-12 mb-4" id="video_duration" style="display:none;">
                            <label class="form-label text-danger fw-bold">Video Duration (in minutes)</label>
                            <input type="number" name="duration_minutes" class="form-control border-2 border-danger" placeholder="How long is the video?">
                        </div>

                        <div class="col-md-12 mb-4">
                            <label class="form-label">Detailed Description</label>
                            <textarea name="description" class="form-control border-2" rows="4" placeholder="Briefly explain what this resource covers..." required></textarea>
                        </div>

                        <div class="col-md-12 mb-4">
                            <label class="form-label">Tags (Optional)</label>
                            <input type="text" name="tags" class="form-control border-2" placeholder="e.g., organic, coffee, ethiopia">
                            <small class="text-muted">Separate tags with commas</small>
                        </div>

                        <div class="col-md-12 mb-4">
                            <label class="form-label">Upload File (Max 100MB)</label>
                            <div class="drop-zone" onclick="document.getElementById('resource_file').click()">
                                <i class="bi bi-cloud-upload fs-1 text-success"></i>
                                <p class="mb-0 fw-bold mt-2">Click to browse or drag file here</p>
                                <small class="text-muted">MP4, PDF, or JPG/PNG</small>
                                <input type="file" name="resource_file" id="resource_file" class="d-none" required>
                                <div id="file-name" class="mt-2 text-primary fw-bold"></div>
                            </div>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" name="upload_resource" class="btn btn-success btn-lg fw-bold rounded-pill py-3 shadow">
                            <i class="bi bi-rocket-takeoff me-2"></i>Publish to Marketplace
                        </button>
                        <a href="expert_dashboard.php" class="btn btn-link text-decoration-none text-muted mt-2">Cancel and Go Back</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleDuration() {
        const type = document.getElementById('content_type').value;
        const durationDiv = document.getElementById('video_duration');
        durationDiv.style.display = (type === 'video') ? 'block' : 'none';
    }

    document.getElementById('resource_file').onchange = function() {
        document.getElementById('file-name').innerText = "Selected: " + this.files[0].name;
    };
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>