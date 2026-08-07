console.log(
    "Customer portal loaded."
);

function toggleSidebar()
{
    const sidebar =
        document.querySelector(
            ".customer-sidebar"
        );

    if (!sidebar) {
        return;
    }

    sidebar.classList.toggle(
        "active"
    );
}

document.addEventListener(
    "DOMContentLoaded",
    function()
    {
        const closeButton =
            document.getElementById(
                "closeFlashModalBtn"
            );

        if (!closeButton) {
            return;
        }

        closeButton.addEventListener(
            "click",
            function()
            {
                const modal =
                    document.getElementById(
                        "flashModal"
                    );

                if (!modal) {
                    return;
                }

                modal.remove();
            }
        );
    }
);

