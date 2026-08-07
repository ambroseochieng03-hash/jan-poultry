<?php

require_once __DIR__ . '/../includes/init.php';

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

    echo json_encode([
        'success' => false,
        'message' => 'Invalid request.'
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
        'message' => 'Invalid CSRF token.'
    ]);

    exit;
}

$id =
(int)(
    $_POST['id']
    ?? 0
);

$action =
trim(
    $_POST['action']
    ?? ''
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

    echo json_encode([
        'success' => false,
        'message' => 'Administrator not found.'
    ]);

    exit;
}

$currentUserId =
Auth::userId();

if (
    $admin['id']
    ==
    $currentUserId
) {

    echo json_encode([
        'success' => false,
        'message' => 'You cannot modify your own account.'
    ]);

    exit;
}

if (
    $admin['role']
    ===
    'super_admin'
) {

    echo json_encode([
        'success' => false,
        'message' => 'Super Admin accounts cannot be modified.'
    ]);

    exit;
}

$newStatus = null;

if (
    $action === 'suspend'
) {

    $newStatus =
    'suspended';
}

if (
    $action === 'activate'
) {

    $newStatus =
    'active';
}

if (!$newStatus) {

    echo json_encode([
        'success' => false,
        'message' => 'Invalid action.'
    ]);

    exit;
}

$update =
$pdo->prepare(

    "

    UPDATE users

    SET status = ?

    WHERE id = ?

    LIMIT 1

    "

);

$update->execute([

    $newStatus,

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

    ucfirst($action)
    .
    ' admin: '
    .
    $admin['email']

]);

echo json_encode([

    'success' => true,

    'message' =>
    $action === 'suspend'
        ? 'Administrator suspended successfully.'
        : 'Administrator activated successfully.'

]);

exit;
