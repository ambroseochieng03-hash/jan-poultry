<?php

require_once
__DIR__ .
'/../includes/init.php';

require_once
__DIR__ .
'/../app/helpers/Auth.php';

Auth::requireLogin();

if (Auth::isGuest()) {

    header(
        "Location: /dashboard"
    );

    exit;
}

include
__DIR__ .
'/../includes/customer-header.php';

$db =
(new Database())
->getConnection();

$userId =
Auth::userId();

/* Add address */
$addressError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $county  = trim((string) ($_POST['county'] ?? ''));
    $town    = trim((string) ($_POST['town'] ?? ''));
    $address = trim((string) ($_POST['address'] ?? ''));
    $phone   = trim((string) ($_POST['phone'] ?? ''));

    if (!Security::checkPostRequest()) {

        $addressError = 'Your session expired. Please reload the page and try again.';

    } elseif (
        $county === '' || mb_strlen($county) > 100
        || $town === '' || mb_strlen($town) > 100
        || $address === '' || mb_strlen($address) > 500
        || preg_match('/^[0-9+\\-\\s]{7,20}$/', $phone) !== 1
    ) {

        $addressError = 'Please fill in every field correctly (phone: 7-20 digits).';

    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $addressError === '') {

    $stmt =
    $db->prepare("
        INSERT INTO addresses
        (
            user_id,
            county,
            town,
            address,
            phone
        )
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $userId,
        $county,
        $town,
        $address,
        $phone
    ]);
}

/* Fetch addresses */
$stmt =
$db->prepare("
    SELECT *
    FROM addresses
    WHERE user_id = ?
    ORDER BY id DESC
");

$stmt->execute([$userId]);

$addresses =
$stmt->fetchAll();

?>

<style>

.address-page {

    max-width: 800px;

    margin: auto;

    padding: 20px;
}


.address-page h1 {

    margin-bottom: 25px;

    color: #fbbf0f;

}


.address-form-card {

    background:
    rgba(255,255,255,.04);

    border:
    1px solid rgba(255,255,255,.08);

    border-radius: 20px;

    padding: 30px;

    backdrop-filter:
    blur(12px);

    display: flex;

    flex-direction: column;

    gap: 15px;
}


.address-form-card input,
.address-form-card textarea {

    width: 100%;

    padding: 14px;

    border-radius: 12px;

    border:
    1px solid rgba(255,255,255,.08);

    background:
    rgba(255,255,255,.04);

    color: white;

}


.address-form-card textarea {

    min-height: 120px;

    resize: vertical;

}


.address-form-card input:focus,
.address-form-card textarea:focus {

    outline: none;

    border-color: #fbbf0f;

}


.address-form-card button {

    padding: 14px;

    border: none;

    border-radius: 12px;

    background: #fbbf0f;

    color: #111;

    font-weight: 700;

    cursor: pointer;

    transition: .3s;
}


.address-form-card button:hover {

    transform:
    translateY(-2px);

}


@media(max-width:600px){

    .address-page {

        padding: 10px;

    }


    .address-form-card {

        padding: 20px;

        border-radius: 16px;

    }

}

</style>

<div class="address-page">

    <h1>My Addresses</h1>

    <?php if ($addressError !== ''): ?>
        <p class="address-error" role="alert" style="color:#b3261e;font-weight:bold;">
            <?= htmlspecialchars($addressError) ?>
        </p>
    <?php endif; ?>

    <form method="POST" class="address-form-card">

        <input type="hidden" name="csrf_token" value="<?= Security::generateCsrfToken() ?>">

        <input name="county" placeholder="County" required>
        <input name="town" placeholder="Town" required>
        <textarea name="address" placeholder="Full Address" required></textarea>
        <input name="phone" placeholder="Phone" required>

        <button type="submit">Add Address</button>

    </form>

    <div class="list">

        <?php foreach ($addresses as $a): ?>

            <div class="card">

                <p><strong><?= htmlspecialchars($a['county']) ?></strong></p>
                <p><?= htmlspecialchars($a['town']) ?></p>
                <p><?= htmlspecialchars($a['address']) ?></p>
                <p><?= htmlspecialchars($a['phone']) ?></p>

            </div>

        <?php endforeach; ?>

    </div>

</div>

<?php include __DIR__ . '/../includes/customer-footer.php'; ?>