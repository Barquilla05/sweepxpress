<?php
require_once __DIR__ . '/../includes/auth.php';
// Check if the user is an admin before proceeding
if (!is_admin()) { header("Location: /sweepxpress/login.php"); exit; }
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/header.php';

$product_id = (int)($_GET['product_id'] ?? 0);

// Helper function for quick redirects
function redirect_to_inventory() {
    header("Location: inventory.php");
    exit;
}

if (!$product_id) redirect_to_inventory();

// --- 1. Fetch Product Details, Current Stock, AND IMAGE PATH ---
$productStmt = $pdo->prepare("
    SELECT 
        p.id, 
        p.name, 
        COALESCE(p.sku, '') AS sku, 
        COALESCE(p.image_path, '') AS image_path, -- ADDED: Fetch the image path
        IFNULL(SUM(i.quantity), 0) AS current_stock
    FROM 
        products p
    LEFT JOIN 
        inventory i ON i.product_id = p.id
    WHERE 
        p.id = :id
    GROUP BY 
        p.id
");
$productStmt->execute([':id' => $product_id]);
$product = $productStmt->fetch(PDO::FETCH_ASSOC);

if (!$product) redirect_to_inventory();

$page_title = 'Inventory History for ' . htmlspecialchars($product['name']);
$current_stock = (int)$product['current_stock'];


// --- 2. Fetch Product Inventory History (All movements) ---
// Fetch them from OLDEST to NEWEST to calculate the running balance sequentially.
$historySql = "SELECT sm.movement_type, sm.quantity, sm.remark, sm.created_at, sm.user_name, l.name as location_name
               FROM stock_movements sm
               JOIN locations l ON l.id = sm.location_id
               WHERE sm.product_id = :id
               ORDER BY sm.created_at ASC, sm.id ASC"; 
$historyStmt = $pdo->prepare($historySql);
$historyStmt->execute([':id' => $product_id]);
$history = $historyStmt->fetchAll(PDO::FETCH_ASSOC);


// --- 3. Calculate Running Balance ---
$running_balance = $current_stock;

// Iterate backwards to calculate the stock *before* the transaction occurred.
for ($i = count($history) - 1; $i >= 0; $i--) {
    $move = $history[$i];
    $quantity = (int)$move['quantity'];
    $delta = 0; 

    // Determine the delta based on the movement type (using logic from stock_adjust.php)
    if ($move['movement_type'] === 'IN') {
        $delta = $quantity;
    } elseif ($move['movement_type'] === 'OUT') {
        $delta = -$quantity;
    } elseif ($move['movement_type'] === 'ADJUST') {
        if (strpos($move['remark'], '[ADD]') !== false) {
             $delta = $quantity;
        } elseif (strpos($move['remark'], '[DEDUCT]') !== false) {
             $delta = -$quantity;
        } else {
             $delta = 0;
        }
    }

    // Current $running_balance is the stock *after* this movement.
    $history[$i]['stock_after'] = $running_balance;
    
    // Calculate the stock *before* this movement
    $running_balance -= $delta;
    $history[$i]['stock_before'] = $running_balance;
}

// Reverse the array for display (Newest first)
$history = array_reverse($history);

$current_stock_display = $current_stock; 
?>

<div class="container-fluid min-vh-100 bg-light px-0 py-4">
    
    <div class="d-flex align-items-start gap-4 mb-4 px-4 px-lg-5">
        
        <div class="product-image-container flex-shrink-0">
            <?php
            // Check if a specific image path is set, otherwise use a default placeholder
            $imagePath = !empty($product['image_path']) 
                ? htmlspecialchars($product['image_path']) 
                : '/path/to/your/default_placeholder.jpg'; // <-- UPDATE THIS PATH
            ?>
            <img src="<?php echo $imagePath; ?>" 
                 alt="<?php echo htmlspecialchars($product['name']); ?> Image" 
                 class="img-thumbnail shadow-sm rounded-3" 
                 style="max-width: 150px; height: auto; object-fit: cover;">
        </div>

        <div class="flex-grow-1">
            <h1 class="display-6 fw-bold text-primary mb-1">
                <?php echo htmlspecialchars($product['name']); ?>
            </h1>
            <p class="lead text-muted mb-0">
                SKU: <?php echo htmlspecialchars($product['sku'] ?: 'N/A'); ?> | 
                Current Stock Across All Locations: <span class="text-info"><?php echo $current_stock_display; ?></span>
            </p>
        </div>

        <a href="inventory.php" class="btn btn-secondary shadow-sm btn-lg ms-auto flex-shrink-0">
            <i class="fas fa-arrow-left me-2"></i> Back to Inventory
        </a>
    </div>
    <div class="card shadow-lg border-0 w-100">
        <div class="card-header bg-white border-bottom py-3 px-4 px-lg-5">
            <h3 class="mb-0 text-dark">
                Stock Movements History <span class="badge bg-primary text-white rounded-pill ms-2"><?php echo count($history); ?> records</span>
            </h3>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover align-middle mb-0">
                    <thead class="table-dark text-center">
                        <tr>
                            <th scope="col" style="width: 10%;">Type</th>
                            <th scope="col" style="width: 8%;">Qty Change</th>
                            <th scope="col" style="width: 12%;">Stock Before</th>
                            <th scope="col" style="width: 12%;">Stock After</th>
                            <th scope="col" style="width: 15%;">Location</th>
                            <th scope="col" style="width: 25%;">Remark</th>
                            <th scope="col" style="width: 10%;">Timestamp</th>
                            <th scope="col" style="width: 8%;">User</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (!$history): ?>
                        <tr><td colspan="8" class="text-center py-5">No movement history found for this product.</td></tr>
                    <?php else: foreach ($history as $move): 
                        // Determine visual classes for the Quantity Change column
                        $qty_change = $move['stock_after'] - $move['stock_before'];
                        $qty_change_class = '';
                        $qty_change_prefix = '';
                        
                        if ($qty_change > 0) {
                            $qty_change_class = 'text-success fw-bold';
                            $qty_change_prefix = '+';
                        } elseif ($qty_change < 0) {
                            $qty_change_class = 'text-danger fw-bold';
                        } else {
                            $qty_change_class = 'text-secondary';
                        }
                    ?>
                        <tr>
                            <td class="text-center fw-bold"><?php echo htmlspecialchars($move['movement_type']); ?></td>
                            <td class="text-center <?php echo $qty_change_class; ?>"><?php echo $qty_change_prefix . abs($qty_change); ?></td>
                            <td class="text-center text-muted"><?php echo $move['stock_before']; ?></td>
                            <td class="text-center fw-bold text-primary"><?php echo $move['stock_after']; ?></td>
                            <td class="text-center"><?php echo htmlspecialchars($move['location_name']); ?></td>
                            <td><?php echo htmlspecialchars($move['remark'] ?: 'N/A'); ?></td>
                            <td class="text-center text-muted small"><?php echo date('Y-m-d H:i:s', strtotime($move['created_at'])); ?></td>
                            <td class="text-center text-secondary small"><?php echo htmlspecialchars($move['user_name']); ?></td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>