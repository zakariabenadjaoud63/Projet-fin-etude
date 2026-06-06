<?php
session_start();
require_once 'connexion_bd.php';
require_once 'includes/security.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
if (($_SESSION['role'] ?? 'client') !== 'client') {
  header('Location: ' . ($_SESSION['role'] === 'mecano' ? 'espace_mecano.php' : 'espace_admin.php'));
  exit();
}

$uid = $_SESSION['user_id'];
$nom_complet = $_SESSION['nom'] ?? '';
$initiales   = strtoupper(mb_substr($nom_complet, 0, 1));
$mots = explode(' ', $nom_complet);
if (count($mots) >= 2) $initiales = strtoupper(mb_substr($mots[0],0,1) . mb_substr($mots[1],0,1));

// Charger tous les RDV
try {
  $stmt = $pdo->prepare("SELECT * FROM rendez_vous WHERE utilisateur_id = :uid ORDER BY date_rdv DESC, heure_rdv DESC");
  $stmt->execute([':uid' => $uid]);
  $tous_rdv = $stmt->fetchAll();
} catch (PDOException $e) { $tous_rdv = []; }

$today = date('Y-m-d');

// Trier par filtre
$a_venir  = array_filter($tous_rdv, fn($r) => $r['date_rdv'] >= $today && $r['statut'] !== 'cancelled');
$passes   = array_filter($tous_rdv, fn($r) => $r['date_rdv'] <  $today && $r['statut'] !== 'cancelled');
$annules  = array_filter($tous_rdv, fn($r) => $r['statut'] === 'cancelled');

$rdv_attente = count(array_filter($tous_rdv, fn($r) => $r['statut'] === 'pending' && $r['date_rdv'] >= $today));

$mois_fr = ['','Jan','Fév','Mar','Avr','Mai','Jun','Jul','Aoû','Sep','Oct','Nov','Déc'];
$activePage = 'rdv';

