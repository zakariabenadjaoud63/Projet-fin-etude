<?php
$activePage = 'contact';
session_start();

$sent = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $nom     = trim($_POST['nom']     ?? '');
  $email   = trim($_POST['email']   ?? '');
  $sujet   = trim($_POST['sujet']   ?? '');
  $message = trim($_POST['message'] ?? '');

  if (!$nom || !$email || !$sujet || !$message) {
    $error = 'Veuillez remplir tous les champs.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $error = 'Adresse e-mail invalide.';
  } else {
    // Ici on pourrait envoyer un mail — pour demo on simule
    $sent = true;
  }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MécaSpeed — Contact</title>
  <style>
  /* ── Hero ── */
  .contact-hero {
    position:relative; background:#0a0a0a;
    padding:5rem 0 4rem; overflow:hidden;
    border-bottom:1px solid rgba(255,255,255,.06);
  }
  .contact-hero::before {
    content:''; position:absolute; inset:0;
    background:
      radial-gradient(ellipse at 30% 50%, rgba(26,107,189,.12) 0%, transparent 55%),
      repeating-linear-gradient(0deg,  transparent, transparent 59px, rgba(255,255,255,.02) 60px),
      repeating-linear-gradient(90deg, transparent, transparent 59px, rgba(255,255,255,.02) 60px);
    pointer-events:none;
  }
  .contact-hero-inner { position:relative; z-index:1; }
  .contact-eyebrow {
    display:inline-block; font-family:'Barlow Condensed',sans-serif;
    font-size:.72rem; font-weight:700; letter-spacing:.14em; text-transform:uppercase;
    color:#1a6bbd; margin-bottom:.75rem;
  }
  .contact-hero h1 {
    font-family:'Barlow Condensed',sans-serif;
    font-size:clamp(2.8rem,6vw,4.8rem); font-weight:900; text-transform:uppercase;
    color:#fff; line-height:1; margin-bottom:1rem;
  }
  .contact-hero h1 em { font-style:normal; color:#1a6bbd; }
  .contact-hero p { font-size:1rem; color:rgba(255,255,255,.42); max-width:480px; line-height:1.65; }

  /* ── Main layout ── */
  .contact-body { padding:4.5rem 0 5rem; background:#0d0d0d; }
  .contact-grid {
    display:grid; grid-template-columns:1fr 1.4fr;
    gap:3.5rem; align-items:start;
  }

  /* ── Info panel ── */
  .contact-info h2 {
    font-family:'Barlow Condensed',sans-serif; font-size:1.4rem; font-weight:800;
    text-transform:uppercase; color:#fff; margin-bottom:.5rem;
  }
  .contact-info-sub { font-size:.88rem; color:rgba(255,255,255,.38); line-height:1.6; margin-bottom:2.5rem; }

  .info-items { display:flex; flex-direction:column; gap:1.25rem; margin-bottom:2.5rem; }
  .info-item {
    display:flex; align-items:flex-start; gap:1rem;
  }
  .info-icon {
    width:40px; height:40px; flex-shrink:0; border-radius:10px;
    background:rgba(26,107,189,.1); border:1px solid rgba(26,107,189,.2);
    display:flex; align-items:center; justify-content:center;
  }
  .info-icon svg { color:#1a6bbd; }
  .info-label {
    font-family:'Barlow Condensed',sans-serif; font-size:.65rem; font-weight:700;
    letter-spacing:.1em; text-transform:uppercase; color:rgba(255,255,255,.28);
    display:block; margin-bottom:3px;
  }
  .info-value { font-size:.9rem; color:rgba(255,255,255,.7); line-height:1.5; }
  .info-value a { color:#1a6bbd; }
  .info-value a:hover { text-decoration:underline; }

  /* Garages */
  .garage-cards { display:flex; flex-direction:column; gap:.75rem; }
  .garage-card {
    background:#101010; border:1px solid rgba(255,255,255,.07);
    border-radius:8px; padding:1rem 1.25rem;
    transition:border-color .15s;
  }
  .garage-card:hover { border-color:rgba(26,107,189,.25); }
  .garage-name {
    font-family:'Barlow Condensed',sans-serif; font-size:.9rem; font-weight:800;
    text-transform:uppercase; letter-spacing:.03em; color:#fff; margin-bottom:.25rem;
  }
  .garage-addr { font-size:.8rem; color:rgba(255,255,255,.35); }

  /* ── Form panel ── */
  .contact-form-panel {
    background:#101010; border:1px solid rgba(255,255,255,.08);
    border-radius:12px; padding:2rem;
  }
  .form-panel-title {
    font-family:'Barlow Condensed',sans-serif; font-size:.7rem; font-weight:700;
    letter-spacing:.12em; text-transform:uppercase; color:rgba(255,255,255,.28);
    margin-bottom:1.5rem; display:block;
  }

  .f-field { margin-bottom:1rem; }
  .f-field label {
    display:block; font-family:'Barlow Condensed',sans-serif;
    font-size:.68rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase;
    color:rgba(255,255,255,.28); margin-bottom:.4rem;
  }
  .f-field input,.f-field select,.f-field textarea {
    width:100%; background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.1);
    border-radius:8px; color:#fff; font-family:'Barlow',sans-serif; font-size:.9rem;
    padding:.72rem 1rem; outline:none;
    transition:border-color .18s, box-shadow .18s; -webkit-appearance:none;
  }
  .f-field input:focus,.f-field select:focus,.f-field textarea:focus {
    border-color:#1a6bbd; box-shadow:0 0 0 3px rgba(26,107,189,.14);
  }
  .f-field input::placeholder,.f-field textarea::placeholder { color:rgba(255,255,255,.14); }
  .f-field select option { background:#141414; }
  .f-field textarea { resize:vertical; min-height:130px; line-height:1.6; }
  .f-row { display:grid; grid-template-columns:1fr 1fr; gap:0 1rem; }

  .btn-send {
    width:100%; margin-top:1.25rem;
    display:flex; align-items:center; justify-content:center; gap:.5rem;
    font-family:'Barlow Condensed',sans-serif; font-size:.88rem; font-weight:700;
    letter-spacing:.06em; text-transform:uppercase;
    background:#1a6bbd; color:#fff; border:none; border-radius:8px;
    padding:.82rem; cursor:pointer; transition:background .15s, box-shadow .15s, transform .15s;
  }
  .btn-send:hover { background:#155fa0; box-shadow:0 4px 20px rgba(26,107,189,.35); transform:translateY(-1px); }

  /* Alerts */
  .c-alert {
    display:flex; align-items:center; gap:.65rem;
    font-family:'Barlow Condensed',sans-serif; font-size:.8rem; font-weight:700;
    letter-spacing:.04em; text-transform:uppercase;
    padding:.8rem 1rem; border-radius:6px; border:1px solid; margin-bottom:1.25rem;
  }
  .c-alert-ok  { background:rgba(74,222,128,.08);  border-color:rgba(74,222,128,.2);  color:#4ade80; }
  .c-alert-err { background:rgba(248,113,113,.08); border-color:rgba(248,113,113,.2); color:#f87171; }

  /* Success state */
  .sent-state {
    text-align:center; padding:2.5rem 1rem;
  }
  .sent-icon {
    width:56px; height:56px; border-radius:14px;
    background:rgba(74,222,128,.1); border:1px solid rgba(74,222,128,.2);
    display:flex; align-items:center; justify-content:center;
    margin:0 auto 1.25rem;
  }
  .sent-state h3 {
    font-family:'Barlow Condensed',sans-serif; font-size:1.2rem; font-weight:800;
    text-transform:uppercase; color:#fff; margin-bottom:.5rem;
  }
  .sent-state p { font-size:.88rem; color:rgba(255,255,255,.38); margin-bottom:1.5rem; }

  /* ── Map placeholder ── */
  .map-section { padding:0 0 5rem; background:#0d0d0d; }
  .map-placeholder {
    border:1px solid rgba(255,255,255,.07); border-radius:12px; overflow:hidden;
    background:#101010; aspect-ratio:3/1; min-height:220px;
    display:flex; align-items:center; justify-content:center;
    flex-direction:column; gap:.75rem;
  }
  .map-placeholder p { font-family:'Barlow Condensed',sans-serif; font-size:.78rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:rgba(255,255,255,.2); }

  @media(max-width:900px){
    .contact-grid { grid-template-columns:1fr; }
    .f-row { grid-template-columns:1fr; }
  }
  </style>
</head>
<body>
<?php require __DIR__ . '/includes/public-nav.php'; ?>

<main id="contenu-principal">

  <!-- ── HERO ── -->
  <section class="contact-hero" aria-labelledby="titre-contact">
    <div class="container contact-hero-inner">
      <span class="contact-eyebrow">Nous contacter</span>
      <h1 id="titre-contact">Parlons de <em>votre véhicule</em></h1>
      <p>Une question, un rendez-vous urgent ou un renseignement ? Notre équipe est disponible et vous répond rapidement.</p>
    </div>
  </section>

  <!-- ── BODY ── -->
  <section class="contact-body" aria-label="Formulaire et coordonnées">
    <div class="container contact-grid">

      <!-- Info -->
      <div class="contact-info">
        <h2>Coordonnées</h2>
        <p class="contact-info-sub">Retrouvez-nous dans l'un de nos deux garages ou contactez-nous directement.</p>

        <div class="info-items">
          <div class="info-item">
            <div class="info-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12a19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 3.6 1.18h3a2 2 0 0 1 2 1.72c.13 1 .39 1.98.75 2.91a2 2 0 0 1-.45 2.11L7.91 9a16 16 0 0 0 6 6l.27-.18a2 2 0 0 1 2.11-.45c.93.36 1.91.62 2.91.75A2 2 0 0 1 21 17z"/></svg>
            </div>
            <div>
              <span class="info-label">Téléphone</span>
              <div class="info-value">
                <a href="tel:+213770000000">+213 770 000 000</a><br>
                <a href="tel:+213660000000">+213 660 000 000</a>
              </div>
            </div>
          </div>

          <div class="info-item">
            <div class="info-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            </div>
            <div>
              <span class="info-label">Email</span>
              <div class="info-value"><a href="mailto:contact@mecaspeed.dz">contact@mecaspeed.dz</a></div>
            </div>
          </div>

          <div class="info-item">
            <div class="info-icon">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </div>
            <div>
              <span class="info-label">Horaires d'ouverture</span>
              <div class="info-value">
                Dimanche – Jeudi : 08h00 – 18h00<br>
                Vendredi – Samedi : 08h00 – 13h00
              </div>
            </div>
          </div>
        </div>

        <span style="font-family:'Barlow Condensed',sans-serif;font-size:.65rem;font-weight:700;letter-spacing:.12em;text-transform:uppercase;color:rgba(255,255,255,.25);display:block;margin-bottom:.85rem">Nos garages</span>
        <div class="garage-cards">
          <div class="garage-card">
            <div class="garage-name">MécaSpeed — Draa Ben Khedda</div>
            <div class="garage-addr">Cité nouvelle, Draa Ben Khedda, Tizi-Ouzou</div>
          </div>
          <div class="garage-card">
            <div class="garage-name">MécaSpeed — Tizi Ouzou</div>
            <div class="garage-addr">Boulevard du 1er Novembre, Tizi-Ouzou</div>
          </div>
        </div>
      </div>

      <!-- Form -->
      <div class="contact-form-panel">

        <?php if ($sent): ?>
          <div class="sent-state">
            <div class="sent-icon">
              <svg width="26" height="26" viewBox="0 0 24 24" fill="none" stroke="#4ade80" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
            </div>
            <h3>Message envoyé !</h3>
            <p>Merci pour votre message. Notre équipe vous répondra dans les plus brefs délais.</p>
            <a href="contact.php" class="btn btn-ghost btn-sm">Envoyer un autre message</a>
          </div>

        <?php else: ?>

          <span class="form-panel-title">Envoyer un message</span>

          <?php if ($error): ?>
            <div class="c-alert c-alert-err">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
              <?= htmlspecialchars($error) ?>
            </div>
          <?php endif; ?>

          <form method="POST" action="contact.php">

            <div class="f-row">
              <div class="f-field">
                <label for="c-nom">Nom complet</label>
                <input type="text" id="c-nom" name="nom" placeholder="Votre nom" required autocomplete="name">
              </div>
              <div class="f-field">
                <label for="c-email">Adresse e-mail</label>
                <input type="email" id="c-email" name="email" placeholder="vous@domaine.com" required autocomplete="email">
              </div>
            </div>

            <div class="f-field">
              <label for="c-sujet">Sujet</label>
              <select id="c-sujet" name="sujet" required>
                <option value="">Choisir le sujet…</option>
                <option value="rdv">Prise de rendez-vous</option>
                <option value="devis">Demande de devis</option>
                <option value="suivi">Question sur un suivi</option>
                <option value="boutique">Question boutique / pièces</option>
                <option value="compte">Problème de compte</option>
                <option value="autre">Autre</option>
              </select>
            </div>

            <div class="f-field">
              <label for="c-garage">Garage concerné</label>
              <select id="c-garage" name="garage">
                <option value="">Tous les garages</option>
                <option value="dbk">Draa Ben Khedda</option>
                <option value="to">Tizi Ouzou</option>
              </select>
            </div>

            <div class="f-field">
              <label for="c-message">Message</label>
              <textarea id="c-message" name="message" placeholder="Décrivez votre demande en détail…" required></textarea>
            </div>

            <button type="submit" class="btn-send">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
              Envoyer le message
            </button>

          </form>

        <?php endif; ?>

      </div>

    </div>
  </section>

  <!-- ── MAP PLACEHOLDER ── -->
  <section class="map-section">
    <div class="container">
      <div class="map-placeholder">
        <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.15)" stroke-width="1.5"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
        <p>Draa Ben Khedda · Tizi Ouzou</p>
      </div>
    </div>
  </section>

</main>

<?php require __DIR__ . '/includes/page-footer.php'; ?>
</body>
</html>
