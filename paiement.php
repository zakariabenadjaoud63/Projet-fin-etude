<?php
session_start();
require_once 'connexion_bd.php';
require_once 'includes/security.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

// Récupérer le panier
$produits_panier = [];
$total_general   = 0;

if (empty($_SESSION['panier'])) { header('Location: panier.php?erreur=panier_vide'); exit(); }

$ids = array_keys($_SESSION['panier']);
$ph  = implode(',', array_fill(0, count($ids), '?'));
try {
  $stmt = $pdo->prepare("SELECT * FROM produits WHERE id IN ($ph)");
  $stmt->execute($ids);
  foreach ($stmt->fetchAll() as $p) {
    $q  = $_SESSION['panier'][$p['id']];
    $st = $p['prix'] * $q;
    $total_general += $st;
    $produits_panier[] = ['id'=>$p['id'],'nom'=>$p['nom'],'prix'=>$p['prix'],'quantite'=>$q,'sous_total'=>$st];
  }
} catch (PDOException $e) { die("Erreur : " . $e->getMessage()); }

$nb_articles = array_sum(array_column($produits_panier,'quantite'));
$activePage  = 'boutique';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MécaSpeed — Paiement</title>
  <style>
  .pay-page { background:#080808; min-height:100vh; }

  /* Topbar */
  .pay-topbar {
    background:#0a0a0a; border-bottom:1px solid rgba(255,255,255,.07); padding:1rem 0;
  }
  .pay-topbar-inner { display:flex; align-items:center; justify-content:space-between; }
  .pay-topbar-title {
    font-family:'Barlow Condensed',sans-serif; font-size:1.1rem; font-weight:900;
    text-transform:uppercase; letter-spacing:.04em; color:#fff;
  }
  .pay-steps { display:flex; align-items:center; gap:0; }
  .pay-step {
    display:flex; align-items:center; gap:.45rem;
    font-family:'Barlow Condensed',sans-serif; font-size:.7rem; font-weight:700;
    letter-spacing:.08em; text-transform:uppercase;
    color:rgba(255,255,255,.28); padding:.25rem .65rem;
  }
  .pay-step.done   { color:#4ade80; }
  .pay-step.active { color:#fff; }
  .pay-step-num {
    width:20px; height:20px; border-radius:50%; flex-shrink:0;
    display:flex; align-items:center; justify-content:center;
    font-size:.62rem; font-weight:900;
    background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.1); color:rgba(255,255,255,.3);
  }
  .pay-step.done .pay-step-num   { background:#4ade80; color:#000; border-color:#4ade80; }
  .pay-step.active .pay-step-num { background:#1a6bbd; color:#fff; border-color:#1a6bbd; }
  .pay-step-sep { color:rgba(255,255,255,.18); font-size:.65rem; padding:0 .1rem; }

  /* Grid */
  .pay-grid {
    display:grid; grid-template-columns:1fr 340px;
    gap:2rem; padding:2.5rem 0 5rem; align-items:start;
  }

  /* Method cards */
  .pay-methods { display:flex; flex-direction:column; gap:.5rem; margin-bottom:2rem; }
  .pay-method {
    display:flex; align-items:center; gap:1rem;
    background:#101010; border:1.5px solid rgba(255,255,255,.08);
    border-radius:10px; padding:1rem 1.25rem; cursor:pointer;
    transition:all .18s; user-select:none;
  }
  .pay-method:hover { border-color:rgba(26,107,189,.3); background:#141414; }
  .pay-method.selected {
    border-color:#1a6bbd; background:rgba(26,107,189,.08);
  }
  .pay-method-radio {
    width:18px; height:18px; border-radius:50%; flex-shrink:0;
    border:2px solid rgba(255,255,255,.2); display:flex; align-items:center; justify-content:center;
    transition:all .18s;
  }
  .pay-method.selected .pay-method-radio { border-color:#1a6bbd; background:#1a6bbd; }
  .pay-method.selected .pay-method-radio::after { content:''; width:7px; height:7px; border-radius:50%; background:#fff; }
  .pay-method-icon {
    width:40px; height:40px; border-radius:8px; flex-shrink:0;
    background:rgba(255,255,255,.05); border:1px solid rgba(255,255,255,.08);
    display:flex; align-items:center; justify-content:center;
  }
  .pay-method.selected .pay-method-icon { background:rgba(26,107,189,.15); border-color:rgba(26,107,189,.25); }
  .pay-method-icon svg { color:rgba(255,255,255,.4); }
  .pay-method.selected .pay-method-icon svg { color:#6aabf7; }
  .pay-method-info {}
  .pay-method-name {
    font-family:'Barlow Condensed',sans-serif; font-size:.9rem; font-weight:800;
    text-transform:uppercase; letter-spacing:.04em; color:#fff; margin-bottom:2px;
  }
  .pay-method-desc { font-size:.78rem; color:rgba(255,255,255,.35); }
  .pay-method-badge {
    margin-left:auto; flex-shrink:0;
    font-family:'Barlow Condensed',sans-serif; font-size:.62rem; font-weight:700;
    letter-spacing:.08em; text-transform:uppercase;
    background:rgba(74,222,128,.1); color:#4ade80; border:1px solid rgba(74,222,128,.2);
    padding:.15rem .55rem; border-radius:100px;
  }

  /* Form panels */
  .pay-form-panel {
    background:#101010; border:1px solid rgba(255,255,255,.08);
    border-radius:12px; padding:1.5rem; margin-bottom:1.5rem;
  }
  .pfp-title {
    font-family:'Barlow Condensed',sans-serif; font-size:.7rem; font-weight:700;
    letter-spacing:.12em; text-transform:uppercase; color:rgba(255,255,255,.28);
    margin-bottom:1.25rem; display:block;
  }

  .f-row-2 { display:grid; grid-template-columns:1fr 1fr; gap:0 1rem; }
  .f-field { margin-bottom:.9rem; }
  .f-field label {
    display:block; font-family:'Barlow Condensed',sans-serif;
    font-size:.68rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase;
    color:rgba(255,255,255,.28); margin-bottom:.4rem;
  }
  .f-field input, .f-field select {
    width:100%; background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.1);
    border-radius:8px; color:#fff; font-family:'Barlow',sans-serif; font-size:.9rem;
    padding:.72rem 1rem; outline:none; transition:border-color .18s,box-shadow .18s;
    -webkit-appearance:none;
  }
  .f-field input:focus, .f-field select:focus {
    border-color:#1a6bbd; box-shadow:0 0 0 3px rgba(26,107,189,.14);
  }
  .f-field input::placeholder { color:rgba(255,255,255,.14); }

  /* Card number special */
  .card-input-wrap { position:relative; }
  .card-input-wrap input { padding-right:3rem; letter-spacing:.08em; }
  .card-input-icon {
    position:absolute; right:.9rem; top:50%; transform:translateY(-50%);
    color:rgba(255,255,255,.25);
  }

  /* Info box */
  .pay-info-box {
    display:flex; align-items:flex-start; gap:.75rem;
    background:rgba(26,107,189,.07); border:1px solid rgba(26,107,189,.15);
    border-radius:8px; padding:.9rem 1rem; margin-bottom:1.5rem;
    font-size:.82rem; color:rgba(255,255,255,.5); line-height:1.55;
  }
  .pay-info-box svg { flex-shrink:0; color:#6aabf7; margin-top:1px; }

  /* Submit */
  .btn-pay {
    width:100%; display:flex; align-items:center; justify-content:center; gap:.6rem;
    font-family:'Barlow Condensed',sans-serif; font-size:.9rem; font-weight:700;
    letter-spacing:.06em; text-transform:uppercase;
    background:#1a6bbd; color:#fff; border:none; border-radius:8px;
    padding:.9rem; cursor:pointer; transition:all .15s;
  }
  .btn-pay:hover { background:#155fa0; box-shadow:0 6px 24px rgba(26,107,189,.4); transform:translateY(-1px); }

  /* Summary */
  .pay-summary {
    background:#101010; border:1px solid rgba(255,255,255,.08); border-radius:12px;
    overflow:hidden; position:sticky; top:calc(64px + 1rem);
  }
  .ps-head {
    padding:1.1rem 1.5rem; border-bottom:1px solid rgba(255,255,255,.07);
    background:rgba(255,255,255,.02);
    font-family:'Barlow Condensed',sans-serif; font-size:.72rem; font-weight:700;
    letter-spacing:.12em; text-transform:uppercase; color:rgba(255,255,255,.3);
  }
  .ps-body { padding:1.25rem 1.5rem; }
  .ps-item {
    display:flex; justify-content:space-between; align-items:center;
    padding:.5rem 0; border-bottom:1px solid rgba(255,255,255,.04);
    font-size:.83rem;
  }
  .ps-item:last-of-type { border-bottom:none; }
  .ps-item-name { color:rgba(255,255,255,.5); }
  .ps-item-name span { color:rgba(255,255,255,.3); }
  .ps-item-price { color:rgba(255,255,255,.7); font-weight:600; }
  .ps-divider { height:1px; background:rgba(255,255,255,.08); margin:.75rem 0; }
  .ps-total {
    display:flex; justify-content:space-between; align-items:baseline; margin-bottom:1.5rem;
  }
  .ps-total-label {
    font-family:'Barlow Condensed',sans-serif; font-size:.82rem; font-weight:800;
    text-transform:uppercase; color:#fff;
  }
  .ps-total-val {
    font-family:'Barlow Condensed',sans-serif; font-size:1.5rem; font-weight:900; color:#fff;
  }
  .ps-total-val small { font-size:.7rem; color:rgba(255,255,255,.3); margin-left:2px; }
  .ps-edit {
    display:block; text-align:center; margin-top:.75rem;
    font-family:'Barlow Condensed',sans-serif; font-size:.72rem; font-weight:700;
    letter-spacing:.07em; text-transform:uppercase; color:rgba(255,255,255,.25);
    transition:color .15s;
  }
  .ps-edit:hover { color:rgba(255,255,255,.6); }

  /* Section title */
  .pay-section-title {
    font-family:'Barlow Condensed',sans-serif; font-size:.7rem; font-weight:700;
    letter-spacing:.14em; text-transform:uppercase; color:rgba(255,255,255,.3);
    margin-bottom:1rem; display:block;
  }

  @media(max-width:900px){
    .pay-grid { grid-template-columns:1fr; }
    .pay-summary { position:static; }
    .pay-steps { display:none; }
    .f-row-2 { grid-template-columns:1fr; }
    .pay-topbar-inner { justify-content:center; }
    .pay-topbar-title { display:none; }
  }
  </style>
</head>
<body class="pay-page">
<?php require __DIR__ . '/includes/public-nav.php'; ?>

<main id="contenu-principal">

  <!-- TOPBAR -->
  <div class="pay-topbar">
    <div class="container pay-topbar-inner">
      <div class="pay-topbar-title">Paiement</div>
      <div class="pay-steps">
        <div class="pay-step done"><span class="pay-step-num">✓</span> Panier</div>
        <span class="pay-step-sep">›</span>
        <div class="pay-step active"><span class="pay-step-num">2</span> Paiement</div>
        <span class="pay-step-sep">›</span>
        <div class="pay-step"><span class="pay-step-num">3</span> Confirmation</div>
      </div>
    </div>
  </div>

  <div style="background:#080808;padding:0">
    <div class="container pay-grid">

      <!-- LEFT: Payment form -->
      <div>

        <!-- Method selection -->
        <span class="pay-section-title">Mode de paiement</span>
        <div class="pay-methods" role="radiogroup" aria-label="Choisir un mode de paiement">

          <div class="pay-method selected" onclick="selectMethod('especes',this)" data-method="especes">
            <div class="pay-method-radio"></div>
            <div class="pay-method-icon">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2"/><path d="M6 12h.01M18 12h.01"/></svg>
            </div>
            <div class="pay-method-info">
              <div class="pay-method-name">Espèces à l'atelier</div>
              <div class="pay-method-desc">Payez en cash lors du retrait de votre commande</div>
            </div>
            <span class="pay-method-badge">Recommandé</span>
          </div>

          <div class="pay-method" onclick="selectMethod('cib',this)" data-method="cib">
            <div class="pay-method-radio"></div>
            <div class="pay-method-icon">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
            </div>
            <div class="pay-method-info">
              <div class="pay-method-name">Carte CIB / EDAHABIA</div>
              <div class="pay-method-desc">Carte bancaire algérienne interbancaire</div>
            </div>
          </div>

          <div class="pay-method" onclick="selectMethod('virement',this)" data-method="virement">
            <div class="pay-method-radio"></div>
            <div class="pay-method-icon">
              <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg>
            </div>
            <div class="pay-method-info">
              <div class="pay-method-name">Virement bancaire</div>
              <div class="pay-method-desc">Paiement par virement CCP ou virement bancaire</div>
            </div>
          </div>

        </div>

        <!-- Espèces info -->
        <div id="form-especes">
          <div class="pay-info-box">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <div>
              Votre commande sera préparée et mise de côté à l'atelier. Présentez votre <strong style="color:#fff">numéro de commande</strong> à l'accueil pour régler en espèces.
            </div>
          </div>
          <div class="pay-form-panel">
            <span class="pfp-title">Coordonnées de livraison</span>
            <div class="f-field">
              <label>Nom complet</label>
              <input type="text" placeholder="Votre nom" value="<?= htmlspecialchars($_SESSION['nom']??'') ?>">
            </div>
            <div class="f-row-2">
              <div class="f-field">
                <label>Téléphone</label>
                <input type="tel" id="tel-especes" placeholder="0550 000 000">
              </div>
              <div class="f-field">
                <label>Garage de retrait</label>
                <select id="garage-especes">
                  <option value="1">MécaSpeed — Draa Ben Khedda</option>
                  <option value="2">MécaSpeed — Tizi Ouzou</option>
                </select>
              </div>
            </div>
          </div>
        </div>

        <!-- CIB form -->
        <div id="form-cib" style="display:none">
          <div class="pay-form-panel">
            <span class="pfp-title">Informations carte</span>
            <div class="f-field">
              <label>Numéro de carte</label>
              <div class="card-input-wrap">
                <input type="text" id="card-num" placeholder="0000 0000 0000 0000" maxlength="19"
                       oninput="formatCard(this)" inputmode="numeric">
                <svg class="card-input-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
              </div>
            </div>
            <div class="f-row-2">
              <div class="f-field">
                <label>Date d'expiration</label>
                <input type="text" id="card-exp" placeholder="MM/AA" maxlength="5" oninput="formatExp(this)">
              </div>
              <div class="f-field">
                <label>Code PIN</label>
                <input type="password" id="card-pin" placeholder="••••" maxlength="4" inputmode="numeric">
              </div>
            </div>
            <div class="f-field">
              <label>Titulaire de la carte</label>
              <input type="text" placeholder="Nom sur la carte" value="<?= htmlspecialchars(strtoupper($_SESSION['nom']??'')) ?>">
            </div>
          </div>
          <div class="pay-info-box">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            <div>Transaction sécurisée. Vos données bancaires sont chiffrées et ne sont jamais stockées sur nos serveurs.</div>
          </div>
        </div>

        <!-- Virement form -->
        <div id="form-virement" style="display:none">
          <div class="pay-info-box">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
            <div>
              Effectuez un virement vers le compte ci-dessous, en mentionnant votre <strong style="color:#fff">nom complet</strong> en référence. Votre commande sera traitée à réception du virement.
            </div>
          </div>
          <div class="pay-form-panel">
            <span class="pfp-title">Coordonnées bancaires MécaSpeed</span>
            <div class="f-field"><label>Banque</label><input type="text" value="CPA — Crédit Populaire d'Algérie" readonly style="color:rgba(255,255,255,.6)"></div>
            <div class="f-field"><label>RIB / Numéro de compte</label><input type="text" value="007 00040 0000040001 95" readonly style="color:#6aabf7;font-family:'Barlow Condensed',sans-serif;font-size:1rem;font-weight:700;letter-spacing:.05em" onclick="this.select()"></div>
            <div class="f-field"><label>Référence du virement</label><input type="text" value="CMD-<?= $_SESSION['user_id'] ?>-<?= date('Ymd') ?>" readonly style="color:rgba(255,255,255,.6)"></div>
          </div>
        </div>

        <!-- Confirm button -->
        <form action="valider_commande.php" method="POST" id="pay-form">
          <?= csrf_field() ?>
          <input type="hidden" name="methode_paiement" id="methode-input" value="especes">
          <button type="submit" class="btn-pay" id="btn-pay">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="20 6 9 17 4 12"/></svg>
            <span id="btn-pay-label">Confirmer la commande</span>
          </button>
        </form>

      </div>

      <!-- RIGHT: Order summary -->
      <aside class="pay-summary">
        <div class="ps-head">Votre commande</div>
        <div class="ps-body">
          <?php foreach ($produits_panier as $item): ?>
            <div class="ps-item">
              <span class="ps-item-name"><?= htmlspecialchars($item['nom']) ?> <span>×<?= $item['quantite'] ?></span></span>
              <span class="ps-item-price"><?= number_format($item['sous_total'],2,',',' ') ?> DA</span>
            </div>
          <?php endforeach; ?>
          <div class="ps-divider"></div>
          <div class="ps-total">
            <span class="ps-total-label">Total</span>
            <span class="ps-total-val"><?= number_format($total_general,2,',',' ') ?><small>DA</small></span>
          </div>
          <a href="panier.php" class="ps-edit">← Modifier le panier</a>
        </div>
      </aside>

    </div>
  </div>

</main>

<script>
const BTN_LABELS = {
  especes:  'Confirmer la commande',
  cib:      'Payer par carte',
  virement: 'J\'ai effectué le virement',
};

function selectMethod(method, el) {
  document.querySelectorAll('.pay-method').forEach(m => m.classList.remove('selected'));
  el.classList.add('selected');
  document.getElementById('methode-input').value = method;
  document.getElementById('btn-pay-label').textContent = BTN_LABELS[method] || 'Confirmer';

  ['especes','cib','virement'].forEach(m => {
    const panel = document.getElementById('form-' + m);
    if (panel) panel.style.display = m === method ? '' : 'none';
  });
}

// Format card number with spaces
function formatCard(input) {
  let val = input.value.replace(/\D/g,'').substring(0,16);
  input.value = val.replace(/(.{4})/g,'$1 ').trim();
}

// Format expiry MM/AA
function formatExp(input) {
  let val = input.value.replace(/\D/g,'');
  if (val.length >= 2) val = val.substring(0,2) + '/' + val.substring(2,4);
  input.value = val;
}
</script>
</body>
</html>
