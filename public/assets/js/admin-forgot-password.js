/*
|--------------------------------------------------------------------------
| JAN Poultry
| Admin Forgot Password Modal
|--------------------------------------------------------------------------
*/

document.addEventListener('DOMContentLoaded', () => {

    const modal =
        document.getElementById(
            'forgotPasswordModal'
        );

    if (!modal) {
        return;
    }

    const openButton =
        document.getElementById(
            'openForgotPasswordModal'
        );

    const closeButton =
        document.getElementById(
            'closeForgotPasswordModal'
        );

    const cancelButton =
        document.getElementById(
            'cancelForgotPassword'
        );

    const form =
        document.getElementById(
            'forgotPasswordForm'
        );

    /*
    ---------------------------------------------------
    Open Modal
    ---------------------------------------------------
    */

    function openModal()
    {
        modal.classList.add('show');

        document.body.style.overflow = 'hidden';

        const emailInput =
            document.getElementById(
                'forgotEmail'
            );

        if (emailInput) {

            setTimeout(() => {

                emailInput.focus();

            }, 150);

        }
    }

    /*
    ---------------------------------------------------
    Close Modal
    ---------------------------------------------------
    */

    function closeModal()
    {
        modal.classList.remove('show');

        document.body.style.overflow = '';

        if (form) {
            form.reset();
        }
    }

    /*
    ---------------------------------------------------
    Open
    ---------------------------------------------------
    */

    if (openButton) {

        openButton.addEventListener(
            'click',
            function (event) {

                event.preventDefault();

                openModal();

            }
        );

    }

    /*
    ---------------------------------------------------
    Close Button
    ---------------------------------------------------
    */

    if (closeButton) {

        closeButton.addEventListener(
            'click',
            closeModal
        );

    }

    /*
    ---------------------------------------------------
    Cancel Button
    ---------------------------------------------------
    */

    if (cancelButton) {

        cancelButton.addEventListener(
            'click',
            closeModal
        );

    }

    /*
    ---------------------------------------------------
    Click Outside
    ---------------------------------------------------
    */

    modal.addEventListener(
        'click',
        function (event) {

            if (
                event.target === modal
            ) {

                closeModal();

            }

        }
    );

    /*
    ---------------------------------------------------
    ESC Key
    ---------------------------------------------------
    */

    document.addEventListener(
        'keydown',
        function (event) {

            if (
                event.key === 'Escape'
                &&
                modal.classList.contains('show')
            ) {

                closeModal();

            }

        }
    );

    /*
    ---------------------------------------------------
    AJAX Forgot Password
    ---------------------------------------------------
    */

    if (form) {

        form.addEventListener(
            'submit',
            async function (event) {

                event.preventDefault();

                const submitButton =
                    document.getElementById(
                        'sendResetButton'
                    );

                const originalText =
                    submitButton.innerHTML;

                submitButton.disabled = true;

                submitButton.innerHTML =

                    `
                        <i class="fa-solid fa-spinner fa-spin"></i>

                        Sending...
                    `;

                /*
                ------------------------------------------
                Prepare Form Data
                ------------------------------------------
                */

                const formData =
                    new FormData(form);

                const email =
                    formData
                        .get('email')
                        .trim()
                        .toLowerCase();

                formData.set(
                    'email',
                    email
                );

                try {

                    const response =
                        await fetch(

                            form.action,

                            {

                                method: 'POST',

                                body: formData

                            }

                        );

                    const result =
                        await response.json();

                    submitButton.disabled = false;

                    submitButton.innerHTML =
                        originalText;

                    /*
                    ------------------------------------------
                    Refresh CSRF Token
                    ------------------------------------------
                    */

                    if (result.csrf_token) {

                        const csrfInput =

                            form.querySelector(
                                'input[name="csrf_token"]'
                            );

                        if (csrfInput) {

                            csrfInput.value =
                                result.csrf_token;

                        }

                    }    

                    if (result.success) {

                        closeModal();

                        /*
                        ------------------------------------------
                        Reusable Response Modal
                        ------------------------------------------
                        */

                        if (
                            typeof showAuthResponseModal ===
                            'function'
                        ) {

                            showAuthResponseModal(
                                true,
                                "Password Reset Link Sent",
                                result.message,
                                email
                            );

                        } else {

                            alert(result.message);

                        }

                    } else {

                        if (
                            typeof showAuthResponseModal === 'function'
                        ) {

                            closeModal();

                            showAuthResponseModal(
                                false,
                                "Request Failed",
                                result.message
                            );

                        } else {

                            alert(result.message);

                        }

                    }

                } catch (error) {

                    submitButton.disabled = false;

                    submitButton.innerHTML =
                        originalText;

                    if (
                        typeof showAuthResponseModal === 'function'
                    ) {

                        closeModal();

                        showAuthResponseModal(
                            false,
                            "Network Error",
                            "Unable to connect to the server. Please try again."
                        );

                    } else {

                        alert(
                            'Unable to connect to the server.'
                        );

                    }

                }

            }

        );

    }


});
