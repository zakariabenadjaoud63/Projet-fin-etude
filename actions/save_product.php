<?php
session_start();
require_once '../connexion_bd.php';
require_once '../includes/security.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php'); exit();
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../espace_admin.php#stock'); exit();
}

require_csrf();

$produit_id   = intval($_POST['produit_id']   ?? 0);
$nom          = trim($_POST['nom']            ?? '');
$description  = trim($_POST['description']    ?? '');
$prix         = max(0, floatval($_POST['prix'] ?? 0));
$en_stock     = max(0, intval($_POST['en_stock'] ?? 0));
$categorie_id = intval($_POST['categorie_id'] ?? 0);
$garage_id    = intval($_POST['garage_id']    ?? 1);

if (!$nom || $categorie_id <= 0) {
    header('Location: ../espace_admin.php?erreur=produit_invalide#stock'); exit();
}

/* ── Gestion upload image ─────────────────────────── */
$image = trim($_POST['image_actuelle'] ?? '');   // conserve l'ancienne si pas de nouvel upload

if (!empty($_FILES['image']['name'])) {
    $file     = $_FILES['image'];
    $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed  = ['jpg','jpeg','png','webp','gif'];

    if (!in_array($ext, $allowed)) {
        header('Location: ../espace_admin.php?erreur=image_format#stock'); exit();
    }
    if ($file['size'] > 3 * 1024 * 1024) {   // 3 Mo max
        header('Location: ../espace_admin.php?erreur=image_trop_grande#stock'); exit();
    }

    $upload_dir = __DIR__ . '/../image/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

    // Nom unique pour éviter les collisions
    $filename = uniqid('prod_') . '.' . $ext;
    $dest     = $upload_dir . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        header('Location: ../espace_admin.php?erreur=upload_echec#stock'); exit();
    }
    $image = $filename;
}

if (!$image) $image = 'default_produit.jpg';

/* ── INSERT ou UPDATE ─────────────────────────────── */
if ($produit_id > 0) {
    $stmt = $pdo->prepare("
        UPDATE produits
        SET nom=:nom, description=:desc, prix=:prix, en_stock=:stk,
            image=:img, categorie_id=:cat, garage_id=:gar
        WHERE id=:id
    ");
    $stmt->execute([
        ':nom'  => $nom,   ':desc' => $description ?: null,
        ':prix' => $prix,  ':stk'  => $en_stock,
        ':img'  => $image, ':cat'  => $categorie_id,
        ':gar'  => $garage_id ?: 1, ':id' => $produit_id,
    ]);
} else {
    $stmt = $pdo->prepare("
        INSERT INTO produits (categorie_id,garage_id,nom,description,prix,image,en_stock,cree_le)
        VALUES (:cat,:gar,:nom,:desc,:prix,:img,:stk,NOW())
    ");
    $stmt->execute([
        ':cat'  => $categorie_id, ':gar'  => $garage_id ?: 1,
        ':nom'  => $nom,          ':desc' => $description ?: null,
        ':prix' => $prix,         ':img'  => $image,
        ':stk'  => $en_stock,
    ]);
}

header('Location: ../espace_admin.php?ok=produit#stock'); exit();
