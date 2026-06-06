<?php
session_start();
require_once 'connexion_bd.php';
require_once 'includes/security.php';

try {
    $produits = $pdo->query("SELECT * FROM produits WHERE en_stock > 0 ORDER BY categorie_id ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Erreur boutique : " . $e->getMessage());
}

$total_articles = 0;
if (isset($_SESSION['panier'])) foreach ($_SESSION['panier'] as $q) $total_articles += $q;

$activePage = 'boutique';

function produit_image_src($image) {
    $image = $image ?: 'placeholder.png';
    if (file_exists(__DIR__ . '/image/' . $image)) return 'image/' . $image;
    if (file_exists(__DIR__ . '/' . $image))        return $image;
    return 'image/' . $image;
}
function produit_cat($nom, $desc = '') {
    $t = strtolower($nom . ' ' . $desc);
    if (str_contains($t, 'huile') || str_contains($t, 'lubrifiant')) return 'huile';
    if (str_contains($t, 'filtre'))  return 'filtre';
    if (str_contains($t, 'frein') || str_contains($t, 'plaquette') || str_contains($t, 'disque')) return 'frein';
    if (str_contains($t, 'batterie')) return 'batterie';
    if (str_contains($t, 'pneu'))    return 'pneu';
    return '';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MécaSpeed — Boutique</title>
  <style>
  /* ── Toolbar ───────────────────────────────────────── */
  .shop-bar {
    background: #0d0d0d;
    border-bottom: 1px solid rgba(255,255,255,.07);
    padding: 1rem 0;
    position: sticky; top: 64px; z-index: 50;
  }
  .shop-bar-inner {
    display: flex; align-items: center; gap: 0.75rem; flex-wrap: wrap;
  }

  /* Search */
  .search-wrap {
    position: relative; flex: 1; min-width: 200px; max-width: 320px;
  }
  .search-wrap svg {
    position: absolute; left: 0.75rem; top: 50%; transform: translateY(-50%);
    color: rgba(255,255,255,.25); pointer-events: none;
  }
  .search-input {
    width: 100%;
    background: rgba(255,255,255,.05);
    border: 1px solid rgba(255,255,255,.1);
    border-radius: 6px;
    color: #fff; font-family: 'Barlow', sans-serif; font-size: 0.88rem;
    padding: 0.6rem 0.9rem 0.6rem 2.25rem;
    outline: none; transition: border-color .18s, box-shadow .18s;
  }
  .search-input:focus {
    border-color: #1a6bbd;
    box-shadow: 0 0 0 3px rgba(26,107,189,.14);
  }
  .search-input::placeholder { color: rgba(255,255,255,.2); }

  /* Divider */
  .bar-sep {
    width: 1px; height: 20px; background: rgba(255,255,255,.1); flex-shrink: 0;
  }

  /* Filter tabs */
  .filter-tabs { display: flex; gap: 0.35rem; flex-wrap: wrap; }
  .filter-tab {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 0.75rem; font-weight: 700;
    letter-spacing: 0.06em; text-transform: uppercase;
    color: rgba(255,255,255,.45);
    background: transparent;
    border: 1px solid rgba(255,255,255,.08);
    padding: 0.38rem 0.85rem; border-radius: 100px;
    cursor: pointer; transition: all .15s;
  }
  .filter-tab:hover { color: rgba(255,255,255,.8); border-color: rgba(255,255,255,.2); }
  .filter-tab.active { background: #1a6bbd; border-color: #1a6bbd; color: #fff; }

  /* Count + cart right */
  .bar-right { display: flex; align-items: center; gap: 0.75rem; margin-left: auto; flex-shrink: 0; }
  .bar-count {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 0.72rem; font-weight: 700; letter-spacing: 0.07em; text-transform: uppercase;
    color: rgba(255,255,255,.25);
  }
  .bar-count strong { color: rgba(255,255,255,.6); }
  .cart-btn {
    display: inline-flex; align-items: center; gap: 0.45rem;
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 0.75rem; font-weight: 700; letter-spacing: 0.06em; text-transform: uppercase;
    color: rgba(255,255,255,.7);
    background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.1);
    padding: 0.4rem 0.85rem; border-radius: 100px;
    text-decoration: none; transition: all .15s;
  }
  .cart-btn:hover { color: #fff; border-color: rgba(255,255,255,.22); }
  .cart-count {
    background: #1a6bbd; color: #fff;
    font-size: 0.65rem; font-weight: 800;
    padding: 1px 6px; border-radius: 100px; min-width: 18px; text-align: center;
  }

  /* ── Grid ──────────────────────────────────────────── */
  .shop-section { padding: 2rem 0 4rem; background: #0d0d0d; }
  .product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
    gap: 1px;
    background: rgba(255,255,255,.06);
    border: 1px solid rgba(255,255,255,.06);
    border-radius: 10px;
    overflow: hidden;
  }

  /* ── Product card ──────────────────────────────────── */
  .product-card {
    background: #0f0f0f;
    display: flex; flex-direction: column;
    transition: background .18s;
    cursor: default;
  }
  .product-card:hover { background: #151515; }
  .product-card:hover .pc-img img { transform: scale(1.04); }

  .pc-img {
    position: relative; aspect-ratio: 4/3;
    background: #1a1a1a; overflow: hidden;
  }
  .pc-img img { width: 100%; height: 100%; object-fit: cover; transition: transform .35s ease; }
  .pc-img-placeholder {
    width: 100%; height: 100%;
    display: flex; align-items: center; justify-content: center;
  }
  .pc-img-placeholder svg { color: rgba(255,255,255,.07); }
  .pc-cat {
    position: absolute; top: .65rem; left: .65rem;
    font-family: 'Barlow Condensed', sans-serif;
    font-size: .65rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase;
    background: rgba(26,107,189,.88); color: #fff;
    padding: .18rem .55rem; border-radius: 3px;
  }
  .pc-stock {
    position: absolute; top: .65rem; right: .65rem;
    display: flex; align-items: center; gap: .3rem;
    font-family: 'Barlow Condensed', sans-serif;
    font-size: .62rem; font-weight: 700; letter-spacing: .07em; text-transform: uppercase;
    background: rgba(0,0,0,.7); color: #4ade80;
    border: 1px solid rgba(74,222,128,.2); padding: .18rem .55rem; border-radius: 3px;
  }
  .pc-stock::before { content:''; width:5px; height:5px; border-radius:50%; background:#4ade80; flex-shrink:0; }

  .pc-body { padding: 1rem 1.1rem; flex: 1; display: flex; flex-direction: column; gap: .4rem; }
  .pc-name {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 1rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .02em; color: #fff; line-height: 1.2;
  }
  .pc-desc { font-size: .8rem; color: rgba(255,255,255,.38); line-height: 1.45; flex: 1; }

  .pc-footer {
    padding: .85rem 1.1rem;
    border-top: 1px solid rgba(255,255,255,.05);
    display: flex; align-items: center; justify-content: space-between; gap: .75rem;
  }
  .pc-price {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 1.25rem; font-weight: 900; color: #fff; line-height: 1;
  }
  .pc-price small { font-size: .7rem; color: rgba(255,255,255,.32); margin-left: 2px; }

  .btn-add {
    display: inline-flex; align-items: center; gap: .4rem;
    font-family: 'Barlow Condensed', sans-serif;
    font-size: .78rem; font-weight: 700; letter-spacing: .06em; text-transform: uppercase;
    background: #1a6bbd; color: #fff; border: none; border-radius: 4px;
    padding: .5rem .95rem; cursor: pointer;
    transition: background .15s, transform .15s;
    white-space: nowrap;
  }
  .btn-add:hover { background: #155fa0; transform: translateY(-1px); }
  .btn-add svg { width: 13px; height: 13px; flex-shrink: 0; }

  /* ── Notifications ─────────────────────────────────── */
  .shop-notif {
    margin-bottom: 1.25rem;
  }

  /* ── Empty ─────────────────────────────────────────── */
  .shop-empty {
    grid-column: 1 / -1;
    padding: 4rem 2rem; text-align: center;
    background: #0f0f0f;
  }
  .shop-empty p { font-size: .85rem; color: rgba(255,255,255,.2); margin-top: .5rem; }

  @media (max-width: 600px) {
    .search-wrap { max-width: 100%; }
    .bar-sep { display: none; }
    .product-grid { grid-template-columns: 1fr; }
    .bar-right { margin-left: 0; }
  }
  </style>
</head>
<body>
<?php require __DIR__ . '/includes/public-nav.php'; ?>

<main id="contenu-principal">

  <!-- ── TOOLBAR ── -->
  <div class="shop-bar">
    <div class="container shop-bar-inner">

      <!-- Recherche -->
      <div class="search-wrap">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
        <input type="text" class="search-input" id="search-input"
               placeholder="Rechercher un produit…"
               oninput="applyFilters()" autocomplete="off">
      </div>

      <div class="bar-sep"></div>

      <!-- Filtres catégorie -->
      <div class="filter-tabs" role="group" aria-label="Filtrer par catégorie">
        <button class="filter-tab active" data-cat="all"      onclick="setFilter('all',this)">Tous</button>
        <button class="filter-tab"        data-cat="huile"    onclick="setFilter('huile',this)">Huiles</button>
        <button class="filter-tab"        data-cat="filtre"   onclick="setFilter('filtre',this)">Filtres</button>
        <button class="filter-tab"        data-cat="frein"    onclick="setFilter('frein',this)">Freinage</button>
        <button class="filter-tab"        data-cat="batterie" onclick="setFilter('batterie',this)">Batteries</button>
        <button class="filter-tab"        data-cat="pneu"     onclick="setFilter('pneu',this)">Pneus</button>
      </div>

      <!-- Compteur + panier -->
      <div class="bar-right">
        <span class="bar-count" id="result-count"><strong><?= count($produits) ?></strong> produit<?= count($produits)>1?'s':'' ?></span>
        <a href="panier.php" class="cart-btn" aria-label="Voir le panier">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
          Panier
          <span class="cart-count"><?= $total_articles ?></span>
        </a>
      </div>

    </div>
  </div>

  <!-- ── PRODUITS ── -->
  <section class="shop-section" aria-label="Catalogue produits">
    <div class="container">

      <?php if (isset($_GET['panier']) && $_GET['panier'] === 'ajoute'): ?>
        <div class="shop-notif">
          <div class="alert alert-success" role="status">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            Produit ajouté au panier.
          </div>
        </div>
      <?php endif; ?>
      <?php if (isset($_GET['commande'])): ?>
        <div class="shop-notif">
          <div class="alert alert-success" role="status">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            Commande #<?= htmlspecialchars($_GET['commande']) ?> enregistrée.
          </div>
        </div>
      <?php endif; ?>

      <div class="product-grid" id="product-grid">
        <?php if (count($produits) > 0): ?>
          <?php foreach ($produits as $p):
            $cat     = produit_cat($p['nom'], $p['description'] ?? '');
            $has_img = ($p['image'] ?? '') && $p['image'] !== 'placeholder.png';
            $src     = htmlspecialchars(produit_image_src($p['image'] ?? ''));
          ?>
            <article class="product-card"
                     data-cat="<?= $cat ?>"
                     data-search="<?= htmlspecialchars(strtolower($p['nom'] . ' ' . ($p['description']??''))) ?>">

              <div class="pc-img">
                <?php if ($has_img): ?>
                  <img src="<?= $src ?>" alt="<?= htmlspecialchars($p['nom']) ?>" loading="lazy">
                <?php else: ?>
                  <div class="pc-img-placeholder">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/></svg>
                  </div>
                <?php endif; ?>
                <?php if ($cat): ?>
                  <span class="pc-cat"><?= htmlspecialchars(ucfirst($cat)) ?></span>
                <?php endif; ?>
                <span class="pc-stock">En stock</span>
              </div>

              <div class="pc-body">
                <h3 class="pc-name"><?= htmlspecialchars($p['nom']) ?></h3>
                <?php if (!empty($p['description'])): ?>
                  <p class="pc-desc"><?= htmlspecialchars($p['description']) ?></p>
                <?php endif; ?>
              </div>

              <div class="pc-footer">
                <div class="pc-price">
                  <?= number_format($p['prix'], 2, ',', ' ') ?><small>DA</small>
                </div>
                <form action="ajouter_panier.php" method="POST">
                  <?= csrf_field() ?>
                  <input type="hidden" name="produit_id" value="<?= htmlspecialchars($p['id']) ?>">
                  <button type="submit" class="btn-add" aria-label="Ajouter <?= htmlspecialchars($p['nom']) ?> au panier">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                    Ajouter
                  </button>
                </form>
              </div>
            </article>
          <?php endforeach; ?>

          <!-- Empty state (filter) -->
          <div class="shop-empty" id="filter-empty" style="display:none">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.15)" stroke-width="1.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <p>Aucun produit ne correspond à votre recherche.</p>
          </div>

        <?php else: ?>
          <div class="shop-empty">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.12)" stroke-width="1.5"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/></svg>
            <p>Aucun produit disponible pour le moment.</p>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </section>

</main>

<?php require __DIR__ . '/includes/page-footer.php'; ?>

<script>
let currentCat = 'all';

function setFilter(cat, btn) {
  currentCat = cat;
  document.querySelectorAll('.filter-tab').forEach(t => t.classList.remove('active'));
  btn.classList.add('active');
  applyFilters();
}

function applyFilters() {
  const q = document.getElementById('search-input').value.trim().toLowerCase();
  const cards = document.querySelectorAll('.product-card');
  let visible = 0;

  cards.forEach(card => {
    const matchCat    = currentCat === 'all' || card.dataset.cat === currentCat;
    const matchSearch = !q || card.dataset.search.includes(q);
    const show = matchCat && matchSearch;
    card.style.display = show ? '' : 'none';
    if (show) visible++;
  });

  // Empty state
  const empty = document.getElementById('filter-empty');
  if (empty) empty.style.display = visible === 0 ? '' : 'none';

  // Counter
  const counter = document.getElementById('result-count');
  if (counter) counter.innerHTML = '<strong>' + visible + '</strong> produit' + (visible > 1 ? 's' : '');
}
</script>
</body>
</html>
