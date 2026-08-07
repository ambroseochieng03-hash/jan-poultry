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

$search =
trim(
    $_GET['search']
    ?? ''
);

$status =
$_GET['status']
?? '';

$sql =

"

SELECT

    id,
    name,
    email,
    phone,
    role,
    status,
    created_at,
    last_login

FROM users

WHERE role = 'customer'
AND status != 'deleted'

";

$params = [];

if ($search !== '') {

    $sql .=

    "

    AND
    (
        name LIKE ?
        OR email LIKE ?
        OR phone LIKE ?
    )

    ";

    $like =
    "%{$search}%";

    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

if ($status !== '') {

    $sql .=

    "

    AND status = ?

    ";

    $params[] =
    $status;
}

$sql .=

"

ORDER BY id DESC

";

$stmt =
$db->prepare($sql);

$stmt->execute(
    $params
);

$users =
$stmt->fetchAll();

$pageCss =
'users.css';

$pageJs =
'users.js';

include
__DIR__ .
'/../includes/admin-header.php';

?>

<div class="admin-page">

    <div class="page-header">

        <div>

            <h1>

                Users Management

            </h1>

            <p>

                Customers, Staff & Delivery Personnel

            </p>

        </div>

    </div>

    <div class="filter-card">

        <form method="GET">

            <input
            type="text"
            name="search"
            placeholder="Search user..."
            value="<?= htmlspecialchars($search) ?>">

            <select name="status">

                <option value="">

                    All Status

                </option>

                <option
                value="active"
                <?= $status === 'active' ? 'selected' : '' ?>>

                    Active

                </option>

                <option
                value="suspended"
                <?= $status === 'suspended' ? 'selected' : '' ?>>

                    Suspended

                </option>

            </select>

            <button
            type="submit"
            class="users-search-btn">

                Search

            </button>

        </form>

    </div>

    <div class="users-grid">

    <?php if (empty($users)): ?>

        <div class="users-empty">

            No users found.

        </div>

    <?php endif; ?>

    <?php foreach ($users as $user): ?>

    <?php

    $avatar =
    strtoupper(
        substr(
            trim($user['name']),
            0,
            1
        )
    );

    ?>

    <div class="user-card">

        <div class="user-avatar">

            <?= htmlspecialchars($avatar) ?>

        </div>

        <div class="user-name">

            <?= htmlspecialchars($user['name']) ?>

        </div>

        <div class="user-email">

            <?= htmlspecialchars($user['email']) ?>

        </div>

        <div class="user-phone">

            <?= htmlspecialchars(
                $user['phone']
                ?? '—'
            ) ?>

        </div>

        <div class="user-badges">

            <span class="user-badge-role">

                <?= ucfirst(
                    $user['role']
                ) ?>

            </span>

            <?php if (
                $user['status']
                === 'active'
            ): ?>

            <span class="user-badge-active">

                Active

            </span>

            <?php else: ?>

            <span class="user-badge-suspended">

                Suspended

            </span>

            <?php endif; ?>

        </div>

        <div class="user-meta">

            <div>

                Created:

                <?= date(
                    'd M Y',
                    strtotime(
                        $user['created_at']
                    )
                ) ?>

            </div>

            <div>

                Last Login:

                <?= htmlspecialchars(
                    $user['last_login']
                    ?? 'Never'
                ) ?>

            </div>

        </div>

        <div class="user-actions">

            <button
            type="button"
            class="
            user-btn
            user-btn-view
            user-view-trigger
            "
            data-name="<?= htmlspecialchars($user['name']) ?>"
            data-email="<?= htmlspecialchars($user['email']) ?>"
            data-phone="<?= htmlspecialchars($user['phone'] ?? 'N/A') ?>"
            data-status="<?= htmlspecialchars($user['status']) ?>"
            data-created="<?= date(
                'd M Y',
                strtotime($user['created_at'])
            ) ?>"
            data-last-login="<?= htmlspecialchars(
                $user['last_login']
                ?? 'Never'
            ) ?>">

                View

            </button>

            <?php if (
                Auth::isSuperAdmin()
            ): ?>

                <?php if (
                    $user['status']
                    === 'active'
                ): ?>

                <button
                type="button"
                class="
                user-btn
                user-btn-suspend
                user-toggle-trigger
                "
                data-id="<?= $user['id'] ?>"
                data-action="suspend"
                data-name="<?= htmlspecialchars(
                    $user['name']
                ) ?>">
                    Suspend
                </button>

                <?php else: ?>

                <button
                type="button"
                class="
                user-btn
                user-btn-activate
                user-toggle-trigger
                "
                data-id="<?= $user['id'] ?>"
                data-action="activate"
                data-name="<?= htmlspecialchars(
                    $user['name']
                ) ?>">
                    Activate
                </button>

                <?php endif; ?>

                <button
                type="button"
                class="
                user-btn
                user-btn-delete
                user-delete-trigger
                "
                data-id="<?= $user['id'] ?>"
                data-name="<?= htmlspecialchars(
                    $user['name']
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
id="userViewModal"
class="staff-modal">

    <div
    class="staff-modal-content">

        <button
        type="button"
        id="userViewClose"
        class="staff-modal-close">

            ×

        </button>

        <h2>

            Customer Details

        </h2>

        <div class="user-view-content">

            <div>

                <strong>Name:</strong>

                <span id="viewUserName"></span>

            </div>

            <div>

                <strong>Email:</strong>

                <span id="viewUserEmail"></span>

            </div>

            <div>

                <strong>Phone:</strong>

                <span id="viewUserPhone"></span>

            </div>

            <div>

                <strong>Status:</strong>

                <span id="viewUserStatus"></span>

            </div>

            <div>

                <strong>Created:</strong>

                <span id="viewUserCreated"></span>

            </div>

            <div>

                <strong>Last Login:</strong>

                <span id="viewUserLastLogin"></span>

            </div>

        </div>

        <div
        class="staff-modal-actions">

            <a
            href="#"
            id="userCallButton"
            class="user-btn user-btn-activate">

                Call

            </a>

            <a
            href="#"
            id="userEmailButton"
            class="user-btn user-btn-view">

                Email

            </a>

            <button
            type="button"
            id="userViewOkay"
            class="staff-modal-cancel">

                Close

            </button>

        </div>

    </div>

