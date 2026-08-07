<?php

declare(strict_types=1);

require_once
__DIR__ .
'/../includes/init.php';

require_once
__DIR__ .
'/../app/services/AuthService.php';

$auth = new AuthService();

$auth->logout();

header(
    'Location: /jan-poultry/auth/login.php'
);

exit;