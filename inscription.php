<?php
$activePage = 'inscription';
session_start();
require_once 'includes/security.php';
if (isset($_SESSION['user_id'])) {
  $role = $_SESSION['role'] ?? '';
  header('Location: ' . ($role === 'mecano' ? 'espace_mecano.php' : ($role === 'admin' ? 'espace_admin.php' : 'espace_client.php')));
  exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MécaSpeed — Inscription</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800;900&family=Barlow:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  html, body { height: 100%; }
  body { font-family: 'Barlow', sans-serif; background: #080808; color: #fff; -webkit-font-smoothing: antialiased; }
  a { text-decoration: none; color: inherit; }

  .split {
    display: grid;
    grid-template-columns: 1fr 1fr;
    min-height: 100vh;
  }

  /* ── LEFT ── */
  .split-left {
    position: relative; background: #0a0a0a;
    border-right: 1px solid rgba(255,255,255,.06);
    display: flex; flex-direction: column; justify-content: space-between;
    padding: 2.5rem; overflow: hidden;
  }
  .split-left::before {
    content: ''; position: absolute; inset: 0;
    background:
      repeating-linear-gradient(0deg,  transparent, transparent 59px, rgba(255,255,255,.025) 60px),
      repeating-linear-gradient(90deg, transparent, transparent 59px, rgba(255,255,255,.025) 60px);
    pointer-events: none;
  }
  .split-left::after {
    content: ''; position: absolute;
    top: -10%; right: -10%; width: 60%; height: 55%;
    background: radial-gradient(ellipse, rgba(26,107,189,.15) 0%, transparent 70%);
    pointer-events: none;
  }

  .left-top { position: relative; z-index: 1; }
  .left-logo {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 1.4rem; font-weight: 900; text-transform: uppercase;
    letter-spacing: .03em; color: #fff; display: inline-block; margin-bottom: 3.5rem;
  }
  .left-logo span { color: #1a6bbd; }
  .left-headline {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: clamp(2.2rem, 3.2vw, 3.2rem);
    font-weight: 900; text-transform: uppercase;
    line-height: .96; color: #fff; margin-bottom: 1.1rem;
  }
  .left-headline em { font-style: normal; color: #1a6bbd; }
  .left-sub {
    font-size: .9rem; color: rgba(255,255,255,.45);
    line-height: 1.6; max-width: 320px; margin-bottom: 2.5rem;
  }

  /* Steps */
  .steps-list { display: flex; flex-direction: column; gap: 1.1rem; }
  .step {
    display: flex; gap: 1rem; align-items: flex-start;
  }
  .step-num {
    width: 28px; height: 28px; border-radius: 8px; flex-shrink: 0;
    background: rgba(26,107,189,.18); border: 1px solid rgba(26,107,189,.3);
    display: flex; align-items: center; justify-content: center;
    font-family: 'Barlow Condensed', sans-serif;
    font-size: .8rem; font-weight: 900; color: #1a6bbd;
  }
  .step-info { padding-top: 3px; }
  .step-title {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: .8rem; font-weight: 800; text-transform: uppercase;
    letter-spacing: .06em; color: #fff; margin-bottom: 2px;
  }
  .step-desc { font-size: .78rem; color: rgba(255,255,255,.35); line-height: 1.4; }

  .left-bottom { position: relative; z-index: 1; }
  .left-copyright {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: .65rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase;
    color: rgba(255,255,255,.18);
  }

  /* ── RIGHT ── */
  .split-right {
    background: #0d0d0d;
    display: flex; align-items: center; justify-content: center;
    padding: 2.5rem;
    overflow-y: auto;
  }

  .form-wrap { width: 100%; max-width: 420px; padding: 1rem 0; }

  .form-eyebrow {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: .68rem; font-weight: 700; letter-spacing: .14em; text-transform: uppercase;
    color: #1a6bbd; margin-bottom: .6rem; display: block;
  }
  .form-title {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 2rem; font-weight: 900; text-transform: uppercase;
    color: #fff; letter-spacing: .02em; line-height: 1; margin-bottom: .4rem;
  }
  .form-sub { font-size: .85rem; color: rgba(255,255,255,.35); line-height: 1.55; margin-bottom: 2rem; }

  /* Fieldset separator */
  .f-section {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: .62rem; font-weight: 700; letter-spacing: .14em; text-transform: uppercase;
    color: rgba(255,255,255,.25); margin-bottom: .85rem; margin-top: 1.5rem;
    display: flex; align-items: center; gap: .6rem;
  }
  .f-section::after { content:''; flex:1; height:1px; background:rgba(255,255,255,.07); }
  .f-section:first-of-type { margin-top: 0; }

  /* Fields */
  .f-row { display: grid; grid-template-columns: 1fr 1fr; gap: 0 .85rem; }
  .f-field { margin-bottom: .9rem; }
  .f-field label {
    display: block;
    font-family: 'Barlow Condensed', sans-serif;
    font-size: .68rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase;
    color: rgba(255,255,255,.3); margin-bottom: .38rem;
  }
  .f-field input[type="text"],
  .f-field input[type="email"],
  .f-field input[type="password"],
  .f-field input[type="tel"] {
    width: 100%;
    background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.1);
    border-radius: 8px; color: #fff;
    font-family: 'Barlow', sans-serif; font-size: .9rem;
    padding: .7rem 1rem; outline: none;
    transition: border-color .18s, background .18s, box-shadow .18s;
  }
  .f-field input:focus {
    border-color: #1a6bbd; background: rgba(26,107,189,.05);
    box-shadow: 0 0 0 3px rgba(26,107,189,.15);
  }
  .f-field input::placeholder { color: rgba(255,255,255,.15); }

  /* Checkbox */
  .f-check { display: flex; align-items: flex-start; gap: .7rem; margin-top: .5rem; }
  .f-check input[type="checkbox"] { width:17px; height:17px; flex-shrink:0; accent-color:#1a6bbd; margin-top:2px; cursor:pointer; }
  .f-check label { font-size:.82rem; color:rgba(255,255,255,.35); line-height:1.45; cursor:pointer; }
  .f-check label a { color:#1a6bbd; }

  /* Submit */
  .btn-submit {
    width: 100%; margin-top: 1.5rem;
    display: flex; align-items: center; justify-content: center; gap: .5rem;
    font-family: 'Barlow Condensed', sans-serif;
    font-size: .9rem; font-weight: 700; letter-spacing: .07em; text-transform: uppercase;
    background: #1a6bbd; color: #fff; border: none; border-radius: 8px;
    padding: .85rem 1.5rem; cursor: pointer;
    transition: background .15s, box-shadow .15s, transform .15s;
  }
  .btn-submit:hover { background:#155fa0; box-shadow:0 4px 24px rgba(26,107,189,.4); transform:translateY(-1px); }

  /* Divider */
  .f-divider { display:flex; align-items:center; gap:.75rem; margin:1.5rem 0 1.1rem; }
  .f-divider-line { flex:1; height:1px; background:rgba(255,255,255,.08); }
  .f-divider span { font-family:'Barlow Condensed',sans-serif; font-size:.65rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:rgba(255,255,255,.2); }

  /* Footer */
  .f-footer { text-align:center; font-size:.85rem; color:rgba(255,255,255,.3); }
  .f-footer a { color:#1a6bbd; font-weight:600; }
  .f-footer a:hover { text-decoration:underline; }

  #erreur-inscription:not(:empty) {
    display:flex; align-items:center; gap:.6rem;
    font-family:'Barlow Condensed',sans-serif; font-size:.78rem; font-weight:700;
    letter-spacing:.04em; text-transform:uppercase;
    padding:.7rem .9rem; border-radius:6px;
    background:rgba(248,113,113,.1); border:1px solid rgba(248,113,113,.25); color:#f87171;
    margin-bottom:1.1rem;
  }

  @media (max-width: 768px) {
    .split { grid-template-columns: 1fr; }
    .split-left { display: none; }
    .split-right { padding: 2rem 1.5rem; align-items: flex-start; padding-top: 3rem; }
    .f-row { grid-template-columns: 1fr; }
  }
  </style>
</head>
<body>
<div class="split">

  <!-- ── LEFT ── -->
  <div class="split-left">
    <div class="left-top">
      <a href="index.php" class="left-logo">Méca<span>Speed</span></a>

      <h1 class="left-headline">
        Rejoignez<br>
        <em>MécaSpeed</em><br>
        en 2 minutes.
      </h1>
      <p class="left-sub">
        Créez votre espace client et accédez à tous les services du garage en ligne.
      </p>

      <div class="steps-list">
        <div class="step">
          <div class="step-num">1</div>
          <div class="step-info">
            <div class="step-title">Créez votre compte</div>
            <div class="step-desc">Remplissez le formulaire en 30 secondes.</div>
          </div>
        </div>
        <div class="step">
          <div class="step-num">2</div>
          <div class="step-info">
            <div class="step-title">Ajoutez vos véhicules</div>
            <div class="step-desc">Enregistrez une ou plusieurs voitures.</div>
          </div>
        </div>
        <div class="step">
          <div class="step-num">3</div>
          <div class="step-info">
            <div class="step-title">Prenez rendez-vous</div>
            <div class="step-desc">Choisissez un créneau et un service.</div>
          </div>
        </div>
        <div class="step">
          <div class="step-num">4</div>
          <div class="step-info">
            <div class="step-title">Suivez en temps réel</div>
            <div class="step-desc">Consultez l'avancement de votre réparation.</div>
          </div>
        </div>
      </div>
    </div>

    <div class="left-bottom">
      <p class="left-copyright">© 2025/2026 MécaSpeed — UMMTO</p>
    </div>
  </div>

  <!-- ── RIGHT ── -->
  <div class="split-right">
    <div class="form-wrap">

      <span class="form-eyebrow">Nouveau compte</span>
      <h2 class="form-title">Inscription</h2>
      <p class="form-sub">Créez votre compte client pour accéder à tous les services MécaSpeed.</p>

      <div id="erreur-inscription" role="alert"></div>

      <form action="traitement_inscription.php" method="POST" id="inscription-form">
        <?= csrf_field() ?>

        <div class="f-section">Identité</div>

        <div class="f-field">
          <label for="client-nom">Nom complet</label>
          <input type="text" id="client-nom" name="nom" placeholder="Prénom Nom" required autocomplete="name">
        </div>

        <div class="f-row">
          <div class="f-field">
            <label for="client-tel">Téléphone</label>
            <input type="tel" id="client-tel" name="telephone" placeholder="0550 000 000" required autocomplete="tel">
          </div>
          <div class="f-field">
            <label for="client-email">E-mail</label>
            <input type="email" id="client-email" name="email" placeholder="vous@domaine.com" required autocomplete="email">
          </div>
        </div>

        <div class="f-section">Sécurité</div>

        <div class="f-row">
          <div class="f-field">
            <label for="client-password">Mot de passe</label>
            <input type="password" id="client-password" name="mot_de_passe" placeholder="••••••••" required autocomplete="new-password">
          </div>
          <div class="f-field">
            <label for="client-password-confirm">Confirmer</label>
            <input type="password" id="client-password-confirm" placeholder="••••••••" required autocomplete="new-password">
          </div>
        </div>

        <input type="hidden" name="role" value="client">

        <div class="f-check">
          <input type="checkbox" id="accept-conditions" name="conditions" required>
          <label for="accept-conditions">J'accepte les conditions d'utilisation de MécaSpeed.</label>
        </div>

        <button type="submit" class="btn-submit">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
          Créer mon compte
        </button>
      </form>

      <div class="f-divider">
        <span class="f-divider-line"></span>
        <span>ou</span>
        <span class="f-divider-line"></span>
      </div>

      <p class="f-footer">
        Déjà inscrit ? <a href="login.php">Se connecter</a>
      </p>

    </div>
  </div>

</div>
<script src="inscription.js"></script>
</body>
</html>
