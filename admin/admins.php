<?php


require_once
__DIR__ .
'/../includes/init.php';

require_once
__DIR__ .
'/../app/helpers/Auth.php';

Auth::requireAdmin();

$db =
(new Database())
->getConnection();

$currentUserId =
Auth::userId();

$stmt =
$db->prepare(

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

    WHERE role IN
    (
        'admin',
        'super_admin'
    )
    AND status != 'deleted'

    ORDER BY

        CASE

            WHEN id = ? THEN 0

            WHEN role = 'super_admin' THEN 1

            ELSE 2

        END,

        id ASC

    "

);

$stmt->execute([
    $currentUserId
]);

$admins =
$stmt->fetchAll();

$pageCss = 'staff.css';

$pageJs = 'staff.js';

include
__DIR__ .
'/../includes/admin-header.php';

?>

<div class="admin-page">

    <div class="page-header">

        <div>

            <h1>

                Admin Management

            </h1>

            <p>

                Manage JAN Poultry administrators.

            </p>

        </div>

        <?php if (
            Auth::isSuperAdmin()
        ): ?>

        <button
        type="button"
        id="openAdminCreateModal"
        class="staff-add-admin-btn">

            + Add Admin

        </button>

        <?php endif; ?>

    </div>

    <div class="staff-grid">

    <?php foreach ($admins as $admin): ?>

    <?php

    $avatar =
    strtoupper(
        substr(
            trim($admin['name']),
            0,
            1
        )
    );

    $isCurrentUser =
    $admin['id']
    ===
    $currentUserId;

    $isSuperAdmin =
    $admin['role']
    ===
    'super_admin';

    $cardClass =
    'staff-card';

    if ($isCurrentUser) {

        $cardClass .=
        ' staff-card-current';

    } elseif ($isSuperAdmin) {

        $cardClass .=
        ' staff-card-super';
    }

    ?>

    <div class="<?= $cardClass ?>">

        <div class="staff-avatar">

            <?= htmlspecialchars(
                $avatar
            ) ?>

        </div>

        <div class="staff-name">

            <?= htmlspecialchars(
                $admin['name']
            ) ?>

        </div>

        <div class="staff-email">

            <?= htmlspecialchars(
                $admin['email']
            ) ?>

        </div>

        <div class="staff-badges">

            <?php if ($isCurrentUser): ?>

            <span
            class="
            staff-badge
            staff-badge-current
            ">
                Current User
            </span>

            <?php endif; ?>

            <?php if ($isSuperAdmin): ?>

            <span
            class="
            staff-badge
            staff-badge-super
            ">
                Super Admin
            </span>

            <?php endif; ?>

            <?php if (
                $admin['status']
                ===
                'active'
            ): ?>

            <span
            class="
            staff-badge
            staff-badge-active
            ">
                Active
            </span>

            <?php else: ?>

            <span
            class="
            staff-badge
            staff-badge-suspended
            ">
                Suspended
            </span>

            <?php endif; ?>

        </div>

        <div class="staff-info">

            <div class="staff-info-row">

                <span
                class="staff-info-label">

                    Phone

                </span>

                <span
                class="staff-info-value">

                    <?= htmlspecialchars(
                        $admin['phone']
                        ?? '—'
                    ) ?>

                </span>

            </div>

            <div class="staff-info-row">

                <span
                class="staff-info-label">

                    Role

                </span>

                <span
                class="staff-info-value">

                    <?= strtoupper(
                        str_replace(
                            '_',
                            ' ',
                            $admin['role']
                        )
                    ) ?>

                </span>

            </div>

            <div class="staff-info-row">

                <span
                class="staff-info-label">

                    Created

                </span>

                <span
                class="staff-info-value">

                    <?= date(
                        'd M Y',
                        strtotime(
                            $admin['created_at']
                        )
                    ) ?>

                </span>

            </div>

        </div>

        <div class="staff-actions">

        <?php if ($isCurrentUser): ?>

        <a
        href="profile"
        class="
        staff-btn
        staff-btn-profile
        ">

            Profile

        </a>

        <?php endif; ?>

            <?php if (
                Auth::isSuperAdmin()
                &&
                !$isCurrentUser
                &&
                !$isSuperAdmin
            ): ?>

                <?php if (
                    $admin['status']
                    ===
                    'active'
                ): ?>

                <button
                type="button"
                class="
                staff-btn
                staff-btn-suspend
                staff-toggle-trigger
                "
                data-id="<?= $admin['id'] ?>"
                data-action="suspend"
                data-name="<?= htmlspecialchars(
                    $admin['name']
                ) ?>">

                    Suspend

                </button>

                <?php else: ?>

                <button
                type="button"
                class="
                staff-btn
                staff-btn-activate
                staff-toggle-trigger
                "
                data-id="<?= $admin['id'] ?>"
                data-action="activate"
                data-name="<?= htmlspecialchars(
                    $admin['name']
                ) ?>">

                    Activate

                </button>

                <?php endif; ?>

                <button
                type="button"
                class="
                staff-btn
                staff-btn-delete
                staff-delete-trigger
                "
                data-id="<?= $admin['id'] ?>"
                data-name="<?= htmlspecialchars(
                    $admin['name']
                ) ?>">

                    Delete

                </button>

            <?php endif; ?>

        </div>

    </div>

    <?php endforeach; ?>

    </div>

