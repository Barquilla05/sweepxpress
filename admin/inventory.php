<?php
require_once __DIR__ . '/../includes/auth.php';
// Assuming is_admin() is defined in auth.php
if (!is_admin()) {
    header("Location: /sweepxpress/login.php");
    exit;
}
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/header.php';

// Helper function (still needed for redirection after search/pagination)
function send_alert_and_redirect($msg, $type = 'success') {
    // Note: This function is primarily for the client-side alert/redirect functionality if needed elsewhere
    echo "<script>window.onload = function(){ alert('". addslashes($msg) ."'); window.location.href='inventory.php'; };</script>";
    exit;
}

// Pagination + search
$limit = 12;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;
$search = trim($_GET['search'] ?? '');

// SQL Query to fetch products and real stock (UNCHANGED)
$sql = "SELECT p.id, p.name, COALESCE(p.sku,'') AS sku, COALESCE(p.price,0) AS price,
         IFNULL(SUM(i.quantity),0) AS real_stock
         FROM products p
         LEFT JOIN inventory i ON i.product_id = p.id
         " . ($search ? "WHERE p.name LIKE :s OR p.sku LIKE :s" : "") . "
         GROUP BY p.id
         ORDER BY p.name ASC
         LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
if ($search) $stmt->bindValue(':s', "%$search%");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count Query (UNCHANGED)
$countSql = "SELECT COUNT(*) FROM products " . ($search ? "WHERE name LIKE :s OR sku LIKE :s" : "");
$countStmt = $pdo->prepare($countSql);
if ($search) $countStmt->execute([':s'=>"%$search%"]); else $countStmt->execute();
$totalProducts = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalProducts / $limit));
?>

<div class="container-fluid min-vh-100 bg-light px-0 py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4 px-4 px-lg-5">
        <h1 class="display-6 fw-bold text-primary mb-0">Inventory Tracking</h1>
        <a href="dashboard.php" class="btn btn-secondary shadow-sm btn-lg">
            <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
        </a>
    </div>

    <form method="GET" class="mb-4 px-4 px-lg-5">
        <div class="input-group">
            <input type="text" name="search" class="form-control form-control-lg" placeholder="Search product or SKU..." value="<?php echo htmlspecialchars($search); ?>">
            <button class="btn btn-primary btn-lg" type="submit">
                <i class="fas fa-search me-1"></i> Search
            </button>
        </div>
    </form>

    <div class="mb-4 px-4 px-lg-5 d-flex flex-wrap gap-2">
        <a href="stock_in.php" 
            class="btn btn-lg shadow-sm"
            style="background-color: #198754 !important; border-color: #198754 !important; color: #ffffff !important;">
            📦 Stock-In
        </a>
        
        <a href="stock_out.php" 
            class="btn btn-lg shadow-sm"
            style="background-color: #ffc107 !important; border-color: #ffc107 !important; color: #212529 !important;">
            📤 Stock-Out
        </a>
        
        <a href="stock_adjust.php" 
            class="btn btn-lg shadow-sm"
            style="background-color: #dc3545 !important; border-color: #dc3545 !important; color: #ffffff !important;">
            ⚙ Adjustment
        </a>
        
        <a href="inventory_full_history.php" 
            class="btn btn-lg shadow-sm"
            style="background-color: #0dcaf0 !important; border-color: #0dcaf0 !important; color: #ffffff !important;">
            📜 Full History
        </a>
    </div>

    <div class="card shadow-lg border-0 w-100">
        <div class="card-header bg-white border-bottom py-3 px-4 px-lg-5">
            <h3 class="mb-0 text-dark">
                Real-Time Stock Overview <span class="badge bg-primary text-white rounded-pill ms-2"><?php echo $totalProducts; ?> items</span>
            </h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-dark text-center">
                        <tr>
                            <th scope="col" class="ps-4 ps-lg-5" style="width: 5%;">ID</th>
                            <th scope="col" style="width: 40%;">Product</th>
                            <th scope="col" style="width: 15%;">SKU</th>
                            <th scope="col" style="width: 15%;" class="text-center">Price (₱)</th>
                            <th scope="col" style="width: 10%;">Stock</th>
                            <th scope="col" style="width: 15%;" class="pe-4 pe-lg-5">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$products): ?>
                        <tr><td colspan="6" class="text-center py-5">No products found matching your criteria.</td></tr>
                    <?php else: foreach ($products as $p):
                        // Stock Status Logic - MODIFIED FOR WHITE TEXT ON LOW STOCK
                        $stock = (int)$p['real_stock'];
                        $statusText = 'In Stock';
                        $statusClass = 'bg-success text-white';

                        if ($stock == 0) {
                            $statusText = 'Out of Stock';
                            $statusClass = 'bg-danger text-white';
                        } elseif ($stock <= 10) {
                            $statusText = 'Low Stock';
                            // FIX: Added text-white for visibility on the bg-warning badge
                            $statusClass = 'bg-warning text-white'; 
                        }
                    ?>
                        <tr class="product-row" onclick="window.location.href='product_history.php?product_id=<?php echo $p['id']; ?>'" style="cursor: pointer;">
                            <td class="text-center ps-4 ps-lg-5"><?php echo $p['id']; ?></td>
                            <td class="fw-bold text-primary"><?php echo htmlspecialchars($p['name']); ?></td>
                            <td class="text-center text-muted"><?php echo htmlspecialchars($p['sku']); ?></td>
                            <td class="text-end fw-bold">₱<?php echo number_format($p['price'],2); ?></td> 
                            <td class="text-center fw-bold"><?php echo $stock; ?></td> 
                            <td class="text-center pe-4 pe-lg-5">
                                <span class="badge <?php echo $statusClass; ?>">
                                    <?php echo $statusText; ?>
                                </span>
                            </td> 
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <nav class="mt-4 px-4 px-lg-5">
        <ul class="pagination justify-content-center">
            <?php for ($i=1; $i<=$totalPages; $i++): 
                // Build query string, preserving search term
                $queryString = '?page=' . $i;
                if ($search) $queryString .= '&search=' . urlencode($search);
            ?>
                <li class="page-item <?php echo $i==$page?'active':''; ?>">
                    <a class="page-link" href="inventory.php<?php echo $queryString; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>