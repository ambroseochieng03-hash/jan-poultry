<?php

$isLoggedIn = isset($_SESSION['user_id']);
$isGuest    = isset($_SESSION['guest_id']);

$currentPage = trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
?>

<nav class="navbar">

    <div class="nav-container">

        <a href="/" class="logo">
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
                <a href="/">
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
                    <a href="/dashboard">
                        Dashboard
                    </a>
                </li>

                <li>
                    <a href="/logout">
                        Logout
                    </a>
                </li>

            <?php elseif ($isGuest): ?>

                <li>
                    <a href="/dashboard">
                        Continue
                    </a>
                </li>

                <li>
                    <a href="/logout">
                        Exit Guest
                    </a>
                </li>

            <?php else: ?>

                <li>
                    <a href="/login">
                        Login
                    </a>
                </li>

                <li>
                    <a href="/register">
                        Register
                    </a>
                </li>

                <li>
                    <a href="/guest-login">
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

            <a href="/">
                Home
            </a>

            <?php if ($isLoggedIn): ?>

                <a href="/dashboard">
                    Dashboard
                </a>

                <a href="/logout">
                    Logout
                </a>

            <?php elseif ($isGuest): ?>

                <a href="/dashboard">
                    Continue
                </a>

                <a href="/logout">
                    Exit Guest
                </a>

            <?php else: ?>

                <a href="/guest-login">
                    Guest Login
                </a>

                <a href="/login">
                    Sign In
                </a>

                <a href="/register">
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