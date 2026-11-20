<?php

require_once
__DIR__ .
'/../includes/init.php';

require_once
__DIR__ .
'/../app/helpers/Auth.php';

Auth::requireSuperAdmin();

header(
    'Content-Type: application/json'
);

if (
    $_SERVER['REQUEST_METHOD']
    !==
    'POST'
) {

    header(
        'Location: admins'
    );

    exit;
}

$csrfToken =
trim(
    $_POST['csrf_token']
    ?? ''
);

if (
    !Security::verifyCsrfToken(
        $csrfToken
    )
) {

    header(
        'Location: admins'
    );

    exit;
}

$id =
(int)(
    $_POST['id']
    ?? 0
);

if ($id <= 0) {

    echo json_encode([
        'success' => false,
        'message' => 'Invalid administrator.'
    ]);

    exit;
}

$stmt =
$pdo->prepare(

    "

    SELECT

        id,
        email,
        role,
        status

    FROM users

    WHERE id = ?

    LIMIT 1

    "

);

$stmt->execute([
    $id
]);

$admin =
$stmt->fetch();

if (!$admin) {

    header(
        'Location: admins'
    );

    exit;
}

if (
    $admin['status']
    ===
    'deleted'
) {

    echo json_encode([
        'success' => false,
        'message' => 'Administrator already deleted.'
    ]);

    exit;
}

if (
    $admin['id']
    ==
    Auth::userId()
) {

    header(
        'Location: admins'
    );

    exit;
}

if (
    $admin['role']
    ===
    'super_admin'
) {

    header(
        'Location: admins'
    );

    exit;
}

$delete =
$pdo->prepare(

    "

    UPDATE users

    SET status = 'deleted'

    WHERE id = ?

    LIMIT 1

    "

);

$delete->execute([
    $id
]);

$log =
$pdo->prepare(

    "

    INSERT INTO admin_logs

    (
        admin_id,
        action
    )

    VALUES

    (
        ?,
        ?
    )

    "

);

$log->execute([

    Auth::userId(),

    'Deleted admin: ' .
    $admin['email']

]);

echo json_encode([

    'success' => true,

    'message' =>
    'Administrator deleted successfully.'

]);

exit;
