/*
|--------------------------------------------------------------------------
| JAN Poultry
| Footer
|--------------------------------------------------------------------------
*/

document.addEventListener(
    'DOMContentLoaded',
    function () {

        const recoverButton =
            document.getElementById(
                'jpFooterRecoverAccount'
            );

        const forgotPasswordButton =
            document.getElementById(
                'openForgotPasswordModal'
            );

        if (
            !recoverButton ||
            !forgotPasswordButton
        ) {
            return;
        }

        recoverButton.addEventListener(
            'click',
            function (event) {

                event.preventDefault();

                forgotPasswordButton.click();

            }
        );

    }
);
