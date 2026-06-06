<?php
session_start();
require_once 'includes/security.php';
require_once 'connexion_bd.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: vehicule.php');
    exit();
}
require_csrf();

$id_proprietaire = $_SESSION['user_id'];
$marque          = trim($_POST['marque'] ?? '');
$modele          = trim($_POST['modele'] ?? '');
$immatriculation = trim($_POST['immatriculation'] ?? '');
$annee           = !empty($_POST['annee']) ? intval($_POST['annee']) : null;

if (empty($marque) || empty($modele) || empty($immatriculation)) {
    header('Location: vehicule.php?erreur=champs_vides');
    exit();
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO vehicules (id_proprietaire, immatriculation, marque, modele, annee)
        VALUES (:id_proprietaire, :immatriculation, :marque, :modele, :annee)
    ");
    $stmt->execute([
        ':id_proprietaire' => $id_proprietaire,
        ':immatriculation' => $immatriculation,
        ':marque'          => $marque,
        ':modele'          => $modele,
        ':annee'           => $annee,
    ]);

    header('Location: vehicule.php?success=1');
    exit();

} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        header('Location: vehicule.php?erreur=immat_existe');
    } else {
        die("Erreur véhicule : " . $e->getMessage());
    }
    exit();
}
?>
