<!-- 🌿 SweepXpress Elegant Footer -->
<footer class="site-footer">
    <div class="footer-container">
        <div class="footer-grid">
            <div class="footer-column">
                <h3>Contact Us</h3>
                <p><i class="fas fa-phone-alt"></i> (63) 761-77232</p>
                <p><i class="fas fa-envelope"></i> Huesales@hotmail.com</p>
            </div>

            <div class="footer-column">
                <h3>Products</h3>
                <ul>
                    <li><a href="/sweepxpress/index.php?category=Cleaning+Agents">Cleaning Agents</a></li>
                    <li><a href="/sweepxpress/index.php?category=Cleaning+Tools">Cleaning Tools</a></li>
                    <li><a href="/sweepxpress/index.php?category=Adhesives+%26+Tapes">Adhesives & Tapes</a></li>
                    <li><a href="/sweepxpress/index.php?category=Floor+%26+Surface+Care">Floor & Surface Care</a></li>
                    <li><a href="/sweepxpress/index.php?category=Equipment">Equipment</a></li>
                    <li><a href="/sweepxpress/index.php">All Products</a></li>
                </ul>
            </div>

            <div class="footer-column">
                <h3>Company</h3>
                <ul>
                    <li><a href="#">About Us</a></li>
                    <li><a href="#">Staffs</a></li>
                </ul>
            </div>

            <div class="footer-column">
                <h3>Follow Us</h3>
                <div class="social-icons">
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <p>© 2025 SweepXpress. All Rights Reserved.</p>
            <div class="footer-links">
                <a href="privacy.php">Privacy Policy</a>
                <a href="terms.php">Terms & Conditions</a>
            </div>
        </div>
    </div>
</footer>

<!-- 🌈 Modern Footer Styling -->
<style>
@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap');

.site-footer {
    background: linear-gradient(135deg, #007a47, #00b37e);
    color: #f8f8f8;
    padding: 40px 0 20px;
    font-family: 'Poppins', sans-serif;
    box-shadow: 0 -4px 12px rgba(0, 0, 0, 0.15);
}

.footer-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 25px;
}

.footer-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
    gap: 35px;
}

.footer-column h3 {
    font-size: 18px;
    font-weight: 600;
    margin-bottom: 12px;
    color: #ffffff;
    position: relative;
}

.footer-column h3::after {
    content: '';
    position: absolute;
    left: 0;
    bottom: -5px;
    width: 40px;
    height: 2px;
    background-color: #8cffc1;
    border-radius: 2px;
}

.footer-column p {
    margin: 6px 0;
    font-size: 14px;
    color: #e0e0e0;
}

.footer-column ul {
    list-style: none;
    padding: 0;
}

.footer-column ul li {
    margin: 7px 0;
}

.footer-column ul li a {
    color: #d8f3e8;
    text-decoration: none;
    font-size: 14px;
    transition: all 0.3s ease;
}

.footer-column ul li a:hover {
    color: #fff;
    text-shadow: 0 0 8px rgba(255, 255, 255, 0.6);
}

.social-icons {
    margin-top: 8px;
}

.social-icons a {
    color: #ffffff;
    font-size: 18px;
    margin-right: 14px;
    transition: transform 0.3s, color 0.3s;
}

.social-icons a:hover {
    color: #8cffc1;
    transform: scale(1.2);
}

.footer-bottom {
    border-top: 1px solid rgba(255,255,255,0.2);
    margin-top: 35px;
    padding-top: 12px;
    text-align: center;
    font-size: 14px;
    color: #d9fbe7;
}

.footer-links {
    margin-top: 8px;
}

.footer-links a {
    color: #c9f7e1;
    margin: 0 10px;
    text-decoration: none;
    font-size: 13px;
    transition: color 0.3s ease;
}

.footer-links a:hover {
    color: #ffffff;
}

/* Responsive */
@media (max-width: 768px) {
    .footer-grid {
        text-align: center;
    }

    .footer-column h3::after {
        left: 50%;
        transform: translateX(-50%);
    }

    .social-icons a {
        margin: 0 10px;
    }
}
</style>
