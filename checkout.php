<?php
// checkout.php - SweepXpress (Visual Payment Grid)

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/header.php';

if (!function_exists('h')) {
    function h($s) { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
}

if (!function_exists('is_logged_in')) {
    function is_logged_in() { return isset($_SESSION['user']); }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
}
$csrf_token = $_SESSION['csrf_token'];

if (empty($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    echo '<div class="alert alert-danger">Your cart is empty.</div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$cart = $_SESSION['cart'];
$ids = array_map('intval', array_keys($cart));
$ids_placeholder = implode(',', $ids) ?: '0';

try {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($ids_placeholder)");
    $stmt->execute();
    $productRows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $stmt->closeCursor();
} catch (PDOException $e) {
    error_log("DB error fetching products in checkout: " . $e->getMessage());
    echo '<div class="alert alert-danger">Could not load cart items. Please try again later.</div>';
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$items = [];
$total = 0.0;
foreach ($productRows as $row) {
    $pid = (int)$row['id'];
    $qty = isset($cart[$pid]) ? (int)$cart[$pid] : 0;
    if ($qty <= 0) continue;
    $price = (float)$row['price'];
    $subtotal = $qty * $price;
    $items[] = ['p' => $row, 'qty' => $qty, 'subtotal' => $subtotal];
    $total += $subtotal;
}

$success = false;
$message = '';
$payment_method = 'COD';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sent_csrf = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'], $sent_csrf)) {
        $message = '<div class="alert alert-danger">Invalid session. Please refresh the page and try again.</div>';
    } else {
        $name = trim($_POST['name'] ?? '');
        $street_address = trim($_POST['street_address'] ?? '');
        $barangay = trim($_POST['barangay'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $province = trim($_POST['province'] ?? '');
        $notes = trim($_POST['notes'] ?? '');
        $address = "$street_address, $barangay, $city, $province"; 
        $payment_method = 'COD';
        $order_type = 'B2C'; 

        if ($name === '' || $street_address === '' || $barangay === '' || $city === '' || $province === '') {
            $message = '<div class="alert alert-danger">Please fill in your full name and all address fields.</div>';
        } elseif (!preg_match('/^[A-Za-z\s.\'-]+$/', $name)) {
            $message = '<div class="alert alert-danger">Full Name can only contain letters, spaces, dots, apostrophes, and hyphens.</div>';
        } elseif (empty($items)) {
            $message = '<div class="alert alert-danger">Your cart items are invalid. Please try again.</div>';
        } else {
            // Determine payment status based on specific payment method
            switch ($payment_method) {
                case 'MASTER CARD':
                case 'VISA':
                    $payment_status = 'Processing'; 
                    break;
                case 'GCash':
                case 'PAYMAYA':
                case 'MAYA':
                case 'BDO':
                case 'BPI':
                case 'METRO BANK':
                case 'GO TYME':
                case 'Cebuana':
                    $payment_status = 'Awaiting Payment';
                    break;
                case 'COD':
                default:
                    $payment_status = 'Pending';
                    break;
            }

            try {
                $pdo->beginTransaction();
                $uid = is_logged_in() ? $_SESSION['user']['id'] : null;
                $status = 'pending';
                $customer_name = $name;

                // Inserting order
                $stmt = $pdo->prepare("INSERT INTO orders 
                    (user_id, total, address, status, created_at, payment_method, payment_status, customer_name, notes)
                    VALUES (?, ?, ?, ?, NOW(), ?, ?, ?, ?)");
                $stmt->execute([
                    $uid, $total, $address, $status,
                    $payment_method, $payment_status, $customer_name, $notes
                ]);
                $stmt->closeCursor();
                $order_id = $pdo->lastInsertId();

                $oi = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
                $getInventory = $pdo->prepare("SELECT id, quantity FROM inventory WHERE product_id = ? AND location_id = 1 FOR UPDATE");
                $updateInventory = $pdo->prepare("UPDATE inventory SET quantity = ? WHERE id = ?");
                $insertStockHistory = $pdo->prepare("INSERT INTO product_stock_history (product_id, action, quantity, note, created_at) VALUES (?, 'OUT', ?, ?, NOW())");
                $insertStockMovement = $pdo->prepare("INSERT INTO stock_movements (product_id, location_id, movement_type, quantity, remark, user_name, created_by, created_at) VALUES (?, 1, 'OUT', ?, ?, ?, ?, NOW())");

                foreach ($items as $it) {
                    $product = $it['p'];
                    $pid = (int)$product['id'];
                    $qty = (int)$it['qty'];
                    $price = (float)$product['price'];
                    $oi->execute([$order_id, $pid, $qty, $price]);
                    $oi->closeCursor();

                    $getInventory->execute([$pid]);
                    $inv = $getInventory->fetch(PDO::FETCH_ASSOC);
                    $getInventory->closeCursor();

                    if ($inv) {
                        $invId = $inv['id'];
                        $newQty = max(0, (int)$inv['quantity'] - $qty);
                        $updateInventory->execute([$newQty, $invId]);
                        $updateInventory->closeCursor();

                        $note = "Order #$order_id checkout";
                        $insertStockHistory->execute([$pid, $qty, $note]);
                        $insertStockHistory->closeCursor();

                        $user_name = is_logged_in() ? ($_SESSION['user']['name'] ?? 'User') : 'Guest';
                        $insertStockMovement->execute([$pid, $qty, $note, $user_name, $user_name]);
                        $insertStockMovement->closeCursor();
                    }
                }
                
                $pdo->commit();
                $_SESSION['cart'] = [];
                $success = true;
            } catch (PDOException $e) {
                $pdo->rollBack();
                error_log("Checkout error: " . $e->getMessage());
                $message = '<div class="alert alert-danger">An error occurred while placing your order. Please try again.</div>';
            }
        }
    }
}
?>

<div class="container my-5">
    <style>
        /* Container for the square shape */
        .payment-option-card {
            cursor: pointer;
            text-align: center;
            padding: 2px; /* Small padding for small squares */
            /* ASPECT RATIO TRICK: Create a square */
            position: relative;
            width: 100%;
            height: 0;
            padding-bottom: 100%; /* Perfect 1:1 aspect ratio (Square) */
        }
        
        /* Content to fill the square */
        .card-content-wrapper {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            
            border: 2px solid #ccc;
            border-radius: 8px;
            transition: all 0.2s;
            background-color: #fff;
            box-sizing: border-box; 
        }
        
        .payment-option-card:hover .card-content-wrapper {
            border-color: #007bff;
            box-shadow: 0 0 10px rgba(0, 123, 255, 0.2);
        }
        
        .payment-option-card input[type="radio"] {
            display: none; 
        }
        
        .payment-option-card input[type="radio"]:checked + .card-content-wrapper {
            border: 3px solid #198754; 
            background-color: #d1e7dd; 
            box-shadow: 0 0 10px rgba(25, 135, 84, 0.4);
        }

        .card-content-wrapper img {
            max-width: 90%; 
            height: auto;
            /* Removed max-height constraint, allowing image to fill more of the box */
            margin-bottom: 0; /* Ensures image sits centrally */
        }
        
        .payment-grid {
            margin: 0;
        }
    </style>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="fw-bold text-primary"><i class="bi bi-cart-check"></i> Checkout</h1>
        <a href="cart.php" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back to Cart</a>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success shadow-sm p-4 rounded-3">
        <h4 class="alert-heading mb-2"><i class="bi bi-check-circle"></i> Order placed successfully!</h4>
        <hr>
        <p>You selected <strong>Cash on Delivery</strong>. Please prepare the exact amount upon delivery.</p>

        <div class="text-center mt-4">
            <a href="/sweepxpress/index.php" class="btn btn-primary btn-lg">Back to Shop</a>
        </div>
    </div>
<?php else: ?>

        <?= $message ?>
        <form method="post" class="row g-4">
            <input type="hidden" name="csrf_token" value="<?= h($csrf_token) ?>">

            <div class="col-md-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-primary text-white fw-semibold"><i class="bi bi-truck"></i> Shipping Details</div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="name" class="form-control" required value="<?= h($_SESSION['user']['name'] ?? '') ?>" pattern="^[A-Za-z\s.'-]+$">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">House/Unit Number & Street</label>
                            <input type="text" name="street_address" class="form-control" required value="<?= h($_POST['street_address'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Barangay</label>
                            <input type="text" name="barangay" class="form-control" required value="<?= h($_POST['barangay'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">City/Municipality</label>
                            <input type="text" name="city" class="form-control" required value="<?= h($_POST['city'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Province</label>
                            <input type="text" name="province" class="form-control" required value="<?= h($_POST['province'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes (optional)</label>
                            <textarea name="notes" rows="2" class="form-control"><?= h($_POST['notes'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card shadow-sm border-0 h-100">
                    <div class="card-header bg-success text-white fw-semibold"><i class="bi bi-cash-stack"></i> Payment Method</div>
                    <div class="card-body">
                        
                        <div class="row g-2 payment-grid"> 
                            <?php 
                            // Using your correct image paths
                            $payment_options = [
                            'COD' => ['type' => 'COD', 'image' => 'assets/Cash.png'],
                        ];


                            $current_selection = $_POST['payment_method'] ?? 'COD';

                            foreach ($payment_options as $value => $data):
                                $checked = ($current_selection === $value) ? 'checked' : '';
                                $id = strtolower(str_replace(' ', '-', $value));
                                $image_url = h($data['image']);
                            ?>
                                <div class="col-3 d-flex flex-column align-items-center"> 
                                    <label class="payment-option-card mb-1">
                                        <input type="radio" name="payment_method" value="<?= h($value) ?>" id="<?= $id ?>" <?= $checked ?> required>
                                        <div class="card-content-wrapper">
                                            <img src="<?= $image_url ?>" alt="<?= h($value) ?>">
                                            </div>
                                    </label>
                                    <span class="payment-label text-center text-truncate px-1" style="font-size: 0.7em; font-weight: 600; height: 1.5em;"><?= h($value) ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <h6 class="mt-4 border-bottom pb-1">Payment Instructions</h6>

                        <div id="e-wallet-details" class="alert alert-info mt-3 border-start border-4 border-success d-none">
                            <p class="mb-1 fw-bold">E-Wallet Transfer (GCash/PAYMAYA/MAYA) Instructions:</p>
                            <p><strong>Account Name:</strong> SweepxPress Payments</p>
                            <p><strong>Account Number/Phone:</strong> 0917-123-4567</p>
                            <small class="text-muted">Send payment, then email screenshot for verification.</small>
                        </div>

                        <div id="bank-transfer-details" class="alert alert-info mt-3 border-start border-4 border-primary d-none">
                            <p class="mb-1 fw-bold">Bank Transfer Instructions:</p>
                            <p><strong>Account Name:</strong> SweepxPress Retail</p>
                            <p><strong>Account Number:</strong> 123456789</p>
                            <small class="text-muted">Ensure payment is sent to the specific bank account corresponding to your choice.</small>
                        </div>
                        
                        <div id="remittance-details" class="alert alert-info mt-3 border-start border-4 border-warning d-none">
                            <p class="mb-1 fw-bold">Remittance (Cebuana Lhuillier) Instructions:</p>
                            <p><strong>Receiver Name:</strong> Juan Dela Cruz</p>
                            <p><strong>Phone Number:</strong> 0917-123-4567</p>
                            <small class="text-muted">Note the transaction number and email it for confirmation.</small>
                        </div>

                        <div id="card-processing-details" class="alert alert-info mt-3 border-start border-4 border-secondary d-none">
                            <p class="mb-1 fw-bold">Card Payment:</p>
                            <p>You will be redirected to a secure payment gateway upon clicking "Place Order".</p>
                        </div>
                        
                        <div id="cod-details" class="alert alert-info mt-3 border-start border-4 border-dark d-none">
                            <p class="mb-1 fw-bold">Cash on Delivery (COD):</p>
                            <p>Please prepare the exact cash amount for your order upon delivery.</p>
                        </div>

                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-dark text-white fw-semibold"><i class="bi bi-receipt"></i> Order Summary</div>
                    <div class="card-body table-responsive">
                        <table class="table table-striped align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Image</th>
                                    <th>Item</th>
                                    <th>Qty</th>
                                    <th>Price</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $it): ?>
                                    <tr>
                                        <td><img src="<?= h($it['p']['image_path']); ?>" width="50" height="50" class="rounded"></td>
                                        <td><?= h($it['p']['name']); ?></td>
                                        <td><?= (int) $it['qty']; ?></td>
                                        <td>₱<?= number_format($it['p']['price'], 2); ?></td>
                                        <td>₱<?= number_format($it['subtotal'], 2); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="table-info fw-bold">
                                    <td colspan="4" class="text-end">Total</td>
                                    <td>₱<?= number_format($total, 2); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-12 mt-3">
            <button class="btn btn-success w-100 btn-lg shadow-sm" type="button" id="confirmOrderBtn">
                <i class="bi bi-bag-check"></i> Place Order
            </button>
        </div>

        </form>
    <?php endif; ?>
</div>
<!-- Order Confirmation Modal -->
<div class="modal fade" id="confirmOrderModal" tabindex="-1" aria-labelledby="confirmOrderModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="confirmOrderModalLabel">
          <i class="bi bi-clipboard-check"></i> Confirm Your Order
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body">
        <!-- Shipping Info -->
        <div class="mb-3">
          <h6 class="fw-bold text-success"><i class="bi bi-person"></i> Shipping Information</h6>
          <p class="mb-1"><strong>Name:</strong> <span id="confirmName"></span></p>
          <p class="mb-1"><strong>Address:</strong> <span id="confirmAddress"></span></p>
          <p class="mb-3"><strong>Notes:</strong> <span id="confirmNotes"></span></p>
        </div>

        <!-- Payment Info -->
        <div class="mb-3">
          <h6 class="fw-bold text-success"><i class="bi bi-cash-stack"></i> Payment Method</h6>
          <p><strong id="confirmPaymentMethod"></strong></p>
        </div>

        <!-- Items Table -->
        <div>
          <h6 class="fw-bold text-success"><i class="bi bi-basket"></i> Items Summary</h6>
          <div class="table-responsive border rounded">
            <table class="table table-sm table-striped mb-0 align-middle">
              <thead class="table-light">
                <tr>
                  <th>Image</th>
                  <th>Item</th>
                  <th class="text-center">Qty</th>
                  <th class="text-end">Subtotal</th>
                </tr>
              </thead>
              <tbody id="confirmItems"></tbody>
              <tfoot>
                <tr class="fw-bold table-info">
                  <td colspan="3" class="text-end">Total</td>
                  <td class="text-end text-success">₱<?= number_format($total, 2); ?></td>
                </tr>
              </tfoot>
            </table>
          </div>
        </div>
      </div>

      <!-- Modal Footer -->
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
          <i class="bi bi-x-circle"></i> Cancel
        </button>
        <button type="button" class="btn btn-success" id="confirmSubmitBtn">
          <i class="bi bi-bag-check"></i> Yes, Place Order
        </button>
      </div>
    </div>
  </div>
</div>


<?php require_once __DIR__ . '/includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show COD instructions by default
    document.getElementById('cod-details').classList.remove('d-none');

    const confirmOrderBtn = document.getElementById('confirmOrderBtn');
    const confirmModal = new bootstrap.Modal(document.getElementById('confirmOrderModal'));
    const confirmSubmitBtn = document.getElementById('confirmSubmitBtn');
    const checkoutForm = document.querySelector('form');

    // Modal fields
    const confirmName = document.getElementById('confirmName');
    const confirmAddress = document.getElementById('confirmAddress');
    const confirmNotes = document.getElementById('confirmNotes');
    const confirmPaymentMethod = document.getElementById('confirmPaymentMethod');
    const confirmItems = document.getElementById('confirmItems');

    // Get product rows from checkout summary (excluding total)
    const itemRows = Array.from(document.querySelectorAll('.table tbody tr')).slice(0, -1);

    confirmOrderBtn.addEventListener('click', function() {
        // Collect shipping info
        const name = checkoutForm.querySelector('input[name="name"]').value.trim();
        const street = checkoutForm.querySelector('input[name="street_address"]').value.trim();
        const barangay = checkoutForm.querySelector('input[name="barangay"]').value.trim();
        const city = checkoutForm.querySelector('input[name="city"]').value.trim();
        const province = checkoutForm.querySelector('input[name="province"]').value.trim();
        const notes = checkoutForm.querySelector('textarea[name="notes"]').value.trim();
        const payment = checkoutForm.querySelector('input[name="payment_method"]:checked')?.value || 'COD';

        // Fill modal details
        confirmName.textContent = name || '(No name provided)';
        confirmAddress.textContent = `${street}, ${barangay}, ${city}, ${province}`;
        confirmNotes.textContent = notes || 'None';
        confirmPaymentMethod.textContent = payment;

        // Clear and repopulate the items
        confirmItems.innerHTML = '';
        itemRows.forEach(row => {
            const imgSrc = row.querySelector('img')?.getAttribute('src') || '';
            const itemName = row.children[1].textContent.trim();
            const qty = row.children[2].textContent.trim();
            const subtotal = row.children[4].textContent.trim();

            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><img src="${imgSrc}" alt="${itemName}" width="50" height="50" class="rounded"></td>
                <td>${itemName}</td>
                <td class="text-center">${qty}</td>
                <td class="text-end">${subtotal}</td>
            `;
            confirmItems.appendChild(tr);
        });

        confirmModal.show();
    });

    confirmSubmitBtn.addEventListener('click', function() {
        confirmModal.hide();
        checkoutForm.submit();
    });
});
</script>
