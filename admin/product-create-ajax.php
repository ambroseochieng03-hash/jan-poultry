<?php

declare(strict_types=1);

require_once
__DIR__ .
'/../includes/init.php';

require_once
__DIR__ .
'/../app/helpers/Auth.php';

require_once
__DIR__ .
'/../app/controllers/ProductController.php';

require_once
__DIR__ .
'/../app/services/UploadService.php';

header(
    'Content-Type: application/json'
);

if (
    $_SERVER['REQUEST_METHOD']
    !== 'POST'
) {

    http_response_code(405);

    echo json_encode([

        'success' => false,

        'message' =>
        'Method not allowed.'

    ]);

    exit;

}

Auth::requireAdminJson();

if (
    !Security::verifyCsrfToken(
        $_POST['csrf_token']
        ?? ''
    )
) {

    http_response_code(403);

    echo json_encode([

        'success' => false,

        'message' =>
        'Invalid CSRF token.'

    ]);

    exit;

}

try {

    $imageName = '';

    if (
        !empty(
            $_FILES['image']['name']
        )
    ) {

        $upload =
        new UploadService();

        $imageName =
        $upload->uploadMedia(
            $_FILES['image']
        );

    }

    $controller =
    new ProductController();

    $controller->create([

        'name' =>
        trim($_POST['name']),

        'description' =>
        trim($_POST['description']),

        'price' =>
        $_POST['price'],

        'image' =>
        $imageName,

        'category' =>
        trim($_POST['category']),

        'product_type' =>
        $_POST['product_type'],

        'breed' =>
        trim($_POST['breed']),

        'age_weeks' =>
        $_POST['age_weeks']
        ?: null,

        'weight_kg' =>
        $_POST['weight_kg']
        ?: null,

        'stock_quantity' =>
        $_POST['stock_quantity'],

        'sku' =>
        trim($_POST['sku']),

        'is_available' =>
        isset($_POST['is_available'])
        ? 1
        : 0

    ]);

    echo json_encode([

        'success' => true,

        'message' =>
        'Product added successfully.'

    ]);

} catch (Exception $e) {

    http_response_code(400);

    echo json_encode([

        'success' => false,

        'message' =>
        $e->getMessage()

    ]);

}
