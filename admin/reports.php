<?php
require_once __DIR__ . '/../includes/auth.php';

// Only admin can view reports
if (!is_admin()) {
    header("Location: /sweepxpress/login.php");
    exit;
}

// DB connection
require_once __DIR__ . '/../config.php';

// Default date range (last 6 months)
$endDate = date("Y-m-d");
$startDate = date("Y-m-d", strtotime("-6 months"));

// Helper for Status Badge Styling
function get_status_badge($status) {
    $status = strtolower($status);
    return match($status) {
        'delivered', 'completed' => 'badge text-bg-success',
        'preparing', 'shipped' => 'badge text-bg-info text-dark',
        'pending' => 'badge text-bg-warning text-dark',
        'cancelled' => 'badge text-bg-danger',
        default => 'badge text-bg-secondary'
    };
}

// Server-side validation for date filter
if (isset($_GET['start_date'], $_GET['end_date'])) {
    $newStartDate = $_GET['start_date'];
    $newEndDate = $_GET['end_date'];

    // Check if dates are valid and the start date is not after the end date
    if (strtotime($newStartDate) && strtotime($newEndDate) && strtotime($newStartDate) <= strtotime($newEndDate)) {
        $startDate = $newStartDate;
        $endDate = $newEndDate;
    }
}

