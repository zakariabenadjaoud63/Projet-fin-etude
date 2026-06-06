<?php
session_start();
require_once 'connexion_bd.php';
require_once 'includes/security.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'mecano') {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: espace_mecano.php');
    exit();
}
require_csrf();

$rdv_id         = intval($_POST['rdv_id'] ?? 0);
$utilisateur_id = intval($_POST['utilisateur_id'] ?? 0);
$immatriculation = trim($_POST['immatriculation'] ?? '');
$modele         = trim($_POST['modele'] ?? '');
$progression    = max(0, min(100, intval($_POST['progression'] ?? 0)));
$statut         = trim($_POST['statut'] ?? 'En cours');
$eta            = trim($_POST['eta'] ?? '');
$note           = trim($_POST['note_mecanicien'] ?? '');
$mecanicien     = $_SESSION['nom'];
$mecano_id       = intval($_SESSION['user_id'] ?? 0);

if (!$utilisateur_id || !$immatriculation || !$modele) {
    header('Location: espace_mecano.php?erreur=champs_manquants');
    exit();
}

if ($rdv_id > 0) {
    $stmt_rdv = $pdo->prepare("SELECT id FROM rendez_vous WHERE id = :id AND mecano_id = :mecano_id LIMIT 1");
    $stmt_rdv->execute([':id' => $rdv_id, ':mecano_id' => $mecano_id]);
    if (!$stmt_rdv->fetch()) {
        header('Location: espace_mecano.php?erreur=rdv_non_autorise#rdv-mecano');
        exit();
    }
}

// Vérifie si un suivi existe déjà pour cette immatriculation + client
$stmt = $pdo->prepare("
    SELECT id FROM suivi_vehicules
    WHERE immatriculation = :immat AND utilisateur_id = :uid
    LIMIT 1
");
$stmt->execute([':immat' => $immatriculation, ':uid' => $utilisateur_id]);
$existant = $stmt->fetch();

if ($existant) {
    // UPDATE
    $pdo->prepare("
        UPDATE suivi_vehicules
        SET progression = :progression,
            statut      = :statut,
            eta         = :eta,
            mecanicien  = :mecanicien,
            note_mecanicien = :note
        WHERE id = :id
    ")->execute([
        ':progression' => $progression,
        ':statut'      => $statut,
        ':eta'         => $eta ?: null,
        ':mecanicien'  => $mecanicien,
        ':note'        => $note ?: null,
        ':id'          => $existant['id'],
    ]);
} else {
    // INSERT — génère une référence unique
    $reference = 'REF-' . strtoupper(substr(md5(uniqid()), 0, 6));

    $pdo->prepare("
        INSERT INTO suivi_vehicules
            (reference, utilisateur_id, immatriculation, modele, progression, statut, eta, mecanicien, note_mecanicien)
        VALUES
            (:ref, :uid, :immat, :modele, :progression, :statut, :eta, :mecanicien, :note)
    ")->execute([
        ':ref'         => $reference,
        ':uid'         => $utilisateur_id,
        ':immat'       => $immatriculation,
        ':modele'      => $modele,
        ':progression' => $progression,
        ':statut'      => $statut,
        ':eta'         => $eta ?: null,
        ':mecanicien'  => $mecanicien,
        ':note'        => $note ?: null,
    ]);
}

header('Location: espace_mecano.php?suivi=ok#suivis-existants');
exit();
?>
