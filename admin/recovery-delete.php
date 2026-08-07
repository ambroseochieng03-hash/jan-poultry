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

$db =
(new Database())
->getConnection();

if (
    $_SERVER['REQUEST_METHOD']
    !==
    'POST'
) {

    echo json_encode([

        'success' => false,

        'message' =>
        'Invalid request method.'

    ]);

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

    echo json_encode([

        'success' => false,

        'message' =>
        'Invalid CSRF token.'

    ]);

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

        'message' =>
        'Invalid record.'

    ]);

    exit;
}

$stmt =
$db->prepare(

    "

    SELECT

        id,
        name,
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

$user =
$stmt->fetch();

if (!$user) {

    echo json_encode([

        'success' => false,

        'message' =>
        'Record not found.'

    ]);

    exit;
}

if (
    $user['status']
    !==
    'deleted'
) {

    echo json_encode([

        'success' => false,

        'message' =>
        'Only deleted accounts can be permanently removed.'

    ]);

    exit;
}

/*
|--------------------------------------------------------------------------
| Log before delete
|--------------------------------------------------------------------------
*/

$log =
$db->prepare(

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

    'Permanently deleted account: '
    .
    $user['email']

]);

/*
|--------------------------------------------------------------------------
| Permanent delete
|--------------------------------------------------------------------------
*/

$delete =
$db->prepare(

    "

    DELETE FROM users

    WHERE id = ?

    LIMIT 1

    "

);

$delete->execute([
    $id
]);

echo json_encode([

    'success' => true,

    'message' =>
    'Account permanently deleted.'

]);

exit;