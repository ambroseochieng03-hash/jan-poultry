<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Route table:  clean public path  =>  page file (relative to project root)
|--------------------------------------------------------------------------
| Rules: auth/* and customer/* pages live at /name, admin/* pages live at
| /admin/name. Browsers never see .php or the real folder layout.
*/

return [
    '/' => 'index.php',
    '/add-to-cart' => 'customer/add-to-cart.php',
    '/addresses' => 'customer/addresses.php',
    '/admin' => 'admin/index.php',
    '/admin/admin-create' => 'admin/admin-create.php',
    '/admin/admin-delete' => 'admin/admin-delete.php',
    '/admin/admin-toggle' => 'admin/admin-toggle.php',
    '/admin/admins' => 'admin/admins.php',
    '/admin/categories' => 'admin/categories.php',
    '/admin/dashboard' => 'admin/dashboard.php',
    '/admin/deliveries' => 'admin/deliveries.php',
    '/admin/forgot-password-ajax' => 'admin/forgot-password-ajax.php',
    '/admin/login' => 'admin/login.php',
    '/admin/logout' => 'admin/logout.php',
    '/admin/logs' => 'admin/logs.php',
    '/admin/order-status' => 'admin/order-status.php',
    '/admin/order-view' => 'admin/order-view.php',
    '/admin/orders' => 'admin/orders.php',
    '/admin/product-create' => 'admin/product-create.php',
    '/admin/product-create-ajax' => 'admin/product-create-ajax.php',
    '/admin/product-delete' => 'admin/product-delete.php',
    '/admin/product-edit' => 'admin/product-edit.php',
    '/admin/product-toggle' => 'admin/product-toggle.php',
    '/admin/products' => 'admin/products.php',
    '/admin/profile' => 'admin/profile.php',
    '/admin/profile-password' => 'admin/profile-password.php',
    '/admin/profile-update' => 'admin/profile-update.php',
    '/admin/recovery-delete' => 'admin/recovery-delete.php',
    '/admin/recovery-recover' => 'admin/recovery-recover.php',
    '/admin/reset-password' => 'admin/reset-password.php',
    '/admin/settings' => 'admin/settings.php',
    '/admin/staff' => 'admin/staff.php',
    '/admin/user-delete' => 'admin/user-delete.php',
    '/admin/user-toggle' => 'admin/user-toggle.php',
    '/admin/users' => 'admin/users.php',
    '/cart-add' => 'customer/cart-add.php',
    '/cart' => 'customer/cart.php',
    '/cart-update' => 'customer/cart-update.php',
    '/change-password' => 'customer/change-password.php',
    '/checkout' => 'customer/checkout.php',
    '/checkout-place' => 'customer/checkout-place.php',
    '/push-subscribe' => 'customer/push-subscribe.php',
    '/dashboard' => 'customer/dashboard.php',
    '/edit-profile' => 'customer/edit-profile.php',
    '/forgot-password-ajax' => 'auth/forgot-password-ajax.php',
    '/auth-ajax' => 'auth/auth-ajax.php',
    '/google-auth' => 'auth/google-auth.php',
    '/guest-login' => 'auth/guest-login.php',
    '/login' => 'auth/login.php',
    '/logout' => 'auth/logout.php',
    '/notification-delete' => 'customer/notification-delete.php',
    '/notifications' => 'customer/notifications.php',
    '/order-action' => 'customer/order-action.php',
    '/order-view' => 'customer/order-view.php',
    '/orders' => 'customer/orders.php',
    '/products' => 'customer/products.php',
    '/profile' => 'customer/profile.php',
    '/register' => 'auth/register.php',
    '/reset-password' => 'auth/reset-password.php',
];
