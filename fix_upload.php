<?php
// fix_upload.php - Simple upload fix
require_once 'includes/db.php';

echo "<h2>Upload Fix</h2>";

// Create directories
if (!file_exists('uploads')) mkdir('uploads', 0755);
if (!file_exists('uploads/expert_resources')) mkdir('uploads/expert_resources', 0755);

echo "<div style='color:green;'>✅ Directories created</div>";

// Create table
// fix_upload.php keessatti kutaa kana qofa bakka buusi
try {
    $pdo->exec("DROP TABLE IF EXISTS expert_resources");
    $pdo->exec("CREATE TABLE expert_resources (
        id INT AUTO_INCREMENT PRIMARY KEY,
        expert_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        content_type VARCHAR(50),
        file_url VARCHAR(500),
        file_size FLOAT DEFAULT 0,            -- Dashboard irratti ni barbaadama
        duration_minutes INT DEFAULT 0,       -- Video-f ni barbaadama
        category VARCHAR(100),                -- Filter gochuuf ni barbaadama
        tags VARCHAR(255),                    -- Search gochuuf ni barbaadama
        status VARCHAR(20) DEFAULT 'published', -- DOGONGORA KANA FURUURAF KANA DABALI
        view_count INT DEFAULT 0,             -- Statistics-f ni barbaadama
        download_count INT DEFAULT 0,         -- Statistics-f ni barbaadama
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<div style='color:green;'>✅ Gabateen haaraa column hunda qabu uumameera!</div>";
} catch (Exception $e) {
    echo "<div style='color:red;'>❌ Dogongora: " . $e->getMessage() . "</div>";
}

// Create expert user
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = 'expert@test.com'");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $hashed = password_hash('password123', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (name, email, password, role, approval_status, is_verified) VALUES (?, ?, ?, ?, 'approved', 1)")
           ->execute(['Expert User', 'expert@test.com', $hashed, 'agri_expert']);
        echo "<div style='color:green;'>✅ Expert user created</div>";
    }
} catch (Exception $e) {
    echo "<div style='color:red;'>❌ " . $e->getMessage() . "</div>";
}

echo "<h3>Login Credentials:</h3>";
echo "<p>Email: expert@test.com</p>";
echo "<p>Password: password123</p>";
echo "<a href='login.php'>Login Now</a>";
?>
