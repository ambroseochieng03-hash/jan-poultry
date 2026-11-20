<div
id="productDetailsModal"
class="product-details-modal">

    <div class="product-details-card">

        <button
        type="button"
        id="closeProductDetailsModal"
        class="product-details-close"
        aria-label="Close">

            &times;

        </button>

        <div
        id="productDetailsMedia"
        class="product-details-media">

            <!-- Image or video inserted by JavaScript -->

        </div>

        <div class="product-details-content">

            <h2
            id="productDetailsName">

                Product Name

            </h2>

            <p
            id="productDetailsDescription"
            class="product-details-description">

                Product description.

            </p>

            <div class="product-details-info">

                <div>

                    <strong>Price:</strong>

                    <span id="productDetailsPrice">

                        KSh 0.00

                    </span>

                </div>

                <div>

                    <strong>Stock:</strong>

                    <span id="productDetailsStock">

                        0

                    </span>

                </div>

                <div>

                    <strong>Status:</strong>

                    <span id="productDetailsAvailability">

                        Available

                    </span>

                </div>

                <div
                id="productDetailsAgeRow"
                style="display:none;">

                    <strong>Age:</strong>

                    <span id="productDetailsAge">

                        -

                    </span>

                </div>

                <div
                id="productDetailsWeightRow"
                style="display:none;">

                    <strong>Weight:</strong>

                    <span id="productDetailsWeight">

                        -

                    </span>

                </div>

            </div>

            <form
            id="productDetailsCartForm"
            method="POST"
            action="add-to-cart">

                <input
                type="hidden"
                name="csrf_token"
                id="productDetailsCsrfToken">

                <input
                type="hidden"
                name="product_id"
                id="productDetailsProductId">

                <div class="product-details-actions">

                    <button
                    type="submit"
                    id="productDetailsAddToCart"
                    class="add-cart-btn">

                        Add To Cart

                    </button>

                    <button
                    type="button"
                    id="closeProductDetailsButton"
                    class="btn btn-outline">

                        Close

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>
