<?php
// system_check.php - Complete system verification
require_once 'includes/db.php';

echo "<h2>System Status Check</h2>";

// 1. Database Connection
echo "<h3>Database Connection</h3>";
try {
    $stmt = $pdo->query("SELECT 1");
    echo "<div style='color:green;'>✅ Database connected successfully</div>";
} catch (Exception $e) {
    echo "<div style='color:red;'>❌ Database connection failed: " . $e->getMessage() . "</div>";
}

// 2. Required Columns Check
echo "<h3>Database Schema</h3>";
$required_columns = ['profile_photo', 'bio'];
foreach ($required_columns as $column) {
    try {
        $stmt = $pdo->query("SELECT $column FROM users LIMIT 1");
        echo "<div style='color:green;'>✅ Column '$column' exists</div>";
    } catch (Exception $e) {
        echo "<div style='color:red;'>❌ Column '$column' missing</div>";
    }
}

// 3. Required Tables Check
echo "<h3>Required Tables</h3>";
$required_tables = ['users', 'payment_methods', 'email_verification'];
foreach ($required_tables as $table) {
    try {
        $stmt = $pdo->query("SELECT 1 FROM $table LIMIT 1");
        echo "<div style='color:green;'>✅ Table '$table' exists</div>";
    } catch (Exception $e) {
        echo "<div style='color:red;'>❌ Table '$table' missing</div>";
    }
}

// 4. File Permissions
echo "<h3>File Permissions</h3>";
$dirs = ['uploads', 'uploads/profiles'];
foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        echo "<div style='color:green;'>✅ Directory '$dir' exists</div>";
    } else {
        echo "<div style='color:orange;'>⚠️ Directory '$dir' missing</div>";
    }
}

// 5. Session Check
echo "<h3>Session Management</h3>";
if (session_status() === PHP_SESSION_ACTIVE) {
    echo "<div style='color:green;'>✅ Session active</div>";
} else {
    echo "<div style='color:orange;'>⚠️ Session not active</div>";
}

echo "<p><a href='fix_database.php'>Run Database Fix</a> | <a href='dashboard.php'>Go to Dashboard</a></p>";
?>
