<?php
// test_contribution.php - DELETE THIS FILE AFTER DEBUGGING
require_once 'includes/db.php';

echo "<h2>Database Debug</h2>";

// 1. Test connection
echo "<p style='color:green'>✅ DB Connected successfully</p>";

// 2. Check if contributions table exists
try {
    $result = $pdo->query("SHOW TABLES LIKE 'contributions'")->fetch();
    if ($result) {
        echo "<p style='color:green'>✅ Table 'contributions' EXISTS</p>";
    } else {
        echo "<p style='color:red'>❌ Table 'contributions' does NOT exist — run sql/schema.sql in phpMyAdmin</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Error: " . $e->getMessage() . "</p>";
}

// 3. Show contributions table columns
try {
    $cols = $pdo->query("DESCRIBE contributions")->fetchAll(PDO::FETCH_ASSOC);
    echo "<p style='color:green'>✅ Columns in contributions table:</p><ul>";
    foreach ($cols as $col) {
        echo "<li><b>" . $col['Field'] . "</b> (" . $col['Type'] . ")</li>";
    }
    echo "</ul>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ Cannot describe table: " . $e->getMessage() . "</p>";
}

// 4. Try a test insert
try {
    $stmt = $pdo->prepare("INSERT INTO contributions (investor_id, amount, project_name) VALUES (?, ?, ?)");
    $stmt->execute([1, 99.99, 'Test Project']);
    $id = $pdo->lastInsertId();
    echo "<p style='color:green'>✅ Test INSERT worked! Row ID: $id</p>";

    // Clean up test row
    $pdo->prepare("DELETE FROM contributions WHERE id = ?")->execute([$id]);
    echo "<p style='color:green'>✅ Test row cleaned up</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>❌ INSERT failed: " . $e->getMessage() . "</p>";
}

echo "<hr><p><b>Visit:</b> <a href='add_contribution.php'>add_contribution.php</a></p>";
echo "<p style='color:orange'><b>Delete this file after debugging!</b></p>";
?>
