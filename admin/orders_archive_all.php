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

// --- CONFIGURATION ---
$limit = 20; // Orders per page

// --- FILTER HANDLING ---
$filter_year = $_GET['year'] ?? '';
$filter_month = $_GET['month'] ?? '';
$current_page = $_GET['page'] ?? 1;

// Ensure current_page is a positive integer
$current_page = max(1, (int)$current_page);

// Base WHERE clause and parameters for both count and fetch queries
$where_clause = " WHERE 1=1";
$params = [];

// Apply Year filter using 'created_at'
if (!empty($filter_year) && is_numeric($filter_year)) {
    $where_clause .= " AND YEAR(created_at) = :year";
    $params[':year'] = $filter_year;
}

// Apply Month filter using 'created_at'
if (!empty($filter_month) && is_numeric($filter_month) && $filter_month >= 1 && $filter_month <= 12) {
    $where_clause .= " AND MONTH(created_at) = :month";
    $params[':month'] = $filter_month;
}

// --- PAGINATION LOGIC (STEP 1: Get Total Count) ---
try {
    // Build count query
    $count_query = "SELECT COUNT(*) AS total FROM orders" . $where_clause;
    $stmt_count = $pdo->prepare($count_query);
    $stmt_count->execute($params);
    $total_records = $stmt_count->fetch()['total'];
    
    $total_pages = ceil($total_records / $limit);
    $offset = ($current_page - 1) * $limit;

} catch (PDOException $e) {
    $total_records = 0;
    $total_pages = 1;
    $offset = 0;
    $error_message = "Database Error: Could not count orders. " . $e->getMessage();
}

// --- ORDER FETCHING (STEP 2: Get Paginated Data) ---
$query = "SELECT * FROM orders" . $where_clause . " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";

// Rebuild params for execution, separating string/numeric binding
$fetch_params = $params;
$fetch_params[':limit'] = $limit;
$fetch_params[':offset'] = $offset;

try {
    $stmt = $pdo->prepare($query);
    // Bind parameters, ensuring LIMIT and OFFSET are bound as integers
    foreach ($fetch_params as $key => &$val) {
        if ($key === ':limit' || $key === ':offset') {
            $stmt->bindParam($key, $val, PDO::PARAM_INT);
        } else {
            $stmt->bindParam($key, $val, PDO::PARAM_STR);
        }
    }
    
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $orders = [];
    $error_message = (isset($error_message) ? $error_message . " | " : "") . "Could not fetch paginated data. " . $e->getMessage();
}

// Get available years for the dropdown filter (requires no LIMIT/OFFSET)
$years = $pdo->query("SELECT DISTINCT YEAR(created_at) AS year FROM orders ORDER BY year DESC")->fetchAll(PDO::FETCH_COLUMN);

// Array of months for the dropdown
$months = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April', 
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August', 
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];

// Prepare base query string for pagination and PDF links (maintaining filters)
$base_query = "";
if (!empty($filter_year)) $base_query .= "&year=" . $filter_year;
if (!empty($filter_month)) $base_query .= "&month=" . $filter_month;


