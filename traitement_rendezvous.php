<?php
session_start();
require_once 'connexion_bd.php';
require_once 'includes/security.php';

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
    header('Location: calendrier.php');
    exit();
}
require_csrf();

$utilisateur_id = $_SESSION['user_id'];
$garage_id      = intval($_POST['garage_id'] ?? 1);
$mecano_id      = !empty($_POST['mecanicien_id']) ? intval($_POST['mecanicien_id']) : null;
$vehicule       = trim($_POST['vehicule'] ?? '');
$id_vl          = !empty($_POST['id_vl']) ? intval($_POST['id_vl']) : null;
$service        = trim($_POST['service'] ?? '');
$date_rdv       = $_POST['date_rdv'] ?? '';
$heure_rdv      = $_POST['heure_rdv'] ?? '';
$notes          = trim($_POST['notes'] ?? '');

if (empty($vehicule) || empty($service) || empty($date_rdv) || empty($heure_rdv)) {
    header('Location: calendrier.php?erreur=champs_vides');
    exit();
}

try {
    $stmt_verif = $pdo->prepare("
        SELECT COUNT(*)
        FROM rendez_vous
        WHERE date_rdv = :date_rdv
          AND heure_rdv = :heure_rdv
          AND garage_id = :garage_id
          AND statut <> 'cancelled'
    ");
    $stmt_verif->execute([
        ':date_rdv' => $date_rdv,
        ':heure_rdv' => $heure_rdv,
        ':garage_id' => $garage_id
    ]);

    if ($stmt_verif->fetchColumn() > 0) {
        header('Location: calendrier.php?erreur=creneau_pris');
        exit();
    }

    $stmt = $pdo->prepare("
        INSERT INTO rendez_vous
            (utilisateur_id, garage_id, mecano_id, id_vl, vehicule, service, date_rdv, heure_rdv, statut, notes, cree_le)
        VALUES
            (:utilisateur_id, :garage_id, :mecano_id, :id_vl, :vehicule, :service, :date_rdv, :heure_rdv, 'pending', :notes, NOW())
    ");
    $stmt->execute([
        ':utilisateur_id' => $utilisateur_id,
        ':garage_id'      => $garage_id,
        ':mecano_id'      => $mecano_id,
        ':id_vl'          => $id_vl,
        ':vehicule'       => $vehicule,
        ':service'        => $service,
        ':date_rdv'       => $date_rdv,
        ':heure_rdv'      => $heure_rdv,
        ':notes'          => $notes ?: null,
    ]);

    header('Location: calendrier.php?success=rdv_pris');
    exit();

} catch (PDOException $e) {
    die("Erreur RDV : " . $e->getMessage());
}
?>
