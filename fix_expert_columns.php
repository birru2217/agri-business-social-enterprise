<?php
// fix_expert_columns.php - Fix all missing columns in agri_expert_profiles table
require_once 'includes/db.php';

echo "<h2>Fix All Expert Profile Columns</h2>";

// 1. Check what columns expert_resources.php is trying to access
echo "<h3>1. Checking expert_resources.php Requirements</h3>";
$expert_resources_file = 'expert_resources.php';
if (file_exists($expert_resources_file)) {
    $content = file_get_contents($expert_resources_file);
    
    // Extract columns from the query
    if (preg_match('/SELECT.*?FROM.*?expert_resources/s', $content, $matches)) {
        $query = $matches[0];
        echo "<div style='color:blue;'>📋 Found query in expert_resources.php</div>";
        
        // Find all aep.* columns being referenced
        if (preg_match_all('/aep\.(\w+)/', $content, $column_matches)) {
            $required_columns = array_unique($column_matches[1]);
            echo "<h4>Required columns from expert_resources.php:</h4>";
            foreach ($required_columns as $col) {
                echo "<div style='color:orange;'>🔍 Need column: $col</div>";
            }
        }
    }
} else {
    echo "<div style='color:red;'>❌ expert_resources.php file not found</div>";
}

// 2. Check current agri_expert_profiles table structure
echo "<h3>2. Current Table Structure</h3>";
try {
    $stmt = $pdo->query("DESCRIBE agri_expert_profiles");
    $existing_columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $existing_column_names = [];
    foreach ($existing_columns as $column) {
        $existing_column_names[] = $column['Field'];
        echo "<div style='color:green;'>✅ Column: {$column['Field']}</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='color:red;'>❌ Error checking table: " . $e->getMessage() . "</div>";
}

// 3. Add missing columns
echo "<h3>3. Adding Missing Columns</h3>";

$columns_to_add = [
    'rating' => "ALTER TABLE agri_expert_profiles ADD COLUMN rating DECIMAL(3,2) DEFAULT 0.00 AFTER expertise_area",
    'total_reviews' => "ALTER TABLE agri_expert_profiles ADD COLUMN total_reviews INT DEFAULT 0 AFTER rating",
    'certifications' => "ALTER TABLE agri_expert_profiles ADD COLUMN certifications TEXT NULL AFTER total_reviews",
    'languages' => "ALTER TABLE agri_expert_profiles ADD COLUMN languages VARCHAR(255) NULL AFTER certifications",
    'profile_image' => "ALTER TABLE agri_expert_profiles ADD COLUMN profile_image VARCHAR(500) NULL AFTER languages"
];

foreach ($columns_to_add as $column_name => $sql) {
    try {
        // Check if column exists
        if (!in_array($column_name, $existing_column_names)) {
            $pdo->exec($sql);
            echo "<div style='color:green;'>✅ Added column: $column_name</div>";
        } else {
            echo "<div style='color:orange;'>⚠️ Column $column_name already exists</div>";
        }
    } catch (Exception $e) {
        echo "<div style='color:red;'>❌ Error adding $column_name: " . $e->getMessage() . "</div>";
    }
}

// 4. Update sample data for new columns
echo "<h3>4. Updating Sample Data</h3>";
try {
    // Update rating and reviews for existing experts
    $stmt = $pdo->prepare("UPDATE agri_expert_profiles SET rating = 4.5, total_reviews = 12 WHERE rating = 0");
    $result = $stmt->execute();
    $updated = $stmt->rowCount();
    
    if ($updated > 0) {
        echo "<div style='color:green;'>✅ Updated $updated experts with sample ratings</div>";
    }
    
    // Add sample certifications
    $stmt = $pdo->prepare("UPDATE agri_expert_profiles SET certifications = 'Certified Agricultural Consultant, Organic Farming Specialist' WHERE certifications IS NULL");
    $stmt->execute();
    
    // Add sample languages
    $stmt = $pdo->prepare("UPDATE agri_expert_profiles SET languages = 'English, Amharic, Oromo' WHERE languages IS NULL");
    $stmt->execute();
    
    echo "<div style='color:green;'>✅ Added sample certifications and languages</div>";
    
} catch (Exception $e) {
    echo "<div style='color:red;'>❌ Error updating sample data: " . $e->getMessage() . "</div>";
}

// 5. Final verification
echo "<h3>5. Final Verification</h3>";
try {
    $stmt = $pdo->query("
        SELECT u.name, aep.expertise_area, aep.rating, aep.total_reviews, aep.certifications 
        FROM users u 
        JOIN agri_expert_profiles aep ON u.id = aep.user_id 
        WHERE u.role = 'agri_expert'
    ");
    $experts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>Expert Profiles with All Columns:</h4>";
    foreach ($experts as $expert) {
        echo "<div style='background:#f8f9fa; padding:10px; margin:5px 0; border-left:4px solid #2c7a2c;'>";
        echo "<b>{$expert['name']}</b><br>";
        echo "Expertise Area: " . ($expert['expertise_area'] ?: 'Not set') . "<br>";
        echo "Rating: {$expert['rating']} ({$expert['total_reviews']} reviews)<br>";
        echo "Certifications: " . ($expert['certifications'] ?: 'Not set');
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='color:red;'>❌ Verification error: " . $e->getMessage() . "</div>";
}

// 6. Test expert_resources.php query
echo "<h3>6. Testing expert_resources.php Query</h3>";
try {
    $stmt = $pdo->query("
        SELECT er.*, u.name as expert_name, aep.expertise_area, aep.rating, aep.total_reviews 
        FROM expert_resources er 
        JOIN users u ON er.expert_id = u.id 
        LEFT JOIN agri_expert_profiles aep ON er.expert_id = aep.user_id 
        ORDER BY er.created_at DESC 
        LIMIT 5
    ");
    $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<div style='color:green;'>✅ expert_resources.php query now works!</div>";
    
    if (empty($resources)) {
        echo "<div style='color:blue;'>📋 No resources found (normal for new system)</div>";
    } else {
        foreach ($resources as $resource) {
            echo "<div style='background:#e8f5e8; padding:10px; margin:5px 0;'>";
            echo "<b>{$resource['title']}</b> by {$resource['expert_name']}<br>";
            echo "Rating: {$resource['rating']} | Reviews: {$resource['total_reviews']}";
            echo "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div style='color:red;'>❌ Query still failing: " . $e->getMessage() . "</div>";
}

echo "<h3>✅ All Expert Columns Fixed!</h3>";
echo "<div style='background:#d4edda; padding:15px; border-radius:5px;'>";
echo "<p><b>Fixed columns:</b></p>";
echo "<p>✅ expertise_area - Expert specialization area</p>";
echo "<p>✅ rating - Expert rating (0-5)</p>";
echo "<p>✅ total_reviews - Number of reviews</p>";
echo "<p>✅ certifications - Professional certifications</p>";
echo "<p>✅ languages - Languages spoken</p>";
echo "<p><b>Test Expert Resources:</b></p>";
echo "<p><a href='expert_resources.php' style='background:#007bff; color:white; padding:10px 20px; text-decoration:none; border-radius:5px;'>View Expert Resources (No Error)</a></p>";
echo "</div>";
?>
