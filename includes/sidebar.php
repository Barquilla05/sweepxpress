<?php
// This file is included by header.php only if the user is logged in.
// It contains the role-specific navigation links for the slide-out sidebar.
?>
<div id="sidebar" class="sidebar text-white">
    <button class="sidebar-close btn btn-light btn-sm" id="closeSidebar">&times;</button>
    <ul class="list-unstyled mt-3">
        <?php if (is_admin()): ?>
            <li class="mb-2">
                <a href="/sweepxpress/admin/dashboard.php" class="d-flex align-items-center text-white text-decoration-none p-2 rounded">
                    <i class="bi bi-speedometer2 fs-4 fw-bold me-2 text-white"></i> Dashboard
                </a>
            </li>
            <li class="mb-2">
                <a href="/sweepxpress/admin/products.php" class="d-flex align-items-center text-white text-decoration-none p-2 rounded">
                    <i class="bi bi-box-seam fs-4 fw-bold me-2 text-white"></i> Products
                </a>
            </li>
            <li class="mb-2">
                <a href="/sweepxpress/admin/allorders.php" class="d-flex align-items-center text-white text-decoration-none p-2 rounded">
                    <i class="bi bi-bag-check fs-4 fw-bold me-2 text-white"></i> Orders
                </a>
            </li>
            <li class="mb-2">
                <a href="/sweepxpress/admin/deliveries.php" class="d-flex align-items-center text-white text-decoration-none p-2 rounded">
                    <i class="bi bi-truck fs-4 fw-bold me-2 text-white"></i> Deliveries
                </a>
            </li>
            <li class="mb-2">
                <a href="/sweepxpress/admin/inventory.php" class="d-flex align-items-center text-white text-decoration-none p-2 rounded">
                    <i class="bi bi-archive fs-4 fw-bold me-2 text-white"></i> Inventory
                </a>
            </li>
            <li class="mb-2">
                <a href="/sweepxpress/admin/customer_management.php" class="d-flex align-items-center text-white text-decoration-none p-2 rounded">
                    <i class="bi bi-people fs-4 fw-bold me-2 text-white"></i> Customers Management
                </a>
            </li>
            <li class="mb-2">
                <a href="/sweepxpress/admin/reports.php" class="d-flex align-items-center text-white text-decoration-none p-2 rounded">
                    <i class="bi bi-bar-chart-line fs-4 fw-bold me-2 text-white"></i> Reports & Analytics
                </a>
            </li>
            <li class="mb-2">
                <a href="/sweepxpress/admin/settings_admin.php" class="d-flex align-items-center text-white text-decoration-none p-2 rounded">
                    <i class="bi bi-gear fs-4 fw-bold me-2 text-white"></i> Settings
                </a>
            </li>
            <li class="mb-2">
                <a href="/sweepxpress/about.php" class="d-flex align-items-center text-white text-decoration-none p-2 rounded">
                    <i class="bi bi-info-circle fs-4 fw-bold me-2 text-white"></i> About Us
                </a>
            </li>

        <?php else: ?>
            <li class="mb-2">
                <a href="/sweepxpress/index.php" class="d-flex align-items-center text-white text-decoration-none p-2 rounded">
                    <i class="bi bi-house-door fs-4 fw-bold me-2 text-white"></i> Home
                </a>
            </li>
            <li class="mb-2">
                <a href="/sweepxpress/customers/my_orders.php" class="d-flex align-items-center text-white text-decoration-none p-2 rounded">
                    <i class="bi bi-receipt-cutoff fs-4 fw-bold me-2 text-white"></i> My Orders
                </a>
            </li>
            <li class="mb-2">
                <a href="/sweepxpress/customers/settings_customer.php" class="d-flex align-items-center text-white text-decoration-none p-2 rounded">
                    <i class="bi bi-person-gear fs-4 fw-bold me-2 text-white"></i> Settings
                </a>
            </li>
            <li class="mb-2">
                <a href="/sweepxpress/about.php" class="d-flex align-items-center text-white text-decoration-none p-2 rounded">
                    <i class="bi bi-info-circle fs-4 fw-bold me-2 text-white"></i> About Us
                </a>
            </li>
            <li class="mb-2">
                <a href="/sweepxpress/contact.php" class="d-flex align-items-center text-white text-decoration-none p-2 rounded">
                    <i class="bi bi-envelope fs-4 fw-bold me-2 text-white"></i> Contact
                </a>
            </li>
        <?php endif; ?>
    </ul>
</div>