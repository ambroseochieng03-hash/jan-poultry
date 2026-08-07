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
        'Record is not deleted.'

    ]);

    exit;
}

$restore =
$db->prepare(

    "

    UPDATE users

    SET status = 'active'

    WHERE id = ?

    LIMIT 1

    "

);

$restore->execute([
    $id
]);

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

    'Recovered '
    .
    $user['role']
    .
    ': '
    .
    $user['email']

]);

echo json_encode([

    'success' => true,

    'message' =>
    'Account recovered successfully.'

]);

exit;