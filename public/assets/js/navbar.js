document.addEventListener(
    "DOMContentLoaded",
    () => {

        const toggle =
            document.getElementById(
                "mobileToggle"
            );

        const menu =
            document.getElementById(
                "mobileMenuCard"
            );

        const overlay =
            document.getElementById(
                "mobileMenuOverlay"
            );

        if (
            !toggle ||
            !menu ||
            !overlay
        ) {

            return;

        }

        toggle.addEventListener(
            "click",
            () => {

                menu.classList.toggle(
                    "active"
                );

                overlay.classList.toggle(
                    "active"
                );

            }
        );

        overlay.addEventListener(
            "click",
            () => {

                menu.classList.remove(
                    "active"
                );

                overlay.classList.remove(
                    "active"
                );

            }
        );

        menu.querySelectorAll(
            "a"
        ).forEach(

            link => {

                link.addEventListener(
                    "click",
                    () => {

                        menu.classList.remove(
                            "active"
                        );

                        overlay.classList.remove(
                            "active"
                        );

                    }
                );

            }

        );

        const recoverLink =
            document.getElementById(
                "navbarRecoverAccount"
            );

        if (recoverLink) {

            recoverLink.addEventListener(
                "click",
                function (event) {

                    event.preventDefault();

                    /*
                    -----------------------------------------
                    Close mobile menu
                    -----------------------------------------
                    */

                    menu.classList.remove(
                        "active"
                    );

                    overlay.classList.remove(
                        "active"
                    );

                    /*
                    -----------------------------------------
                    Trigger existing modal button
                    -----------------------------------------
                    */

                    const modalButton =
                        document.getElementById(
                            "openForgotPasswordModal"
                        );

                    if (modalButton) {

                        modalButton.click();

                    }

                }
            );

        }

    }
    
);