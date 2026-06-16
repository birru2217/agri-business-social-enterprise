<?php
// expert_dashboard.php
require_once 'includes/db.php';
require_once 'includes/session.php';

checkLogin();
checkRole(['agri_expert']);

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_role = $_SESSION['role'] ?? 'agri_expert';
$error = '';
$success = '';

// ── Handle resource upload ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_resource'])) {
    $title        = trim($_POST['title'] ?? '');
    $description  = trim($_POST['description'] ?? '');
    $content_type = $_POST['content_type'] ?? '';
    $category     = trim($_POST['category'] ?? '');
    $tags         = trim($_POST['tags'] ?? '');
    $file_url     = '';
    $file_size    = 0;
    $duration_minutes = (int)($_POST['duration_minutes'] ?? 0);

    if (isset($_FILES['resource_file']) && $_FILES['resource_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/expert_resources/';
        if (!file_exists($upload_dir)) mkdir($upload_dir, 0777, true);

        $allowed_ext = ['pdf', 'mp4', 'avi', 'mov'];
        $ext = strtolower(pathinfo($_FILES['resource_file']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed_ext)) {
            $error = "Invalid file type. Allowed: PDF, MP4, AVI, MOV.";
        } elseif ($_FILES['resource_file']['size'] > 50 * 1024 * 1024) {
            $error = "File too large. Maximum size is 50MB.";
        } else {
            $file_name = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($_FILES['resource_file']['name']));
            $target_path = $upload_dir . $file_name;
            if (move_uploaded_file($_FILES['resource_file']['tmp_name'], $target_path)) {
                $file_url  = $target_path;
                $file_size = round($_FILES['resource_file']['size'] / 1024 / 1024, 2);
            } else {
                $error = "Failed to move uploaded file.";
            }
        }
    }

    if (empty($error)) {
        if (empty($title) || empty($content_type)) {
            $error = "Title and content type are required.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO expert_resources
                    (expert_id, title, description, content_type, file_url, file_size, duration_minutes, category, tags, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'published')");
                $stmt->execute([$user_id, $title, $description, $content_type, $file_url, $file_size, $duration_minutes, $category, $tags]);
                $success = "Resource published successfully!";
            } catch (PDOException $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }
    }
}

// ── Handle resource delete ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_resource'])) {
    $res_id = (int)$_POST['resource_id'];
    try {
        // Only delete own resources
        $stmt = $pdo->prepare("DELETE FROM expert_resources WHERE id = ? AND expert_id = ?");
        $stmt->execute([$res_id, $user_id]);
        $success = "Resource deleted.";
    } catch (PDOException $e) {
        $error = "Delete failed: " . $e->getMessage();
    }
}

// ── Handle expert profile update ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $expertise_area    = trim($_POST['expertise_area'] ?? '');
    $experiences_years  = (int)($_POST['experience_years'] ?? 0);
    $certifications    = trim($_POST['certifications'] ?? '');
    $bio               = trim($_POST['bio'] ?? '');
    $rating = floatval($_POST['rating'] ?? 0);

    try {
        // Upsert expert profile
        $stmt = $pdo->prepare("SELECT id FROM agri_expert_profiles WHERE user_id = ?");
        $stmt->execute([$user_id]);
        if ($stmt->fetch()) {
            $stmt = $pdo->prepare("UPDATE agri_expert_profiles
                SET expertise_area=?, experience_years=?, certifications=?, bio=?, rating=?
                WHERE user_id=?");
            $stmt->execute([$expertise_area, $experience_years, $certifications, $bio, $rating, $user_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO agri_expert_profiles
                (user_id, expertise_area, experience_years, certifications, bio, consultation_rate)
                VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $expertise_area, $experience_years, $certifications, $bio, $rating]);
        }
        // Also update bio in users table
        $pdo->prepare("UPDATE users SET bio=? WHERE id=?")->execute([$bio, $user_id]);
        $success = "Profile updated successfully!";
    } catch (PDOException $e) {
        $error = "Profile update failed: " . $e->getMessage();
    }
}

// ── Fetch data ───────────────────────────────────────────────────────────────
try {
    $resources_stmt = $pdo->prepare("SELECT * FROM expert_resources WHERE expert_id = ? ORDER BY created_at DESC");
    $resources_stmt->execute([$user_id]);
    $resources_list = $resources_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $resources_list = [];
    $error = "Could not load resources: " . $e->getMessage();
}

$total_resources       = count($resources_list);
$total_downloads_count = array_sum(array_column($resources_list, 'download_count'));
$total_views_count     = array_sum(array_column($resources_list, 'view_count'));

