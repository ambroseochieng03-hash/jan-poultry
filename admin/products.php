<?php

require_once
__DIR__ .
'/../includes/init.php';

require_once
__DIR__ .
'/../app/controllers/ProductController.php';

$pageCss =
'create-product.css';

$pageJs  = 'products.js';

include
__DIR__ .
'/../includes/admin-header.php';

$controller =
new ProductController();

$products =
$controller->getAll();

$productSuccess =
$_SESSION['product_success']
?? '';

$productError =
$_SESSION['product_error']
?? '';

unset(
    $_SESSION['product_success'],
    $_SESSION['product_error']
);

?>

<div class="products-admin-header">

    <div>

        <h1 class="products-admin-title">
            Products
        </h1>

        <p class="products-admin-subtitle">
            Manage products, media, stock and availability.
        </p>

    </div>

    <button
    type="button"
    id="productsCreateTrigger"
    class="products-admin-add-btn">

        + Add Product

</button>

</div>

<?php if ($productSuccess): ?>

<div class="products-admin-alert-success">

    <?= htmlspecialchars(
        $productSuccess
    ) ?>

</div>

<?php endif; ?>

<?php if ($productError): ?>

<div class="products-admin-alert-error">

    <?= htmlspecialchars(
        $productError
    ) ?>

</div>

<?php endif; ?>

<?php if (empty($products)): ?>

<div class="products-admin-empty">

    No products found.

</div>

<?php else: ?>

<div class="products-admin-grid">

<?php foreach ($products as $product): ?>

<div class="products-admin-card">

    <div class="products-admin-media">

        <?php if (!empty($product['image'])): ?>

            <?php if (
                strtolower(
                    pathinfo(
                        $product['image'],
                        PATHINFO_EXTENSION
                    )
                ) === 'mp4'
            ): ?>

                <video
                autoplay
                muted
                loop
                playsinline>

                    <source
                    src="/jan-poultry/public/uploads/products/<?= htmlspecialchars($product['image']) ?>"
                    type="video/mp4">

                </video>

            <?php else: ?>

                <img
                src="/jan-poultry/public/uploads/products/<?= htmlspecialchars($product['image']) ?>"
                alt="<?= htmlspecialchars($product['name']) ?>">

            <?php endif; ?>

        <?php else: ?>

            <div class="products-admin-placeholder">

                🐔

            </div>

        <?php endif; ?>

    </div>

    <div class="products-admin-body">

        <h3 class="products-admin-name">

            <?= htmlspecialchars(
                $product['name']
            ) ?>

        </h3>

        <div class="products-admin-price">

            KSh <?= number_format(
                $product['price'],
                2
            ) ?>

        </div>

        <div class="products-admin-meta">

            <span>

                <?= htmlspecialchars(
                    $product['product_type']
                ) ?>

            </span>

            <span>

            Stock:

            <?php if ((int)$product['stock_quantity'] <= 0): ?>

                Out of Stock

            <?php else: ?>

                <?= (int)$product['stock_quantity'] ?>

            <?php endif; ?>

        </span>

        </div>

        <div
        class="products-admin-status
        <?=
        (
            (int)$product['stock_quantity'] > 0
            &&
            $product['is_available']
        )
            ? 'products-admin-status-active'
            : 'products-admin-status-disabled'
        ?>">

            <?=
            (
                (int)$product['stock_quantity'] > 0
                &&
                $product['is_available']
            )
                ? 'Available'
                : 'Disabled'
            ?>

        </div>

        <div class="products-admin-actions">

            <button
            type="button"
            class="products-admin-edit products-edit-trigger"

            data-id="<?= $product['id'] ?>"

            data-name="<?= htmlspecialchars($product['name']) ?>"

            data-description="<?= htmlspecialchars($product['description']) ?>"

            data-price="<?= $product['price'] ?>"

            data-category="<?= htmlspecialchars($product['category']) ?>"

            data-type="<?= htmlspecialchars($product['product_type']) ?>"

            data-breed="<?= htmlspecialchars($product['breed']) ?>"

            data-age="<?= $product['age_weeks'] ?>"

            data-weight="<?= $product['weight_kg'] ?>"

            data-stock="<?= $product['stock_quantity'] ?>"

            data-sku="<?= htmlspecialchars($product['sku']) ?>"

            data-available="<?= $product['is_available'] ?>"

            data-image="<?= htmlspecialchars($product['image']) ?>">

                Edit

            </button>

            <button
            type="button"
            class="products-admin-toggle products-toggle-trigger"

            data-id="<?= $product['id'] ?>"

            data-name="<?= htmlspecialchars(
            $product['name']
            ) ?>"

            data-status="<?= $product['is_available'] ?>"

            data-stock="<?= (int)$product['stock_quantity'] ?>">  

                <?=
                (
                    $product['is_available']
                    &&
                    (int)$product['stock_quantity'] > 0
                )
                    ? 'Disable'
                    : 'Enable'
                ?>

            </button>

            <button
            type="button"
            class="products-admin-delete products-delete-trigger"

            data-id="<?= $product['id'] ?>"

            data-name="<?= htmlspecialchars(
            $product['name']
            ) ?>">

                Delete

            </button>

        </div>

    </div>

