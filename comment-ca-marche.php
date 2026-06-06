<?php
$activePage = 'how';
session_start();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MécaSpeed — Comment ça marche</title>
  <style>
  /* ── Page-specific styles ── */

  /* Hero */
  .how-hero {
    position: relative; background: #0a0a0a;
    padding: 5.5rem 0 4.5rem; overflow: hidden;
    border-bottom: 1px solid rgba(255,255,255,.06);
    text-align: center;
  }
  .how-hero::before {
    content:''; position:absolute; inset:0;
    background:
      radial-gradient(ellipse at 50% 0%, rgba(26,107,189,.14) 0%, transparent 60%),
      repeating-linear-gradient(0deg,  transparent, transparent 59px, rgba(255,255,255,.02) 60px),
      repeating-linear-gradient(90deg, transparent, transparent 59px, rgba(255,255,255,.02) 60px);
    pointer-events: none;
  }
  .how-hero-inner { position:relative; z-index:1; }
  .how-eyebrow {
    display: inline-block;
    font-family:'Barlow Condensed',sans-serif; font-size:.72rem; font-weight:700;
    letter-spacing:.14em; text-transform:uppercase; color:#1a6bbd; margin-bottom:.75rem;
  }
  .how-hero h1 {
    font-family:'Barlow Condensed',sans-serif;
    font-size: clamp(2.8rem,6vw,5rem); font-weight:900; text-transform:uppercase;
    color:#fff; line-height:1; margin-bottom:1rem;
  }
  .how-hero h1 em { font-style:normal; color:#1a6bbd; }
  .how-hero p {
    font-size:1rem; color:rgba(255,255,255,.45); max-width:520px;
    margin:0 auto 2rem; line-height:1.65;
  }

  /* ── Steps section ── */
  .steps-section { padding:5rem 0; background:#0d0d0d; }

  .steps-grid {
    display:grid; grid-template-columns:1fr 1fr;
    gap:0; position:relative;
  }
  /* Vertical line between steps */
  .steps-grid::before {
    content:''; position:absolute;
    left:50%; top:3rem; bottom:3rem; width:1px;
    background: linear-gradient(to bottom, transparent, rgba(26,107,189,.3) 20%, rgba(26,107,189,.3) 80%, transparent);
  }

  .step-block {
    padding:3rem; position:relative;
    border-bottom:1px solid rgba(255,255,255,.05);
  }
  .step-block:nth-child(odd)  { border-right:1px solid rgba(255,255,255,.05); }
  .step-block:nth-last-child(1),
  .step-block:nth-last-child(2) { border-bottom:none; }

  .step-num {
    display:inline-flex; align-items:center; justify-content:center;
    width:44px; height:44px; border-radius:12px;
    background:rgba(26,107,189,.12); border:1px solid rgba(26,107,189,.25);
    font-family:'Barlow Condensed',sans-serif; font-size:1.1rem; font-weight:900; color:#1a6bbd;
    margin-bottom:1.25rem;
  }
  .step-icon {
    width:44px; height:44px; border-radius:12px;
    background:rgba(26,107,189,.1); border:1px solid rgba(26,107,189,.2);
    display:flex; align-items:center; justify-content:center;
    margin-bottom:1.25rem;
  }
  .step-icon svg { color:#1a6bbd; }
  .step-block h3 {
    font-family:'Barlow Condensed',sans-serif; font-size:1.2rem; font-weight:800;
    text-transform:uppercase; letter-spacing:.04em; color:#fff; margin-bottom:.55rem;
  }
  .step-block p { font-size:.88rem; color:rgba(255,255,255,.42); line-height:1.65; }

  /* ── Roles section ── */
  .roles-section { padding:5rem 0; background:#080808; border-top:1px solid rgba(255,255,255,.06); }

  .roles-grid {
    display:grid; grid-template-columns:repeat(3,1fr);
    gap:1.25rem; margin-top:3rem;
  }
  .role-card {
    background:#101010; border:1px solid rgba(255,255,255,.07);
    border-radius:12px; padding:2rem; position:relative; overflow:hidden;
    transition:border-color .2s, transform .2s;
  }
  .role-card:hover { border-color:rgba(26,107,189,.3); transform:translateY(-3px); }
  .role-card::before {
    content:''; position:absolute; top:0; left:0; right:0; height:2px;
  }
  .role-client::before   { background:linear-gradient(90deg,#1a6bbd,transparent); }
  .role-mecano::before   { background:linear-gradient(90deg,#4ade80,transparent); }
  .role-admin::before    { background:linear-gradient(90deg,#fbbf24,transparent); }
  .role-tag {
    display:inline-flex; align-items:center; gap:.35rem;
    font-family:'Barlow Condensed',sans-serif; font-size:.65rem; font-weight:700;
    letter-spacing:.1em; text-transform:uppercase;
    padding:.22rem .6rem; border-radius:100px; border:1px solid; margin-bottom:1.25rem;
  }
  .tag-client  { background:rgba(26,107,189,.1);  color:#6aabf7; border-color:rgba(26,107,189,.25); }
  .tag-mecano  { background:rgba(74,222,128,.1);  color:#4ade80; border-color:rgba(74,222,128,.25); }
  .tag-admin   { background:rgba(251,191,36,.1);  color:#fbbf24; border-color:rgba(251,191,36,.25); }
  .role-card h3 {
    font-family:'Barlow Condensed',sans-serif; font-size:1.3rem; font-weight:900;
    text-transform:uppercase; color:#fff; margin-bottom:1.1rem;
  }
  .role-features { list-style:none; padding:0; display:flex; flex-direction:column; gap:.55rem; }
  .role-features li {
    display:flex; align-items:flex-start; gap:.65rem;
    font-size:.84rem; color:rgba(255,255,255,.5); line-height:1.45;
  }
  .role-features li::before {
    content:''; width:5px; height:5px; border-radius:50%; flex-shrink:0;
    background:#1a6bbd; margin-top:6px;
  }
  .role-mecano .role-features li::before { background:#4ade80; }
  .role-admin  .role-features li::before { background:#fbbf24; }

  /* ── FAQ ── */
  .faq-section { padding:5rem 0; background:#0d0d0d; border-top:1px solid rgba(255,255,255,.06); }
  .faq-list { display:flex; flex-direction:column; gap:.5rem; max-width:760px; margin:0 auto; }
  .faq-item {
    background:#101010; border:1px solid rgba(255,255,255,.07); border-radius:8px; overflow:hidden;
  }
  .faq-q {
    width:100%; display:flex; align-items:center; justify-content:space-between;
    padding:1.1rem 1.5rem; cursor:pointer; background:none; border:none; text-align:left;
  }
  .faq-q-text {
    font-family:'Barlow Condensed',sans-serif; font-size:.95rem; font-weight:700;
    text-transform:uppercase; letter-spacing:.03em; color:rgba(255,255,255,.8);
  }
  .faq-chevron { color:rgba(255,255,255,.3); transition:transform .2s; flex-shrink:0; }
  .faq-item.open .faq-chevron { transform:rotate(180deg); }
  .faq-item.open .faq-q-text { color:#fff; }
  .faq-a {
    display:none; padding:0 1.5rem 1.1rem;
    font-size:.88rem; color:rgba(255,255,255,.42); line-height:1.65;
    border-top:1px solid rgba(255,255,255,.05); padding-top:1rem;
  }
  .faq-item.open .faq-a { display:block; }

  /* ── CTA ── */
  .how-cta {
    padding:5rem 0; background:#080808;
    border-top:1px solid rgba(255,255,255,.06); text-align:center;
  }
  .how-cta h2 {
    font-family:'Barlow Condensed',sans-serif;
    font-size:clamp(2rem,4vw,3.2rem); font-weight:900; text-transform:uppercase;
    color:#fff; margin-bottom:.75rem; line-height:1;
  }
  .how-cta h2 em { font-style:normal; color:#1a6bbd; }
  .how-cta p { font-size:.95rem; color:rgba(255,255,255,.38); margin-bottom:2rem; }
  .how-cta-btns { display:flex; gap:.75rem; justify-content:center; flex-wrap:wrap; }

  /* Section header centered */
  .sec-center { text-align:center; margin-bottom:1rem; }
  .sec-eyebrow {
    display:inline-block; font-family:'Barlow Condensed',sans-serif;
    font-size:.7rem; font-weight:700; letter-spacing:.14em; text-transform:uppercase;
    color:#1a6bbd; margin-bottom:.6rem;
  }
  .sec-title {
    font-family:'Barlow Condensed',sans-serif;
    font-size:clamp(1.8rem,3.5vw,2.8rem); font-weight:800; text-transform:uppercase;
    color:#fff; line-height:1.05;
  }
  .sec-sub { font-size:.9rem; color:rgba(255,255,255,.38); margin-top:.6rem; }

  @media(max-width:900px){
    .steps-grid { grid-template-columns:1fr; }
    .steps-grid::before { display:none; }
    .step-block:nth-child(odd) { border-right:none; }
    .roles-grid { grid-template-columns:1fr; }
  }
  </style>
</head>
<body>
<?php require __DIR__ . '/includes/public-nav.php'; ?>

<main id="contenu-principal">

  <!-- ── HERO ── -->
  <section class="how-hero" aria-labelledby="titre-how">
    <div class="container how-hero-inner">
      <span class="how-eyebrow">Guide d'utilisation</span>
      <h1 id="titre-how">Comment ça <em>marche</em>&nbsp;?</h1>
      <p>MécaSpeed simplifie la gestion de votre véhicule — de la prise de rendez-vous au suivi d'intervention en temps réel.</p>
      <div style="display:flex;gap:.75rem;justify-content:center;flex-wrap:wrap">
        <a href="inscription.php" class="btn btn-primary">Créer un compte gratuit</a>
        <a href="contact.php"     class="btn btn-outline">Nous contacter</a>
      </div>
    </div>
  </section>

  <!-- ── STEPS ── -->
  <section class="steps-section" aria-labelledby="titre-steps">
    <div class="container">
      <div class="sec-center">
        <span class="sec-eyebrow">Processus</span>
        <h2 class="sec-title" id="titre-steps">En 4 étapes simples</h2>
        <p class="sec-sub">Tout le cycle, de la création de compte à l'intervention terminée.</p>
      </div>

      <div class="steps-grid" style="margin-top:3.5rem">

        <div class="step-block">
          <div class="step-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
          </div>
          <h3>Créer votre compte</h3>
          <p>Inscrivez-vous en 30 secondes. Renseignez votre nom, email et téléphone. Votre espace client est immédiatement accessible après inscription.</p>
        </div>

        <div class="step-block">
          <div class="step-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 3v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
          </div>
          <h3>Ajouter vos véhicules</h3>
          <p>Enregistrez une ou plusieurs voitures avec leur marque, modèle, immatriculation et année. Chaque véhicule obtient sa propre fiche de suivi.</p>
        </div>

        <div class="step-block">
          <div class="step-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          </div>
          <h3>Prendre rendez-vous</h3>
          <p>Consultez le calendrier, choisissez une date disponible, sélectionnez le service souhaité et votre mécanicien. Confirmé en quelques clics.</p>
        </div>

        <div class="step-block">
          <div class="step-icon">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
          </div>
          <h3>Suivre en temps réel</h3>
          <p>Votre mécanicien met à jour la fiche d'intervention en direct. Consultez la progression, l'ETA et les notes depuis votre espace client.</p>
        </div>

      </div>
    </div>
  </section>

  <!-- ── RÔLES ── -->
  <section class="roles-section" aria-labelledby="titre-roles">
    <div class="container">
      <div class="sec-center">
        <span class="sec-eyebrow">Accès par rôle</span>
        <h2 class="sec-title" id="titre-roles">Un espace pour chaque utilisateur</h2>
        <p class="sec-sub">MécaSpeed adapte l'interface selon le rôle de chaque utilisateur.</p>
      </div>

      <div class="roles-grid">

        <div class="role-card role-client">
          <span class="role-tag tag-client">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            Client
          </span>
          <h3>Espace client</h3>
          <ul class="role-features">
            <li>Créer un compte et se connecter</li>
            <li>Gérer ses véhicules (ajout, consultation)</li>
            <li>Prendre rendez-vous avec choix de garage et mécanicien</li>
            <li>Consulter et annuler ses rendez-vous</li>
            <li>Suivre l'avancement des interventions</li>
            <li>Commander des pièces depuis la boutique</li>
          </ul>
        </div>

        <div class="role-card role-mecano">
          <span class="role-tag tag-mecano">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="3"/><path d="M19.07 4.93a10 10 0 0 1 0 14.14M4.93 4.93a10 10 0 0 0 0 14.14"/></svg>
            Mécanicien
          </span>
          <h3>Espace mécanicien</h3>
          <ul class="role-features">
            <li>Voir les rendez-vous assignés</li>
            <li>Confirmer ou annuler un rendez-vous</li>
            <li>Créer et mettre à jour la fiche de suivi</li>
            <li>Définir le statut, la progression et l'ETA</li>
            <li>Laisser une note visible par le client</li>
            <li>Consulter l'historique des interventions</li>
          </ul>
        </div>

        <div class="role-card role-admin">
          <span class="role-tag tag-admin">
            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
            Admin
          </span>
          <h3>Console admin</h3>
          <ul class="role-features">
            <li>Vue globale des rendez-vous et statistiques</li>
            <li>Gérer les comptes utilisateurs et rôles</li>
            <li>Affecter les mécaniciens aux rendez-vous</li>
            <li>Consulter le parc de véhicules clients</li>
            <li>Gérer le stock de la boutique</li>
            <li>Ajouter ou supprimer des produits</li>
          </ul>
        </div>

      </div>
    </div>
  </section>

  <!-- ── FAQ ── -->
  <section class="faq-section" aria-labelledby="titre-faq">
    <div class="container">
      <div class="sec-center" style="margin-bottom:2.5rem">
        <span class="sec-eyebrow">Questions fréquentes</span>
        <h2 class="sec-title" id="titre-faq">FAQ</h2>
      </div>

      <div class="faq-list">
        <?php foreach([
          ["L'inscription est-elle gratuite ?", "Oui, la création de compte client est entièrement gratuite. Vous accédez à toutes les fonctionnalités dès l'inscription."],
          ["Combien de véhicules puis-je enregistrer ?", "Il n'y a pas de limite. Vous pouvez enregistrer autant de véhicules que vous le souhaitez dans votre espace client."],
          ["Comment est notifié le mécanicien de mon rendez-vous ?", "Dès que vous validez un rendez-vous, il apparaît dans l'espace du mécanicien assigné. L'administrateur peut également affecter manuellement un mécanicien."],
          ["Puis-je annuler un rendez-vous ?", "Oui, depuis votre espace client dans la section Mes rendez-vous → À venir, vous pouvez annuler tout rendez-vous futur non encore annulé."],
          ["Les pièces commandées sont-elles livrées ?", "Non, les produits de la boutique sont à récupérer directement à l'atelier MécaSpeed lors de votre intervention."],
          ["Comment contacter le garage directement ?", "Rendez-vous sur notre page Contact pour nous joindre par formulaire, email ou téléphone."],
        ] as [$q, $a]): ?>
          <div class="faq-item">
            <button class="faq-q" onclick="toggleFaq(this)" aria-expanded="false">
              <span class="faq-q-text"><?= $q ?></span>
              <svg class="faq-chevron" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><polyline points="6 9 12 15 18 9"/></svg>
            </button>
            <div class="faq-a"><?= $a ?></div>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ── CTA FINAL ── -->
  <section class="how-cta">
    <div class="container">
      <h2>Prêt à essayer <em>MécaSpeed</em>&nbsp;?</h2>
      <p>Créez votre compte gratuit et prenez votre premier rendez-vous en moins de 2 minutes.</p>
      <div class="how-cta-btns">
        <a href="inscription.php" class="btn btn-primary">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><line x1="19" y1="8" x2="19" y2="14"/><line x1="22" y1="11" x2="16" y2="11"/></svg>
          Créer un compte
        </a>
        <a href="login.php" class="btn btn-outline">Se connecter</a>
      </div>
    </div>
  </section>

</main>

<?php require __DIR__ . '/includes/page-footer.php'; ?>

<script>
function toggleFaq(btn) {
  const item = btn.closest('.faq-item');
  const isOpen = item.classList.contains('open');
  document.querySelectorAll('.faq-item.open').forEach(i => i.classList.remove('open'));
  if (!isOpen) item.classList.add('open');
  btn.setAttribute('aria-expanded', String(!isOpen));
}
</script>
</body>
</html>
