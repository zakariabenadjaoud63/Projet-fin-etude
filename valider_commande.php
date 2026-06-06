<?php
session_start();
require_once 'connexion_bd.php';
require_once 'includes/security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: panier.php');
    exit();
}
require_csrf();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?erreur=connexion_requise');
    exit();
}
if (($_SESSION['role'] ?? 'client') !== 'client') {
    if ($_SESSION['role'] === 'mecano') {
        header('Location: espace_mecano.php');
        exit();
    }
    if ($_SESSION['role'] === 'admin') {
        header('Location: espace_admin.php');
        exit();
    }
}

if (empty($_SESSION['panier'])) {
    header('Location: panier.php?erreur=panier_vide');
    exit();
}

$id_client = $_SESSION['user_id'];
$total_general = 0;
$produits_commandes = [];

try {
    $pdo->beginTransaction();

    $ids = array_keys($_SESSION['panier']);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare("SELECT * FROM produits WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    $liste_produits = $stmt->fetchAll();

    foreach ($liste_produits as $produit) {
        $quantite = $_SESSION['panier'][$produit['id']];
        $total_general += $produit['prix'] * $quantite;
        $produits_commandes[] = [
            'id' => $produit['id'],
            'prix' => $produit['prix'],
            'quantite' => $quantite
        ];
    }

    $stmt_comm = $pdo->prepare("
        INSERT INTO commandes (utilisateur_id, total, statut, cree_le)
        VALUES (:user_id, :total, 'en_attente', NOW())
    ");
    $stmt_comm->execute([
        ':user_id' => $id_client,
        ':total' => $total_general
    ]);

    $id_commande = $pdo->lastInsertId();

    $stmt_ligne = $pdo->prepare("
        INSERT INTO commande_lignes (commande_id, produit_id, quantite, prix_unitaire)
        VALUES (:commande_id, :produit_id, :quantite, :prix)
    ");

    foreach ($produits_commandes as $item) {
        $stmt_ligne->execute([
            ':commande_id' => $id_commande,
            ':produit_id' => $item['id'],
            ':quantite' => $item['quantite'],
            ':prix' => $item['prix']
        ]);
    }

    $pdo->commit();
    unset($_SESSION['panier']);

    $methode = trim($_POST['methode_paiement'] ?? 'especes');
    header('Location: confirmation_commande.php?id=' . urlencode($id_commande) . '&methode=' . urlencode($methode));
    exit();
} catch (PDOException $e) {
    $pdo->rollBack();
    die("Erreur lors de la validation de la commande : " . $e->getMessage());
}
