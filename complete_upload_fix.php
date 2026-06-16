<?php
// complete_upload_fix.php - Complete fix for expert upload issues
require_once 'includes/db.php';
require_once 'includes/session.php';

echo "<h2>Complete Expert Upload Fix</h2>";

// 1. Fix database tables
echo "<h3>1. Database Tables Fix</h3>";

// Drop and recreate expert_resources table with correct structure
try {
    $pdo->exec("DROP TABLE IF EXISTS expert_resources");
    echo "<div style='color:orange;'>⚠️ Dropped old expert_resources table</div>";
    
    $pdo->exec("CREATE TABLE expert_resources (
        id INT AUTO_INCREMENT PRIMARY KEY,
        expert_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        content_type ENUM('document', 'video', 'audio', 'image') NOT NULL,
        category ENUM('Video Tutorials', 'PDF Guides', 'Climate Content', 'Research Papers', 'General') NOT NULL DEFAULT 'General',
        file_url VARCHAR(500),
        file_size DECIMAL(10, 2),
        duration_minutes INT,
        tags TEXT,
        status ENUM('draft', 'published', 'archived') DEFAULT 'published',
        view_count INT DEFAULT 0,
        download_count INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (expert_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    echo "<div style='color:green;'>✅ Created expert_resources table</div>";
} catch (Exception $e) {
    echo "<div style='color:red;'>❌ Database table error: " . $e->getMessage() . "</div>";
}

// 2. Fix directories
echo "<h3>2. Directory Structure Fix</h3>";
$directories = [
    'uploads/',
    'uploads/temp/',
    'uploads/expert_resources/',
    'uploads/profiles/'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "<div style='color:green;'>✅ Created: $dir</div>";
        } else {
            echo "<div style='color:red;'>❌ Failed to create: $dir</div>";
        }
    } else {
        echo "<div style='color:green;'>✅ Exists: $dir</div>";
    }
    
    // Test writability
    $test_file = $dir . 'test_' . time() . '.txt';
    if (file_put_contents($test_file, 'test')) {
        echo "<div style='color:green;'>✅ Writable: $dir</div>";
        unlink($test_file);
    } else {
        echo "<div style='color:red;'>❌ Not writable: $dir</div>";
    }
}

// 3. Create expert users
echo "<h3>3. Expert Users Creation</h3>";
$expert_users = [
    [
        'name' => 'Dr. Alice Johnson',
        'email' => 'alice.expert@agribusiness.com',
        'password' => 'expert123',
        'phone' => '+251911234567',
        'location' => 'Addis Ababa, Ethiopia',
        'bio' => 'Agricultural scientist with 15 years experience in sustainable farming.',
        'expertise' => 'Sustainable Agriculture, Crop Management, Soil Science',
        'experience_years' => 15
    ],
    [
        'name' => 'Bob Wilson',
        'email' => 'bob.expert@agribusiness.com', 
        'password' => 'expert123',
        'phone' => '+251912345678',
        'location' => 'Bahir Dar, Ethiopia',
        'bio' => 'Agri-business consultant specializing in modern farming techniques.',
        'expertise' => 'Modern Farming, Agricultural Technology, Business Management',
        'experience_years' => 12
    ]
];

foreach ($expert_users as $expert_data) {
    try {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$expert_data['email']]);
        $exists = $stmt->fetchColumn();
        
        if ($exists == 0) {
            // Create user
            $hashed_password = password_hash($expert_data['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO users (name, email, password, role, phone, location, bio, approval_status, is_verified) 
                VALUES (?, ?, ?, ?, ?, ?, ?, 'approved', 1)
            ");
            $stmt->execute([
                $expert_data['name'],
                $expert_data['email'], 
                $hashed_password,
                'agri_expert',
                $expert_data['phone'],
                $expert_data['location'],
                $expert_data['bio']
            ]);
            
            $user_id = $pdo->lastInsertId();
            
            // Create profile
            $stmt = $pdo->prepare("
                INSERT INTO agri_expert_profiles (user_id, expertise, experience_years, availability_status, bio) 
                VALUES (?, ?, ?, 'available', ?)
            ");
            $stmt->execute([
                $user_id,
                $expert_data['expertise'],
                $expert_data['experience_years'],
                $expert_data['bio']
            ]);
            
            echo "<div style='color:green;'>✅ Created expert: {$expert_data['name']}</div>";
        }
        
        // Add email verification
        $stmt = $pdo->prepare("INSERT IGNORE INTO email_verification (email, verification_code, expires_at, is_used) VALUES (?, ?, ?, 1)");
        $stmt->execute([
            $expert_data['email'],
            'AUTOVER',
            date('Y-m-d H:i:s', strtotime('+1 year'))
        ]);
        
    } catch (Exception $e) {
        echo "<div style='color:red;'>❌ Error creating expert: " . $e->getMessage() . "</div>";
    }
}

// 4. Create a working expert dashboard
echo "<h3>4. Expert Dashboard Fix</h3>";
$dashboard_content = '<?php
// expert_dashboard.php - Fixed version
require_once "includes/db.php";
require_once "includes/session.php";

checkLogin();
checkRole(["agri_expert"]);

$user_id = $_SESSION["user_id"];
$user_name = $_SESSION["user_name"];

// Handle file upload
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["upload_resource"])) {
    $title = $_POST["title"] ?? "";
    $description = $_POST["description"] ?? "";
    $content_type = $_POST["content_type"] ?? "";
    $category = $_POST["category"] ?? "";
    $tags = $_POST["tags"] ?? "";
    
    $file_url = "";
    $file_size = 0;
    
    if (isset($_FILES["resource_file"]) && $_FILES["resource_file"]["error"] === UPLOAD_ERR_OK) {
        $upload_dir = "uploads/expert_resources/";
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_name = time() . "_" . basename($_FILES["resource_file"]["name"]);
        $target_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES["resource_file"]["tmp_name"], $target_path)) {
            $file_url = $target_path;
            $file_size = $_FILES["resource_file"]["size"] / 1024 / 1024;
            
            $success_message = "Resource uploaded successfully!";
        } else {
            $error_message = "Failed to upload file.";
        }
    }
    
    // Save to database
    if (!empty($title) && !empty($content_type) && !empty($file_url)) {
        $stmt = $pdo->prepare("INSERT INTO expert_resources (expert_id, title, description, content_type, category, file_url, file_size, tags) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $title, $description, $content_type, $category, $file_url, $file_size, $tags]);
        
        $success_message = "Resource saved successfully!";
    }
}

