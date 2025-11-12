<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config.php';

// Added a local helper function for basic HTML escaping
if (!function_exists('h')) {
    function h($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

// Only admin can view this page
if (!is_admin()) {
    header("Location: /sweepxpress/login.php");
    exit;
}

// Get the current admin's details (using the variable defined in dashboard.php)
$currentAdmin = $_SESSION['user']; 

// --- ORDER-SPECIFIC QUERIES FOR STATS CARDS ---

// Card 1: Total Orders (All statuses)
$totalOrders = $pdo->query("SELECT COUNT(*) AS cnt FROM orders")->fetch()['cnt'];

// Card 2: Purchase Orders/Pending Orders
$pendingOrders = $pdo->query("SELECT COUNT(*) AS cnt FROM orders WHERE status = 'pending'")->fetch()['cnt'];

// Card 3: Cancellation Requests 
$cancellationRequests = $pdo->query("SELECT COUNT(*) AS cnt FROM orders WHERE status = 'cancellation_requested'")->fetch()['cnt'];

// Card 4: Orders Complete (Using 'delivered' status for completion)
$ordersComplete = $pdo->query("SELECT COUNT(*) AS cnt FROM orders WHERE status = 'delivered'")->fetch()['cnt'];

// ** NEW QUERY: All Orders Archive (Includes ALL statuses, same as totalOrders) **
// Using the same query as $totalOrders to get the total count for the archive card.
$archivedOrders = $pdo->query("SELECT COUNT(*) AS cnt FROM orders")->fetch()['cnt'];


require_once __DIR__ . '/../includes/header.php';
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
<style>
    body { background: #f5f7fa; font-family: 'Inter', sans-serif; }
    h1, h6 { font-weight: 600; }
    /* Style for the clickable card links */
    .order-stat-link { 
        border-radius: 12px; 
        transition: transform .2s ease;
        text-decoration: none; 
        color: inherit; 
        display: block; 
    }
    .order-stat-link:hover { 
        transform: translateY(-3px); 
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; 
    }
    .order-stat-card {
        padding: 1.5rem;
        height: 100%;
    }
    .badge-custom { font-size: 0.8rem; padding: 4px 8px; border-radius: 30px; }
    .profile-card {
        border-radius: 12px;
        background: linear-gradient(45deg, #0d6efd, #0056b3);
        color: white;
        padding: 1.5rem;
        text-align: right;
    }
    /* Para pantay ang 5 cards, gagawing 1/5 ang column */
    .col-md-5th {
        width: 20%; 
    }
</style>

<div class="container-fluid p-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="fw-bold text-primary">Orders Dashboard</h1>
        <div class="d-flex gap-2">
            <a href="generate_pdf.php?status=all" target="_blank" class="btn btn-danger shadow-sm">
                <i class="fas fa-file-pdf"></i> Generate All Orders PDF
            </a>
            <a href="dashboard.php" class="btn btn-secondary shadow-sm">⬅ Back to Main Dashboard</a>
        </div>
    </div>

    <div class="row g-3 mb-4 text-center d-flex align-items-stretch justify-content-start">
        
        <div class="col-6 col-md-3 d-flex"> 
            <a href="order_all.php" class="order-stat-link card shadow-sm bg-primary text-white flex-grow-1">
                <div class="order-stat-card d-flex flex-column justify-content-center">
                    <h6>Regular Orders</h6>
                    <h4><?php echo h($totalOrders); ?></h4>
                    <span class="badge bg-light text-primary badge-custom">Customer Orders</span>
                </div>
            </a>
        </div>
        
        <div class="col-6 col-md-3 d-flex">
            <a href="orders_pending.php" class="order-stat-link card shadow-sm bg-warning text-dark flex-grow-1">
                <div class="order-stat-card d-flex flex-column justify-content-center">
                    <h6>Purchase Order</h6>
                    <h4><?php echo h($pendingOrders); ?></h4>
                    <span class="badge bg-dark text-warning badge-custom">Business Orders</span>
                </div>
            </a>
        </div>
        
        <div class="col-6 col-md-3 d-flex">
            <a href="orders_cancellation.php" class="order-stat-link card shadow-sm bg-danger text-white flex-grow-1">
                <div class="order-stat-card d-flex flex-column justify-content-center">
                    <h6>Cancellation Request</h6>
                    <h4><?php echo h($cancellationRequests); ?></h4>
                    <span class="badge bg-light text-danger badge-custom">Review Requests</span>
                </div>
            </a>
        </div>
        
        <div class="col-6 col-md-3 d-flex">
            <a href="orders_completed.php" class="order-stat-link card shadow-sm bg-success text-white flex-grow-1">
                <div class="order-stat-card d-flex flex-column justify-content-center">
                    <h6>Orders Complete</h6>
                    <h4><?php echo h($ordersComplete); ?></h4>
                    <span class="badge bg-light text-success badge-custom">View History</span>
                </div>
            </a>
        </div>
        
        <div class="col-12 col-md-3 d-flex"> 
            <a href="orders_archive_all.php" class="order-stat-link card shadow-sm bg-info text-white flex-grow-1">
                <div class="order-stat-card d-flex flex-column justify-content-center">
                    <h6>All Orders Archive</h6>
                    <h4><?php echo h($archivedOrders); ?></h4>
                    <span class="badge bg-dark text-info badge-custom">All Statuses Included</span>
                </div>
            </a>
        </div>
    </div>
    <div class="alert alert-info shadow-sm" role="alert">
        Click any of the cards above to navigate to the specific order list.
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>