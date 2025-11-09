<?php
// Assuming config.php defines necessary paths/functions and header.php starts the HTML document
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/header.php';

// Define the content for easy editing later
$company_name = "SweepXpress";
$established_year = 2020; // Example
$mission = "Our mission is to empower every individual and business to create a spotless, healthy, and vibrant environment. We achieve this by meticulously curating and supplying the most effective and innovative cleaning tools available.";
$vision = "To be the Philippines' most trusted and leading provider of high-quality cleaning supplies, known for our commitment to customer satisfaction and environmental sustainability.";

// --- CONFIGURATION FOR HERO BANNER IMAGE ---
// Use an image appropriate for an 'About Us' page (e.g., a team, or a high-quality product shot).
$about_banner_image_path = '/sweepxpress/assets/Banner1.jpg';; // **Single Image Path**
// --------------------------------------------
?>

<div class="hero-section text-white d-flex align-items-center justify-content-center"
     style="background: linear-gradient(rgba(0,0,0,0.6), rgba(0,0,0,0.6)), url('<?php echo $about_banner_image_path; ?>') no-repeat center center;
            background-size: cover;
            height: 450px; /* Slightly taller for 'About Us' */
            margin-bottom: 3rem;">
    <div class="text-center">
        <h1 class="display-4 fw-bold mb-3">OUR STORY: CLEAN STARTS WITH US</h1>
        <p class="lead mb-4">Discover the passion, commitment, and expertise behind **SweepXpress**.</p>
        <a href="/sweepxpress/contact.php" class="btn btn-lg btn-light border-0"
           style="background-color: #f7b733; color: #388e3c; font-weight: bold;">
            MEET THE TEAM
        </a>
    </div>
</div>
<div class="container my-5">
    <div class="row text-center mb-5">
        <div class="col-md-8 mx-auto">
            <h2 class="fw-bold text-success mb-3">A Local Company with a Global Vision for Cleanliness</h2>
            <p class="lead text-muted">
                Founded in **<?php echo $established_year; ?>**, **<?php echo $company_name; ?>** was created to fill a gap in the market for truly **high-quality, long-lasting** cleaning supplies. Our journey is driven by the belief that better tools lead to a better quality of life.
            </p>
        </div>
    </div>
    
    <div class="row mb-5">
        <div class="col-md-6 mb-4">
            <div class="card h-100 border-0 shadow-lg" style="border-top: 5px solid #388e3c;">
                <div class="card-body">
                    <h3 class="card-title text-center text-success mb-4">🎯 Our Mission</h3>
                    <p class="card-text text-center"><?php echo $mission; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-6 mb-4">
            <div class="card h-100 border-0 shadow-lg" style="border-top: 5px solid #f7b733;">
                <div class="card-body">
                    <h3 class="card-title text-center" style="color: #f7b733;">🌟 Our Vision</h3>
                    <p class="card-text text-center"><?php echo $vision; ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center mb-4">
        <h2 class="text-success mb-4">What Makes Us Different</h2>
    </div>
    <div class="row g-4">
        <div class="col-lg-4">
            <div class="p-4 border rounded shadow-sm text-center h-100">
                <i class="fas fa-hand-holding-usd fa-3x mb-3 text-success"></i>
                <h4 class="fw-bold">Value & Quality</h4>
                <p>We source only products that meet high standards of **durability and effectiveness**, ensuring you get the best value for your investment.</p>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="p-4 border rounded shadow-sm text-center h-100">
                <i class="fas fa-leaf fa-3x mb-3" style="color: #388e3c;"></i>
                <h4 class="fw-bold">Eco-Conscious Choice</h4>
                <p>We actively promote and stock **sustainable and biodegradable** cleaning solutions, helping you clean green.</p>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="p-4 border rounded shadow-sm text-center h-100">
                <i class="fas fa-people-carry fa-3x mb-3" style="color: #f7b733;"></i>
                <h4 class="fw-bold">Customer First</h4>
                <p>Your satisfaction is our priority. We offer expert advice and responsive customer support for all your cleaning needs.</p>
            </div>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>