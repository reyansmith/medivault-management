<?php
session_start();
session_destroy();
header("Location: mlogin.php");
exit();
?>