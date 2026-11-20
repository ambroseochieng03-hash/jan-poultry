<?php

require_once
__DIR__ .
'/../includes/init.php';

require_once
__DIR__ .
'/../app/services/AuthService.php';

$auth =
new AuthService();

$auth->logout();

header(
    'Location: login'
);

exit;