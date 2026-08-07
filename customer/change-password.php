<?php

require_once
__DIR__ .
'/../includes/init.php';

require_once
__DIR__ .
'/../app/helpers/Auth.php';


Auth::requireLogin();


if(Auth::isGuest()){

header(
"Location: /jan-poultry/customer/profile.php"
);

exit;

}


$db =
(new Database())
->getConnection();



$userId =
Auth::userId();



$stmt =
$db->prepare(
"SELECT password_hash
 FROM users
 WHERE id=?"
);


$stmt->execute([
$userId
]);


$user =
$stmt->fetch();


if($_SERVER['REQUEST_METHOD']==='POST'){

if (

    !Security::verifyCsrfToken(

        $_POST['csrf_token']
        ??
        ''

    )

) {

    $_SESSION['profile_error'] =

        "Your session has expired. Please refresh the page and try again.";

    header(
        "Location: /jan-poultry/customer/profile.php"
    );

    exit;

}


$current =
$_POST['current_password'];


$new =
$_POST['new_password'];

if ($current === $new) {

    $_SESSION['profile_error'] =
    "Your new password must be different from your current password.";

    header(
        "Location: /jan-poultry/customer/profile.php"
    );

    exit;

}


if(
password_verify(
$current,
$user['password_hash']
)
){


$hash =
password_hash(
$new,
PASSWORD_ARGON2ID
);



try {

    $update =
    $db->prepare(

        "

        UPDATE users

        SET password_hash = ?

        WHERE id = ?

        "

    );

    $update->execute([

        $hash,

        $userId

    ]);

    $_SESSION['profile_message'] =

        "Password updated successfully.";

} catch (PDOException $e) {

    throw $e;

}

    header(
        "Location: /jan-poultry/customer/profile.php"
    );

    exit;



}else{

$_SESSION['profile_error'] =
"Current password is incorrect.";

header(
    "Location: /jan-poultry/customer/profile.php"
);

exit;

}



}

?>