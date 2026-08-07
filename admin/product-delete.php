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
| VERIFY POST
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD']
    !== 'POST'
) {

    header(
        'Location: products.php'
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| AUTH
|--------------------------------------------------------------------------
*/

Auth::requireAdmin();

/*
|--------------------------------------------------------------------------
| CSRF
|--------------------------------------------------------------------------
*/

if (
    !Security::verifyCsrfToken(
        $_POST['csrf_token']
        ?? ''
    )
) {

    $_SESSION['product_error'] =
    'Invalid request token.';

    header(
        'Location: products.php'
    );

    exit;
}

/*
|--------------------------------------------------------------------------
| VALIDATE ID
|--------------------------------------------------------------------------
*/

$id =
(int)(
    $_POST['id']
    ?? 0
);

if ($id <= 0) {

    $_SESSION['product_error'] =
    'Invalid product ID.';

    header(
        'Location: products.php'
    );

    exit;
}

$controller =
new ProductController();

$product =
$controller->find($id);

if (!$product) {

    $_SESSION['product_error'] =
    'Product not found.';

    header(
        'Location: products.php'
    );

    exit;
}

try {

    /*
    |--------------------------------------------------------------------------
    | DELETE DATABASE RECORD FIRST
    |--------------------------------------------------------------------------
    */

    $deleted =
    $controller->delete($id);

    if (!$deleted) {

        throw new Exception(
            'Failed to delete product.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE MEDIA AFTER DB SUCCESS
    |--------------------------------------------------------------------------
    */

    if (
        !empty(
            $product['image']
        )
    ) {

        $mediaPath =
        __DIR__
        .
        '/../public/uploads/products/'
        .
        $product['image'];

        if (
            file_exists(
                $mediaPath
            )
        ) {

            unlink(
                $mediaPath
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SUCCESS
    |--------------------------------------------------------------------------
    */

    $_SESSION['product_success'] =
    'Product deleted successfully.';

} catch (Exception $e) {

    $_SESSION['product_error'] =
    $e->getMessage();
}

header(
    'Location: products.php'
);

exit;