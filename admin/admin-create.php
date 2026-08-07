<?php

require_once
__DIR__ .
'/../includes/init.php';

require_once
__DIR__ .
'/../app/controllers/AdminController.php';

$controller =
new AdminController();

$controller->createAdmin();