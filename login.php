<?php
// login.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db.php';
require_once 'includes/email_service.php';

$error = '';
$error_html = false; // true = contains safe HTML (developer-written links)

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        try {
            // Find user
            $stmt = $pdo->prepare("SELECT id, name, email, password, role, approval_status FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // AKKA SALPHAATTI SEENTANII TEST GOOTANIIF: 
            // Koodiin kun Hash (password_verify) fi Plain Text (@Biruk2217) lachuu eeyyama!
            if ($user && (password_verify($password, $user['password']) || $password === $user['password'])) {
                
                // Check email verification first
                if (!isEmailVerified($pdo, $email)) {
                    // Dev mode: auto-verify if no verification record exists at all
                    $stmt2 = $pdo->prepare("SELECT COUNT(*) FROM email_verification WHERE email = ?");
                    $stmt2->execute([$email]);
                    $has_any_record = $stmt2->fetchColumn();

                    if (!$has_any_record) {
                        // No record at all — auto-verify for localhost dev environment
                        $stmt2 = $pdo->prepare("INSERT IGNORE INTO email_verification (email, verification_code, expires_at, is_used) VALUES (?, 'AUTOVFY', DATE_ADD(NOW(), INTERVAL 1 YEAR), TRUE)");
                        $stmt2->execute([$email]);
                    } else {
                        $error = "Please verify your email address first. <a href='verify_email.php?email=" . urlencode($email) . "' class='alert-link'>Click here to verify</a>.";
                        $error_html = true;
                    }
                }

                if (empty($error) || isEmailVerified($pdo, $email)) {
                    if ($user['approval_status'] === 'pending') {
                        $error = "Your account is pending admin approval. Please wait for approval notification.";
                    } elseif ($user['approval_status'] === 'rejected') {
                        $error = "Your account registration has been rejected. Please contact support.";
                    } else {
                        // Email verified and approved, start session
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['user_name'] = $user['name'];
                        $_SESSION['user_role'] = $user['role'];

                        // Redirect based on role
                        if ($user['role'] === 'agri_expert') {
                            header("Location: expert_dashboard.php");
                        } else {
                            header("Location: dashboard.php");
                        }
                        exit();
                    }
                }
            } else {
                $error = "Invalid email or password.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Agri-Business Social Enterprise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
        }
        .login-card { 
            max-width: 450px; 
            margin-top: 80px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border-radius: 20px;
            padding: 40px;
        }
        .login-header {
            text-align: center;
            margin-bottom: 35px;
        }
        .login-logo {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 36px;
            font-weight: bold;
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        .login-title {
            font-size: 28px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        .login-subtitle {
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 0;
        }
        .form-control {
            border: 2px solid #e1e8ed;
            border-radius: 10px;
            padding: 12px 16px;
            font-size: 15px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        .form-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            border: none;
            border-radius: 10px;
            padding: 12px 20px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }
        .alert {
            border-radius: 10px;
            border: none;
            padding: 12px 16px;
        }
        .text-center a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
        }
        .text-center a:hover {
            text-decoration: underline;
        }
        .input-group-text {
            background: #f8f9fa;
            border: 2px solid #e1e8ed;
            border-right: none;
            border-radius: 10px 0 0 10px;
        }
        .input-group .form-control {
            border-radius: 0 10px 10px 0;
        }
    </style>
</head>
<body>
    <div class="container d-flex justify-content-center">
        <div class="card login-card shadow w-100">
            <div class="login-header">
                <div class="login-logo">AB</div>
                <h2 class="login-title">Welcome Back</h2>
                <p class="login-subtitle">Sign in to your Agri-Business account</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?php echo $error_html ? $error : htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" placeholder="Enter your email">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label">Password</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                        <input type="password" name="password" class="form-control" required placeholder="Enter your password">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100 mb-3">Sign In</button>
            </form>
            <div class="text-center">
                <p class="mb-2">Don't have an account? <a href="signup.php">Create account</a></p>
                <small class="text-muted">© 2024 Agri-Business Social Enterprise</small>
            </div>
        </div>
    </div>
</body>
</html>