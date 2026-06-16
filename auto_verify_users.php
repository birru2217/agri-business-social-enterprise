<?php
// auto_verify_users.php - Auto-verify all existing users to fix login issue
require_once 'includes/db.php';

echo "<h2>Auto-Verify Existing Users</h2>";

try {
    // First ensure email_verification table exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS email_verification (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(100) NOT NULL UNIQUE,
        verification_code VARCHAR(6) NOT NULL,
        expires_at TIMESTAMP NOT NULL,
        is_used BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<div style='color:green;'>✅ email_verification table ensured</div>";
    
    // Get all existing users
    $stmt = $pdo->query("SELECT id, name, email FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $verified_count = 0;
    $skipped_count = 0;
    
    foreach ($users as $user) {
        // Check if user already has a verification record
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM email_verification WHERE email = ? AND is_used = TRUE");
        $stmt->execute([$user['email']]);
        $already_verified = $stmt->fetchColumn();
        
        if ($already_verified) {
            echo "<div style='color:orange;'>⚠️ User {$user['name']} ({$user['email']}) already verified</div>";
            $skipped_count++;
        } else {
            // Create a verification record and mark it as used
            $stmt = $pdo->prepare("INSERT IGNORE INTO email_verification (email, verification_code, expires_at, is_used) VALUES (?, ?, ?, TRUE)");
            $result = $stmt->execute([
                $user['email'],
                'AUTOVER', // Auto-verification code
                date('Y-m-d H:i:s', strtotime('+1 year')) // Far future expiration
            ]);
            
            if ($result) {
                echo "<div style='color:green;'>✅ Auto-verified user: {$user['name']} ({$user['email']})</div>";
                $verified_count++;
            } else {
                echo "<div style='color:orange;'>⚠️ User {$user['name']} ({$user['email']}) already has record</div>";
                $skipped_count++;
            }
        }
    }
    
    echo "<h3>Summary</h3>";
    echo "<div style='background:#f8f9fa; padding:15px; border-radius:5px;'>";
    echo "<p><b>Total users:</b> " . count($users) . "</p>";
    echo "<p><b>Newly verified:</b> $verified_count</p>";
    echo "<p><b>Already verified:</b> $skipped_count</p>";
    echo "<p style='color:green; font-weight:bold;'>✅ All users can now login without email verification!</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='color:red;'>❌ Error: " . $e->getMessage() . "</div>";
}

echo "<p><a href='login.php'>Test Login</a> | <a href='dashboard.php'>Go to Dashboard</a></p>";
?>
