const staffDeleteModal =
document.getElementById(
    "staffDeleteModal"
);

const staffDeleteName =
document.getElementById(
    "staffDeleteAdminName"
);

const staffDeleteForm =
document.getElementById(
    "staffDeleteForm"
);

const staffDeleteId =
document.getElementById(
    "staffDeleteId"
);

const staffDeleteClose =
document.getElementById(
    "staffDeleteClose"
);

const staffDeleteCancel =
document.getElementById(
    "staffDeleteCancel"
);

document
.querySelectorAll(
    ".staff-delete-trigger"
)
.forEach(
    (button) => {

        button.addEventListener(
            "click",
            () => {

                const id =
                button.dataset.id;

                const name =
                button.dataset.name;

                staffDeleteName.textContent =
                name;

                staffDeleteId.value =
                id;

                staffDeleteModal.classList.add(
                    "staff-modal-show"
                );

            }
        );

    }
);

function closeStaffDeleteModal()
{
    staffDeleteModal.classList.remove(
        "staff-modal-show"
    );
}

if (staffDeleteClose) {

    staffDeleteClose.addEventListener(
        "click",
        closeStaffDeleteModal
    );

}

if (staffDeleteCancel) {

    staffDeleteCancel.addEventListener(
        "click",
        closeStaffDeleteModal
    );

}

window.addEventListener(
    "click",
    (event) => {

        if (
            event.target ===
            staffDeleteModal
        ) {

            closeStaffDeleteModal();

        }

    }
);

/* ==========================================
   TOGGLE ADMIN STATUS MODAL
========================================== */

const staffToggleModal =
document.getElementById(
    "staffToggleModal"
);

const staffToggleTitle =
document.getElementById(
    "staffToggleTitle"
);

const staffToggleAdminName =
document.getElementById(
    "staffToggleAdminName"
);

const staffToggleForm =
document.getElementById(
    "staffToggleForm"
);

const staffToggleId =
document.getElementById(
    "staffToggleId"
);

const staffToggleAction =
document.getElementById(
    "staffToggleAction"
);

const staffToggleClose =
document.getElementById(
    "staffToggleClose"
);

const staffToggleCancel =
document.getElementById(
    "staffToggleCancel"
);

document
.querySelectorAll(
    ".staff-toggle-trigger"
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

                staffToggleId.value =
                id;

                staffToggleAction.value =
                action;

                staffToggleAdminName.textContent =
                name;

                staffToggleTitle.textContent =
                action === "suspend"
                ? "Suspend Administrator"
                : "Activate Administrator";

                staffToggleModal.classList.add(
                    "staff-modal-show"
                );

            }
        );

    }
);

function closeStaffToggleModal()
{
    staffToggleModal.classList.remove(
        "staff-modal-show"
    );
}

staffToggleClose
?.addEventListener(
    "click",
    closeStaffToggleModal
);

staffToggleCancel
?.addEventListener(
    "click",
    closeStaffToggleModal
);

/* ==========================================
   CREATE ADMIN MODAL
========================================== */

const openAdminCreateModal =
document.getElementById(
    "openAdminCreateModal"
);

const adminCreateModal =
document.getElementById(
    "adminCreateModal"
);

const adminCreateClose =
document.getElementById(
    "adminCreateClose"
);

const adminCreateCancel =
document.getElementById(
    "adminCreateCancel"
);

const adminCreateForm =
document.getElementById(
    "adminCreateForm"
);

const adminCreateSubmit =
document.getElementById(
    "adminCreateSubmit"
);

const staffResponseModal =
document.getElementById(
    "staffResponseModal"
);

const staffResponseTitle =
document.getElementById(
    "staffResponseTitle"
);

const staffResponseMessage =
document.getElementById(
    "staffResponseMessage"
);

const staffResponseOkay =
document.getElementById(
    "staffResponseOkay"
);

/* -------------------------
OPEN CREATE MODAL
------------------------- */

if (
    openAdminCreateModal
) {

    openAdminCreateModal
    .addEventListener(
        "click",
        () => {

            adminCreateModal
            .classList.add(
                "staff-modal-show"
            );

        }
    );

}

/* -------------------------
CLOSE CREATE MODAL
------------------------- */

function closeAdminCreateModal()
{
    adminCreateModal
    ?.classList.remove(
        "staff-modal-show"
    );
}

adminCreateClose
?.addEventListener(
    "click",
    closeAdminCreateModal
);

adminCreateCancel
?.addEventListener(
    "click",
    closeAdminCreateModal
);

/* -------------------------
RESPONSE MODAL
------------------------- */

function showResponseModal(
    title,
    message
)
{
    staffResponseTitle.textContent =
    title;

    staffResponseMessage.textContent =
    message;

    staffResponseModal.classList.add(
        "staff-modal-show"
    );
}

staffResponseOkay
?.addEventListener(
    "click",
    () => {

        staffResponseModal.classList.remove(
            "staff-modal-show"
        );

    }
);

/* -------------------------
AJAX CREATE ADMIN
------------------------- */

if (
    adminCreateForm
) {

    adminCreateForm
    .addEventListener(
        "submit",
        async (event) => {

            event.preventDefault();

            adminCreateSubmit.disabled =
            true;

            adminCreateSubmit.textContent =
            "Creating...";

            try {

                const formData =
                new FormData(
                    adminCreateForm
                );

                const response =
                await fetch(
                    "admin-create.php",
                    {
                        method: "POST",
                        body: formData
                    }
                );

                const result =
                await response.json();

                closeAdminCreateModal();

                showResponseModal(
                    result.success
                    ? "Success"
                    : "Error",
                    result.message
                );

                if (
                    result.success
                ) {

                    adminCreateForm.reset();

                    setTimeout(
                        () => {

                            location.reload();

                        },
                        1500
                    );

                }

            }

            catch (error) {

                closeAdminCreateModal();

                showResponseModal(
                    "Error",
                    "Something went wrong. Please try again."
                );

            }

            finally {

                adminCreateSubmit.disabled =
                false;

                adminCreateSubmit.textContent =
                "Create Admin";

            }

        }
    );

}

if (
    staffDeleteForm
) {

    staffDeleteForm.addEventListener(
        "submit",
        async (event) => {

            event.preventDefault();

            try {

                const formData =
                new FormData(
                    staffDeleteForm
                );

                const response =
                await fetch(
                    "admin-delete.php",
                    {
                        method: "POST",
                        body: formData
                    }
                );

                const result =
                await response.json();

                closeStaffDeleteModal();

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

                closeStaffDeleteModal();

                showResponseModal(
                    "Error",
                    "Something went wrong."
                );

            }

        }
    );

}

if (
    staffToggleForm
) {

    staffToggleForm.addEventListener(
        "submit",
        async (event) => {

            event.preventDefault();

            try {

                const formData =
                new FormData(
                    staffToggleForm
                );

                const response =
                await fetch(
                    "admin-toggle.php",
                    {
                        method: "POST",
                        body: formData
                    }
                );

                const result =
                await response.json();

                closeStaffToggleModal();

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

                closeStaffToggleModal();

                showResponseModal(
                    "Error",
                    "Something went wrong."
                );

            }

        }
    );

}