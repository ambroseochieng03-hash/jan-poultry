<?php

require_once
__DIR__ .
'/../includes/init.php';

require_once
__DIR__ .
'/../app/controllers/AuthController.php';

$controller =
new AuthController();

$controller->updateProfile();
