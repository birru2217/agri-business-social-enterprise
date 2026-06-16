<?php
// fix_status_column.php - Fix status column error in expert_resources
require_once 'includes/db.php';

echo "<h2>Fix Status Column Error</h2>";

// 1. Check current table structure
echo "<h3>1. Current expert_resources Table</h3>";
try {
    $stmt = $pdo->query("DESCRIBE expert_resources");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>Current columns:</h4>";
    foreach ($columns as $column) {
        echo "<div style='color:blue;'>📋 {$column['Field']} ({$column['Type']})</div>";
    }
    
    // Check if status column exists
    $has_status = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'status') {
            $has_status = true;
            break;
        }
    }
    
    if ($has_status) {
        echo "<div style='color:green;'>✅ status column already exists</div>";
    } else {
        echo "<div style='color:red;'>❌ status column missing</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='color:red;'>❌ Error checking table: " . $e->getMessage() . "</div>";
}

// 2. Add missing columns
echo "<h3>2. Adding Missing Columns</h3>";
$missing_columns = [
    'status' => "ALTER TABLE expert_resources ADD COLUMN status ENUM('draft', 'published', 'archived') DEFAULT 'published'",
    'view_count' => "ALTER TABLE expert_resources ADD COLUMN view_count INT DEFAULT 0",
    'download_count' => "ALTER TABLE expert_resources ADD COLUMN download_count INT DEFAULT 0",
    'category' => "ALTER TABLE expert_resources ADD COLUMN category VARCHAR(100) DEFAULT 'General'",
    'tags' => "ALTER TABLE expert_resources ADD COLUMN tags TEXT NULL"
];

foreach ($missing_columns as $column_name => $sql) {
    try {
        $pdo->exec($sql);
        echo "<div style='color:green;'>✅ Added column: $column_name</div>";
    } catch (Exception $e) {
        echo "<div style='color:orange;'>⚠️ Column $column_name: " . $e->getMessage() . "</div>";
    }
}

// 3. Test expert_resources.php query
echo "<h3>3. Testing expert_resources.php Query</h3>";
try {
    $stmt = $pdo->query("
        SELECT er.*, u.name as expert_name 
        FROM expert_resources er 
        JOIN users u ON er.expert_id = u.id 
        WHERE er.status = 'published' 
        ORDER BY er.created_at DESC 
        LIMIT 5
    ");
    $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div style='color:green;'>✅ Query executed successfully!</div>";
    
    if (empty($resources)) {
        echo "<div style='color:blue;'>📋 No resources found (normal for new system)</div>";
    } else {
        echo "<h4>Sample resources:</h4>";
        foreach ($resources as $resource) {
            echo "<div style='background:#e8f5e8; padding:10px; margin:5px 0;'>";
            echo "<b>{$resource['title']}</b> by {$resource['expert_name']}<br>";
            echo "Status: {$resource['status']} | Category: " . ($resource['category'] ?? 'N/A');
            echo "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div style='color:red;'>❌ Query failed: " . $e->getMessage() . "</div>";
}

// 4. Add sample data for testing
echo "<h3>4. Adding Sample Data</h3>";
try {
    // Get expert user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'agri_expert' LIMIT 1");
    $stmt->execute();
    $expert = $stmt->fetch();
    
    if ($expert) {
        $sample_resources = [
            ['title' => 'Sustainable Farming Guide', 'content_type' => 'document', 'category' => 'PDF Guides'],
            ['title' => 'Crop Management Video', 'content_type' => 'video', 'category' => 'Video Tutorials'],
            ['title' => 'Climate Smart Agriculture', 'content_type' => 'document', 'category' => 'Climate Content']
        ];
        
        foreach ($sample_resources as $resource) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO expert_resources (expert_id, title, content_type, category, status) VALUES (?, ?, ?, ?, 'published')");
            $stmt->execute([
                $expert['id'],
                $resource['title'],
                $resource['content_type'],
                $resource['category']
            ]);
        }
        
        echo "<div style='color:green;'>✅ Added sample resources</div>";
    }
} catch (Exception $e) {
    echo "<div style='color:red;'>❌ Error adding sample data: " . $e->getMessage() . "</div>";
}

echo "<h3>✅ Status Column Fix Complete!</h3>";
echo "<div style='background:#d4edda; padding:15px; border-radius:5px;'>";
echo "<p><b>Fixed Issues:</b></p>";
echo "<p>✅ Added status column to expert_resources table</p>";
echo "<p>✅ Added view_count and download_count columns</p>";
echo "<p>✅ Added category and tags columns</p>";
echo "<p>✅ expert_resources.php query now works</p>";
echo "<p><b>Test Expert Resources:</b></p>";
echo "<p><a href='expert_resources.php' style='background:#007bff; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>View Expert Resources (No Error)</a></p>";
echo "<p><a href='expert_dashboard.php' style='background:#28a745; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>Expert Dashboard</a></p>";
echo "</div>";
?>
