<?php
session_start();
require_once 'connexion_bd.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
if (($_SESSION['role'] ?? 'client') !== 'client') {
  header('Location: ' . ($_SESSION['role'] === 'mecano' ? 'espace_mecano.php' : 'espace_admin.php'));
  exit();
}

$uid = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM suivi_vehicules WHERE utilisateur_id = :uid ORDER BY cree_le DESC");
$stmt->execute([':uid' => $uid]);
$suivis = $stmt->fetchAll();

$suivi_id  = isset($_GET['id']) ? intval($_GET['id']) : null;
$immat_url = isset($_GET['immatriculation']) ? trim($_GET['immatriculation']) : '';
$suivi_sel = null;

if ($suivi_id) foreach ($suivis as $s) { if ($s['id'] == $suivi_id) { $suivi_sel = $s; break; } }
if (!$suivi_sel && $immat_url !== '') foreach ($suivis as $s) { if (strcasecmp($s['immatriculation'], $immat_url) === 0) { $suivi_sel = $s; break; } }
if (!$suivi_sel && count($suivis) === 1) $suivi_sel = $suivis[0];

$activePage = 'suivi';

function pct_class(int $p): string {
  return $p >= 80 ? 'high' : '';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MécaSpeed — Suivi véhicule</title>
</head>
<body>
<?php require __DIR__ . '/includes/public-nav.php'; ?>

<main id="contenu-principal">

  <!-- HERO -->
  <section class="page-hero" aria-labelledby="titre-suivi">
    <div class="container page-hero-inner">
      <span class="eyebrow">Atelier en direct</span>
      <h1 id="titre-suivi">Suivi de mes <em>véhicules</em></h1>
      <p class="hero-lead">Consultez l'avancement des réparations créées par votre mécanicien, en temps réel.</p>
      <?php if ($immat_url !== '' && !$suivi_sel): ?>
        <div class="alert alert-warning" role="alert">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          Aucun suivi trouvé pour l'immatriculation <?= htmlspecialchars($immat_url) ?>.
        </div>
      <?php endif; ?>
    </div>
  </section>

  <?php if (empty($suivis)): ?>
    <section class="ms-section">
      <div class="container">
        <div class="empty-state">
          <div class="empty-state-icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          </div>
          <h3>Aucun suivi en cours</h3>
          <p>Votre mécanicien n'a pas encore créé de fiche de suivi pour votre véhicule.</p>
          <a href="calendrier.php" class="btn btn-primary">Prendre un rendez-vous</a>
        </div>
      </div>
    </section>

  <?php else: ?>
    <section class="ms-section" aria-labelledby="titre-liste-suivis">
      <div class="container">
        <div style="display:grid;grid-template-columns:<?= count($suivis) > 1 ? '280px 1fr' : '1fr' ?>;gap:2rem;align-items:start">

          <?php if (count($suivis) > 1): ?>
          <!-- Liste des véhicules -->
          <div>
            <div class="section-head">
              <span class="eyebrow">Sélectionner</span>
              <h2 id="titre-liste-suivis" style="font-size:1.2rem">Véhicules suivis</h2>
            </div>
            <div style="display:flex;flex-direction:column;gap:0.6rem">
              <?php foreach ($suivis as $s): ?>
                <a href="suivi.php?id=<?= $s['id'] ?>#detail-suivi"
                   style="display:block;background:<?= ($suivi_sel && $suivi_sel['id'] == $s['id']) ? 'rgba(26,107,189,.12)' : 'var(--surface-2)' ?>;
                          border:1px solid <?= ($suivi_sel && $suivi_sel['id'] == $s['id']) ? 'var(--blue)' : 'var(--border)' ?>;
                          border-radius:8px;padding:1rem;transition:all .18s">
                  <div style="font-family:'Barlow Condensed',sans-serif;font-size:0.95rem;font-weight:800;text-transform:uppercase;color:#fff;margin-bottom:0.4rem">
                    <?= htmlspecialchars($s['modele']) ?>
                  </div>
                  <div style="font-size:0.78rem;color:var(--text-muted);margin-bottom:0.6rem"><?= htmlspecialchars($s['immatriculation']) ?></div>
                  <div class="progress-track"><div class="progress-fill <?= pct_class(intval($s['progression'])) ?>" style="width:<?= intval($s['progression']) ?>%"></div></div>
                  <div style="font-family:'Barlow Condensed',sans-serif;font-size:0.72rem;font-weight:700;color:var(--blue);margin-top:0.3rem"><?= intval($s['progression']) ?>%</div>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

          <!-- Détail du suivi -->
          <div id="detail-suivi">
            <?php if ($suivi_sel): ?>
              <div class="section-head">
                <span class="eyebrow">Fiche intervention</span>
                <h2 style="font-size:clamp(1.5rem,3vw,2.2rem)">
                  <?= htmlspecialchars($suivi_sel['modele']) ?>
                </h2>
              </div>

              <div class="tracking-card">
                <div class="tracking-card-header">
                  <div>
                    <div class="tracking-car"><?= htmlspecialchars($suivi_sel['modele']) ?></div>
                    <div style="font-size:0.82rem;color:var(--text-muted);margin-top:2px"><?= htmlspecialchars($suivi_sel['immatriculation']) ?> · Réf. <?= htmlspecialchars($suivi_sel['reference']) ?></div>
                  </div>
                  <span class="status-pill <?= intval($suivi_sel['progression']) >= 100 ? 'status-confirmed' : 'status-pending' ?>">
                    <?= htmlspecialchars($suivi_sel['statut']) ?>
                  </span>
                </div>

                <div class="tracking-body">
                  <!-- Progression -->
                  <div class="tracking-progress">
                    <div class="tracking-progress-header">
                      <span class="tracking-progress-label">Progression de l'intervention</span>
                      <span class="progress-pct"><?= intval($suivi_sel['progression']) ?>%</span>
                    </div>
                    <div class="progress-track" style="height:8px">
                      <div class="progress-fill <?= pct_class(intval($suivi_sel['progression'])) ?>" style="width:<?= intval($suivi_sel['progression']) ?>%"></div>
                    </div>
                  </div>

                  <!-- Data -->
                  <div class="data-list">
                    <div class="data-row">
                      <span class="data-key">Mécanicien</span>
                      <span class="data-val"><?= htmlspecialchars($suivi_sel['mecanicien'] ?? 'Non attribué') ?></span>
                    </div>
                    <div class="data-row">
                      <span class="data-key">Heure estimée (ETA)</span>
                      <span class="data-val"><?= htmlspecialchars($suivi_sel['eta'] ?? 'Non renseignée') ?></span>
                    </div>
                    <div class="data-row">
                      <span class="data-key">Dernière mise à jour</span>
                      <span class="data-val"><?= date('d/m/Y à H:i', strtotime($suivi_sel['cree_le'])) ?></span>
                    </div>
                  </div>

                  <?php if (!empty($suivi_sel['note_mecanicien'])): ?>
                    <div class="tracking-note">
                      <span class="tracking-note-label">Note du mécanicien</span>
                      <?= nl2br(htmlspecialchars($suivi_sel['note_mecanicien'])) ?>
                    </div>
                  <?php endif; ?>

                  <div style="display:flex;gap:0.75rem;flex-wrap:wrap;margin-top:1.5rem">
                    <a href="calendrier.php" class="btn btn-primary">
                      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                      Nouveau rendez-vous
                    </a>
                    <a href="espace_client.php" class="btn btn-outline">Retour espace client</a>
                  </div>
                </div>
              </div>

            <?php else: ?>
              <div class="empty-state">
                <div class="empty-state-icon">
                  <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </div>
                <h3>Sélectionnez un véhicule</h3>
                <p>Cliquez sur un véhicule dans la liste pour voir le détail du suivi.</p>
              </div>
            <?php endif; ?>
          </div>

        </div>
      </div>
    </section>
  <?php endif; ?>

</main>

<?php require __DIR__ . '/includes/page-footer.php'; ?>
</body>
</html>
