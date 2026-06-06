<?php
session_start();
require_once 'connexion_bd.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

$commande_id = intval($_GET['id']   ?? 0);
$methode     = $_GET['methode']      ?? 'especes';
$commande    = null;
$lignes      = [];

if ($commande_id > 0) {
  $stmt = $pdo->prepare("SELECT * FROM commandes WHERE id=:id AND utilisateur_id=:uid LIMIT 1");
  $stmt->execute([':id'=>$commande_id,':uid'=>$_SESSION['user_id']]);
  $commande = $stmt->fetch(PDO::FETCH_ASSOC);
  if ($commande) {
    $stmt_l = $pdo->prepare("SELECT cl.*,p.nom,p.image FROM commande_lignes cl JOIN produits p ON cl.produit_id=p.id WHERE cl.commande_id=:id");
    $stmt_l->execute([':id'=>$commande_id]);
    $lignes = $stmt_l->fetchAll(PDO::FETCH_ASSOC);
  }
}

$methode_labels = [
  'especes'  => 'Espèces à l\'atelier',
  'cib'      => 'Carte CIB / EDAHABIA',
  'virement' => 'Virement bancaire',
];
$activePage = 'boutique';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MécaSpeed — Confirmation commande</title>
  <style>
  .conf-page { background:#080808; min-height:100vh; }

  /* Topbar */
  .conf-topbar {
    background:#0a0a0a; border-bottom:1px solid rgba(255,255,255,.07); padding:1rem 0;
  }
  .conf-topbar-inner { display:flex; align-items:center; justify-content:space-between; }
  .conf-topbar-title {
    font-family:'Barlow Condensed',sans-serif; font-size:1.1rem; font-weight:900;
    text-transform:uppercase; letter-spacing:.04em; color:#fff;
  }
  .conf-steps { display:flex; align-items:center; gap:0; }
  .conf-step {
    display:flex; align-items:center; gap:.45rem;
    font-family:'Barlow Condensed',sans-serif; font-size:.7rem; font-weight:700;
    letter-spacing:.08em; text-transform:uppercase;
    color:rgba(255,255,255,.28); padding:.25rem .65rem;
  }
  .conf-step.done { color:#4ade80; }
  .conf-step-num {
    width:20px; height:20px; border-radius:50%; flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    font-size:.62rem; font-weight:900;
    background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.1); color:rgba(255,255,255,.3);
  }
  .conf-step.done .conf-step-num { background:#4ade80; color:#000; border-color:#4ade80; }
  .conf-step-sep { color:rgba(255,255,255,.18); font-size:.65rem; padding:0 .1rem; }

  /* Grid */
  .conf-grid {
    display:grid; grid-template-columns:1fr 340px;
    gap:2rem; padding:2.5rem 0 5rem; align-items:start;
  }

  /* Success banner */
  .conf-banner {
    background:rgba(74,222,128,.06); border:1px solid rgba(74,222,128,.2);
    border-radius:12px; padding:2rem 2rem 1.75rem; margin-bottom:1.5rem;
    display:flex; align-items:flex-start; gap:1.25rem;
  }
  .conf-banner-icon {
    width:52px; height:52px; border-radius:14px; flex-shrink:0;
    background:rgba(74,222,128,.15); border:1px solid rgba(74,222,128,.3);
    display:flex; align-items:center; justify-content:center;
  }
  .conf-banner-icon svg { color:#4ade80; }
  .conf-banner-title {
    font-family:'Barlow Condensed',sans-serif; font-size:1.4rem; font-weight:900;
    text-transform:uppercase; color:#fff; margin-bottom:.35rem;
  }
  .conf-banner-sub { font-size:.88rem; color:rgba(255,255,255,.45); line-height:1.6; }
  .conf-order-num {
    display:inline-flex; align-items:center; gap:.45rem; margin-top:.75rem;
    font-family:'Barlow Condensed',sans-serif; font-size:.78rem; font-weight:700;
    letter-spacing:.08em; text-transform:uppercase;
    background:rgba(255,255,255,.06); border:1px solid rgba(255,255,255,.1);
    padding:.3rem .85rem; border-radius:100px; color:rgba(255,255,255,.6);
  }
  .conf-order-num strong { color:#fff; }

  /* Steps card */
  .conf-next-steps {
    background:#101010; border:1px solid rgba(255,255,255,.08);
    border-radius:12px; overflow:hidden; margin-bottom:1.5rem;
  }
  .cns-head {
    padding:1.1rem 1.5rem; border-bottom:1px solid rgba(255,255,255,.07);
    font-family:'Barlow Condensed',sans-serif; font-size:.7rem; font-weight:700;
    letter-spacing:.12em; text-transform:uppercase; color:rgba(255,255,255,.28);
  }
  .cns-steps { padding:1.25rem 1.5rem; display:flex; flex-direction:column; gap:1.1rem; }
  .cns-step { display:flex; gap:1rem; align-items:flex-start; }
  .cns-step-num {
    width:28px; height:28px; border-radius:8px; flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    font-family:'Barlow Condensed',sans-serif; font-size:.78rem; font-weight:900;
  }
  .cns-step-num.done  { background:rgba(74,222,128,.15); color:#4ade80; border:1px solid rgba(74,222,128,.25); }
  .cns-step-num.todo  { background:rgba(26,107,189,.12); color:#6aabf7; border:1px solid rgba(26,107,189,.2); }
  .cns-step-info {}
  .cns-step-title {
    font-family:'Barlow Condensed',sans-serif; font-size:.85rem; font-weight:800;
    text-transform:uppercase; color:#fff; margin-bottom:2px;
  }
  .cns-step-desc { font-size:.78rem; color:rgba(255,255,255,.35); line-height:1.45; }

  /* Actions */
  .conf-actions { display:flex; gap:.75rem; flex-wrap:wrap; }

  /* Right panel */
  .conf-summary {
    background:#101010; border:1px solid rgba(255,255,255,.08); border-radius:12px;
    overflow:hidden; position:sticky; top:calc(64px + 1rem);
  }
  .css-head {
    padding:1.1rem 1.5rem; border-bottom:1px solid rgba(255,255,255,.07);
    background:rgba(255,255,255,.02);
  }
  .css-head-title {
    font-family:'Barlow Condensed',sans-serif; font-size:.72rem; font-weight:700;
    letter-spacing:.12em; text-transform:uppercase; color:rgba(255,255,255,.3);
  }
  .css-head-num {
    font-family:'Barlow Condensed',sans-serif; font-size:1rem; font-weight:900;
    color:#6aabf7; margin-top:2px;
  }
  .css-body { padding:1.25rem 1.5rem; }

  .css-item {
    display:flex; align-items:center; gap:.85rem;
    padding:.6rem 0; border-bottom:1px solid rgba(255,255,255,.04);
  }
  .css-item:last-of-type { border-bottom:none; }
  .css-item-name {
    font-size:.83rem; color:rgba(255,255,255,.6); flex:1; min-width:0;
    overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
  }
  .css-item-qty { font-size:.75rem; color:rgba(255,255,255,.3); }
  .css-item-price { font-size:.83rem; color:rgba(255,255,255,.7); font-weight:600; white-space:nowrap; }

  .css-divider { height:1px; background:rgba(255,255,255,.08); margin:.85rem 0; }

  .css-info-row {
    display:flex; justify-content:space-between; align-items:center;
    padding:.4rem 0; font-size:.8rem;
  }
  .css-info-key { color:rgba(255,255,255,.3); font-family:'Barlow Condensed',sans-serif; font-size:.68rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase; }
  .css-info-val { color:rgba(255,255,255,.7); }

  .css-total-row {
    display:flex; justify-content:space-between; align-items:baseline;
    padding:.75rem 0 0; border-top:1px solid rgba(255,255,255,.08); margin-top:.5rem;
  }
  .css-total-label { font-family:'Barlow Condensed',sans-serif; font-size:.82rem; font-weight:800; text-transform:uppercase; color:#fff; }
  .css-total-val { font-family:'Barlow Condensed',sans-serif; font-size:1.4rem; font-weight:900; color:#fff; }
  .css-total-val small { font-size:.65rem; color:rgba(255,255,255,.3); }

  /* Not found */
  .conf-notfound {
    grid-column:1/-1; padding:4rem; text-align:center;
    background:#101010; border:1px solid rgba(255,255,255,.07); border-radius:12px;
  }
  .conf-notfound h3 { font-family:'Barlow Condensed',sans-serif; font-size:1.2rem; font-weight:800; text-transform:uppercase; color:rgba(255,255,255,.3); margin-bottom:.5rem; }
  .conf-notfound p { font-size:.85rem; color:rgba(255,255,255,.2); margin-bottom:1.25rem; }

  @media(max-width:900px){
    .conf-grid { grid-template-columns:1fr; }
    .conf-summary { position:static; }
    .conf-steps { display:none; }
  }
  </style>
</head>
<body class="conf-page">
<?php require __DIR__ . '/includes/public-nav.php'; ?>

<main id="contenu-principal">

  <!-- TOPBAR -->
  <div class="conf-topbar">
    <div class="container conf-topbar-inner">
      <div class="conf-topbar-title">Confirmation</div>
      <div class="conf-steps">
        <div class="conf-step done"><span class="conf-step-num">✓</span> Panier</div>
        <span class="conf-step-sep">›</span>
        <div class="conf-step done"><span class="conf-step-num">✓</span> Paiement</div>
        <span class="conf-step-sep">›</span>
        <div class="conf-step done"><span class="conf-step-num">✓</span> Confirmation</div>
      </div>
    </div>
  </div>

  <div style="background:#080808;padding:0">
    <div class="container conf-grid">

      <?php if (!$commande): ?>

        <div class="conf-notfound">
          <h3>Commande introuvable</h3>
          <p>Cette commande n'existe pas ou ne vous appartient pas.</p>
          <a href="boutique.php" class="btn btn-primary">Boutique</a>
        </div>

      <?php else: ?>

        <!-- LEFT -->
        <div>

          <!-- Banner -->
          <div class="conf-banner">
            <div class="conf-banner-icon">
              <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <div>
              <div class="conf-banner-title">Commande confirmée !</div>
              <div class="conf-banner-sub">
                Merci pour votre commande. Elle est enregistrée et sera préparée dans les plus brefs délais.
              </div>
              <div class="conf-order-num">
                Numéro de commande — <strong>#<?= intval($commande['id']) ?></strong>
              </div>
            </div>
          </div>

          <!-- Prochaines étapes -->
          <div class="conf-next-steps">
            <div class="cns-head">Prochaines étapes</div>
            <div class="cns-steps">

              <div class="cns-step">
                <div class="cns-step-num done">✓</div>
                <div class="cns-step-info">
                  <div class="cns-step-title">Commande enregistrée</div>
                  <div class="cns-step-desc">Votre commande #<?= intval($commande['id']) ?> a été créée le <?= date('d/m/Y à H:i', strtotime($commande['cree_le'])) ?>.</div>
                </div>
              </div>

              <?php if ($methode === 'virement'): ?>
                <div class="cns-step">
                  <div class="cns-step-num todo">2</div>
                  <div class="cns-step-info">
                    <div class="cns-step-title">Confirmer votre virement</div>
                    <div class="cns-step-desc">Effectuez le virement de <?= number_format($commande['total'],2,',',' ') ?> DA en mentionnant votre nom en référence.</div>
                  </div>
                </div>
              <?php endif; ?>

              <div class="cns-step">
                <div class="cns-step-num todo"><?= $methode === 'virement' ? '3' : '2' ?></div>
                <div class="cns-step-info">
                  <div class="cns-step-title">Préparation de votre commande</div>
                  <div class="cns-step-desc">Nos équipes préparent vos articles dans un délai de 24 à 48h.</div>
                </div>
              </div>

              <div class="cns-step">
                <div class="cns-step-num todo"><?= $methode === 'virement' ? '4' : '3' ?></div>
                <div class="cns-step-info">
                  <div class="cns-step-title">Retrait à l'atelier</div>
                  <div class="cns-step-desc">
                    Présentez le numéro <strong style="color:#fff">#<?= intval($commande['id']) ?></strong> à l'accueil.
                    <?php if ($methode === 'especes'): ?>
                      Réglez <?= number_format($commande['total'],2,',',' ') ?> DA en espèces à ce moment-là.
                    <?php elseif ($methode === 'cib'): ?>
                      Paiement déjà effectué par carte.
                    <?php endif; ?>
                  </div>
                </div>
              </div>

            </div>
          </div>

          <!-- Actions -->
          <div class="conf-actions">
            <a href="boutique.php"       class="btn btn-ghost">Continuer les achats</a>
            <a href="espace_client.php"  class="btn btn-outline">Mon espace</a>
            <a href="calendrier.php"     class="btn btn-outline">Prendre un RDV</a>
          </div>

        </div>

        <!-- RIGHT -->
        <aside class="conf-summary">
          <div class="css-head">
            <div class="css-head-title">Récapitulatif</div>
            <div class="css-head-num">#<?= intval($commande['id']) ?></div>
          </div>
          <div class="css-body">

            <?php foreach ($lignes as $l): ?>
              <div class="css-item">
                <div class="css-item-name"><?= htmlspecialchars($l['nom']) ?></div>
                <div class="css-item-qty">×<?= intval($l['quantite']) ?></div>
                <div class="css-item-price"><?= number_format($l['prix_unitaire']*$l['quantite'],2,',',' ') ?> DA</div>
              </div>
            <?php endforeach; ?>

            <div class="css-divider"></div>

            <div class="css-info-row">
              <span class="css-info-key">Statut</span>
              <span class="css-info-val" style="color:#4ade80;font-family:'Barlow Condensed',sans-serif;font-size:.75rem;font-weight:700;letter-spacing:.07em;text-transform:uppercase">
                ● <?= htmlspecialchars($commande['statut']) ?>
              </span>
            </div>
            <div class="css-info-row">
              <span class="css-info-key">Mode de paiement</span>
              <span class="css-info-val"><?= htmlspecialchars($methode_labels[$methode] ?? $methode) ?></span>
            </div>
            <div class="css-info-row">
              <span class="css-info-key">Date</span>
              <span class="css-info-val"><?= date('d/m/Y', strtotime($commande['cree_le'])) ?></span>
            </div>

            <div class="css-total-row">
              <span class="css-total-label">Total</span>
              <span class="css-total-val"><?= number_format($commande['total'],2,',',' ') ?><small> DA</small></span>
            </div>

          </div>
        </aside>

      <?php endif; ?>

    </div>
  </div>

</main>

</body>
</html>
