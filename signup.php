<?php
// signup.php
require_once 'includes/db.php';
require_once 'includes/email_service.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];

    // Basic validation
    $allowed_roles = ['farmer', 'investor', 'customer', 'agri_expert'];
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $error = "Please fill in all fields.";
    } elseif (!in_array($role, $allowed_roles)) {
        $error = "Invalid role selected.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } else {
        // Hash password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "Email already registered.";
            } else {
                $phone    = $_POST['phone'] ?? '';
                $location = $_POST['location'] ?? '';

                // Insert user first
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role, phone, location, approval_status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
                if ($stmt->execute([$name, $email, $hashed_password, $role, $phone, $location])) {
                    // Try to create verification record (requires email_verification table)
                    try {
                        $verification_code = createVerificationRecord($pdo, $email);
                        sendVerificationEmail($email, $verification_code);
                    } catch (PDOException $ve) {
                        error_log("Verification table error: " . $ve->getMessage());
                    }
                    // Always redirect to verify_email page so user can see the OTP
                    header("Location: verify_email.php?email=" . urlencode($email));
                    exit();
                } else {
                    $error = "An error occurred inserting the user. Please try again.";
                }
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
            error_log("Signup DB error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup - Agri-Business Social Enterprise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .signup-card { max-width: 500px; margin-top: 50px; }
    </style>
</head>
<body>
    <div class="container d-flex justify-content-center">
        <div class="card signup-card shadow p-4 w-100">
            <h2 class="text-center mb-4">Join Our Agri-Network</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>

            <form method="POST" action="signup.php">
                <div class="mb-3">
                    <label class="form-label">Full Name</label>
                    <input type="text" name="name" class="form-control" required value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" name="phone" class="form-control" value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>" placeholder="+1234567890">
                </div>
                <div class="mb-3">
                    <label class="form-label">Location</label>
                    <input type="text" name="location" class="form-control" value="<?php echo isset($location) ? htmlspecialchars($location) : ''; ?>" placeholder="City, Country">
                </div>
                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Select Your Role</label>
                    <select name="role" class="form-select" required>
                        <option value="">Choose role...</option>
                        <option value="farmer">Farmer/Producer</option>
                        <option value="investor">Social Investor/Donor</option>
                        <option value="customer">Customer/Buyer</option>
                        <option value="agri_expert">Agricultural Expert</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary w-100">Register</button>
            </form>
            <div class="text-center mt-3">
                <p>Already have an account? <a href="login.php">Login here</a></p>
            </div>
        </div>
    </div>
</body>
</html>
