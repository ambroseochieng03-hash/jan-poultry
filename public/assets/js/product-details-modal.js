
/*
|--------------------------------------------------------------------------
| JAN Poultry
| Product Details Modal
|--------------------------------------------------------------------------
*/

(function () {

    "use strict";

    const modal =
        document.getElementById(
            "productDetailsModal"
        );

    if (!modal) {
        return;
    }

    const media =
        document.getElementById(
            "productDetailsMedia"
        );

    const name =
        document.getElementById(
            "productDetailsName"
        );

    const description =
        document.getElementById(
            "productDetailsDescription"
        );

    const price =
        document.getElementById(
            "productDetailsPrice"
        );

    const stock =
        document.getElementById(
            "productDetailsStock"
        );

    const availability =
        document.getElementById(
            "productDetailsAvailability"
        );

    const ageRow =
        document.getElementById(
            "productDetailsAgeRow"
        );

    const age =
        document.getElementById(
            "productDetailsAge"
        );

    const weightRow =
        document.getElementById(
            "productDetailsWeightRow"
        );

    const weight =
        document.getElementById(
            "productDetailsWeight"
        );

    const productId =
        document.getElementById(
            "productDetailsProductId"
        );

    const csrf =
        document.getElementById(
            "productDetailsCsrfToken"
        );

    const addButton =
        document.getElementById(
            "productDetailsAddToCart"
        );

    const closeButton =
        document.getElementById(
            "closeProductDetailsModal"
        );

    const closeFooterButton =
        document.getElementById(
            "closeProductDetailsButton"
        );

    function closeModal()
    {
        modal.classList.remove(
            "show"
        );
    }

    document
        .querySelectorAll(
            ".details-btn"
        )
        .forEach(function (button) {

            button.addEventListener(
                "click",
                function (event) {

                    event.preventDefault();

                    const image =
                        button.dataset.image;

                    const extension =
                        image
                        .split(".")
                        .pop()
                        .toLowerCase();

                    media.innerHTML = "";

                    if (
                        extension === "mp4"
                    ) {

                        media.innerHTML =
`
<video
controls
playsinline
preload="metadata"
style="width:100%;height:auto;border-radius:12px;">

<source
src="${window.jpMediaUrl(image)}"
type="video/mp4">

</video>
`;

                    } else {

                        media.innerHTML =
`
<img
src="${window.jpMediaUrl(image)}"
alt=""
style="width:100%;height:auto;border-radius:12px;">
`;

                    }

                    name.textContent =
                        button.dataset.name;

                    description.textContent =
                        button.dataset.description;

                    price.textContent =
                        "KSh " +
                        button.dataset.price;

                    const stockQuantity =
                        Number(
                            button.dataset.stock
                        );

                    if (
                        stockQuantity <= 0
                    ) {

                        stock.textContent =
                            "Out Of Stock";

                    } else {

                        stock.textContent =
                            "Stock: " +
                            stockQuantity;

                    }

                    const available =
                        Number(
                            button.dataset.available
                        ) === 1;

                    availability.textContent =
                        available
                            ? "Available"
                            : "Currently Unavailable";

                    productId.value =
                        button.dataset.productId;

                    csrf.value =
                        button.dataset.csrf;

                    if (
                        button.dataset.productType ===
                        "live_chicken"
                    ) {

                        ageRow.style.display =
                            "block";

                        weightRow.style.display =
                            "block";

                        age.textContent =
                            button.dataset.age +
                            " weeks";

                        weight.textContent =
                            button.dataset.weight +
                            " kg";

                    } else {

                        ageRow.style.display =
                            "none";

                        weightRow.style.display =
                            "none";

                    }

                    if (!available) {

                        addButton.disabled =
                            true;

                        addButton.textContent =
                            "Currently Unavailable";

                    } else if (
                        stockQuantity <= 0
                    ) {

                        addButton.disabled =
                            true;

                        addButton.textContent =
                            "Out Of Stock";

                    } else {

                        addButton.disabled =
                            false;

                        addButton.textContent =
                            "Add To Cart";

                    }

                    modal.classList.add(
                        "show"
                    );

                }
            );

        });

    closeButton.addEventListener(
        "click",
        closeModal
    );

    closeFooterButton.addEventListener(
        "click",
        closeModal
    );

    modal.addEventListener(
        "click",
        function (event) {

            if (
                event.target === modal
            ) {
                closeModal();
            }

        }
    );

    document.addEventListener(
        "keydown",
        function (event) {

            if (
                event.key === "Escape"
            ) {
                closeModal();
            }

        }
    );

})();