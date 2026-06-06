<?php
session_start();
require_once 'connexion_bd.php';
require_once 'includes/security.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}
require_csrf();

$email        = trim($_POST['email'] ?? '');
$mot_de_passe = $_POST['mot_de_passe'] ?? '';

if (empty($email) || empty($mot_de_passe)) {
    header('Location: login.php?erreur=champs_vides');
    exit();
}

$stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE email = :email LIMIT 1");
$stmt->execute([':email' => $email]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: login.php?erreur=identifiants');
    exit();
}

$mdp_ok = password_verify($mot_de_passe, $user['mot_de_passe'])
       || $mot_de_passe === $user['mot_de_passe'];

if (!$mdp_ok) {
    header('Location: login.php?erreur=identifiants');
    exit();
}

$_SESSION['user_id']    = $user['id'];
$_SESSION['nom']        = $user['nom'];
$_SESSION['role']       = strtolower(trim($user['role']));
$_SESSION['garages_id'] = $user['garages_id'];

switch ($_SESSION['role']) {
    case 'admin':
        header('Location: espace_admin.php');
        break;
    case 'mecano':
        header('Location: espace_mecano.php');
        break;
    default:
        header('Location: espace_client.php');
}
exit();
?>
