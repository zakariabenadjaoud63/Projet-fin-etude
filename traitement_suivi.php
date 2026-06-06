<?php
session_start();
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_POST['immatriculation'])) {
    header('Location: suivi.php');
    exit();
}

$immat = trim($_POST['immatriculation']);
$uid = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT * FROM suivi_vehicules
    WHERE immatriculation = :immat AND utilisateur_id = :uid
    ORDER BY cree_le DESC LIMIT 1
");
$stmt->execute([':immat' => $immat, ':uid' => $uid]);
$vehicule = $stmt->fetch();

if (!$vehicule) {
    header('Location: suivi.php?erreur=introuvable');
    exit();
}

$activePage = 'suivi';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MecaSpeed - Resultat du suivi</title>
</head>
<body>
<?php require __DIR__ . '/includes/public-nav.php'; ?>

<main id="contenu-principal">
  <section class="page-header" aria-labelledby="titre-resultat-suivi">
    <p>Suivi vehicule</p>
    <h2 id="titre-resultat-suivi">Resultat de recherche</h2>
    <p>Detail de la fiche de suivi correspondant a l'immatriculation demandee.</p>
  </section>

  <section class="section-detail-suivi" aria-labelledby="titre-fiche-suivi">
    <h2 id="titre-fiche-suivi">Fiche de suivi</h2>
    <article class="detail-suivi">
      <header>
        <h3><?= htmlspecialchars($vehicule['modele']) ?></h3>
        <p>Reference : <?= htmlspecialchars($vehicule['reference']) ?></p>
        <p>Immatriculation : <?= htmlspecialchars($vehicule['immatriculation']) ?></p>
      </header>

      <dl>
        <dt>Statut</dt>
        <dd><?= htmlspecialchars($vehicule['statut']) ?></dd>
        <dt>Progression</dt>
        <dd><?= intval($vehicule['progression']) ?>%</dd>
        <dt>ETA</dt>
        <dd><?= htmlspecialchars($vehicule['eta'] ?? 'Non renseignee') ?></dd>
        <dt>Mecanicien</dt>
        <dd><?= htmlspecialchars($vehicule['mecanicien'] ?? 'Non attribue') ?></dd>
      </dl>

      <section class="note-mecanicien" aria-labelledby="titre-note-suivi">
        <h4 id="titre-note-suivi">Note du mecanicien</h4>
        <p><?= nl2br(htmlspecialchars($vehicule['note_mecanicien'] ?? 'Aucune note.')) ?></p>
      </section>
    </article>
  </section>

  <nav aria-label="Actions suivi">
    <a href="suivi.php">Retour au suivi</a>
    <a href="calendrier.php">Prendre un rendez-vous</a>
  </nav>
</main>
</body>
</html>
