<?php
// cart.php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/header.php';

// Helper function from checkout.php for consistency
if (!function_exists('h')) {
    function h($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
}

$message = '';

// Update quantities (This PHP logic remains the same for processing the update)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['qty'])) {
    foreach ($_POST['qty'] as $pid => $q) {
        // Ensure quantity is an integer and non-negative
        $q = max(0, (int)$q);
        
        // Logic to remove if quantity is 0
        if ($q === 0) {
            unset($_SESSION['cart'][$pid]);
        } else {
            $_SESSION['cart'][$pid] = $q;
        }
    }
    // REMOVED: $message = '<p class="alert success">Cart updated.</p>';
}

$cart = $_SESSION['cart'] ?? []; // Use null coalescing for safety
$items = [];
$total = 0;
if ($cart) {
    // Sanitize IDs for the SQL IN clause
    $ids = implode(',', array_map('intval', array_keys($cart)));
    
    // NOTE: Using PDO::query for this is generally safe since $ids is sanitized with intval
    $stmt = $pdo->query("SELECT * FROM products WHERE id IN ($ids)"); 
    
    foreach ($stmt as $row) {
        $qty = (int)$cart[$row['id']];
        $subtotal = $qty * (float)$row['price'];
        $items[] = ['p'=>$row, 'qty'=>$qty, 'subtotal'=>$subtotal];
        $total += $subtotal;
    }
}

// 🟢 NEW LOGIC: Determine the checkout path and button text based on user role
$checkoutUrl = '/sweepxpress/checkout.php';
$checkoutText = 'Proceed to Checkout';

// Check if a user is logged in and what their role is
if (isset($_SESSION['user']['role'])) {
    $userRole = $_SESSION['user']['role'];
    
    if ($userRole === 'business') {
        $checkoutUrl = '/sweepxpress/bussiness/purchaseorder.php';
        $checkoutText = 'Generate Purchase Order';
    }
    // If 'customer' or any other role, it remains the default 'checkout.php'
}
// -------------------------------------------------------------
?>

<div class="container my-5">
    <h1>Your Cart</h1>
    
    <?= $message; ?>
    
    <?php if (!$items): ?>
      <p>Cart is empty. <a href="/sweepxpress/index.php">Shop now</a></p>
    <?php else: ?>
    
    <form id="cartForm" method="post">
        <table class="table table-striped align-middle">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $it): ?>
                    <tr>
                        <td><img class="thumb" src="<?php echo h($it['p']['image_path']); ?>" width="50" height="50"> <?php echo h($it['p']['name']); ?></td>
                        <td>₱<?php echo number_format($it['p']['price'], 2); ?></td>
                        <td>
                            <input type="number" min="0" class="qty-input" name="qty[<?php echo (int)$it['p']['id']; ?>]" value="<?php echo (int)$it['qty']; ?>" style="width:80px">
                        </td>
                        <td>₱<?php echo number_format($it['subtotal'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-end fw-bold">Total</td>
                    <td class="fw-bold"><strong>₱<?php echo number_format($total, 2); ?></strong></td>
                </tr>
            </tfoot>
        </table>
        
        </form>
    
    <div class="actions" style="margin-top:12px; display:flex; gap: 10px;">
        <!-- 🟢 ADDED: Continue Shopping Button -->
        <a class="btn btn-outline-secondary w-100" href="/sweepxpress/index.php">Continue Shopping</a>
        <!-- The existing checkout button -->
        <a class="btn btn-success w-100" href="<?= h($checkoutUrl) ?>"><?= h($checkoutText) ?></a>
    </div>
    
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const cartForm = document.getElementById('cartForm');
    const qtyInputs = document.querySelectorAll('.qty-input');
    
    // Attach event listener to each quantity input field
    qtyInputs.forEach(input => {
        // Use 'change' event to detect when the field loses focus or Enter is pressed
        input.addEventListener('change', function() {
            // Check if the input is a valid positive number or zero
            let q = parseInt(this.value);
            if (q < 0 || isNaN(q)) {
                // Reset to 1 if input is invalid or negative
                this.value = 1; 
                return;
            }
            // If the quantity is 0, the PHP script will handle removal on submission.
            
            // Submit the form automatically to update the cart in session
            cartForm.submit();
        });
    });
});
</script>
