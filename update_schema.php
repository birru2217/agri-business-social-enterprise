<?php
// update_schema.php - Apply database schema updates
require_once 'includes/db.php';

echo "<h2>Updating Database Schema...</h2>";

try {
    // Add profile_photo column to users table
    $sql = "ALTER TABLE users ADD COLUMN profile_photo VARCHAR(500) AFTER location";
    $pdo->exec($sql);
    echo "<div style='color:green; padding:10px; border:2px solid green; margin-bottom:10px;'>";
    echo "<b>SUCCESS:</b> Added profile_photo column to users table";
    echo "</div>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "<div style='color:orange; padding:10px; border:2px solid orange; margin-bottom:10px;'>";
        echo "<b>INFO:</b> profile_photo column already exists in users table";
        echo "</div>";
    } else {
        echo "<div style='color:red; padding:10px; border:2px solid red; margin-bottom:10px;'>";
        echo "<b>ERROR:</b> " . $e->getMessage();
        echo "</div>";
    }
}

try {
    // Add bio column to users table
    $sql = "ALTER TABLE users ADD COLUMN bio TEXT AFTER profile_photo";
    $pdo->exec($sql);
    echo "<div style='color:green; padding:10px; border:2px solid green; margin-bottom:10px;'>";
    echo "<b>SUCCESS:</b> Added bio column to users table";
    echo "</div>";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "<div style='color:orange; padding:10px; border:2px solid orange; margin-bottom:10px;'>";
        echo "<b>INFO:</b> bio column already exists in users table";
        echo "</div>";
    } else {
        echo "<div style='color:red; padding:10px; border:2px solid red; margin-bottom:10px;'>";
        echo "<b>ERROR:</b> " . $e->getMessage();
        echo "</div>";
    }
}

echo "<h3>Schema Update Complete!</h3>";
echo "<p><a href='dashboard.php'>Go to Dashboard</a></p>";
?>
