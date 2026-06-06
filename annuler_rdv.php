<?php
session_start();
require_once 'connexion_bd.php';
require_once 'includes/security.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if (($_SESSION['role'] ?? 'client') !== 'client') {
    redirect_role_home();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: espace_client.php');
    exit();
}

require_csrf();

$rdv_id = intval($_POST['rdv_id'] ?? 0);
if ($rdv_id > 0) {
    $stmt = $pdo->prepare("
        UPDATE rendez_vous
        SET statut = 'cancelled'
        WHERE id = :id
          AND utilisateur_id = :uid
          AND statut <> 'cancelled'
          AND date_rdv >= CURDATE()
    ");
    $stmt->execute([':id' => $rdv_id, ':uid' => $_SESSION['user_id']]);
}

header('Location: espace_client.php?rdv=annule');
exit();
