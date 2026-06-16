<?php
// yields.php
require_once 'includes/db.php';
require_once 'includes/session.php';

checkLogin();
checkRole(['farmer', 'admin']);

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Mock yield data for the chart and table
$yield_data = [
    ['crop' => 'Tomatoes', 'month' => 'Jan', 'yield' => 120, 'target' => 150],
    ['crop' => 'Tomatoes', 'month' => 'Feb', 'yield' => 140, 'target' => 150],
    ['crop' => 'Tomatoes', 'month' => 'Mar', 'yield' => 165, 'target' => 150],
    ['crop' => 'Maize', 'month' => 'Jan', 'yield' => 500, 'target' => 450],
    ['crop' => 'Maize', 'month' => 'Feb', 'yield' => 480, 'target' => 450],
    ['crop' => 'Maize', 'month' => 'Mar', 'yield' => 520, 'target' => 450],
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yield Tracking - Agri-Biz</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { background-color: #f4f7f6; }
        .sidebar { min-height: 100vh; background-color: #2c3e50; color: white; padding: 20px; }
        .sidebar a { color: #bdc3c7; text-decoration: none; display: block; padding: 10px; border-radius: 5px; }
        .sidebar a:hover, .sidebar a.active { background-color: #34495e; color: white; }
        .main-content { padding: 30px; }
        .chart-container { position: relative; height: 300px; width: 100%; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-3 col-lg-2 sidebar">
                <h3 class="text-center mb-4">Agri-Biz</h3>
                <a href="index.php"><i class="bi bi-house me-2"></i> Home</a>
                <a href="dashboard.php"><i class="bi bi-speedometer2 me-2"></i> Dashboard</a>
                <a href="manage_crops.php"><i class="bi bi-tree me-2"></i> My Crops</a>
                <a href="yields.php" class="active"><i class="bi bi-graph-up me-2"></i> Track Yields</a>
                <a href="resource_library.php"><i class="bi bi-book me-2"></i> Resource Library</a>
                <hr>
                <a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right me-2"></i> Logout</a>
            </nav>

            <!-- Main Content -->
            <main class="col-md-9 col-lg-10 main-content">
                

                <div class="row g-4">
                    <div class="col-md-8">
                        <div class="card shadow-sm border-0 p-4 rounded-4 mb-4">
                            <header class="d-flex justify-content-between align-items-center mb-4">
    <h2>Yield Performance Tracking</h2>
    <a href="add_yield.php" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold">
        <i class="bi bi-plus-lg me-2"></i> New Harvest Record
    </a>
</header>
                            <div class="chart-container">
                                <canvas id="yieldChart"></canvas>
                            </div>
                        </div>

                        <div class="card shadow-sm border-0 p-4 rounded-4">
                            <h5 class="mb-4 fw-bold text-muted">Harvest History</h5>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Crop</th>
                                            <th>Month</th>
                                            <th>Actual Yield (kg)</th>
                                            <th>Target (kg)</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($yield_data as $row): ?>
                                            <tr>
                                                <td class="fw-bold"><?php echo htmlspecialchars($row['crop']); ?></td>
                                                <td><?php echo $row['month']; ?></td>
                                                <td><?php echo $row['yield']; ?> kg</td>
                                                <td class="text-muted"><?php echo $row['target']; ?> kg</td>
                                                <td>
                                                    <?php if ($row['yield'] >= $row['target']): ?>
                                                        <span class="badge bg-success-subtle text-success rounded-pill px-3">Above Target</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning-subtle text-warning rounded-pill px-3">Below Target</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card shadow-sm border-0 p-4 rounded-4 mb-4 bg-primary text-white">
                            <h5>Season Summary</h5>
                            <div class="mt-4">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Total Harvest</span>
                                    <span class="fw-bold">1,845 kg</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Productivity Index</span>
                                    <span class="fw-bold">88.5%</span>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span>Growth vs Last Month</span>
                                    <span class="fw-bold text-warning">+12%</span>
                                </div>
                            </div>
                        </div>

                        <div class="card shadow-sm border-0 p-4 rounded-4">
                            <h5 class="text-muted mb-4">Climate Alerts</h5>
                            <div class="alert alert-warning border-0 rounded-3 mb-3 small d-flex align-items-center">
                                <i class="bi bi-exclamation-triangle-fill fs-4 me-3"></i>
                                <div>High humidity predicted for next week. Risk of blight.</div>
                            </div>
                            <div class="alert alert-info border-0 rounded-3 small d-flex align-items-center">
                                <i class="bi bi-info-circle-fill fs-4 me-3"></i>
                                <div>Optimal soil moisture detected. Resume irrigation in 3 days.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        const ctx = document.getElementById('yieldChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Jan', 'Feb', 'Mar'],
                datasets: [
                    {
                        label: 'Tomatoes',
                        data: [120, 140, 165],
                        backgroundColor: '#ff6b6b',
                        borderRadius: 8
                    },
                    {
                        label: 'Maize',
                        data: [500, 480, 520],
                        backgroundColor: '#4ecdc4',
                        borderRadius: 8
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                },
                scales: {
                    y: { beginAtZero: true, grid: { display: false } },
                    x: { grid: { display: false } }
                }
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