</div>

<?php endforeach; ?>

</div>

<?php endif; ?>


<div
id="productsEditModal"
class="products-edit-modal">

    <div
    class="products-edit-modal-content">

        <button
        type="button"
        id="productsEditClose"
        class="products-edit-close">

            ×

        </button>

        <h2>

            Edit Product

        </h2>

        <form
        id="productsEditForm"
        method="POST"
        enctype="multipart/form-data"
        action="product-edit.php">

            <input
            type="hidden"
            name="id"
            id="edit-id">

            <input
            type="hidden"
            name="csrf_token"
            value="<?= Security::generateCsrfToken() ?>">

            <input
            type="text"
            name="name"
            id="edit-name"
            required>

            <textarea
            name="description"
            id="edit-description"
            required></textarea>

            <input
            type="number"
            step="0.01"
            name="price"
            id="edit-price"
            required>

            <input
            type="text"
            name="category"
            id="edit-category">

            <select
            name="product_type"
            id="edit-type">

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
            id="edit-breed">

            <input
            type="number"
            name="age_weeks"
            id="edit-age">

            <input
            type="number"
            step="0.01"
            name="weight_kg"
            id="edit-weight">

            <input
            type="number"
            name="stock_quantity"
            id="edit-stock">

            <input
            type="text"
            name="sku"
            id="edit-sku">

            <div
            id="edit-media-preview">

            </div>

            <input
            type="file"
            name="image"
            accept="image/*,video/mp4">

            <label>

                <input
                type="checkbox"
                name="is_available"
                id="edit-available">

                Available

            </label>

            <div
            class="products-edit-buttons">

                <button
                type="button"
                id="productsEditCancel"
                class="products-edit-cancel">

                    Cancel

                </button>

                <button
                type="submit"
                class="products-edit-save">

                    Save Changes

                </button>

            </div>

        </form>

    </div>

</div>

<!-- Delete Modal -->

<div
id="productsDeleteModal"
class="products-edit-modal">

    <div
    class="products-delete-modal-content">

        <button
        type="button"
        id="productsDeleteClose"
        class="products-edit-close">

            ×

        </button>

        <h2>

            Delete Product

        </h2>

        <p
        class="products-delete-message">

            Delete
            <strong
            id="deleteProductName">

            </strong>
            ?

        </p>

        <p
        class="products-delete-warning">

            This action cannot be undone. <br><br>
            This product will be permanently deleted from the database.

        </p>

        <form
        method="POST"
        action="product-delete.php">

            <input
            type="hidden"
            name="id"
            id="delete-id">

            <input
            type="hidden"
            name="csrf_token"
            value="<?= Security::generateCsrfToken() ?>">

            <div
            class="products-edit-buttons">

                <button
                type="button"
                id="productsDeleteCancel"
                class="products-edit-cancel">

                    Cancel

                </button>

                <button
                type="submit"
                class="products-admin-delete">

                    Delete Permanently

                </button>

            </div>

        </form>

    </div>

