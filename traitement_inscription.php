<?php
session_start();
require_once 'connexion_bd.php';
require_once 'includes/security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: inscription.php');
    exit();
}
require_csrf();

$nom          = trim($_POST['nom'] ?? '');
$email        = trim($_POST['email'] ?? '');
$telephone    = trim($_POST['telephone'] ?? '');
$mot_de_passe = $_POST['mot_de_passe'] ?? '';
$role = 'client';

if (empty($nom) || empty($email) || empty($mot_de_passe)) {
    header('Location: inscription.php?erreur=champs_vides');
    exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: inscription.php?erreur=email_invalide');
    exit();
}

try {
    $verif = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = :email LIMIT 1");
    $verif->execute([':email' => $email]);
    if ($verif->fetch()) {
        header('Location: inscription.php?erreur=email_existe');
        exit();
    }

    $hash = password_hash($mot_de_passe, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        INSERT INTO utilisateurs (nom, email, telephone, garages_id, mot_de_passe, role, cree_le)
        VALUES (:nom, :email, :telephone, NULL, :mot_de_passe, :role, NOW())
    ");
    $stmt->execute([
        ':nom'         => $nom,
        ':email'       => $email,
        ':telephone'   => $telephone ?: null,
        ':mot_de_passe'=> $hash,
        ':role'        => $role,
    ]);

    header('Location: login.php?inscription=success');
    exit();

} catch (PDOException $e) {
    die("Erreur inscription : " . $e->getMessage());
}
?>
