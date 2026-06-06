<?php
session_start();
require_once 'connexion_bd.php';
require_once 'includes/security.php';

if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }
if (($_SESSION['role'] ?? 'client') !== 'client') {
  header('Location: ' . ($_SESSION['role'] === 'mecano' ? 'espace_mecano.php' : 'espace_admin.php'));
  exit();
}

$uid = $_SESSION['user_id'];

// Charger les données fraîches depuis la DB
$stmt = $pdo->prepare("SELECT nom, email, telephone, cree_le FROM utilisateurs WHERE id = :uid");
$stmt->execute([':uid' => $uid]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) { header('Location: logout.php'); exit(); }

// Initiales avatar
$nom_complet = $user['nom'];
$initiales   = strtoupper(mb_substr($nom_complet, 0, 1));
$mots = explode(' ', $nom_complet);
if (count($mots) >= 2) $initiales = strtoupper(mb_substr($mots[0],0,1).mb_substr($mots[1],0,1));

$rdv_attente = 0;
$activePage  = 'profil';

// Messages
$ok  = $_GET['ok']     ?? '';
$err = $_GET['erreur'] ?? '';

$err_messages = [
  'champs_vides'   => 'Veuillez remplir tous les champs obligatoires.',
  'email_invalide' => "L'adresse e-mail saisie est invalide.",
  'email_pris'     => 'Cette adresse e-mail est déjà utilisée par un autre compte.',
  'champs_mdp'     => 'Veuillez remplir tous les champs du mot de passe.',
  'mdp_court'      => 'Le nouveau mot de passe doit contenir au moins 6 caractères.',
  'mdp_diff'       => 'Les deux mots de passe ne correspondent pas.',
  'mdp_incorrect'  => 'Le mot de passe actuel est incorrect.',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MécaSpeed — Mon profil</title>
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
  .sb-profile { padding:0 1.25rem 1.75rem; border-bottom:1px solid rgba(255,255,255,.06); margin-bottom:1.5rem; }
  .sb-avatar { width:52px;height:52px;border-radius:14px;background:linear-gradient(135deg,#1a6bbd,#0e4a8a);display:flex;align-items:center;justify-content:center;font-family:'Barlow Condensed',sans-serif;font-size:1.15rem;font-weight:900;color:#fff;margin-bottom:.9rem; }
  .sb-name { font-family:'Barlow Condensed',sans-serif;font-size:1rem;font-weight:800;text-transform:uppercase;letter-spacing:.04em;color:#fff;margin-bottom:.3rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; }
  .sb-role { display:inline-flex;font-family:'Barlow Condensed',sans-serif;font-size:.65rem;font-weight:700;letter-spacing:.1em;text-transform:uppercase;color:#1a6bbd;background:rgba(26,107,189,.12);border:1px solid rgba(26,107,189,.2);padding:.18rem .55rem;border-radius:3px; }
  .sb-nav { flex:1;padding:0 .75rem; }
  .sb-section-label { font-family:'Barlow Condensed',sans-serif;font-size:.6rem;font-weight:700;letter-spacing:.16em;text-transform:uppercase;color:rgba(255,255,255,.2);padding:0 .6rem;margin-bottom:.4rem;display:block; }
  .sb-group { margin-bottom:1.5rem; }
  .sb-link { display:flex;align-items:center;gap:.65rem;font-family:'Barlow Condensed',sans-serif;font-size:.82rem;font-weight:600;letter-spacing:.04em;text-transform:uppercase;color:rgba(255,255,255,.45);padding:.55rem .75rem;border-radius:6px;transition:all .15s;text-decoration:none;border:1px solid transparent; }
  .sb-link svg { flex-shrink:0;opacity:.5;transition:opacity .15s; }
  .sb-link:hover { color:rgba(255,255,255,.85);background:rgba(255,255,255,.05); }
  .sb-link:hover svg { opacity:1; }
  .sb-link.active { color:#fff;background:rgba(26,107,189,.14);border-color:rgba(26,107,189,.18); }
  .sb-link.active svg { opacity:1;color:#1a6bbd; }
  .sb-bottom { padding:1.25rem 1.5rem 0;border-top:1px solid rgba(255,255,255,.06);margin-top:auto; }
  .sb-logout { display:flex;align-items:center;gap:.6rem;font-family:'Barlow Condensed',sans-serif;font-size:.78rem;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:rgba(255,255,255,.28);padding:.5rem;border-radius:6px;transition:all .15s;text-decoration:none; }
  .sb-logout:hover { color:#f87171;background:rgba(248,113,113,.07); }

  /* Main */
  .dash-main { overflow-y:auto; }
  .dash-topbar { display:flex;align-items:center;justify-content:space-between;padding:1.75rem 2.5rem;border-bottom:1px solid rgba(255,255,255,.06);background:#0a0a0a;position:sticky;top:0;z-index:10;flex-shrink:0; }
  .topbar-label { font-family:'Barlow Condensed',sans-serif;font-size:.7rem;font-weight:700;letter-spacing:.14em;text-transform:uppercase;color:rgba(255,255,255,.3); }
  .topbar-label strong { display:block;margin-top:2px;font-size:1.35rem;color:#fff;letter-spacing:.02em; }
  .dash-body { padding:2rem 2.5rem;display:flex;flex-direction:column;gap:2rem; }

  /* Avatar large */
  .profile-header {
    display: flex; align-items: center; gap: 1.5rem;
    padding: 1.75rem; background: #101010;
    border: 1px solid rgba(255,255,255,.07); border-radius: 12px;
  }
  .profile-avatar-lg {
    width: 72px; height: 72px; border-radius: 18px; flex-shrink: 0;
    background: linear-gradient(135deg, #1a6bbd 0%, #0e4a8a 100%);
    display: flex; align-items: center; justify-content: center;
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 1.6rem; font-weight: 900; color: #fff;
    letter-spacing: .02em;
  }
  .profile-header-info {}
  .profile-header-name {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 1.4rem; font-weight: 900; text-transform: uppercase;
    letter-spacing: .04em; color: #fff; line-height: 1; margin-bottom: .35rem;
  }
  .profile-header-email { font-size: .85rem; color: rgba(255,255,255,.38); margin-bottom: .5rem; }
  .profile-header-since {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: .65rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase;
    color: rgba(255,255,255,.22);
  }

  /* Sections */
  .settings-section {
    background: #101010; border: 1px solid rgba(255,255,255,.07);
    border-radius: 12px; overflow: hidden;
  }
  .settings-head {
    padding: 1.25rem 1.5rem;
    border-bottom: 1px solid rgba(255,255,255,.07);
    background: rgba(255,255,255,.02);
    display: flex; align-items: center; gap: .75rem;
  }
  .settings-head-icon {
    width: 32px; height: 32px; border-radius: 8px; flex-shrink: 0;
    background: rgba(26,107,189,.1); border: 1px solid rgba(26,107,189,.2);
    display: flex; align-items: center; justify-content: center;
  }
  .settings-head-icon svg { color: #1a6bbd; }
  .settings-head-title {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: .88rem; font-weight: 800; text-transform: uppercase;
    letter-spacing: .05em; color: #fff;
  }
  .settings-head-sub { font-size: .75rem; color: rgba(255,255,255,.28); margin-top: 1px; }
  .settings-body { padding: 1.5rem; }

  /* Form */
  .f-row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 0 1rem; }
  .f-field { margin-bottom: 1rem; }
  .f-field label {
    display: block; font-family: 'Barlow Condensed', sans-serif;
    font-size: .68rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase;
    color: rgba(255,255,255,.28); margin-bottom: .4rem;
  }
  .f-field input {
    width: 100%; background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.1);
    border-radius: 8px; color: #fff; font-family: 'Barlow', sans-serif; font-size: .9rem;
    padding: .72rem 1rem; outline: none;
    transition: border-color .18s, box-shadow .18s;
  }
  .f-field input:focus { border-color: #1a6bbd; box-shadow: 0 0 0 3px rgba(26,107,189,.14); }
  .f-field input::placeholder { color: rgba(255,255,255,.14); }
  .f-hint { font-size: .72rem; color: rgba(255,255,255,.22); margin-top: .3rem; }

  /* Password strength */
  .pwd-strength { margin-top: .4rem; display: flex; gap: .3rem; align-items: center; }
  .pwd-bar { flex: 1; height: 3px; border-radius: 2px; background: rgba(255,255,255,.08); transition: background .3s; }
  .pwd-label { font-family: 'Barlow Condensed', sans-serif; font-size: .65rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: rgba(255,255,255,.25); min-width: 50px; }

  /* Submit */
  .f-actions { display: flex; align-items: center; gap: .75rem; margin-top: 1.25rem; padding-top: 1.25rem; border-top: 1px solid rgba(255,255,255,.06); }
  .btn-save {
    display: inline-flex; align-items: center; gap: .45rem;
    font-family: 'Barlow Condensed', sans-serif; font-size: .82rem; font-weight: 700;
    letter-spacing: .06em; text-transform: uppercase;
    background: #1a6bbd; color: #fff; border: none; border-radius: 6px;
    padding: .68rem 1.4rem; cursor: pointer; transition: background .15s, box-shadow .15s, transform .15s;
  }
  .btn-save:hover { background: #155fa0; box-shadow: 0 4px 18px rgba(26,107,189,.35); transform: translateY(-1px); }
  .btn-danger-save {
    display: inline-flex; align-items: center; gap: .45rem;
    font-family: 'Barlow Condensed', sans-serif; font-size: .82rem; font-weight: 700;
    letter-spacing: .06em; text-transform: uppercase;
    background: rgba(248,113,113,.1); color: #f87171;
    border: 1px solid rgba(248,113,113,.25); border-radius: 6px;
    padding: .68rem 1.4rem; cursor: pointer; transition: all .15s;
  }
  .btn-danger-save:hover { background: #f87171; color: #fff; border-color: #f87171; }

  /* Alerts */
  .p-alert {
    display: flex; align-items: center; gap: .65rem;
    font-family: 'Barlow Condensed', sans-serif; font-size: .8rem; font-weight: 700;
    letter-spacing: .04em; text-transform: uppercase;
    padding: .8rem 1.1rem; border-radius: 8px; border: 1px solid; margin-bottom: 1.5rem;
  }
  .p-alert-ok  { background: rgba(74,222,128,.08); border-color: rgba(74,222,128,.2);  color: #4ade80; }
  .p-alert-err { background: rgba(248,113,113,.08);border-color: rgba(248,113,113,.2); color: #f87171; }

  /* Danger zone */
  .danger-zone {
    background: rgba(248,113,113,.04);
    border: 1px solid rgba(248,113,113,.12);
    border-radius: 12px; padding: 1.5rem;
  }
  .danger-zone-title {
    font-family: 'Barlow Condensed', sans-serif; font-size: .88rem; font-weight: 800;
    text-transform: uppercase; letter-spacing: .05em; color: #f87171; margin-bottom: .4rem;
  }
  .danger-zone p { font-size: .82rem; color: rgba(255,255,255,.35); margin-bottom: 1rem; }

  @media(max-width:900px){
    .dash-root { grid-template-columns:1fr; }
    .dash-sidebar { display:none; }
    .dash-topbar { padding:1.25rem 1.5rem; }
    .dash-body { padding:1.5rem; }
    .f-row-2 { grid-template-columns:1fr; }
  }
  </style>
</head>
<body>
<?php require __DIR__ . '/includes/public-nav.php'; ?>

<div class="dash-root">

  <!-- SIDEBAR -->
  <?php require __DIR__ . '/includes/dashboard-sidebar.php'; ?>

  <!-- MAIN -->
  <main class="dash-main" id="contenu-principal">

    <div class="dash-topbar">
      <div class="topbar-label">
        Compte
        <strong>Mon profil</strong>
      </div>
      <a href="espace_client.php" class="btn btn-sm btn-outline">← Tableau de bord</a>
    </div>

    <div class="dash-body">

      <!-- Alertes globales -->
      <?php if ($ok === 'info'): ?>
        <div class="p-alert p-alert-ok">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          Informations mises à jour avec succès.
        </div>
      <?php elseif ($ok === 'password'): ?>
        <div class="p-alert p-alert-ok" id="securite">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          Mot de passe modifié avec succès.
        </div>
      <?php elseif ($err && !in_array($err, ['champs_mdp','mdp_court','mdp_diff','mdp_incorrect'])): ?>
        <div class="p-alert p-alert-err">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
          <?= htmlspecialchars($err_messages[$err] ?? 'Une erreur est survenue.') ?>
        </div>
      <?php endif; ?>

      <!-- Carte profil -->
      <div class="profile-header">
        <div class="profile-avatar-lg"><?= $initiales ?></div>
        <div class="profile-header-info">
          <div class="profile-header-name"><?= htmlspecialchars($user['nom']) ?></div>
          <div class="profile-header-email"><?= htmlspecialchars($user['email']) ?></div>
          <div class="profile-header-since">
            Membre depuis <?= date('d/m/Y', strtotime($user['cree_le'])) ?>
          </div>
        </div>
      </div>

      <!-- ── Section 1 : Informations personnelles ── -->
      <div class="settings-section">
        <div class="settings-head">
          <div class="settings-head-icon">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
          </div>
          <div>
            <div class="settings-head-title">Informations personnelles</div>
            <div class="settings-head-sub">Nom, e-mail et téléphone</div>
          </div>
        </div>
        <div class="settings-body">
          <form action="traitement_profil.php" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_info">

            <div class="f-field">
              <label for="nom">Nom complet</label>
              <input type="text" id="nom" name="nom"
                     value="<?= htmlspecialchars($user['nom']) ?>"
                     placeholder="Prénom Nom" required autocomplete="name">
            </div>

            <div class="f-row-2">
              <div class="f-field">
                <label for="email">Adresse e-mail</label>
                <input type="email" id="email" name="email"
                       value="<?= htmlspecialchars($user['email']) ?>"
                       placeholder="vous@domaine.com" required autocomplete="email">
              </div>
              <div class="f-field">
                <label for="telephone">Téléphone</label>
                <input type="tel" id="telephone" name="telephone"
                       value="<?= htmlspecialchars($user['telephone'] ?? '') ?>"
                       placeholder="0550 000 000" autocomplete="tel">
              </div>
            </div>

            <div class="f-actions">
              <button type="submit" class="btn-save">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Enregistrer les modifications
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- ── Section 2 : Sécurité ── -->
      <div class="settings-section" id="securite">
        <div class="settings-head">
          <div class="settings-head-icon">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
          </div>
          <div>
            <div class="settings-head-title">Sécurité</div>
            <div class="settings-head-sub">Changer votre mot de passe</div>
          </div>
        </div>
        <div class="settings-body">

          <?php if ($err && in_array($err, ['champs_mdp','mdp_court','mdp_diff','mdp_incorrect'])): ?>
            <div class="p-alert p-alert-err" style="margin-bottom:1.25rem">
              <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
              <?= htmlspecialchars($err_messages[$err] ?? '') ?>
            </div>
          <?php endif; ?>

          <form action="traitement_profil.php" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update_password">

            <div class="f-field">
              <label for="mdp-actuel">Mot de passe actuel</label>
              <input type="password" id="mdp-actuel" name="mot_de_passe_actuel"
                     placeholder="Entrez votre mot de passe actuel" required autocomplete="current-password">
            </div>

            <div class="f-row-2">
              <div class="f-field">
                <label for="mdp-nouveau">Nouveau mot de passe</label>
                <input type="password" id="mdp-nouveau" name="nouveau_mot_de_passe"
                       placeholder="Min. 6 caractères" required autocomplete="new-password"
                       oninput="checkStrength(this.value)">
                <div class="pwd-strength">
                  <div class="pwd-bar" id="bar1"></div>
                  <div class="pwd-bar" id="bar2"></div>
                  <div class="pwd-bar" id="bar3"></div>
                  <div class="pwd-bar" id="bar4"></div>
                  <span class="pwd-label" id="pwd-label">—</span>
                </div>
              </div>
              <div class="f-field">
                <label for="mdp-confirm">Confirmer le nouveau</label>
                <input type="password" id="mdp-confirm" name="confirmer_mot_de_passe"
                       placeholder="Répétez le mot de passe" required autocomplete="new-password">
                <p class="f-hint" id="match-hint"></p>
              </div>
            </div>

            <div class="f-actions">
              <button type="submit" class="btn-danger-save">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                Changer le mot de passe
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- ── Zone danger ── -->
      <div class="danger-zone">
        <div class="danger-zone-title">Zone dangereuse</div>
        <p>La déconnexion mettra fin à votre session sur tous les appareils.</p>
        <a href="logout.php" class="btn btn-sm btn-danger">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
          Se déconnecter
        </a>
      </div>

    </div><!-- /dash-body -->
  </main>

</div>

<?php /* no footer in dashboard */ ?>

<script>
// Force de mot de passe
function checkStrength(val) {
  const bars  = ['bar1','bar2','bar3','bar4'].map(id => document.getElementById(id));
  const label = document.getElementById('pwd-label');
  let score = 0;
  if (val.length >= 6)  score++;
  if (val.length >= 10) score++;
  if (/[A-Z]/.test(val) && /[0-9]/.test(val)) score++;
  if (/[^A-Za-z0-9]/.test(val)) score++;

  const colors  = ['#f87171','#fbbf24','#4ade80','#4ade80'];
  const labels  = ['Faible','Moyen','Fort','Très fort'];
  bars.forEach((b, i) => b.style.background = i < score ? colors[score-1] : 'rgba(255,255,255,.08)');
  label.textContent  = val.length ? labels[Math.max(0,score-1)] : '—';
  label.style.color  = val.length ? colors[Math.max(0,score-1)] : 'rgba(255,255,255,.25)';
}

// Vérification correspondance en temps réel
document.getElementById('mdp-confirm').addEventListener('input', function() {
  const hint = document.getElementById('match-hint');
  const ref  = document.getElementById('mdp-nouveau').value;
  if (!this.value) { hint.textContent = ''; return; }
  if (this.value === ref) {
    hint.textContent = '✓ Les mots de passe correspondent';
    hint.style.color = '#4ade80';
  } else {
    hint.textContent = '✕ Ne correspondent pas';
    hint.style.color = '#f87171';
  }
});
</script>
</body>
</html>
