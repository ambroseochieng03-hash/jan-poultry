const menuToggle =
document.getElementById(
    "menuToggle"
);

const sidebar =
document.getElementById(
    "adminSidebar"
);

if (
    menuToggle &&
    sidebar
) {

    menuToggle.addEventListener(
        "click",
        () => {

            sidebar.classList.toggle(
                "show-sidebar"
            );

        }
    );
}

/*
|--------------------------------------------------------------------------
| PRODUCT EDIT MODAL
|--------------------------------------------------------------------------
*/

const editModal =
document.getElementById(
    "productsEditModal"
);

const editClose =
document.getElementById(
    "productsEditClose"
);

const editCancel =
document.getElementById(
    "productsEditCancel"
);

const editButtons =
document.querySelectorAll(
    ".products-edit-trigger"
);

if (
    editModal &&
    editButtons.length
) {

    editButtons.forEach(
        (button) => {

            button.addEventListener(
                "click",
                () => {

                    document.getElementById(
                        "edit-id"
                    ).value =
                    button.dataset.id;

                    document.getElementById(
                        "edit-name"
                    ).value =
                    button.dataset.name;

                    document.getElementById(
                        "edit-description"
                    ).value =
                    button.dataset.description;

                    document.getElementById(
                        "edit-price"
                    ).value =
                    button.dataset.price;

                    document.getElementById(
                        "edit-category"
                    ).value =
                    button.dataset.category;

                    document.getElementById(
                        "edit-type"
                    ).value =
                    button.dataset.type;

                    document.getElementById(
                        "edit-breed"
                    ).value =
                    button.dataset.breed;

                    document.getElementById(
                        "edit-age"
                    ).value =
                    button.dataset.age;

                    document.getElementById(
                        "edit-weight"
                    ).value =
                    button.dataset.weight;

                    document.getElementById(
                        "edit-stock"
                    ).value =
                    button.dataset.stock;

                    document.getElementById(
                        "edit-sku"
                    ).value =
                    button.dataset.sku;

                    document.getElementById(
                        "edit-available"
                    ).checked =
                    button.dataset.available === "1";

                    const preview =
                    document.getElementById(
                        "edit-media-preview"
                    );

                    preview.innerHTML = "";

                    if (
                        button.dataset.image
                    ) {

                        const imagePath =
                        "/uploads/products/"
                        +
                        button.dataset.image;

                        if (
                            button.dataset.image
                            .toLowerCase()
                            .endsWith(".mp4")
                        ) {

                            preview.innerHTML =
                            `
                            <video
                            controls
                            autoplay
                            muted
                            loop
                            class="products-edit-preview-video">
                                <source
                                src="${imagePath}"
                                type="video/mp4">
                            </video>
                            `;

                        } else {

                            preview.innerHTML =
                            `
                            <img
                            src="${imagePath}"
                            class="products-edit-preview-image">
                            `;
                        }
                    }

                    editModal.classList.add(
                        "products-edit-modal-show"
                    );

                }
            );

        }
    );

    function closeModal() {

        editModal.classList.remove(
            "products-edit-modal-show"
        );

    }

    if (editClose) {

        editClose.addEventListener(
            "click",
            closeModal
        );

    }

    if (editCancel) {

        editCancel.addEventListener(
            "click",
            closeModal
        );

    }

    editModal.addEventListener(
        "click",
        (e) => {

            if (
                e.target === editModal
            ) {

                closeModal();

            }

        }
    );

}

/*
====================================
DELETE MODAL
====================================
*/

const deleteModal =
document.getElementById(
    "productsDeleteModal"
);

const deleteTriggers =
document.querySelectorAll(
    ".products-delete-trigger"
);

const deleteClose =
document.getElementById(
    "productsDeleteClose"
);

const deleteCancel =
document.getElementById(
    "productsDeleteCancel"
);

const deleteId =
document.getElementById(
    "delete-id"
);

const deleteProductName =
document.getElementById(
    "deleteProductName"
);

deleteTriggers.forEach(
    (button) => {

        button.addEventListener(
            "click",
            () => {

                deleteId.value =
                button.dataset.id;

                deleteProductName.textContent =
                button.dataset.name;

                deleteModal.classList.add(
                    "products-edit-modal-show"
                );

            }
        );

    }
);

if (
    deleteClose
) {

    deleteClose.addEventListener(
        "click",
        () => {

            deleteModal.classList.remove(
                "products-edit-modal-show"
            );

        }
    );
}

if (
    deleteCancel
) {

    deleteCancel.addEventListener(
        "click",
        () => {

            deleteModal.classList.remove(
                "products-edit-modal-show"
            );

        }
    );
}

window.addEventListener(
    "click",
    (event) => {

        if (
            event.target === deleteModal
        ) {

            deleteModal.classList.remove(
                "products-edit-modal-show"
            );

        }

    }
);

//TOGGLE THE BUTTON FOR DISABLE

const toggleModal =
document.getElementById(
    "productsToggleModal"
);

const toggleClose =
document.getElementById(
    "productsToggleClose"
);

const toggleCancel =
document.getElementById(
    "productsToggleCancel"
);

const toggleId =
document.getElementById(
    "toggle-id"
);

const toggleMessage =
document.getElementById(
    "toggleModalMessage"
);

const toggleTitle =
document.getElementById(
    "toggleModalTitle"
);

const toggleWarning =
document.getElementById(
    "toggleModalWarning"
);

const toggleSubmit =
document.getElementById(
    "toggleSubmitButton"
);

document
.querySelectorAll(
    ".products-toggle-trigger"
)
.forEach(button => {

    button.addEventListener(
        "click",
        () => {

            const id =
            button.dataset.id;

            const name =
            button.dataset.name;

            const status =
            button.dataset.status;

            const stock =
            Number(
                button.dataset.stock
            );

            toggleId.value =
            id;

            if (
                stock <= 0
            ) {

                toggleTitle.textContent =
                "Out of Stock";

                toggleMessage.innerHTML =

                '<strong>' +

                name +

                '</strong> is currently out of stock.<br><br>' +

                'Increase the stock quantity using <strong>Edit</strong> before enabling this product.';

                toggleSubmit.textContent =
                "Enable";

                toggleSubmit.disabled =
                true;

                toggleWarning.textContent =
                "This product cannot be enabled until its stock quantity is greater than zero.";

            }
            else if (
                status === "1"
            ) {

                toggleTitle.textContent =
                "Disable Product";

                toggleMessage.innerHTML =
                'Disable <strong>' +
                name +
                '</strong>?';

                toggleSubmit.textContent =
                "Disable";

                toggleWarning.textContent =
                "Customers will still see this product but may not be able to add it to cart.";

                toggleSubmit.disabled =
                false;

            }
            else {

                toggleTitle.textContent =
                "Enable Product";

                toggleMessage.innerHTML =
                'Enable <strong>' +
                name +
                '</strong>?';

                toggleSubmit.textContent =
                "Enable";

                toggleSubmit.disabled =
                false;

                toggleWarning.textContent =
                "This product will become available to customers immediately.";

            }

            toggleModal.classList.add(
                "products-edit-modal-show"
            );
        }
    );
});

if (
    toggleClose
) {

    toggleClose.addEventListener(
        "click",
        () => {

            toggleModal.classList.remove(
                "products-edit-modal-show"
            );
        }
    );
}

if (
    toggleCancel
) {

    toggleCancel.addEventListener(
        "click",
        () => {

            toggleModal.classList.remove(
                "products-edit-modal-show"
            );
        }
    );
}
