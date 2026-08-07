const recoveryRecoverModal =
document.getElementById(
    'recoveryRecoverModal'
);

const recoveryDeleteModal =
document.getElementById(
    'recoveryDeleteModal'
);

const recoveryResponseModal =
document.getElementById(
    'recoveryResponseModal'
);

const recoveryResponseTitle =
document.getElementById(
    'recoveryResponseTitle'
);

const recoveryResponseMessage =
document.getElementById(
    'recoveryResponseMessage'
);

const recoveryResponseOkay =
document.getElementById(
    'recoveryResponseOkay'
);

function recoveryShowModal(
    modal
)
{
    modal.classList.add(
        'recovery-modal-show'
    );
}

function recoveryHideModal(
    modal
)
{
    modal.classList.remove(
        'recovery-modal-show'
    );
}

document
.querySelectorAll(
'.recovery-recover-trigger'
)
.forEach(button =>
{
    button.addEventListener(
        'click',
        () =>
        {
            document.getElementById(
                'recoveryRecoverId'
            ).value =
            button.dataset.id;

            document.getElementById(
                'recoveryRecoverName'
            ).textContent =
            button.dataset.name;

            recoveryShowModal(
                recoveryRecoverModal
            );
        }
    );
});

document
.querySelectorAll(
'.recovery-delete-trigger'
)
.forEach(button =>
{
    button.addEventListener(
        'click',
        () =>
        {
            document.getElementById(
                'recoveryDeleteId'
            ).value =
            button.dataset.id;

            document.getElementById(
                'recoveryDeleteName'
            ).textContent =
            button.dataset.name;

            recoveryShowModal(
                recoveryDeleteModal
            );
        }
    );
});

[
'recoveryRecoverClose',
'recoveryRecoverCancel'
]
.forEach(id =>
{
    const element =
    document.getElementById(
        id
    );

    if (element)
    {
        element.addEventListener(
            'click',
            () =>
            recoveryHideModal(
                recoveryRecoverModal
            )
        );
    }
});

[
'recoveryDeleteClose',
'recoveryDeleteCancel'
]
.forEach(id =>
{
    const element =
    document.getElementById(
        id
    );

    if (element)
    {
        element.addEventListener(
            'click',
            () =>
            recoveryHideModal(
                recoveryDeleteModal
            )
        );
    }
});

if (
    recoveryResponseOkay
)
{
    recoveryResponseOkay
    .addEventListener(
        'click',
        () =>
        {
            location.reload();
        }
    );
}

document
.getElementById(
    'recoveryRecoverForm'
)
.addEventListener(
    'submit',
    async function(e)
    {
        e.preventDefault();

        const response =
        await fetch(
            'recovery-recover.php',
            {
                method: 'POST',
                body: new FormData(this)
            }
        );

        const data =
        await response.json();

        recoveryHideModal(
            recoveryRecoverModal
        );

        recoveryResponseTitle.textContent =
        data.success
        ? 'Success'
        : 'Error';

        recoveryResponseMessage.textContent =
        data.message;

        recoveryShowModal(
            recoveryResponseModal
        );
    }
);

document
.getElementById(
    'recoveryDeleteForm'
)
.addEventListener(
    'submit',
    async function(e)
    {
        e.preventDefault();

        const response =
        await fetch(
            'recovery-delete.php',
            {
                method:'POST',
                body:new FormData(
                    this
                )
            }
        );

        const data =
        await response.json();

        recoveryHideModal(
            recoveryDeleteModal
        );

        recoveryResponseTitle.textContent =
        data.success
        ? 'Success'
        : 'Error';

        recoveryResponseMessage.textContent =
        data.message;

        recoveryShowModal(
            recoveryResponseModal
        );
    }
);