<?php
$activePage = 'home';
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MécaSpeed — Accueil</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800;900&family=Barlow:wght@400;500;600&display=swap" rel="stylesheet">
  <!-- mecaspeed-ui.css chargé via public-nav.php -->
  <style>
    /* ── Reset & base ── */
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { font-size: 16px; scroll-behavior: smooth; }
    body {
      font-family: 'Barlow', sans-serif;
      font-weight: 400;
      color: #111;
      background: #0d0d0d;
      line-height: 1.6;
    }

    /* ── Utilities ── */
    .container { max-width: 1160px; margin: 0 auto; padding: 0 1.5rem; }
    .eyebrow {
      display: inline-block;
      font-family: 'Barlow Condensed', sans-serif;
      font-size: 0.75rem;
      font-weight: 700;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: #1a6bbd;
      margin-bottom: 0.6rem;
    }
    .btn {
      display: inline-block;
      font-family: 'Barlow Condensed', sans-serif;
      font-size: 0.9rem;
      font-weight: 700;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      padding: 0.7rem 1.6rem;
      border-radius: 4px;
      text-decoration: none;
      transition: transform 0.15s, box-shadow 0.15s;
      cursor: pointer;
      border: none;
    }
    .btn:hover { transform: translateY(-2px); }
    .btn-primary { background: #1a6bbd; color: #fff; }
    .btn-primary:hover { background: #155fa0; box-shadow: 0 4px 16px rgba(26,107,189,.35); }
    .btn-light { background: #fff; color: #111; }
    .btn-light:hover { background: #f0f0f0; }
    .btn-outline {
      background: transparent;
      color: #fff;
      border: 1.5px solid rgba(255,255,255,.35);
    }
    .btn-outline:hover { border-color: #fff; background: rgba(255,255,255,.06); }
    .text-link {
      font-family: 'Barlow Condensed', sans-serif;
      font-size: 0.85rem;
      font-weight: 700;
      letter-spacing: 0.06em;
      text-transform: uppercase;
      color: #1a6bbd;
      text-decoration: none;
    }
    .text-link:hover { text-decoration: underline; }

    /* ── HEADER ── */
    .site-header {
      position: sticky; top: 0; z-index: 100;
      background: rgba(13,13,13,.92);
      backdrop-filter: blur(12px);
      border-bottom: 1px solid rgba(255,255,255,.08);
    }
    .nav-inner {
      display: flex; align-items: center; justify-content: space-between;
      height: 64px; gap: 1rem;
    }
    .nav-logo {
      font-family: 'Barlow Condensed', sans-serif;
      font-size: 1.6rem; font-weight: 900;
      letter-spacing: 0.03em; text-transform: uppercase;
      color: #fff; text-decoration: none;
    }
    .nav-logo span { color: #1a6bbd; }
    .nav-links { display: flex; align-items: center; gap: 0.25rem; flex-wrap: wrap; }
    .nav-links a {
      font-family: 'Barlow Condensed', sans-serif;
      font-size: 0.85rem; font-weight: 600;
      letter-spacing: 0.05em; text-transform: uppercase;
      color: rgba(255,255,255,.7);
      text-decoration: none; padding: 0.4rem 0.75rem; border-radius: 4px;
      transition: color .2s, background .2s;
    }
    .nav-links a:hover, .nav-links a.is-active { color: #fff; background: rgba(255,255,255,.06); }
    .nav-links a.nav-cta {
      background: #1a6bbd; color: #fff;
      padding: 0.4rem 1rem; margin-left: 0.25rem;
    }
    .nav-links a.nav-cta:hover { background: #155fa0; }
    .menu-toggle { display: none; }

    /* ── HERO ── */
    .hero {
      position: relative;
      background: #0d0d0d;
      padding: 6rem 0 5rem;
      overflow: hidden;
    }
    .hero-texture {
      position: absolute; inset: 0;
      background-image:
        radial-gradient(circle at 70% 40%, rgba(26,107,189,.13) 0%, transparent 55%),
        repeating-linear-gradient(0deg, transparent, transparent 79px, rgba(255,255,255,.025) 80px),
        repeating-linear-gradient(90deg, transparent, transparent 79px, rgba(255,255,255,.025) 80px);
      pointer-events: none;
    }
    .hero-grid {
      position: relative;
      display: grid; grid-template-columns: 1fr 1fr;
      gap: 3rem; align-items: center;
    }
    .hero-copy h1 {
      font-family: 'Barlow Condensed', sans-serif;
      font-size: clamp(3rem, 6vw, 5.5rem);
      font-weight: 900; line-height: 1.0;
      text-transform: uppercase; color: #fff;
      margin-bottom: 1.25rem;
    }
    .hero-copy h1 em { font-style: normal; color: #1a6bbd; }
    .hero-lead { color: rgba(255,255,255,.6); font-size: 1.05rem; max-width: 460px; margin-bottom: 2rem; }
    .hero-actions { display: flex; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 2rem; }
    .trust-pills { display: flex; gap: 0.5rem; flex-wrap: wrap; }
    .trust-pills span {
      font-family: 'Barlow Condensed', sans-serif;
      font-size: 0.75rem; font-weight: 700;
      letter-spacing: 0.08em; text-transform: uppercase;
      background: rgba(255,255,255,.07);
      color: rgba(255,255,255,.65);
      border: 1px solid rgba(255,255,255,.1);
      padding: 0.3rem 0.75rem; border-radius: 100px;
    }

    /* Panel */
    .hero-panel-wrap { position: relative; }
    .hero-panel {
      background: #1a1a1a;
      border: 1px solid rgba(255,255,255,.1);
      border-radius: 12px; padding: 1.75rem;
    }
    .panel-top {
      display: flex; justify-content: space-between; align-items: center;
      margin-bottom: 1.25rem;
    }
    .status-dot {
      font-family: 'Barlow Condensed', sans-serif;
      font-size: 0.75rem; font-weight: 700;
      letter-spacing: 0.08em; text-transform: uppercase;
      color: #4ade80;
      display: flex; align-items: center; gap: 0.4rem;
    }
    .status-dot::before {
      content: ''; width: 7px; height: 7px; border-radius: 50%;
      background: #4ade80;
      box-shadow: 0 0 8px #4ade80;
      animation: pulse 2s infinite;
    }
    @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.4} }
    .panel-top span:last-child { font-size: 0.8rem; color: rgba(255,255,255,.4); }
    .panel-title {
      font-family: 'Barlow Condensed', sans-serif;
      font-size: 1.1rem; font-weight: 700;
      text-transform: uppercase; color: #fff;
      margin-bottom: 1.25rem;
    }
    .steps { display: flex; flex-direction: column; gap: 0.6rem; margin-bottom: 1.5rem; }
    .step-row {
      display: flex; align-items: center; gap: 0.75rem;
      padding: 0.6rem 0.75rem; border-radius: 6px;
      background: rgba(255,255,255,.04);
      border: 1px solid rgba(255,255,255,.06);
    }
    .step-row span {
      font-family: 'Barlow Condensed', sans-serif;
      font-size: 0.75rem; font-weight: 900;
      color: #1a6bbd; min-width: 24px;
    }
    .step-row strong { font-size: 0.9rem; color: rgba(255,255,255,.85); font-weight: 600; }
    .panel-contact {
      display: flex; gap: 1.5rem;
      border-top: 1px solid rgba(255,255,255,.08); padding-top: 1rem;
    }
    .panel-contact small { display: block; font-size: 0.7rem; color: rgba(255,255,255,.4); margin-bottom: 2px; }
    .panel-contact strong { font-size: 0.85rem; color: rgba(255,255,255,.8); }
    .floating-card {
      position: absolute; bottom: -1.25rem; left: -1.25rem;
      background: #1a1a1a; border: 1px solid rgba(255,255,255,.1);
      border-radius: 10px; padding: 0.75rem 1rem;
      display: flex; align-items: center; gap: 0.75rem;
    }
    .floating-icon { font-size: 1.5rem; }
    .floating-card strong { display: block; font-size: 0.85rem; color: #fff; }
    .floating-card p { font-size: 0.75rem; color: rgba(255,255,255,.5); margin: 0; }

    /* ── STATS ── */
    .stats-band { background: #161616; border-top: 1px solid rgba(255,255,255,.07); border-bottom: 1px solid rgba(255,255,255,.07); }
    .stats-grid {
      display: grid; grid-template-columns: repeat(4, 1fr);
      text-align: center;
    }
    .stats-grid article {
      padding: 2rem 1rem;
      border-right: 1px solid rgba(255,255,255,.07);
    }
    .stats-grid article:last-child { border-right: none; }
    .stats-grid strong {
      font-family: 'Barlow Condensed', sans-serif;
      font-size: 3rem; font-weight: 900;
      color: #fff; line-height: 1; display: block;
      margin-bottom: 0.4rem;
    }
    .stats-grid strong span { color: #1a6bbd; }
    .stats-grid p { font-size: 0.8rem; color: rgba(255,255,255,.45); }

    /* ── SECTIONS commune ── */
    .section { padding: 5rem 0; }
    .section-head {
      display: flex; justify-content: space-between; align-items: flex-end;
      margin-bottom: 2.5rem; gap: 1rem;
    }
    .section-head h2 {
      font-family: 'Barlow Condensed', sans-serif;
      font-size: clamp(1.8rem, 3.5vw, 2.8rem);
      font-weight: 800; text-transform: uppercase;
      color: #fff; line-height: 1.1;
      max-width: 480px;
    }

    /* ── SERVICES ── */
    .services { background: #0d0d0d; }
    .service-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
      gap: 1px; background: rgba(255,255,255,.07);
      border: 1px solid rgba(255,255,255,.07); border-radius: 10px; overflow: hidden;
    }
    .service-card {
      background: #111; padding: 1.75rem;
      transition: background .2s;
    }
    .service-card:hover { background: #181818; }
    .badge {
      display: inline-block;
      font-family: 'Barlow Condensed', sans-serif;
      font-size: 0.7rem; font-weight: 700;
      letter-spacing: 0.1em; text-transform: uppercase;
      color: #1a6bbd; background: rgba(26,107,189,.13);
      border: 1px solid rgba(26,107,189,.2);
      padding: 0.2rem 0.6rem; border-radius: 4px;
      margin-bottom: 0.75rem;
    }
    .service-card h3 {
      font-family: 'Barlow Condensed', sans-serif;
      font-size: 1.2rem; font-weight: 700;
      text-transform: uppercase; color: #fff;
      margin-bottom: 0.5rem;
    }
    .service-card p { font-size: 0.85rem; color: rgba(255,255,255,.5); line-height: 1.5; }

    /* ── ACTIONS CLIENT ── */
    .actions-client { background: #0a0a0a; }
    .actions-grid {
      display: grid; grid-template-columns: 1fr 1.6fr;
      gap: 3rem; align-items: start;
    }
    .actions-copy h2 {
      font-family: 'Barlow Condensed', sans-serif;
      font-size: clamp(1.8rem, 3vw, 2.6rem);
      font-weight: 800; text-transform: uppercase;
      color: #fff; line-height: 1.1; margin-bottom: 1rem;
    }
    .actions-copy p { font-size: 0.9rem; color: rgba(255,255,255,.5); line-height: 1.6; }
    .action-stack { display: flex; flex-direction: column; gap: 1px; }
    .action-card {
      background: #141414;
      border: 1px solid rgba(255,255,255,.08);
      border-radius: 8px;
      padding: 1.5rem;
      display: flex; gap: 1.25rem; align-items: flex-start;
      transition: border-color .2s;
      margin-bottom: 10px;
    }
    .action-card:hover { border-color: rgba(26,107,189,.3); }
    .action-icon { font-size: 1.75rem; flex-shrink: 0; line-height: 1; }
    .action-card h3 {
      font-family: 'Barlow Condensed', sans-serif;
      font-size: 1.1rem; font-weight: 700;
      text-transform: uppercase; color: #fff;
      margin-bottom: 0.4rem;
    }
    .action-card p { font-size: 0.85rem; color: rgba(255,255,255,.5); margin-bottom: 0.85rem; }

    /* ── FONCTIONS (roles) ── */
    .section-fonctions { background: #0d0d0d; padding: 5rem 0; }
    .section-fonctions header { margin-bottom: 2.5rem; }
    .section-fonctions header h2 {
      font-family: 'Barlow Condensed', sans-serif;
      font-size: clamp(1.8rem, 3.5vw, 2.8rem);
      font-weight: 800; text-transform: uppercase;
      color: #fff; line-height: 1.1;
    }
    .liste-fonctions {
      display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 1.25rem;
    }
    .liste-fonctions article {
      background: #141414;
      border: 1px solid rgba(255,255,255,.08);
      border-radius: 10px; padding: 1.75rem;
      transition: border-color .2s;
    }
    .liste-fonctions article:hover { border-color: rgba(26,107,189,.3); }
    .liste-fonctions h3 {
      font-family: 'Barlow Condensed', sans-serif;
      font-size: 1.1rem; font-weight: 800;
      text-transform: uppercase; color: #1a6bbd;
      margin-bottom: 1rem; letter-spacing: 0.05em;
    }
    .liste-fonctions ul { list-style: none; padding: 0; }
    .liste-fonctions li {
      font-size: 0.85rem; color: rgba(255,255,255,.6);
      padding: 0.45rem 0;
      border-bottom: 1px solid rgba(255,255,255,.05);
      padding-left: 1rem; position: relative;
    }
    .liste-fonctions li::before {
      content: '→'; position: absolute; left: 0;
      color: #1a6bbd; font-size: 0.75rem;
    }
    .liste-fonctions li:last-child { border-bottom: none; }

    /* ── CTA FINAL ── */
    .final-cta { background: #111; border-top: 1px solid rgba(255,255,255,.07); padding: 5rem 0; }
    .cta-panel {
      display: flex; justify-content: space-between; align-items: center;
      gap: 2rem; flex-wrap: wrap;
    }
    .cta-panel h2 {
      font-family: 'Barlow Condensed', sans-serif;
      font-size: clamp(1.8rem, 3.5vw, 2.8rem);
      font-weight: 800; text-transform: uppercase;
      color: #fff; margin-bottom: 0.5rem;
    }
    .cta-panel p { font-size: 0.9rem; color: rgba(255,255,255,.5); max-width: 520px; }
    .cta-actions { display: flex; gap: 0.75rem; flex-shrink: 0; flex-wrap: wrap; }

    /* ── FOOTER ── */
    .site-footer {
      background: #0a0a0a;
      border-top: 1px solid rgba(255,255,255,.07);
      padding: 3rem 0 1.5rem;
    }
    .footer-grid {
      display: grid; grid-template-columns: 2fr 1fr 1fr 1fr;
      gap: 2rem; margin-bottom: 2rem;
    }
    .footer-logo {
      font-family: 'Barlow Condensed', sans-serif;
      font-size: 1.4rem; font-weight: 900;
      text-transform: uppercase; color: #fff;
      display: block; margin-bottom: 0.75rem;
    }
    .footer-logo span { color: #1a6bbd; }
    .footer-grid > div > p { font-size: 0.8rem; color: rgba(255,255,255,.4); line-height: 1.5; }
    .footer-grid h4 {
      font-family: 'Barlow Condensed', sans-serif;
      font-size: 0.75rem; font-weight: 700;
      letter-spacing: 0.1em; text-transform: uppercase;
      color: rgba(255,255,255,.3); margin-bottom: 0.75rem;
    }
    .footer-grid a {
      display: block; font-size: 0.85rem;
      color: rgba(255,255,255,.55); text-decoration: none;
      margin-bottom: 0.4rem; transition: color .2s;
    }
    .footer-grid a:hover { color: #fff; }
    .footer-bottom {
      display: flex; justify-content: space-between;
      border-top: 1px solid rgba(255,255,255,.07); padding-top: 1.25rem;
      font-size: 0.75rem; color: rgba(255,255,255,.25);
    }

    /* ── PAGE HEADER (hero index.php) ── */
    .page-header {
      position: relative;
      background: #0d0d0d;
      padding: 6rem 0 5rem;
      overflow: hidden;
    }
    .page-header::before {
      content: '';
      position: absolute; inset: 0;
      background-image:
        radial-gradient(circle at 70% 40%, rgba(26,107,189,.13) 0%, transparent 55%),
        repeating-linear-gradient(0deg, transparent, transparent 79px, rgba(255,255,255,.025) 80px),
        repeating-linear-gradient(90deg, transparent, transparent 79px, rgba(255,255,255,.025) 80px);
      pointer-events: none;
    }
    .page-header > * { position: relative; }
    .page-header .eyebrow-line {
      font-family: 'Barlow Condensed', sans-serif;
      font-size: 0.75rem; font-weight: 700;
      letter-spacing: 0.12em; text-transform: uppercase;
      color: #1a6bbd; display: block; margin-bottom: 0.75rem;
    }
    .page-header h2 {
      font-family: 'Barlow Condensed', sans-serif;
      font-size: clamp(2.4rem, 5.5vw, 5rem);
      font-weight: 900; line-height: 1.0;
      text-transform: uppercase; color: #fff;
      margin-bottom: 1.25rem; max-width: 780px;
    }
    .page-header > p {
      color: rgba(255,255,255,.6); font-size: 1.05rem;
      max-width: 560px; margin-bottom: 2.25rem; line-height: 1.65;
    }
    .page-header nav { display: flex; gap: 0.75rem; flex-wrap: wrap; }

    /* ── SERVICES (index.php) ── */
    .section-services { background: #0d0d0d; padding: 5rem 0; }
    .section-services header { margin-bottom: 2.5rem; }
    .section-services header h2 {
      font-family: 'Barlow Condensed', sans-serif;
      font-size: clamp(1.8rem, 3.5vw, 2.8rem);
      font-weight: 800; text-transform: uppercase;
      color: #fff; line-height: 1.1;
    }
    .liste-services {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
      gap: 1px; background: rgba(255,255,255,.07);
      border: 1px solid rgba(255,255,255,.07); border-radius: 10px; overflow: hidden;
    }
    .liste-services article {
      background: #111; padding: 1.75rem;
      transition: background .2s;
    }
    .liste-services article:hover { background: #181818; }
    .liste-services h3 {
      font-family: 'Barlow Condensed', sans-serif;
      font-size: 1.2rem; font-weight: 700;
      text-transform: uppercase; color: #fff;
      margin-bottom: 0.5rem;
    }
    .liste-services p { font-size: 0.85rem; color: rgba(255,255,255,.5); line-height: 1.5; }

    /* ── RESPONSIVE ── */
    @media (max-width: 900px) {
      .hero-grid, .actions-grid { grid-template-columns: 1fr; }
      .floating-card { display: none; }
      .stats-grid { grid-template-columns: repeat(2, 1fr); }
      .stats-grid article { border-right: none; border-bottom: 1px solid rgba(255,255,255,.07); }
      .footer-grid { grid-template-columns: 1fr 1fr; }
      .cta-panel { flex-direction: column; align-items: flex-start; }
    }
    @media (max-width: 600px) {
      .nav-links { display: none; }
      .menu-toggle { display: flex; flex-direction: column; gap: 5px; background: none; border: none; cursor: pointer; padding: 4px; }
      .menu-toggle span { display: block; width: 24px; height: 2px; background: #fff; border-radius: 2px; }
      .stats-grid { grid-template-columns: 1fr 1fr; }
      .footer-grid { grid-template-columns: 1fr; }
    }
  </style>
</head>
<body>
<?php require __DIR__ . '/includes/public-nav.php'; ?>

<main id="contenu-principal">

  <!-- HERO -->
  <section class="page-header" aria-labelledby="titre-accueil">
    <div class="container">
      <span class="eyebrow-line">Garage connecté — service rapide</span>
      <h2 id="titre-accueil">Entretien, rendez-vous,<br>boutique et suivi<br><em style="font-style:normal;color:#1a6bbd">véhicule.</em></h2>
      <p>
        MécaSpeed centralise les services principaux d'un garage rapide :
        réservation, gestion des véhicules, commande de pièces et suivi des réparations.
      </p>
      <nav aria-label="Actions principales">
        <a class="btn btn-primary" href="calendrier.php">Prendre un rendez-vous</a>
        <a class="btn btn-light" href="boutique.php">Voir la boutique</a>
        <a class="btn btn-outline" href="suivi.php">Suivre un véhicule</a>
      </nav>

      <div class="trust-pills" style="margin-top:2rem">
        <span>Disponible 24h/24</span>
        <span>Diagnostic clair</span>
        <span>Suivi en ligne</span>
        <span>Panier pièces</span>
      </div>
    </div>
  </section>

  <!-- STATS -->
  <section class="stats-band" aria-label="Chiffres clés">
    <div class="container stats-grid">
      <article>
        <strong>24<span>h</span></strong>
        <p>Accès aux services en ligne</p>
      </article>
      <article>
        <strong>6<span>+</span></strong>
        <p>Interventions rapides principales</p>
      </article>
      <article>
        <strong>3</strong>
        <p>Rôles utilisateurs distincts</p>
      </article>
      <article>
        <strong>100<span>%</span></strong>
        <p>Expérience digitalisée</p>
      </article>
    </div>
  </section>

  <!-- SERVICES -->
  <section class="section-services" aria-labelledby="titre-services">
    <div class="container">
      <header>
        <span class="eyebrow">Services atelier</span>
        <h2 id="titre-services">Interventions disponibles</h2>
      </header>
      <div class="liste-services">
        <article>
          <span class="badge">Entretien</span>
          <h3>Vidange et filtres</h3>
          <p>Vidange moteur, remplacement des filtres et contrôle des niveaux.</p>
        </article>
        <article>
          <span class="badge">Sécurité</span>
          <h3>Freinage</h3>
          <p>Contrôle et remplacement des plaquettes, disques et composants de freinage.</p>
        </article>
        <article>
          <span class="badge">Pneus</span>
          <h3>Pneus</h3>
          <p>Montage, vérification de l'état des pneus et conseils de remplacement.</p>
        </article>
        <article>
          <span class="badge">Énergie</span>
          <h3>Batterie</h3>
          <p>Diagnostic de démarrage, contrôle de charge et remplacement batterie.</p>
        </article>
        <article>
          <span class="badge">Contrôle</span>
          <h3>Diagnostic électronique</h3>
          <p>Lecture des anomalies et préparation d'une intervention adaptée.</p>
        </article>
        <article>
          <span class="badge">Contrôle</span>
          <h3>Contrôle général</h3>
          <p>Vérification visuelle, niveaux, éclairage et points de sécurité essentiels.</p>
        </article>
      </div>
    </div>
  </section>

  <!-- FONCTIONS PAR RÔLE -->
  <section class="section-fonctions" aria-labelledby="titre-fonctions">
    <div class="container">
      <header>
        <span class="eyebrow">Fonctions du site</span>
        <h2 id="titre-fonctions">Parcours complet pour chaque utilisateur</h2>
      </header>
      <div class="liste-fonctions">
        <article>
          <h3>Client</h3>
          <ul>
            <li>Créer un compte et se connecter.</li>
            <li>Ajouter ses véhicules.</li>
            <li>Prendre un rendez-vous avec choix du garage, service, date et heure.</li>
            <li>Consulter le suivi de réparation créé par le mécanicien.</li>
            <li>Commander des produits depuis la boutique.</li>
          </ul>
        </article>
        <article>
          <h3>Mécanicien</h3>
          <ul>
            <li>Voir les rendez-vous assignés.</li>
            <li>Confirmer ou annuler un rendez-vous.</li>
            <li>Créer ou modifier le suivi d'un véhicule.</li>
            <li>Informer le client avec statut, progression, ETA et note.</li>
          </ul>
        </article>
        <article>
          <h3>Administrateur</h3>
          <ul>
            <li>Consulter les rendez-vous.</li>
            <li>Consulter les véhicules enregistrés.</li>
            <li>Surveiller le stock boutique.</li>
            <li>Mettre à jour les quantités de stock.</li>
          </ul>
        </article>
      </div>
    </div>
  </section>

  <!-- CTA FINAL -->
  <section class="final-cta">
    <div class="container cta-panel">
      <div>
        <span class="eyebrow">Passez à l'action</span>
        <h2>Besoin d'un entretien ou d'un suivi véhicule ?</h2>
        <p>MécaSpeed centralise vos demandes pour réduire l'attente et améliorer la communication avec le garage.</p>
      </div>
      <div class="cta-actions">
        <a class="btn btn-primary" href="calendrier.php">Prendre RDV</a>
        <a class="btn btn-light" href="boutique.php">Voir la boutique</a>
      </div>
    </div>
  </section>

</main>

<?php require __DIR__ . '/includes/page-footer.php'; ?>
</body>
</html>