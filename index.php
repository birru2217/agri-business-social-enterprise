<?php
// index.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agri-Biz Social Enterprise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        .hero { background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('https://images.unsplash.com/photo-1500382017468-9049fed747ef?ixlib=rb-1.2.1&auto=format&fit=crop&w=1920&q=80'); height: 100vh; background-size: cover; background-position: center; color: white; display: flex; align-items: center; justify-content: center; text-align: center; }
        .btn-custom { padding: 12px 30px; border-radius: 50px; font-weight: bold; transition: all 0.3s; }
        .feature-card { border: none; transition: transform 0.3s; border-radius: 15px; }
        .feature-card:hover { transform: translateY(-10px); }
        .navbar-brand { font-weight: 800; font-size: 1.5rem; }
        .navbar { z-index: 1050 !important; }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#">Agri-Biz</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item"><a class="nav-link" href="#features">Features</a></li>
                    <li class="nav-item"><a class="nav-link" href="impact_reports.php">Social Impact</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item"><a class="btn btn-primary btn-custom ms-2" href="dashboard.php">Go to Dashboard</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                        <li class="nav-item"><a class="btn btn-success btn-custom ms-2" href="signup.php">Join Now</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <h1 class="display-2 fw-bold mb-4">Empowering Smallholder Farmers</h1>
            <p class="lead mb-5 fs-4">A social enterprise platform bridging the gap between farmers, investors, and consumers through sustainable agriculture.</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="signup.php" class="btn btn-success btn-lg btn-custom shadow-lg">Start Farming</a>
                <a href="impact_reports.php" class="btn btn-outline-light btn-lg btn-custom shadow-lg">See Our Impact</a>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-5 bg-light">
        <div class="container py-5">
            <div class="row text-center mb-5">
                <div class="col-md-8 offset-md-2">
                    <h2 class="fw-bold">How it Works</h2>
                    <p class="text-muted">A complete ecosystem designed for social good and economic growth.</p>
                </div>
            </div>
         <div class="row g-4">

                <div class="col-md-4">
    <!-- LINKII: Kaardii guutuu kana gara marketplace.php-tti hidheera -->
    <a href="marketplace.php" class="text-decoration-none text-dark d-block h-100">
        <div class="card feature-card shadow-sm h-100 text-center position-relative overflow-hidden">
            
            <!-- Suuraa kee (Padding irraa bilisa kan ta'e) -->
            <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTVS3DOzFjoK2tKalqKayqcO_4-AgNl8PZHVA&s" class="card-img-top" alt="Marketplace" style="height: 180px; object-fit: cover;">

            <!-- KAN SIRREEFFAME: p-4 as keessa galeera, kanaaf barreeffamni hundi qulqulluun mul'ata -->
            <div class="card-body p-4">
                <i class="bi bi-shop fs-1 text-success mb-3 d-block"></i>
                <h4 class="fw-bold">Marketplace</h4>
                <p class="text-muted mb-0">Directly buy fresh produce from farmers, ensuring they get fair prices and you get quality crops.</p>
            </div>
            
        </div>
    </a>
</div>
                <div class="col-md-4">
    <div class="card feature-card shadow-sm h-100 text-center position-relative overflow-hidden">
        
        <?php
        // Fakeenyaf: Dataabeesii keessaa yoo gabaasni balaa jiraate True ta'a
        $disaster_alert = true; 
        $affected_area = "Bule Hora";
        $crop_impacted = "Buna (Coffee)";

        if ($disaster_alert): 
        ?>
            <!-- Beeksisa Tasaa (Emergency Badge) -->
            <!-- Beeksisa Ariifachiisaa Qonnaan Bulaaf (Warning Badge - Kan Sirreeffame) -->

<span class="position-absolute top-0 start-50 translate-middle-x mt-2 badge rounded-pill bg-warning text-dark" style="z-index: 10;">

    ⚠️    CLIMATE ALERT  

</span>
        <?php endif; ?>

        <!-- Suuraa Gabaasa Dhiibbaa Agarsiisu -->
        <img src="https://images.unsplash.com/photo-1551836022-d5d88e9218df?auto=format&fit=crop&w=600&q=80" class="card-img-top" alt="Annual Data Reports" style="height: 180px; object-fit: crop;">

        <div class="card-body p-4">
            <i class="bi bi-graph-up-arrow fs-1 text-primary mb-3 d-block"></i>
            <h4 class="fw-bold">Impact Tracking</h4>
            
            <?php if ($disaster_alert): ?>
                <!-- Yoo jijjiiramni qilleensaa dhiibbaa uume kana agarsiisi -->
                <p class="text-danger fw-bold mb-1">⚠️ Dhiibbaa Qilleensaa Tasaa:</p>
                <p class="small text-muted mb-3">
                    Naannoo <strong><?php echo $affected_area; ?></strong> keessatti oomisha <strong><?php echo $crop_impacted; ?></strong> irratti dhiibbaan uumameera.
                </p>
                <a href="impact_reports.php" class="btn btn-sm btn-danger w-100">Gabaasa Balaa Ilaali</a>
            <?php else: ?>
                <!-- Yoo haalli jireenyaa idilee ta'e isa duraan ture agarsiisa -->
                <p class="text-muted">Investors can see exactly how their contributions are helping local communities and reducing poverty.</p>
            <?php endif; ?>
        </div>

    </div>
</div>
<div class="col-md-4">
    <div class="card feature-card shadow-sm h-100 text-center position-relative overflow-hidden">
        
        <?php
        // Fakeenyaf: Yoo jijjiiramni qilleensaa jiraate true ta'a
        $climate_issue = true; 
        $emergency_guide = "Hongee Keessatti Buna Bulchuuf";

        if ($climate_issue): 
        ?>
            <!-- Beeksisa Ariifachiisaa Qonnaan Bulaaf (Warning Badge) -->
           <!-- Beeksisa Ariifachiisaa Qonnaan Bulaaf (Warning Badge - Kan Sirreeffame) -->
<span class="position-absolute top-0 start-50 translate-middle-x mt-2 badge rounded-pill bg-warning text-dark" style="z-index: 10;">
    ⚠️ ADVISORY ACTIVE
</span>
        <?php endif; ?>

        <!-- KAN BAKKA BU'E: Suuraa haaraa ati ergite as jalatti jijjiirameera -->
        <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcS-Wnio2vdBCQM6Oag6R9hFq026Rc8ehNIqu0qL_bhKPiNcbmFxS24_Uk0&s" class="card-img-top" alt="Resource Library Guide" style="height: 180px; object-fit: cover;">

        <div class="card-body p-4">
            <i class="bi bi-book fs-1 text-warning mb-3 d-block"></i>
            <h4 class="fw-bold">Resource Library</h4>
            
            <?php if ($climate_issue): ?>
                <!-- Yoo jijjiiramni qilleensaa tasa dhufe gorsa kana dursa hiriiri -->
                <p class="text-warning fw-bold mb-1">💡 Gorsa Ariifachiisaa:</p>
                <p class="small text-muted mb-3">
                    Qajeelfama haaraa <strong>"<?php echo $emergency_guide; ?>"</strong> jedhu dubbisuun oomisha kee baraari.
                </p>
                <a href="resource_library.php" class="btn btn-sm btn-warning text-dark fw-bold w-100">Qajeelfama Dubbisi</a>
            <?php else: ?>
                <!-- Haala idilee irratti barruu kanaan dura ture agarsiisa -->
                <p class="text-muted">Farmers get access to "Best Practices" and "Climate-Smart Farming" guides to improve their yields.</p>
            <?php endif; ?>
        </div>

    </div>
</div>

                </div>
                
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-white py-5">
        <div class="container text-center">
            <h3 class="fw-bold mb-4">Agri-Biz</h3>
            <p class="mb-4">Building a sustainable future for agriculture, one farmer at a time.</p>
            <div class="d-flex justify-content-center gap-3 fs-3 mb-4">
                <i class="bi bi-facebook"></i>
                <i class="bi bi-twitter"></i>
                <i class="bi bi-instagram"></i>
                <i class="bi bi-linkedin"></i>
            </div>
            <hr class="w-50 mx-auto my-4 opacity-25">
            <p class="small text-muted">&copy; 2026 Agri-Biz Social Enterprise. All rights reserved.</p>
        </div>
        <div class="container text-center">
            <span class="text-muted-50">Crafted with ❤️ and Innovation</span> <br>
            <small class="text-uppercase tracking-wider" style="font-size: 0.75rem; letter-spacing: 1px;">
                Developed by <span class="text-info fw-bold">Enterprise Development Team</span>
            </small>
        </p></div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const nav = document.querySelector('nav');
            if (window.scrollY > 50) {
                nav.classList.add('bg-dark', 'shadow');
            } else {
                nav.classList.remove('bg-dark', 'shadow');
            }
        });
    </script>
</body>
</html>
