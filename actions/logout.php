<?php
session_start();
// Include database connection
require_once('../db/config.php');
session_unset();
session_destroy();
header("Location: ../view/Login.php");
exit();
?>
