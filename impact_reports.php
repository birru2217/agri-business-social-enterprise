<?php
/**
 * impact_reports.php
 * Dashboard showing social metrics and farmer income trends.
 */

// 1. Load database and session management
require_once 'includes/db.php';
require_once 'includes/session.php';

// 2. Fetch social metrics from the database
try {
    $stmt = $pdo->query("SELECT * FROM impact_reports ORDER BY updated_at DESC");
    $metrics = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $metrics = [];
}

// 3. Provide fallback mock data if the database table is empty
if (empty($metrics)) {
    $metrics = [
       [
            'metric_name' => 'Farmers Supported', 
            'value' => 1250, 
            'unit' => 'Smallholders', 
            'description' => 'Total number of smallholder farmers registered and active.',
            'image' => 'https://png.pngtree.com/thumb_back/fh260/background/20240716/pngtree-a-beautiful-female-customer-buying-sustainable-organic-vegetables-image_16014743.jpg'
        ],
        [
            'metric_name' => 'Carbon Offset', 
            'value' => 340, 
            'unit' => 'Tons', 
            'description' => 'Estimated carbon sequestered through climate-smart farming.',
            'image' => 'https://be-cis.com/wp-content/uploads/2023/12/view-green-forest-trees-with-co2-scaled.webp'
        ],
        [
            'metric_name' => 'Poverty Reduction', 
            'value' => 15.5, 
            'unit' => '%', 
            'description' => 'Average increase in annual income for our registered farmers.',
            'image' => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTVS3DOzFjoK2tKalqKayqcO_4-AgNl8PZHVA&s'
        ],
        [
            'metric_name' => 'Land Cultivated', 
            'value' => 5000, 
            'unit' => 'Acres', 
            'description' => 'Total land area using sustainable agricultural practices.',
            'image' => 'https://tse4.mm.bing.net/th/id/OIP.hABPU7IqLiLkZ7ZtRIAVTwAAAA?cb=thfvnextfalcon&pid=ImgDet&w=194&h=139&c=7&o=7&rm=3'
      ] 
      ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impact Report - Agri-Business Social Enterprise</title>
    <!-- Bootstrap 5 & Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Chart.js for data visualization -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --primary-gradient: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%); }
        body { background-color: #f8f9fa; }
        .impact-header { background: var(--primary-gradient); color: white; padding: 80px 0; }
        .metric-card { border-radius: 15px; border: none; transition: all 0.3s ease; }
        .metric-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .chart-container { background: white; border-radius: 15px; padding: 25px; box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="dashboard.php"><i class="bi bi-tree-fill text-success me-2"></i>Agri-Biz</a>
            <div class="ms-auto">
                <a href="index.php" class="btn btn-outline-light btn-sm me-2">Home</a>
                <a href="dashboard.php" class="btn btn-outline-light btn-sm rounded-pill px-3">Back to Dashboard</a>
            </div>
        </div>
    </nav>

    <!-- Header Section -->
    <header class="impact-header text-center">
        <div class="container">
            <h1 class="display-4 fw-bold">Social Impact Dashboard</h1>
            <p class="lead">Transparency in every harvest. Tracking our collective contribution to sustainable agriculture.</p>
        </div>
    </header>

    <div class="container my-5">
        
        <!-- SECTION 1: Fresh Produce Marketplace Cards (Suuraawwan dabalataa wajjin) -->
        <h3 class="fw-bold mb-4 text-dark"><i class="bi bi-shop text-success me-2"></i>Our Marketplace Impact</h3>
        <div class="row g-4 mb-5">
            <?php foreach ($metrics as $metric): ?>
                <div class="col-md-4">
                    <a href="marketplace.php" class="text-decoration-none text-dark d-block h-100">
                        <div class="card metric-card shadow-sm h-100 text-center position-relative overflow-hidden">
                            <!-- Suuraa Array keessaa fidu -->
                            <img src="<?php echo $metric['image']; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($metric['metric_name']); ?>" style="height: 180px; object-fit: cover;">
                            <div class="card-body p-4">
                                <i class="bi bi-shop fs-2 text-success mb-2 d-block"></i>
                                <h5 class="fw-bold"><?php echo htmlspecialchars($metric['metric_name']); ?></h5>
                                <p class="text-muted small mb-0"><?php echo htmlspecialchars($metric['description']); ?></p>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

        <hr class="my-5">

        <!-- SECTION 2: Numeric Metrics Grid -->
        <h3 class="fw-bold mb-4 text-dark"><i class="bi bi-shield-check text-primary me-2"></i>Key Performance Metrics</h3>
        <div class="row g-4 mb-5">
            <?php foreach ($metrics as $metric): ?>
                <div class="col-md-6 col-lg-3">
                    <div class="card metric-card shadow-sm h-100 p-4 text-center">
                        <div class="mb-3">
                            <i class="bi bi-graph-up-arrow fs-1 text-primary"></i>
                        </div>
                        <h6 class="text-muted text-uppercase fw-bold small"><?php echo htmlspecialchars($metric['metric_name']); ?></h6>
                        <h2 class="fw-bold text-dark">
                            <?php echo number_format($metric['value'], ($metric['unit'] === '%' ? 1 : 0)); ?>
                            <small class="fs-6 text-muted"><?php echo htmlspecialchars($metric['unit']); ?></small>
                        </h2>
                        <p class="mt-2 small text-muted"><?php echo htmlspecialchars($metric['description']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Trend Chart Section -->
        <div class="row">
            <div class="col-lg-10 offset-lg-1">
                <div class="chart-container">
                    <h4 class="fw-bold mb-4"><i class="bi bi-activity text-primary me-2"></i>Growth of Farmer Income (Trend)</h4>
                    <div style="height: 400px;">
                        <canvas id="impactChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container text-center">
            <p class="mb-0 small opacity-75">&copy; 2026 Agri-Business Social Enterprise. All rights reserved.</p>
        </div>
    </footer>

    <!-- Chart Script -->
    <script>
        const ctx = document.getElementById('impactChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul'],
                datasets: [{
                    label: 'Avg. Farmer Income (USD)',
                    data: [120, 150, 180, 210, 250, 280, 320],
                    borderColor: '#2a5298',
                    backgroundColor: 'rgba(42, 82, 152, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 6,
                    pointBackgroundColor: '#fff',
                    pointBorderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true, position: 'top' }
                },
                scales: {
                    y: { 
                        beginAtZero: true,
                        grid: { borderDash: [5, 5] },
                        ticks: { callback: function(value) { return '$' + value; } }
                    },
                    x: { grid: { display: false } }
                }
            }
        });
    </script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>