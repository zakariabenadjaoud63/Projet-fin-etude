<?php
session_start();
require_once '../connexion_bd.php';
require_once '../includes/security.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../espace_admin.php#admin-stock');
    exit();
}

require_csrf();

$produit_id = intval($_POST['produit_id'] ?? 0);
if ($produit_id > 0) {
    $stmt = $pdo->prepare("DELETE FROM produits WHERE id = :id");
    $stmt->execute([':id' => $produit_id]);
}

header('Location: ../espace_admin.php?ok=produit_supprime#admin-stock');
exit();
