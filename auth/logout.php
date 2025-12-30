<?php
session_start();
session_destroy();
header('Location: /pms_hotel/auth/login.php');
exit;
?>
