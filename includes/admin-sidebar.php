<div class="admin-sidebar" id="adminSidebar">

    <div class="admin-brand">

        JAN POULTRY

    </div>

    <nav class="admin-menu">

        <a href="/admin">
            Dashboard
        </a>

        <a href="/admin/products">
            Products
        </a>

        <a href="/admin/orders">
            Orders
        </a>

        <a href="/admin/users">
            Customers
        </a>

        <a href="/admin/admins">
            Admins
        </a>

        <?php if (Auth::isSuperAdmin()): ?>

        <a href="/admin/staff">
            Recovery Center
        </a>

        <?php endif; ?>

        <a href="/admin/deliveries">
            Deliveries
        </a>

        <a href="/admin/logs">
            Logs
        </a>

        <a href="/admin/settings">
            Settings
        </a>

        <a href="/admin/profile">
            Profile
        </a>

        <a href="/admin/logout">
            Logout
        </a>

    </nav>

</div>
