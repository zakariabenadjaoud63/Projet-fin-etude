<?php
session_start();
require_once 'includes/security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: panier.php');
    exit();
}

require_csrf();
unset($_SESSION['panier']);

header('Location: panier.php');
exit();