require_once __DIR__ . '/../includes/header.php';
?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<div class="container-fluid p-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="fw-bold text-info">📦 All Orders Archive</h1>
        <div class="d-flex gap-2">
            <a href="generate_pdf.php?status=all<?php echo h($base_query); ?>" target="_blank" class="btn btn-danger shadow-sm">
                <i class="fas fa-file-pdf"></i> Download PDF Report
            </a>
            <a href="allorders.php" class="btn btn-secondary shadow-sm">⬅ Back to All Orders List</a>
        </div>
    </div>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger" role="alert">
            <?php echo h($error_message); ?>
        </div>
    <?php endif; ?>

    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <h5 class="card-title">Filter Orders Archive</h5>
            <form method="GET" class="row g-3 align-items-end">
                <input type="hidden" name="page" value="1"> 
                
                <div class="col-md-3">
                    <label for="filter_year" class="form-label">Filter by Year</label>
                    <select id="filter_year" name="year" class="form-select">
                        <option value="">All Years</option>
                        <?php foreach ($years as $year): ?>
                            <option value="<?php echo h($year); ?>" 
                                <?php echo ($filter_year == $year) ? 'selected' : ''; ?>>
                                <?php echo h($year); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="filter_month" class="form-label">Filter by Month</label>
                    <select id="filter_month" name="month" class="form-select">
                        <option value="">All Months</option>
                        <?php foreach ($months as $num => $name): ?>
                            <option value="<?php echo h($num); ?>" 
                                <?php echo ($filter_month == $num) ? 'selected' : ''; ?>>
                                <?php echo h($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-auto">
                    <button type="submit" class="btn btn-primary">Apply Filters</button>
                    <a href="orders_archive_all.php" class="btn btn-outline-secondary">Reset</a>
                </div>
            </form>
        </div>
    </div>
    <div class="card shadow">
        <div class="card-header bg-info text-white">
            Showing <?php echo count($orders); ?> of <?php echo $total_records; ?> Orders 
            (Page <?php echo $current_page; ?> of <?php echo $total_pages; ?>)
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Date/Time</th>
                            <th>Customer ID</th>
                            <th>Status</th>
                            <th>Total</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($orders)): ?>
                            <tr>
                                <td colspan="6" class="text-center">No orders found matching the filter criteria.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?php echo h($order['id']); ?></td>
                                    <td><?php echo h(date('Y-m-d H:i', strtotime($order['created_at']))); ?></td>
                                    <td><?php echo h($order['user_id']); ?></td>
                                    <td><span class="badge 
                                        <?php 
                                            // Status styling logic
                                            if ($order['status'] == 'delivered') echo 'bg-success';
                                            // Renaming 'shipped' to 'Preparing' and using bg-info
                                            elseif ($order['status'] == 'shipped') echo 'bg-info text-dark'; 
                                            elseif ($order['status'] == 'pending') echo 'bg-warning text-dark';
                                            // Using bg-danger for cancelled or cancellation requested
                                            elseif ($order['status'] == 'cancelled' || $order['status'] == 'cancellation_requested') echo 'bg-danger';
                                            else echo 'bg-secondary';
                                        ?>">
                                        <?php 
                                            $displayStatus = $order['status'];
                                            // Rename 'shipped' to 'Preparing' for display
                                            if ($displayStatus == 'shipped') {
                                                echo 'Preparing';
                                            } else {
                                                echo h(ucfirst(str_replace('_', ' ', $displayStatus)));
                                            }
                                        ?>
                                    </span></td>
                                    <td>$<?php echo h(number_format($order['total'] ?? 0, 2)); ?></td>
                                    <td>
                                        <a href="view_order.php?id=<?php echo h($order['id']); ?>" class="btn btn-sm btn-outline-info">View Details</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if ($total_pages > 1): ?>
        <div class="card-footer d-flex justify-content-center">
            <nav>
                <ul class="pagination mb-0">
                    <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="orders_archive_all.php?page=<?php echo $current_page - 1 . $base_query; ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    
                    <?php 
                    $start_page = max(1, $current_page - 2);
                    $end_page = min($total_pages, $current_page + 2);

                    if ($start_page > 1) {
                        echo '<li class="page-item"><a class="page-link" href="orders_archive_all.php?page=1' . $base_query . '">1</a></li>';
                        if ($start_page > 2) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                    }

                    for ($p = $start_page; $p <= $end_page; $p++): 
                    ?>
                        <li class="page-item <?php echo ($p == $current_page) ? 'active' : ''; ?>">
                            <a class="page-link" href="orders_archive_all.php?page=<?php echo $p . $base_query; ?>"><?php echo $p; ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php
                    if ($end_page < $total_pages) {
                        if ($end_page < $total_pages - 1) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        echo '<li class="page-item"><a class="page-link" href="orders_archive_all.php?page=' . $total_pages . $base_query . '">' . $total_pages . '</a></li>';
                    }
                    ?>

                    <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="orders_archive_all.php?page=<?php echo $current_page + 1 . $base_query; ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
    </div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>