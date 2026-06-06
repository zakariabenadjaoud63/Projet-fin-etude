<?php
session_start();
require_once 'includes/security.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
if (($_SESSION['role'] ?? 'client') !== 'client') {
  header('Location: ' . ($_SESSION['role'] === 'mecano' ? 'espace_mecano.php' : 'espace_admin.php'));
  exit();
}
require_once 'connexion_bd.php';

$stmt = $pdo->prepare("SELECT * FROM vehicules WHERE id_proprietaire = :id ORDER BY id_v DESC");
$stmt->execute([':id' => $_SESSION['user_id']]);
$mes_vehicules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pour sidebar
$nom_complet = $_SESSION['nom'] ?? '';
$initiales   = strtoupper(mb_substr($nom_complet, 0, 1));
$mots = explode(' ', $nom_complet);
if (count($mots) >= 2) $initiales = strtoupper(mb_substr($mots[0],0,1) . mb_substr($mots[1],0,1));
$rdv_attente = 0; // pas chargé ici, pas nécessaire

$activePage = 'vehicules';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MécaSpeed — Mes véhicules</title>
  <style>
  /* ── Layout dashboard partagé ── */
  .dash-root {
    display: grid;
    grid-template-columns: 240px 1fr;
    min-height: calc(100vh - 64px);
    background: #080808;
  }
  .dash-sidebar {
    border-right: 1px solid rgba(255,255,255,.07);
    padding: 2rem 0;
    position: sticky; top: 64px;
    height: calc(100vh - 64px);
    overflow-y: auto;
    display: flex; flex-direction: column;
  }
  .sb-profile { padding: 0 1.25rem 1.75rem; border-bottom: 1px solid rgba(255,255,255,.06); margin-bottom: 1.5rem; }
  .sb-avatar {
    width: 52px; height: 52px; border-radius: 14px;
    background: linear-gradient(135deg, #1a6bbd 0%, #0e4a8a 100%);
    display: flex; align-items: center; justify-content: center;
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 1.15rem; font-weight: 900; color: #fff;
    margin-bottom: 0.9rem;
  }
  .sb-name {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 1rem; font-weight: 800; text-transform: uppercase;
    letter-spacing: 0.04em; color: #fff; margin-bottom: 0.3rem;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  }
  .sb-role {
    display: inline-flex; align-items: center;
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 0.65rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase;
    color: #1a6bbd; background: rgba(26,107,189,.12); border: 1px solid rgba(26,107,189,.2);
    padding: 0.18rem 0.55rem; border-radius: 3px;
  }
  .sb-nav { flex: 1; padding: 0 0.75rem; }
  .sb-section-label {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 0.6rem; font-weight: 700; letter-spacing: 0.16em; text-transform: uppercase;
    color: rgba(255,255,255,.2); padding: 0 0.6rem; margin-bottom: 0.4rem; display: block;
  }
  .sb-group { margin-bottom: 1.5rem; }
  .sb-link {
    display: flex; align-items: center; gap: 0.65rem;
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 0.82rem; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase;
    color: rgba(255,255,255,.45); padding: 0.55rem 0.75rem; border-radius: 6px;
    transition: all .15s; text-decoration: none;
  }
  .sb-link svg { flex-shrink: 0; opacity: .5; transition: opacity .15s; }
  .sb-link:hover { color: rgba(255,255,255,.85); background: rgba(255,255,255,.05); }
  .sb-link:hover svg { opacity: 1; }
  .sb-link.active { color: #fff; background: rgba(26,107,189,.14); border: 1px solid rgba(26,107,189,.18); }
  .sb-link.active svg { opacity: 1; color: #1a6bbd; }
  .sb-badge {
    margin-left: auto; font-size: 0.62rem; font-weight: 800;
    background: rgba(26,107,189,.25); color: #6aabf7;
    padding: 1px 6px; border-radius: 10px; min-width: 18px; text-align: center;
  }
  .sb-bottom { padding: 1.25rem 1.5rem 0; border-top: 1px solid rgba(255,255,255,.06); margin-top: auto; }
  .sb-logout {
    display: flex; align-items: center; gap: 0.6rem;
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 0.78rem; font-weight: 700; letter-spacing: 0.05em; text-transform: uppercase;
    color: rgba(255,255,255,.28); padding: 0.5rem; border-radius: 6px;
    transition: all .15s; text-decoration: none; width: 100%;
  }
  .sb-logout:hover { color: #f87171; background: rgba(248,113,113,.07); }

  /* ── Main area ── */
  .dash-main { overflow-y: auto; }
  .dash-topbar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 1.75rem 2.5rem;
    border-bottom: 1px solid rgba(255,255,255,.06);
    background: #0a0a0a;
    position: sticky; top: 0; z-index: 10;
  }
  .topbar-title {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 0.7rem; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase;
    color: rgba(255,255,255,.3);
  }
  .topbar-title strong {
    display: block; margin-top: 2px;
    font-size: 1.35rem; color: #fff; letter-spacing: 0.02em;
  }

  /* ── Vehicles ── */
  .dash-body { padding: 2rem 2.5rem; display: flex; flex-direction: column; gap: 2.5rem; }

  .sec-head { display: flex; align-items: center; justify-content: space-between; margin-bottom: 1.1rem; }
  .sec-title {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 0.7rem; font-weight: 700; letter-spacing: 0.14em; text-transform: uppercase;
    color: rgba(255,255,255,.3);
  }

  /* Véhicule list rows */
  .vehicle-list { display: flex; flex-direction: column; gap: 0.5rem; }
  .vehicle-row {
    display: grid;
    grid-template-columns: 40px 1fr auto auto;
    align-items: center; gap: 1.25rem;
    padding: 1.1rem 1.25rem;
    background: #101010;
    border: 1px solid rgba(255,255,255,.06);
    border-radius: 8px;
    transition: border-color .15s, background .15s;
  }
  .vehicle-row:hover { background: #141414; border-color: rgba(255,255,255,.1); }
  .vr-icon {
    width: 40px; height: 40px;
    background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.07);
    border-radius: 8px; display: flex; align-items: center; justify-content: center;
  }
  .vr-icon svg { color: rgba(255,255,255,.3); }
  .vr-info { min-width: 0; }
  .vr-name {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 0.95rem; font-weight: 800; text-transform: uppercase;
    letter-spacing: 0.03em; color: #fff;
  }
  .vr-immat {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 0.75rem; font-weight: 700; letter-spacing: 0.08em;
    color: #1a6bbd; margin-top: 2px;
  }
  .vr-year { font-size: 0.75rem; color: rgba(255,255,255,.22); text-align: right; }
  .vr-actions { display: flex; gap: 0.4rem; }
  .vr-btn {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 0.7rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase;
    color: rgba(255,255,255,.4); background: rgba(255,255,255,.04);
    border: 1px solid rgba(255,255,255,.07); padding: 0.3rem 0.75rem;
    border-radius: 4px; text-decoration: none; transition: all .15s; white-space: nowrap;
  }
  .vr-btn:hover { color: #fff; background: rgba(255,255,255,.08); border-color: rgba(255,255,255,.14); }

  /* Add form panel */
  .add-panel {
    background: #101010;
    border: 1px solid rgba(255,255,255,.07);
    border-radius: 10px; overflow: hidden;
  }
  .add-panel-header {
    padding: 1.1rem 1.5rem;
    border-bottom: 1px solid rgba(255,255,255,.06);
    display: flex; align-items: center; justify-content: space-between;
    cursor: pointer; user-select: none;
  }
  .add-panel-header-left {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 0.82rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase;
    color: rgba(255,255,255,.6); display: flex; align-items: center; gap: 0.6rem;
  }
  .add-panel-toggle {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 0.68rem; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase;
    color: #1a6bbd;
  }
  .add-panel-body { padding: 1.5rem; }

  /* Form fields (compact) */
  .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0 1rem; }
  .f-field { margin-bottom: 0.9rem; }
  .f-field label {
    display: block;
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 0.68rem; font-weight: 700; letter-spacing: 0.1em; text-transform: uppercase;
    color: rgba(255,255,255,.3); margin-bottom: 0.4rem;
  }
  .f-field input {
    width: 100%;
    background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.1);
    border-radius: 6px; color: #fff;
    font-family: 'Barlow', sans-serif; font-size: 0.9rem;
    padding: 0.65rem 0.9rem; outline: none; transition: border-color .18s, box-shadow .18s;
  }
  .f-field input:focus { border-color: #1a6bbd; box-shadow: 0 0 0 3px rgba(26,107,189,.14); }
  .f-field input::placeholder { color: rgba(255,255,255,.15); }

  /* Empty */
  .dash-empty {
    padding: 3rem 1.5rem; text-align: center;
    border: 1px dashed rgba(255,255,255,.07); border-radius: 8px;
  }
  .dash-empty p { font-size: 0.82rem; color: rgba(255,255,255,.2); margin-top: 0.5rem; }

  @media (max-width: 900px) {
    .dash-root { grid-template-columns: 1fr; }
    .dash-sidebar { display: none; }
    .dash-topbar { padding: 1.25rem 1.5rem; }
    .dash-body { padding: 1.5rem; }
    .vehicle-row { grid-template-columns: 40px 1fr auto; }
    .vr-year { display: none; }
  }
  @media (max-width: 540px) {
    .form-row { grid-template-columns: 1fr; }
  }
  </style>
</head>
<body>
<?php require __DIR__ . '/includes/public-nav.php'; ?>

<div class="dash-root">

  <!-- SIDEBAR -->
  <?php require __DIR__ . '/includes/dashboard-sidebar.php'; ?>

  <!-- MAIN -->
  <main class="dash-main" id="contenu-principal">

    <!-- Topbar -->
    <div class="dash-topbar">
      <div class="topbar-title">
        Garage virtuel
        <strong>Mes véhicules</strong>
      </div>
      <button class="btn btn-sm btn-primary" onclick="toggleAddPanel()">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Ajouter un véhicule
      </button>
    </div>

    <div class="dash-body">

      <!-- Alerts -->
      <?php if (isset($_GET['success'])): ?>
        <div class="alert alert-success" role="status">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          Véhicule ajouté avec succès.
        </div>
      <?php endif; ?>
      <?php if (isset($_GET['erreur'])): ?>
        <div class="alert alert-error" role="alert">
          <?= $_GET['erreur'] === 'immat_existe' ? 'Cette immatriculation est déjà enregistrée.' : 'Veuillez remplir les champs obligatoires.' ?>
        </div>
      <?php endif; ?>

      <!-- Formulaire ajout (collapsible) -->
      <div class="add-panel" id="add-panel" style="<?= isset($_GET['success']) || isset($_GET['erreur']) ? '' : 'display:none' ?>">
        <div class="add-panel-header" onclick="toggleAddPanel()">
          <span class="add-panel-header-left">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Nouveau véhicule
          </span>
          <span class="add-panel-toggle" id="panel-toggle-label">Fermer ↑</span>
        </div>
        <div class="add-panel-body">
          <form action="traitement_ajouter_vehicule.php" method="POST">
            <?= csrf_field() ?>
            <div class="form-row">
              <div class="f-field">
                <label for="v-marque">Marque</label>
                <input type="text" id="v-marque" name="marque" placeholder="Peugeot" required>
              </div>
              <div class="f-field">
                <label for="v-modele">Modèle</label>
                <input type="text" id="v-modele" name="modele" placeholder="208" required>
              </div>
              <div class="f-field">
                <label for="v-immat">Immatriculation</label>
                <input type="text" id="v-immat" name="immatriculation" placeholder="12345 112 15" required>
              </div>
              <div class="f-field">
                <label for="v-annee">Année</label>
                <input type="number" id="v-annee" name="annee" placeholder="2018" min="1980" max="2027">
              </div>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Enregistrer le véhicule</button>
          </form>
        </div>
      </div>

      <!-- Liste véhicules -->
      <div>
        <div class="sec-head">
          <span class="sec-title"><?= count($mes_vehicules) ?> véhicule<?= count($mes_vehicules) > 1 ? 's' : '' ?> enregistré<?= count($mes_vehicules) > 1 ? 's' : '' ?></span>
        </div>

        <?php if (count($mes_vehicules) > 0): ?>
          <div class="vehicle-list">
            <?php foreach ($mes_vehicules as $v): ?>
              <div class="vehicle-row">
                <div class="vr-icon">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 3v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                </div>
                <div class="vr-info">
                  <div class="vr-name"><?= htmlspecialchars(strtoupper($v['marque']) . ' ' . $v['modele']) ?></div>
                  <div class="vr-immat"><?= htmlspecialchars($v['immatriculation']) ?></div>
                </div>
                <div class="vr-year"><?= htmlspecialchars($v['annee'] ?? '—') ?></div>
                <div class="vr-actions">
                  <a href="suivi.php?immatriculation=<?= urlencode($v['immatriculation']) ?>" class="vr-btn">Suivi</a>
                  <a href="calendrier.php" class="vr-btn">RDV</a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

        <?php else: ?>
          <div class="dash-empty">
            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.15)" stroke-width="1.5"><rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 3v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
            <p>Aucun véhicule enregistré pour le moment.</p>
            <button class="btn btn-sm btn-ghost" onclick="toggleAddPanel(true)" style="margin-top:.5rem">
              Ajouter mon premier véhicule
            </button>
          </div>
        <?php endif; ?>
      </div>

    </div><!-- /dash-body -->
  </main>

</div>

<?php require __DIR__ . '/includes/page-footer.php'; ?>

<script>
function toggleAddPanel(forceOpen) {
  const panel = document.getElementById('add-panel');
  const label = document.getElementById('panel-toggle-label');
  const isHidden = panel.style.display === 'none';
  const show = forceOpen !== undefined ? forceOpen : isHidden;
  panel.style.display = show ? '' : 'none';
  if (label) label.textContent = show ? 'Fermer ↑' : 'Ouvrir ↓';
}
// Init label
(function(){
  const panel = document.getElementById('add-panel');
  const label = document.getElementById('panel-toggle-label');
  if (label && panel) label.textContent = panel.style.display === 'none' ? 'Ouvrir ↓' : 'Fermer ↑';
})();
</script>
</body>
</html>
