<?php
session_start();
require_once 'connexion_bd.php';
require_once 'includes/security.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
if (($_SESSION['role'] ?? 'client') !== 'client') {
  header('Location: ' . ($_SESSION['role'] === 'mecano' ? 'espace_mecano.php' : 'espace_admin.php'));
  exit();
}

$uid         = $_SESSION['user_id'];
$nom_complet = $_SESSION['nom'] ?? '';
$initiales   = strtoupper(mb_substr($nom_complet, 0, 1));
$mots = explode(' ', $nom_complet);
if (count($mots) >= 2) $initiales = strtoupper(mb_substr($mots[0],0,1) . mb_substr($mots[1],0,1));

try {
  $stmt = $pdo->prepare("SELECT * FROM commandes WHERE utilisateur_id = :uid ORDER BY cree_le DESC");
  $stmt->execute([':uid' => $uid]);
  $toutes = $stmt->fetchAll();
} catch (PDOException $e) { $toutes = []; }

$rdv_attente  = 0;
$nb_commandes = count($toutes);

$en_attente     = array_filter($toutes, fn($c) => ($c['statut'] ?? '') === 'en_attente');
$en_preparation = array_filter($toutes, fn($c) => ($c['statut'] ?? '') === 'en_preparation');
$livrees        = array_filter($toutes, fn($c) => ($c['statut'] ?? '') === 'livree');
$annulees       = array_filter($toutes, fn($c) => ($c['statut'] ?? '') === 'annulee');

$activePage = 'commandes';

