<?php

require_once
__DIR__ .
'/../includes/init.php';

require_once
__DIR__ .
'/../app/helpers/Auth.php';

Auth::requireSuperAdmin();

$db =
(new Database())
->getConnection();

if (
    $_SERVER['REQUEST_METHOD']
    !==
    'POST'
) {

    header(
        'Location: users.php'
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
        'Location: users.php'
    );

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

    header("Location: users.php");
    exit;
}

$stmt =
$db->prepare("
    SELECT id, role
    FROM users
    WHERE id = ?
    LIMIT 1
");

$stmt->execute([$id]);

$user = $stmt->fetch();

if (!$user) {
    header("Location: users.php");
    exit;
}

/* prevent self-action */
if ($id == Auth::userId()) {
    header("Location: users.php");
    exit;
}

/* block admin modification */
if (in_array($user['role'], ['admin', 'super_admin'])) {
    header("Location: users.php");
    exit;
}

$newStatus = null;

if ($action === 'suspend') {
    $newStatus = 'suspended';
}

if ($action === 'activate') {
    $newStatus = 'active';
}

if (!$newStatus) {
    header("Location: users.php");
    exit;
}

$update =
$db->prepare("
    UPDATE users
    SET status = ?
    WHERE id = ?
    LIMIT 1
");

$update->execute([$newStatus, $id]);

/* log */
$log =
$db->prepare("
    INSERT INTO admin_logs (admin_id, action)
    VALUES (?, ?)
");

$log->execute([

    Auth::userId(),

    ucfirst($action)
    .
    ' customer ID '
    .
    $id

]);

header(
    'Content-Type: application/json'
);

echo json_encode([

    'success' => true,

    'message' =>
    'Customer status updated successfully.'

]);

exit;