function st_class(string $s): string { return match($s){'confirmed'=>'st-ok','cancelled'=>'st-cancel',default=>'st-wait'}; }
function st_label(string $s): string { return match($s){'confirmed'=>'Confirmé','cancelled'=>'Annulé',default=>'En attente'}; }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MécaSpeed — Mes rendez-vous</title>
  <style>
  .dash-root {
    display: grid; grid-template-columns: 240px 1fr;
    min-height: calc(100vh - 64px); background: #080808;
  }
  .dash-sidebar {
    border-right: 1px solid rgba(255,255,255,.07);
    padding: 2rem 0; position: sticky; top: 64px;
    height: calc(100vh - 64px); overflow-y: auto;
    display: flex; flex-direction: column;
  }
  .sb-profile { padding: 0 1.25rem 1.75rem; border-bottom: 1px solid rgba(255,255,255,.06); margin-bottom: 1.5rem; }
  .sb-avatar {
    width: 52px; height: 52px; border-radius: 14px;
    background: linear-gradient(135deg,#1a6bbd,#0e4a8a);
    display: flex; align-items: center; justify-content: center;
    font-family: 'Barlow Condensed',sans-serif; font-size: 1.15rem; font-weight: 900;
    color: #fff; margin-bottom: .9rem;
  }
  .sb-name { font-family:'Barlow Condensed',sans-serif; font-size:1rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em; color:#fff; margin-bottom:.3rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
  .sb-role { display:inline-flex; font-family:'Barlow Condensed',sans-serif; font-size:.65rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:#1a6bbd; background:rgba(26,107,189,.12); border:1px solid rgba(26,107,189,.2); padding:.18rem .55rem; border-radius:3px; }
  .sb-nav { flex:1; padding:0 .75rem; }
  .sb-section-label { font-family:'Barlow Condensed',sans-serif; font-size:.6rem; font-weight:700; letter-spacing:.16em; text-transform:uppercase; color:rgba(255,255,255,.2); padding:0 .6rem; margin-bottom:.4rem; display:block; }
  .sb-group { margin-bottom:1.5rem; }
  .sb-link { display:flex; align-items:center; gap:.65rem; font-family:'Barlow Condensed',sans-serif; font-size:.82rem; font-weight:600; letter-spacing:.04em; text-transform:uppercase; color:rgba(255,255,255,.45); padding:.55rem .75rem; border-radius:6px; transition:all .15s; text-decoration:none; }
  .sb-link svg { flex-shrink:0; opacity:.5; transition:opacity .15s; }
  .sb-link:hover { color:rgba(255,255,255,.85); background:rgba(255,255,255,.05); }
  .sb-link:hover svg { opacity:1; }
  .sb-link.active { color:#fff; background:rgba(26,107,189,.14); border:1px solid rgba(26,107,189,.18); }
  .sb-link.active svg { opacity:1; color:#1a6bbd; }
  .sb-badge { margin-left:auto; font-size:.62rem; font-weight:800; background:rgba(26,107,189,.25); color:#6aabf7; padding:1px 6px; border-radius:10px; min-width:18px; text-align:center; }
  .sb-badge.warn { background:rgba(251,191,36,.2); color:#fbbf24; }
  .sb-bottom { padding:1.25rem 1.5rem 0; border-top:1px solid rgba(255,255,255,.06); margin-top:auto; }
  .sb-logout { display:flex; align-items:center; gap:.6rem; font-family:'Barlow Condensed',sans-serif; font-size:.78rem; font-weight:700; letter-spacing:.05em; text-transform:uppercase; color:rgba(255,255,255,.28); padding:.5rem; border-radius:6px; transition:all .15s; text-decoration:none; }
  .sb-logout:hover { color:#f87171; background:rgba(248,113,113,.07); }

  /* ── Main ── */
  .dash-main { overflow-y: auto; }
  .dash-topbar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 1.75rem 2.5rem;
    border-bottom: 1px solid rgba(255,255,255,.06);
    background: #0a0a0a;
    position: sticky; top: 0; z-index: 10;
  }
  .topbar-title { font-family:'Barlow Condensed',sans-serif; font-size:.7rem; font-weight:700; letter-spacing:.14em; text-transform:uppercase; color:rgba(255,255,255,.3); }
  .topbar-title strong { display:block; margin-top:2px; font-size:1.35rem; color:#fff; letter-spacing:.02em; }

  /* ── Filter tabs ── */
  .rdv-filters {
    display: flex; gap: 0; border-bottom: 1px solid rgba(255,255,255,.07);
    background: #0a0a0a; padding: 0 2.5rem;
  }
  .rdv-filter-btn {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: .78rem; font-weight: 700; letter-spacing: .07em; text-transform: uppercase;
    color: rgba(255,255,255,.3);
    padding: .85rem 1.1rem;
    border: none; background: transparent; cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: color .15s, border-color .15s;
    display: flex; align-items: center; gap: .5rem;
  }
  .rdv-filter-btn:hover { color: rgba(255,255,255,.65); }
  .rdv-filter-btn.active { color: #fff; border-bottom-color: #1a6bbd; }
  .rdv-filter-count {
    font-size: .62rem; font-weight: 800;
    padding: 1px 6px; border-radius: 10px; min-width: 18px; text-align: center;
    background: rgba(255,255,255,.08); color: rgba(255,255,255,.4);
  }
  .rdv-filter-btn.active .rdv-filter-count { background: rgba(26,107,189,.25); color: #6aabf7; }

  /* ── Content ── */
  .dash-body { padding: 2rem 2.5rem; }

  /* RDV list */
  .rdv-list { display: flex; flex-direction: column; gap: .5rem; }

  .rdv-row {
    display: grid;
    grid-template-columns: 56px 1fr auto auto;
    align-items: center; gap: 1.25rem;
    padding: 1rem 1.25rem;
    background: #101010;
    border: 1px solid rgba(255,255,255,.06);
    border-radius: 8px;
    transition: border-color .15s, background .15s;
  }
  .rdv-row:hover { background: #141414; border-color: rgba(255,255,255,.1); }
  .rdv-row.is-past { opacity: .55; }

  /* Date block */
  .rdv-date-block {
    background: rgba(255,255,255,.04);
    border: 1px solid rgba(255,255,255,.07);
    border-radius: 8px;
    padding: .45rem .4rem; text-align: center;
    flex-shrink: 0;
  }
  .rdv-day {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 1.3rem; font-weight: 900; color: #fff; line-height: 1; display: block;
  }
  .rdv-month {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: .58rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase;
    color: rgba(255,255,255,.3); display: block; margin-top: 1px;
  }
  /* Upcoming: highlight date */
  .rdv-row.is-upcoming .rdv-date-block {
    background: rgba(26,107,189,.14);
    border-color: rgba(26,107,189,.25);
  }
  .rdv-row.is-upcoming .rdv-day { color: #fff; }
  .rdv-row.is-upcoming .rdv-month { color: #1a6bbd; }

  /* Info */
  .rdv-info { min-width: 0; }
  .rdv-service {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: .92rem; font-weight: 800; text-transform: uppercase;
    letter-spacing: .03em; color: #fff; margin-bottom: 3px;
  }
  .rdv-meta {
    display: flex; gap: .75rem; flex-wrap: wrap;
    font-size: .75rem; color: rgba(255,255,255,.3);
  }
  .rdv-meta span { display: flex; align-items: center; gap: .3rem; }
  .rdv-meta svg { flex-shrink: 0; }

  /* Status */
  .st-ok,.st-cancel,.st-wait {
    display: inline-flex; align-items: center; gap: .35rem;
    font-family:'Barlow Condensed',sans-serif; font-size:.68rem; font-weight:700;
    letter-spacing:.08em; text-transform:uppercase;
    padding:.22rem .65rem; border-radius:100px; border:1px solid; white-space:nowrap;
  }
  .st-ok::before,.st-cancel::before,.st-wait::before { content:''; width:5px; height:5px; border-radius:50%; flex-shrink:0; }
  .st-ok    { background:rgba(74,222,128,.08);  color:#4ade80; border-color:rgba(74,222,128,.2); }
  .st-ok::before     { background:#4ade80; }
  .st-cancel{ background:rgba(248,113,113,.08); color:#f87171; border-color:rgba(248,113,113,.2); }
  .st-cancel::before { background:#f87171; }
  .st-wait  { background:rgba(251,191,36,.08);  color:#fbbf24; border-color:rgba(251,191,36,.2); }
  .st-wait::before   { background:#fbbf24; }

  /* Actions */
  .rdv-actions { display: flex; gap: .4rem; flex-shrink: 0; }
  .btn-annuler {
    font-family:'Barlow Condensed',sans-serif; font-size:.68rem; font-weight:700;
    letter-spacing:.06em; text-transform:uppercase;
    color:rgba(248,113,113,.5); background:transparent;
    border:1px solid rgba(248,113,113,.14); padding:.28rem .7rem; border-radius:4px;
    cursor:pointer; transition:all .15s;
  }
  .btn-annuler:hover { color:#f87171; background:rgba(248,113,113,.08); border-color:rgba(248,113,113,.3); }

  /* Empty */
  .rdv-empty {
    padding: 3.5rem 1.5rem; text-align: center;
    border: 1px dashed rgba(255,255,255,.07); border-radius: 8px;
  }
  .rdv-empty p { font-size:.82rem; color:rgba(255,255,255,.2); margin-top:.4rem; margin-bottom:1.25rem; }

  /* Panel containers */
  .rdv-panel { display: none; }
  .rdv-panel.active { display: block; }

  @media (max-width:900px) {
    .dash-root { grid-template-columns:1fr; }
    .dash-sidebar { display:none; }
    .dash-topbar,.rdv-filters,.dash-body { padding-left:1.5rem; padding-right:1.5rem; }
    .rdv-row { grid-template-columns:56px 1fr auto; }
  }
  @media (max-width:520px) {
    .rdv-row { grid-template-columns:1fr auto; }
    .rdv-date-block { display:none; }
    .rdv-actions { flex-direction:column; }
  }
  </style>
</head>
<body>
<?php require __DIR__ . '/includes/public-nav.php'; ?>

<div class="dash-root">

  <?php require __DIR__ . '/includes/dashboard-sidebar.php'; ?>

  <main class="dash-main" id="contenu-principal">

    <!-- Topbar -->
    <div class="dash-topbar">
      <div class="topbar-title">
        Planning
        <strong>Mes rendez-vous</strong>
      </div>
      <a href="calendrier.php" class="btn btn-sm btn-primary">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
        Nouveau RDV
      </a>
    </div>

    <!-- Alerts -->
    <?php if (isset($_GET['rdv']) && $_GET['rdv'] === 'annule'): ?>
      <div style="padding:1rem 2.5rem 0">
        <div class="alert alert-success" role="status">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          Rendez-vous annulé avec succès.
        </div>
      </div>
    <?php endif; ?>

    <!-- Filter tabs -->
    <div class="rdv-filters" role="tablist">
      <button class="rdv-filter-btn active" role="tab" aria-selected="true"  onclick="showPanel('avenir',this)">
        À venir
        <span class="rdv-filter-count"><?= count($a_venir) ?></span>
      </button>
      <button class="rdv-filter-btn" role="tab" aria-selected="false" onclick="showPanel('passes',this)">
        Passés
        <span class="rdv-filter-count"><?= count($passes) ?></span>
      </button>
      <button class="rdv-filter-btn" role="tab" aria-selected="false" onclick="showPanel('annules',this)">
        Annulés
        <span class="rdv-filter-count"><?= count($annules) ?></span>
      </button>
    </div>

    <div class="dash-body">

      <!-- ── À VENIR ── -->
      <div class="rdv-panel active" id="panel-avenir" role="tabpanel">
        <?php if (empty($a_venir)): ?>
          <div class="rdv-empty">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.15)" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            <p>Aucun rendez-vous à venir.</p>
            <a href="calendrier.php" class="btn btn-sm btn-primary">Prendre un rendez-vous</a>
          </div>
        <?php else: ?>
          <div class="rdv-list">
            <?php foreach ($a_venir as $r): ?>
              <div class="rdv-row is-upcoming">
                <div class="rdv-date-block">
                  <span class="rdv-day"><?= date('d', strtotime($r['date_rdv'])) ?></span>
                  <span class="rdv-month"><?= $mois_fr[intval(date('n', strtotime($r['date_rdv'])))] ?></span>
                </div>
                <div class="rdv-info">
                  <div class="rdv-service"><?= htmlspecialchars($r['service']) ?></div>
                  <div class="rdv-meta">
                    <span>
                      <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                      <?= htmlspecialchars(substr($r['heure_rdv'],0,5)) ?>
                    </span>
                    <?php if (!empty($r['vehicule'])): ?>
                    <span>
                      <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 3v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                      <?= htmlspecialchars($r['vehicule']) ?>
                    </span>
                    <?php endif; ?>
                  </div>
                </div>
                <span class="<?= st_class($r['statut']) ?>"><?= st_label($r['statut']) ?></span>
                <div class="rdv-actions">
                  <form action="annuler_rdv.php" method="POST">
                    <?= csrf_field() ?>
                    <input type="hidden" name="rdv_id" value="<?= intval($r['id']) ?>">
                    <button type="submit" class="btn-annuler">Annuler</button>
                  </form>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- ── PASSÉS ── -->
      <div class="rdv-panel" id="panel-passes" role="tabpanel">
        <?php if (empty($passes)): ?>
          <div class="rdv-empty">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.15)" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            <p>Aucun rendez-vous passé.</p>
          </div>
        <?php else: ?>
          <div class="rdv-list">
            <?php foreach ($passes as $r): ?>
              <div class="rdv-row is-past">
                <div class="rdv-date-block">
                  <span class="rdv-day"><?= date('d', strtotime($r['date_rdv'])) ?></span>
                  <span class="rdv-month"><?= $mois_fr[intval(date('n', strtotime($r['date_rdv'])))] ?></span>
                </div>
                <div class="rdv-info">
                  <div class="rdv-service"><?= htmlspecialchars($r['service']) ?></div>
                  <div class="rdv-meta">
                    <span>
                      <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                      <?= htmlspecialchars(substr($r['heure_rdv'],0,5)) ?>
                    </span>
                    <?php if (!empty($r['vehicule'])): ?>
                    <span>
                      <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 3v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                      <?= htmlspecialchars($r['vehicule']) ?>
                    </span>
                    <?php endif; ?>
                  </div>
                </div>
                <span class="<?= st_class($r['statut']) ?>"><?= st_label($r['statut']) ?></span>
                <div class="rdv-actions">
                  <a href="calendrier.php" class="btn btn-sm btn-ghost">Reprendre</a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- ── ANNULÉS ── -->
      <div class="rdv-panel" id="panel-annules" role="tabpanel">
        <?php if (empty($annules)): ?>
          <div class="rdv-empty">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.15)" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
            <p>Aucun rendez-vous annulé.</p>
          </div>
        <?php else: ?>
          <div class="rdv-list">
            <?php foreach ($annules as $r): ?>
              <div class="rdv-row">
                <div class="rdv-date-block">
                  <span class="rdv-day"><?= date('d', strtotime($r['date_rdv'])) ?></span>
                  <span class="rdv-month"><?= $mois_fr[intval(date('n', strtotime($r['date_rdv'])))] ?></span>
                </div>
                <div class="rdv-info">
                  <div class="rdv-service"><?= htmlspecialchars($r['service']) ?></div>
                  <div class="rdv-meta">
                    <span>
                      <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                      <?= date('d/m/Y', strtotime($r['date_rdv'])) ?> · <?= htmlspecialchars(substr($r['heure_rdv'],0,5)) ?>
                    </span>
                  </div>
                </div>
                <span class="st-cancel">Annulé</span>
                <div class="rdv-actions">
                  <a href="calendrier.php" class="btn btn-sm btn-ghost">Reprogrammer</a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

    </div><!-- /dash-body -->
  </main>

</div>

<?php require __DIR__ . '/includes/page-footer.php'; ?>

<script>
function showPanel(id, btn) {
  document.querySelectorAll('.rdv-panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.rdv-filter-btn').forEach(b => { b.classList.remove('active'); b.setAttribute('aria-selected','false'); });
  document.getElementById('panel-' + id).classList.add('active');
  btn.classList.add('active');
  btn.setAttribute('aria-selected','true');
}
</script>
</body>
</html>
