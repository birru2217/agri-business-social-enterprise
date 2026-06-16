<?php
// verify_email.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'includes/db.php';
require_once 'includes/email_service.php';

$error = '';
$success = '';
$email = '';

$email = $_GET['email'] ?? ($_POST['email'] ?? ($_POST['resend_email'] ?? ''));

// Auto-generate a fresh code when arriving from login page via ?email=
if (!empty($_GET['email']) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $auto_email = trim($_GET['email']);
    if (filter_var($auto_email, FILTER_VALIDATE_EMAIL)) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$auto_email]);
            if ($stmt->fetch()) {
                $new_code = createVerificationRecord($pdo, $auto_email);
                sendVerificationEmail($auto_email, $new_code);
                // success message will show the dev code box
            }
        } catch (PDOException $e) {
            $error = "Could not generate code: " . $e->getMessage();
        }
    }
}

// 2. Verification Code POST keessa jiraachuu isaa mirkaneessi (Line 14 fix)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verification_code'])) {
    
    $verification_code = trim($_POST['verification_code']);

    if (empty($email) || empty($verification_code)) {
        $error = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($verification_code) !== 6 || !is_numeric($verification_code)) {
        $error = "Verification code must be 6 digits.";
    } else {
        try {
            if (verifyEmailCode($pdo, $email, $verification_code)) {
                // Set approval_status based on role — agri_expert gets auto-approved
                $stmt = $pdo->prepare("SELECT role FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $user_row = $stmt->fetch(PDO::FETCH_ASSOC);
                $new_status = ($user_row && $user_row['role'] === 'agri_expert') ? 'approved' : 'pending';

                $stmt = $pdo->prepare("UPDATE users SET approval_status = ? WHERE email = ?");
                $stmt->execute([$new_status, $email]);

                if ($new_status === 'approved') {
                    $success = "Email verified! Your agri-expert account is approved. <a href='login.php' class='alert-link'>Login now</a>.";
                } else {
                    $success = "Email verified successfully! Your account is now pending admin approval.";
                }
                // Clear dev session code after successful verification
                unset($_SESSION['dev_verification_code'], $_SESSION['dev_verification_email']);
            } else {
                $error = "Invalid or expired verification code.";
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Handle resend verification code
if (isset($_POST['resend_code'])) {
    $resend_email = trim($_POST['resend_email']);
    $email = $resend_email;
    if (empty($resend_email)) {
        $error = "Please enter your email address.";
    } elseif (!filter_var($resend_email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        try {
            // Check if user exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$resend_email]);
            if ($stmt->fetch()) {
                // Create new verification code
                $new_code = createVerificationRecord($pdo, $resend_email);
                
                // Send verification email
                if (sendVerificationEmail($resend_email, $new_code)) {
                    $success = "New verification code sent to {$resend_email}. Please check your email.";
                    $email = $resend_email;
                } else {
                    $error = "Failed to send verification email. Please try again.";
                }
            } else {
                $error = "Email address not found in our system.";
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
    <title>Email Verification - Agri-Business</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        :root { --primary-green: #2c7a2c; --secondary-green: #48bb78; }
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .verification-card { 
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border-radius: 20px;
            padding: 40px;
            max-width: 500px;
            width: 100%;
        }
        .verification-header {
            text-align: center;
            margin-bottom: 35px;
        }
        .verification-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 36px;
            box-shadow: 0 10px 25px rgba(44, 122, 44, 0.3);
        }
        .verification-title {
            font-size: 24px;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        .verification-subtitle {
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
            border-color: var(--primary-green);
            box-shadow: 0 0 0 3px rgba(44, 122, 44, 0.1);
        }
        .form-label {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
            border: none;
            border-radius: 10px;
            padding: 12px 20px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(44, 122, 44, 0.3);
        }
        .btn-outline-secondary {
            border-radius: 10px;
            padding: 10px 16px;
            font-weight: 500;
        }
        .code-input {
            font-size: 24px;
            text-align: center;
            letter-spacing: 8px;
            font-weight: bold;
        }
        .alert {
            border-radius: 10px;
            border: none;
            padding: 12px 16px;
        }
        .info-box {
            background: #f8f9fa;
            border-left: 4px solid var(--primary-green);
            padding: 15px;
            border-radius: 0 8px 8px 0;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="verification-card">
        <div class="verification-header">
            <div class="verification-icon">
                <i class="bi bi-envelope-check"></i>
            </div>
            <h2 class="verification-title">Verify Your Email</h2>
            <p class="verification-subtitle">Enter the 6-digit code sent to your email address</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php
        // DEV MODE: Show verification code on screen since no mail server is available
        if (!empty($_SESSION['dev_verification_code']) && !empty($_SESSION['dev_verification_email'])):
        ?>
            <div class="alert alert-warning">
                <strong>⚠️ Dev Mode — No Mail Server:</strong><br>
                Code for <strong><?php echo htmlspecialchars($_SESSION['dev_verification_email']); ?></strong>:
                <span style="font-size:22px; font-weight:bold; letter-spacing:6px; color:#2c7a2c;">
                    <?php echo htmlspecialchars($_SESSION['dev_verification_code']); ?>
                </span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle me-2"></i><?php echo $success; ?>
                <div class="mt-2">
                    <a href="login.php" class="btn btn-success btn-sm">Proceed to Login</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!$success): ?>
            <div class="info-box">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Check your email inbox</strong> for a 6-digit verification code. 
                The code will expire in 15 minutes.
            </div>

            <form method="POST" action="verify_email.php">
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" placeholder="Enter your email">
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Verification Code</label>
                    <input type="text" name="verification_code" class="form-control code-input" required maxlength="6" placeholder="000000" pattern="[0-9]{6}">
                    <small class="text-muted">Enter the 6-digit code from your email</small>
                </div>
                
                <button type="submit" class="btn btn-primary w-100 mb-3">
                    <i class="bi bi-check-circle me-2"></i>Verify Email
                </button>
            </form>

            <hr class="my-4">

            <div class="text-center">
                <p class="mb-2">Didn't receive the code?</p>
                <form method="POST" action="verify_email.php" class="d-inline">
                    <input type="hidden" name="resend_email" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                    <button type="submit" name="resend_code" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-arrow-clockwise me-1"></i>Resend Code
                    </button>
                </form>
            </div>
        <?php endif; ?>

        <div class="text-center mt-4">
            <a href="login.php" class="text-decoration-none text-muted">
                <i class="bi bi-arrow-left me-1"></i>Back to Login
            </a>
        </div>
    </div>

    <script>
        // Auto-focus and format verification code input
        document.addEventListener('DOMContentLoaded', function() {
            const codeInput = document.querySelector('.code-input');
            if (codeInput) {
                codeInput.addEventListener('input', function(e) {
                    // Only allow numbers
                    this.value = this.value.replace(/[^0-9]/g, '');
                    
                    // Auto-submit when 6 digits entered
                    if (this.value.length === 6) {
                        const form = this.closest('form');
                        if (form) {
                            setTimeout(() => form.submit(), 500);
                        }
                    }
                });
                
                codeInput.focus();
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
