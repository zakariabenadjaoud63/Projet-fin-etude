<?php
session_start();
require_once 'includes/security.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') { header("Location: login.php"); exit(); }
require_once 'connexion_bd.php';

$liste_rdv=$liste_vehicules=$liste_produits=$liste_users=$liste_mecanos=$liste_garages=$liste_categories=[];
$err_rdv=$err_veh=$err_stk=$err_usr=null;

try { $liste_rdv = $pdo->query("SELECT r.id,r.date_rdv,r.heure_rdv,r.notes,r.vehicule as vehicule_text,r.service,r.statut,r.mecano_id,u.nom as nom_client,m.nom as nom_mecano FROM rendez_vous r JOIN utilisateurs u ON r.utilisateur_id=u.id LEFT JOIN utilisateurs m ON r.mecano_id=m.id ORDER BY r.date_rdv DESC,r.heure_rdv DESC")->fetchAll(); } catch(Exception $e){ $err_rdv=$e->getMessage(); }
try { $liste_vehicules = $pdo->query("SELECT v.id_v,v.marque,v.modele,v.immatriculation,v.annee,u.nom as nom_proprio FROM vehicules v JOIN utilisateurs u ON v.id_proprietaire=u.id ORDER BY v.id_v DESC")->fetchAll(); } catch(Exception $e){ $err_veh=$e->getMessage(); }
try { $liste_produits = $pdo->query("SELECT id,categorie_id,garage_id,nom,description,prix,image,en_stock FROM produits ORDER BY id DESC")->fetchAll(); } catch(Exception $e){ $err_stk=$e->getMessage(); }
try {
  $liste_users      = $pdo->query("SELECT id,nom,email,telephone,garages_id,role,cree_le FROM utilisateurs ORDER BY id DESC")->fetchAll();
  $liste_mecanos    = $pdo->query("SELECT id,nom,garages_id FROM utilisateurs WHERE role='mecano' ORDER BY nom ASC")->fetchAll();
  $liste_garages    = $pdo->query("SELECT id,nom FROM garages ORDER BY nom ASC")->fetchAll();
  $liste_categories = $pdo->query("SELECT id,nom FROM categories_produits ORDER BY nom ASC")->fetchAll();
} catch(Exception $e){ $err_usr=$e->getMessage(); }

$ruptures      = count(array_filter($liste_produits, fn($p) => intval($p['en_stock']) <= 0));
$rdv_pending   = count(array_filter($liste_rdv, fn($r) => $r['statut'] === 'pending'));
$rdv_confirmed = count(array_filter($liste_rdv, fn($r) => $r['statut'] === 'confirmed'));
$nb_clients    = count(array_filter($liste_users, fn($u) => $u['role'] === 'client'));
$nb_mecanos    = count(array_filter($liste_users, fn($u) => $u['role'] === 'mecano'));

$nom_admin = $_SESSION['nom'] ?? 'Admin';
$initiales  = strtoupper(mb_substr($nom_admin, 0, 1));

