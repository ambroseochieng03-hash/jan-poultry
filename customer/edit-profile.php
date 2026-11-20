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
        "Location: /profile"
    );

    exit;
}

$db =
(new Database())
->getConnection();


$userId =
Auth::userId();


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

            "Location: /profile"

        );

        exit;

    }

    $name =
    trim($_POST['name']);


    $email =
    trim($_POST['email']);


    $phone =
    trim($_POST['phone']);

    $check =
    $db->prepare(

        "

        SELECT id

        FROM users

        WHERE email = ?

        AND id != ?

        LIMIT 1

        "

    );

    $check->execute([

        $email,

        $userId

    ]);

    if (

        $check->fetch()

    ) {

        $_SESSION['profile_error'] =

            "That email address is already registered.";

        header(

            "Location: /profile"

        );

        exit;

    }

    try {

        $update =
        $db->prepare(

            "

            UPDATE users

            SET

                name = ?,

                email = ?,

                phone = ?

            WHERE id = ?

            "

        );

        $update->execute([

            $name,

            $email,

            $phone,

            $userId

        ]);

        $_SESSION['name'] =
            $name;

        $_SESSION['profile_message'] =
            "Profile updated successfully.";

    } catch (PDOException $e) {

        if (

            $e->getCode() === '23000'

        ) {

            $_SESSION['profile_error'] =

                "That email address is already registered.";

        } else {

            throw $e;

        }

    }

    header(

        "Location: /profile"

    );

    exit;


}



?>
