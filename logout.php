<?php
require_once 'config.php';
unset($_SESSION['user_logged_in'], $_SESSION['user_id'], $_SESSION['user_nama'], $_SESSION['user_email'], $_SESSION['user_hp']);
header("Location: index.php");
exit();
?>
