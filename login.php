<?php
$activePage = 'login';
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
  <title>MécaSpeed — Connexion</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800;900&family=Barlow:wght@400;500;600&display=swap" rel="stylesheet">
  <style>
  *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
  html, body { height: 100%; }
  body { font-family: 'Barlow', sans-serif; background: #080808; color: #fff; -webkit-font-smoothing: antialiased; }
  a { text-decoration: none; color: inherit; }

  /* ── Split layout ── */
  .split {
    display: grid;
    grid-template-columns: 1fr 1fr;
    min-height: 100vh;
  }

  /* ── LEFT — brand panel ── */
  .split-left {
    position: relative;
    background: #0a0a0a;
    border-right: 1px solid rgba(255,255,255,.06);
    display: flex; flex-direction: column;
    justify-content: space-between;
    padding: 2.5rem;
    overflow: hidden;
  }
  /* Grid texture */
  .split-left::before {
    content: '';
    position: absolute; inset: 0;
    background:
      repeating-linear-gradient(0deg,  transparent, transparent 59px, rgba(255,255,255,.025) 60px),
      repeating-linear-gradient(90deg, transparent, transparent 59px, rgba(255,255,255,.025) 60px);
    pointer-events: none;
  }
  /* Blue glow */
  .split-left::after {
    content: '';
    position: absolute;
    bottom: -10%; left: -10%;
    width: 70%; height: 60%;
    background: radial-gradient(ellipse, rgba(26,107,189,.18) 0%, transparent 70%);
    pointer-events: none;
  }

  .left-top { position: relative; z-index: 1; }
  .left-logo {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 1.4rem; font-weight: 900;
    text-transform: uppercase; letter-spacing: .03em; color: #fff;
    display: inline-block; margin-bottom: 3.5rem;
  }
  .left-logo span { color: #1a6bbd; }

  .left-headline {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: clamp(2.4rem, 3.5vw, 3.5rem);
    font-weight: 900; text-transform: uppercase;
    line-height: .96; color: #fff; margin-bottom: 1.1rem;
  }
  .left-headline em { font-style: normal; color: #1a6bbd; }
  .left-sub {
    font-size: .9rem; color: rgba(255,255,255,.45);
    line-height: 1.6; max-width: 320px; margin-bottom: 2.5rem;
  }

  /* Feature list */
  .feat-list { display: flex; flex-direction: column; gap: .65rem; }
  .feat-item {
    display: flex; align-items: center; gap: .75rem;
    font-family: 'Barlow Condensed', sans-serif;
    font-size: .8rem; font-weight: 700;
    letter-spacing: .06em; text-transform: uppercase;
    color: rgba(255,255,255,.5);
  }
  .feat-dot {
    width: 6px; height: 6px; border-radius: 50%;
    background: #1a6bbd; flex-shrink: 0;
    box-shadow: 0 0 8px rgba(26,107,189,.6);
  }

  /* Bottom */
  .left-bottom { position: relative; z-index: 1; }
  .left-copyright {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: .65rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase;
    color: rgba(255,255,255,.18);
  }

  /* ── RIGHT — form panel ── */
  .split-right {
    background: #0d0d0d;
    display: flex; align-items: center; justify-content: center;
    padding: 2.5rem;
  }

  .form-wrap {
    width: 100%; max-width: 400px;
  }

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
  .form-sub {
    font-size: .85rem; color: rgba(255,255,255,.35);
    line-height: 1.55; margin-bottom: 2.25rem;
  }

  /* Fields */
  .f-field { margin-bottom: 1rem; }
  .f-field label {
    display: block;
    font-family: 'Barlow Condensed', sans-serif;
    font-size: .68rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase;
    color: rgba(255,255,255,.3); margin-bottom: .4rem;
  }
  .f-field input {
    width: 100%;
    background: rgba(255,255,255,.04);
    border: 1px solid rgba(255,255,255,.1);
    border-radius: 8px; color: #fff;
    font-family: 'Barlow', sans-serif; font-size: .95rem;
    padding: .75rem 1rem; outline: none;
    transition: border-color .18s, background .18s, box-shadow .18s;
  }
  .f-field input:focus {
    border-color: #1a6bbd;
    background: rgba(26,107,189,.05);
    box-shadow: 0 0 0 3px rgba(26,107,189,.15);
  }
  .f-field input::placeholder { color: rgba(255,255,255,.15); }

  /* Submit */
  .btn-submit {
    width: 100%; margin-top: 1.5rem;
    display: flex; align-items: center; justify-content: center; gap: .5rem;
    font-family: 'Barlow Condensed', sans-serif;
    font-size: .9rem; font-weight: 700; letter-spacing: .07em; text-transform: uppercase;
    background: #1a6bbd; color: #fff;
    border: none; border-radius: 8px;
    padding: .85rem 1.5rem; cursor: pointer;
    transition: background .15s, box-shadow .15s, transform .15s;
  }
  .btn-submit:hover {
    background: #155fa0;
    box-shadow: 0 4px 24px rgba(26,107,189,.4);
    transform: translateY(-1px);
  }
  .btn-submit svg { flex-shrink: 0; }

  /* Alert */
  .f-alert {
    display: flex; align-items: center; gap: .6rem;
    font-family: 'Barlow Condensed', sans-serif;
    font-size: .78rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase;
    padding: .7rem .9rem; border-radius: 6px; border: 1px solid; margin-bottom: 1.25rem;
  }
  .f-alert-info  { background: rgba(26,107,189,.1); border-color: rgba(26,107,189,.25); color: #72b4f5; }
  .f-alert-error { background: rgba(248,113,113,.1); border-color: rgba(248,113,113,.25); color: #f87171; }

  /* Divider */
  .f-divider {
    display: flex; align-items: center; gap: .75rem;
    margin: 1.75rem 0 1.25rem;
  }
  .f-divider-line { flex: 1; height: 1px; background: rgba(255,255,255,.08); }
  .f-divider span {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: .65rem; font-weight: 700; letter-spacing: .1em; text-transform: uppercase;
    color: rgba(255,255,255,.2);
  }

  /* Footer link */
  .f-footer {
    text-align: center;
    font-size: .85rem; color: rgba(255,255,255,.3);
  }
  .f-footer a { color: #1a6bbd; font-weight: 600; }
  .f-footer a:hover { text-decoration: underline; }

  /* Error div from JS */
  #erreur-login:not(:empty) {
    display: flex; align-items: center; gap: .6rem;
    font-family: 'Barlow Condensed', sans-serif;
    font-size: .78rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase;
    padding: .7rem .9rem; border-radius: 6px;
    background: rgba(248,113,113,.1); border: 1px solid rgba(248,113,113,.25); color: #f87171;
    margin-bottom: 1.25rem;
  }

  /* ── Responsive ── */
  @media (max-width: 768px) {
    .split { grid-template-columns: 1fr; }
    .split-left { display: none; }
    .split-right { padding: 2rem 1.5rem; align-items: flex-start; padding-top: 3rem; }
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
        Votre garage,<br>
        votre <em>espace</em><br>
        en ligne.
      </h1>
      <p class="left-sub">
        Gérez vos rendez-vous, suivez vos interventions et commandez vos pièces depuis un seul endroit.
      </p>

      <div class="feat-list">
        <div class="feat-item"><span class="feat-dot"></span>Prise de rendez-vous en ligne</div>
        <div class="feat-item"><span class="feat-dot"></span>Suivi d'intervention en temps réel</div>
        <div class="feat-item"><span class="feat-dot"></span>Boutique pièces et lubrifiants</div>
        <div class="feat-item"><span class="feat-dot"></span>Espace mécanicien & administration</div>
      </div>
    </div>

    <div class="left-bottom">
      <p class="left-copyright">© 2025/2026 MécaSpeed — UMMTO</p>
    </div>
  </div>

  <!-- ── RIGHT ── -->
  <div class="split-right">
    <div class="form-wrap">

      <span class="form-eyebrow">Bienvenue</span>
      <h2 class="form-title">Connexion</h2>
      <p class="form-sub">Entrez vos identifiants. L'espace adapté à votre rôle s'ouvrira automatiquement.</p>

      <?php if (isset($_GET['erreur']) && $_GET['erreur'] === 'connexion_requise'): ?>
        <div class="f-alert f-alert-info">
          <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
          Connectez-vous pour continuer.
        </div>
      <?php endif; ?>

      <div id="erreur-login" role="alert"></div>

      <form action="traitement_connexion.php" method="POST" id="login-form">
        <?= csrf_field() ?>

        <div class="f-field">
          <label for="email">Adresse e-mail</label>
          <input type="email" id="email" name="email"
                 placeholder="exemple@domaine.com" required autocomplete="email">
        </div>

        <div class="f-field">
          <label for="mot_de_passe">Mot de passe</label>
          <input type="password" id="mot_de_passe" name="mot_de_passe"
                 placeholder="••••••••" required autocomplete="current-password">
        </div>

        <button type="submit" class="btn-submit">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><polyline points="10 17 15 12 10 7"/><line x1="15" y1="12" x2="3" y2="12"/></svg>
          Se connecter
        </button>
      </form>

      <div class="f-divider">
        <span class="f-divider-line"></span>
        <span>ou</span>
        <span class="f-divider-line"></span>
      </div>

      <p class="f-footer">
        Pas encore de compte ? <a href="inscription.php">Créer un compte</a>
      </p>

    </div>
  </div>

</div>
<script src="login.js"></script>
</body>
</html>
