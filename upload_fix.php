<?php
// upload_fix.php - Quick fix for expert upload
require_once 'includes/db.php';

echo "<h2>Expert Upload Fix</h2>";

// 1. Create directories
$dirs = ['uploads/', 'uploads/expert_resources/'];
foreach ($dirs as $dir) {
    if (!file_exists($dir)) {
        mkdir($dir, 0755, true);
        echo "<div style='color:green;'>✅ Created: $dir</div>";
    }
}

// 2. Create expert_resources table
try {
    $pdo->exec("DROP TABLE IF EXISTS expert_resources");
    $pdo->exec("CREATE TABLE expert_resources (
        id INT AUTO_INCREMENT PRIMARY KEY,
        expert_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        content_type VARCHAR(50),
        category VARCHAR(100),
        file_url VARCHAR(500),
        file_size DECIMAL(10,2),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (expert_id) REFERENCES users(id)
    )");
    echo "<div style='color:green;'>✅ Created expert_resources table</div>";
} catch (Exception $e) {
    echo "<div style='color:red;'>❌ DB Error: " . $e->getMessage() . "</div>";
}

// 3. Create expert user if not exists
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = 'expert@test.com'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $hashed = password_hash('password123', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (name, email, password, role, approval_status, is_verified) VALUES (?, ?, ?, ?, 'approved', 1)")
           ->execute(['Expert User', 'expert@test.com', $hashed, 'agri_expert']);
        echo "<div style='color:green;'>✅ Created expert user</div>";
    }
} catch (Exception $e) {
    echo "<div style='color:red;'>❌ User Error: " . $e->getMessage() . "</div>";
}

echo "<h3>Login as Expert:</h3>";
echo "<p>Email: expert@test.com</p>";
echo "<p>Password: password123</p>";
echo "<a href='login.php' class='btn btn-primary'>Login Now</a>";
?>
