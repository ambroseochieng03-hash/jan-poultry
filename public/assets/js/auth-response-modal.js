/*
|--------------------------------------------------------------------------
| JAN Poultry
| Authentication Response Modal
|--------------------------------------------------------------------------
*/

document.addEventListener(
    "DOMContentLoaded",
    function () {

        const modal =
            document.getElementById(
                "authResponseModal"
            );

        if (!modal) {
            return;
        }

        const closeButton =
            document.getElementById(
                "closeAuthResponseModal"
            );

        const closeFooterButton =
            document.getElementById(
                "closeAuthResponseButton"
            );

        /*
        ---------------------------------------------------
        Close Modal
        ---------------------------------------------------
        */

        function closeModal() {

            modal.classList.remove(
                "show"
            );

            document.body.style.overflow = "";

        }

        /*
        ---------------------------------------------------
        X Button
        ---------------------------------------------------
        */

        closeButton.addEventListener(
            "click",
            closeModal
        );

        /*
        ---------------------------------------------------
        Close Button
        ---------------------------------------------------
        */

        closeFooterButton.addEventListener(
            "click",
            closeModal
        );

        /*
        ---------------------------------------------------
        Outside Click
        ---------------------------------------------------
        */

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

        /*
        ---------------------------------------------------
        ESC Key
        ---------------------------------------------------
        */

        document.addEventListener(
            "keydown",
            function (event) {

                if (
                    event.key === "Escape"
                    &&
                    modal.classList.contains(
                        "show"
                    )
                ) {

                    closeModal();

                }

            }
        );

        /*
        ---------------------------------------------------
        Global Function
        ---------------------------------------------------
        */

        window.showAuthResponseModal =
            function (
                success,
                title,
                message,
                email = ""
            ) {

                const icon =
                    document.getElementById(
                        "authResponseIcon"
                    );

                const titleElement =
                    document.getElementById(
                        "authResponseTitle"
                    );

                const messageElement =
                    document.getElementById(
                        "authResponseMessage"
                    );

                const providerContainer =
                    document.getElementById(
                        "authProviderButtonContainer"
                    );

                /*
                ------------------------------------------
                Text
                ------------------------------------------
                */

                titleElement.textContent =
                    title;

                messageElement.textContent =
                    message;

                /*
                ------------------------------------------
                Icon
                ------------------------------------------
                */

                if (success) {

                    icon.classList.remove(
                        "error"
                    );

                    icon.innerHTML =
                        '<i class="fa-solid fa-circle-check"></i>';

                } else {

                    icon.classList.add(
                        "error"
                    );

                    icon.innerHTML =
                        '<i class="fa-solid fa-circle-xmark"></i>';

                }

                /*
                ------------------------------------------
                Email Provider Button
                ------------------------------------------
                */

                providerContainer.innerHTML = "";

                const lower =
                    email.toLowerCase();

                let button = null;

                if (
                    lower.endsWith("@gmail.com")
                ) {

                    button =
                        createProviderButton(
                            "gmail",
                            "fa-brands fa-google",
                            "Open Gmail",
                            "https://mail.google.com/"
                        );

                }

                else if (

                    lower.endsWith("@outlook.com")
                    ||
                    lower.endsWith("@hotmail.com")
                    ||
                    lower.endsWith("@live.com")

                ) {

                    button =
                        createProviderButton(
                            "outlook",
                            "fa-brands fa-microsoft",
                            "Open Outlook",
                            "https://outlook.live.com/mail/"
                        );

                }

                else if (

                    lower.endsWith("@yahoo.com")

                ) {

                    button =
                        createProviderButton(
                            "yahoo",
                            "fa-brands fa-yahoo",
                            "Open Yahoo",
                            "https://mail.yahoo.com/"
                        );

                }

                if (button) {

                    providerContainer.appendChild(
                        button
                    );

                }

                modal.classList.add(
                    "show"
                );

                document.body.style.overflow =
                    "hidden";

            };

        /*
        ---------------------------------------------------
        Create Provider Button
        ---------------------------------------------------
        */

        function createProviderButton(
            cssClass,
            icon,
            text,
            url
        ) {

            const button =
                document.createElement(
                    "button"
                );

            button.type =
                "button";

            button.className =
                "provider-btn " +
                cssClass;

            button.innerHTML =

                '<i class="' +
                icon +
                '"></i> ' +
                text;

            button.addEventListener(
                "click",
                function () {

                    window.open(
                        url,
                        "_blank"
                    );

                }
            );

            return button;

        }

    }
);