</div>

<!-- Toggle Modal -->

<div
id="productsToggleModal"
class="products-edit-modal">

    <div
    class="products-delete-modal-content">

        <button
        type="button"
        id="productsToggleClose"
        class="products-edit-close">

            ×

        </button>

        <h2
        id="toggleModalTitle">

            Disable Product

        </h2>

        <p
        class="products-delete-message">

            <span
            id="toggleModalMessage">

            </span>

        </p>

        <p
        id="toggleModalWarning"
        class="products-delete-warning">

            Customers will still see this
            product but may not be able
            to add it to cart.

        </p>

        <form
        method="POST"
        action="product-toggle.php">

            <input
            type="hidden"
            name="id"
            id="toggle-id">

            <input
            type="hidden"
            name="csrf_token"
            value="<?= Security::generateCsrfToken() ?>">

            <div
            class="products-edit-buttons">

                <button
                type="button"
                id="productsToggleCancel"
                class="products-edit-cancel">

                    Cancel

                </button>

                <button
                type="submit"
                id="toggleSubmitButton"
                class="products-admin-toggle">

                    Disable

                </button>

            </div>

        </form>

    </div>

</div>

<!-- CREATE PRODUCT MODAL -->

<div
id="productsCreateModal"
class="products-edit-modal">

    <div
    class="products-edit-modal-content">

        <button
        type="button"
        id="productsCreateClose"
        class="products-edit-close">

            ×

        </button>

        <h2>

            Add Product

        </h2>

        <form
        id="productsCreateForm"
        method="POST"
        enctype="multipart/form-data">

            <input
            type="hidden"
            name="csrf_token"
            value="<?= Security::generateCsrfToken() ?>">

            <input
            type="text"
            name="name"
            placeholder="Product Name"
            required>

            <input
            type="number"
            step="0.01"
            name="price"
            placeholder="Price (KES)"
            required>

            <textarea
            name="description"
            placeholder="Description"
            required></textarea>

            <input
            type="text"
            name="category"
            placeholder="Category">

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

            <div
            id="create-media-preview">

            </div>

            <input
            type="file"
            name="image"
            id="create-image"
            accept="image/*,video/*">

            <label>

                <input
                type="checkbox"
                name="is_available"
                checked>

                Available

            </label>

            <div
            class="products-edit-buttons">

                <button
                type="button"
                id="productsCreateCancel"
                class="products-edit-cancel">

                    Cancel

                </button>

                <button
                type="submit"
                class="products-edit-save"
                id="productsCreateSave">

                    Save Product

                </button>

            </div>

        </form>

    </div>

</div>

<!-- RESPONSE MODAL -->

<div
id="productsResponseModal"
class="products-edit-modal">

    <div
    id="productsResponseContent"
    class="products-delete-modal-content">

        <button
        type="button"
        id="productsResponseClose"
        class="products-edit-close">

            ×

        </button>

        <div
        id="productsResponseIcon"
        class="products-response-icon">

            ✓

        </div>

        <h2
        id="productsResponseTitle">

            Success

        </h2>

        <p
        id="productsResponseMessage"
        class="products-delete-message">

            Product added successfully.

        </p>

        <div
        class="products-edit-buttons">

            <button
            type="button"
            id="productsResponseOkay"
            class="products-edit-save">

                OK

            </button>

        </div>

    </div>

</div>

<?php

include
__DIR__ .
'/../includes/admin-footer.php';
?>