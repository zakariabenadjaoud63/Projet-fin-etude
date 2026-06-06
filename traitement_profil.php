<?php
session_start();
require_once 'connexion_bd.php';
require_once 'includes/security.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: profil.php'); exit(); }

verify_csrf_token($_POST['csrf_token'] ?? '');

$uid    = $_SESSION['user_id'];
$action = $_POST['action'] ?? '';

if ($action === 'update_info') {

  $nom       = trim($_POST['nom']       ?? '');
  $email     = trim($_POST['email']     ?? '');
  $telephone = trim($_POST['telephone'] ?? '');

  if (!$nom || !$email) {
    header('Location: profil.php?erreur=champs_vides'); exit();
  }
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header('Location: profil.php?erreur=email_invalide'); exit();
  }

  // Vérifier si l'email est pris par un autre compte
  $check = $pdo->prepare("SELECT id FROM utilisateurs WHERE email = :email AND id != :uid");
  $check->execute([':email' => $email, ':uid' => $uid]);
  if ($check->fetch()) {
    header('Location: profil.php?erreur=email_pris'); exit();
  }

  $stmt = $pdo->prepare("UPDATE utilisateurs SET nom = :nom, email = :email, telephone = :tel WHERE id = :uid");
  $stmt->execute([':nom' => $nom, ':email' => $email, ':tel' => $telephone, ':uid' => $uid]);

  // Mettre à jour la session
  $_SESSION['nom']   = $nom;
  $_SESSION['email'] = $email;

  header('Location: profil.php?ok=info'); exit();

} elseif ($action === 'update_password') {

  $actuel    = $_POST['mot_de_passe_actuel']    ?? '';
  $nouveau   = $_POST['nouveau_mot_de_passe']   ?? '';
  $confirmer = $_POST['confirmer_mot_de_passe'] ?? '';

  if (!$actuel || !$nouveau || !$confirmer) {
    header('Location: profil.php?erreur=champs_mdp#securite'); exit();
  }
  if (strlen($nouveau) < 6) {
    header('Location: profil.php?erreur=mdp_court#securite'); exit();
  }
  if ($nouveau !== $confirmer) {
    header('Location: profil.php?erreur=mdp_diff#securite'); exit();
  }

  // Vérifier le mot de passe actuel
  $stmt = $pdo->prepare("SELECT mot_de_passe FROM utilisateurs WHERE id = :uid");
  $stmt->execute([':uid' => $uid]);
  $row = $stmt->fetch();

  if (!$row || !password_verify($actuel, $row['mot_de_passe'])) {
    header('Location: profil.php?erreur=mdp_incorrect#securite'); exit();
  }

  $hash = password_hash($nouveau, PASSWORD_DEFAULT);
  $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = :hash WHERE id = :uid")
      ->execute([':hash' => $hash, ':uid' => $uid]);

  header('Location: profil.php?ok=password#securite'); exit();

} else {
  header('Location: profil.php'); exit();
}
