<?php
require __DIR__.'/includes/bootstrap.php';
require __DIR__.'/includes/auth.php';
wz_logout();
header('Location: index.php');
exit;
