<?php
session_start();
require_once 'connexion_bd.php';
require_once 'includes/security.php';

$activePage = 'boutique';
$produits_panier = [];
$total_general   = 0;

if (!empty($_SESSION['panier'])) {
  $ids = array_keys($_SESSION['panier']);
  $ph  = implode(',', array_fill(0, count($ids), '?'));
  try {
    $stmt = $pdo->prepare("SELECT * FROM produits WHERE id IN ($ph)");
    $stmt->execute($ids);
    foreach ($stmt->fetchAll() as $p) {
      $q  = $_SESSION['panier'][$p['id']];
      $st = $p['prix'] * $q;
      $total_general += $st;
      $produits_panier[] = ['id'=>$p['id'],'nom'=>$p['nom'],'prix'=>$p['prix'],
                            'image'=>$p['image']??'','quantite'=>$q,'sous_total'=>$st];
    }
  } catch (PDOException $e) { die("Erreur : " . $e->getMessage()); }
}

$nb_articles = array_sum(array_column($produits_panier,'quantite'));

function img_src2($image) {
  if (!$image || $image === 'placeholder.png') return null;
  if (file_exists(__DIR__.'/image/'.$image)) return 'image/'.$image;
  if (file_exists(__DIR__.'/'.$image)) return $image;
  return null;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MécaSpeed — Panier</title>
  <style>
  /* ── Checkout steps bar ── */
  .cart-topbar{
    background:#0a0a0a;border-bottom:1px solid rgba(255,255,255,.07);
    padding:1rem 0;
  }
  .cart-topbar-inner{
    display:flex;align-items:center;justify-content:space-between;
  }
  .cart-topbar-title{
    font-family:'Barlow Condensed',sans-serif;font-size:1.1rem;font-weight:900;
    text-transform:uppercase;letter-spacing:.04em;color:#fff;
  }
  .checkout-steps{display:flex;align-items:center;gap:0;}
  .co-step{
    display:flex;align-items:center;gap:.45rem;
    font-family:'Barlow Condensed',sans-serif;font-size:.7rem;font-weight:700;
    letter-spacing:.08em;text-transform:uppercase;
    color:rgba(255,255,255,.28);padding:.25rem .65rem;
  }
  .co-step.active{color:#fff;}
  .co-step.done{color:#4ade80;}
  .co-step-n{
    width:20px;height:20px;border-radius:50%;flex-shrink:0;
    display:flex;align-items:center;justify-content:center;
    font-size:.62rem;font-weight:900;
    background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.1);color:rgba(255,255,255,.3);
  }
  .co-step.active .co-step-n{background:#1a6bbd;color:#fff;border-color:#1a6bbd;}
  .co-step.done   .co-step-n{background:#4ade80;color:#000;border-color:#4ade80;}
  .co-sep{color:rgba(255,255,255,.18);font-size:.65rem;padding:0 .1rem;}

  /* ── Layout ── */
  .co-layout{
    display:grid;grid-template-columns:1fr 380px;
    min-height:calc(100vh - 64px - 57px);
  }

  /* LEFT */
  .co-left{
    padding:2.5rem 3rem 4rem;
    border-right:1px solid rgba(255,255,255,.07);
    overflow-y:auto;
  }

  .co-section-title{
    font-family:'Barlow Condensed',sans-serif;font-size:.68rem;font-weight:700;
    letter-spacing:.14em;text-transform:uppercase;color:rgba(255,255,255,.28);
    margin-bottom:1.1rem;display:flex;align-items:center;justify-content:space-between;
  }
  .co-clear-btn{
    font-family:'Barlow Condensed',sans-serif;font-size:.65rem;font-weight:700;
    letter-spacing:.07em;text-transform:uppercase;
    color:rgba(248,113,113,.45);background:none;border:none;cursor:pointer;transition:color .15s;
  }
  .co-clear-btn:hover{color:#f87171;}

  /* Item row */
  .cart-item{
    display:grid;grid-template-columns:64px 1fr auto;
    gap:1rem;align-items:center;
    padding:1rem 0;
    border-bottom:1px solid rgba(255,255,255,.05);
  }
  .cart-item:last-of-type{border-bottom:none;}

  .ci-img{
    width:64px;height:64px;border-radius:8px;overflow:hidden;
    background:#1a1a1a;border:1px solid rgba(255,255,255,.07);
    display:flex;align-items:center;justify-content:center;flex-shrink:0;
  }
  .ci-img img{width:100%;height:100%;object-fit:cover;}
  .ci-img svg{color:rgba(255,255,255,.12);}

  .ci-name{
    font-family:'Barlow Condensed',sans-serif;font-size:.9rem;font-weight:800;
    text-transform:uppercase;letter-spacing:.03em;color:#fff;margin-bottom:.25rem;
  }
  .ci-price{font-size:.78rem;color:rgba(255,255,255,.32);}

  .ci-right{display:flex;align-items:center;gap:.85rem;flex-shrink:0;}

  .qty-ctrl{
    display:flex;align-items:center;
    background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.1);border-radius:6px;
  }
  .qty-btn{
    width:28px;height:28px;display:flex;align-items:center;justify-content:center;
    background:none;border:none;color:rgba(255,255,255,.4);cursor:pointer;
    font-size:1rem;font-weight:700;transition:all .15s;
  }
  .qty-btn:hover{color:#fff;background:rgba(255,255,255,.07);}
  .qty-val{
    font-family:'Barlow Condensed',sans-serif;font-size:.88rem;font-weight:800;
    color:#fff;min-width:22px;text-align:center;
  }

  .ci-sub{
    font-family:'Barlow Condensed',sans-serif;font-size:1rem;font-weight:900;
    color:#fff;min-width:100px;text-align:right;
  }
  .ci-sub small{font-size:.65rem;color:rgba(255,255,255,.3);margin-left:2px;}

  .ci-del{
    width:26px;height:26px;border-radius:5px;
    display:flex;align-items:center;justify-content:center;
    background:none;border:1px solid rgba(248,113,113,.15);
    color:rgba(248,113,113,.4);cursor:pointer;transition:all .15s;
  }
  .ci-del:hover{background:rgba(248,113,113,.1);color:#f87171;border-color:rgba(248,113,113,.3);}

  /* Empty */
  .co-empty{
    display:flex;flex-direction:column;align-items:center;justify-content:center;
    min-height:400px;text-align:center;gap:1rem;
  }
  .co-empty-icon{
    width:60px;height:60px;border-radius:14px;
    background:rgba(255,255,255,.05);border:1px solid rgba(255,255,255,.07);
    display:flex;align-items:center;justify-content:center;
  }
  .co-empty-icon svg{color:rgba(255,255,255,.2);}
  .co-empty h3{font-family:'Barlow Condensed',sans-serif;font-size:1.1rem;font-weight:800;text-transform:uppercase;color:rgba(255,255,255,.3);}
  .co-empty p{font-size:.84rem;color:rgba(255,255,255,.22);}

  /* Continue link */
  .co-continue{
    display:inline-flex;align-items:center;gap:.4rem;margin-top:1.5rem;
    font-family:'Barlow Condensed',sans-serif;font-size:.72rem;font-weight:700;
    letter-spacing:.07em;text-transform:uppercase;color:rgba(255,255,255,.28);
    transition:color .15s;
  }
  .co-continue:hover{color:rgba(255,255,255,.65);}

  /* RIGHT — Summary */
  .co-right{
    background:#0d0d0d;padding:2.5rem 2rem 4rem;
    display:flex;flex-direction:column;
  }
  .co-right-title{
    font-family:'Barlow Condensed',sans-serif;font-size:.68rem;font-weight:700;
    letter-spacing:.14em;text-transform:uppercase;color:rgba(255,255,255,.28);
    margin-bottom:1.5rem;
  }

  /* Summary lines */
  .sum-line{
    display:flex;justify-content:space-between;align-items:center;
    padding:.5rem 0;border-bottom:1px solid rgba(255,255,255,.04);
    font-size:.83rem;
  }
  .sum-line:last-of-type{border-bottom:none;}
  .sum-line-name{color:rgba(255,255,255,.45);display:flex;align-items:center;gap:.5rem;}
  .sum-line-qty{font-size:.72rem;color:rgba(255,255,255,.25);background:rgba(255,255,255,.06);padding:.1rem .45rem;border-radius:4px;}
  .sum-line-price{color:rgba(255,255,255,.7);font-weight:600;}

  .sum-divider{height:1px;background:rgba(255,255,255,.07);margin:1rem 0;}

  .sum-total{
    display:flex;justify-content:space-between;align-items:baseline;
    margin-bottom:.4rem;
  }
  .sum-total-label{font-family:'Barlow Condensed',sans-serif;font-size:.82rem;font-weight:800;text-transform:uppercase;color:#fff;}
  .sum-total-val{font-family:'Barlow Condensed',sans-serif;font-size:1.65rem;font-weight:900;color:#fff;line-height:1;}
  .sum-total-val small{font-size:.7rem;color:rgba(255,255,255,.3);margin-left:2px;}

  .sum-articles{font-size:.75rem;color:rgba(255,255,255,.22);margin-bottom:1.75rem;}

  /* Checkout button */
  .btn-to-pay{
    display:flex;align-items:center;justify-content:center;gap:.6rem;
    font-family:'Barlow Condensed',sans-serif;font-size:.88rem;font-weight:700;
    letter-spacing:.06em;text-transform:uppercase;
    background:#1a6bbd;color:#fff;border:none;border-radius:8px;
    padding:.9rem 1.5rem;cursor:pointer;transition:all .15s;
    text-decoration:none;width:100%;
  }
  .btn-to-pay:hover{background:#155fa0;box-shadow:0 6px 28px rgba(26,107,189,.4);transform:translateY(-1px);}

  /* Trust */
  .sum-trust{display:flex;flex-direction:column;gap:.55rem;margin-top:1.25rem;}
  .sum-trust-item{
    display:flex;align-items:center;gap:.55rem;
    font-size:.75rem;color:rgba(255,255,255,.24);
  }
  .sum-trust-item svg{flex-shrink:0;color:rgba(255,255,255,.2);}

  @media(max-width:820px){
    .co-layout{grid-template-columns:1fr;}
    .co-left{padding:1.5rem;border-right:none;border-bottom:1px solid rgba(255,255,255,.07);}
    .co-right{padding:1.5rem;}
    .checkout-steps{display:none;}
  }
  </style>
</head>
<body>
<?php require __DIR__ . '/includes/public-nav.php'; ?>

<!-- CHECKOUT STEPS BAR -->
<div class="cart-topbar">
  <div class="container cart-topbar-inner">
    <div class="cart-topbar-title">Panier</div>
    <div class="checkout-steps">
      <div class="co-step active"><span class="co-step-n">1</span>Panier</div>
      <span class="co-sep">›</span>
      <div class="co-step"><span class="co-step-n">2</span>Paiement</div>
      <span class="co-sep">›</span>
      <div class="co-step"><span class="co-step-n">3</span>Confirmation</div>
    </div>
    <div style="width:80px"></div>
  </div>
</div>

<main id="contenu-principal">
  <div class="co-layout">

    <!-- LEFT : items -->
    <div class="co-left">

      <?php if (empty($produits_panier)): ?>
        <div class="co-empty">
          <div class="co-empty-icon">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
          </div>
          <h3>Panier vide</h3>
          <p>Vous n'avez pas encore ajouté d'articles.</p>
          <a href="boutique.php" class="btn btn-primary">Voir la boutique</a>
        </div>

      <?php else: ?>
        <div class="co-section-title">
          <span><?= count($produits_panier) ?> produit<?= count($produits_panier)>1?'s':'' ?> · <?= $nb_articles ?> article<?= $nb_articles>1?'s':'' ?></span>
          <form action="vider_panier.php" method="POST" style="display:inline">
            <?= csrf_field() ?>
            <button type="submit" class="co-clear-btn">Vider le panier</button>
          </form>
        </div>

        <?php foreach ($produits_panier as $item): ?>
          <div class="cart-item" id="item-<?= $item['id'] ?>">

            <div class="ci-img">
              <?php $src = img_src2($item['image']); ?>
              <?php if ($src): ?>
                <img src="<?= htmlspecialchars($src) ?>" alt="<?= htmlspecialchars($item['nom']) ?>">
              <?php else: ?>
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/></svg>
              <?php endif; ?>
            </div>

            <div>
              <div class="ci-name"><?= htmlspecialchars($item['nom']) ?></div>
              <div class="ci-price"><?= number_format($item['prix'],2,',',' ') ?> DA / unité</div>
            </div>

            <div class="ci-right">
              <div class="qty-ctrl">
                <button type="button" class="qty-btn" onclick="changeQty(<?= $item['id'] ?>,-1)">−</button>
                <span class="qty-val" id="qty-<?= $item['id'] ?>"><?= $item['quantite'] ?></span>
                <button type="button" class="qty-btn" onclick="changeQty(<?= $item['id'] ?>,+1)">+</button>
              </div>
              <div class="ci-sub" id="sub-<?= $item['id'] ?>"><?= number_format($item['sous_total'],2,',',' ') ?><small>DA</small></div>
              <button type="button" class="ci-del" onclick="removeItem(<?= $item['id'] ?>)" aria-label="Supprimer">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>

            <!-- Hidden update form -->
            <form action="mettre_a_jour_panier.php" method="POST" id="upd-<?= $item['id'] ?>" style="display:none">
              <?= csrf_field() ?>
              <input type="hidden" name="produit_id" value="<?= $item['id'] ?>">
              <input type="hidden" name="quantite"   id="upd-qty-<?= $item['id'] ?>" value="<?= $item['quantite'] ?>">
            </form>
            <form action="mettre_a_jour_panier.php" method="POST" id="rm-<?= $item['id'] ?>" style="display:none">
              <?= csrf_field() ?>
              <input type="hidden" name="produit_id" value="<?= $item['id'] ?>">
              <input type="hidden" name="quantite"   value="0">
            </form>
          </div>
        <?php endforeach; ?>

        <a href="boutique.php" class="co-continue">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
          Continuer les achats
        </a>
      <?php endif; ?>

    </div>

    <!-- RIGHT : summary -->
    <?php if (!empty($produits_panier)): ?>
    <div class="co-right">
      <div class="co-right-title">Récapitulatif de commande</div>

      <?php foreach ($produits_panier as $item): ?>
        <div class="sum-line">
          <span class="sum-line-name">
            <?= htmlspecialchars($item['nom']) ?>
            <span class="sum-line-qty" id="sum-qty-<?= $item['id'] ?>">×<?= $item['quantite'] ?></span>
          </span>
          <span class="sum-line-price" id="sum-sub-<?= $item['id'] ?>"><?= number_format($item['sous_total'],2,',',' ') ?> DA</span>
        </div>
      <?php endforeach; ?>

      <div class="sum-divider"></div>

      <div class="sum-total">
        <span class="sum-total-label">Total</span>
        <span class="sum-total-val" id="total-val"><?= number_format($total_general,2,',',' ') ?><small>DA</small></span>
      </div>
      <div class="sum-articles" id="sum-articles"><?= $nb_articles ?> article<?= $nb_articles>1?'s':'' ?> au total</div>

      <a href="paiement.php" class="btn-to-pay">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="1" y="4" width="22" height="16" rx="2" ry="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
        Procéder au paiement
      </a>

      <div class="sum-trust">
        <div class="sum-trust-item"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>Commande sécurisée</div>
        <div class="sum-trust-item"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>Retrait à l'atelier</div>
        <div class="sum-trust-item"><svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 12 20 22 4 22 4 12"/><rect x="2" y="7" width="20" height="5"/><path d="M12 22V7"/></svg>Stock confirmé à la validation</div>
      </div>
    </div>
    <?php endif; ?>

  </div>
</main>

<script>
const PRIX={<?php foreach($produits_panier as $p) echo $p['id'].':'.$p['prix'].','; ?>};
const QTY ={<?php foreach($produits_panier as $p) echo $p['id'].':'.$p['quantite'].','; ?>};

function fmt(n){return n.toLocaleString('fr-FR',{minimumFractionDigits:2,maximumFractionDigits:2});}

function changeQty(id,delta){
  const el=document.getElementById('qty-'+id);
  if(!el)return;
  let qty=parseInt(el.textContent)+delta;
  if(qty<1){removeItem(id);return;}
  el.textContent=qty;
  QTY[id]=qty;
  const sub=PRIX[id]*qty;
  const subEl=document.getElementById('sub-'+id);
  if(subEl)subEl.innerHTML=fmt(sub)+'<small>DA</small>';
  const ssum=document.getElementById('sum-sub-'+id);
  if(ssum)ssum.textContent=fmt(sub)+' DA';
  const sqty=document.getElementById('sum-qty-'+id);
  if(sqty)sqty.textContent='×'+qty;
  let total=0,arts=0;
  for(const[pid,q] of Object.entries(QTY)){total+=(PRIX[pid]||0)*q;arts+=q;}
  const tv=document.getElementById('total-val');
  if(tv)tv.innerHTML=fmt(total)+'<small>DA</small>';
  const sa=document.getElementById('sum-articles');
  if(sa)sa.textContent=arts+' article'+(arts>1?'s':'')+' au total';
  const inp=document.getElementById('upd-qty-'+id);
  if(inp)inp.value=qty;
  clearTimeout(window['t_'+id]);
  window['t_'+id]=setTimeout(()=>document.getElementById('upd-'+id)?.submit(),900);
}

function removeItem(id){
  const row=document.getElementById('item-'+id);
  if(row){row.style.transition='all .2s';row.style.opacity='0';row.style.transform='translateX(16px)';}
  setTimeout(()=>document.getElementById('rm-'+id)?.submit(),220);
}
</script>
</body>
</html>
