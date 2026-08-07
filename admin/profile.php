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

$stmt =
$db->prepare(
    "

    SELECT *

    FROM users

    WHERE id = ?

    LIMIT 1

    "
);

$stmt->execute([
    Auth::userId()
]);

$user =
$stmt->fetch();

$pageCss = 'profile.css';

$pageJs = 'profile.js';

$csrfToken =
Security::generateCsrfToken();

include
__DIR__ .
'/../includes/admin-header.php';

$avatar =
strtoupper(
    substr(
        $user['name'],
        0,
        1
    )
);

?>

<div class="profile-page">

    <div class="profile-hero">

        <div class="profile-avatar">

            <?= htmlspecialchars($avatar) ?>

        </div>

        <div class="profile-hero-content">

            <h1>

                <?= htmlspecialchars(
                    $user['name']
                ) ?>

            </h1>

            <div class="profile-badges">

                <span
                class="
                profile-badge
                profile-role
                ">

                    <?= strtoupper(
                        str_replace(
                            '_',
                            ' ',
                            $user['role']
                        )
                    ) ?>

                </span>

                <span
                class="
                profile-badge
                <?= $user['status'] === 'active'
                    ? 'profile-status-active'
                    : 'profile-status-suspended'
                ?>
                ">

                    <?= strtoupper(
                        $user['status']
                    ) ?>

                </span>

            </div>

        </div>

    </div>

    <div class="profile-grid">

        <div class="profile-card">

            <h2>

                Account Information

            </h2>

            <div class="profile-info-row">

                <span>Name</span>

                <strong>

                    <?= htmlspecialchars(
                        $user['name']
                    ) ?>

                </strong>

            </div>

            <div class="profile-info-row">

                <span>Email</span>

                <strong>

                    <?= htmlspecialchars(
                        $user['email']
                    ) ?>

                </strong>

            </div>

            <div class="profile-info-row">

                <span>Phone</span>

                <strong>

                    <?= htmlspecialchars(
                        $user['phone']
                        ?? '—'
                    ) ?>

                </strong>

            </div>

            <div class="profile-info-row">

                <span>Role</span>

                <strong>

                    <?= strtoupper(
                        str_replace(
                            '_',
                            ' ',
                            $user['role']
                        )
                    ) ?>

                </strong>

            </div>

            <div class="profile-info-row">

                <span>Created</span>

                <strong>

                    <?= date(
                        'd M Y',
                        strtotime(
                            $user['created_at']
                        )
                    ) ?>

                </strong>

            </div>

            <div class="profile-info-row">

                <span>Last Login</span>

                <strong>

                    <?= $user['last_login']
                        ? date(
                            'd M Y H:i',
                            strtotime(
                                $user['last_login']
                            )
                        )
                        : 'Never'
                    ?>

                </strong>

            </div>

        </div>

        <div class="profile-action-buttons">

            <button
            type="button"
            id="openEditProfileModal"
            class="profile-btn profile-btn-primary">

                Edit Profile

            </button>

            <button
            type="button"
            id="openPasswordModal"
            class="profile-btn profile-btn-secondary">

                Change Password

            </button>

        </div>

    </div>

</div>

<div
id="editProfileModal"
class="profile-modal">

    <div class="profile-modal-content">

        <button
        type="button"
        class="profile-modal-close"
        data-close-modal>

            ×

        </button>

        <h2>

            Edit Profile

        </h2>

        <form
        id="editProfileForm"
        method="POST"
        action="profile-update.php">

            <input
            type="hidden"
            name="csrf_token"
            value="<?= $csrfToken ?>">

            <input
            type="hidden"
            id="originalEmail"
            value="<?= htmlspecialchars(
                $user['email']
            ) ?>">

            <label>Name</label>

            <input
            type="text"
            name="name"
            id="editName"
            value="<?= htmlspecialchars(
                $user['name']
            ) ?>"
            required>

            <label>Email</label>

            <input
            type="email"
            name="email"
            id="editEmail"
            value="<?= htmlspecialchars(
                $user['email']
            ) ?>"
            required>

            <label>Phone</label>

            <input
            type="text"
            name="phone"
            value="<?= htmlspecialchars(
                $user['phone']
                ?? ''
            ) ?>">

            <div class="profile-modal-actions">

                <button
                type="button"
                data-close-modal
                class="profile-btn-cancel">

                    Cancel

                </button>

                <button
                type="submit"
                class="profile-btn-save">

                    Update Profile

                </button>

            </div>

        </form>

    </div>

</div>

<div
id="emailVerificationModal"
class="profile-modal">

    <div class="profile-modal-content">

        <h2>

            Confirm Email Change

        </h2>

        <p>

            Enter your current password
            to change your email.

        </p>

        <input
        type="password"
        id="emailVerificationPassword">

        <label
        class="profile-checkbox">

            <input
            type="checkbox"
            id="showEmailPassword">

            Show Password

        </label>

        <div class="profile-modal-actions">

            <button
            type="button"
            id="cancelEmailVerification"
            class="profile-btn-cancel">

                Cancel

            </button>

            <button
            type="button"
            id="confirmEmailChange"
            class="profile-btn-save">

                Confirm Update

            </button>

        </div>

    </div>

</div>

<div
id="passwordModal"
class="profile-modal">

    <div class="profile-modal-content">

        <button
        type="button"
        class="profile-modal-close"
        data-close-modal>

            ×

        </button>

        <h2>

            Change Password

        </h2>

        <form
        method="POST"
        action="profile-password.php"
        id="changePasswordForm">

            <input
            type="hidden"
            name="csrf_token"
            value="<?= $csrfToken ?>">

            <label>

                Current Password

            </label>

            <input
            type="password"
            name="current_password"
            class="password-field"
            required>

            <label>

                New Password

            </label>

            <input
            type="password"
            name="new_password"
            class="password-field"
            required>

            <label>

                Confirm Password

            </label>

            <input
            type="password"
            name="confirm_password"
            class="password-field"
            required>

            <label
            class="profile-checkbox">

                <input
                type="checkbox"
                id="togglePasswords">

                Show Passwords

            </label>

            <div class="profile-modal-actions">

                <button
                type="button"
                data-close-modal
                class="profile-btn-cancel">

                    Cancel

                </button>

                <button
                type="submit"
                class="profile-btn-save">

                    Change Password

                </button>

            </div>

        </form>

    </div>

</div>

<div
id="profileMessageModal"
class="profile-modal">

    <div
    class="
    profile-modal-content
    profile-message-modal
    ">

        <h2
        id="profileMessageTitle">

        </h2>

        <p
        id="profileMessageText">

        </p>

        <button
        type="button"
        id="profileMessageOk"
        class="profile-btn-save">

            OK

        </button>

    </div>

</div>

<?php

include
__DIR__ .
'/../includes/admin-footer.php';

?>