<?php
// Requires: $nom_complet, $initiales, $rdv_attente, $activePage
$_sb = $activePage ?? '';
?>
<aside class="dash-sidebar" aria-label="Navigation du tableau de bord">

  <div class="sb-profile">
    <div class="sb-avatar"><?= $initiales ?></div>
    <div class="sb-name"><?= htmlspecialchars($nom_complet) ?></div>
    <span class="sb-role">Client</span>
  </div>

  <nav class="sb-nav">
    <div class="sb-group">
      <span class="sb-section-label">Espace</span>

      <a href="espace_client.php" class="sb-link <?= $_sb === 'dashboard' ? 'active' : '' ?>">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
        Vue d'ensemble
      </a>

      <a href="vehicule.php" class="sb-link <?= $_sb === 'vehicules' ? 'active' : '' ?>">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 3v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
        Mes véhicules
      </a>

      <a href="mes_rdv.php" class="sb-link <?= $_sb === 'rdv' ? 'active' : '' ?>">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
        Rendez-vous
        <?php if (!empty($rdv_attente) && $rdv_attente > 0): ?>
          <span class="sb-badge warn"><?= $rdv_attente ?></span>
        <?php endif; ?>
      </a>

      <a href="suivi.php" class="sb-link <?= $_sb === 'suivi' ? 'active' : '' ?>">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
        Suivi atelier
      </a>

      <a href="mes_commandes.php" class="sb-link <?= $_sb === 'commandes' ? 'active' : '' ?>">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
        Mes commandes
        <?php if (!empty($nb_commandes) && $nb_commandes > 0): ?>
          <span class="sb-badge"><?= $nb_commandes ?></span>
        <?php endif; ?>
      </a>
    </div>

    <div class="sb-group">
      <span class="sb-section-label">Compte</span>
      <a href="profil.php" class="sb-link <?= $_sb === 'profil' ? 'active' : '' ?>">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        Mon profil
      </a>
    </div>
  </nav>

  <div class="sb-bottom">
    <a href="logout.php" class="sb-logout">
      <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
      Déconnexion
    </a>
  </div>

</aside>
