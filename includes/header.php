<?php
require_once __DIR__ . '/../config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SweepXpress</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <link rel="stylesheet" href="/sweepxpress/assets/style.css">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">

    <style>
        *:focus {
            outline: none !important;
            box-shadow: none !important;
        }

        /* Adjusted for the new light blue background */
        .navbar-light .nav-link,
        .navbar-text {
            color: #1a1a1a !important; /* Dark text for light background */
            transition: color 0.3s ease-in-out;
        }
        
        /* Ensures the text color is correct for logged-in user info */
        .navbar-text.text-light {
            color: #1a1a1a !important; 
        }

        .nav-link::after {
            content: '';
            position: absolute;
            width: 100%;
            transform: scaleX(0);
            height: 2px;
            bottom: 0;
            left: 0;
            background-color: #3366ff; /* Primary Blue underline */
            transform-origin: bottom right;
            transition: transform 0.3s ease-in-out;
        }

        .nav-link:hover::after {
            transform: scaleX(1);
            transform-origin: bottom left;
        }

        /* Logout button colors - changed to be a blue button on light background */
        .btn-logout {
            background-color: #3366ff; /* Primary Blue */
            color: #ffffff;
            border: 1px solid #3366ff;
            transition: all 0.3s ease;
            font-weight: 600;
            padding: 10px 16px;
            border-radius: 14px;
        }
        .btn-logout:hover {
            background-color: #5588ff; /* Accent Blue on hover */
            color: #ffffff;
            border-color: #5588ff;
        }

        /* Style para sa profile picture */
        .profile-img {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 8px;
            border: 2px solid #3366ff; /* Primary Blue border */
        }
    </style>

    <script>
        /**
         * Updates the cart badge count and toggles its visibility.
         * Called by the 'addToCart' function in assets/script.js.
         * @param {number} newCount - The new total number of items in the cart.
         */
        function updateCartBadge(newCount) {
            const countDisplay = document.getElementById('cart-badge-count');
            const badge = document.getElementById('cart-badge');

            if (countDisplay && badge) {
                // 1. Update the display count text
                countDisplay.textContent = newCount;
                
                // 2. Show or Hide the entire badge based on the count
                if (newCount > 0) {
                    badge.classList.remove('d-none'); // Show the badge
                } else {
                    badge.classList.add('d-none');    // Hide the badge
                }
            }
        }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" defer></script>
    <script src="/sweepxpress/assets/script.js"></script>
</head>
<body>
    
<nav class="navbar navbar-expand-lg navbar-light shadow-sm">
    <div class="container-fluid">

        <?php if (is_logged_in()): ?>
            <button class="btn btn-outline-primary me-2" id="sidebarToggle">&#9776;</button>
        <?php endif; ?>

        <a class="navbar-brand fw-bold" href="<?php 
            // Check for supplier and admin roles to set the correct dashboard link
            if (is_admin()) {
                echo '/sweepxpress/admin/dashboard.php';
            } elseif (is_supplier()) {
                echo '/sweepxpress/supplier/dashboard.php';
            } else {
                echo '/sweepxpress/index.php';
            }
        ?>"> 
            Sweep<span class="text-primary">X</span>press
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-lg-center">

                <?php if (is_logged_in()): ?>
                    <?php 
                        // Get profile image or default
                        $profileImage = !empty($_SESSION['user']['profile_image']) 
                            ? h($_SESSION['user']['profile_image']) 
                            : '/sweepxpress/assets/profile-icon.jpg';
                    ?>
                    <li class="nav-item me-3 d-flex align-items-center">
                        <img src="<?php echo $profileImage; ?>" alt="Profile" class="profile-img">
                        <span class="navbar-text text-light">Hi, <?php echo h($_SESSION['user']['username']); ?></span>
                    </li>

                    <?php if (!is_admin() && !is_supplier()): // Only show cart to regular users ?>
                        
                        <?php 
                            // Get the total number of items in the cart
                            $cartCount = array_sum($_SESSION['cart'] ?? []);
                            // Determine initial visibility class
                            $badge_visibility_class = ($cartCount == 0 ? 'd-none' : '');
                        ?>

                        <li class="nav-item me-3"> 
                            <a class="nav-link position-relative" href="/sweepxpress/cart.php" aria-label="Cart with items count">
                                
                                <i class="bi bi-cart-fill fs-5"></i>
                                
                                <span id="cart-badge" 
                                      class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger text-white <?php echo $badge_visibility_class; ?>">
                                    <span id="cart-badge-count"><?php echo $cartCount; ?></span>
                                    <span class="visually-hidden">items in cart</span>
                                </span>
                            </a>
                        </li>

                    <?php endif; ?>

                    <li class="nav-item">
                        <a class="btn btn-logout ms-lg-3" href="/sweepxpress/logout.php">Logout</a>
                    </li>

                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="/sweepxpress/index.php">Home</a></li>
                    <li class="nav-item"><a class="nav-link" href="/sweepxpress/about.php">About Us</a></li>
                    <li class="nav-item"><a class="nav-link" href="/sweepxpress/contact.php">Contact</a></li>
                    <li class="nav-item"><a class="nav-link" href="/sweepxpress/privacy.php">Privacy Policy</a></li>
                    <li class="nav-item"><a class="nav-link" href="/sweepxpress/terms.php">Terms & Conditions</a></li>
                    <li class="nav-item"><a class="nav-link" href="/sweepxpress/login.php">Login</a></li>
                <?php endif; ?>

            </ul>
        </div>
    </div>
</nav>

<?php if (is_logged_in()) require_once __DIR__ . '/sidebar.php'; ?>

<main class="container-fluid py-4"></main>