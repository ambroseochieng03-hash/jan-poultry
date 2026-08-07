<?php

require_once
__DIR__ .
'/../includes/init.php';

require_once
__DIR__ .
'/../app/helpers/Auth.php';

if (
    $_SERVER['REQUEST_METHOD']
    !== 'POST'
) {

    header(
        'Location: products.php'
    );

    exit;
}

Auth::requireAdmin();

if (
    !Security::verifyCsrfToken(
        $_POST['csrf_token'] ?? ''
    )
) {

    $_SESSION['product_error'] =
    'Invalid request token.';

    header(
        'Location: products.php'
    );

    exit;
}

require_once
__DIR__ .
'/../app/controllers/ProductController.php';

require_once
__DIR__ .
'/../app/services/UploadService.php';

$controller =
new ProductController();

$id =
(int)($_POST['id'] ?? 0);

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

/*
|--------------------------------------------------------------------------
| INPUT VALIDATION
|--------------------------------------------------------------------------
*/

$name =
trim($_POST['name'] ?? '');

$description =
trim($_POST['description'] ?? '');

$category =
trim($_POST['category'] ?? '');

$breed =
trim($_POST['breed'] ?? '');

$sku =
trim($_POST['sku'] ?? '');


if (
    strlen($name) < 2
) {

    $_SESSION['product_error'] =
    'Product name is too short.';

    header(
        'Location: products.php'
    );

    exit;
}


if (
    strlen($description) < 5
) {

    $_SESSION['product_error'] =
    'Product description is too short.';

    header(
        'Location: products.php'
    );

    exit;
}

    $imageName =
    $product['image'];

    $price =
    (float)($_POST['price'] ?? 0);

    $stockQuantity =
    (int)($_POST['stock_quantity'] ?? 0);


    $ageWeeks =
    !empty($_POST['age_weeks'])
    ? (int)$_POST['age_weeks']
    : null;


    $weightKg =
    !empty($_POST['weight_kg'])
    ? (float)$_POST['weight_kg']
    : null;



    /*
    |--------------------------------------------------------------------------
    | NUMERIC VALIDATION
    |--------------------------------------------------------------------------
    */


    if (
        $price < 0
    ) {

        $_SESSION['product_error'] =
        'Price cannot be negative.';

        header(
            'Location: products.php'
        );

        exit;

    }

    $allowedTypes = [

        'live_chicken',
        'meat'

    ];


    $productType =
    $_POST['product_type'] ?? '';



    if (
        !in_array(
            $productType,
            $allowedTypes,
            true
        )
    ) {

        $_SESSION['product_error'] =
        'Invalid product type.';

        header(
            'Location: products.php'
        );

        exit;

    }



    if (
        $stockQuantity < 0
    ) {

        $_SESSION['product_error'] =
        'Stock quantity cannot be negative.';

        header(
            'Location: products.php'
        );

        exit;

    }



    if (
        $ageWeeks !== null
        &&
        $ageWeeks < 0
    ) {

        $_SESSION['product_error'] =
        'Invalid age value.';

        header(
            'Location: products.php'
        );

        exit;

    }



    if (
        $weightKg !== null
        &&
        $weightKg < 0
    ) {

        $_SESSION['product_error'] =
        'Invalid weight value.';

        header(
            'Location: products.php'
        );

        exit;

    }

    try {

        if (
            !empty($_FILES['image']['name'])
        ) {

            $upload =
            new UploadService();

            $imageName =
            $upload->uploadMedia(
                $_FILES['image']
            );

            /*
            ----------------------------------
            Delete old media after replacement
            ----------------------------------
            */

            if (
                !empty($product['image'])
            ) {

                $oldFile =
                __DIR__
                .
                '/../public/uploads/products/'
                .
                $product['image'];

                if (
                    file_exists(
                        $oldFile
                    )
                ) {

                    unlink(
                        $oldFile
                    );

                }

            }

        }

    } catch (Exception $e) {

        $_SESSION['product_error'] =
        'UPLOAD ERROR: '
        .
        $e->getMessage();

        header(
            'Location: products.php'
        );

        exit;
    }

    if (
        empty(
            $_SESSION['product_error']
        )
    ) {

        $updated =
    $controller->update(
        $id,
        [

        'name' =>
        $name,

        'description' =>
        $description,

        'price' =>
        $price,

        'image' =>
        $imageName,

        'category' =>
        $category,

        'product_type' =>
        $productType,

        'breed' =>
        $breed,

        'age_weeks' =>
        $ageWeeks,

        'weight_kg' =>
        $weightKg,

        'stock_quantity' =>
        $stockQuantity,

        'sku' =>
        $sku,

        'is_available' =>
        isset($_POST['is_available'])
        ? 1
        : 0

        ]
    );

    if(
        !$updated
    ){

        $_SESSION['product_error'] =
        'Product update failed.';

        header(
            'Location: products.php'
        );

        exit;

    }

        $_SESSION['product_success'] =
        'Product updated successfully.';


        /*
        |--------------------------------------------------------------------------
        | TODO: ADMIN LOG
        |--------------------------------------------------------------------------
        |
        | Admin:
        | Auth::name()
        |
        | Action:
        | Updated product
        |
        | Product ID:
        | $id
        |
        */

        header(
            'Location: products.php'
        );

        exit;
    }

?>