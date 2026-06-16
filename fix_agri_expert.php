<?php
// fix_agri_expert.php
// One-click fix for existing agri_expert accounts that are stuck

// --- KAN DABALAME: Dogoggorri yoo jiraate dhoofsamuu dhiisee iskiriinii irratti akka bahu godha ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'includes/db.php';

echo "<!DOCTYPE html><html><head>
<title>Fix Agri Expert Accounts</title>
<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>
</head><body class='p-4'>";

echo "<h2>🔧 Fix Agri Expert Accounts</h2>";

try {
    // Step 0: KAN DABALAME — Data duraan ENUM haaraa ala jiru temporary sirreessa (Crash akka hin uumamneef)
    echo "<h5>Step 0: Pre-checking data consistency...</h5>";
    $pdo->exec("UPDATE users SET role = 'customer' WHERE role NOT IN ('farmer','investor','customer','admin','agri_expert') OR role = '' OR role IS NULL");
    echo "<div class='alert alert-info'>✅ Pre-check complete (Cleaned bad values)</div>";

    // Step 1: Fix ENUM — ensure agri_expert is a valid role value
    echo "<h5>Step 1: Checking role ENUM...</h5>";
    $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('farmer','investor','customer','admin','agri_expert') NOT NULL");
    echo "<div class='alert alert-success'>✅ role ENUM updated to include agri_expert</div>";

    // Step 2: Fix any accounts where role saved as empty string (ENUM mismatch)
    $stmt = $pdo->query("SELECT id, name, email, role FROM users WHERE role = '' OR role IS NULL");
    $broken = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($broken) {
        echo "<h5>Step 2: Found " . count($broken) . " broken role accounts:</h5><ul>";
        foreach ($broken as $u) {
            echo "<li>" . htmlspecialchars($u['name']) . " (" . htmlspecialchars($u['email']) . ") — role: '" . htmlspecialchars($u['role']) . "'</li>";
        }
        echo "</ul><div class='alert alert-warning'>⚠️ These need manual role assignment. Update them in phpMyAdmin.</div>";
    } else {
        echo "<div class='alert alert-success'>✅ No broken role accounts found</div>";
    }

    // Step 3: Ensure email_verification table exists
    echo "<h5>Step 3: Ensuring email_verification table...</h5>";
    $pdo->exec("CREATE TABLE IF NOT EXISTS email_verification (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(100) NOT NULL UNIQUE,
        verification_code VARCHAR(10) NOT NULL,
        expires_at TIMESTAMP NOT NULL,
        is_used BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email (email)
    )");
    echo "<div class='alert alert-success'>✅ email_verification table ready</div>";

    // Step 4: Auto-verify + approve all agri_expert accounts
    echo "<h5>Step 4: Fixing agri_expert accounts...</h5>";
    $stmt = $pdo->query("SELECT id, name, email, role, approval_status FROM users WHERE role = 'agri_expert'");
    $experts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($experts)) {
        echo "<div class='alert alert-warning'>⚠️ No agri_expert accounts found in database.<br>
        <strong>This means the role ENUM was missing 'agri_expert' when you registered.</strong><br>
        Please register again at <a href='signup.php'>signup.php</a> — it will work now.</div>";
    } else {
        foreach ($experts as $expert) {
            // Auto-verify email
            $pdo->prepare("INSERT IGNORE INTO email_verification (email, verification_code, expires_at, is_used)
                VALUES (?, 'AUTOVFY', DATE_ADD(NOW(), INTERVAL 1 YEAR), TRUE)")
                ->execute([$expert['email']]);

            // Auto-approve
            $pdo->prepare("UPDATE users SET approval_status = 'approved' WHERE id = ?")
                ->execute([$expert['id']]);

            echo "<div class='alert alert-success'>
                ✅ Fixed: <strong>" . htmlspecialchars($expert['name']) . "</strong>
                (" . htmlspecialchars($expert['email']) . ")
                — was: <em>" . htmlspecialchars($expert['approval_status']) . "</em> → now: <strong>approved</strong>
            </div>";
        }
    }

    // Step 5: Show all users summary
    echo "<h5>Step 5: Current users in database:</h5>";
    $all = $pdo->query("SELECT id, name, email, role, approval_status FROM users ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    echo "<table class='table table-bordered table-sm'><thead><tr>
        <th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th>
    </tr></thead><tbody>";
    foreach ($all as $u) {
        $role_color = $u['role'] === 'agri_expert' ? 'success' : 'secondary';
        $status_color = $u['approval_status'] === 'approved' ? 'success' : ($u['approval_status'] === 'rejected' ? 'danger' : 'warning');
        $u_status = isset($u['approval_status']) ? htmlspecialchars($u['approval_status']) : 'N/A';
        echo "<tr>
            <td>{$u['id']}</td>
            <td>" . htmlspecialchars($u['name']) . "</td>
            <td>" . htmlspecialchars($u['email']) . "</td>
            <td><span class='badge bg-{$role_color}'>" . htmlspecialchars($u['role']) . "</span></td>
            <td><span class='badge bg-{$status_color}'>{$u_status}</span></td>
        </tr>";
    }
    echo "</tbody></table>";

} catch (PDOException $e) {
    echo "<div class='alert alert-danger'>❌ SQL Error: " . htmlspecialchars($e->getMessage()) . "</div>";
} catch (Exception $e) {
    echo "<div class='alert alert-danger'>❌ General Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "<hr><div class='d-flex gap-2'>
    <a href='signup.php' class='btn btn-primary'>Register New Expert</a>
    <a href='login.php' class='btn btn-success'>Go to Login</a>
    <a href='dashboard.php' class='btn btn-secondary'>Dashboard</a>
</div>";

echo "</body></html>";
?>