<?php
session_start();
// On détruit toutes les variables de session
session_unset();
session_destroy();

// On redirige vers login
header("Location: login.php");
exit();
?>