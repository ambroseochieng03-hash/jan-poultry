/*
|--------------------------------------------------------------------------
| CREATE PRODUCT MODAL
|--------------------------------------------------------------------------
*/

const createModal =
document.getElementById(
    "productsCreateModal"
);

const createTrigger =
document.getElementById(
    "productsCreateTrigger"
);

const createClose =
document.getElementById(
    "productsCreateClose"
);

const createCancel =
document.getElementById(
    "productsCreateCancel"
);

if (
    createModal &&
    createTrigger
) {

    function openCreateModal() {

        createModal.classList.add(
            "products-edit-modal-show"
        );

    }

    function closeCreateModal() {

        createModal.classList.remove(
            "products-edit-modal-show"
        );

    }

    createTrigger.addEventListener(
        "click",
        openCreateModal
    );

    if (createClose) {

        createClose.addEventListener(
            "click",
            closeCreateModal
        );

    }

    if (createCancel) {

        createCancel.addEventListener(
            "click",
            closeCreateModal
        );

    }

    createModal.addEventListener(
        "click",
        (event) => {

            if (
                event.target === createModal
            ) {

                closeCreateModal();

            }

        }
    );

}

/*
|--------------------------------------------------------------------------
| RESPONSE MODAL
|--------------------------------------------------------------------------
*/

const responseModal =
document.getElementById(
    "productsResponseModal"
);

const responseContent =
document.getElementById(
    "productsResponseContent"
);

const responseIcon =
document.getElementById(
    "productsResponseIcon"
);

const responseTitle =
document.getElementById(
    "productsResponseTitle"
);

const responseMessage =
document.getElementById(
    "productsResponseMessage"
);

const responseClose =
document.getElementById(
    "productsResponseClose"
);

const responseOkay =
document.getElementById(
    "productsResponseOkay"
);

let responseTimer = null;

let reloadAfterClose = false;

function closeResponseModal(){

    responseModal.classList.remove(
        "products-edit-modal-show"
    );

    if(reloadAfterClose){

        location.reload();

    }

}

function showResponseModal(
    success,
    message
){

    clearTimeout(
        responseTimer
    );

    reloadAfterClose =
    success;

    responseContent.classList.remove(
        "products-response-success",
        "products-response-error"
    );

    if(success){

        responseContent.classList.add(
            "products-response-success"
        );

        responseIcon.textContent = "✓";

        responseTitle.textContent =
        "Success";

    }else{

        responseContent.classList.add(
            "products-response-error"
        );

        responseIcon.textContent = "✕";

        responseTitle.textContent =
        "Error";

    }

    responseMessage.textContent =
    message;

    responseModal.classList.add(
        "products-edit-modal-show"
    );

    responseTimer =
    setTimeout(

        closeResponseModal,

        2000

    );

}

responseClose?.addEventListener(
    "click",
    closeResponseModal
);

responseOkay?.addEventListener(
    "click",
    closeResponseModal
);

responseModal?.addEventListener(
    "click",
    (event)=>{

        if(
            event.target === responseModal
        ){

            closeResponseModal();

        }

    }
);

/*
|--------------------------------------------------------------------------
| CREATE PRODUCT AJAX
|--------------------------------------------------------------------------
*/

const createForm =
document.getElementById(
    "productsCreateForm"
);

const createSaveButton =
document.getElementById(
    "productsCreateSave"
);

const createDefaultButtonText =
createSaveButton
? createSaveButton.textContent
: "";

function setCreateLoading(
    loading
){

    if(
        !createSaveButton
    ){

        return;

    }

    createSaveButton.disabled =
    loading;

    createSaveButton.textContent =
    loading
    ? "Saving..."
    : createDefaultButtonText;

}

if(
    createForm
){

    createForm.addEventListener(
        "submit",
        async function(event){

            event.preventDefault();

            setCreateLoading(
                true
            );

            const formData =
            new FormData(
                createForm
            );

            try{

                const response =
                await fetch(

                    "product-create-ajax",

                    {

                        method:"POST",

                        body:formData

                    }

                );

                const result =
                await response.json();

                setCreateLoading(
                    false
                );

                if(
                    result.success
                ){

                    showResponseModal(
                        true,
                        result.message
                    );

                    createForm.reset();

                    createModal.classList.remove(
                        "products-edit-modal-show"
                    ); // Let showResponseModal() handle the reload.

                    location.reload();

                }else{

                    showResponseModal(
                        false,
                        result.message
                    );

                }

            }catch(error){

                setCreateLoading(
                    false
                );

                showResponseModal(
                    false,
                    "An unexpected error occurred."
                );

                console.error(
                    error
                );

            }

        }

    );

}
