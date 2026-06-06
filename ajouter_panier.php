<?php
session_start();
require_once 'includes/security.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['produit_id'])) {
    require_csrf();
    $produit_id = intval($_POST['produit_id']);
    $quantite = 1;

    if (!isset($_SESSION['panier'])) {
        $_SESSION['panier'] = [];
    }

    if (isset($_SESSION['panier'][$produit_id])) {
        $_SESSION['panier'][$produit_id] += $quantite;
    } else {
        $_SESSION['panier'][$produit_id] = $quantite;
    }

    header("Location: boutique.php?panier=ajoute");
    exit();
}

header("Location: boutique.php");
exit();
