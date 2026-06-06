<?php
if (session_status() === PHP_SESSION_NONE) session_start();

$_active   = $activePage ?? '';
$_loggedin = isset($_SESSION['user_id']);
$_role     = $_SESSION['role'] ?? null;
$_nom      = $_SESSION['nom']  ?? '';

$_space = 'espace_client.php';
if ($_role === 'mecano') $_space = 'espace_mecano.php';
elseif ($_role === 'admin') $_space = 'espace_admin.php';

function ms_active(string $page, string $active): string {
  return $page === $active ? ' aria-current="page"' : '';
}

$_cart = 0;
if (isset($_SESSION['panier'])) foreach ($_SESSION['panier'] as $q) $_cart += $q;
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800;900&family=Barlow:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="includes/mecaspeed-ui.css">

<style>
/* ══ NAV OVERRIDES ══════════════════════════════════ */
.nav-inner {
  display: grid;
  grid-template-columns: auto 1fr auto;
  align-items: center;
  height: 64px;
  gap: 1rem;
}

/* Centre — liens */
.nav-links {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.1rem;
  flex-wrap: nowrap;
}

.nav-links a {
  font-family: 'Barlow Condensed', sans-serif;
  font-size: 0.83rem; font-weight: 600;
  letter-spacing: 0.05em; text-transform: uppercase;
  color: rgba(255,255,255,.55);
  padding: 0.4rem 0.8rem; border-radius: 4px;
  transition: color .18s, background .18s;
  white-space: nowrap; text-decoration: none;
}
.nav-links a:hover,
.nav-links a[aria-current="page"] {
  color: #fff;
  background: rgba(255,255,255,.07);
}

/* Droite */
.nav-right {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  flex-shrink: 0;
}

