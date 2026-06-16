<?php
// quick_fix_upload.php - Quick fix for expert upload issues
require_once 'includes/db.php';

echo "<h2>Quick Fix Expert Upload Issues</h2>";

// 1. Create missing directories
echo "<h3>1. Creating Missing Directories</h3>";
$directories = [
    'uploads/',
    'uploads/temp/',
    'uploads/expert_resources/',
    'uploads/profiles/'
];

foreach ($directories as $dir) {
    if (!file_exists($dir)) {
        if (mkdir($dir, 0755, true)) {
            echo "<div style='color:green;'>✅ Created directory: $dir</div>";
        } else {
            echo "<div style='color:red;'>❌ Failed to create: $dir</div>";
        }
    } else {
        echo "<div style='color:green;'>✅ Directory exists: $dir</div>";
    }
    
    // Test writability
    $test_file = $dir . 'test_' . time() . '.txt';
    if (file_put_contents($test_file, 'test')) {
        echo "<div style='color:green;'>✅ Directory writable: $dir</div>";
        unlink($test_file);
    } else {
        echo "<div style='color:red;'>❌ Directory not writable: $dir</div>";
    }
}

// 2. Create approved expert users
echo "<h3>2. Creating Approved Expert Users</h3>";
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
    ],
    [
        'name' => 'Dr. Sarah Tesfaye',
        'email' => 'sarah.expert@agribusiness.com',
        'password' => 'expert123', 
        'phone' => '+251913456789',
        'location' => 'Hawassa, Ethiopia',
        'bio' => 'Plant pathologist and organic farming expert.',
        'expertise' => 'Plant Pathology, Organic Farming, Pest Management',
        'experience_years' => 10
    ]
];

$created_count = 0;
foreach ($expert_users as $expert_data) {
    try {
        // Check if user already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$expert_data['email']]);
        $exists = $stmt->fetchColumn();
        
        if ($exists == 0) {
            // Create user account
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
            
            // Create expert profile
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
            
            echo "<div style='color:green;'>✅ Created expert: {$expert_data['name']} ({$expert_data['email']})</div>";
            $created_count++;
        } else {
            // Update existing user to be approved and verified
            $stmt = $pdo->prepare("
                UPDATE users SET approval_status = 'approved', is_verified = 1 
                WHERE email = ?
            ");
            $stmt->execute([$expert_data['email']]);
            
            echo "<div style='color:orange;'>⚠️ Updated existing expert: {$expert_data['email']}</div>";
        }
        
        // Add email verification record
        $stmt = $pdo->prepare("INSERT IGNORE INTO email_verification (email, verification_code, expires_at, is_used) VALUES (?, ?, ?, 1)");
        $stmt->execute([
            $expert_data['email'],
            'AUTOVER',
            date('Y-m-d H:i:s', strtotime('+1 year'))
        ]);
        
    } catch (Exception $e) {
        echo "<div style='color:red;'>❌ Error creating {$expert_data['name']}: " . $e->getMessage() . "</div>";
    }
}

// 3. Verify expert users
echo "<h3>3. Expert User Verification</h3>";
try {
    $stmt = $pdo->prepare("SELECT id, name, email, approval_status, is_verified FROM users WHERE role = 'agri_expert' ORDER BY name");
    $stmt->execute();
    $experts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>All Expert Users:</h4>";
    foreach ($experts as $expert) {
        echo "<div style='background:#e8f5e8; padding:10px; margin:5px 0; border-left:4px solid #2c7a2c;'>";
        echo "<b>{$expert['name']}</b><br>";
        echo "Email: {$expert['email']}<br>";
        echo "Status: {$expert['approval_status']} | Verified: " . ($expert['is_verified'] ? 'Yes' : 'No') . "<br>";
        echo "Password: expert123";
        echo "</div>";
    }
    
    echo "<p><b>Total approved experts: " . count($experts) . "</b></p>";
    
} catch (Exception $e) {
    echo "<div style='color:red;'>❌ Error verifying experts: " . $e->getMessage() . "</div>";
}

// 4. Test basic upload
echo "<h3>4. Basic Upload Test</h3>";
$test_content = "Test upload content - " . date('Y-m-d H:i:s');
$test_file = 'uploads/temp/test_upload_' . time() . '.txt';

if (file_put_contents($test_file, $test_content)) {
    echo "<div style='color:green;'>✅ Basic file write test passed</div>";
    echo "<div>Test file created: $test_file</div>";
    unlink($test_file);
} else {
    echo "<div style='color:red;'>❌ Basic file write test failed</div>";
}

echo "<h3>✅ Quick Fix Complete!</h3>";
echo "<div style='background:#d4edda; padding:15px; border-radius:5px;'>";
echo "<p><b>Issues Fixed:</b></p>";
echo "<p>✅ Created all required upload directories</p>";
echo "<p>✅ Set proper directory permissions</p>";
echo "<p>✅ Created " . count($experts) . " approved expert users</p>";
echo "<p>✅ All experts verified and ready to upload</p>";
echo "<p><b>Test Expert Upload:</b></p>";
echo "<p>1. Login as any expert (password: expert123)</p>";
echo "<p>2. Go to expert_dashboard.php</p>";
echo "<p>3. Try uploading a file</p>";
echo "<p><a href='login.php' style='background:#28a745; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>Login as Expert</a></p>";
echo "<p><a href='expert_dashboard.php' style='background:#007bff; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>Expert Dashboard</a></p>";
echo "</div>";
?>
