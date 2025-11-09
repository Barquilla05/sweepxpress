<?php
// purchaseorder.php

// 1. CORE CONFIG: MUST BE FIRST, as it handles PDO and session start.
require_once __DIR__ . '/../config.php'; 

// Initialize variables BEFORE any output starts
$message = '';
$user = $_SESSION['user'] ?? null;
$userId = $user['id'] ?? null; // Get the authenticated user's ID
$order_submitted = false; // Tracks if the submission was successful

// 2. ROLE CHECK: Must happen before output (Potential redirect)
if (!$user || ($user['role'] !== 'business' && $user['role'] !== 'admin')) {
    $_SESSION['error_message'] = "You must be logged in as a Business or Admin to create a Purchase Order.";
    header("Location: /sweepxpress/login.php");
    exit;
}

// Load cart data
$cart = $_SESSION['cart'] ?? [];
$items = [];
$total = 0;

if ($cart) {
    // Sanitize IDs for the SQL IN clause
    $ids = implode(',', array_map('intval', array_keys($cart)));
    
    // Fetch products
    $stmt = $pdo->query("SELECT * FROM products WHERE id IN ($ids)"); 
    
    foreach ($stmt as $row) {
        $qty = (int)($cart[$row['id']] ?? 0);
        if ($qty > 0) {
            $subtotal = $qty * (float)$row['price'];
            $items[] = ['p'=>$row, 'qty'=>$qty, 'subtotal'=>$subtotal];
            $total += $subtotal;
        }
    }
}

// 3. EMPTY CART CHECK: Must happen before output (Potential redirect)
// Only redirect if cart is empty AND this is a fresh GET request (not a failed POST attempt)
if (!$items && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['info_message'] = "Your cart is empty. Please add items to create a Purchase Order.";
    header("Location: /sweepxpress/cart.php");
    exit;
}

// --- UPDATED LOGIC: 4. HANDLE PURCHASE ORDER SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$order_submitted) {
    
    // Gather and sanitize input
    $companyName = trim($_POST['company_name'] ?? '');
    $contactName = trim($_POST['contact_name'] ?? '');
    $email = $user['email'] ?? ''; 
    $billingAddress = trim($_POST['billing_address'] ?? '');
    $shippingAddress = trim($_POST['shipping_address'] ?? $billingAddress); 
    $paymentTerms = trim($_POST['payment_terms'] ?? '');

    // Basic validation
    if (empty($companyName) || empty($contactName) || empty($billingAddress) || empty($paymentTerms)) {
        $message = '<div class="alert alert-danger">Please fill in all required company and address fields.</div>';
    } elseif ($items) {
        
        // Database preparation
        $business_details_note = "--- PURCHASE ORDER REQUEST DETAILS ---\n"
                               . "Buyer Company Name: " . $companyName . "\n"
                               . "Buyer Contact Name: " . $contactName . "\n"
                               . "Buyer Email: " . $email . "\n"
                               . "Billing Address: " . $billingAddress . "\n"
                               . "------------------------------------";
        
        // Start PDO Transaction
        $pdo->beginTransaction();
        try {
            
            // A. Insert into `orders` table (FIXED: REMOVED 'updated_at')
            $stmt = $pdo->prepare("
                INSERT INTO orders (user_id, total, address, customer_name, notes, payment_terms, status, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'Pending PO', NOW())
            ");
            
            $stmt->execute([
                $userId, 
                $total, 
                $shippingAddress, 
                $contactName, 
                $business_details_note, 
                $paymentTerms
            ]);
            $orderId = $pdo->lastInsertId();

            // B. Insert into `order_items` table (FIXED: REMOVED 'subtotal' from query and execution array)
            $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            foreach ($items as $it) {
                $itemStmt->execute([
                    $orderId, 
                    $it['p']['id'], 
                    $it['qty'], 
                    $it['p']['price']
                    // Removed $it['subtotal']
                ]);
            }
            
            // C. Insert into 'purchase_orders' table (linking order to PO status)
            // 🔴 TEMPORARILY COMMENTED OUT DUE TO MISSING TABLE ERROR (sweepxpress_db.purchase_orders)
            /*
            $po_sql = "INSERT INTO purchase_orders (order_id) VALUES (?)";
            $po_stmt = $pdo->prepare($po_sql);
            $po_stmt->execute([$orderId]);
            */
            
            // Commit transaction
            $pdo->commit();

            // 4.4. Clear the cart and set the local success message (KEY: NO REDIRECT)
            unset($_SESSION['cart']);
            $order_submitted = true; // Set flag to hide the form and show success block
            $message = '<div class="alert alert-success alert-dismissible fade show" role="alert">';
            $message .= '<strong>Success! Your order has been submitted!</strong> Purchase Order Request (No. #<strong>' . h($orderId) . '</strong>) has been placed.';
            // NOTE: The link below will still work, but you'll need to create the 'purchase_orders.php' page next.
            $message .= '<p>You can view all your pending requests on the <a href="/sweepxpress/purchase_orders.php" class="alert-link">Purchase Orders List</a>.</p>';
            $message .= '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
            $message .= '</div>';


        } catch (Exception $e) {
            $pdo->rollBack();
            // --- KEPT MODIFIED: Showing detailed error message for debugging in case of new issue ---
            error_log("Purchase Order Error: " . $e->getMessage()); 
            $message = '<div class="alert alert-danger">';
            $message .= '<strong>An error occurred while placing your order.</strong><br>';
            $message .= 'Database Error: ' . h($e->getMessage()); // Display the specific error
            $message .= '</div>';
            // --- END MODIFIED ---
        }
    } else {
        $message = '<div class="alert alert-warning">The cart was empty during submission. No order was created.</div>';
    }
}
// --- END UPDATED LOGIC ---

