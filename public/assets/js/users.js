console.log(
    "Users page loaded."
);

/* ==========================================
   VIEW CUSTOMER MODAL
========================================== */

const userViewModal =
document.getElementById(
    "userViewModal"
);

const userViewClose =
document.getElementById(
    "userViewClose"
);

const userViewOkay =
document.getElementById(
    "userViewOkay"
);

const viewUserName =
document.getElementById(
    "viewUserName"
);

const viewUserEmail =
document.getElementById(
    "viewUserEmail"
);

const viewUserPhone =
document.getElementById(
    "viewUserPhone"
);

const viewUserStatus =
document.getElementById(
    "viewUserStatus"
);

const viewUserCreated =
document.getElementById(
    "viewUserCreated"
);

const viewUserLastLogin =
document.getElementById(
    "viewUserLastLogin"
);

const userCallButton =
document.getElementById(
    "userCallButton"
);

const userEmailButton =
document.getElementById(
    "userEmailButton"
);

document
.querySelectorAll(
    ".user-view-trigger"
)
.forEach(
    (button) => {

        button.addEventListener(
            "click",
            () => {

                viewUserName.textContent =
                button.dataset.name;

                viewUserEmail.textContent =
                button.dataset.email;

                viewUserPhone.textContent =
                button.dataset.phone;

                viewUserStatus.textContent =
                button.dataset.status;

                viewUserCreated.textContent =
                button.dataset.created;

                viewUserLastLogin.textContent =
                button.dataset.lastLogin;

                userCallButton.href =
                "tel:" +
                button.dataset.phone;

                userEmailButton.href =
                "mailto:" +
                button.dataset.email;

                userViewModal.classList.add(
                    "staff-modal-show"
                );

            }
        );

    }
);

function closeUserViewModal()
{
    userViewModal.classList.remove(
        "staff-modal-show"
    );
}

userViewClose
?.addEventListener(
    "click",
    closeUserViewModal
);

userViewOkay
?.addEventListener(
    "click",
    closeUserViewModal
);

window.addEventListener(
    "click",
    (event) => {

        if (
            event.target ===
            userViewModal
        ) {

            closeUserViewModal();

        }

    }
);

userViewClose?.addEventListener(
    "click",
    closeUserViewModal
);

userViewOkay?.addEventListener(
    "click",
    closeUserViewModal
);

window.addEventListener(
    "click",
    (event) => {

        if (
            event.target ===
            userViewModal
        ) {

            closeUserViewModal();

        }

    }
);

/* ==========================================
   RESPONSE MODAL
========================================== */

const userResponseModal =
document.getElementById(
    "userResponseModal"
);

const userResponseTitle =
document.getElementById(
    "userResponseTitle"
);

const userResponseMessage =
document.getElementById(
    "userResponseMessage"
);

const userResponseOkay =
document.getElementById(
    "userResponseOkay"
);

function showResponseModal(
    title,
    message
)
{
    userResponseTitle.textContent =
    title;

    userResponseMessage.textContent =
    message;

    userResponseModal.classList.add(
        "staff-modal-show"
    );
}

userResponseOkay
?.addEventListener(
    "click",
    () => {

        userResponseModal.classList.remove(
            "staff-modal-show"
        );

    }
);

/* ==========================================
   TOGGLE MODAL
========================================== */

const userToggleModal =
document.getElementById(
    "userToggleModal"
);

const userToggleClose =
document.getElementById(
    "userToggleClose"
);

const userToggleCancel =
document.getElementById(
    "userToggleCancel"
);

const userToggleForm =
document.getElementById(
    "userToggleForm"
);

const userToggleId =
document.getElementById(
    "userToggleId"
);

const userToggleAction =
document.getElementById(
    "userToggleAction"
);

const userToggleName =
document.getElementById(
    "userToggleName"
);

const userToggleTitle =
document.getElementById(
    "userToggleTitle"
);

