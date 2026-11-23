<?php

require_once
__DIR__ .
'/../includes/init.php';
require_once __DIR__ . '/../app/models/Category.php';
$categoryNames = (new Category())->allNames();


require_once
__DIR__ .
'/../app/controllers/ProductController.php';

require_once
__DIR__ .
'/../app/services/UploadService.php';

include
__DIR__ .
'/../includes/admin-header.php';

$controller =
new ProductController();

$message = '';
$uploadFailed = false;

$postRejected = $_SERVER['REQUEST_METHOD'] === 'POST' && !Security::checkPostRequest();

if ($postRejected) {

    $message = 'Security check failed. Please reload the page and try again.';
    $uploadFailed = true;

}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$postRejected) {

    $imageName = '';

    if (
        !empty($_FILES['image']['name'])
    ) {

        try {

            $upload =
            new UploadService();

            $imageName =
            $upload->uploadMedia(
                $_FILES['image']
            );

        } catch (Exception $e) {

            $message =
            $e->getMessage();

            $uploadFailed = true;

        }

    }

    if (!$uploadFailed) {

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
            (new Category())->canonical((string) ($_POST['category'] ?? '')),

            'product_type' =>
            $_POST['product_type'],

            'breed' =>
            trim($_POST['breed']),

            'age_weeks' =>
            $_POST['age_weeks'] ?: null,

            'weight_kg' =>
            $_POST['weight_kg'] ?: null,

            'stock_quantity' =>
            $_POST['stock_quantity'],

            'sku' =>
            trim($_POST['sku']),

            'is_available' =>
            isset($_POST['is_available'])
            ? 1
            : 0

        ]);

        $message =
        'Product added successfully.';
    }

}

?>

<div class="page-header">

    <h1>Add Product</h1>

</div>

<?php if ($message): ?>

<div class="success-box">

    <?= htmlspecialchars($message) ?>

</div>

<?php endif; ?>

<form
method="POST"
enctype="multipart/form-data"
class="product-form">

    <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">

    <input
    type="text"
    name="name"
    placeholder="Product Name"
    required>

    <textarea
    name="description"
    placeholder="Description"
    required></textarea>

    <input
    type="number"
    step="0.01"
    name="price"
    placeholder="Price (KES)"
    required>

    <select name="category">
    <option value="">No category</option>
    <?php foreach ($categoryNames as $categoryName): ?>
        <option value="<?= htmlspecialchars($categoryName) ?>"><?= htmlspecialchars($categoryName) ?></option>
    <?php endforeach; ?>
</select>

    <select
    name="product_type"
    required>

        <option value="">
            Select Type
        </option>

        <option value="live_chicken">
            Live Chicken
        </option>

        <option value="meat">
            Meat
        </option>

    </select>

    <input
    type="text"
    name="breed"
    placeholder="Breed">

    <input
    type="number"
    name="age_weeks"
    placeholder="Age (Weeks)">

    <input
    type="number"
    step="0.01"
    name="weight_kg"
    placeholder="Weight (KG)">

    <input
    type="number"
    name="stock_quantity"
    placeholder="Stock Quantity"
    required>

    <input
    type="text"
    name="sku"
    placeholder="SKU">

    <label>

        Product Image

    </label>

    <input
    type="file"
    name="image"
    accept="image/*,video/*">

    <label>

        <input
        type="checkbox"
        name="is_available"
        checked>

        Available

    </label>

    <button
    type="submit"
    class="gold-btn">

        Save Product

    </button>

</form>

<?php

include
__DIR__ .
'/../includes/admin-footer.php';
?>