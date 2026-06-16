<?php
// fix_expertise_column.php - Fix expertise_area column error
require_once 'includes/db.php';

echo "<h2>Fix Expertise Area Column Error</h2>";

// 1. Check current agri_expert_profiles table structure
echo "<h3>1. Checking Current Table Structure</h3>";
try {
    $stmt = $pdo->query("DESCRIBE agri_expert_profiles");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>Current columns in agri_expert_profiles:</h4>";
    foreach ($columns as $column) {
        echo "<div style='color:blue;'>📋 {$column['Field']} ({$column['Type']})</div>";
    }
    
    // Check if expertise_area exists
    $has_expertise_area = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'expertise_area') {
            $has_expertise_area = true;
            break;
        }
    }
    
    if ($has_expertise_area) {
        echo "<div style='color:green;'>✅ expertise_area column already exists</div>";
    } else {
        echo "<div style='color:red;'>❌ expertise_area column missing</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='color:red;'>❌ Error checking table structure: " . $e->getMessage() . "</div>";
}

// 2. Add expertise_area column if it doesn't exist
echo "<h3>2. Adding expertise_area Column</h3>";
try {
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM agri_expert_profiles LIKE 'expertise_area'");
    if ($stmt->rowCount() == 0) {
        $pdo->exec("ALTER TABLE agri_expert_profiles ADD COLUMN expertise_area VARCHAR(255) NULL AFTER expertise");
        echo "<div style='color:green;'>✅ Added expertise_area column to agri_expert_profiles</div>";
    } else {
        echo "<div style='color:orange;'>⚠️ expertise_area column already exists</div>";
    }
} catch (Exception $e) {
    echo "<div style='color:red;'>❌ Error adding expertise_area column: " . $e->getMessage() . "</div>";
}

// 3. Update expertise_area with data from expertise column
echo "<h3>3. Updating expertise_area Data</h3>";
try {
    $stmt = $pdo->prepare("UPDATE agri_expert_profiles SET expertise_area = expertise WHERE expertise_area IS NULL AND expertise IS NOT NULL");
    $result = $stmt->execute();
    $updated = $stmt->rowCount();
    
    if ($updated > 0) {
        echo "<div style='color:green;'>✅ Updated $updated records with expertise_area data</div>";
    } else {
        echo "<div style='color:orange;'>⚠️ No records needed updating</div>";
    }
} catch (Exception $e) {
    echo "<div style='color:red;'>❌ Error updating expertise_area: " . $e->getMessage() . "</div>";
}

// 4. Verify the fix
echo "<h3>4. Verification</h3>";
try {
    $stmt = $pdo->query("
        SELECT u.name, ap.expertise, ap.expertise_area 
        FROM users u 
        JOIN agri_expert_profiles ap ON u.id = ap.user_id 
        WHERE u.role = 'agri_expert'
    ");
    $experts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>Expert Profiles:</h4>";
    foreach ($experts as $expert) {
        echo "<div style='background:#f8f9fa; padding:10px; margin:5px 0; border-left:4px solid #2c7a2c;'>";
        echo "<b>{$expert['name']}</b><br>";
        echo "Expertise: " . ($expert['expertise'] ?: 'Not set') . "<br>";
        echo "Expertise Area: " . ($expert['expertise_area'] ?: 'Not set');
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='color:red;'>❌ Error verifying fix: " . $e->getMessage() . "</div>";
}

// 5. Test expert_resources.php query
echo "<h3>5. Testing expert_resources.php Query</h3>";
try {
    $stmt = $pdo->query("
        SELECT er.*, u.name as expert_name, aep.expertise_area 
        FROM expert_resources er 
        JOIN users u ON er.expert_id = u.id 
        LEFT JOIN agri_expert_profiles aep ON er.expert_id = aep.user_id 
        ORDER BY er.created_at DESC 
        LIMIT 5
    ");
    $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div style='color:green;'>✅ Query executed successfully</div>";
    echo "<h4>Sample Resources:</h4>";
    
    if (empty($resources)) {
        echo "<div style='color:orange;'>⚠️ No resources found (this is normal for new system)</div>";
    } else {
        foreach ($resources as $resource) {
            echo "<div style='background:#e8f5e8; padding:10px; margin:5px 0;'>";
            echo "<b>{$resource['title']}</b> by {$resource['expert_name']}<br>";
            echo "Expertise Area: " . ($resource['expertise_area'] ?: 'Not set');
            echo "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div style='color:red;'>❌ Query test failed: " . $e->getMessage() . "</div>";
}

echo "<h3>✅ Expertise Column Fix Complete!</h3>";
echo "<div style='background:#d4edda; padding:15px; border-radius:5px;'>";
echo "<p><b>What was fixed:</b></p>";
echo "<p>✅ Added expertise_area column to agri_expert_profiles table</p>";
echo "<p>✅ Updated existing records with expertise data</p>";
echo "<p>✅ Fixed expert_resources.php query error</p>";
echo "<p><b>Test Expert Resources:</b></p>";
echo "<p><a href='expert_resources.php' style='background:#007bff; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>View Expert Resources</a></p>";
echo "<p><a href='expert_dashboard.php' style='background:#28a745; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>Expert Dashboard</a></p>";
echo "</div>";
?>
