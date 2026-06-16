<?php
// fix_expert_upload.php - Diagnose and fix expert upload issues
require_once 'includes/db.php';
require_once 'includes/session.php';

echo "<h2>Fix Expert Upload Issues</h2>";

// 1. Check PHP upload settings
echo "<h3>1. PHP Upload Configuration</h3>";
$upload_settings = [
    'file_uploads' => ini_get('file_uploads'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_execution_time' => ini_get('max_execution_time'),
    'memory_limit' => ini_get('memory_limit')
];

foreach ($upload_settings as $setting => $value) {
    echo "<div style='color:blue;'>📋 $setting: $value</div>";
}

if (ini_get('file_uploads') !== '1') {
    echo "<div style='color:red;'>❌ File uploads are disabled in PHP configuration</div>";
} else {
    echo "<div style='color:green;'>✅ File uploads are enabled</div>";
}

// 2. Check upload directory permissions
echo "<h3>2. Upload Directory Check</h3>";
$upload_dirs = [
    'uploads/',
    'uploads/expert_resources/',
    'uploads/temp/'
];

foreach ($upload_dirs as $dir) {
    if (!file_exists($dir)) {
        echo "<div style='color:red;'>❌ Directory missing: $dir</div>";
        if (mkdir($dir, 0755, true)) {
            echo "<div style='color:green;'>✅ Created directory: $dir</div>";
        } else {
            echo "<div style='color:red;'>❌ Failed to create: $dir</div>";
        }
    } else {
        echo "<div style='color:green;'>✅ Directory exists: $dir</div>";
        
        // Check if writable
        $test_file = $dir . 'test_' . time() . '.txt';
        if (file_put_contents($test_file, 'test')) {
            echo "<div style='color:green;'>✅ Directory writable: $dir</div>";
            unlink($test_file);
        } else {
            echo "<div style='color:red;'>❌ Directory not writable: $dir</div>";
        }
    }
}

// 3. Check expert_resources table
echo "<h3>3. Database Table Check</h3>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'expert_resources'");
    if ($stmt->rowCount() == 0) {
        echo "<div style='color:red;'>❌ expert_resources table missing</div>";
        
        // Create table
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
    } else {
        echo "<div style='color:green;'>✅ expert_resources table exists</div>";
        
        // Check table structure
        $stmt = $pdo->query("DESCRIBE expert_resources");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<h4>Table columns:</h4>";
        foreach ($columns as $column) {
            echo "<div style='color:blue;'>📋 {$column['Field']} ({$column['Type']})</div>";
        }
    }
} catch (Exception $e) {
    echo "<div style='color:red;'>❌ Database error: " . $e->getMessage() . "</div>";
}

// 4. Check expert_dashboard.php upload form
echo "<h3>4. Upload Form Check</h3>";
$dashboard_file = 'expert_dashboard.php';
if (file_exists($dashboard_file)) {
    $content = file_get_contents($dashboard_file);
    
    $required_elements = [
        'method="POST"' => 'POST method',
        'enctype="multipart/form-data"' => 'File upload encoding',
        'name="upload_resource"' => 'Upload form handler',
        'name="resource_file"' => 'File input',
        'name="title"' => 'Title input',
        'name="content_type"' => 'Content type select',
        'name="category"' => 'Category select',
        'move_uploaded_file' => 'File move function'
    ];
    
    foreach ($required_elements as $element => $description) {
        if (strpos($content, $element) !== false) {
            echo "<div style='color:green;'>✅ $description found</div>";
        } else {
            echo "<div style='color:red;'>❌ $description missing</div>";
        }
    }
} else {
    echo "<div style='color:red;'>❌ expert_dashboard.php file not found</div>";
}

// 5. Create a simple upload test
echo "<h3>5. Upload Test</h3>";
echo "<form method='POST' enctype='multipart/form-data' action=''>";
echo "<div style='background:#f8f9fa; padding:15px; border-radius:5px;'>";
echo "<h4>Test Upload Form</h4>";
echo "<div class='mb-3'>";
echo "<label class='form-label'>Title:</label>";
echo "<input type='text' name='test_title' class='form-control' required>";
echo "</div>";
echo "<div class='mb-3'>";
echo "<label class='form-label'>File:</label>";
echo "<input type='file' name='test_file' class='form-control' required>";
echo "</div>";
echo "<button type='submit' name='test_upload' class='btn btn-primary'>Test Upload</button>";
echo "</div>";
echo "</form>";

// Handle test upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_upload'])) {
    $title = $_POST['test_title'] ?? '';
    
    if (isset($_FILES['test_file']) && $_FILES['test_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/expert_resources/';
        $file_name = time() . '_' . basename($_FILES['test_file']['name']);
        $target_path = $upload_dir . $file_name;
        
        if (move_uploaded_file($_FILES['test_file']['tmp_name'], $target_path)) {
            echo "<div style='color:green;'>✅ Test upload successful!</div>";
            echo "<div>File saved as: $target_path</div>";
            echo "<div>File size: " . ($_FILES['test_file']['size'] / 1024 / 1024) . " MB</div>";
            
            // Clean up test file
            unlink($target_path);
        } else {
            echo "<div style='color:red;'>❌ Failed to move uploaded file</div>";
        }
    } else {
        echo "<div style='color:red;'>❌ File upload error: " . $_FILES['test_file']['error'] . "</div>";
    }
}

// 6. Expert user check
echo "<h3>6. Expert User Check</h3>";
try {
    $stmt = $pdo->prepare("SELECT id, name, email FROM users WHERE role = 'agri_expert' AND approval_status = 'approved'");
    $stmt->execute();
    $experts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($experts)) {
        echo "<div style='color:red;'>❌ No approved expert users found</div>";
        echo "<p><a href='fix_expert_profiles_table.php'>Create Expert Users</a></p>";
    } else {
        echo "<div style='color:green;'>✅ Found " . count($experts) . " approved experts</div>";
        foreach ($experts as $expert) {
            echo "<div style='color:blue;'>📋 {$expert['name']} ({$expert['email']})</div>";
        }
    }
} catch (Exception $e) {
    echo "<div style='color:red;'>❌ Error checking experts: " . $e->getMessage() . "</div>";
}

echo "<h3>7. Recommendations</h3>";
echo "<div style='background:#f8f9fa; padding:15px; border-radius:5px;'>";
echo "<p><b>If upload fails, check:</b></p>";
echo "<p>1. PHP file upload settings (upload_max_filesize, post_max_size)</p>";
echo "<p>2. Directory permissions (uploads/expert_resources/)</p>";
echo "<p>3. Form enctype='multipart/form-data'</p>";
echo "<p>4. Expert user is logged in and approved</p>";
echo "<p><b>Test Steps:</b></p>";
echo "<p>1. Use test form above to verify basic upload</p>";
echo "<p>2. Login as expert and try expert dashboard</p>";
echo "<p>3. Check browser console for JavaScript errors</p>";
echo "<p><a href='expert_dashboard.php' style='background:#007bff; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>Try Expert Dashboard Upload</a></p>";
echo "</div>";
?>
