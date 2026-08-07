<div class="admin-sidebar" id="adminSidebar">

    <div class="admin-brand">

        JAN POULTRY

    </div>

    <nav class="admin-menu">

        <a href="/jan-poultry/admin/index.php">
            Dashboard
        </a>

        <a href="/jan-poultry/admin/products.php">
            Products
        </a>

        <a href="/jan-poultry/admin/orders.php">
            Orders
        </a>

        <a href="/jan-poultry/admin/users.php">
            Customers
        </a>

        <a href="/jan-poultry/admin/admins.php">
            Admins
        </a>

        <?php if (Auth::isSuperAdmin()): ?>

        <a href="/jan-poultry/admin/staff.php">
            Recovery Center
        </a>

        <?php endif; ?>

        <a href="/jan-poultry/admin/deliveries.php">
            Deliveries
        </a>

        <a href="/jan-poultry/admin/logs.php">
            Logs
        </a>

        <a href="/jan-poultry/admin/settings.php">
            Settings
        </a>

        <a href="/jan-poultry/admin/profile.php">
            Profile
        </a>

        <a href="/jan-poultry/admin/logout.php">
            Logout
        </a>

    </nav>

</div>
