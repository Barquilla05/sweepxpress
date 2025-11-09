<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config.php';

// Only admin can view dashboard
if (!is_admin()) {
    header("Location: /sweepxpress/login.php");
    exit;
}

// Get the current admin's details
$currentAdmin = $_SESSION['user'];

// --- DB QUERIES ---
try {
    // Core Stats
    $totalSales = $pdo->query("SELECT IFNULL(SUM(total),0) AS sales FROM orders")->fetch()['sales'];
    $totalOrders = $pdo->query("SELECT COUNT(*) AS cnt FROM orders")->fetch()['cnt'];
    $totalProducts = $pdo->query("SELECT COUNT(*) AS cnt FROM products")->fetch()['cnt'];
    $totalUsers = $pdo->query("SELECT COUNT(*) AS cnt FROM users")->fetch()['cnt'];

    // Decision-Making Metrics
    $totalCancelledOrders = $pdo->query("SELECT COUNT(*) AS cnt FROM orders WHERE status = 'cancelled'")->fetch()['cnt'];
    $aov = $totalOrders > 0 ? $totalSales / $totalOrders : 0;
    $cancellationRate = $totalOrders > 0 ? ($totalCancelledOrders / $totalOrders) * 100 : 0;

    // Sales data for chart (Monthly Trend - Last 6 Months)
    $salesData = $pdo->query("
        SELECT DATE_FORMAT(created_at, '%b %Y') as month, IFNULL(SUM(total), 0) as monthly_total
        FROM orders
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY MONTH(created_at), YEAR(created_at)
        ORDER BY created_at ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
    $months = array_column($salesData, 'month');
    $totals = array_column($salesData, 'monthly_total');

    // Top products
    $topProducts = $pdo->query("
        SELECT p.name, SUM(o.quantity) as qty
        FROM order_items o
        JOIN products p ON o.product_id = p.id
        GROUP BY p.id
        ORDER BY qty DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Top users
    $topUsers = $pdo->query("
        SELECT u.name, COUNT(o.id) as orders
        FROM users u
        JOIN orders o ON o.user_id = u.id
        GROUP BY u.id
        ORDER BY orders DESC
        LIMIT 3
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Recent orders with status
    $recentOrders = $pdo->query("
        SELECT o.id, u.name, o.total, o.status, o.created_at
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        ORDER BY o.created_at DESC
        LIMIT 5
    ")->fetchAll();

    // Orders Breakdown data for chart
    $deliveredOrders = $pdo->query("SELECT COUNT(*) AS cnt FROM orders WHERE status = 'delivered' OR status = 'completed'")->fetch()['cnt'];
    $pendingOrders = $pdo->query("SELECT COUNT(*) AS cnt FROM orders WHERE status = 'pending'")->fetch()['cnt'];
    $preparingOrders = $pdo->query("SELECT COUNT(*) AS cnt FROM orders WHERE status = 'preparing' OR status = 'shipped'")->fetch()['cnt'];
    $cancelledOrders = $pdo->query("SELECT COUNT(*) AS cnt FROM orders WHERE status = 'cancelled'")->fetch()['cnt'];

    // User Role Counts
    $adminCount = $pdo->query("SELECT COUNT(*) AS cnt FROM users WHERE role = 'admin'")->fetch()['cnt'];
    $customerCount = $pdo->query("SELECT COUNT(*) AS cnt FROM users WHERE role = 'customer'")->fetch()['cnt'];
    $businessCount = $pdo->query("SELECT COUNT(*) AS cnt FROM users WHERE role = 'business'")->fetch()['cnt'];

    // New Users (last 30 days)
    $oneMonthAgo = date('Y-m-d H:i:s', strtotime('-30 days'));
    $newUsersCount = $pdo->prepare("SELECT COUNT(*) AS cnt FROM users WHERE created_at >= ?");
    $newUsersCount->execute([$oneMonthAgo]);
    $newUsers = $newUsersCount->fetch()['cnt'];

    // Low Stock Products (Assume stock < 10 is low)
    $lowStockProducts = $pdo->query("
        SELECT name, stock
        FROM products
        WHERE stock < 10
        ORDER BY stock ASC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Sales Per Week (Last 8 Weeks)
    $salesPerWeekData = $pdo->query("
        SELECT CONCAT('W', WEEK(created_at, 1)) as week_label, SUM(total) as weekly_total
        FROM orders
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 8 WEEK)
        GROUP BY week_label
        ORDER BY created_at ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
    $weekLabels = array_column($salesPerWeekData, 'week_label');
    $weekTotals = array_column($salesPerWeekData, 'weekly_total');

    // Lost Sales (Cancelled Orders - Last 6 Months)
    $lostSalesData = $pdo->query("
        SELECT DATE_FORMAT(created_at, '%b %Y') as month, IFNULL(SUM(total), 0) as lost_total
        FROM orders
        WHERE status = 'cancelled'
        AND created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY MONTH(created_at), YEAR(created_at)
        ORDER BY created_at ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
    $lostSalesLabels = array_column($lostSalesData, 'month');
    $lostSalesTotals = array_column($lostSalesData, 'lost_total');

} catch (PDOException $e) {
    // Minimal error handling for dashboard visibility
    error_log("Dashboard DB Error: " . $e->getMessage());
}

require_once __DIR__ . '/../includes/header.php';
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
    /* Global Styles */
    body { background: #f5f7fa; font-family: 'Inter', sans-serif; }
    h1, h6 { font-weight: 600; }
    .text-primary-dark { color: #4e73df !important; }

    /* Modern Card Aesthetic */
    .dashboard-card { 
        border: none;
        border-radius: 1rem; /* Softer edges */
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important; /* Deeper shadow */
        transition: transform .2s ease, box-shadow .2s ease;
        background-color: white;
    }
    .dashboard-card:hover { 
        transform: translateY(-5px); 
        box-shadow: 0 1rem 2rem rgba(0, 0, 0, 0.1) !important; 
    }

    /* KPI Styles */
    .kpi-title { font-size: 0.9rem; font-weight: 500; color: #6c757d; margin-bottom: 0.2rem; }
    .kpi-value { font-size: 2rem; font-weight: 700; line-height: 1; }
    .kpi-icon-box { font-size: 3rem; opacity: 0.15; }
    
    /* Subtle Badges for Aesthetic */
    .badge-subtle { padding: 0.4rem 0.6rem; font-weight: 600; font-size: 0.75rem; border-radius: 0.5rem; }
    .bg-primary-subtle { background-color: #e0f7ff !important; color: #0d6efd !important; }
    .bg-success-subtle { background-color: #d1f7e0 !important; color: #198754 !important; }
    .bg-warning-subtle { background-color: #fff4d9 !important; color: #ffc107 !important; }
    .bg-danger-subtle { background-color: #ffe0e6 !important; color: #dc3545 !important; }
    .bg-info-subtle { background-color: #e0faff !important; color: #0dcaf0 !important; }

    /* Utility Styles */
    .profile-card {
        border-radius: 1rem;
        background: linear-gradient(45deg, #4e73df, #2e4b86); /* Darker blue gradient */
        color: white;
        padding: 1rem 1.5rem;
        text-align: right;
    }
    .order-table-dense td, .order-table-dense th { padding: 0.75rem 0.75rem; }
    
    canvas { max-height: 220px !important; } /* Standardized chart height */

    /* === NEW/MODIFIED STYLES FOR HEADER CARD === */
    .aesthetic-header-card {
        border-radius: 1rem;
        /* A vibrant, professional blue-to-teal gradient */
        background: linear-gradient(135deg, #4e73df 0%, #2980b9 50%, #16a085 100%);
        color: white;
        padding: 1rem 1.5rem;
        box-shadow: 0 0.75rem 1.5rem rgba(0, 0, 0, 0.15) !important;
        /* Ensure it matches the general card style */
        transition: transform .2s ease, box-shadow .2s ease;
    }
    .aesthetic-header-card:hover { 
        transform: translateY(-5px); 
        box-shadow: 0 1rem 2rem rgba(0, 0, 0, 0.2) !important; 
    }
    .header-time {
        font-size: 1.8rem;
        font-weight: 700;
        line-height: 1;
        margin-bottom: 0.2rem;
    }
    .header-date {
        font-size: 0.9rem;
        font-weight: 400;
        opacity: 0.8;
    }
    /* === END NEW/MODIFIED STYLES === */
</style>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
        <h1 class="fw-bolder text-primary-dark"><i class="fas fa-chart-line me-2"></i> Growth & Operations Center</h1>
        
        <div class="aesthetic-header-card d-flex align-items-center justify-content-between">
            <div class="me-4 text-start">
                <h6 class="mb-0 fw-normal">Welcome back, <span class="fw-bold"><?php echo htmlspecialchars($currentAdmin['name']); ?>!</span></h6>
                <p class="small mb-0 mt-1" style="opacity: 0.7;">Admin since <?php echo htmlspecialchars(date('M Y', strtotime($currentAdmin['created_at']))); ?></p>
            </div>
            
            <div style="height: 40px; border-left: 1px solid rgba(255, 255, 255, 0.3);"></div>
            
            <div class="ms-4 text-end">
                <div class="header-time" id="current-time"></div>
                <div class="header-date" id="current-date"></div>
            </div>
        </div>
        </div>

    <h3 class="fw-bold mb-3 text-secondary border-bottom border-secondary-subtle pb-2">Financial Health</h3>
    <div class="row g-4 mb-5 text-center d-flex align-items-stretch">
        
        <div class="col-lg-3 col-md-6 d-flex">
            <div class="card shadow-lg dashboard-card flex-fill p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="kpi-title text-success">TOTAL REVENUE</div>
                        <div class="kpi-value text-success">₱<?php echo number_format($totalSales, 2); ?></div>
                    </div>
                    <i class="fas fa-dollar-sign kpi-icon-box text-success"></i>
                </div>
                <span class="badge badge-subtle bg-success-subtle mt-2">All-Time Sales</span>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 d-flex">
            <div class="card shadow-lg dashboard-card flex-fill p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="kpi-title text-info">AVERAGE ORDER VALUE</div>
                        <div class="kpi-value text-info">₱<?php echo number_format($aov, 2); ?></div>
                    </div>
                    <i class="fas fa-coins kpi-icon-box text-info"></i>
                </div>
                <span class="badge badge-subtle bg-info-subtle mt-2">Marketing Efficiency</span>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 d-flex">
            <div class="card shadow-lg dashboard-card flex-fill p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="kpi-title text-primary-dark">TOTAL TRANSACTIONS</div>
                        <div class="kpi-value text-primary-dark"><?php echo number_format($totalOrders); ?></div>
                    </div>
                    <i class="fas fa-shopping-basket kpi-icon-box text-primary-dark"></i>
                </div>
                <span class="badge badge-subtle bg-primary-subtle mt-2">Operational Volume</span>
            </div>
        </div>

        <div class="col-lg-3 col-md-6 d-flex">
            <div class="card shadow-lg dashboard-card flex-fill p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="kpi-title text-warning">ACTIVE SKUS</div>
                        <div class="kpi-value text-warning"><?php echo $totalProducts; ?></div>
                    </div>
                    <i class="fas fa-box-open kpi-icon-box text-warning"></i>
                </div>
                <span class="badge badge-subtle bg-warning-subtle mt-2">Product Catalog</span>
            </div>
        </div>
    </div>
    
    <h3 class="fw-bold mb-3 text-secondary border-bottom border-secondary-subtle pb-2">Customer & Risk</h3>
    <div class="row g-4 mb-5 text-center d-flex align-items-stretch">
        
        <div class="col-lg-3 col-md-6 d-flex">
            <div class="card shadow-lg dashboard-card flex-fill p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="kpi-title text-danger">CANCELLATION RATE</div>
                        <div class="kpi-value text-danger"><?php echo number_format($cancellationRate, 2); ?>%</div>
                    </div>
                    <i class="fas fa-times-circle kpi-icon-box text-danger"></i>
                </div>
                <span class="badge badge-subtle bg-danger-subtle mt-2">Critical Risk Indicator</span>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 d-flex">
            <div class="card shadow-lg dashboard-card flex-fill p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="kpi-title text-success">NEW USERS (30 DAYS)</div>
                        <div class="kpi-value text-success"><?php echo number_format($newUsers); ?></div>
                    </div>
                    <i class="fas fa-user-plus kpi-icon-box text-success"></i>
                </div>
                <span class="badge badge-subtle bg-success-subtle mt-2">Acquisition Velocity</span>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 d-flex">
            <div class="card shadow-lg dashboard-card flex-fill p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="kpi-title text-secondary">TOTAL USER BASE</div>
                        <div class="kpi-value text-secondary"><?php echo number_format($totalUsers); ?></div>
                    </div>
                    <i class="fas fa-users kpi-icon-box text-secondary"></i>
                </div>
                <span class="badge badge-subtle bg-secondary mt-2 text-white">Market Reach</span>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 d-flex">
            <div class="card shadow-lg dashboard-card flex-fill p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="kpi-title text-danger">LOW STOCK ITEMS</div>
                        <div class="kpi-value text-danger"><?php echo count($lowStockProducts); ?></div>
                    </div>
                    <i class="fas fa-exclamation-triangle kpi-icon-box text-danger"></i>
                </div>
                <span class="badge badge-subtle bg-danger-subtle mt-2">Supply Chain Risk</span>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-7">
            <div class="card shadow-lg dashboard-card h-100 p-3">
                <div class="card-body">
                    <h5 class="fw-bold text-primary-dark"><i class="fas fa-chart-area me-2"></i> Monthly Revenue Trend</h5>
                    <p class="text-muted small">The main growth velocity over the past six months.</p>
                    <canvas id="salesChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-md-5">
            <div class="card shadow-lg dashboard-card h-100 p-3">
                <div class="card-body">
                    <h5 class="fw-bold text-danger"><i class="fas fa-ban me-2"></i> Lost Sales (Cancellation Cost)</h5>
                    <p class="text-muted small">Total value of orders cancelled over the last 6 months.</p>
                    <canvas id="lostSalesChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row g-4 mb-5">
        
        <div class="col-lg-4 col-md-6">
            <div class="card shadow-lg dashboard-card h-100 p-3">
                <div class="card-body">
                    <h6 class="fw-bold text-info"><i class="fas fa-calendar-week me-1"></i> Weekly Performance</h6>
                    <p class="text-muted small">Trend of total sales for the last 8 weeks.</p>
                    <canvas id="salesPerWeekChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4 col-md-6">
            <div class="card shadow-lg dashboard-card h-100 p-3">
                <div class="card-body">
                    <h6 class="fw-bold text-success"><i class="fas fa-trophy me-1"></i> Top 5 Products (Units)</h6>
                    <p class="text-muted small">Focus marketing efforts on these top performers.</p>
                    <canvas id="topProductsChart"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-4 col-md-12">
            <div class="card shadow-lg dashboard-card h-100 p-3">
                <div class="card-body">
                    <h6 class="fw-bold text-warning"><i class="fas fa-sitemap me-1"></i> Orders Funnel</h6>
                    <p class="text-muted small">Current breakdown of all pending orders.</p>
                    <canvas id="ordersChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row g-4">
        
        <div class="col-lg-5">
            <div class="row g-4 h-100">
                <div class="col-md-6 col-lg-12">
                    <div class="card shadow-lg dashboard-card h-100">
                        <div class="card-header bg-primary-subtle border-bottom-0">
                            <h6 class="mb-0 fw-bold text-primary">👥 Customer Segmentation</h6>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                    Customer Accounts
                                    <span class="badge bg-success rounded-pill"><?php echo $customerCount; ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                    Business Accounts
                                    <span class="badge bg-info rounded-pill"><?php echo $businessCount; ?></span>
                                </li>
                                <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                    Top Buyers 
                                    <span class="badge bg-dark rounded-pill"><?php echo count($topUsers); ?></span>
                                </li>
                            </ul>
                            <p class="small text-muted mt-3 mb-0 border-top pt-2">
                                <span class="fw-bold text-primary"><?php echo $newUsers; ?></span> new users joined last 30 days.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-12">
                    <div class="card shadow-lg dashboard-card h-100">
                        <div class="card-header bg-danger-subtle border-bottom-0">
                            <h6 class="mb-0 fw-bold text-danger">⚠️ Low Stock Inventory</h6>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <?php if (count($lowStockProducts) > 0): ?>
                                    <?php foreach ($lowStockProducts as $product): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                            <?php echo htmlspecialchars($product['name']); ?>
                                            <span class="badge bg-danger rounded-pill"><?php echo $product['stock']; ?> left</span>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                       <li class="list-group-item text-success text-center py-2">All inventory is healthy! 🎉</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-7">
            <div class="card shadow-lg dashboard-card h-100">
                <div class="card-body">
                    <h5 class="fw-bold text-secondary"><i class="fas fa-clock-rotate-left me-2"></i> Recent Order Activity</h5>
                    <p class="text-muted small">Last 5 transactions for quick review.</p>
                    <table class="table table-striped table-hover align-middle mb-0 order-table-dense">
                        <thead class="table-light"><tr><th>ID</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
                        <tbody>
                            <?php foreach ($recentOrders as $ro): ?>
                                <tr>
                                    <td><span class="badge bg-primary">#<?php echo $ro['id']; ?></span></td>
                                    <td><?php echo htmlspecialchars($ro['name'] ?? 'N/A'); ?></td>
                                    <td class="fw-bold text-success">₱<?php echo number_format($ro['total'], 2); ?></td>
                                    <td>
                                        <?php
                                        $statusClass = '';
                                        $displayStatus = ucfirst($ro['status']);

                                        if ($ro['status'] == 'delivered' || $ro['status'] == 'completed') {
                                            $statusClass = 'bg-success-subtle text-success';
                                            $displayStatus = 'Delivered';
                                        } elseif ($ro['status'] == 'pending') {
                                            $statusClass = 'bg-warning-subtle text-warning';
                                            $displayStatus = 'Pending';
                                        } elseif ($ro['status'] == 'preparing' || $ro['status'] == 'shipped') {
                                            $statusClass = 'bg-info-subtle text-info';
                                            $displayStatus = 'Preparing';
                                        } elseif ($ro['status'] == 'cancelled') {
                                            $statusClass = 'bg-danger-subtle text-danger';
                                            $displayStatus = 'Cancelled';
                                        }
                                        ?>
                                        <span class="badge badge-subtle <?= $statusClass; ?>"><?php echo htmlspecialchars($displayStatus); ?></span>
                                    </td>
                                    <td><?php echo date("M d, H:i", strtotime($ro['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // --- Chart Data & Configurations ---

    // 1. Monthly Sales Trend
    new Chart(document.getElementById('salesChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($months); ?>,
            datasets: [{
                label: 'Revenue (₱)',
                data: <?php echo json_encode($totals); ?>,
                borderColor: '#4e73df', /* Primary Dark Blue */
                backgroundColor: 'rgba(78, 115, 223, 0.1)',
                tension: 0.4,
                fill: true,
                pointRadius: 5,
                pointHoverRadius: 7,
                pointBackgroundColor: '#4e73df'
            }]
        },
        options: { 
            plugins: { legend: { display: false }}, 
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, ticks: { callback: function(value) { return '₱' + value.toLocaleString(); } } },
            }
        }
    });

    // 2. Orders Breakdown
    new Chart(document.getElementById('ordersChart'), {
        type: 'doughnut',
        data: {
            labels: ['Delivered', 'Pending', 'Preparing', 'Cancelled'],
            datasets: [{
                data: [
                    <?php echo $deliveredOrders; ?>, 
                    <?php echo $pendingOrders; ?>, 
                    <?php echo $preparingOrders; ?>, 
                    <?php echo $cancelledOrders; ?>
                ],
                backgroundColor: ['#198754', '#ffc107', '#0dcaf0', '#dc3545'],
                hoverOffset: 10
            }]
        },
        options: { 
            plugins: { 
                legend: { position: 'bottom' },
                tooltip: { 
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) { label += ': '; }
                            label += context.parsed + ' orders';
                            return label;
                        }
                    }
                }
            }, 
            responsive: true, 
            maintainAspectRatio: false
        }
    });

    // 3. Top Products
    new Chart(document.getElementById('topProductsChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($topProducts, 'name')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($topProducts, 'qty')); ?>,
                backgroundColor: '#20c997', /* Teal for Success/Product */
                borderColor: '#1aa37a',
                borderWidth: 1,
                borderRadius: 5
            }]
        },
        options: { 
            indexAxis: 'y', // Horizontal bars for better product name readability
            plugins: { legend: { display: false }}, 
            responsive: true,
            maintainAspectRatio: false
        }
    });
    
    // 4. Sales Per Week
    new Chart(document.getElementById('salesPerWeekChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($weekLabels); ?>,
            datasets: [{
                label: 'Weekly Sales (₱)',
                data: <?php echo json_encode($weekTotals); ?>,
                backgroundColor: '#0dcaf0', /* Info Blue */
                borderColor: '#0b97b6',
                borderWidth: 1
            }]
        },
        options: { 
            plugins: { legend: { display: false }}, 
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
    
    // 5. Lost Sales
    new Chart(document.getElementById('lostSalesChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($lostSalesLabels); ?>,
            datasets: [{
                label: 'Lost Sales (₱)',
                data: <?php echo json_encode($lostSalesTotals); ?>,
                borderColor: '#dc3545', /* Danger Red */
                backgroundColor: 'rgba(220, 53, 69, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: { 
            plugins: { legend: { display: false }}, 
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: { beginAtZero: true, ticks: { callback: function(value) { return '₱' + value.toLocaleString(); } } },
            }
        }
    });
    
    // --- Dynamic Time and Date Update ---
    function updateDateTime() {
        const now = new Date();

        // Time format (e.g., 12:31:38 AM)
        const timeOptions = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true };
        const currentTime = now.toLocaleTimeString('en-US', timeOptions); 
        const timeElement = document.getElementById('current-time');
        if (timeElement) {
            // Remove seconds for a cleaner look in the header
            timeElement.textContent = currentTime.replace(/:\d{2}\s/, ' '); 
        }

        // Date format (e.g., Tuesday, Nov 4, 2025)
        const dateOptions = { weekday: 'long', month: 'short', day: 'numeric', year: 'numeric' };
        const currentDate = now.toLocaleDateString('en-US', dateOptions);
        const dateElement = document.getElementById('current-date');
        if (dateElement) {
             dateElement.textContent = currentDate;
        }
    }

    // Update immediately and then every second
    updateDateTime();
    setInterval(updateDateTime, 1000);

</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>