.nav-right-link {
  font-family: 'Barlow Condensed', sans-serif;
  font-size: 0.82rem; font-weight: 600;
  letter-spacing: 0.05em; text-transform: uppercase;
  color: rgba(255,255,255,.55);
  padding: 0.4rem 0.75rem; border-radius: 4px;
  transition: color .18s, background .18s;
  white-space: nowrap; text-decoration: none;
}
.nav-right-link:hover { color: #fff; background: rgba(255,255,255,.07); }

.nav-cta {
  font-family: 'Barlow Condensed', sans-serif;
  font-size: 0.82rem; font-weight: 700;
  letter-spacing: 0.05em; text-transform: uppercase;
  background: #1a6bbd; color: #fff !important;
  padding: 0.42rem 1rem; border-radius: 4px;
  transition: background .18s; white-space: nowrap;
  text-decoration: none;
}
.nav-cta:hover { background: #155fa0; }

/* Mon espace */
.nav-espace {
  display: inline-flex; align-items: center; gap: 0.45rem;
  font-family: 'Barlow Condensed', sans-serif;
  font-size: 0.82rem; font-weight: 700;
  letter-spacing: 0.05em; text-transform: uppercase;
  color: rgba(255,255,255,.85);
  background: rgba(255,255,255,.07);
  border: 1px solid rgba(255,255,255,.12);
  padding: 0.4rem 0.9rem; border-radius: 4px;
  transition: all .18s; white-space: nowrap;
  max-width: 170px; overflow: hidden; text-overflow: ellipsis;
  text-decoration: none;
}
.nav-espace:hover { color: #fff; background: rgba(26,107,189,.18); border-color: rgba(26,107,189,.35); }

/* Déco */
.nav-logout-btn {
  display: inline-flex; align-items: center; gap: 0.4rem;
  font-family: 'Barlow Condensed', sans-serif;
  font-size: 0.78rem; font-weight: 700;
  letter-spacing: 0.05em; text-transform: uppercase;
  color: rgba(255,255,255,.32);
  background: transparent; border: 1px solid rgba(255,255,255,.08);
  padding: 0.4rem 0.75rem; border-radius: 4px;
  transition: all .18s; white-space: nowrap; text-decoration: none;
}
.nav-logout-btn:hover { color: #f87171; background: rgba(248,113,113,.08); border-color: rgba(248,113,113,.2); }

/* Panier */
.nav-cart-link {
  display: inline-flex; align-items: center; gap: 0.4rem;
  font-family: 'Barlow Condensed', sans-serif;
  font-size: 0.83rem; font-weight: 600;
  letter-spacing: 0.05em; text-transform: uppercase;
  color: rgba(255,255,255,.55);
  padding: 0.4rem 0.75rem; border-radius: 4px;
  transition: color .18s, background .18s; text-decoration: none;
}
.nav-cart-link:hover { color: #fff; background: rgba(255,255,255,.07); }
.nav-cart-badge {
  background: #1a6bbd; color: #fff;
  font-size: 0.62rem; font-weight: 800;
  padding: 1px 6px; border-radius: 100px; min-width: 17px; text-align: center; line-height: 1.4;
}

/* ── Hamburger ── */
.menu-toggle {
  display: none; flex-direction: column; gap: 5px;
  background: none; border: none; cursor: pointer; padding: 6px;
}
.menu-toggle span { display: block; width: 24px; height: 2px; background: #fff; border-radius: 2px; }

/* ── Mobile ── */
@media (max-width: 820px) {
  .nav-inner { grid-template-columns: auto 1fr auto; }
  .nav-links  { display: none; }
  .nav-right  { display: none; }
  .menu-toggle { display: flex; grid-column: 3; }
}
</style>

<header class="site-header">
  <div class="container nav-inner">

    <!-- Logo -->
    <a class="nav-logo" href="index.php">Méca<span>Speed</span></a>

    <!-- Centre — liens principaux -->
    <nav class="nav-links" id="ms-nav" aria-label="Navigation principale">
      <?php if (!$_loggedin): ?>
        <a href="index.php"<?= ms_active('home', $_active) ?>>Accueil</a>
        <a href="boutique.php"<?= ms_active('boutique', $_active) ?>>Boutique</a>
        <a href="comment-ca-marche.php"<?= ms_active('how', $_active) ?>>Comment ça marche</a>
        <a href="contact.php"<?= ms_active('contact', $_active) ?>>Contact</a>

      <?php elseif ($_role === 'client'): ?>
        <a href="index.php"<?= ms_active('home', $_active) ?>>Accueil</a>
        <a href="boutique.php"<?= ms_active('boutique', $_active) ?>>Boutique</a>
        <a href="calendrier.php"<?= ms_active('rdv', $_active) ?>>Rendez-vous</a>
        <a href="suivi.php"<?= ms_active('suivi', $_active) ?>>Suivi</a>
        <a href="comment-ca-marche.php"<?= ms_active('how', $_active) ?>>Comment ça marche</a>
        <a href="contact.php"<?= ms_active('contact', $_active) ?>>Contact</a>

      <?php elseif ($_role === 'mecano'): ?>
        <a href="espace_mecano.php#rdv">Mes RDV</a>
        <a href="espace_mecano.php#suivi">Suivi atelier</a>
        <a href="espace_mecano.php#historique">Historique</a>

      <?php elseif ($_role === 'admin'): ?>
        <a href="espace_admin.php#rdv">Rendez-vous</a>
        <a href="espace_admin.php#users">Utilisateurs</a>
        <a href="espace_admin.php#vehicules">Véhicules</a>
        <a href="espace_admin.php#stock">Stock</a>
      <?php endif; ?>
    </nav>

    <!-- Droite — auth -->
    <div class="nav-right" id="ms-nav-right">
      <?php if (!$_loggedin): ?>
        <a href="login.php"<?= ms_active('login', $_active) ?> class="nav-right-link">Connexion</a>
        <a href="inscription.php"<?= ms_active('inscription', $_active) ?> class="nav-cta">S'inscrire</a>

      <?php else: ?>
        <?php if ($_role === 'client'): ?>
          <a href="panier.php" class="nav-cart-link">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            Panier<?php if ($_cart > 0): ?><span class="nav-cart-badge"><?= $_cart ?></span><?php endif; ?>
          </a>
        <?php endif; ?>
        <a href="<?= $_space ?>" class="nav-espace">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          <?= htmlspecialchars($_nom ?: 'Mon espace') ?>
        </a>
        <a href="logout.php" class="nav-logout-btn">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
          Déco
        </a>
      <?php endif; ?>
    </div>

    <!-- Hamburger -->
    <button class="menu-toggle" type="button" aria-label="Menu" aria-expanded="false" id="ms-menu-toggle">
      <span></span><span></span><span></span>
    </button>

  </div>
</header>

<script>
(function(){
  const toggle = document.getElementById('ms-menu-toggle');
  if (!toggle) return;
  toggle.addEventListener('click', function(){
    const open = toggle.getAttribute('aria-expanded') === 'true';
    toggle.setAttribute('aria-expanded', String(!open));
    const nav   = document.getElementById('ms-nav');
    const right = document.getElementById('ms-nav-right');
    if (!open) {
      [nav, right].forEach((el, i) => {
        if (!el) return;
        Object.assign(el.style, {
          display:'flex', flexDirection:'column', alignItems:'flex-start',
          position:'absolute', top: i===0 ? '64px' : 'auto',
          left:'0', right:'0', zIndex:'99',
          background:'rgba(10,10,10,.98)', padding: i===0 ? '1rem 1.5rem' : '0.75rem 1.5rem 1.25rem',
          borderBottom:'1px solid rgba(255,255,255,.07)',
          backdropFilter:'blur(16px)', gap:'0.2rem'
        });
      });
      if (right) {
        const navH = nav ? nav.getBoundingClientRect().height + 64 : 200;
        right.style.top = navH + 'px';
      }
    } else {
      [nav, right].forEach(el => el && el.removeAttribute('style'));
    }
  });
})();
</script>
