<?php
header('Content-Type: text/html; charset=utf-8');
session_start();

//session törlése -> kijelentkeztetés
session_unset();
session_destroy();

header('Location: login.php');
exit();
?>