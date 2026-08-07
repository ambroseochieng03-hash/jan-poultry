/*
|--------------------------------------------------------------------------
| JAN Poultry
| Application Response Modal
|--------------------------------------------------------------------------
*/

(function () {

    "use strict";

    const modal =
        document.getElementById(
            "jpAppResponseModal"
        );

    if (!modal) {
        return;
    }

    const icon =
        document.getElementById(
            "jpAppModalIcon"
        );

    const title =
        document.getElementById(
            "jpAppModalTitle"
        );

    const message =
        document.getElementById(
            "jpAppModalMessage"
        );

    const button =
        document.getElementById(
            "jpAppModalButton"
        );

    let timer = null;

    let redirectUrl = null;

    function closeModal() {

        modal.classList.remove(
            "jp-show"
        );

    }

    button.addEventListener(
        "click",
        function () {

            if (timer) {

                clearTimeout(
                    timer
                );

            }

            if (redirectUrl) {

                window.location.href =
                    redirectUrl;

                return;
            }

            closeModal();

        }
    );

    window.showAppResponseModal =
        function (options = {}) {

            icon.textContent =
                options.icon
                ??
                (
                    options.success
                        ? "✅"
                        : "⚠️"
                );

            title.textContent =
                options.title ?? "Success";

            message.textContent =
                options.message ?? "";

            button.textContent =
                options.buttonText ?? "OK";

            redirectUrl =
                options.redirect ?? null;

            modal.classList.add(
                "jp-show"
            );

            if (timer) {

                clearTimeout(
                    timer
                );

            }

            if (options.autoClose) {

                timer =
                    setTimeout(
                        function () {

                            if (redirectUrl) {

                                window.location.href =
                                    redirectUrl;

                                return;

                            }

                            closeModal();

                        },

                        options.autoClose

                    );

            }

        };

})();