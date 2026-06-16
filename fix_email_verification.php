<?php
// fix_email_verification.php - Fix email verification database issues
require_once 'includes/db.php';

echo "<h2>Fix Email Verification System</h2>";

// 1. Add is_verified column to users table
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_verified'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_verified INT DEFAULT 0 AFTER location");
        echo "<div style='color:green;'>✅ Added is_verified column to users table</div>";
    } else {
        echo "<div style='color:orange;'>⚠️ is_verified column already exists</div>";
    }
} catch (Exception $e) {
    echo "<div style='color:red;'>❌ Error adding is_verified column: " . $e->getMessage() . "</div>";
}

// 2. Ensure email_verification table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS email_verification (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(100) NOT NULL,
        verification_code VARCHAR(6) NOT NULL,
        expires_at TIMESTAMP NOT NULL,
        is_used INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email (email),
        INDEX idx_code (verification_code),
        INDEX idx_expires (expires_at)
    )");
    echo "<div style='color:green;'>✅ email_verification table ensured</div>";
} catch (Exception $e) {
    echo "<div style='color:red;'>❌ Error creating email_verification table: " . $e->getMessage() . "</div>";
}

// 3. Auto-verify existing users to fix login issue
try {
    $stmt = $pdo->query("SELECT id, email FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $verified_count = 0;
    foreach ($users as $user) {
        // Set is_verified = 1 for all existing users
        $stmt = $pdo->prepare("UPDATE users SET is_verified = 1 WHERE id = ?");
        $stmt->execute([$user['id']]);
        $verified_count++;
        
        // Also add to email_verification table as used
        $stmt = $pdo->prepare("INSERT IGNORE INTO email_verification (email, verification_code, expires_at, is_used) VALUES (?, ?, ?, 1)");
        $stmt->execute([
            $user['email'],
            'AUTOVER',
            date('Y-m-d H:i:s', strtotime('+1 year'))
        ]);
    }
    
    echo "<div style='color:green;'>✅ Auto-verified $verified_count existing users</div>";
} catch (Exception $e) {
    echo "<div style='color:red;'>❌ Error auto-verifying users: " . $e->getMessage() . "</div>";
}

echo "<h3>Next Steps</h3>";
echo "<div style='background:#f8f9fa; padding:15px; border-radius:5px;'>";
echo "<p><b>✅ Email verification system is now fixed!</b></p>";
echo "<p>All existing users are now verified and can login.</p>";
echo "<p>New users will need to verify their email before login.</p>";
echo "<p><a href='login.php' style='background:#28a745; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>Test Login Now</a></p>";
echo "</div>";
?>
