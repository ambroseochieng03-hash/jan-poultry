<?php

require_once
__DIR__ .
'/../includes/init.php';

require_once
__DIR__ .
'/../app/helpers/Auth.php';

Auth::requireSuperAdmin();

$db =
(new Database())
->getConnection();

$stmt =
$db->query(

    "

    SELECT

        id,
        name,
        email,
        phone,
        role,
        status,
        created_at

    FROM users

    WHERE status = 'deleted'

    ORDER BY id DESC

    "

);

$deletedUsers =
$stmt->fetchAll();

$pageCss =
'recovery-center.css';

$pageJs =
'recovery-center.js';

include
__DIR__ .
'/../includes/admin-header.php';

?>

<div class="admin-page">

    <div class="page-header">

        <div>

            <h1>

                Recovery Center

            </h1>

            <p>

                Recover or permanently remove deleted accounts.

            </p>

        </div>

    </div>

    <?php if (empty($deletedUsers)): ?>

        <div class="recovery-empty">

            <h3>

                No deleted records found

            </h3>

            <p>

                Deleted accounts will appear here for recovery.

            </p>

        </div>

    <?php else: ?>

        <div class="recovery-grid">

            <?php foreach ($deletedUsers as $user): ?>

                <?php

                $avatar =
                strtoupper(
                    substr(
                        trim(
                            $user['name']
                            ?? 'U'
                        ),
                        0,
                        1
                    )
                );

                ?>

                <div
                class="recovery-card">

                    <div
                    class="recovery-avatar">

                        <?= htmlspecialchars(
                            $avatar
                        ) ?>

                    </div>

                    <div
                    class="recovery-name">

                        <?= htmlspecialchars(
                            $user['name']
                            ?: 'Unnamed User'
                        ) ?>

                    </div>

                    <div
                    class="recovery-email">

                        <?= htmlspecialchars(
                            $user['email']
                            ?: 'No Email'
                        ) ?>

                    </div>

                    <div
                    class="recovery-badges">

                        <span
                        class="
                        recovery-badge
                        recovery-badge-deleted
                        ">

                            Deleted

                        </span>

                        <span
                        class="
                        recovery-badge
                        recovery-badge-role
                        ">

                            <?= strtoupper(
                                str_replace(
                                    '_',
                                    ' ',
                                    $user['role']
                                )
                            ) ?>

                        </span>

                    </div>

                    <div
                    class="recovery-info">

                        <div
                        class="recovery-info-row">

                            <span
                            class="recovery-info-label">

                                Phone

                            </span>

                            <span
                            class="recovery-info-value">

                                <?= htmlspecialchars(
                                    $user['phone']
                                    ?: '—'
                                ) ?>

                            </span>

                        </div>

                        <div
                        class="recovery-info-row">

                            <span
                            class="recovery-info-label">

                                Created

                            </span>

                            <span
                            class="recovery-info-value">

                                <?= date(
                                    'd M Y',
                                    strtotime(
                                        $user['created_at']
                                    )
                                ) ?>

                            </span>

                        </div>

                    </div>

                    <div
                    class="recovery-actions">

                        <button
                        type="button"
                        class="
                        recovery-btn
                        recovery-btn-recover
                        recovery-recover-trigger
                        "
                        data-id="<?= $user['id'] ?>"
                        data-name="<?= htmlspecialchars(
                            $user['name']
                        ) ?>">

                            Recover

                        </button>

                        <button
                        type="button"
                        class="
                        recovery-btn
                        recovery-btn-delete
                        recovery-delete-trigger
                        "
                        data-id="<?= $user['id'] ?>"
                        data-name="<?= htmlspecialchars(
                            $user['name']
                        ) ?>">

                            Permanent Delete

                        </button>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    <?php endif; ?>

</div>

<div
id="recoveryRecoverModal"
class="recovery-modal">

    <div
    class="recovery-modal-content">

        <button
        type="button"
        id="recoveryRecoverClose"
        class="recovery-modal-close">

            ×

        </button>

        <h2>

            Recover Account

        </h2>

        <p
        class="recovery-modal-message">

            Are you sure you want to recover

            <strong
            id="recoveryRecoverName">

            </strong>

            ?

        </p>

        <form
        id="recoveryRecoverForm">

            <input
            type="hidden"
            name="csrf_token"
            value="<?= Security::generateCsrfToken() ?>">

            <input
            type="hidden"
            name="id"
            id="recoveryRecoverId">

            <div
            class="recovery-modal-actions">

                <button
                type="button"
                id="recoveryRecoverCancel"
                class="recovery-modal-cancel">

                    Cancel

                </button>

                <button
                type="submit"
                class="recovery-modal-confirm">

                    Recover

                </button>

            </div>

        </form>

    </div>

</div>

<div
id="recoveryDeleteModal"
class="recovery-modal">

    <div
    class="recovery-modal-content">

        <button
        type="button"
        id="recoveryDeleteClose"
        class="recovery-modal-close">

            ×

        </button>

        <h2>

            Permanent Delete

        </h2>

        <p
        class="recovery-modal-message">

            Are you sure you want to permanently delete

            <strong
            id="recoveryDeleteName">

            </strong>

            ?

        </p>

        <p
        class="recovery-modal-warning">

            WARNING: This action cannot be undone and will permanently remove the account from the database.

        </p>

        <form
        id="recoveryDeleteForm">

            <input
            type="hidden"
            name="csrf_token"
            value="<?= Security::generateCsrfToken() ?>">

            <input
            type="hidden"
            name="id"
            id="recoveryDeleteId">

            <div
            class="recovery-modal-actions">

                <button
                type="button"
                id="recoveryDeleteCancel"
                class="recovery-modal-cancel">

                    Cancel

                </button>

                <button
                type="submit"
                class="recovery-modal-delete">

                    Permanently Delete

                </button>

            </div>

        </form>

    </div>

</div>

<div
id="recoveryResponseModal"
class="recovery-modal">

    <div
    class="recovery-modal-content">

        <h2
        id="recoveryResponseTitle">

        </h2>

        <p
        id="recoveryResponseMessage">

        </p>

        <div
        class="recovery-modal-actions">

            <button
            type="button"
            id="recoveryResponseOkay"
            class="recovery-modal-cancel">

                Okay

            </button>

        </div>

    </div>

</div>

<?php

include
__DIR__ .
'/../includes/admin-footer.php';

?>