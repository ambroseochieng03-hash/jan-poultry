<?php

require_once
__DIR__ .
'/../includes/init.php';

require_once
__DIR__ .
'/../app/helpers/Auth.php';

Auth::requireLogin();

$pageCss =
'customer-profile.css';

$pageJs =
'customer-profile.js';

include
__DIR__ .
'/../includes/customer-header.php';

$db =
(new Database())
->getConnection();

$isGuest = Auth::isGuest();

if ($isGuest) {

    $user = [

        'name' =>
            $_SESSION['guest_name'],

        'phone' =>
            $_SESSION['guest_phone'],

        'email' =>
            'Guest Account',

        'role' =>
            'guest',

        'status' =>
            'active',

        'created_at' =>
            date('Y-m-d')
    ];

} else {

    $userId =
    Auth::userId();

    $stmt =
    $db->prepare("
        SELECT *
        FROM users
        WHERE id = ?
    ");

    $stmt->execute([
        $userId
    ]);

    $user =
    $stmt->fetch();
}

?>

<div class="profile-page">

<?php

$appModal = null;

if (
    isset($_SESSION['profile_message'])
) {

    $appModal = [

        'icon' => '✓',

        'title' => 'Success',

        'message' =>
        $_SESSION['profile_message']

    ];

    unset(
        $_SESSION['profile_message']
    );

}

if (
    isset($_SESSION['profile_error'])
) {

    $appModal = [

        'icon' => '✕',

        'title' => 'Error',

        'message' =>
        $_SESSION['profile_error']

    ];

    unset(
        $_SESSION['profile_error']
    );

}

?>

<h1 style="margin-bottom:25px;">
    My Profile
</h1>

<div class="profile-card">

    <div class="profile-top">

        <div class="profile-avatar">

            <?= strtoupper(
                substr(
                    $user['name'],
                    0,
                    1
                )
            ) ?>

        </div>

        <div>

            <div class="profile-name">

                <?= htmlspecialchars(
                    $user['name']
                ) ?>

            </div>

            <span class="profile-badge">

                <?= $isGuest
                    ? 'Guest Account'
                    : 'Registered Customer'
                ?>

            </span>

        </div>

    </div>

    <div class="profile-grid">

        <div class="info-card">

            <div class="info-label">
                Name
            </div>

            <div class="info-value">
                <?= htmlspecialchars($user['name']) ?>
            </div>

        </div>

        <div class="info-card">

            <div class="info-label">
                Phone
            </div>

            <div class="info-value">
                <?= htmlspecialchars($user['phone']) ?>
            </div>

        </div>

        <div class="info-card">

            <div class="info-label">
                Email
            </div>

            <div class="info-value">
                <?= htmlspecialchars($user['email']) ?>
            </div>

        </div>

        <div class="info-card">

            <div class="info-label">
                Account Type
            </div>

            <div class="info-value">

                <?= ucfirst(
                    $user['role']
                ) ?>

            </div>

        </div>

        <div class="info-card">

            <div class="info-label">
                Status
            </div>

            <div class="info-value">

                <?= ucfirst(
                    $user['status']
                ) ?>

            </div>

        </div>

    </div>

    <div class="profile-actions">

        <?php if (!$isGuest): ?>

            <button
            onclick="openEditProfile()"
            class="profile-btn primary-btn">

            Edit Profile

            </button>


            <button
            onclick="openPasswordChange()"
            class="profile-btn secondary-btn"
            style="color: black;">

            Change Password

            </button>

        <?php else: ?>

            <a
            href="/register"
            class="profile-btn primary-btn">

                Create Account

            </a>

        <?php endif; ?>

    </div>

</div>

</div>

<div id="editProfileModal" class="modal">

    <div class="modal-box">

        <h2>Edit Profile</h2>

        <form method="POST" action="edit-profile">

            <input
            type="hidden"
            name="csrf_token"
            value="<?= Security::generateCsrfToken() ?>">

            <input
            type="text"
            name="name"
            value="<?= htmlspecialchars($user['name']) ?>"
            required>


            <input
            type="email"
            name="email"
            value="<?= htmlspecialchars($user['email']) ?>"
            required>


            <input
            type="text"
            name="phone"
            value="<?= htmlspecialchars($user['phone']) ?>"
            required>


            <button class="profile-btn primary-btn">
                Save Changes
            </button>

        </form>


        <button onclick="closeEditProfile()" class="profile-btn primary-btn">
            Cancel
        </button>

    </div>

</div>



<div id="passwordModal" class="modal">

    <div class="modal-box">

        <h2>Change Password</h2>


        <form method="POST" action="change-password">

            <input
            type="hidden"
            name="csrf_token"
            value="<?= Security::generateCsrfToken() ?>">

            <input
            type="password"
            name="current_password"
            placeholder="Current Password"
            required>


            <input
            type="password"
            name="new_password"
            placeholder="New Password"
            required>


            <button class="profile-btn primary-btn">
                Update Password
            </button>


        </form>


        <button class="profile-btn primary-btn" onclick="closePasswordChange()">
            Cancel
        </button>


    </div>

</div>

<?php if ($appModal): ?>

<script>

document.addEventListener(

    "DOMContentLoaded",

    function () {

        showAppResponseModal({

            icon:
            <?= json_encode($appModal['icon']) ?>,

            title:
            <?= json_encode($appModal['title']) ?>,

            message:
            <?= json_encode($appModal['message']) ?>,

            autoClose: 2000

        });

    }

);

</script>

<?php endif; ?>

<?php include __DIR__ . '/../includes/customer-footer.php'; ?>