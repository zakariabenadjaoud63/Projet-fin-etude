<?php
session_start();
require_once 'connexion_bd.php';
require_once 'includes/security.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'mecano') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $rdv_id         = intval($_POST['rdv_id'] ?? 0);
    $nouveau_statut = trim($_POST['nouveau_statut'] ?? '');
    $mecano_id      = intval($_SESSION['user_id'] ?? 0);

    $statuts_valides = ['pending', 'confirmed', 'cancelled'];
    if ($rdv_id > 0 && in_array($nouveau_statut, $statuts_valides)) {
        $stmt = $pdo->prepare("UPDATE rendez_vous SET statut = :statut WHERE id = :id AND mecano_id = :mecano_id");
        $stmt->execute([
            ':statut' => $nouveau_statut,
            ':id' => $rdv_id,
            ':mecano_id' => $mecano_id
        ]);
    }
}

header('Location: espace_mecano.php#rdv-mecano');
exit();
?>