</div>

<div
id="staffDeleteModal"
class="staff-modal">

    <div
    class="staff-modal-content">

        <button
        type="button"
        id="staffDeleteClose"
        class="staff-modal-close">

            ×

        </button>

        <h2>

            Delete Administrator

        </h2>

        <p
        class="staff-modal-message">

            Are you sure you want to delete administrator

            <strong
            id="staffDeleteAdminName">

            </strong>

            ?

        </p>

        <p
        class="staff-modal-warning">

            This action permanently removes the administrator account.

        </p>

        <form
        id="staffDeleteForm">

            <input
            type="hidden"
            name="csrf_token"
            value="<?= Security::generateCsrfToken() ?>">

            <input
            type="hidden"
            name="id"
            id="staffDeleteId">

            <div
            class="staff-modal-actions">

                <button
                type="button"
                id="staffDeleteCancel"
                class="staff-modal-cancel">

                    Cancel

                </button>

                <button
                type="submit"
                id="staffDeleteConfirm"
                class="staff-modal-delete">

                    Delete

                </button>

            </div>

        </form>

    </div>

</div>

<div
id="staffToggleModal"
class="staff-modal">

    <div
    class="staff-modal-content">

        <button
        type="button"
        id="staffToggleClose"
        class="staff-modal-close">

            ×

        </button>

        <h2
        id="staffToggleTitle">

        </h2>


        <p
        class="staff-modal-message">

            Are you sure you want to

            <strong
            id="staffToggleAdminName">

            </strong>

            ?

        </p>


        <form
        id="staffToggleForm">


            <input
            type="hidden"
            name="csrf_token"
            value="<?= Security::generateCsrfToken() ?>">


            <input
            type="hidden"
            name="id"
            id="staffToggleId">


            <input
            type="hidden"
            name="action"
            id="staffToggleAction">


            <div
            class="staff-modal-actions">


                <button
                type="button"
                id="staffToggleCancel"
                class="staff-modal-cancel">

                    Cancel

                </button>


                <button
                type="submit"
                class="staff-modal-delete">

                    Confirm

                </button>


            </div>


        </form>

    </div>

</div>

<?php if (Auth::isSuperAdmin()): ?>

<div
id="adminCreateModal"
class="staff-modal">

    <div
    class="staff-modal-content">

        <button
        type="button"
        id="adminCreateClose"
        class="staff-modal-close">

            ×

        </button>

        <h2>

            Create Administrator

        </h2>

        <form
        id="adminCreateForm">

            <input
            type="hidden"
            name="csrf_token"
            value="<?= Security::generateCsrfToken() ?>">

            <div class="staff-form-group">

                <input
                type="text"
                name="name"
                placeholder="Full Name"
                required>

            </div>

            <div class="staff-form-group">

                <input
                type="email"
                name="email"
                placeholder="Email Address"
                required>

            </div>

            <div class="staff-form-group">

                <input
                type="text"
                name="phone"
                placeholder="Phone Number"
                required>

            </div>

            <div
            class="staff-modal-actions">

                <button
                type="button"
                id="adminCreateCancel"
                class="staff-modal-cancel">

                    Cancel

                </button>

                <button
                type="submit"
                id="adminCreateSubmit"
                class="staff-modal-delete">

                    Create Admin

                </button>

            </div>

        </form>

    </div>

</div>

<div
id="staffResponseModal"
class="staff-modal">

    <div
    class="staff-modal-content">

        <h2
        id="staffResponseTitle">

        </h2>

        <p
        id="staffResponseMessage">

        </p>

        <div
        class="staff-modal-actions">

            <button
            type="button"
            id="staffResponseOkay"
            class="staff-modal-cancel">

                Okay

            </button>

        </div>

    </div>

</div>

<?php endif; ?>

<?php

include
__DIR__ .
'/../includes/admin-footer.php';

?>