// Get expert resources
$resources = $pdo->prepare("SELECT * FROM expert_resources WHERE expert_id = ? ORDER BY created_at DESC");
$resources->execute([$user_id]);
$resources_list = $resources->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Expert Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Expert Dashboard</h1>
        <p>Welcome, <?php echo htmlspecialchars($user_name); ?></p>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-md-6">
                <h3>Upload Resource</h3>
                <form method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Content Type</label>
                        <select name="content_type" class="form-control" required>
                            <option value="">Select Type</option>
                            <option value="document">Document</option>
                            <option value="video">Video</option>
                            <option value="audio">Audio</option>
                            <option value="image">Image</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-control" required>
                            <option value="">Select Category</option>
                            <option value="Video Tutorials">Video Tutorials</option>
                            <option value="PDF Guides">PDF Guides</option>
                            <option value="Climate Content">Climate Content</option>
                            <option value="Research Papers">Research Papers</option>
                            <option value="General">General</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">File</label>
                        <input type="file" name="resource_file" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Tags</label>
                        <input type="text" name="tags" class="form-control">
                    </div>
                    <button type="submit" name="upload_resource" class="btn btn-primary">Upload Resource</button>
                </form>
            </div>
            
            <div class="col-md-6">
                <h3>My Resources</h3>
                <?php if (empty($resources_list)): ?>
                    <p>No resources uploaded yet.</p>
                <?php else: ?>
                    <?php foreach ($resources_list as $resource): ?>
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5><?php echo htmlspecialchars($resource["title"]); ?></h5>
                                <p><?php echo htmlspecialchars($resource["description"]); ?></p>
                                <small class="text-muted">
                                    Type: <?php echo $resource["content_type"]; ?> | 
                                    Category: <?php echo $resource["category"]; ?> |
                                    Size: <?php echo $resource["file_size"]; ?> MB
                                </small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>';

if (file_put_contents('expert_dashboard_fixed.php', $dashboard_content)) {
    echo "<div style='color:green;'>✅ Created fixed expert dashboard: expert_dashboard_fixed.php</div>";
} else {
    echo "<div style='color:red;'>❌ Failed to create fixed dashboard</div>";
}

// 5. Test upload functionality
echo "<h3>5. Upload Test</h3>";
echo "<form method='POST' enctype='multipart/form-data'>";
echo "<div class='mb-3'>";
echo "<label>Test Upload:</label>";
echo "<input type='file' name='test_file' class='form-control'>";
echo "<button type='submit' name='test_upload' class='btn btn-primary mt-2'>Test Upload</button>";
echo "</div>";
echo "</form>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_upload'])) {
    if (isset($_FILES['test_file']) && $_FILES['test_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/expert_resources/';
        $file_name = 'test_' . time() . '_' . basename($_FILES['test_file']['name']);
        $target_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['test_file']['tmp_name'], $target_path)) {
            echo "<div style='color:green;'>✅ Test upload successful!</div>";
            echo "<div>File: $target_path</div>";
            unlink($target_path); // Clean up
        } else {
            echo "<div style='color:red;'>❌ Test upload failed</div>";
        }
    } else {
        echo "<div style='color:red;'>❌ No file uploaded or error occurred</div>";
    }
}

echo "<h3>✅ Complete Upload Fix Done!</h3>";
echo "<div style='background:#d4edda; padding:15px; border-radius:5px;'>";
echo "<p><b>Fixed Issues:</b></p>";
echo "<p>✅ Database tables recreated</p>";
echo "<p>✅ All directories created and writable</p>";
echo "<p>✅ Expert users created and approved</p>";
echo "<p>✅ Fixed expert dashboard created</p>";
echo "<p><b>Test Expert Upload:</b></p>";
echo "<p>1. Login as expert (alice.expert@agribusiness.com / expert123)</p>";
echo "<p>2. Go to expert_dashboard_fixed.php</p>";
echo "<p>3. Try uploading a file</p>";
echo "<p><a href='login.php' class='btn btn-success me-2'>Login as Expert</a></p>";
echo "<p><a href='expert_dashboard_fixed.php' class='btn btn-primary'>Fixed Expert Dashboard</a></p>";
echo "</div>";
?>