// Expert profile & User photo details fetched in one clean query
try {
    $ep_stmt = $pdo->prepare("SELECT ep.*, u.bio as user_bio, u.profile_photo FROM users u 
        LEFT JOIN agri_expert_profiles ep ON u.id = ep.user_id WHERE u.id = ?");
    $ep_stmt->execute([$user_id]);
    $expert_profile = $ep_stmt->fetch(PDO::FETCH_ASSOC);
    $profile_photo = $expert_profile['profile_photo'] ?? null;
} catch (PDOException $e) {
    $expert_profile = null;
    $profile_photo = null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expert Dashboard - Agri-Biz</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root { --green: #2c7a2c; --green-light: #48bb78; }
        body { background: #f4f7f6; font-family: 'Inter', sans-serif; }

        /* Sidebar */
        .sidebar { min-height: 100vh; background: linear-gradient(135deg, #1a3a1a, #2c5f2e); color: #fff; padding: 20px; }
        .sidebar a { color: #b2dfb2; text-decoration: none; display: block; padding: 10px 14px; border-radius: 8px; margin-bottom: 4px; transition: all .25s; }
        .sidebar a:hover, .sidebar a.active { background: rgba(72,187,120,.2); color: #fff; transform: translateX(4px); }
        .sidebar a i { width: 20px; }
        .sidebar-brand { font-size: 1.4rem; font-weight: 800; color: #fff; text-align: center; margin-bottom: 24px; }
        
        /* Modern Profile Box Fix */
        .profile-box { background: rgba(255,255,255,.1); border-radius: 12px; padding: 16px; text-align: center; margin-bottom: 20px; }
        .avatar { width: 68px; height: 68px; border-radius: 50%; background: linear-gradient(135deg,var(--green),var(--green-light));
                  display: flex; align-items: center; justify-content: center; font-size: 20px; font-weight: 700;
                  color: #fff; margin: 0 auto 10px; background-size: cover; background-position: center; border: 2px solid rgba(255,255,255,0.2); }
        .profile-name { font-weight: 600; font-size: 1rem; color: #fff; margin-bottom: 2px; }
        .profile-role { font-size: 0.8rem; color: #cbd5e0; margin-bottom: 6px; text-transform: capitalize; }
        .status-dot { width: 8px; height: 8px; background-color: #48bb78; border-radius: 50%; display: inline-block; margin-right: 4px; }
        .profile-status { font-size: 0.75rem; color: #a0aec0; display: flex; align-items: center; justify-content: center; }

        /* Cards */
        .stat-card { border: none; border-radius: 16px; box-shadow: 0 4px 12px rgba(0,0,0,.06); transition: transform .2s; }
        .stat-card:hover { transform: translateY(-4px); }
        .resource-card { border: none; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,.06); transition: all .3s; }
        .resource-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,.12); }
        .upload-zone { border: 2px dashed #cbd5e0; border-radius: 12px; padding: 20px; text-align: center; transition: all .3s; }
        .upload-zone:hover { border-color: var(--green); background: #f0fff4; }
        .badge-type { font-size: .7rem; padding: .3rem .7rem; border-radius: 20px; }
        .nav-tabs .nav-link { color: #555; border-radius: 8px 8px 0 0; }
        .nav-tabs .nav-link.active { color: var(--green); font-weight: 600; border-bottom-color: #fff; }
        .form-control:focus, .form-select:focus { border-color: var(--green); box-shadow: 0 0 0 3px rgba(44,122,44,.1); }
        .btn-green { background: linear-gradient(135deg,var(--green),var(--green-light)); border: none; color: #fff; }
        .btn-green:hover { opacity: .9; color: #fff; transform: translateY(-1px); }
    </style>
</head>
<body>
<div class="container-fluid">
<div class="row">

    <!-- ── Sidebar ─────────────────────────────────────────────────── -->
    <nav class="col-md-3 col-lg-2 sidebar d-flex flex-column">
        <div class="sidebar-brand">🌾 Agri-Biz</div>
        
        <!-- Profile Box Area Fixed & Structured -->
        <div class="profile-box">
            <div class="avatar" style="<?php echo $profile_photo ? "background-image: url('" . htmlspecialchars($profile_photo) . "');" : ''; ?>">
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

        <a href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>
        <a href="expert_dashboard.php" class="active"><i class="bi bi-mortarboard me-2"></i>Expert Hub</a>
        <a href="expert_resources.php"><i class="bi bi-collection me-2"></i>All Resources</a>
        <a href="resource_library.php"><i class="bi bi-book me-2"></i>Resource Library</a>
        <a href="profile.php"><i class="bi bi-person me-2"></i>My Profile</a>
        <hr style="border-color:rgba(255,255,255,.2);">
        <a href="logout.php" style="color:#ff8080;"><i class="bi bi-box-arrow-right me-2"></i>Logout</a>
    </nav>

    <!-- ── Main Content ────────────────────────────────────────────── -->
    <main class="col-md-9 col-lg-10 p-4">

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-1">Expert Dashboard</h3>
                <p class="text-muted mb-0">Manage your resources and profile</p>
            </div>
            <span class="badge bg-success px-3 py-2"><?php echo date('F d, Y'); ?></span>
        </div>

        <!-- Alerts -->
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle me-2"></i><?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Stats Row -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card stat-card p-4 bg-white">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-success bg-opacity-10 p-3 rounded-4">
                            <i class="bi bi-collection text-success fs-4"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Resources Published</div>
                            <div class="fw-bold fs-3"><?php echo $total_resources; ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card p-4 bg-white">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary bg-opacity-10 p-3 rounded-4">
                            <i class="bi bi-eye text-primary fs-4"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Total Views</div>
                            <div class="fw-bold fs-3"><?php echo number_format($total_views_count); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card p-4 bg-white">
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-info bg-opacity-10 p-3 rounded-4">
                            <i class="bi bi-download text-info fs-4"></i>
                        </div>
                        <div>
                            <div class="text-muted small">Total Downloads</div>
                            <div class="fw-bold fs-3"><?php echo number_format($total_downloads_count); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs mb-4" id="expertTabs">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#tab-upload">
                    <i class="bi bi-cloud-upload me-1"></i>Upload Resource
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#tab-resources">
                    <i class="bi bi-collection me-1"></i>My Resources
                    <span class="badge bg-success ms-1"><?php echo $total_resources; ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#tab-profile">
                    <i class="bi bi-person-badge me-1"></i>Expert Profile
                </a>
            </li>
        </ul>

        <div class="tab-content">

            <!-- ── Tab: Upload ──────────────────────────────────────── -->
            <div class="tab-pane fade show active" id="tab-upload">
                <div class="card border-0 rounded-4 shadow-sm p-4" style="max-width:680px;">
                    <h5 class="fw-bold mb-4"><i class="bi bi-cloud-upload me-2 text-success"></i>Publish New Resource</h5>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Resource Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" required placeholder="e.g., Climate-Smart Farming Techniques">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Content Type <span class="text-danger">*</span></label>
                                <select name="content_type" class="form-select" required id="contentTypeSelect">
                                    <option value="">Select type…</option>
                                    <option value="video">🎬 Video Tutorial</option>
                                    <option value="pdf">📄 PDF Guide</option>
                                    <option value="climate_guide">🌦️ Climate Guide</option>
                                    <option value="research_paper">🔬 Research Paper</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Category</label>
                                <input type="text" name="category" class="form-control" placeholder="e.g., Soil Management">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Description</label>
                                <textarea name="description" class="form-control" rows="3" placeholder="Describe what farmers will learn…"></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Tags</label>
                                <input type="text" name="tags" class="form-control" placeholder="organic, sustainable, irrigation">
                            </div>
                            <div class="col-12" id="duration_field" style="display:none;">
                                <label class="form-label fw-semibold">Video Duration (minutes)</label>
                                <input type="number" name="duration_minutes" class="form-control" min="1" placeholder="e.g., 15">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Upload File <span class="text-danger">*</span></label>
                                <div class="upload-zone">
                                    <i class="bi bi-cloud-arrow-up fs-2 text-muted mb-2 d-block"></i>
                                    <input type="file" name="resource_file" class="form-control" accept=".pdf,.mp4,.avi,.mov" required>
                                    <small class="text-muted mt-2 d-block">PDF, MP4, AVI, MOV — max 50 MB</small>
                                </div>
                            </div>
                            <div class="col-12">
                                <button type="submit" name="upload_resource" class="btn btn-green w-100 rounded-pill py-2 fw-semibold">
                                    <i class="bi bi-upload me-2"></i>Publish Resource
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ── Tab: My Resources ────────────────────────────────── -->
            <div class="tab-pane fade" id="tab-resources">
                <?php if (empty($resources_list)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox fs-1 text-muted"></i>
                        <p class="text-muted mt-3">No resources yet. Upload your first one!</p>
                        <a href="#tab-upload" class="btn btn-green rounded-pill px-4" data-bs-toggle="tab">Upload Now</a>
                    </div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($resources_list as $res): ?>
                            <?php
                            $type_colors = ['video'=>'danger','pdf'=>'primary','climate_guide'=>'success','research_paper'=>'warning'];
                            $type_icons  = ['video'=>'bi-camera-video','pdf'=>'bi-file-pdf','climate_guide'=>'bi-cloud-sun','research_paper'=>'bi-journal-text'];
                            $color = $type_colors[$res['content_type']] ?? 'secondary';
                            $icon  = $type_icons[$res['content_type']] ?? 'bi-file';
                            ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card resource-card h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <span class="badge-type bg-<?php echo $color; ?> text-white">
                                                <i class="bi <?php echo $icon; ?> me-1"></i><?php echo ucfirst(str_replace('_',' ',$res['content_type'])); ?>
                                            </span>
                                            <small class="text-muted"><?php echo date('M d', strtotime($res['created_at'])); ?></small>
                                        </div>
                                        <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($res['title']); ?></h6>
                                        <p class="small text-muted mb-2"><?php echo htmlspecialchars(mb_substr($res['description'] ?? '', 0, 80, 'UTF-8')); ?>…</p>
                                        <?php if ($res['category']): ?>
                                            <span class="badge bg-light text-dark mb-2"><?php echo htmlspecialchars($res['category']); ?></span>
                                        <?php endif; ?>
                                        <div class="d-flex justify-content-between align-items-center mt-2">
                                            <small class="text-muted">
                                                <i class="bi bi-eye me-1"></i><?php echo $res['view_count']; ?>
                                                <i class="bi bi-download ms-2 me-1"></i><?php echo $res['download_count']; ?>
                                                <?php if ($res['file_size'] > 0): ?>
                                                    <i class="bi bi-hdd ms-2 me-1"></i><?php echo $res['file_size']; ?> MB
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="card-footer bg-white border-top-0 d-flex gap-2 p-3">
                                        <?php if ($res['file_url']): ?>
                                            <a href="<?php echo htmlspecialchars($res['file_url']); ?>" target="_blank" class="btn btn-sm btn-outline-primary flex-fill">
                                                <i class="bi bi-box-arrow-up-right me-1"></i>View
                                            </a>
                                        <?php endif; ?>
                                        <form method="POST" class="flex-fill" onsubmit="return confirm('Delete this resource?')">
                                            <input type="hidden" name="resource_id" value="<?php echo $res['id']; ?>">
                                            <button type="submit" name="delete_resource" class="btn btn-sm btn-outline-danger w-100">
                                                <i class="bi bi-trash me-1"></i>Delete
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ── Tab: Expert Profile ──────────────────────────────── -->
            <div class="tab-pane fade" id="tab-profile">
                <div class="card border-0 rounded-4 shadow-sm p-4" style="max-width:680px;">
                    <h5 class="fw-bold mb-4"><i class="bi bi-person-badge me-2 text-success"></i>Your Expert Profile</h5>
                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Area of Expertise</label>
                                <input type="text" name="expertise_area" class="form-control"
                                    value="<?php echo htmlspecialchars($expert_profile['expertise_area'] ?? ''); ?>"
                                    placeholder="e.g., Soil Science, Crop Diseases">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Years of Experience</label>
                                <input type="number" name="experience_years" class="form-control" min="0"
                                    value="<?php echo (int)($expert_profile['experience_years'] ?? 0); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Consultation Rate (ETB/hr)</label>
                                <input type="number" name="rating" class="form-control" step="0.01" min="0"
                                    value="<?php echo (float)($expert_profile['rating'] ?? 0); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Qualifications</label>
                                <input type="text" name="certifications" class="form-control"
                                    value="<?php echo htmlspecialchars($expert_profile['certifications'] ?? ''); ?>"
                                    placeholder="e.g., MSc Agronomy, PhD Plant Science">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Bio / About You</label>
                                <textarea name="bio" class="form-control" rows="4"
                                    placeholder="Tell farmers about your background and how you can help…"><?php echo htmlspecialchars($expert_profile['user_bio'] ?? $expert_profile['bio'] ?? ''); ?></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" name="update_profile" class="btn btn-green rounded-pill px-4 fw-semibold">
                                    <i class="bi bi-save me-2"></i>Save Profile
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

        </div><!-- /tab-content -->
    </main>
</div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Show duration field only for video
    document.getElementById('contentTypeSelect').addEventListener('change', function () {
        document.getElementById('duration_field').style.display = this.value === 'video' ? 'block' : 'none';
    });

    // Keep active tab after form submit (via hash)
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach(tab => {
        tab.addEventListener('shown.bs.tab', e => {
            history.replaceState(null, null, e.target.getAttribute('href'));
        });
    });
    // Restore tab on load
    const hash = window.location.hash;
    if (hash) {
        const tab = document.querySelector(`[href="${hash}"]`);
        if (tab) new bootstrap.Tab(tab).show();
    }
</script>
</body>
</html>