document
.querySelectorAll(
    ".user-toggle-trigger"
)
.forEach(
    (button) => {

        button.addEventListener(
            "click",
            () => {

                const id =
                button.dataset.id;

                const action =
                button.dataset.action;

                const name =
                button.dataset.name;

                userToggleId.value =
                id;

                userToggleAction.value =
                action;

                userToggleName.textContent =
                name;

                userToggleTitle.textContent =
                action === "suspend"
                ? "Suspend Customer"
                : "Activate Customer";

                userToggleModal.classList.add(
                    "staff-modal-show"
                );

            }
        );

    }
);

function closeUserToggleModal()
{
    userToggleModal.classList.remove(
        "staff-modal-show"
    );
}

userToggleClose
?.addEventListener(
    "click",
    closeUserToggleModal
);

userToggleCancel
?.addEventListener(
    "click",
    closeUserToggleModal
);

if (
    userToggleForm
) {

    userToggleForm.addEventListener(
        "submit",
        async (event) => {

            event.preventDefault();

            try {

                const formData =
                new FormData(
                    userToggleForm
                );

                const response =
                await fetch(
                    "user-toggle",
                    {
                        method: "POST",
                        body: formData
                    }
                );

                const result =
                await response.json();

                closeUserToggleModal();

                showResponseModal(
                    result.success
                    ? "Success"
                    : "Error",
                    result.message
                );

                if (
                    result.success
                ) {

                    setTimeout(
                        () => {

                            location.reload();

                        },
                        1500
                    );

                }

            }

            catch {

                closeUserToggleModal();

                showResponseModal(
                    "Error",
                    "Something went wrong."
                );

            }

        }
    );

}

/* ==========================================
   DELETE MODAL
========================================== */

const userDeleteModal =
document.getElementById(
    "userDeleteModal"
);

const userDeleteClose =
document.getElementById(
    "userDeleteClose"
);

const userDeleteCancel =
document.getElementById(
    "userDeleteCancel"
);

const userDeleteForm =
document.getElementById(
    "userDeleteForm"
);

const userDeleteId =
document.getElementById(
    "userDeleteId"
);

const userDeleteName =
document.getElementById(
    "userDeleteName"
);

document
.querySelectorAll(
    ".user-delete-trigger"
)
.forEach(
    (button) => {

        button.addEventListener(
            "click",
            () => {

                userDeleteId.value =
                button.dataset.id;

                userDeleteName.textContent =
                button.dataset.name;

                userDeleteModal.classList.add(
                    "staff-modal-show"
                );

            }
        );

    }
);

function closeUserDeleteModal()
{
    userDeleteModal.classList.remove(
        "staff-modal-show"
    );
}

userDeleteClose
?.addEventListener(
    "click",
    closeUserDeleteModal
);

userDeleteCancel
?.addEventListener(
    "click",
    closeUserDeleteModal
);

if (
    userDeleteForm
) {

    userDeleteForm.addEventListener(
        "submit",
        async (event) => {

            event.preventDefault();

            try {

                const formData =
                new FormData(
                    userDeleteForm
                );

                const response =
                await fetch(
                    "user-delete",
                    {
                        method: "POST",
                        body: formData
                    }
                );

                const result =
                await response.json();

                closeUserDeleteModal();

                showResponseModal(
                    result.success
                    ? "Success"
                    : "Error",
                    result.message
                );

                if (
                    result.success
                ) {

                    setTimeout(
                        () => {

                            location.reload();

                        },
                        1500
                    );

                }

            }

            catch {

                closeUserDeleteModal();

                showResponseModal(
                    "Error",
                    "Something went wrong."
                );

            }

        }
    );

}

/* ==========================================
   OUTSIDE CLICK CLOSE
========================================== */

window.addEventListener(
    "click",
    (event) => {

        if (
            event.target ===
            userToggleModal
        ) {

            closeUserToggleModal();

        }

        if (
            event.target ===
            userDeleteModal
        ) {

            closeUserDeleteModal();

        }

        if (
            event.target ===
            userResponseModal
        ) {

            userResponseModal.classList.remove(
                "staff-modal-show"
            );

        }

    }
);