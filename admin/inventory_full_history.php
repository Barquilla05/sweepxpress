<?php
require_once __DIR__ . '/../includes/auth.php';
if (!is_admin()) { header("Location: /sweepxpress/login.php"); exit; }
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/header.php';

$page_title = 'Full Stock Movement History';

// Pagination and search setup (optional but good practice)
$limit = 20;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $limit;
$search = trim($_GET['search'] ?? '');

$search_clause = "";
$bind_values = [];

if ($search) {
    $search_clause = " WHERE p.name LIKE :s OR p.sku LIKE :s OR s.remark LIKE :s ";
    $bind_values[':s'] = "%$search%";
}

// Fetch all stock movements with related details
$sql = "SELECT s.id, s.product_id, s.movement_type, s.quantity, s.remark, s.created_at, s.user_name,
               l.name AS location_name, p.name AS product_name, p.sku, p.price
        FROM stock_movements s
        LEFT JOIN locations l ON s.location_id = l.id
        LEFT JOIN products p ON s.product_id = p.id
        {$search_clause}
        ORDER BY s.created_at DESC
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
if ($search) $stmt->bindValue(':s', $bind_values[':s']);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total count for pagination
$countSql = "SELECT COUNT(*) FROM stock_movements s LEFT JOIN products p ON s.product_id = p.id {$search_clause}";
$countStmt = $pdo->prepare($countSql);
if ($search) $countStmt->bindValue(':s', $bind_values[':s']);
$countStmt->execute();
$totalRecords = $countStmt->fetchColumn();
$totalPages = max(1, ceil($totalRecords / $limit));
?>
<div class="container-fluid mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3"><?php echo $page_title; ?></h1>
        <a href="inventory.php" class="btn btn-secondary">⬅ Back to Inventory</a>
    </div>

    <form method="GET" class="mb-4">
        <div class="input-group">
            <input type="text" name="search" class="form-control" placeholder="Search product name, SKU or remark..." value="<?php echo htmlspecialchars($search); ?>">
            <button class="btn btn-primary" type="submit">Search</button>
        </div>
    </form>

    <div class="table-responsive shadow-sm rounded">
        <?php if (!$rows): ?>
            <p class='text-muted p-3'>No stock movement history found.</p>
        <?php else: ?>
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-dark text-center">
                    <tr>
                        <th>Date</th>
                        <th>Name of Product</th>
                        <th>SKU</th>
                        <th>Price (₱)</th>
                        <th>Type</th>
                        <th>Quantity</th>
                        <th>Location</th>
                        <th>User who imported/modified</th>
                        <th>Remark</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr style="cursor: pointer;" onclick="window.location.href='product_history.php?product_id=<?php echo $r['product_id']; ?>'">
                        <td><?php echo $r['created_at']; ?></td>
                        <td class="text-primary fw-bold"><?php echo htmlspecialchars($r['product_name']); ?></td>
                        <td class="text-center"><?php echo htmlspecialchars($r['sku']); ?></td>
                        <td class="text-end">₱<?php echo number_format($r['price'], 2); ?></td>
                        <td class="text-center">
                            <span class="badge bg-<?php echo ($r['movement_type']=='IN'?'success':($r['movement_type']=='OUT'?'warning':'danger')); ?> text-white">
                                <?php echo $r['movement_type']; ?>
                            </span>
                        </td>
                        <td class="text-center"><?php echo $r['quantity']; ?></td>
                        <td><?php echo htmlspecialchars($r['location_name']); ?></td>
                        <td><?php 
                            // START OF DYNAMIC NAME REPLACEMENT LOGIC
                            $display_name = $r['user_name'];
                            
                            // If the stored name is 'admin' (case-insensitive), replace it with the viewer's session name.
                            if (isset($_SESSION['user_name']) && strcasecmp($display_name, 'admin') === 0) {
                                $display_name = $_SESSION['user_name'];
                            }
                            
                            // If the name is still empty/null, use a fallback.
                            echo htmlspecialchars($display_name ?: 'System/Unknown'); 
                            // END OF DYNAMIC NAME REPLACEMENT LOGIC
                        ?></td>
                        <td><?php echo htmlspecialchars($r['remark']); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <nav class="mt-3">
        <ul class="pagination justify-content-center">
            <?php for ($i=1; $i<=$totalPages; $i++): ?>
                <li class="page-item <?php echo $i==$page?'active':''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $search?'&search='.urlencode($search):''; ?>"><?php echo $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>