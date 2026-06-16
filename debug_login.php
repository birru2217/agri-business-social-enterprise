<?php
// debug_login.php - Debug login email verification issue
require_once 'includes/db.php';

echo "<h2>Login Debug Tool</h2>";

// Check if email_verification table exists
echo "<h3>Database Tables Check</h3>";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'email_verification'");
    $table_exists = $stmt->rowCount() > 0;
    
    if ($table_exists) {
        echo "<div style='color:green;'>✅ email_verification table exists</div>";
        
        // Check if any records exist
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM email_verification");
        $count = $stmt->fetch()['count'];
        echo "<div style='color:blue;'>📊 Email verification records: $count</div>";
        
        // Show sample records
        if ($count > 0) {
            $stmt = $pdo->query("SELECT email, verification_code, is_used, expires_at FROM email_verification LIMIT 5");
            $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo "<h4>Sample Records:</h4>";
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>Email</th><th>Code</th><th>Used</th><th>Expires</th></tr>";
            foreach ($records as $record) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($record['email']) . "</td>";
                echo "<td>" . htmlspecialchars($record['verification_code']) . "</td>";
                echo "<td>" . ($record['is_used'] ? 'Yes' : 'No') . "</td>";
                echo "<td>" . $record['expires_at'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "<div style='color:red;'>❌ email_verification table does not exist</div>";
    }
} catch (Exception $e) {
    echo "<div style='color:red;'>❌ Error checking table: " . $e->getMessage() . "</div>";
}

// Check existing users
echo "<h3>Existing Users</h3>";
try {
    $stmt = $pdo->query("SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Created</th><th>Email Status</th></tr>";
    
    foreach ($users as $user) {
        $email_status = "Unknown";
        try {
            if (function_exists('isEmailVerified')) {
                $email_status = isEmailVerified($pdo, $user['email']) ? "Verified" : "Not Verified";
            } else {
                $email_status = "Function missing";
            }
        } catch (Exception $e) {
            $email_status = "Error: " . $e->getMessage();
        }
        
        echo "<tr>";
        echo "<td>" . $user['id'] . "</td>";
        echo "<td>" . htmlspecialchars($user['name']) . "</td>";
        echo "<td>" . htmlspecialchars($user['email']) . "</td>";
        echo "<td>" . $user['role'] . "</td>";
        echo "<td>" . $user['created_at'] . "</td>";
        echo "<td style='color:" . ($email_status === "Verified" ? "green" : "red") . "'>" . $email_status . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "<div style='color:red;'>❌ Error checking users: " . $e->getMessage() . "</div>";
}

echo "<h3>Quick Fixes</h3>";
echo "<div style='background:#f8f9fa; padding:15px; border-radius:5px;'>";
echo "<p><b>Problem:</b> Users can't login because email verification system expects verification records.</p>";
echo "<p><b>Solution Options:</b></p>";
echo "<ol>";
echo "<li><a href='fix_database.php'>Run Database Fix</a> - Creates missing tables</li>";
echo "<li><a href='auto_verify_users.php'>Auto-Verify All Users</a> - Bypass verification for existing users</li>";
echo "<li><a href='disable_verification.php'>Disable Email Verification</a> - Turn off verification requirement</li>";
echo "</ol>";
echo "</div>";
?>