function cmd_class(string $s): string { return match($s){'livree'=>'st-ok','annulee'=>'st-cancel','en_preparation'=>'st-blue',default=>'st-wait'}; }
function cmd_label(string $s): string { return match($s){'livree'=>'Livrée','annulee'=>'Annulée','en_preparation'=>'En préparation','en_attente'=>'En attente',default=>ucfirst(str_replace('_',' ',$s))}; }
function pay_label(string $s): string { return match($s){'especes'=>'Espèces','cib'=>'Carte CIB','virement'=>'Virement',default=>$s}; }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MécaSpeed — Mes commandes</title>
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
  .sb-avatar { width:52px; height:52px; border-radius:14px; background:linear-gradient(135deg,#1a6bbd,#0e4a8a); display:flex; align-items:center; justify-content:center; font-family:'Barlow Condensed',sans-serif; font-size:1.15rem; font-weight:900; color:#fff; margin-bottom:.9rem; }
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
  .cmd-filters {
    display: flex; gap: 0;
    border-bottom: 1px solid rgba(255,255,255,.07);
    background: #0a0a0a; padding: 0 2.5rem;
    overflow-x: auto;
  }
  .cmd-filter-btn {
    font-family:'Barlow Condensed',sans-serif; font-size:.78rem; font-weight:700;
    letter-spacing:.07em; text-transform:uppercase; color:rgba(255,255,255,.3);
    padding:.85rem 1.1rem; border:none; background:transparent; cursor:pointer;
    border-bottom:2px solid transparent; transition:color .15s,border-color .15s;
    display:flex; align-items:center; gap:.5rem; white-space:nowrap;
  }
  .cmd-filter-btn:hover { color:rgba(255,255,255,.65); }
  .cmd-filter-btn.active { color:#fff; border-bottom-color:#1a6bbd; }
  .cmd-filter-count {
    font-size:.62rem; font-weight:800; padding:1px 6px; border-radius:10px;
    min-width:18px; text-align:center;
    background:rgba(255,255,255,.08); color:rgba(255,255,255,.4);
  }
  .cmd-filter-btn.active .cmd-filter-count { background:rgba(26,107,189,.25); color:#6aabf7; }

  /* ── Content ── */
  .dash-body { padding: 2rem 2.5rem; }

  /* ── Order cards ── */
  .cmd-list { display: flex; flex-direction: column; gap: .5rem; }
  .cmd-card {
    display: grid; grid-template-columns: auto 1fr auto auto;
    align-items: center; gap: 1.25rem;
    padding: 1rem 1.25rem;
    background: #101010; border: 1px solid rgba(255,255,255,.06);
    border-radius: 8px; transition: border-color .15s, background .15s;
    text-decoration: none;
  }
  .cmd-card:hover { background: #141414; border-color: rgba(255,255,255,.1); }

  /* ID block */
  .cmd-id-block {
    background: rgba(26,107,189,.1); border: 1px solid rgba(26,107,189,.18);
    border-radius: 8px; padding: .5rem .75rem; text-align: center; min-width: 56px;
    flex-shrink: 0;
  }
  .cmd-id-num {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 1.1rem; font-weight: 900; color: #6aabf7; line-height: 1; display: block;
  }
  .cmd-id-label {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: .56rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase;
    color: rgba(255,255,255,.25); display: block; margin-top: 1px;
  }

  /* Info */
  .cmd-info { min-width: 0; }
  .cmd-date-row {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: .9rem; font-weight: 800; color: #fff; margin-bottom: 3px;
    text-transform: uppercase; letter-spacing: .02em;
  }
  .cmd-meta {
    display: flex; gap: .75rem; flex-wrap: wrap;
    font-size: .75rem; color: rgba(255,255,255,.3);
  }
  .cmd-meta span { display: flex; align-items: center; gap: .3rem; }

  /* Status pills */
  .st-ok,.st-cancel,.st-wait,.st-blue {
    display: inline-flex; align-items: center; gap: .35rem;
    font-family:'Barlow Condensed',sans-serif; font-size:.68rem; font-weight:700;
    letter-spacing:.08em; text-transform:uppercase;
    padding:.22rem .65rem; border-radius:100px; border:1px solid; white-space:nowrap;
  }
  .st-ok::before,.st-cancel::before,.st-wait::before,.st-blue::before { content:''; width:5px; height:5px; border-radius:50%; flex-shrink:0; }
  .st-ok    { background:rgba(74,222,128,.08);  color:#4ade80; border-color:rgba(74,222,128,.2); }
  .st-ok::before     { background:#4ade80; }
  .st-cancel{ background:rgba(248,113,113,.08); color:#f87171; border-color:rgba(248,113,113,.2); }
  .st-cancel::before { background:#f87171; }
  .st-wait  { background:rgba(251,191,36,.08);  color:#fbbf24; border-color:rgba(251,191,36,.2); }
  .st-wait::before   { background:#fbbf24; }
  .st-blue  { background:rgba(26,107,189,.1);   color:#6aabf7; border-color:rgba(26,107,189,.25); }
  .st-blue::before   { background:#6aabf7; }

  /* Total */
  .cmd-total {
    font-family:'Barlow Condensed',sans-serif;
    font-size:1rem; font-weight:900; color:#fff; white-space:nowrap; text-align:right;
  }
  .cmd-total small { font-size:.65rem; color:rgba(255,255,255,.3); margin-left:2px; }

  /* Actions */
  .cmd-action {
    font-family:'Barlow Condensed',sans-serif; font-size:.7rem; font-weight:700;
    letter-spacing:.06em; text-transform:uppercase; color:rgba(255,255,255,.4);
    background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.07);
    padding:.3rem .8rem; border-radius:4px; text-decoration:none;
    transition:all .15s; white-space:nowrap; flex-shrink:0;
  }
  .cmd-action:hover { color:#fff; background:rgba(255,255,255,.08); border-color:rgba(255,255,255,.14); }

  /* Empty */
  .cmd-empty {
    padding: 3.5rem 1.5rem; text-align: center;
    border: 1px dashed rgba(255,255,255,.07); border-radius: 8px;
  }
  .cmd-empty p { font-size:.82rem; color:rgba(255,255,255,.2); margin-top:.4rem; margin-bottom:1.25rem; }

  /* Panel */
  .cmd-panel { display: none; }
  .cmd-panel.active { display: block; }

  @media (max-width:900px) {
    .dash-root { grid-template-columns:1fr; }
    .dash-sidebar { display:none; }
    .dash-topbar, .cmd-filters, .dash-body { padding-left:1.5rem; padding-right:1.5rem; }
    .cmd-card { grid-template-columns:1fr auto; }
    .cmd-id-block { display:none; }
  }
  @media (max-width:520px) {
    .cmd-card { grid-template-columns:1fr; gap:.6rem; }
    .cmd-meta { flex-wrap:wrap; }
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
        Boutique
        <strong>Mes commandes</strong>
      </div>
      <a href="boutique.php" class="btn btn-sm btn-primary">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
        Nouvelle commande
      </a>
    </div>

    <!-- Filter tabs -->
    <div class="cmd-filters" role="tablist">
      <button class="cmd-filter-btn active" role="tab" aria-selected="true" onclick="showPanel('toutes',this)">
        Toutes
        <span class="cmd-filter-count"><?= count($toutes) ?></span>
      </button>
      <button class="cmd-filter-btn" role="tab" aria-selected="false" onclick="showPanel('attente',this)">
        En attente
        <span class="cmd-filter-count"><?= count($en_attente) ?></span>
      </button>
      <button class="cmd-filter-btn" role="tab" aria-selected="false" onclick="showPanel('preparation',this)">
        En préparation
        <span class="cmd-filter-count"><?= count($en_preparation) ?></span>
      </button>
      <button class="cmd-filter-btn" role="tab" aria-selected="false" onclick="showPanel('livrees',this)">
        Livrées
        <span class="cmd-filter-count"><?= count($livrees) ?></span>
      </button>
      <button class="cmd-filter-btn" role="tab" aria-selected="false" onclick="showPanel('annulees',this)">
        Annulées
        <span class="cmd-filter-count"><?= count($annulees) ?></span>
      </button>
    </div>

    <div class="dash-body">

      <?php
      $panels = [
        'toutes'      => $toutes,
        'attente'     => $en_attente,
        'preparation' => $en_preparation,
        'livrees'     => $livrees,
        'annulees'    => $annulees,
      ];
      $empty_msgs = [
        'toutes'      => ['Aucune commande pour le moment.', true],
        'attente'     => ['Aucune commande en attente.', false],
        'preparation' => ['Aucune commande en préparation.', false],
        'livrees'     => ['Aucune commande livrée.', false],
        'annulees'    => ['Aucune commande annulée.', false],
      ];
      foreach ($panels as $key => $liste):
      ?>
        <div class="cmd-panel <?= $key === 'toutes' ? 'active' : '' ?>" id="panel-<?= $key ?>" role="tabpanel">
          <?php if (empty($liste)): ?>
            <div class="cmd-empty">
              <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.15)" stroke-width="1.5"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
              <p><?= $empty_msgs[$key][0] ?></p>
              <?php if ($empty_msgs[$key][1]): ?>
                <a href="boutique.php" class="btn btn-sm btn-primary">Découvrir la boutique</a>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <div class="cmd-list">
              <?php foreach ($liste as $cmd): ?>
                <div class="cmd-card">
                  <div class="cmd-id-block">
                    <span class="cmd-id-num"><?= intval($cmd['id']) ?></span>
                    <span class="cmd-id-label">N°</span>
                  </div>
                  <div class="cmd-info">
                    <div class="cmd-date-row"><?= date('d/m/Y', strtotime($cmd['cree_le'])) ?></div>
                    <div class="cmd-meta">
                      <span>
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <?= date('H:i', strtotime($cmd['cree_le'])) ?>
                      </span>
                      <span>
                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                        <?= pay_label($cmd['methode_paiement'] ?? '') ?>
                      </span>
                    </div>
                  </div>
                  <span class="<?= cmd_class($cmd['statut'] ?? '') ?>"><?= cmd_label($cmd['statut'] ?? 'en_attente') ?></span>
                  <div style="display:flex;align-items:center;gap:.75rem">
                    <span class="cmd-total"><?= number_format($cmd['total'], 2, ',', ' ') ?><small>DA</small></span>
                    <a href="confirmation_commande.php?id=<?= intval($cmd['id']) ?>&methode=<?= htmlspecialchars($cmd['methode_paiement'] ?? 'especes') ?>" class="cmd-action">Détails</a>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>

    </div><!-- /dash-body -->
  </main>

</div>

<script>
function showPanel(id, btn) {
  document.querySelectorAll('.cmd-panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.cmd-filter-btn').forEach(b => { b.classList.remove('active'); b.setAttribute('aria-selected','false'); });
  document.getElementById('panel-' + id).classList.add('active');
  btn.classList.add('active');
  btn.setAttribute('aria-selected','true');
}
</script>
</body>
</html>