try {
    // 1. Sales overview (Metrics for the filter range)
    $stmt = $pdo->prepare("SELECT IFNULL(SUM(total),0) AS total_sales, COUNT(id) AS total_orders, IFNULL(AVG(total),0) AS avg_order, IFNULL(MAX(total),0) AS max_order FROM orders WHERE created_at BETWEEN :start AND :end");
    $stmt->execute(['start' => $startDate . ' 00:00:00', 'end' => $endDate . ' 23:59:59']);
    $salesOverview = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 2. New Users in Range (Marketing Insight)
    $stmt = $pdo->prepare("SELECT COUNT(id) AS new_users FROM users WHERE created_at BETWEEN :start AND :end");
    $stmt->execute(['start' => $startDate . ' 00:00:00', 'end' => $endDate . ' 23:59:59']);
    $newUsers = $stmt->fetch(PDO::FETCH_ASSOC)['new_users'];
    
    // 3. Overall User Count (Context)
    $totalUsers = $pdo->query("SELECT COUNT(id) FROM users")->fetchColumn();

    // 4. Top products
    $stmt = $pdo->prepare("
        SELECT p.name, SUM(oi.quantity) AS qty_sold, SUM(oi.quantity * oi.price) AS revenue
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        JOIN orders o ON oi.order_id = o.id
        WHERE o.created_at BETWEEN :start AND :end
        GROUP BY p.id
        ORDER BY qty_sold DESC
        LIMIT 5
    ");
    $stmt->execute(['start' => $startDate . ' 00:00:00', 'end' => $endDate . ' 23:59:59']);
    $topProducts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Top customers
    $stmt = $pdo->prepare("
        SELECT u.name, u.email, SUM(o.total) AS spent
        FROM orders o
        JOIN users u ON o.user_id = u.id
        WHERE o.created_at BETWEEN :start AND :end
        GROUP BY u.id
        ORDER BY spent DESC
        LIMIT 5
    ");
    $stmt->execute(['start' => $startDate . ' 00:00:00', 'end' => $endDate . ' 23:59:59']);
    $topCustomers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 6. Orders by status
    $stmt = $pdo->prepare("
        SELECT status, COUNT(*) AS count
        FROM orders
        WHERE created_at BETWEEN :start AND :end
        GROUP BY status
    ");
    $stmt->execute(['start' => $startDate . ' 00:00:00', 'end' => $endDate . ' 23:59:59']);
    $orderStatus = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 7. Sales per month
    $stmt = $pdo->prepare("
        SELECT DATE_FORMAT(created_at, '%Y-%m') AS month, SUM(total) AS monthly_sales
        FROM orders
        WHERE created_at BETWEEN :start AND :end
        GROUP BY month
        ORDER BY month ASC
    ");
    $stmt->execute(['start' => $startDate . ' 00:00:00', 'end' => $endDate . ' 23:59:59']);
    $monthlySales = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 8. Detailed Sales Table
    $detailedSales = $pdo->prepare("
        SELECT o.id, u.name, o.total, o.status, o.created_at
        FROM orders o
        LEFT JOIN users u ON o.user_id = u.id
        WHERE o.created_at BETWEEN :start AND :end
        ORDER BY o.created_at DESC
    ");
    $detailedSales->execute(['start' => $startDate . ' 00:00:00', 'end' => $endDate . ' 23:59:59']);
    $salesRows = $detailedSales->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Handle database connection or query errors
    $salesOverview = ['total_sales' => 0, 'total_orders' => 0, 'avg_order' => 0, 'max_order' => 0];
    $newUsers = 0;
    $totalUsers = 0;
    $topProducts = $topCustomers = $orderStatus = $monthlySales = $salesRows = [];
    echo "<div class='alert alert-danger'>Database Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}

// Include your common header + sidebar
require_once __DIR__ . '/../includes/header.php';
?>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>
    /* Styling for the new, modern dashboard cards */
    .dashboard-card { 
        border: none; 
        border-radius: 15px; 
        transition: all 0.3s ease-in-out; 
    }
    .dashboard-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15) !important;
    }
    .kpi-icon {
        font-size: 2.5rem;
        opacity: 0.7;
    }
    .kpi-title {
        font-size: 0.9rem;
        font-weight: 600;
    }
    .kpi-value {
        font-size: 1.8rem;
        font-weight: 700;
    }
</style>

<div class="container-fluid p-4">
    <h1 class="mb-4 text-dark fw-bolder border-bottom pb-2">🚀 Marketing & Sales Dashboard</h1>

    <div class="card shadow-sm mb-5 dashboard-card bg-light">
        <div class="card-body">
            <h5 class="card-title text-primary"><i class="fas fa-calendar-alt me-2"></i> Report Date Range</h5>
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-5">
                    <label for="start_date" class="form-label small text-muted">Start Date</label>
                    <input type="date" name="start_date" id="start_date" value="<?= htmlspecialchars($startDate) ?>" class="form-control" required>
                </div>
                <div class="col-md-5">
                    <label for="end_date" class="form-label small text-muted">End Date</label>
                    <input type="date" name="end_date" id="end_date" value="<?= htmlspecialchars($endDate) ?>" class="form-control" required>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100"><i class="fas fa-filter me-1"></i> Apply</button>
                </div>
            </form>
        </div>
    </div>
    
    <h3 class="mb-3 text-secondary">Key Metrics (<?= date("M d, Y", strtotime($startDate)) ?> to <?= date("M d, Y", strtotime($endDate)) ?>)</h3>
    <div class="row g-4 mb-5">
        
        <div class="col-lg-3 col-md-6">
            <div class="card shadow dashboard-card text-success">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="kpi-title text-uppercase">Total Sales</div>
                        <div class="kpi-value">₱<?= number_format($salesOverview['total_sales'], 2); ?></div>
                    </div>
                    <i class="fas fa-dollar-sign kpi-icon"></i>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card shadow dashboard-card text-primary">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="kpi-title text-uppercase">Total Orders</div>
                        <div class="kpi-value"><?= number_format($salesOverview['total_orders']); ?></div>
                    </div>
                    <i class="fas fa-shopping-cart kpi-icon"></i>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card shadow dashboard-card text-warning">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="kpi-title text-uppercase">New Customers</div>
                        <div class="kpi-value"><?= number_format($newUsers); ?></div>
                    </div>
                    <i class="fas fa-user-plus kpi-icon"></i>
                </div>
            </div>
        </div>

        <div class="col-lg-3 col-md-6">
            <div class="card shadow dashboard-card text-secondary">
                <div class="card-body d-flex justify-content-between align-items-center">
                    <div>
                        <div class="kpi-title text-uppercase">Total User Base</div>
                        <div class="kpi-value"><?= number_format($totalUsers); ?></div>
                    </div>
                    <i class="fas fa-users kpi-icon"></i>
                </div>
            </div>
        </div>
    </div>
    
    <hr class="my-4">

    <div class="row g-4 mb-5">
        
        <div class="col-lg-8">
            <div class="card shadow-lg dashboard-card h-100">
                <div class="card-body">
                    <h4 class="text-primary fw-bold mb-3"><i class="fas fa-chart-line me-2"></i> Revenue Trend</h4>
                    <p class="text-muted small">Sales performance over the selected period.</p>
                    <div style="max-height: 380px;">
                        <canvas id="monthlySalesChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
             <div class="card shadow-lg dashboard-card h-100">
                <div class="card-body">
                    <h4 class="text-danger fw-bold mb-3"><i class="fas fa-clipboard-list me-2"></i> Order Funnel</h4>
                    <p class="text-muted small">Breakdown by current order status.</p>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Status</th>
                                    <th class="text-end">Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orderStatus as $row): ?>
                                    <tr>
                                        <td>
                                            <span class="<?= get_status_badge($row['status']); ?>"><?= htmlspecialchars(ucfirst($row['status'])); ?></span>
                                        </td>
                                        <td class="text-end fw-bold"><?= $row['count']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$orderStatus): ?>
                                    <tr><td colspan="2" class="text-center text-muted">No orders found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-lg-6">
            <div class="card shadow dashboard-card h-100">
                <div class="card-body">
                    <h4 class="text-info fw-bold mb-3"><i class="fas fa-boxes me-2"></i> Top 5 Product Adoption</h4>
                    <p class="text-muted small">Most popular products by quantity sold (QoQ Analysis).</p>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Product</th>
                                    <th>Qty Sold</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topProducts as $row): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['name']); ?></td>
                                        <td class="fw-bold"><?= $row['qty_sold']; ?></td>
                                        <td class="text-success fw-bold">₱<?= number_format($row['revenue'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$topProducts): ?>
                                    <tr><td colspan="3" class="text-center text-muted">No product data available for this range.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-6">
            <div class="card shadow dashboard-card h-100">
                <div class="card-body">
                    <h4 class="text-warning fw-bold mb-3"><i class="fas fa-star me-2"></i> High-Value Customers</h4>
                    <p class="text-muted small">Top 5 customers by total spending (Loyalty potential).</p>
                    <div class="table-responsive">
                        <table class="table table-striped table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Total Spent</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topCustomers as $row): ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($row['name']); ?></td>
                                        <td><?= htmlspecialchars($row['email']); ?></td>
                                        <td class="text-warning fw-bold">₱<?= number_format($row['spent'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (!$topCustomers): ?>
                                    <tr><td colspan="3" class="text-center text-muted">No customer data available for this range.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-lg dashboard-card mt-4">
        <div class="card-body">
            <h4 class="text-dark fw-bold mb-3"><i class="fas fa-search me-2"></i> Detailed Sales Deep Dive</h4>
            <p class="text-muted small">Interactive table of all orders within the selected date range.</p>
            <div class="table-responsive">
                <table id="salesTable" class="table table-striped table-hover w-100">
                    <thead class="table-dark">
                        <tr>
                            <th>Order ID</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($salesRows as $row): ?>
                            <tr>
                                <td>#<?= htmlspecialchars($row['id']) ?></td>
                                <td><?= htmlspecialchars($row['name'] ?? 'N/A') ?></td>
                                <td class="fw-bold">₱<?= number_format(htmlspecialchars($row['total']), 2) ?></td>
                                <td><span class="<?= get_status_badge($row['status']); ?>"><?= ucfirst(htmlspecialchars($row['status'])) ?></span></td>
                                <td><?= date("M d, Y", strtotime(htmlspecialchars($row['created_at']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    // Data for the chart
    const monthlySalesData = {
        labels: <?= json_encode(array_map(fn($m) => date("M Y", strtotime($m)), array_column($monthlySales, 'month'))) ?>,
        datasets: [{
            label: 'Monthly Sales (₱)',
            data: <?= json_encode(array_column($monthlySales, 'monthly_sales')) ?>,
            borderColor: '#007bff', // Primary Blue
            backgroundColor: 'rgba(0, 123, 255, 0.1)', // Light blue fill
            pointBackgroundColor: '#007bff',
            pointBorderColor: '#fff',
            pointHoverBackgroundColor: '#fff',
            pointHoverBorderColor: '#007bff',
            fill: true,
            tension: 0.4,
            borderWidth: 3
        }]
    };

    // Chart configuration
    const config = {
        type: 'line',
        data: monthlySalesData,
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false,
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += '₱' + context.parsed.y.toLocaleString('en-US', {minimumFractionDigits: 2});
                            }
                            return label;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)', // Lighter grid lines
                        drawBorder: false
                    },
                    ticks: {
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        }
    };

    // Render the chart and initialize DataTables
    document.addEventListener('DOMContentLoaded', function() {
        const ctx = document.getElementById('monthlySalesChart');
        if (ctx) {
             new Chart(ctx, config);
        }
       
        // Initialize DataTables for the detailed sales table
        $('#salesTable').DataTable({
            responsive: true,
            pageLength: 10,
            order: [[4, "desc"]] // Sort by Date column descending
        });
    });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>