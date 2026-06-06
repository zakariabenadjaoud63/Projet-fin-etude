<?php
session_start();
require_once 'connexion_bd.php';
require_once 'includes/security.php';
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
if (($_SESSION['role'] ?? 'client') !== 'client') {
  header('Location: ' . ($_SESSION['role'] === 'mecano' ? 'espace_mecano.php' : 'espace_admin.php'));
  exit();
}

$id_client     = $_SESSION['user_id'];
$nom_complet   = $_SESSION['nom'] ?? 'Client';
$vehicules     = [];
$mes_rdv       = [];
$mes_commandes = [];

try {
  $stmt = $pdo->prepare("SELECT * FROM vehicules WHERE id_proprietaire = :uid ORDER BY id_v DESC");
  $stmt->execute([':uid' => $id_client]);
  $vehicules = $stmt->fetchAll();
} catch (PDOException $e) {}

try {
  $stmt = $pdo->prepare("SELECT * FROM rendez_vous WHERE utilisateur_id = :uid ORDER BY date_rdv DESC, heure_rdv DESC");
  $stmt->execute([':uid' => $id_client]);
  $mes_rdv = $stmt->fetchAll();
} catch (PDOException $e) {}

try {
  $stmt = $pdo->prepare("SELECT * FROM commandes WHERE utilisateur_id = :uid ORDER BY cree_le DESC LIMIT 10");
  $stmt->execute([':uid' => $id_client]);
  $mes_commandes = $stmt->fetchAll();
} catch (PDOException $e) {}

$nb_commandes = count($mes_commandes);

// Stats calculées
$rdv_confirmes = count(array_filter($mes_rdv, fn($r) => $r['statut'] === 'confirmed'));
$rdv_attente   = count(array_filter($mes_rdv, fn($r) => $r['statut'] === 'pending'));
$today         = date('Y-m-d');

// Prochain RDV à venir
$prochain_rdv  = null;
foreach ($mes_rdv as $r) {
  if ($r['date_rdv'] >= $today && $r['statut'] !== 'cancelled') {
    $prochain_rdv = $r;
    break;
  }
}

// Initiales avatar
$initiales = strtoupper(mb_substr($nom_complet, 0, 1));
$mots = explode(' ', $nom_complet);
if (count($mots) >= 2) $initiales = strtoupper(mb_substr($mots[0],0,1) . mb_substr($mots[1],0,1));

