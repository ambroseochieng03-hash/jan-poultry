document.addEventListener(
    "DOMContentLoaded",
    () =>
    {

        let pendingProfileData = null;


        /*
        ==========================================
        MODALS
        ==========================================
        */

        const editProfileModal =
        document.getElementById(
            "editProfileModal"
        );

        const passwordModal =
        document.getElementById(
            "passwordModal"
        );

        const emailVerificationModal =
        document.getElementById(
            "emailVerificationModal"
        );


        const openEditProfileModal =
        document.getElementById(
            "openEditProfileModal"
        );

        const openPasswordModal =
        document.getElementById(
            "openPasswordModal"
        );


        if (
            openEditProfileModal &&
            editProfileModal
        ) {

            openEditProfileModal.onclick =
            () =>
            {

                editProfileModal.classList.add(
                    "profile-modal-show"
                );

            };

        }


        if (
            openPasswordModal &&
            passwordModal
        ) {

            openPasswordModal.onclick =
            () =>
            {

                passwordModal.classList.add(
                    "profile-modal-show"
                );

            };

        }



        /*
        ==========================================
        CLOSE MODALS
        ==========================================
        */

        document
        .querySelectorAll(
            "[data-close-modal], .profile-modal-close"
        )
        .forEach(
            button =>
            {

                button.onclick =
                () =>
                {

                    const modal =
                    button.closest(
                        ".profile-modal"
                    );

                    if(modal)
                    {

                        modal.classList.remove(
                            "profile-modal-show"
                        );

                    }

                };

            }
        );


        document
        .querySelectorAll(
            ".profile-modal"
        )
        .forEach(
            modal =>
            {

                modal.onclick =
                event =>
                {

                    if(
                        event.target === modal
                    )
                    {

                        modal.classList.remove(
                            "profile-modal-show"
                        );

                    }

                };

            }
        );


        document.addEventListener(
            "keydown",
            event =>
            {

                if(
                    event.key === "Escape"
                )
                {

                    document
                    .querySelectorAll(
                        ".profile-modal"
                    )
                    .forEach(
                        modal =>
                        {

                            modal.classList.remove(
                                "profile-modal-show"
                            );

                        }
                    );

                }

            }
        );



        /*
        ==========================================
        MESSAGE MODAL
        ==========================================
        */

        function showMessage(
            title,
            message
        ){

            const modal =
            document.getElementById(
                "profileMessageModal"
            );

            const titleBox =
            document.getElementById(
                "profileMessageTitle"
            );

            const textBox =
            document.getElementById(
                "profileMessageText"
            );


            if(
                modal &&
                titleBox &&
                textBox
            )
            {

                titleBox.textContent =
                title;

                textBox.textContent =
                message;


                modal.classList.add(
                    "profile-modal-show"
                );

            }

        }



        const messageOk =
        document.getElementById(
            "profileMessageOk"
        );


        if(messageOk)
        {

            messageOk.onclick =
            () =>
            {

                document
                .getElementById(
                    "profileMessageModal"
                )
                .classList.remove(
                    "profile-modal-show"
                );

                location.reload();

            };

        }



        /*
        ==========================================
        EDIT PROFILE AJAX
        ==========================================
        */


        const editProfileForm =
        document.getElementById(
            "editProfileForm"
        );


        const originalEmail =
        document.getElementById(
            "originalEmail"
        );


        if(editProfileForm)
        {

            editProfileForm.onsubmit =
            event =>
            {

                event.preventDefault();


                const formData =
                new FormData(
                    editProfileForm
                );


                const newEmail =
                formData.get(
                    "email"
                );


                if(
                    newEmail.toLowerCase()
                    !==
                    originalEmail.value.toLowerCase()
                )
                {

                    pendingProfileData =
                    formData;


                    emailVerificationModal
                    .classList.add(
                        "profile-modal-show"
                    );


                    return;

                }


                updateProfile(
                    formData
                );

            };

        }



        function updateProfile(
            formData
        ){

            fetch(
                "profile-update",
                {
                    method:"POST",
                    body:formData
                }
            )
            .then(
                response =>
                response.json()
            )
            .then(
                data =>
                {

                    if(data.success)
                    {

                        showMessage(
                            "Success",
                            data.message
                        );

                    }
                    else
                    {

                        showMessage(
                            "Error",
                            data.message
                        );

                    }

                }
            )
            .catch(
                () =>
                {

                    showMessage(
                        "Error",
                        "Something went wrong."
                    );

                }
            );

        }




        /*
        ==========================================
        EMAIL CHANGE CONFIRMATION
        ==========================================
        */


        const confirmEmailChange =
        document.getElementById(
            "confirmEmailChange"
        );


        if(confirmEmailChange)
        {

            confirmEmailChange.onclick =
            () =>
            {

                const password =
                document.getElementById(
                    "emailVerificationPassword"
                )
                .value;


                if(!password)
                {

                    showMessage(
                        "Error",
                        "Password required."
                    );

                    return;

                }


                pendingProfileData.append(
                    "current_password",
                    password
                );


                emailVerificationModal
                .classList.remove(
                    "profile-modal-show"
                );


                updateProfile(
                    pendingProfileData
                );


                pendingProfileData =
                null;

            };

        }

        /*
        ==========================================
        EMAIL VERIFICATION CANCEL
        ==========================================
        */

        const cancelEmailVerification =
        document.getElementById(
            "cancelEmailVerification"
        );


        if(cancelEmailVerification)
        {

            cancelEmailVerification.onclick =
            () =>
            {

                emailVerificationModal
                .classList
                .remove(
                    "profile-modal-show"
                );


                document
                .getElementById(
                    "emailVerificationPassword"
                )
                .value = "";


                pendingProfileData = null;

            };

        }



        /*
        ==========================================
        CHANGE PASSWORD AJAX
        ==========================================
        */


        const passwordForm =
        document.getElementById(
            "changePasswordForm"
        );


        if(passwordForm)
        {

            passwordForm.onsubmit =
            event =>
            {

                event.preventDefault();


                const formData =
                new FormData(
                    passwordForm
                );


                fetch(
                    "profile-password",
                    {
                        method:"POST",
                        body:formData
                    }
                )
                .then(
                    response =>
                    response.json()
                )
                .then(
                    data =>
                    {

                        passwordModal
                        .classList.remove(
                            "profile-modal-show"
                        );


                        showMessage(
                            data.success
                            ? "Success"
                            : "Error",
                            data.message
                        );

                    }
                )
                .catch(
                    () =>
                    {

                        showMessage(
                            "Error",
                            "Something went wrong."
                        );

                    }
                );

            };

        }



        /*
        ==========================================
        PASSWORD VISIBILITY
        ==========================================
        */


        const togglePasswords =
        document.getElementById(
            "togglePasswords"
        );


        if(togglePasswords)
        {

            togglePasswords.onchange =
            () =>
            {

                document
                .querySelectorAll(
                    ".password-field"
                )
                .forEach(
                    field =>
                    {

                        field.type =
                        togglePasswords.checked
                        ? "text"
                        : "password";

                    }
                );

            };

        }



        const showEmailPassword =
        document.getElementById(
            "showEmailPassword"
        );


        const emailPasswordField =
        document.getElementById(
            "emailVerificationPassword"
        );


        if(
            showEmailPassword &&
            emailPasswordField
        )
        {

            showEmailPassword.onchange =
            () =>
            {

                emailPasswordField.type =
                showEmailPassword.checked
                ? "text"
                : "password";

            };

        }


    }
);