function st_cl(string $s): string { return match($s){'confirmed'=>'ast-ok','cancelled'=>'ast-cancel',default=>'ast-wait'}; }
function st_lb(string $s): string { return match($s){'confirmed'=>'Confirmé','cancelled'=>'Annulé',default=>'En attente'}; }
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MécaSpeed — Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800;900&family=Barlow:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="includes/mecaspeed-ui.css">
  <style>
  /* ═══════════════════════════════════════════════
     ADMIN LAYOUT — full-screen sidebar dashboard
  ═══════════════════════════════════════════════ */
  * { box-sizing: border-box; margin: 0; padding: 0; }
  html, body { height: 100%; }
  body { font-family: 'Barlow', sans-serif; background: #080808; color: #fff; -webkit-font-smoothing: antialiased; }
  a { text-decoration: none; color: inherit; }

  .admin-root {
    display: grid;
    grid-template-columns: 220px 1fr;
    height: 100vh;
    overflow: hidden;
  }

  /* ── SIDEBAR ─────────────────────────────────── */
  .adm-sidebar {
    background: #0c0c0c;
    border-right: 1px solid rgba(255,255,255,.07);
    display: flex; flex-direction: column;
    height: 100vh; overflow-y: auto;
    padding: 1.5rem 0;
    position: sticky; top: 0;
  }

  /* Logo */
  .adm-logo {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 1.25rem; font-weight: 900;
    text-transform: uppercase; letter-spacing: .03em;
    color: #fff; padding: 0 1.25rem 1.5rem;
    border-bottom: 1px solid rgba(255,255,255,.06);
    margin-bottom: 1.5rem; display: block;
  }
  .adm-logo span { color: #1a6bbd; }
  .adm-logo small {
    display: block; margin-top: 2px;
    font-family: 'Barlow Condensed', sans-serif;
    font-size: .6rem; font-weight: 700;
    letter-spacing: .14em; text-transform: uppercase;
    color: rgba(255,255,255,.25);
  }

  /* Nav */
  .adm-nav { flex: 1; padding: 0 .75rem; }
  .adm-section-label {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: .58rem; font-weight: 700;
    letter-spacing: .16em; text-transform: uppercase;
    color: rgba(255,255,255,.2);
    padding: 0 .6rem; margin-bottom: .35rem; display: block;
  }
  .adm-group { margin-bottom: 1.5rem; }

  .adm-link {
    display: flex; align-items: center; gap: .65rem;
    font-family: 'Barlow Condensed', sans-serif;
    font-size: .8rem; font-weight: 600;
    letter-spacing: .04em; text-transform: uppercase;
    color: rgba(255,255,255,.4);
    padding: .52rem .75rem; border-radius: 6px;
    transition: all .15s; cursor: pointer;
    border: 1px solid transparent;
    background: none; width: 100%; text-align: left;
  }
  .adm-link svg { flex-shrink: 0; opacity: .45; transition: opacity .15s; }
  .adm-link:hover { color: rgba(255,255,255,.8); background: rgba(255,255,255,.05); }
  .adm-link:hover svg { opacity: .8; }
  .adm-link.active {
    color: #fff; background: rgba(26,107,189,.14);
    border-color: rgba(26,107,189,.2);
  }
  .adm-link.active svg { opacity: 1; color: #1a6bbd; }

  .adm-badge {
    margin-left: auto; font-size: .6rem; font-weight: 800;
    padding: 1px 6px; border-radius: 10px; min-width: 18px; text-align: center;
  }
  .adm-badge-blue { background: rgba(26,107,189,.25); color: #6aabf7; }
  .adm-badge-amber{ background: rgba(251,191,36,.2);  color: #fbbf24; }
  .adm-badge-red  { background: rgba(248,113,113,.2); color: #f87171; }

  /* Profile + logout bottom */
  .adm-bottom {
    margin-top: auto; padding: 1.25rem;
    border-top: 1px solid rgba(255,255,255,.06);
  }
  .adm-profile {
    display: flex; align-items: center; gap: .75rem;
    margin-bottom: 1rem;
  }
  .adm-avatar {
    width: 34px; height: 34px; border-radius: 8px;
    background: linear-gradient(135deg,#1a6bbd,#0e4a8a);
    display: flex; align-items: center; justify-content: center;
    font-family: 'Barlow Condensed', sans-serif;
    font-size: .85rem; font-weight: 900; color: #fff; flex-shrink: 0;
  }
  .adm-profile-name {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: .78rem; font-weight: 800;
    text-transform: uppercase; letter-spacing: .04em; color: #fff;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  }
  .adm-profile-role {
    font-size: .62rem; color: rgba(255,255,255,.3);
    text-transform: uppercase; letter-spacing: .08em;
    font-family: 'Barlow Condensed', sans-serif; font-weight: 700;
  }
  .adm-logout {
    display: flex; align-items: center; gap: .55rem;
    font-family: 'Barlow Condensed', sans-serif;
    font-size: .75rem; font-weight: 700;
    letter-spacing: .05em; text-transform: uppercase;
    color: rgba(255,255,255,.25);
    padding: .45rem .5rem; border-radius: 6px;
    transition: all .15s; width: 100%;
  }
  .adm-logout:hover { color: #f87171; background: rgba(248,113,113,.07); }

  /* ── MAIN ─────────────────────────────────────── */
  .adm-main {
    height: 100vh; overflow-y: auto;
    display: flex; flex-direction: column;
    min-width: 0;
  }

  /* Topbar */
  .adm-topbar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 1.25rem 2rem;
    background: #0a0a0a;
    border-bottom: 1px solid rgba(255,255,255,.06);
    position: sticky; top: 0; z-index: 10; flex-shrink: 0;
  }
  .adm-topbar-title {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 1.1rem; font-weight: 800;
    text-transform: uppercase; letter-spacing: .04em; color: #fff;
  }
  .adm-topbar-sub {
    font-size: .72rem; color: rgba(255,255,255,.3);
    font-family: 'Barlow Condensed', sans-serif;
    font-weight: 700; letter-spacing: .08em; text-transform: uppercase;
    margin-bottom: 2px;
  }

  /* Content */
  .adm-content { padding: 2rem; flex: 1; }

  /* Panels */
  .adm-panel { display: none; }
  .adm-panel.active { display: block; }

  /* ── STATS CARDS (Statistics panel) ───────────── */
  .stat-cards {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(180px,1fr));
    gap: .75rem; margin-bottom: 2rem;
  }
  .stat-card {
    background: #101010; border: 1px solid rgba(255,255,255,.07);
    border-radius: 10px; padding: 1.25rem 1.25rem 1rem;
  }
  .sc-label {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: .62rem; font-weight: 700; letter-spacing: .13em; text-transform: uppercase;
    color: rgba(255,255,255,.3); margin-bottom: .55rem; display: block;
  }
  .sc-value {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 2rem; font-weight: 900; color: #fff; line-height: 1;
    display: block; margin-bottom: .25rem;
  }
  .sc-value.blue  { color: #1a6bbd; }
  .sc-value.green { color: #4ade80; }
  .sc-value.amber { color: #fbbf24; }
  .sc-value.red   { color: #f87171; }
  .sc-sub { font-size: .72rem; color: rgba(255,255,255,.22); }

  /* Progress bars for stats */
  .sc-bar-track { height: 3px; background: rgba(255,255,255,.07); border-radius: 2px; margin-top: .75rem; }
  .sc-bar-fill  { height: 100%; border-radius: 2px; }

  /* ── SECTION HEADER ───────────────────────────── */
  .sec-hd {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 1.25rem;
  }
  .sec-hd h2 {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 1rem; font-weight: 800; text-transform: uppercase;
    letter-spacing: .04em; color: #fff;
  }
  .sec-hd-sub { font-size: .72rem; color: rgba(255,255,255,.3); margin-top: 1px; }

  /* ── TABLE ────────────────────────────────────── */
  .adm-table-wrap {
    border: 1px solid rgba(255,255,255,.07);
    border-radius: 10px; overflow: auto;
    background: #0f0f0f;
  }
  .adm-table { width: 100%; border-collapse: collapse; font-size: .82rem; white-space: nowrap; }
  .adm-table thead th {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: .62rem; font-weight: 700; letter-spacing: .11em; text-transform: uppercase;
    color: rgba(255,255,255,.25); padding: .75rem 1rem; text-align: left;
    background: rgba(255,255,255,.025); border-bottom: 1px solid rgba(255,255,255,.06);
  }
  .adm-table tbody tr { border-bottom: 1px solid rgba(255,255,255,.04); transition: background .12s; }
  .adm-table tbody tr:last-child { border-bottom: none; }
  .adm-table tbody tr:hover { background: rgba(255,255,255,.025); }
  .adm-table td { padding: .75rem 1rem; color: rgba(255,255,255,.7); vertical-align: middle; }
  .td-strong { font-weight: 600; color: #fff; }
  .td-muted  { color: rgba(255,255,255,.3); }
  .td-blue   { font-family:'Barlow Condensed',sans-serif; font-weight:700; color:#1a6bbd; }

  /* Inline table controls */
  .td-form { display: flex; align-items: center; gap: .4rem; }
  .td-form label { display: none; }
  .td-sel {
    background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.1);
    border-radius: 4px; color: #fff; font-family: 'Barlow', sans-serif;
    font-size: .78rem; padding: .3rem .5rem; outline: none; -webkit-appearance: none;
  }
  .td-sel:focus { border-color: #1a6bbd; }
  .td-sel option { background: #1a1a1a; }
  .td-inp {
    background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.1);
    border-radius: 4px; color: #fff; font-family: 'Barlow', sans-serif;
    font-size: .78rem; padding: .3rem .5rem; outline: none; width: auto;
  }
  .td-inp:focus { border-color: #1a6bbd; }

  /* ── STATUS PILLS ─────────────────────────────── */
  .ast-ok,.ast-wait,.ast-cancel {
    display: inline-flex; align-items: center; gap: .35rem;
    font-family:'Barlow Condensed',sans-serif; font-size:.65rem; font-weight:700;
    letter-spacing:.07em; text-transform:uppercase;
    padding:.2rem .6rem; border-radius:100px; border:1px solid; white-space:nowrap;
  }
  .ast-ok::before,.ast-wait::before,.ast-cancel::before { content:''; width:5px; height:5px; border-radius:50%; flex-shrink:0; }
  .ast-ok    { background:rgba(74,222,128,.08);  color:#4ade80; border-color:rgba(74,222,128,.2); }
  .ast-ok::before     { background:#4ade80; }
  .ast-wait  { background:rgba(251,191,36,.08);  color:#fbbf24; border-color:rgba(251,191,36,.2); }
  .ast-wait::before   { background:#fbbf24; }
  .ast-cancel{ background:rgba(248,113,113,.08); color:#f87171; border-color:rgba(248,113,113,.2); }
  .ast-cancel::before { background:#f87171; }

  /* ── ROLE BADGE ───────────────────────────────── */
  .role-badge {
    font-family:'Barlow Condensed',sans-serif; font-size:.62rem; font-weight:700;
    letter-spacing:.08em; text-transform:uppercase;
    padding:.18rem .55rem; border-radius:3px; border:1px solid;
  }
  .role-client { background:rgba(255,255,255,.05); color:rgba(255,255,255,.5); border-color:rgba(255,255,255,.1); }
  .role-mecano { background:rgba(26,107,189,.12);  color:#6aabf7; border-color:rgba(26,107,189,.2); }
  .role-admin  { background:rgba(248,113,113,.1);  color:#f87171; border-color:rgba(248,113,113,.2); }

  /* ── STOCK BADGE ──────────────────────────────── */
  .stk-ok    { background:rgba(74,222,128,.08);  color:#4ade80; border:1px solid rgba(74,222,128,.2);  font-family:'Barlow Condensed',sans-serif; font-size:.65rem; font-weight:700; letter-spacing:.07em; text-transform:uppercase; padding:.18rem .55rem; border-radius:100px; }
  .stk-low   { background:rgba(251,191,36,.08);  color:#fbbf24; border:1px solid rgba(251,191,36,.2);  font-family:'Barlow Condensed',sans-serif; font-size:.65rem; font-weight:700; letter-spacing:.07em; text-transform:uppercase; padding:.18rem .55rem; border-radius:100px; }
  .stk-rupture{ background:rgba(248,113,113,.08); color:#f87171; border:1px solid rgba(248,113,113,.2); font-family:'Barlow Condensed',sans-serif; font-size:.65rem; font-weight:700; letter-spacing:.07em; text-transform:uppercase; padding:.18rem .55rem; border-radius:100px; }

  /* ── FORM PANEL ───────────────────────────────── */
  .adm-form-panel {
    background: #101010; border: 1px solid rgba(255,255,255,.07);
    border-radius: 10px; padding: 1.5rem; margin-bottom: 1.5rem;
  }
  .adm-form-panel-title {
    font-family:'Barlow Condensed',sans-serif; font-size:.7rem; font-weight:700;
    letter-spacing:.12em; text-transform:uppercase; color:rgba(255,255,255,.3);
    margin-bottom:1.1rem; display:block;
  }
  .f-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:0 1rem; }
  .f-field { margin-bottom:.85rem; }
  .f-field label {
    display:block; font-family:'Barlow Condensed',sans-serif;
    font-size:.65rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase;
    color:rgba(255,255,255,.28); margin-bottom:.35rem;
  }
  .f-field input,.f-field select,.f-field textarea {
    width:100%; background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.1);
    border-radius:6px; color:#fff; font-family:'Barlow',sans-serif; font-size:.85rem;
    padding:.58rem .8rem; outline:none; transition:border-color .18s;
    -webkit-appearance:none;
  }
  .f-field input:focus,.f-field select:focus,.f-field textarea:focus { border-color:#1a6bbd; }
  .f-field input::placeholder,.f-field textarea::placeholder { color:rgba(255,255,255,.15); }
  .f-field select option { background:#1a1a1a; }

  /* ── BUTTONS ──────────────────────────────────── */
  .abtn {
    display:inline-flex; align-items:center; gap:.4rem;
    font-family:'Barlow Condensed',sans-serif; font-size:.75rem; font-weight:700;
    letter-spacing:.06em; text-transform:uppercase;
    padding:.42rem .9rem; border-radius:4px; border:1px solid transparent;
    cursor:pointer; transition:all .15s; white-space:nowrap;
  }
  .abtn:hover { transform:translateY(-1px); }
  .abtn-primary { background:#1a6bbd; color:#fff; border-color:#1a6bbd; }
  .abtn-primary:hover { background:#155fa0; }
  .abtn-ghost { background:transparent; color:#1a6bbd; border-color:#1a6bbd; }
  .abtn-ghost:hover { background:#1a6bbd; color:#fff; }
  .abtn-danger { background:rgba(248,113,113,.1); color:#f87171; border-color:rgba(248,113,113,.2); }
  .abtn-danger:hover { background:#f87171; color:#fff; border-color:#f87171; transform:none; }
  .abtn-muted { background:rgba(255,255,255,.05); color:rgba(255,255,255,.5); border-color:rgba(255,255,255,.08); }
  .abtn-muted:hover { background:rgba(255,255,255,.09); color:#fff; }

  /* ── EMPTY ────────────────────────────────────── */
  .adm-empty {
    padding:3rem; text-align:center;
    border:1px dashed rgba(255,255,255,.07); border-radius:8px;
  }
  .adm-empty p { font-size:.82rem; color:rgba(255,255,255,.2); margin-top:.4rem; }

  /* ── ALERT ────────────────────────────────────── */
  .adm-alert {
    display:flex; align-items:center; gap:.65rem;
    font-family:'Barlow Condensed',sans-serif; font-size:.82rem; font-weight:700;
    letter-spacing:.04em; text-transform:uppercase;
    padding:.75rem 1rem; border-radius:6px; margin-bottom:1.25rem; border:1px solid;
  }
  .adm-alert-ok  { background:rgba(74,222,128,.08);  border-color:rgba(74,222,128,.2);  color:#4ade80; }
  .adm-alert-err { background:rgba(248,113,113,.08); border-color:rgba(248,113,113,.2); color:#f87171; }

  /* ── RESPONSIVE ───────────────────────────────── */
  @media(max-width:900px){
    .admin-root { grid-template-columns:1fr; }
    .adm-sidebar { display:none; }
    .adm-main { margin-left:0; }
    .adm-content { padding:1.25rem; }
    .stat-cards { grid-template-columns:1fr 1fr; }
  }
  </style>
</head>
<body>

<div class="admin-root">

  <!-- ══ SIDEBAR ═══════════════════════════════════════════════ -->
  <aside class="adm-sidebar">

    <a href="espace_admin.php" class="adm-logo">
      Méca<span>Speed</span>
      <small>Console administrateur</small>
    </a>

    <nav class="adm-nav">
      <div class="adm-group">
        <span class="adm-section-label">Navigation</span>

        <button class="adm-link active" onclick="showPanel('stats',this)">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
          Statistiques
        </button>

        <button class="adm-link" onclick="showPanel('rdv',this)">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          Rendez-vous
          <?php if($rdv_pending>0): ?>
            <span class="adm-badge adm-badge-amber"><?= $rdv_pending ?></span>
          <?php endif; ?>
        </button>

        <button class="adm-link" onclick="showPanel('users',this)">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
          Utilisateurs
          <span class="adm-badge adm-badge-blue"><?= count($liste_users) ?></span>
        </button>

        <button class="adm-link" onclick="showPanel('vehicules',this)">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 3v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
          Véhicules
          <span class="adm-badge adm-badge-blue"><?= count($liste_vehicules) ?></span>
        </button>

        <button class="adm-link" onclick="showPanel('stock',this)">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
          Stock boutique
          <?php if($ruptures>0): ?>
            <span class="adm-badge adm-badge-red"><?= $ruptures ?></span>
          <?php endif; ?>
        </button>

      </div>
    </nav>

    <div class="adm-bottom">
      <div class="adm-profile">
        <div class="adm-avatar"><?= $initiales ?></div>
        <div>
          <div class="adm-profile-name"><?= htmlspecialchars($nom_admin) ?></div>
          <div class="adm-profile-role">Administrateur</div>
        </div>
      </div>
      <a href="logout.php" class="adm-logout">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Déconnexion
      </a>
    </div>

  </aside>

  <!-- ══ MAIN ══════════════════════════════════════════════════ -->
  <main class="adm-main" id="contenu-principal">

    <!-- Topbar -->
    <div class="adm-topbar">
      <div>
        <div class="adm-topbar-sub" id="topbar-sub">Vue globale</div>
        <div class="adm-topbar-title" id="topbar-title">Statistiques</div>
      </div>
      <div style="display:flex;gap:.5rem;align-items:center">
        <?php if(isset($_GET['ok'])): ?>
          <div class="adm-alert adm-alert-ok" style="margin:0;padding:.4rem .85rem">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            Opération réussie
          </div>
        <?php endif; ?>
        <?php if(isset($_GET['erreur'])): ?>
          <div class="adm-alert adm-alert-err" style="margin:0;padding:.4rem .85rem">Erreur : <?= htmlspecialchars($_GET['erreur']) ?></div>
        <?php endif; ?>
        <span style="font-family:'Barlow Condensed',sans-serif;font-size:.72rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:rgba(255,255,255,.2)"><?= date('d/m/Y') ?></span>
      </div>
    </div>

    <div class="adm-content">

      <!-- ══════ PANEL : STATISTIQUES ══════ -->
      <div class="adm-panel active" id="panel-stats">

        <!-- KPI cards -->
        <div class="stat-cards">
          <div class="stat-card">
            <span class="sc-label">Rendez-vous total</span>
            <span class="sc-value blue"><?= count($liste_rdv) ?></span>
            <span class="sc-sub"><?= $rdv_confirmed ?> confirmés · <?= $rdv_pending ?> en attente</span>
            <div class="sc-bar-track"><div class="sc-bar-fill" style="width:<?= count($liste_rdv)?min(100,round($rdv_confirmed/max(1,count($liste_rdv))*100)):0 ?>%;background:#1a6bbd"></div></div>
          </div>
          <div class="stat-card">
            <span class="sc-label">En attente</span>
            <span class="sc-value <?= $rdv_pending>0?'amber':'' ?>"><?= $rdv_pending ?></span>
            <span class="sc-sub">à confirmer ou affecter</span>
            <div class="sc-bar-track"><div class="sc-bar-fill" style="width:<?= count($liste_rdv)?min(100,round($rdv_pending/max(1,count($liste_rdv))*100)):0 ?>%;background:#fbbf24"></div></div>
          </div>
          <div class="stat-card">
            <span class="sc-label">Utilisateurs</span>
            <span class="sc-value"><?= count($liste_users) ?></span>
            <span class="sc-sub"><?= $nb_clients ?> clients · <?= $nb_mecanos ?> mécaniciens</span>
            <div class="sc-bar-track"><div class="sc-bar-fill" style="width:<?= count($liste_users)?min(100,round($nb_clients/max(1,count($liste_users))*100)):0 ?>%;background:#4ade80"></div></div>
          </div>
          <div class="stat-card">
            <span class="sc-label">Véhicules</span>
            <span class="sc-value"><?= count($liste_vehicules) ?></span>
            <span class="sc-sub">enregistrés dans le système</span>
          </div>
          <div class="stat-card">
            <span class="sc-label">Produits</span>
            <span class="sc-value"><?= count($liste_produits) ?></span>
            <span class="sc-sub"><?= $ruptures ?> rupture<?= $ruptures>1?'s':'' ?> de stock</span>
            <div class="sc-bar-track"><div class="sc-bar-fill" style="width:<?= $ruptures>0?min(100,round($ruptures/max(1,count($liste_produits))*100)):0 ?>%;background:#f87171"></div></div>
          </div>
          <div class="stat-card">
            <span class="sc-label">Ruptures de stock</span>
            <span class="sc-value <?= $ruptures>0?'red':'' ?>"><?= $ruptures ?></span>
            <span class="sc-sub"><?= $ruptures>0?'Action requise':'Tout en stock' ?></span>
          </div>
        </div>

        <!-- Résumé RDV par statut -->
        <div class="sec-hd" style="margin-top:1rem">
          <div><h2>Derniers rendez-vous</h2><div class="sec-hd-sub">5 plus récents</div></div>
          <button class="abtn abtn-ghost" onclick="showPanel('rdv', document.querySelectorAll('.adm-link')[1])">Voir tous →</button>
        </div>
        <div class="adm-table-wrap">
          <table class="adm-table">
            <thead><tr><th>Date</th><th>Client</th><th>Service</th><th>Mécanicien</th><th>Statut</th></tr></thead>
            <tbody>
              <?php foreach(array_slice($liste_rdv,0,5) as $r): ?>
                <tr>
                  <td class="td-strong"><?= date('d/m/Y',strtotime($r['date_rdv'])) ?> <span class="td-muted" style="font-size:.75rem"><?= substr($r['heure_rdv'],0,5) ?></span></td>
                  <td><?= htmlspecialchars($r['nom_client']) ?></td>
                  <td><?= htmlspecialchars($r['service']??'—') ?></td>
                  <td class="td-muted"><?= htmlspecialchars($r['nom_mecano']??'Non affecté') ?></td>
                  <td><span class="<?= st_cl($r['statut']) ?>"><?= st_lb($r['statut']) ?></span></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

      </div>

      <!-- ══════ PANEL : RENDEZ-VOUS ══════ -->
      <div class="adm-panel" id="panel-rdv">
        <div class="sec-hd">
          <div><h2>Rendez-vous clients</h2><div class="sec-hd-sub"><?= count($liste_rdv) ?> au total · <?= $rdv_pending ?> en attente</div></div>
        </div>

        <?php if($err_rdv): ?>
          <div class="adm-alert adm-alert-err"><?= htmlspecialchars($err_rdv) ?></div>
        <?php elseif(empty($liste_rdv)): ?>
          <div class="adm-empty"><svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.15)" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg><p>Aucun rendez-vous.</p></div>
        <?php else: ?>
          <div class="adm-table-wrap">
            <table class="adm-table">
              <thead><tr><th>Date</th><th>Client</th><th>Véhicule</th><th>Service</th><th>Mécanicien</th><th>Statut</th><th>Affecter</th></tr></thead>
              <tbody>
                <?php foreach($liste_rdv as $rdv): ?>
                  <tr>
                    <td><span class="td-strong"><?= date('d/m/Y',strtotime($rdv['date_rdv'])) ?></span><br><span class="td-muted" style="font-size:.72rem"><?= substr($rdv['heure_rdv'],0,5) ?></span></td>
                    <td><?= htmlspecialchars($rdv['nom_client']) ?></td>
                    <td class="td-muted"><?= htmlspecialchars(strtoupper($rdv['vehicule_text'])) ?></td>
                    <td><?= htmlspecialchars($rdv['service']??'—') ?></td>
                    <td class="td-muted"><?= htmlspecialchars($rdv['nom_mecano']??'—') ?></td>
                    <td><span class="<?= st_cl($rdv['statut']) ?>"><?= st_lb($rdv['statut']) ?></span></td>
                    <td>
                      <form action="actions/assign_rdv.php" method="POST" class="td-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="rdv_id" value="<?= intval($rdv['id']) ?>">
                        <label>Mécanicien</label>
                        <select name="mecano_id" class="td-sel">
                          <option value="">—</option>
                          <?php foreach($liste_mecanos as $m): ?>
                            <option value="<?= intval($m['id']) ?>" <?= intval($rdv['mecano_id'])===intval($m['id'])?'selected':'' ?>><?= htmlspecialchars($m['nom']) ?></option>
                          <?php endforeach; ?>
                        </select>
                        <button type="submit" class="abtn abtn-ghost" style="padding:.28rem .7rem">OK</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <!-- ══════ PANEL : UTILISATEURS ══════ -->
      <div class="adm-panel" id="panel-users">

        <!-- Créer mécanicien -->
        <div class="adm-form-panel">
          <span class="adm-form-panel-title">Créer un compte mécanicien</span>
          <form action="actions/create_user.php" method="POST">
            <?= csrf_field() ?>
            <input type="hidden" name="role" value="mecano">
            <div class="f-grid">
              <div class="f-field"><label>Nom</label><input type="text" name="nom" placeholder="Prénom Nom" required></div>
              <div class="f-field"><label>Email</label><input type="email" name="email" placeholder="email@mecaspeed.com" required></div>
              <div class="f-field"><label>Téléphone</label><input type="tel" name="telephone" placeholder="0550000000"></div>
              <div class="f-field"><label>Mot de passe</label><input type="password" name="mot_de_passe" placeholder="Temporaire" required></div>
              <div class="f-field"><label>Garage</label><select name="garages_id"><option value="">Non affecté</option><?php foreach($liste_garages as $g): ?><option value="<?= intval($g['id']) ?>"><?= htmlspecialchars($g['nom']) ?></option><?php endforeach; ?></select></div>
            </div>
            <button type="submit" class="abtn abtn-primary">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              Créer le mécanicien
            </button>
          </form>
        </div>

        <div class="sec-hd">
          <div><h2>Tous les utilisateurs</h2><div class="sec-hd-sub"><?= count($liste_users) ?> comptes · <?= $nb_clients ?> clients · <?= $nb_mecanos ?> mécaniciens</div></div>
        </div>

        <?php if($err_usr): ?>
          <div class="adm-alert adm-alert-err"><?= htmlspecialchars($err_usr) ?></div>
        <?php elseif(!empty($liste_users)): ?>
          <div class="adm-table-wrap">
            <table class="adm-table">
              <thead><tr><th>#</th><th>Nom</th><th>Email</th><th>Téléphone</th><th>Rôle</th><th>Garage</th><th>Modifier</th><th>Supprimer</th></tr></thead>
              <tbody>
                <?php foreach($liste_users as $u): ?>
                  <tr>
                    <td class="td-muted"><?= intval($u['id']) ?></td>
                    <td class="td-strong"><?= htmlspecialchars($u['nom']) ?></td>
                    <td class="td-muted"><?= htmlspecialchars($u['email']) ?></td>
                    <td class="td-muted"><?= htmlspecialchars($u['telephone']??'—') ?></td>
                    <td><span class="role-badge role-<?= $u['role'] ?>"><?= $u['role'] ?></span></td>
                    <td class="td-muted"><?= htmlspecialchars($u['garages_id']??'—') ?></td>
                    <td>
                      <form action="actions/update_user.php" method="POST" class="td-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="user_id" value="<?= intval($u['id']) ?>">
                        <label>Rôle</label>
                        <select name="role" class="td-sel">
                          <?php foreach(['client','mecano','admin'] as $ro): ?><option value="<?= $ro ?>" <?= $u['role']===$ro?'selected':'' ?>><?= $ro ?></option><?php endforeach; ?>
                        </select>
                        <label>Garage</label>
                        <select name="garages_id" class="td-sel">
                          <option value="">—</option>
                          <?php foreach($liste_garages as $g): ?><option value="<?= intval($g['id']) ?>" <?= intval($u['garages_id'])===intval($g['id'])?'selected':'' ?>><?= htmlspecialchars($g['nom']) ?></option><?php endforeach; ?>
                        </select>
                        <button type="submit" class="abtn abtn-muted" style="padding:.28rem .65rem">MAJ</button>
                      </form>
                    </td>
                    <td>
                      <?php if(intval($u['id'])!==intval($_SESSION['user_id'])): ?>
                        <form action="actions/delete_user.php" method="POST">
                          <?= csrf_field() ?>
                          <input type="hidden" name="user_id" value="<?= intval($u['id']) ?>">
                          <button type="submit" class="abtn abtn-danger" style="padding:.28rem .65rem">Supprimer</button>
                        </form>
                      <?php else: ?>
                        <span class="td-muted" style="font-size:.72rem">Vous</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <!-- ══════ PANEL : VÉHICULES ══════ -->
      <div class="adm-panel" id="panel-vehicules">
        <div class="sec-hd">
          <div><h2>Véhicules clients</h2><div class="sec-hd-sub"><?= count($liste_vehicules) ?> enregistrés</div></div>
        </div>
        <?php if($err_veh): ?>
          <div class="adm-alert adm-alert-err"><?= htmlspecialchars($err_veh) ?></div>
        <?php elseif(empty($liste_vehicules)): ?>
          <div class="adm-empty"><p>Aucun véhicule enregistré.</p></div>
        <?php else: ?>
          <div class="adm-table-wrap">
            <table class="adm-table">
              <thead><tr><th>#</th><th>Propriétaire</th><th>Marque</th><th>Modèle</th><th>Immatriculation</th><th>Année</th></tr></thead>
              <tbody>
                <?php foreach($liste_vehicules as $v): ?>
                  <tr>
                    <td class="td-muted"><?= intval($v['id_v']) ?></td>
                    <td class="td-strong"><?= htmlspecialchars($v['nom_proprio']) ?></td>
                    <td><?= htmlspecialchars($v['marque']) ?></td>
                    <td><?= htmlspecialchars($v['modele']) ?></td>
                    <td class="td-blue"><?= htmlspecialchars($v['immatriculation']) ?></td>
                    <td class="td-muted"><?= htmlspecialchars($v['annee']??'—') ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

      <!-- ══════ PANEL : STOCK ══════ -->
      <div class="adm-panel" id="panel-stock">

        <!-- Ajouter produit -->
        <div class="adm-form-panel">
          <span class="adm-form-panel-title">Ajouter un produit</span>
          <form action="actions/save_product.php" method="POST" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="f-grid">
              <div class="f-field"><label>Nom</label><input type="text" name="nom" placeholder="Ex: Huile Castrol 5W40" required></div>
              <div class="f-field"><label>Prix (DA)</label><input type="number" step="0.01" min="0" name="prix" placeholder="950.00" required></div>
              <div class="f-field"><label>Stock</label><input type="number" min="0" name="en_stock" value="0"></div>
              <div class="f-field">
                <label>Photo du produit</label>
                <label for="upload-new" style="display:flex;align-items:center;gap:.7rem;background:rgba(255,255,255,.04);border:1.5px dashed rgba(255,255,255,.15);border-radius:6px;padding:.62rem .9rem;cursor:pointer;transition:all .18s" id="upload-new-label"
                  onmouseover="this.style.borderColor='#1a6bbd';this.style.background='rgba(26,107,189,.06)'"
                  onmouseout="this.style.borderColor='rgba(255,255,255,.15)';this.style.background='rgba(255,255,255,.04)'">
                  <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.4)" stroke-width="1.8"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                  <div>
                    <div id="upload-new-name" style="font-family:'Barlow Condensed',sans-serif;font-size:.76rem;font-weight:700;letter-spacing:.05em;text-transform:uppercase;color:rgba(255,255,255,.55)">Choisir une image</div>
                    <div style="font-size:.65rem;color:rgba(255,255,255,.22);margin-top:1px">JPG · PNG · WEBP — max 3 Mo</div>
                  </div>
                </label>
                <input type="file" id="upload-new" name="image" accept="image/*" style="display:none"
                  onchange="previewUpload(this,'upload-new-name','preview-new')">
                <img id="preview-new" src="" alt="" style="display:none;margin-top:.5rem;max-height:72px;border-radius:6px;border:1px solid rgba(255,255,255,.1);object-fit:cover">
              </div>
              <div class="f-field"><label>Catégorie</label><select name="categorie_id" required><?php foreach($liste_categories as $c): ?><option value="<?= intval($c['id']) ?>"><?= htmlspecialchars($c['nom']) ?></option><?php endforeach; ?></select></div>
              <div class="f-field"><label>Garage</label><select name="garage_id"><?php foreach($liste_garages as $g): ?><option value="<?= intval($g['id']) ?>"><?= htmlspecialchars($g['nom']) ?></option><?php endforeach; ?></select></div>
            </div>
            <div class="f-field" style="max-width:100%"><label>Description</label><textarea name="description" rows="2" placeholder="Description du produit…"></textarea></div>
            <button type="submit" class="abtn abtn-primary">
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
              Ajouter le produit
            </button>
          </form>
        </div>

        <div class="sec-hd">
          <div><h2>Inventaire boutique</h2><div class="sec-hd-sub"><?= count($liste_produits) ?> produits · <?= $ruptures ?> rupture<?= $ruptures>1?'s':'' ?></div></div>
        </div>

        <?php if($err_stk): ?>
          <div class="adm-alert adm-alert-err"><?= htmlspecialchars($err_stk) ?></div>
        <?php elseif(!empty($liste_produits)): ?>
          <div class="adm-table-wrap">
            <table class="adm-table">
              <thead><tr><th>#</th><th>Produit</th><th>Prix</th><th>Stock</th><th>Modifier</th><th>Supprimer</th></tr></thead>
              <tbody>
                <?php foreach($liste_produits as $p): $stk=intval($p['en_stock']); ?>
                  <tr>
                    <td class="td-muted"><?= intval($p['id']) ?></td>
                    <td class="td-strong"><?= htmlspecialchars($p['nom']) ?></td>
                    <td><?= number_format($p['prix'],2,',',' ') ?> <span class="td-muted">DA</span></td>
                    <td>
                      <?php if($stk<=0): ?>
                        <span class="stk-rupture">Rupture</span>
                      <?php elseif($stk<=5): ?>
                        <span class="stk-low"><?= $stk ?> restants</span>
                      <?php else: ?>
                        <span class="stk-ok"><?= $stk ?></span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <form action="actions/save_product.php" method="POST" class="td-form" enctype="multipart/form-data">
                        <?= csrf_field() ?>
                        <input type="hidden" name="produit_id"    value="<?= intval($p['id']) ?>">
                        <input type="hidden" name="description"   value="<?= htmlspecialchars($p['description']??'',ENT_QUOTES) ?>">
                        <input type="hidden" name="image_actuelle" value="<?= htmlspecialchars($p['image']??'',ENT_QUOTES) ?>">
                        <input type="hidden" name="categorie_id"  value="<?= intval($p['categorie_id']) ?>">
                        <input type="hidden" name="garage_id"     value="<?= intval($p['garage_id']) ?>">
                        <label>Nom</label><input type="text"   class="td-inp" name="nom"      value="<?= htmlspecialchars($p['nom']) ?>"   required style="width:110px">
                        <label>Prix</label><input type="number" class="td-inp" name="prix"     value="<?= $p['prix'] ?>" step=".01" min="0" required style="width:75px">
                        <label>Stock</label><input type="number" class="td-inp" name="en_stock" value="<?= $stk ?>" min="0" style="width:55px">
                        <label for="img-<?= intval($p['id']) ?>" title="Changer la photo" style="cursor:pointer;display:inline-flex;align-items:center;background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);border-radius:4px;padding:.28rem .55rem">
                          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.55)" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                        </label>
                        <input type="file" id="img-<?= intval($p['id']) ?>" name="image" accept="image/*" style="display:none"
                          onchange="this.previousElementSibling.previousElementSibling.title=this.files[0]?.name||'Photo'">
                        <button type="submit" class="abtn abtn-muted" style="padding:.28rem .65rem">MAJ</button>
                      </form>
                    </td>
                    <td>
                      <form action="actions/delete_product.php" method="POST">
                        <?= csrf_field() ?>
                        <input type="hidden" name="produit_id" value="<?= intval($p['id']) ?>">
                        <button type="submit" class="abtn abtn-danger" style="padding:.28rem .65rem">Suppr.</button>
                      </form>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>

    </div><!-- /adm-content -->
  </main>

</div><!-- /admin-root -->

<script>
// Preview upload image
function previewUpload(input, nameId, previewId) {
  const file = input.files[0];
  const nameEl = document.getElementById(nameId);
  const previewEl = document.getElementById(previewId);
  if (!file) return;
  if (nameEl) nameEl.textContent = file.name;
  if (previewEl) {
    const reader = new FileReader();
    reader.onload = e => {
      previewEl.src = e.target.result;
      previewEl.style.display = 'block';
    };
    reader.readAsDataURL(file);
  }
}

const PANELS = {
  stats:     { title:'Statistiques',    sub:'Vue globale' },
  rdv:       { title:'Rendez-vous',     sub:'Planning clients' },
  users:     { title:'Utilisateurs',    sub:'Accès et rôles' },
  vehicules: { title:'Véhicules',       sub:'Flotte clients' },
  stock:     { title:'Stock boutique',  sub:'Inventaire' },
};

function showPanel(id, btn) {
  document.querySelectorAll('.adm-panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.adm-link').forEach(b => b.classList.remove('active'));
  document.getElementById('panel-' + id).classList.add('active');
  if (btn) btn.classList.add('active');
  const info = PANELS[id] || {};
  document.getElementById('topbar-title').textContent = info.title || '';
  document.getElementById('topbar-sub').textContent   = info.sub   || '';
  // update URL hash silently
  history.replaceState(null, '', '#' + id);
}

// Restore from hash
(function(){
  const hash = location.hash.replace('#','');
  const btns = document.querySelectorAll('.adm-link');
  const map  = { stats:0, rdv:1, users:2, vehicules:3, stock:4 };
  if (hash && map[hash] !== undefined) {
    showPanel(hash, btns[map[hash]]);
  }
})();
</script>
</body>
</html>
