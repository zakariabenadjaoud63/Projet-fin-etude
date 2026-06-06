<?php
session_start();
require_once '../connexion_bd.php';
require_once '../includes/security.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../espace_admin.php#admin-rdv');
    exit();
}

require_csrf();

$rdv_id = intval($_POST['rdv_id'] ?? 0);
$mecano_id = !empty($_POST['mecano_id']) ? intval($_POST['mecano_id']) : null;

if ($rdv_id > 0) {
    if ($mecano_id) {
        $stmt_check = $pdo->prepare("SELECT id FROM utilisateurs WHERE id = :id AND role = 'mecano' LIMIT 1");
        $stmt_check->execute([':id' => $mecano_id]);
        if (!$stmt_check->fetch()) {
            header('Location: ../espace_admin.php?erreur=mecano_invalide#admin-rdv');
            exit();
        }
    }

    $stmt = $pdo->prepare("UPDATE rendez_vous SET mecano_id = :mecano_id WHERE id = :id");
    $stmt->execute([':mecano_id' => $mecano_id, ':id' => $rdv_id]);
}

header('Location: ../espace_admin.php?ok=rdv#admin-rdv');
exit();
