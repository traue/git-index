<?php
declare(strict_types=1);

require __DIR__ . '/_bootstrap.php';

Auth::logout();
header('Location: login.php');
exit;
