<?php
session_start();
require_once 'includes/security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: panier.php');
    exit();
}

require_csrf();

$produit_id = intval($_POST['produit_id'] ?? 0);
$quantite = max(0, intval($_POST['quantite'] ?? 0));

if ($produit_id > 0 && isset($_SESSION['panier'][$produit_id])) {
    if ($quantite === 0) {
        unset($_SESSION['panier'][$produit_id]);
    } else {
        $_SESSION['panier'][$produit_id] = $quantite;
    }
}

if (empty($_SESSION['panier'])) {
    unset($_SESSION['panier']);
}

header('Location: panier.php');
exit();