// 5. INCLUDE HEADER: Only now can we include files that output HTML.
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container my-5">
    <h1><i class="bi bi-file-earmark-text-fill"></i> Purchase Order Request</h1>
    
    <?= $message; // Displaying the local success or error message ?>

    <?php if (!$order_submitted && $items): // Only show form if order hasn't been submitted AND cart is not empty ?>
    
        <p class="lead">Please verify your order and provide the required information to generate your Purchase Order request.</p>
        
        <div class="card shadow-lg border-0">
            <div class="card-body p-4 p-md-5">
                <form method="post">
                    
                    <h4 class="mb-4 text-primary"><i class="bi bi-person-lines-fill"></i> Buyer Details</h4>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="company_name" class="form-label">Buyer Company Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="company_name" name="company_name" required value="<?= h($_POST['company_name'] ?? ''); ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="contact_name" class="form-label">Buyer Contact Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="contact_name" name="contact_name" value="<?= h($_POST['contact_name'] ?? $user['name'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12 mb-3">
                            <label for="buyer_email" class="form-label">Buyer Email</label>
                            <input type="email" class="form-control" id="buyer_email" value="<?= h($user['email'] ?? ''); ?>" disabled readonly>
                        </div>
                    </div>
                    
                    <h4 class="mt-4 mb-3 text-primary"><i class="bi bi-geo-alt-fill"></i> Address & Terms</h4>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="billing_address" class="form-label">Billing Address <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="billing_address" name="billing_address" rows="3" required><?= h($_POST['billing_address'] ?? ''); ?></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="shipping_address" class="form-label">Shipping Address (Leave blank if same as Billing)</label>
                            <textarea class="form-control" id="shipping_address" name="shipping_address" rows="3"><?= h($_POST['shipping_address'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 mb-4">
                            <label for="payment_terms" class="form-label">Payment Terms <span class="text-danger">*</span></label>
                            <select class="form-select" id="payment_terms" name="payment_terms" required>
                                <?php $selectedTerm = $_POST['payment_terms'] ?? ''; ?>
                                <option value="" disabled <?= empty($selectedTerm) ? 'selected' : '' ?>>Select Payment Terms</option>
                                <option value="Net 30" <?= $selectedTerm == 'Net 30' ? 'selected' : '' ?>>Net 30</option>
                                <option value="Net 45" <?= $selectedTerm == 'Net 45' ? 'selected' : '' ?>>Net 45</option>
                                <option value="Net 60" <?= $selectedTerm == 'Net 60' ? 'selected' : '' ?>>Net 60</option>
                                <option value="Upon Delivery" <?= $selectedTerm == 'Upon Delivery' ? 'selected' : '' ?>>Upon Delivery (COD)</option>
                            </select>
                        </div>
                    </div>
                    
                    <hr class="my-5">

                    <h4 class="mb-3 text-primary"><i class="bi bi-cart-fill"></i> Final Order Summary</h4>
                    
                    <div class="table-responsive mb-4">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-primary">
                                <tr>
                                    <th class="w-50">Product</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($items as $it): ?>
                                    <tr>
                                        <td><?= h($it['p']['name']); ?></td>
                                        <td class="text-center"><?= (int)$it['qty']; ?></td>
                                        <td class="text-end">₱<?= number_format($it['p']['price'], 2); ?></td>
                                        <!-- Note: We are calculating the subtotal here for display, even though we don't save the column in the database -->
                                        <td class="text-end">₱<?= number_format($it['subtotal'], 2); ?></td> 
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-light">
                                    <td colspan="3" class="fw-bold text-end">Grand Total</td>
                                    <td class="fw-bold text-end"><strong>₱<?= number_format($total, 2); ?></strong></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    <div class="d-grid gap-2 d-md-block">
                        <button class="btn btn-primary btn-lg w-100" type="submit">Submit Purchase Order Request</button>
                        <a href="/sweepxpress/cart.php" class="btn btn-secondary w-100 mt-2">← Back to Cart</a>
                    </div>
                </form>
            </div>
        </div>

    <?php elseif ($order_submitted): // Show success confirmation block ?>
        <div class="text-center py-5">
            <h3>✅ Purchase Order Request Submitted!</h3>
            <p class="lead">Your request has been saved and is now pending review.</p>
            <a href="/sweepxpress/purchase_orders.php" class="btn btn-lg btn-success mt-3">View All Purchase Orders</a>
            <a href="/sweepxpress/index.php" class="btn btn-lg btn-secondary mt-3 ms-2">Continue Shopping</a>
        </div>
    <?php else: ?>
        <div class="alert alert-info text-center">
            The cart is currently empty. Please add items to create a Purchase Order.
        </div>
    <?php endif; ?>
</div>

<?php 
// 🟢 PATH FIX: Use /../ to go up one directory to find includes/footer.php
require_once __DIR__ . '/../includes/footer.php'; 
?>