</div>

<div
id="userToggleModal"
class="staff-modal">

    <div
    class="staff-modal-content">

        <button
        type="button"
        id="userToggleClose"
        class="staff-modal-close">

            ×

        </button>

        <h2
        id="userToggleTitle">

        </h2>

        <p
        class="staff-modal-message">

            Are you sure you want to

            <strong
            id="userToggleName">

            </strong>

            ?

        </p>

        <form
        id="userToggleForm">

            <input
            type="hidden"
            name="csrf_token"
            value="<?= Security::generateCsrfToken() ?>">

            <input
            type="hidden"
            name="id"
            id="userToggleId">

            <input
            type="hidden"
            name="action"
            id="userToggleAction">

            <div
            class="staff-modal-actions">

                <button
                type="button"
                id="userToggleCancel"
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

<div
id="userDeleteModal"
class="staff-modal">

    <div
    class="staff-modal-content">

        <button
        type="button"
        id="userDeleteClose"
        class="staff-modal-close">

            ×

        </button>

        <h2>

            Delete Customer

        </h2>

        <p
        class="staff-modal-message">

            Are you sure you want to delete

            <strong
            id="userDeleteName">

            </strong>

            ?

        </p>

        <form
        id="userDeleteForm">

            <input
            type="hidden"
            name="csrf_token"
            value="<?= Security::generateCsrfToken() ?>">

            <input
            type="hidden"
            name="id"
            id="userDeleteId">

            <div
            class="staff-modal-actions">

                <button
                type="button"
                id="userDeleteCancel"
                class="staff-modal-cancel">

                    Cancel

                </button>

                <button
                type="submit"
                class="staff-modal-delete">

                    Delete

                </button>

            </div>

        </form>

    </div>

</div>

<div
id="userResponseModal"
class="staff-modal">

    <div
    class="staff-modal-content">

        <h2
        id="userResponseTitle">

        </h2>

        <p
        id="userResponseMessage">

        </p>

        <div
        class="staff-modal-actions">

            <button
            type="button"
            id="userResponseOkay"
            class="staff-modal-cancel">

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
