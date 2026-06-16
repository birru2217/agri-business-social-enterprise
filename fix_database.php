<?php
// fix_database.php - Fix missing database columns
require_once 'includes/db.php';

echo "<h2>Database Schema Fix</h2>";
echo "<p>This script will add missing columns to fix the profile photo error.</p>";

$updates = [
    [
        'sql' => "ALTER TABLE users ADD COLUMN profile_photo VARCHAR(500) AFTER location",
        'description' => 'profile_photo column for user profile pictures'
    ],
    [
        'sql' => "ALTER TABLE users ADD COLUMN bio TEXT AFTER profile_photo", 
        'description' => 'bio column for user descriptions'
    ],
    [
        'sql' => "CREATE TABLE IF NOT EXISTS payment_methods (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            provider VARCHAR(50) NOT NULL,
            type ENUM('bank', 'mobile_money', 'card') NOT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            config_data JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        'description' => 'payment_methods table for payment gateway'
    ],
    [
        'sql' => "CREATE TABLE IF NOT EXISTS payment_transactions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            transaction_id VARCHAR(100) NOT NULL UNIQUE,
            user_id INT NOT NULL,
            order_id INT,
            contribution_id INT,
            amount DECIMAL(10, 2) NOT NULL,
            currency VARCHAR(3) DEFAULT 'ETB',
            payment_method_id INT NOT NULL,
            status ENUM('pending', 'processing', 'completed', 'failed', 'refunded') DEFAULT 'pending',
            provider_transaction_id VARCHAR(100),
            provider_response JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )",
        'description' => 'payment_transactions table'
    ],
    [
        'sql' => "CREATE TABLE IF NOT EXISTS user_payment_accounts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            payment_method_id INT NOT NULL,
            account_number VARCHAR(100),
            account_name VARCHAR(100),
            is_default BOOLEAN DEFAULT FALSE,
            is_verified BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        'description' => 'user_payment_accounts table'
    ],
    [
        'sql' => "CREATE TABLE IF NOT EXISTS email_verification (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(100) NOT NULL UNIQUE,
            verification_code VARCHAR(6) NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            is_used BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        'description' => 'email_verification table'
    ],
    [
        'sql' => "CREATE TABLE IF NOT EXISTS password_reset (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(100) NOT NULL UNIQUE,
            reset_token VARCHAR(64) NOT NULL,
            expires_at TIMESTAMP NOT NULL,
            is_used BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
        'description' => 'password_reset table'
    ]
];

$success_count = 0;
$error_count = 0;

foreach ($updates as $update) {
    try {
        $pdo->exec($update['sql']);
        echo "<div style='color:green; padding:10px; border:2px solid green; margin-bottom:10px;'>";
        echo "<b>SUCCESS:</b> Added " . $update['description'];
        echo "</div>";
        $success_count++;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false || strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "<div style='color:orange; padding:10px; border:2px solid orange; margin-bottom:10px;'>";
            echo "<b>INFO:</b> " . $update['description'] . " already exists";
            echo "</div>";
        } else {
            echo "<div style='color:red; padding:10px; border:2px solid red; margin-bottom:10px;'>";
            echo "<b>ERROR:</b> Failed to add " . $update['description'] . "<br>";
            echo "Error: " . $e->getMessage();
            echo "</div>";
            $error_count++;
        }
    }
}

echo "<h3>Summary</h3>";
echo "<div style='padding:15px; background:#f8f9fa; border-radius:5px;'>";
echo "<p><b>Successful updates:</b> $success_count</p>";
echo "<p><b>Errors:</b> $error_count</p>";

if ($error_count === 0) {
    echo "<p style='color:green; font-weight:bold;'>✅ Database schema is now up to date!</p>";
    echo "<p><a href='dashboard.php' style='background:#28a745; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>Go to Dashboard</a></p>";
} else {
    echo "<p style='color:red; font-weight:bold;'>❌ Some updates failed. Please check the errors above.</p>";
}
echo "</div>";
?>
