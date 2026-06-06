<?php
session_start();
require_once '../connexion_bd.php';
require_once '../includes/security.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../espace_admin.php#admin-users');
    exit();
}

require_csrf();

$nom = trim($_POST['nom'] ?? '');
$email = trim($_POST['email'] ?? '');
$telephone = trim($_POST['telephone'] ?? '');
$mot_de_passe = $_POST['mot_de_passe'] ?? '';
$role = ($_POST['role'] ?? '') === 'mecano' ? 'mecano' : 'client';
$garages_id = !empty($_POST['garages_id']) ? intval($_POST['garages_id']) : null;

if (!$nom || !$email || !$mot_de_passe || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: ../espace_admin.php?erreur=utilisateur_invalide#admin-users');
    exit();
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO utilisateurs (nom, email, telephone, garages_id, mot_de_passe, role, cree_le)
        VALUES (:nom, :email, :telephone, :garages_id, :mot_de_passe, :role, NOW())
    ");
    $stmt->execute([
        ':nom' => $nom,
        ':email' => $email,
        ':telephone' => $telephone ?: null,
        ':garages_id' => $garages_id,
        ':mot_de_passe' => password_hash($mot_de_passe, PASSWORD_DEFAULT),
        ':role' => $role
    ]);
    header('Location: ../espace_admin.php?ok=user#admin-users');
    exit();
} catch (PDOException $e) {
    header('Location: ../espace_admin.php?erreur=email_existe#admin-users');
    exit();
}
