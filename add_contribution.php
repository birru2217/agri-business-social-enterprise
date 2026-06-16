<?php
// add_contribution.php
require_once 'includes/db.php';
require_once 'includes/session.php';

checkLogin();

$error = "";
$success = "";

// PHP Logic: Gaafa namni button 'Save' tuqu ragaan asitti qindaa'a
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $investor_id = $_SESSION['user_id']; // ID nama seenee
    $project_name = $_POST['project_name']; // HTML name="project_name" irraa dhufa
    $amount = $_POST['amount'];             // HTML name="amount" irraa dhufa

    if (!empty($project_name) && !empty($amount)) {
        try {
            // Maqaa column database keetii waliin tokko ta'uu qaba
            $sql = "INSERT INTO contributions (investor_id, amount, project_name) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$investor_id, $amount, $project_name]);

            header("Location: contributions.php?success=1");
            exit();
        } catch (PDOException $e) {
            $error = "Dogongora Database: " . $e->getMessage();
        }
    } else {
        $error = "Maaloo, bakka duwwaa hunda guuti!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>New Contribution</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f4f7f6; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
        .card { border-radius: 15px; border: none; }
        .btn-primary { background-color: #2c3e50; border: none; }
        .btn-primary:hover { background-color: #34495e; }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card shadow-lg p-4">
                <h3 class="text-center mb-4">Buusii Haaraa Galmeessi</h3>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <form action="add_contribution.php" method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Maqaa Project (Project Name)</label>
                        <input type="text" name="project_name" class="form-control" placeholder="Fkn: Sustainable Water Systems" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Hamma Qarshii (Amount $)</label>
                        <input type="number" step="0.01" name="amount" class="form-control" placeholder="Fkn: 500" required>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary rounded-pill p-2">
                            <i class="bi bi-save me-2"></i> Save Contribution
                        </button>
                        <a href="contributions.php" class="btn btn-light rounded-pill p-2">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

</body>
</html>