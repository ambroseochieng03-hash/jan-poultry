<?php

require_once
__DIR__ .
'/../includes/init.php';

require_once
__DIR__ .
'/../app/helpers/Auth.php';

require_once
__DIR__ .
'/../app/controllers/ProductController.php';

/*
|--------------------------------------------------------------------------
| ONLY POST REQUESTS
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD']
    !== 'POST'
) {

    header(
        'Location: products'
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| ADMIN AUTHORIZATION
|--------------------------------------------------------------------------
*/

Auth::requireAdmin();

/*
|--------------------------------------------------------------------------
| CSRF PROTECTION
|--------------------------------------------------------------------------
*/

if (
    !Security::verifyCsrfToken(
        $_POST['csrf_token'] ?? ''
    )
) {

    $_SESSION['product_error'] =
    'Invalid request token.';

    header(
        'Location: products'
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| VALIDATE PRODUCT ID
|--------------------------------------------------------------------------
*/

$id =
(int) ($_POST['id'] ?? 0);

if ($id <= 0) {

    $_SESSION['product_error'] =
    'Invalid product selected.';

    header(
        'Location: products'
    );

    exit;
}

$controller =
new ProductController();

/*
|--------------------------------------------------------------------------
| VERIFY PRODUCT EXISTS
|--------------------------------------------------------------------------
*/

$product =
$controller->find($id);

if (!$product) {

    $_SESSION['product_error'] =
    'Product not found.';

    header(
        'Location: products'
    );

    exit;
}

try {

    $oldStatus =
    (int) $product['is_available'];

    $success =
    $controller
    ->toggleAvailability($id);

    if (!$success) {

        throw new Exception(
            'Failed to update product status.'
        );
    }

    $newStatus =
    $oldStatus
    ? 'disabled'
    : 'enabled';

    /*
    |--------------------------------------------------------------------------
    | FUTURE LOGGING LOCATION
    |--------------------------------------------------------------------------
    */

    /*
    Log:
    Admin Admin disabled product Kuku
    */

    $_SESSION['product_success'] =
    'Product '
    .
    htmlspecialchars(
        $product['name']
    )
    .
    ' successfully '
    .
    $newStatus
    .
    '.';

} catch (Exception $e) {

    $_SESSION['product_error'] =
    'ERROR: '
    .
    $e->getMessage();
}

header(
    'Location: products'
);

exit;