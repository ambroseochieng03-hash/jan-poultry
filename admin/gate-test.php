<?php

require_once
__DIR__ .
'/../app/helpers/AdminGate.php';


AdminGate::requireGate();


echo "GATE OPEN";
