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
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

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
        $_POST['county'],
        $_POST['town'],
        $_POST['address'],
        $_POST['phone']
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

    color: #d4af37;

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

    border-color: #d4af37;

}


.address-form-card button {

    padding: 14px;

    border: none;

    border-radius: 12px;

    background: #d4af37;

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

    <form method="POST" class="address-form-card">

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