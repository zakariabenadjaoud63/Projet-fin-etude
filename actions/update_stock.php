<?php
session_start();
require_once '../connexion_bd.php';
require_once '../includes/security.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') { header('Location: ../login.php'); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../espace_admin.php#admin-stock'); exit(); }
require_csrf();

$produit_id = intval($_POST['produit_id'] ?? 0);
$en_stock   = max(0, intval($_POST['en_stock'] ?? 0));

if ($produit_id > 0) {
    $stmt = $pdo->prepare("UPDATE produits SET en_stock = :stock WHERE id = :id");
    $stmt->execute([':stock' => $en_stock, ':id' => $produit_id]);
}

header('Location: ../espace_admin.php#admin-stock');
exit();
