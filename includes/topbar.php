<?php
// includes/topbar.php
// Variables attendues : $page_title (string), $topbar_action (html optionnel)
$page_title    = $page_title    ?? 'MécaSpeed';
$topbar_action = $topbar_action ?? '';
?>
<div class="topbar">
  <!-- Bouton hamburger mobile -->
  <button id="menu-toggle"
          onclick="document.getElementById('sidebar').classList.toggle('open');"
         
          aria-label="Menu">
    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
      <line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/>
    </svg>
  </button>

  <span class="topbar-title"><?= htmlspecialchars($page_title) ?></span>

  <div class="topbar-actions">
    <?= $topbar_action ?>
  </div>
</div>