$activePage = 'dashboard';
$jours_fr   = ['Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi'];
$mois_fr    = ['','Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
$date_auj   = $jours_fr[date('w')] . ' ' . date('j') . ' ' . $mois_fr[intval(date('n'))] . ' ' . date('Y');

function st_class(string $s): string { return match($s){'confirmed'=>'st-ok','cancelled'=>'st-cancel',default=>'st-wait'}; }
function st_label(string $s): string { return match($s){'confirmed'=>'Confirmé','cancelled'=>'Annulé',default=>'En attente'}; }
function cmd_class(string $s): string { return match($s){'livree'=>'st-ok','annulee'=>'st-cancel','en_preparation'=>'st-blue',default=>'st-wait'}; }
function cmd_label(string $s): string { return match($s){'livree'=>'Livrée','annulee'=>'Annulée','en_preparation'=>'En préparation','en_attente'=>'En attente',default=>ucfirst(str_replace('_',' ',$s))}; }
function pay_label(string $s): string { return match($s){'especes'=>'Espèces','cib'=>'Carte CIB','virement'=>'Virement',default=>$s}; }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MécaSpeed — Mon espace</title>
  <style>
  /* ─── DASHBOARD LAYOUT ─────────────────────────────────── */
  .dash-root {
    display: grid;
    grid-template-columns: 240px 1fr;
    min-height: calc(100vh - 64px);
    background: #080808;
  }

  /* ─── SIDEBAR ──────────────────────────────────────────── */
  .dash-sidebar {
    border-right: 1px solid rgba(255,255,255,.07);
    padding: 2rem 0;
    position: sticky;
    top: 64px;
    height: calc(100vh - 64px);
    overflow-y: auto;
    display: flex;
    flex-direction: column;
  }

  /* Profile block */
  .sb-profile {
    padding: 0 1.25rem 1.75rem;
    border-bottom: 1px solid rgba(255,255,255,.06);
    margin-bottom: 1.5rem;
  }
  .sb-avatar {
    width: 52px; height: 52px;
    border-radius: 14px;
    background: linear-gradient(135deg, #1a6bbd 0%, #0e4a8a 100%);
    display: flex; align-items: center; justify-content: center;
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 1.15rem; font-weight: 900;
    color: #fff; letter-spacing: 0.02em;
    margin-bottom: 0.9rem;
    flex-shrink: 0;
  }
  .sb-name {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 1rem; font-weight: 800;
    text-transform: uppercase; letter-spacing: 0.04em;
    color: #fff; margin-bottom: 0.3rem;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  }
  .sb-role {
    display: inline-flex; align-items: center; gap: 0.3rem;
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 0.65rem; font-weight: 700;
    letter-spacing: 0.1em; text-transform: uppercase;
    color: #1a6bbd;
    background: rgba(26,107,189,.12);
    border: 1px solid rgba(26,107,189,.2);
    padding: 0.18rem 0.55rem; border-radius: 3px;
  }

  /* Nav items */
  .sb-nav { flex: 1; padding: 0 0.75rem; }
  .sb-section-label {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 0.6rem; font-weight: 700;
    letter-spacing: 0.16em; text-transform: uppercase;
    color: rgba(255,255,255,.2);
    padding: 0 0.6rem; margin-bottom: 0.4rem; display: block;
  }
  .sb-group { margin-bottom: 1.5rem; }
  .sb-link {
    display: flex; align-items: center; gap: 0.65rem;
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 0.82rem; font-weight: 600;
    letter-spacing: 0.04em; text-transform: uppercase;
    color: rgba(255,255,255,.45);
    padding: 0.55rem 0.75rem; border-radius: 6px;
    transition: all .15s; text-decoration: none;
    position: relative;
  }
  .sb-link svg { flex-shrink: 0; opacity: .5; transition: opacity .15s; }
  .sb-link:hover { color: rgba(255,255,255,.85); background: rgba(255,255,255,.05); }
  .sb-link:hover svg { opacity: 1; }
  .sb-link.active {
    color: #fff; background: rgba(26,107,189,.14);
    border: 1px solid rgba(26,107,189,.18);
  }
  .sb-link.active svg { opacity: 1; color: #1a6bbd; }
  .sb-badge {
    margin-left: auto;
    font-size: 0.62rem; font-weight: 800;
    background: rgba(26,107,189,.25); color: #6aabf7;
    padding: 1px 6px; border-radius: 10px;
    min-width: 18px; text-align: center;
  }
  .sb-badge.warn { background: rgba(251,191,36,.2); color: #fbbf24; }

  /* Déco */
  .sb-bottom {
    padding: 1.25rem 1.5rem 0;
    border-top: 1px solid rgba(255,255,255,.06);
    margin-top: auto;
  }
  .sb-logout {
    display: flex; align-items: center; gap: 0.6rem;
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 0.78rem; font-weight: 700;
    letter-spacing: 0.05em; text-transform: uppercase;
    color: rgba(255,255,255,.28);
    padding: 0.5rem 0.5rem; border-radius: 6px;
    transition: all .15s; text-decoration: none; width: 100%;
  }
  .sb-logout:hover { color: #f87171; background: rgba(248,113,113,.07); }

  /* ─── MAIN CONTENT ─────────────────────────────────────── */
  .dash-main { overflow-y: auto; }

  .dash-topbar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 1.75rem 2.5rem;
    border-bottom: 1px solid rgba(255,255,255,.06);
    background: #0a0a0a;
    position: sticky; top: 0; z-index: 10;
  }
  .topbar-greeting {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 0.78rem; font-weight: 700;
    letter-spacing: 0.1em; text-transform: uppercase;
    color: rgba(255,255,255,.3);
  }
  .topbar-greeting strong {
    display: block; margin-top: 2px;
    font-size: 1.4rem; color: #fff; letter-spacing: 0.02em;
  }
  .topbar-date {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 0.75rem; font-weight: 600;
    letter-spacing: 0.06em; text-transform: uppercase;
    color: rgba(255,255,255,.22);
  }
  .topbar-actions { display: flex; gap: 0.6rem; }

  /* ─── STATS ROW ─────────────────────────────────────────── */
  .dash-stats {
    display: grid; grid-template-columns: repeat(5, 1fr);
    border-bottom: 1px solid rgba(255,255,255,.06);
  }
  .dash-stat {
    padding: 1.5rem 2rem;
    border-right: 1px solid rgba(255,255,255,.06);
    display: flex; flex-direction: column; gap: 0.3rem;
  }
  .dash-stat:last-child { border-right: none; }
  .ds-label {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 0.62rem; font-weight: 700;
    letter-spacing: 0.13em; text-transform: uppercase;
    color: rgba(255,255,255,.3);
  }
  .ds-value {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 1.75rem; font-weight: 900;
    color: #fff; line-height: 1;
  }
  .ds-value.blue   { color: #1a6bbd; }
  .ds-value.green  { color: #4ade80; }
  .ds-value.amber  { color: #fbbf24; }
  .ds-sub {
    font-size: 0.72rem; color: rgba(255,255,255,.25);
  }

  /* ─── CONTENT SECTIONS ─────────────────────────────────── */
  .dash-body { padding: 2rem 2.5rem; display: flex; flex-direction: column; gap: 2.5rem; }

  /* Section header */
  .sec-head {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 1.1rem;
  }
  .sec-title {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 0.7rem; font-weight: 700;
    letter-spacing: 0.14em; text-transform: uppercase;
    color: rgba(255,255,255,.3);
  }
  .sec-action {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 0.7rem; font-weight: 700;
    letter-spacing: 0.08em; text-transform: uppercase;
    color: #1a6bbd; text-decoration: none;
  }
  .sec-action:hover { text-decoration: underline; }

  /* ─── NEXT RDV BANNER ──────────────────────────────────── */
  .next-rdv {
    background: rgba(26,107,189,.07);
    border: 1px solid rgba(26,107,189,.18);
    border-radius: 10px;
    padding: 1.25rem 1.5rem;
    display: flex; align-items: center; gap: 1.5rem;
  }
  .next-rdv-date {
    flex-shrink: 0;
    background: rgba(26,107,189,.18);
    border: 1px solid rgba(26,107,189,.25);
    border-radius: 8px; padding: 0.6rem 1rem;
    text-align: center; min-width: 64px;
  }
  .nrd-day {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 1.8rem; font-weight: 900;
    color: #fff; line-height: 1; display: block;
  }
  .nrd-month {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 0.65rem; font-weight: 700;
    letter-spacing: 0.1em; text-transform: uppercase;
    color: #1a6bbd; display: block; margin-top: 1px;
  }
  .next-rdv-info { flex: 1; }
  .nrd-service {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 1.05rem; font-weight: 800;
    text-transform: uppercase; color: #fff; margin-bottom: 0.3rem;
  }
  .nrd-meta {
    font-size: 0.82rem; color: rgba(255,255,255,.4);
    display: flex; gap: 1rem; flex-wrap: wrap;
  }
  .nrd-meta span { display: flex; align-items: center; gap: 0.3rem; }

  /* ─── VEHICLES LIST ─────────────────────────────────────── */
  .vehicle-list { display: flex; flex-direction: column; gap: 1px; }
  .vehicle-row {
    display: flex; align-items: center; gap: 1.25rem;
    padding: 1rem 1.25rem;
    background: #101010;
    border: 1px solid rgba(255,255,255,.06);
    border-radius: 8px;
    transition: border-color .15s, background .15s;
  }
  .vehicle-row:hover { background: #141414; border-color: rgba(255,255,255,.1); }
  .vr-icon {
    width: 36px; height: 36px; flex-shrink: 0;
    background: rgba(255,255,255,.04);
    border: 1px solid rgba(255,255,255,.07);
    border-radius: 8px;
    display: flex; align-items: center; justify-content: center;
  }
  .vr-icon svg { color: rgba(255,255,255,.3); }
  .vr-info { flex: 1; min-width: 0; }
  .vr-name {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 0.92rem; font-weight: 800;
    text-transform: uppercase; color: #fff; letter-spacing: 0.03em;
  }
  .vr-immat {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 0.72rem; font-weight: 700;
    color: #1a6bbd; letter-spacing: 0.08em; margin-top: 1px;
  }
  .vr-year { font-size: 0.72rem; color: rgba(255,255,255,.25); }
  .vr-actions { display: flex; gap: 0.4rem; flex-shrink: 0; }
  .vr-btn {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 0.7rem; font-weight: 700;
    letter-spacing: 0.06em; text-transform: uppercase;
    color: rgba(255,255,255,.4);
    background: rgba(255,255,255,.04);
    border: 1px solid rgba(255,255,255,.07);
    padding: 0.3rem 0.7rem; border-radius: 4px;
    text-decoration: none; transition: all .15s;
    white-space: nowrap;
  }
  .vr-btn:hover { color: #fff; background: rgba(255,255,255,.08); border-color: rgba(255,255,255,.14); }

  /* ─── RDV TABLE ─────────────────────────────────────────── */
  .rdv-table-wrap {
    border: 1px solid rgba(255,255,255,.07);
    border-radius: 10px;
    overflow: hidden;
  }
  .rdv-table { width: 100%; border-collapse: collapse; }
  .rdv-table thead th {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 0.62rem; font-weight: 700;
    letter-spacing: 0.12em; text-transform: uppercase;
    color: rgba(255,255,255,.25);
    padding: 0.75rem 1.25rem; text-align: left;
    background: rgba(255,255,255,.025);
    border-bottom: 1px solid rgba(255,255,255,.06);
    white-space: nowrap;
  }
  .rdv-table tbody tr {
    border-bottom: 1px solid rgba(255,255,255,.04);
    transition: background .12s;
  }
  .rdv-table tbody tr:last-child { border-bottom: none; }
  .rdv-table tbody tr:hover { background: rgba(255,255,255,.02); }
  .rdv-table td {
    padding: 0.9rem 1.25rem; vertical-align: middle;
    font-size: 0.85rem; color: rgba(255,255,255,.65);
  }
  .td-date {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 0.88rem; font-weight: 700;
    color: #fff; white-space: nowrap;
  }
  .td-hour { color: rgba(255,255,255,.3); font-size: 0.8rem; margin-top: 1px; }
  .td-service {
    font-family: 'Barlow Condensed', sans-serif;
    font-weight: 700; text-transform: uppercase;
    letter-spacing: 0.03em; font-size: 0.82rem; color: rgba(255,255,255,.8);
  }

  /* Status pills */
  .st-ok, .st-cancel, .st-wait {
    display: inline-flex; align-items: center; gap: 0.35rem;
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 0.68rem; font-weight: 700;
    letter-spacing: 0.08em; text-transform: uppercase;
    padding: 0.22rem 0.65rem; border-radius: 100px;
    border: 1px solid; white-space: nowrap;
  }
  .st-ok::before, .st-cancel::before, .st-wait::before {
    content: ''; width: 5px; height: 5px; border-radius: 50%; flex-shrink: 0;
  }
  .st-ok     { background: rgba(74,222,128,.08);  color: #4ade80; border-color: rgba(74,222,128,.2); }
  .st-ok::before     { background: #4ade80; }
  .st-cancel { background: rgba(248,113,113,.08); color: #f87171; border-color: rgba(248,113,113,.2); }
  .st-cancel::before { background: #f87171; }
  .st-wait   { background: rgba(251,191,36,.08);  color: #fbbf24; border-color: rgba(251,191,36,.2); }
  .st-wait::before   { background: #fbbf24; }
  .st-blue   { background: rgba(26,107,189,.1);   color: #6aabf7; border-color: rgba(26,107,189,.25); }
  .st-blue::before   { background: #6aabf7; }

  /* Annuler inline */
  .btn-annuler {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 0.65rem; font-weight: 700;
    letter-spacing: 0.07em; text-transform: uppercase;
    color: rgba(248,113,113,.55);
    background: transparent; border: 1px solid rgba(248,113,113,.15);
    padding: 0.25rem 0.65rem; border-radius: 4px;
    cursor: pointer; transition: all .15s;
  }
  .btn-annuler:hover { color: #f87171; background: rgba(248,113,113,.08); border-color: rgba(248,113,113,.3); }

  /* ─── EMPTY ─────────────────────────────────────────────── */
  .dash-empty {
    padding: 2.5rem 1.5rem; text-align: center;
    border: 1px dashed rgba(255,255,255,.07); border-radius: 8px;
  }
  .dash-empty p {
    font-size: 0.82rem; color: rgba(255,255,255,.2); margin-bottom: 1rem;
  }

  /* ─── COMMANDES TABLE ──────────────────────────────────────── */
  .cmd-table-wrap {
    border: 1px solid rgba(255,255,255,.07);
    border-radius: 10px; overflow: hidden;
  }
  .cmd-table { width: 100%; border-collapse: collapse; }
  .cmd-table thead th {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 0.62rem; font-weight: 700;
    letter-spacing: 0.12em; text-transform: uppercase;
    color: rgba(255,255,255,.25);
    padding: 0.75rem 1.25rem; text-align: left;
    background: rgba(255,255,255,.025);
    border-bottom: 1px solid rgba(255,255,255,.06);
    white-space: nowrap;
  }
  .cmd-table tbody tr {
    border-bottom: 1px solid rgba(255,255,255,.04);
    transition: background .12s;
  }
  .cmd-table tbody tr:last-child { border-bottom: none; }
  .cmd-table tbody tr:hover { background: rgba(255,255,255,.02); }
  .cmd-table td {
    padding: 0.9rem 1.25rem; vertical-align: middle;
    font-size: 0.85rem; color: rgba(255,255,255,.6);
  }
  .cmd-id {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 0.88rem; font-weight: 800; color: #1a6bbd;
    white-space: nowrap;
  }
  .cmd-date { color: rgba(255,255,255,.55); white-space: nowrap; }
  .cmd-methode {
    font-size: 0.78rem; color: rgba(255,255,255,.35);
    white-space: nowrap;
  }
  .cmd-total {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 0.95rem; font-weight: 900; color: #fff;
    white-space: nowrap;
  }
  .cmd-total small { font-size: 0.65rem; color: rgba(255,255,255,.3); margin-left: 2px; }

  /* ─── RESPONSIVE ─────────────────────────────────────────── */
  @media (max-width: 900px) {
    .dash-root { grid-template-columns: 1fr; }
    .dash-sidebar { display: none; }
    .dash-topbar { padding: 1.25rem 1.5rem; }
    .dash-body { padding: 1.5rem; }
    .dash-stats { grid-template-columns: repeat(3, 1fr); }
    .cmd-table thead th:nth-child(3) { display: none; }
    .cmd-table td:nth-child(3) { display: none; }
  }
  @media (max-width: 560px) {
    .dash-stats { grid-template-columns: 1fr 1fr; }
    .next-rdv { flex-direction: column; gap: 1rem; }
  }
  </style>
</head>
<body>
<?php require __DIR__ . '/includes/public-nav.php'; ?>

<div class="dash-root">

  <!-- ══ SIDEBAR ══════════════════════════════════════════ -->
  <?php require __DIR__ . '/includes/dashboard-sidebar.php'; ?>

  <!-- ══ MAIN ═════════════════════════════════════════════ -->
  <main class="dash-main" id="contenu-principal">

    <!-- Top bar -->
    <div class="dash-topbar">
      <div class="topbar-greeting">
        Bienvenue
        <strong><?= htmlspecialchars($nom_complet) ?></strong>
      </div>
      <div style="display:flex;align-items:center;gap:1.5rem">
        <span class="topbar-date"><?= $date_auj ?></span>
        <div class="topbar-actions">
          <a href="calendrier.php" class="btn btn-sm btn-primary">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
            Nouveau RDV
          </a>
        </div>
      </div>
    </div>

    <!-- Stats -->
    <div class="dash-stats">
      <div class="dash-stat">
        <span class="ds-label">Véhicules</span>
        <span class="ds-value"><?= count($vehicules) ?></span>
        <span class="ds-sub">enregistrés</span>
      </div>
      <div class="dash-stat">
        <span class="ds-label">Rendez-vous</span>
        <span class="ds-value"><?= count($mes_rdv) ?></span>
        <span class="ds-sub">au total</span>
      </div>
      <div class="dash-stat">
        <span class="ds-label">Confirmés</span>
        <span class="ds-value green"><?= $rdv_confirmes ?></span>
        <span class="ds-sub">rendez-vous</span>
      </div>
      <div class="dash-stat">
        <span class="ds-label">En attente</span>
        <span class="ds-value <?= $rdv_attente > 0 ? 'amber' : '' ?>"><?= $rdv_attente ?></span>
        <span class="ds-sub">à confirmer</span>
      </div>
      <div class="dash-stat">
        <span class="ds-label">Commandes</span>
        <span class="ds-value blue"><?= $nb_commandes ?></span>
        <span class="ds-sub">passées</span>
      </div>
    </div>

    <!-- Body -->
    <div class="dash-body">

      <?php if (isset($_GET['rdv']) && $_GET['rdv'] === 'annule'): ?>
        <div class="alert alert-success" role="status">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          Rendez-vous annulé avec succès.
        </div>
      <?php endif; ?>

      <!-- ── PROCHAIN RDV ── -->
      <?php if ($prochain_rdv): ?>
      <div>
        <div class="sec-head">
          <span class="sec-title">Prochain rendez-vous</span>
          <a href="mes_rdv.php" class="sec-action">Voir tous →</a>
        </div>
        <div class="next-rdv">
          <div class="next-rdv-date">
            <span class="nrd-day"><?= date('d', strtotime($prochain_rdv['date_rdv'])) ?></span>
            <span class="nrd-month"><?= $mois_fr[intval(date('n', strtotime($prochain_rdv['date_rdv'])))] ?></span>
          </div>
          <div class="next-rdv-info">
            <div class="nrd-service"><?= htmlspecialchars($prochain_rdv['service']) ?></div>
            <div class="nrd-meta">
              <span>
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <?= htmlspecialchars(substr($prochain_rdv['heure_rdv'], 0, 5)) ?>
              </span>
              <span class="st-<?= ['confirmed'=>'ok','cancelled'=>'cancel','pending'=>'wait'][$prochain_rdv['statut']] ?? 'wait' ?>">
                <?= st_label($prochain_rdv['statut']) ?>
              </span>
            </div>
          </div>
          <?php if ($prochain_rdv['statut'] !== 'cancelled'): ?>
            <form action="annuler_rdv.php" method="POST" style="flex-shrink:0">
              <?= csrf_field() ?>
              <input type="hidden" name="rdv_id" value="<?= intval($prochain_rdv['id']) ?>">
              <button type="submit" class="btn-annuler">Annuler</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- ── MES VÉHICULES ── -->
      <div>
        <div class="sec-head">
          <span class="sec-title">Mes véhicules</span>
          <a href="vehicule.php" class="sec-action">Gérer →</a>
        </div>

        <?php if (count($vehicules) > 0): ?>
          <div class="vehicle-list">
            <?php foreach ($vehicules as $v): ?>
              <div class="vehicle-row">
                <div class="vr-icon">
                  <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 3v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                </div>
                <div class="vr-info">
                  <div class="vr-name"><?= htmlspecialchars(strtoupper($v['marque']) . ' ' . $v['modele']) ?></div>
                  <div class="vr-immat"><?= htmlspecialchars($v['immatriculation']) ?></div>
                </div>
                <div class="vr-year"><?= htmlspecialchars($v['annee'] ?? '—') ?></div>
                <div class="vr-actions">
                  <a href="suivi.php?immatriculation=<?= urlencode($v['immatriculation']) ?>" class="vr-btn">Suivi</a>
                  <a href="calendrier.php" class="vr-btn">RDV</a>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="dash-empty">
            <p>Aucun véhicule enregistré pour le moment.</p>
            <a href="vehicule.php" class="btn btn-sm btn-ghost">Ajouter un véhicule</a>
          </div>
        <?php endif; ?>
      </div>

      <!-- ── HISTORIQUE RDV ── -->
      <div>
        <div class="sec-head">
          <span class="sec-title">Historique des rendez-vous</span>
          <a href="calendrier.php" class="sec-action">Prendre RDV →</a>
        </div>

        <?php if (count($mes_rdv) > 0): ?>
          <div class="rdv-table-wrap">
            <table class="rdv-table">
              <thead>
                <tr>
                  <th>Date</th>
                  <th>Service</th>
                  <th>Statut</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach (array_slice($mes_rdv, 0, 8) as $rdv): ?>
                  <tr>
                    <td>
                      <div class="td-date"><?= date('d/m/Y', strtotime($rdv['date_rdv'])) ?></div>
                      <div class="td-hour"><?= htmlspecialchars(substr($rdv['heure_rdv'], 0, 5)) ?></div>
                    </td>
                    <td><span class="td-service"><?= htmlspecialchars($rdv['service']) ?></span></td>
                    <td><span class="<?= st_class($rdv['statut']) ?>"><?= st_label($rdv['statut']) ?></span></td>
                    <td style="text-align:right">
                      <?php if ($rdv['statut'] !== 'cancelled' && strtotime($rdv['date_rdv']) >= strtotime($today)): ?>
                        <form action="annuler_rdv.php" method="POST">
                          <?= csrf_field() ?>
                          <input type="hidden" name="rdv_id" value="<?= intval($rdv['id']) ?>">
                          <button type="submit" class="btn-annuler">Annuler</button>
                        </form>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="dash-empty">
            <p>Aucun rendez-vous enregistré.</p>
            <a href="calendrier.php" class="btn btn-sm btn-primary">Prendre un rendez-vous</a>
          </div>
        <?php endif; ?>
      </div>

      <!-- ── MES COMMANDES (widget résumé) ── -->
      <div>
        <div class="sec-head">
          <span class="sec-title">Commandes récentes</span>
          <a href="mes_commandes.php" class="sec-action">Voir tout →</a>
        </div>

        <?php if (count($mes_commandes) > 0): ?>
          <div style="border:1px solid rgba(255,255,255,.07);border-radius:10px;overflow:hidden;background:#101010">
            <?php foreach (array_slice($mes_commandes, 0, 4) as $cmd): ?>
              <div style="display:flex;align-items:center;gap:1rem;padding:.85rem 1.25rem;border-bottom:1px solid rgba(255,255,255,.04);transition:background .12s" onmouseover="this.style.background='rgba(255,255,255,.02)'" onmouseout="this.style.background=''">
                <span style="font-family:'Barlow Condensed',sans-serif;font-size:.82rem;font-weight:800;color:#1a6bbd;min-width:36px">#<?= intval($cmd['id']) ?></span>
                <span style="font-size:.78rem;color:rgba(255,255,255,.4);flex:1"><?= date('d/m/Y', strtotime($cmd['cree_le'])) ?></span>
                <span class="<?= cmd_class($cmd['statut'] ?? '') ?>"><?= cmd_label($cmd['statut'] ?? 'en_attente') ?></span>
                <span style="font-family:'Barlow Condensed',sans-serif;font-size:.9rem;font-weight:800;color:#fff;white-space:nowrap"><?= number_format($cmd['total'],2,',',' ') ?> <small style="font-size:.65rem;color:rgba(255,255,255,.3)">DA</small></span>
                <a href="confirmation_commande.php?id=<?= intval($cmd['id']) ?>&methode=<?= htmlspecialchars($cmd['methode_paiement'] ?? 'especes') ?>" class="vr-btn">Voir</a>
              </div>
            <?php endforeach; ?>
            <?php if (count($mes_commandes) > 4): ?>
              <div style="padding:.75rem 1.25rem;text-align:center">
                <a href="mes_commandes.php" style="font-family:'Barlow Condensed',sans-serif;font-size:.72rem;font-weight:700;letter-spacing:.07em;text-transform:uppercase;color:rgba(255,255,255,.25);text-decoration:none;transition:color .15s" onmouseover="this.style.color='#1a6bbd'" onmouseout="this.style.color='rgba(255,255,255,.25)'">Voir les <?= count($mes_commandes) ?> commandes →</a>
              </div>
            <?php endif; ?>
          </div>
        <?php else: ?>
          <div class="dash-empty">
            <p>Aucune commande passée pour le moment.</p>
            <a href="boutique.php" class="btn btn-sm btn-primary">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
              Découvrir la boutique
            </a>
          </div>
        <?php endif; ?>
      </div>

    </div><!-- /dash-body -->
  </main>

</div><!-- /dash-root -->

</body>
</html>
