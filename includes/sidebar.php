<?php
// includes/sidebar.php
// Usage : require_once 'includes/sidebar.php';
// Variables attendues : $_SESSION['role'], $_SESSION['nom'], $_SESSION['user_id']
// Optionnel : $active_page = 'dashboard' | 'vehicules' | 'rdv' | 'suivi' | 'boutique' | 'panier' | 'admin_rdv' | 'admin_users' | 'admin_stock'

$role     = $_SESSION['role']    ?? 'client';
$nom      = $_SESSION['nom']     ?? 'Utilisateur';
$initiale = mb_strtoupper(mb_substr($nom, 0, 1));
$active   = $active_page ?? '';

$role_labels = [
  'client' => 'Client',
  'mecano' => 'Mécanicien',
  'admin'  => 'Administrateur',
];
$role_label = $role_labels[$role] ?? ucfirst($role);

// Couleur avatar selon le rôle
$avatar_styles = [
  'admin'  => 'background:rgba(239,68,68,.15);border-color:var(--red);color:var(--red);',
  'mecano' => 'background:rgba(249,115,22,.15);border-color:var(--orange);color:var(--orange);',
  'client' => 'background:var(--teal-dim);border-color:var(--teal);color:var(--teal);',
];
$avatar_style = $avatar_styles[$role] ?? $avatar_styles['client'];

// Sous-titre sidebar selon rôle
$sub_labels = [
  'admin'  => 'Administration',
  'mecano' => 'Espace Atelier',
  'client' => 'Espace Client',
];
$sub_label = $sub_labels[$role] ?? 'Espace Client';

function nav_item($href, $icon_path, $label, $is_active, $badge = '') {
  $ac = $is_active ? ' active' : '';
  $bd = $badge ? "<span class=\"badge badge-orange\">$badge</span>" : '';
  echo "<a class=\"nav-item$ac\" href=\"$href\">$icon_path $label $bd</a>";
}

// SVG Icons
$ic = [
  'dashboard' => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>',
  'car'       => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M5 17H3a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v5a2 2 0 0 1-2 2h-2"/><circle cx="7.5" cy="17.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>',
  'calendar'  => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>',
  'clock'     => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>',
  'shop'      => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>',
  'cart'      => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>',
  'users'     => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
  'box'       => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>',
  'wrench'    => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z"/></svg>',
  'logout'    => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>',
  'home'      => '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
];

// Badge panier
$panier_count = 0;
if (isset($_SESSION['panier'])) {
  foreach ($_SESSION['panier'] as $q) $panier_count += $q;
}

// RDV en attente (admin seulement)
$pending_rdv = 0;
if ($role === 'admin' && isset($pdo)) {
  try { $pending_rdv = $pdo->query("SELECT COUNT(*) FROM rendez_vous WHERE statut='pending'")->fetchColumn(); }
  catch(Exception $e) {}
}
?>

<aside class="sidebar" id="sidebar">
  <div class="sidebar-logo">
    Méca<span>Speed</span>
    <small><?= $sub_label ?></small>
  </div>

  <nav class="sidebar-nav">

    <?php if ($role === 'client'): ?>

      <div class="nav-section-label">Principal</div>
      <?php nav_item('espace_client.php',  $ic['dashboard'], 'Dashboard',       $active==='dashboard') ?>
      <?php nav_item('vehicule.php',       $ic['car'],       'Mes Véhicules',   $active==='vehicules') ?>
      <?php nav_item('calendrier.php',     $ic['calendar'],  'Prendre RDV',     $active==='rdv') ?>
      <?php nav_item('suivi.php',          $ic['clock'],     'Suivi Véhicule',  $active==='suivi') ?>

      <div class="nav-section-label">Boutique</div>
      <?php nav_item('boutique.php',       $ic['shop'],  'Boutique',   $active==='boutique') ?>
      <?php nav_item('panier.php',         $ic['cart'],  'Mon Panier', $active==='panier', $panier_count ?: '') ?>

    <?php elseif ($role === 'mecano'): ?>

      <div class="nav-section-label">Atelier</div>
      <?php nav_item('espace_mecano.php',  $ic['dashboard'], 'Tableau de bord', $active==='dashboard') ?>
      <?php nav_item('espace_mecano.php',  $ic['calendar'],  'Mes RDV',         $active==='rdv') ?>
      <?php nav_item('espace_mecano.php',  $ic['wrench'],    'Suivi véhicules', $active==='suivi') ?>

    <?php elseif ($role === 'admin'): ?>

      <div class="nav-section-label">Vue globale</div>
      <?php nav_item('espace_admin.php?s=dashboard', $ic['dashboard'], 'Dashboard',    $active==='dashboard') ?>

      <div class="nav-section-label">Gestion</div>
      <?php nav_item('espace_admin.php?s=rdv',   $ic['calendar'], 'Rendez-vous',  $active==='admin_rdv',   $pending_rdv ?: '') ?>
      <?php nav_item('espace_admin.php?s=users', $ic['users'],    'Utilisateurs', $active==='admin_users') ?>
      <?php nav_item('espace_admin.php?s=stock', $ic['box'],      'Stock',        $active==='admin_stock') ?>

    <?php endif; ?>

  </nav>

  <div class="sidebar-footer">
    <div class="user-pill">
      <div class="user-avatar"><?= $initiale ?></div>
      <div>
        <div class="user-name"><?= htmlspecialchars($nom) ?></div>
        <div class="user-role"><?= $role_label ?></div>
      </div>
    </div>
    <a href="logout.php"
      
      
      >
      <?= $ic['logout'] ?> Déconnexion
    </a>
  </div>
</aside>

<!-- Overlay mobile -->
<div id="sidebar-overlay" onclick="document.getElementById('sidebar').classList.remove('open');"></div>
