<?php

$isLoggedIn = isset($_SESSION['user_id']);
$isGuest    = isset($_SESSION['guest_id']);

$currentPage = basename($_SERVER['PHP_SELF']);
?>

<nav class="navbar">

    <div class="nav-container">

        <a href="/jan-poultry/" class="logo">
            JAN POULTRY
        </a>

        <button
            class="mobile-toggle"
            id="mobileToggle"
            aria-label="Open Menu">

            <i class="fas fa-bars"></i>

        </button>

        <ul class="nav-links" id="navLinks">

            <li>
                <a href="/jan-poultry/">
                    Home
                </a>
            </li>

            <li>
                <a href="#">
                    Products
                </a>
            </li>

            <li>
                <a href="#">
                    About
                </a>
            </li>

            <li>
                <a href="#">
                    Contact
                </a>
            </li>

            <?php if ($isLoggedIn): ?>

                <li>
                    <a href="/jan-poultry/customer/dashboard.php">
                        Dashboard
                    </a>
                </li>

                <li>
                    <a href="/jan-poultry/auth/logout.php">
                        Logout
                    </a>
                </li>

            <?php elseif ($isGuest): ?>

                <li>
                    <a href="/jan-poultry/customer/dashboard.php">
                        Continue
                    </a>
                </li>

                <li>
                    <a href="/jan-poultry/auth/logout.php">
                        Exit Guest
                    </a>
                </li>

            <?php else: ?>

                <li>
                    <a href="/jan-poultry/auth/login.php">
                        Login
                    </a>
                </li>

                <li>
                    <a href="/jan-poultry/auth/register.php">
                        Register
                    </a>
                </li>

                <li>
                    <a href="/jan-poultry/auth/guest-login.php">
                        Guest Access
                    </a>
                </li>

            <?php endif; ?>

        </ul>

        <div
            class="mobile-menu-overlay"
            id="mobileMenuOverlay">

        </div>

        <div
            class="mobile-menu-card"
            id="mobileMenuCard">

            <a href="/jan-poultry/">
                Home
            </a>

            <?php if ($isLoggedIn): ?>

                <a href="/jan-poultry/customer/dashboard.php">
                    Dashboard
                </a>

                <a href="/jan-poultry/auth/logout.php">
                    Logout
                </a>

            <?php elseif ($isGuest): ?>

                <a href="/jan-poultry/customer/dashboard.php">
                    Continue
                </a>

                <a href="/jan-poultry/auth/logout.php">
                    Exit Guest
                </a>

            <?php else: ?>

                <a href="/jan-poultry/auth/guest-login.php">
                    Guest Login
                </a>

                <a href="/jan-poultry/auth/login.php">
                    Sign In
                </a>

                <a href="/jan-poultry/auth/register.php">
                    Create Account
                </a>

            <?php endif; ?>

            <a
                href="#"
                id="mobileAboutLink">

                About

            </a>

            <a
                href="#"
                id="mobileContactLink">

                Contact

            </a>

            <hr>

            <a href="#" id="navbarRecoverAccount">
                Recover Your Account
            </a>

        </div>

    </div>

</nav>