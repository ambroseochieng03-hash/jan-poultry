/*
|--------------------------------------------------------------------------
| CREATE PRODUCT MODAL
|--------------------------------------------------------------------------
*/

const createModal = document.getElementById("productsCreateModal");
const createTrigger = document.getElementById("productsCreateTrigger");
const createClose = document.getElementById("productsCreateClose");
const createCancel = document.getElementById("productsCreateCancel");

if (createModal && createTrigger) {
    function openCreateModal() {
        createModal.classList.add("products-edit-modal-show");
    }

    function closeCreateModal() {
        createModal.classList.remove("products-edit-modal-show");
    }

    createTrigger.addEventListener("click", openCreateModal);
    if (createClose) createClose.addEventListener("click", closeCreateModal);
    if (createCancel) createCancel.addEventListener("click", closeCreateModal);

    createModal.addEventListener("click", (event) => {
        if (event.target === createModal) {
            closeCreateModal();
        }
    });
}

/*
|--------------------------------------------------------------------------
| RESPONSE MODAL
|--------------------------------------------------------------------------
*/

const responseModal = document.getElementById("productsResponseModal");
const responseContent = document.getElementById("productsResponseContent");
const responseIcon = document.getElementById("productsResponseIcon");
const responseTitle = document.getElementById("productsResponseTitle");
const responseMessage = document.getElementById("productsResponseMessage");
const responseClose = document.getElementById("productsResponseClose");
const responseOkay = document.getElementById("productsResponseOkay");

let responseTimer = null;
let reloadAfterClose = false;

function closeResponseModal(){
    responseModal.classList.remove("products-edit-modal-show");
    if(reloadAfterClose){
        location.reload();
    }
}

function showResponseModal(success, message, shouldReload = success){
    clearTimeout(responseTimer);
    reloadAfterClose = shouldReload;

    responseContent.classList.remove("products-response-success", "products-response-error");

    if(success){
        responseContent.classList.add("products-response-success");
        responseIcon.textContent = "✓";
        responseTitle.textContent = "Success";
    }else{
        responseContent.classList.add("products-response-error");
        responseIcon.textContent = "✕";
        responseTitle.textContent = "Error";
    }

    responseMessage.textContent = message;
    responseModal.classList.add("products-edit-modal-show");

    responseTimer = setTimeout(closeResponseModal, 2000);
}

responseClose?.addEventListener("click", closeResponseModal);
responseOkay?.addEventListener("click", closeResponseModal);
responseModal?.addEventListener("click", (event)=>{
    if(event.target === responseModal){
        closeResponseModal();
    }
});

/*
|--------------------------------------------------------------------------
| CREATE PRODUCT AJAX
|--------------------------------------------------------------------------
*/

const createForm = document.getElementById("productsCreateForm");
const createSaveButton = document.getElementById("productsCreateSave");
const createDefaultButtonText = createSaveButton ? createSaveButton.textContent : "";

function setCreateLoading(loading){
    if(!createSaveButton) return;
    createSaveButton.disabled = loading;
    createSaveButton.textContent = loading ? "Saving..." : createDefaultButtonText;
}

if(createForm){
    createForm.addEventListener("submit", async function(event){
        event.preventDefault();
        setCreateLoading(true);

        const formData = new FormData(createForm);

        try{
            const response = await fetch("product-create-ajax.php", {
                method:"POST",
                body:formData
            });
            const result = await response.json();
            setCreateLoading(false);

            if(result.success){
                showResponseModal(true, result.message, true); // Reloads page only for creation
                createForm.reset();
                createModal.classList.remove("products-edit-modal-show");
            }else{
                showResponseModal(false, result.message);
            }
        }catch(error){
            setCreateLoading(false);
            showResponseModal(false, "An unexpected error occurred.");
            console.error(error);
        }
    });
}

/*
|--------------------------------------------------------------------------
| PRODUCTS TOGGLE SETUP & AJAX HANDLING (No Reload)
|--------------------------------------------------------------------------
*/

// Handlers for closing the toggle modal
const toggleModal = document.getElementById("productsToggleModal");
const toggleClose = document.getElementById("productsToggleClose");
const toggleCancel = document.getElementById("productsToggleCancel");

function closeToggleModal() {
    if (toggleModal) toggleModal.classList.remove("products-edit-modal-show");
}

if (toggleClose) toggleClose.addEventListener("click", closeToggleModal);
if (toggleCancel) toggleCancel.addEventListener("click", closeToggleModal);
toggleModal?.addEventListener("click", (event) => {
    if (event.target === toggleModal) closeToggleModal();
});

// Open modal and map dataset properties dynamically on click
document.addEventListener("click", function(event) {
    const trigger = event.target.closest(".products-toggle-trigger");
    if (!trigger) return;

    const productId = trigger.getAttribute("data-id");
    const productName = trigger.getAttribute("data-name");
    const currentStatus = trigger.getAttribute("data-status"); // "1" or "0"

    const toggleIdInput = document.getElementById("toggle-id");
    const toggleModalTitle = document.getElementById("toggleModalTitle");
    const toggleModalMessage = document.getElementById("toggleModalMessage");
    const toggleSubmitButton = document.getElementById("toggleSubmitButton");

    if (toggleIdInput) toggleIdInput.value = productId;
    if (toggleModalTitle) {
        toggleModalTitle.textContent = currentStatus === "1" ? "Disable Product" : "Enable Product";
    }
    if (toggleModalMessage) {
        toggleModalMessage.textContent = `Are you sure you want to change the status of ${productName}?`;
    }
    if (toggleSubmitButton) {
        toggleSubmitButton.textContent = currentStatus === "1" ? "Disable" : "Enable";
    }

    if (toggleModal) {
        toggleModal.classList.add("products-edit-modal-show");
    }
});

// Submit form data cleanly via AJAX fetch
const toggleForm = document.querySelector('form[action="product-toggle.php"]');

if (toggleForm) {
    toggleForm.addEventListener("submit", async function (event) {
        event.preventDefault();

        const formData = new FormData(toggleForm);
        const actionUrl = toggleForm.getAttribute("action");
        const productId = formData.get("id");

        try {
            const response = await fetch(actionUrl, {
                method: "POST",
                body: formData
            });
            const result = await response.json();

            if (result.success) {
                closeToggleModal();

                // Explicit false keeps your current view and scroll position locked
                showResponseModal(true, result.message, false);

                // Target and update the exact DOM card elements inline
                const toggleBtn = document.querySelector(`.products-toggle-trigger[data-id="${productId}"]`);
                if (toggleBtn) {
                    const card = toggleBtn.closest(".products-admin-card");
                    const statusDiv = card.querySelector(".products-admin-status");

                    const currentStatus = toggleBtn.getAttribute("data-status");
                    const newStatus = currentStatus === "1" ? "0" : "1";

                    // Update structural tracking attribute and button text
                    toggleBtn.setAttribute("data-status", newStatus);
                    toggleBtn.textContent = newStatus === "1" ? "Disable" : "Enable";

                    // Update visual availability flag text and class
                    statusDiv.className = `products-admin-status ${
                        newStatus === "1" ? "products-admin-status-active" : "products-admin-status-disabled"
                    }`;
                    statusDiv.textContent = newStatus === "1" ? "Available" : "Disabled";
                }

            } else {
                showResponseModal(false, result.message);
            }
        } catch (error) {
            showResponseModal(false, "An unexpected error occurred.");
            console.error(error);
        }
    });
}