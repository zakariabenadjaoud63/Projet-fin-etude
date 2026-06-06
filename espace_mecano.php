<?php
session_start();
require_once 'connexion_bd.php';
require_once 'includes/security.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mecano') { header('Location: login.php'); exit(); }

$mid      = $_SESSION['user_id'];
$nom_mec  = $_SESSION['nom'] ?? 'Mécanicien';
$initiales = strtoupper(mb_substr($nom_mec, 0, 1));
$mots = explode(' ', $nom_mec);
if (count($mots) >= 2) $initiales = strtoupper(mb_substr($mots[0],0,1).mb_substr($mots[1],0,1));

$rdvs = $pdo->prepare("
  SELECT r.*, u.nom as nom_client, u.id as client_id, g.nom as nom_garage,
         v.immatriculation as vehicule_immat, CONCAT(v.marque,' ',v.modele) as vehicule_modele
  FROM rendez_vous r
  JOIN utilisateurs u ON r.utilisateur_id = u.id
  LEFT JOIN garages g ON r.garage_id = g.id
  LEFT JOIN vehicules v ON r.id_vl = v.id_v
  WHERE r.mecano_id = :mid
  ORDER BY r.statut ASC, r.date_rdv ASC
");
$rdvs->execute([':mid' => $mid]);
$rdvs = $rdvs->fetchAll();

$suivis = $pdo->prepare("
  SELECT sv.*, u.nom as nom_client
  FROM suivi_vehicules sv
  JOIN utilisateurs u ON sv.utilisateur_id = u.id
  WHERE sv.mecanicien = :nom
  ORDER BY sv.cree_le DESC
");
$suivis->execute([':nom' => $nom_mec]);
$suivis = $suivis->fetchAll();

$nb_pending   = count(array_filter($rdvs, fn($r) => $r['statut'] === 'pending'));
$nb_confirmed = count(array_filter($rdvs, fn($r) => $r['statut'] === 'confirmed'));
$nb_cancelled = count(array_filter($rdvs, fn($r) => $r['statut'] === 'cancelled'));
$today        = date('Y-m-d');

function sc(string $s): string { return match($s){'confirmed'=>'ms-ok','cancelled'=>'ms-cancel',default=>'ms-wait'}; }
function sl(string $s): string { return match($s){'confirmed'=>'Confirmé','cancelled'=>'Annulé',default=>'En attente'}; }

$mois_fr = ['','Jan','Fév','Mar','Avr','Mai','Jun','Jul','Aoû','Sep','Oct','Nov','Déc'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MécaSpeed — Atelier</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@400;600;700;800;900&family=Barlow:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="includes/mecaspeed-ui.css">
  <style>
  /* ═══════════════════════════════════════════
     MECHANIC DASHBOARD
  ═══════════════════════════════════════════ */
  html,body { height:100%; }
  body { background:#080808; }

  .mec-root {
    display: grid;
    grid-template-columns: 220px 1fr;
    height: 100vh; overflow: hidden;
  }

  /* ── SIDEBAR ─────────────────────────────── */
  .mec-sidebar {
    background: #0c0c0c;
    border-right: 1px solid rgba(255,255,255,.07);
    display: flex; flex-direction: column;
    height: 100vh; overflow-y: auto;
    padding: 1.5rem 0;
    position: sticky; top: 0;
  }

  .mec-logo {
    font-family: 'Barlow Condensed', sans-serif;
    font-size: 1.2rem; font-weight: 900; text-transform: uppercase;
    letter-spacing: .03em; color: #fff;
    padding: 0 1.25rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,.06);
    margin-bottom: 1.5rem; display: block;
  }
  .mec-logo span { color: #1a6bbd; }
  .mec-logo small {
    display: block; margin-top: 2px;
    font-family: 'Barlow Condensed', sans-serif; font-size: .58rem;
    font-weight: 700; letter-spacing: .14em; text-transform: uppercase;
    color: rgba(255,255,255,.22);
  }

  .mec-nav { flex: 1; padding: 0 .75rem; }
  .mec-section-label {
    font-family: 'Barlow Condensed', sans-serif; font-size: .58rem; font-weight: 700;
    letter-spacing: .16em; text-transform: uppercase; color: rgba(255,255,255,.2);
    padding: 0 .6rem; margin-bottom: .35rem; display: block;
  }
  .mec-group { margin-bottom: 1.5rem; }

  .mec-link {
    display: flex; align-items: center; gap: .65rem;
    font-family: 'Barlow Condensed', sans-serif; font-size: .8rem;
    font-weight: 600; letter-spacing: .04em; text-transform: uppercase;
    color: rgba(255,255,255,.4); padding: .52rem .75rem; border-radius: 6px;
    transition: all .15s; cursor: pointer; border: 1px solid transparent;
    background: none; width: 100%; text-align: left;
  }
  .mec-link svg { flex-shrink: 0; opacity: .45; transition: opacity .15s; }
  .mec-link:hover { color: rgba(255,255,255,.8); background: rgba(255,255,255,.05); }
  .mec-link:hover svg { opacity: .8; }
  .mec-link.active { color: #fff; background: rgba(26,107,189,.14); border-color: rgba(26,107,189,.2); }
  .mec-link.active svg { opacity: 1; color: #1a6bbd; }

  .mec-badge {
    margin-left: auto; font-size: .6rem; font-weight: 800;
    padding: 1px 6px; border-radius: 10px; min-width: 18px; text-align: center;
  }
  .mb-blue  { background: rgba(26,107,189,.25); color: #6aabf7; }
  .mb-amber { background: rgba(251,191,36,.2);  color: #fbbf24; }
  .mb-green { background: rgba(74,222,128,.2);  color: #4ade80; }

  .mec-bottom {
    padding: 1.25rem; margin-top: auto;
    border-top: 1px solid rgba(255,255,255,.06);
  }
  .mec-profile { display: flex; align-items: center; gap: .75rem; margin-bottom: 1rem; }
  .mec-avatar {
    width: 34px; height: 34px; border-radius: 8px;
    background: linear-gradient(135deg,#1a6bbd,#0e4a8a);
    display: flex; align-items: center; justify-content: center;
    font-family: 'Barlow Condensed', sans-serif; font-size: .85rem;
    font-weight: 900; color: #fff; flex-shrink: 0;
  }
  .mec-profile-name {
    font-family: 'Barlow Condensed', sans-serif; font-size: .78rem;
    font-weight: 800; text-transform: uppercase; letter-spacing: .04em; color: #fff;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
  }
  .mec-profile-role {
    font-size: .62rem; color: rgba(255,255,255,.3); text-transform: uppercase;
    letter-spacing: .08em; font-family: 'Barlow Condensed', sans-serif; font-weight: 700;
  }
  .mec-logout {
    display: flex; align-items: center; gap: .55rem;
    font-family: 'Barlow Condensed', sans-serif; font-size: .75rem; font-weight: 700;
    letter-spacing: .05em; text-transform: uppercase; color: rgba(255,255,255,.25);
    padding: .45rem .5rem; border-radius: 6px; transition: all .15s; text-decoration: none;
  }
  .mec-logout:hover { color: #f87171; background: rgba(248,113,113,.07); }

  /* ── MAIN ────────────────────────────────── */
  .mec-main { height: 100vh; overflow-y: auto; display: flex; flex-direction: column; min-width: 0; }

  .mec-topbar {
    display: flex; align-items: center; justify-content: space-between;
    padding: 1.25rem 2rem; background: #0a0a0a;
    border-bottom: 1px solid rgba(255,255,255,.06);
    position: sticky; top: 0; z-index: 10; flex-shrink: 0;
  }
  .mec-topbar-sub { font-family:'Barlow Condensed',sans-serif; font-size:.7rem; font-weight:700; letter-spacing:.12em; text-transform:uppercase; color:rgba(255,255,255,.28); margin-bottom:2px; }
  .mec-topbar-title { font-family:'Barlow Condensed',sans-serif; font-size:1.1rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em; color:#fff; }

  .mec-content { padding: 2rem; flex: 1; }

  /* Panels */
  .mec-panel { display: none; }
  .mec-panel.active { display: block; }

  /* ── STAT CARDS ───────────────────────────── */
  .mec-stats {
    display: grid; grid-template-columns: repeat(4,1fr);
    gap: .75rem; margin-bottom: 2rem;
  }
  .msc {
    background: #101010; border: 1px solid rgba(255,255,255,.07);
    border-radius: 10px; padding: 1.1rem 1.25rem;
  }
  .msc-label { font-family:'Barlow Condensed',sans-serif; font-size:.6rem; font-weight:700; letter-spacing:.13em; text-transform:uppercase; color:rgba(255,255,255,.28); display:block; margin-bottom:.5rem; }
  .msc-val   { font-family:'Barlow Condensed',sans-serif; font-size:1.9rem; font-weight:900; color:#fff; line-height:1; display:block; margin-bottom:.2rem; }
  .msc-val.blue  { color:#1a6bbd; }
  .msc-val.amber { color:#fbbf24; }
  .msc-val.green { color:#4ade80; }
  .msc-sub  { font-size:.7rem; color:rgba(255,255,255,.22); }

  /* ── SEC HEAD ─────────────────────────────── */
  .sec-hd { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.1rem; }
  .sec-hd-title { font-family:'Barlow Condensed',sans-serif; font-size:.95rem; font-weight:800; text-transform:uppercase; letter-spacing:.04em; color:#fff; }
  .sec-hd-sub { font-size:.7rem; color:rgba(255,255,255,.28); margin-top:1px; }

  /* ── RDV CARDS ────────────────────────────── */
  .rdv-stack { display:flex; flex-direction:column; gap:.5rem; }

  .rdv-card {
    background: #101010; border: 1px solid rgba(255,255,255,.07);
    border-radius: 8px; overflow: hidden;
    transition: border-color .15s;
  }
  .rdv-card:hover { border-color: rgba(255,255,255,.12); }
  .rdv-card.is-pending { border-left: 3px solid #fbbf24; }
  .rdv-card.is-confirmed { border-left: 3px solid #4ade80; }
  .rdv-card.is-cancelled { border-left: 3px solid rgba(248,113,113,.4); opacity: .6; }

  .rdv-card-inner {
    display: grid; grid-template-columns: 60px 1fr auto;
    align-items: center; gap: 1.25rem; padding: 1rem 1.25rem;
  }
  .rdv-date-block {
    background: rgba(255,255,255,.04); border: 1px solid rgba(255,255,255,.08);
    border-radius: 8px; padding: .45rem .3rem; text-align: center;
  }
  .rdv-day { font-family:'Barlow Condensed',sans-serif; font-size:1.4rem; font-weight:900; color:#fff; line-height:1; display:block; }
  .rdv-month { font-family:'Barlow Condensed',sans-serif; font-size:.58rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:rgba(255,255,255,.3); display:block; margin-top:1px; }
  .rdv-card.is-pending .rdv-date-block { background:rgba(251,191,36,.08); border-color:rgba(251,191,36,.2); }
  .rdv-card.is-pending .rdv-month { color:#fbbf24; }
  .rdv-card.is-confirmed .rdv-date-block { background:rgba(74,222,128,.07); border-color:rgba(74,222,128,.18); }
  .rdv-card.is-confirmed .rdv-month { color:#4ade80; }

  .rdv-info { min-width:0; }
  .rdv-service { font-family:'Barlow Condensed',sans-serif; font-size:.92rem; font-weight:800; text-transform:uppercase; letter-spacing:.03em; color:#fff; margin-bottom:.3rem; }
  .rdv-meta { display:flex; gap:.75rem; flex-wrap:wrap; font-size:.75rem; color:rgba(255,255,255,.32); }
  .rdv-meta span { display:flex; align-items:center; gap:.3rem; }
  .rdv-client-name { color:rgba(255,255,255,.55); font-weight:600; }

  .rdv-right { display:flex; flex-direction:column; align-items:flex-end; gap:.6rem; flex-shrink:0; }

  /* Notes */
  .rdv-notes {
    padding: .6rem 1.25rem .75rem;
    border-top: 1px solid rgba(255,255,255,.05);
    font-size: .78rem; color: rgba(255,255,255,.35);
    background: rgba(255,255,255,.01);
  }
  .rdv-notes span { font-family:'Barlow Condensed',sans-serif; font-size:.6rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:rgba(255,255,255,.2); display:block; margin-bottom:2px; }

  /* Actions */
  .rdv-actions { display:flex; gap:.4rem; flex-wrap:wrap; }
  .ma { display:inline-flex; align-items:center; gap:.35rem; font-family:'Barlow Condensed',sans-serif; font-size:.7rem; font-weight:700; letter-spacing:.06em; text-transform:uppercase; padding:.32rem .8rem; border-radius:4px; border:1px solid transparent; cursor:pointer; transition:all .15s; white-space:nowrap; }
  .ma:hover { transform:translateY(-1px); }
  .ma-confirm { background:rgba(74,222,128,.1); color:#4ade80; border-color:rgba(74,222,128,.22); }
  .ma-confirm:hover { background:#4ade80; color:#000; }
  .ma-cancel  { background:rgba(248,113,113,.08); color:#f87171; border-color:rgba(248,113,113,.18); }
  .ma-cancel:hover { background:#f87171; color:#fff; transform:none; }
  .ma-suivi   { background:rgba(26,107,189,.12); color:#6aabf7; border-color:rgba(26,107,189,.25); }
  .ma-suivi:hover { background:#1a6bbd; color:#fff; }

  /* ── STATUS PILLS ─────────────────────────── */
  .ms-ok,.ms-wait,.ms-cancel {
    display:inline-flex; align-items:center; gap:.35rem;
    font-family:'Barlow Condensed',sans-serif; font-size:.65rem; font-weight:700;
    letter-spacing:.08em; text-transform:uppercase;
    padding:.2rem .6rem; border-radius:100px; border:1px solid; white-space:nowrap;
  }
  .ms-ok::before,.ms-wait::before,.ms-cancel::before { content:''; width:5px; height:5px; border-radius:50%; flex-shrink:0; }
  .ms-ok    { background:rgba(74,222,128,.08);  color:#4ade80; border-color:rgba(74,222,128,.2); }
  .ms-ok::before     { background:#4ade80; }
  .ms-wait  { background:rgba(251,191,36,.08);  color:#fbbf24; border-color:rgba(251,191,36,.2); }
  .ms-wait::before   { background:#fbbf24; }
  .ms-cancel{ background:rgba(248,113,113,.08); color:#f87171; border-color:rgba(248,113,113,.2); }
  .ms-cancel::before { background:#f87171; }

  /* ── SUIVI FORM ───────────────────────────── */
  .suivi-layout { display:grid; grid-template-columns:1fr 1fr; gap:1.5rem; align-items:start; }

  .suivi-form-panel {
    background: #101010; border: 1px solid rgba(255,255,255,.08);
    border-radius: 10px; padding: 1.5rem;
  }
  .suivi-form-title { font-family:'Barlow Condensed',sans-serif; font-size:.7rem; font-weight:700; letter-spacing:.12em; text-transform:uppercase; color:rgba(255,255,255,.28); margin-bottom:1.25rem; display:block; }

  .f-field { margin-bottom:.9rem; }
  .f-field label { display:block; font-family:'Barlow Condensed',sans-serif; font-size:.65rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:rgba(255,255,255,.28); margin-bottom:.38rem; }
  .f-field input,.f-field select,.f-field textarea {
    width:100%; background:rgba(255,255,255,.04); border:1px solid rgba(255,255,255,.1);
    border-radius:6px; color:#fff; font-family:'Barlow',sans-serif; font-size:.88rem;
    padding:.62rem .85rem; outline:none; transition:border-color .18s, box-shadow .18s;
    -webkit-appearance:none;
  }
  .f-field input:focus,.f-field select:focus,.f-field textarea:focus { border-color:#1a6bbd; box-shadow:0 0 0 3px rgba(26,107,189,.14); }
  .f-field input::placeholder,.f-field textarea::placeholder { color:rgba(255,255,255,.14); }
  .f-field select option { background:#1a1a1a; }
  .f-field textarea { resize:vertical; min-height:80px; line-height:1.5; }
  .f-row { display:grid; grid-template-columns:1fr 1fr; gap:0 .9rem; }
  .range-wrap { display:flex; align-items:center; gap:.75rem; }
  .range-wrap input[type=range] { flex:1; -webkit-appearance:none; height:4px; background:rgba(255,255,255,.1); border-radius:2px; outline:none; border:none; padding:0; cursor:pointer; }
  .range-wrap input[type=range]::-webkit-slider-thumb { -webkit-appearance:none; width:17px; height:17px; border-radius:50%; background:#1a6bbd; border:3px solid #0c0c0c; box-shadow:0 0 0 1px #1a6bbd; }
  .range-val { font-family:'Barlow Condensed',sans-serif; font-size:.9rem; font-weight:800; color:#1a6bbd; min-width:36px; text-align:right; }

  .btn-submit-form {
    width:100%; display:flex; align-items:center; justify-content:center; gap:.45rem; margin-top:1.25rem;
    font-family:'Barlow Condensed',sans-serif; font-size:.82rem; font-weight:700; letter-spacing:.06em; text-transform:uppercase;
    background:#1a6bbd; color:#fff; border:none; border-radius:6px;
    padding:.75rem; cursor:pointer; transition:background .15s, box-shadow .15s;
  }
  .btn-submit-form:hover { background:#155fa0; box-shadow:0 4px 18px rgba(26,107,189,.35); }

  /* Pre-fill select */
  .prefill-select { width:100%; margin-bottom:1.25rem; }

  /* ── SUIVI HISTORY CARDS ──────────────────── */
  .suivi-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(280px,1fr)); gap:.75rem; }
  .suivi-card {
    background:#101010; border:1px solid rgba(255,255,255,.07); border-radius:10px; overflow:hidden;
    transition:border-color .15s;
  }
  .suivi-card:hover { border-color:rgba(26,107,189,.25); }
  .suivi-card-head {
    padding:.9rem 1.1rem; border-bottom:1px solid rgba(255,255,255,.06);
    display:flex; align-items:center; justify-content:space-between; gap:.75rem;
    background:rgba(255,255,255,.02);
  }
  .suivi-car { font-family:'Barlow Condensed',sans-serif; font-size:.9rem; font-weight:800; text-transform:uppercase; color:#fff; }
  .suivi-immat { font-family:'Barlow Condensed',sans-serif; font-size:.7rem; font-weight:700; letter-spacing:.07em; color:#1a6bbd; margin-top:1px; }
  .suivi-body { padding:.9rem 1.1rem; }
  .suivi-prog-label { display:flex; justify-content:space-between; align-items:center; margin-bottom:.4rem; }
  .suivi-prog-text { font-family:'Barlow Condensed',sans-serif; font-size:.62rem; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:rgba(255,255,255,.28); }
  .suivi-prog-pct { font-family:'Barlow Condensed',sans-serif; font-size:.8rem; font-weight:900; color:#1a6bbd; }
  .prog-track { height:4px; background:rgba(255,255,255,.08); border-radius:2px; overflow:hidden; margin-bottom:.85rem; }
  .prog-fill { height:100%; background:#1a6bbd; border-radius:2px; }
  .prog-fill.done { background:#4ade80; }
  .suivi-dl { display:flex; flex-direction:column; gap:.3rem; }
  .suivi-dl-row { display:flex; gap:.75rem; font-size:.76rem; padding:.3rem 0; border-bottom:1px solid rgba(255,255,255,.04); }
  .suivi-dl-row:last-child { border-bottom:none; }
  .suivi-dl-key { font-family:'Barlow Condensed',sans-serif; font-size:.62rem; font-weight:700; letter-spacing:.08em; text-transform:uppercase; color:rgba(255,255,255,.25); min-width:70px; flex-shrink:0; padding-top:1px; }
  .suivi-dl-val { color:rgba(255,255,255,.7); line-height:1.4; }
  .suivi-card-foot { padding:.7rem 1.1rem; border-top:1px solid rgba(255,255,255,.05); display:flex; gap:.4rem; }

  /* ── EMPTY ────────────────────────────────── */
  .mec-empty { padding:3rem; text-align:center; border:1px dashed rgba(255,255,255,.07); border-radius:8px; }
  .mec-empty p { font-size:.82rem; color:rgba(255,255,255,.2); margin-top:.4rem; }

  /* ── ALERTS ───────────────────────────────── */
  .mec-alert { display:flex; align-items:center; gap:.65rem; font-family:'Barlow Condensed',sans-serif; font-size:.8rem; font-weight:700; letter-spacing:.04em; text-transform:uppercase; padding:.7rem .95rem; border-radius:6px; border:1px solid; margin-bottom:1.25rem; }
  .mec-alert-ok  { background:rgba(74,222,128,.08);  border-color:rgba(74,222,128,.2);  color:#4ade80; }
  .mec-alert-err { background:rgba(248,113,113,.08); border-color:rgba(248,113,113,.2); color:#f87171; }

  @media(max-width:900px){
    .mec-root { grid-template-columns:1fr; }
    .mec-sidebar { display:none; }
    .mec-content { padding:1.25rem; }
    .mec-stats { grid-template-columns:1fr 1fr; }
    .suivi-layout { grid-template-columns:1fr; }
    .rdv-card-inner { grid-template-columns:1fr auto; }
  }
  </style>
</head>
<body>

<div class="mec-root">

  <!-- ══ SIDEBAR ═══════════════════════════════════════════ -->
  <aside class="mec-sidebar">

    <a href="espace_mecano.php" class="mec-logo">
      Méca<span>Speed</span>
      <small>Espace mécanicien</small>
    </a>

    <nav class="mec-nav">
      <div class="mec-group">
        <span class="mec-section-label">Tableau de bord</span>

        <button class="mec-link active" onclick="showPanel('overview',this)">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>
          Vue d'ensemble
        </button>

        <button class="mec-link" onclick="showPanel('rdv',this)">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
          Mes rendez-vous
          <?php if($nb_pending>0): ?>
            <span class="mec-badge mb-amber"><?= $nb_pending ?></span>
          <?php endif; ?>
        </button>

        <button class="mec-link" onclick="showPanel('suivi',this)">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
          Créer un suivi
        </button>

        <button class="mec-link" onclick="showPanel('historique',this)">
          <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
          Historique suivis
          <?php if(count($suivis)>0): ?>
            <span class="mec-badge mb-blue"><?= count($suivis) ?></span>
          <?php endif; ?>
        </button>
      </div>
    </nav>

    <div class="mec-bottom">
      <div class="mec-profile">
        <div class="mec-avatar"><?= $initiales ?></div>
        <div>
          <div class="mec-profile-name"><?= htmlspecialchars($nom_mec) ?></div>
          <div class="mec-profile-role">Mécanicien</div>
        </div>
      </div>
      <a href="logout.php" class="mec-logout">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        Déconnexion
      </a>
    </div>

  </aside>

  <!-- ══ MAIN ══════════════════════════════════════════════ -->
  <main class="mec-main" id="contenu-principal">

    <div class="mec-topbar">
      <div>
        <div class="mec-topbar-sub" id="tb-sub">Atelier</div>
        <div class="mec-topbar-title" id="tb-title">Vue d'ensemble</div>
      </div>
      <div style="display:flex;gap:.5rem;align-items:center">
        <?php if(isset($_GET['suivi'])): ?>
          <div class="mec-alert mec-alert-ok" style="margin:0;padding:.38rem .85rem">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
            Suivi enregistré
          </div>
        <?php endif; ?>
        <?php if(isset($_GET['erreur'])): ?>
          <div class="mec-alert mec-alert-err" style="margin:0;padding:.38rem .85rem">
            <?= htmlspecialchars($_GET['erreur']) ?>
          </div>
        <?php endif; ?>
        <span style="font-family:'Barlow Condensed',sans-serif;font-size:.7rem;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:rgba(255,255,255,.2)"><?= date('d/m/Y') ?></span>
      </div>
    </div>

    <div class="mec-content">

      <!-- ══ PANEL : VUE D'ENSEMBLE ══ -->
      <div class="mec-panel active" id="panel-overview">

        <div class="mec-stats">
          <div class="msc">
            <span class="msc-label">RDV assignés</span>
            <span class="msc-val"><?= count($rdvs) ?></span>
            <span class="msc-sub">au total</span>
          </div>
          <div class="msc">
            <span class="msc-label">En attente</span>
            <span class="msc-val amber"><?= $nb_pending ?></span>
            <span class="msc-sub">à traiter</span>
          </div>
          <div class="msc">
            <span class="msc-label">Confirmés</span>
            <span class="msc-val green"><?= $nb_confirmed ?></span>
            <span class="msc-sub">rendez-vous</span>
          </div>
          <div class="msc">
            <span class="msc-label">Suivis créés</span>
            <span class="msc-val blue"><?= count($suivis) ?></span>
            <span class="msc-sub">interventions</span>
          </div>
        </div>

        <!-- Prochains RDV -->
        <div class="sec-hd">
          <div>
            <div class="sec-hd-title">Prochains rendez-vous</div>
            <div class="sec-hd-sub">À venir · en attente de confirmation</div>
          </div>
          <button class="ma ma-suivi" onclick="showPanel('rdv',document.querySelectorAll('.mec-link')[1])">Voir tous →</button>
        </div>

        <?php
          $prochains = array_filter($rdvs, fn($r) => $r['statut'] !== 'cancelled' && $r['date_rdv'] >= $today);
          $prochains = array_slice(array_values($prochains), 0, 4);
        ?>
        <?php if(empty($prochains)): ?>
          <div class="mec-empty">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.15)" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
            <p>Aucun rendez-vous à venir.</p>
          </div>
        <?php else: ?>
          <div class="rdv-stack">
            <?php foreach($prochains as $r):
              $immat  = $r['vehicule_immat'] ?: $r['vehicule'];
              $modele = $r['vehicule_modele'] ?: $r['vehicule'];
              $cls    = match($r['statut']){'confirmed'=>'is-confirmed','cancelled'=>'is-cancelled',default=>'is-pending'};
            ?>
              <div class="rdv-card <?= $cls ?>">
                <div class="rdv-card-inner">
                  <div class="rdv-date-block">
                    <span class="rdv-day"><?= date('d',strtotime($r['date_rdv'])) ?></span>
                    <span class="rdv-month"><?= $mois_fr[intval(date('n',strtotime($r['date_rdv'])))] ?></span>
                  </div>
                  <div class="rdv-info">
                    <div class="rdv-service"><?= htmlspecialchars($r['service']??'Intervention') ?></div>
                    <div class="rdv-meta">
                      <span class="rdv-client-name"><?= htmlspecialchars($r['nom_client']) ?></span>
                      <span><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg><?= substr($r['heure_rdv'],0,5) ?></span>
                      <?php if($modele): ?><span><?= htmlspecialchars($modele) ?> · <span style="color:#1a6bbd"><?= htmlspecialchars($immat) ?></span></span><?php endif; ?>
                    </div>
                  </div>
                  <div class="rdv-right">
                    <span class="<?= sc($r['statut']) ?>"><?= sl($r['statut']) ?></span>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <!-- Suivis récents -->
        <?php if(!empty($suivis)): ?>
          <div class="sec-hd" style="margin-top:2rem">
            <div>
              <div class="sec-hd-title">Suivis récents</div>
              <div class="sec-hd-sub">Dernières fiches créées</div>
            </div>
            <button class="ma ma-suivi" onclick="showPanel('historique',document.querySelectorAll('.mec-link')[3])">Voir tous →</button>
          </div>
          <div class="suivi-grid">
            <?php foreach(array_slice($suivis,0,3) as $sv): ?>
              <div class="suivi-card">
                <div class="suivi-card-head">
                  <div>
                    <div class="suivi-car"><?= htmlspecialchars($sv['modele']) ?></div>
                    <div class="suivi-immat"><?= htmlspecialchars($sv['immatriculation']) ?></div>
                  </div>
                  <span class="ms-<?= intval($sv['progression'])>=100?'ok':'wait' ?>" style="font-size:.6rem"><?= intval($sv['progression']) ?>%</span>
                </div>
                <div class="suivi-body">
                  <div class="suivi-prog-label">
                    <span class="suivi-prog-text">Progression</span>
                    <span class="suivi-prog-pct"><?= intval($sv['progression']) ?>%</span>
                  </div>
                  <div class="prog-track"><div class="prog-fill <?= intval($sv['progression'])>=100?'done':'' ?>" style="width:<?= intval($sv['progression']) ?>%"></div></div>
                  <div class="suivi-dl-row" style="border:none;padding:0;font-size:.76rem;color:rgba(255,255,255,.5)"><?= htmlspecialchars($sv['statut']) ?></div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

      </div>

      <!-- ══ PANEL : MES RDV ══ -->
      <div class="mec-panel" id="panel-rdv">
        <div class="sec-hd">
          <div>
            <div class="sec-hd-title">Mes rendez-vous</div>
            <div class="sec-hd-sub"><?= count($rdvs) ?> assignés · <?= $nb_pending ?> en attente</div>
          </div>
        </div>

        <?php if(empty($rdvs)): ?>
          <div class="mec-empty">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.15)" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="2"/></svg>
            <p>Aucun rendez-vous assigné pour le moment.</p>
          </div>
        <?php else: ?>
          <div class="rdv-stack">
            <?php foreach($rdvs as $r):
              $immat  = $r['vehicule_immat'] ?: $r['vehicule'];
              $modele = $r['vehicule_modele'] ?: $r['vehicule'];
              $cls    = match($r['statut']){'confirmed'=>'is-confirmed','cancelled'=>'is-cancelled',default=>'is-pending'};
              $args   = [intval($r['client_id']), $immat, $modele, intval($r['id'])];
            ?>
              <div class="rdv-card <?= $cls ?>">
                <div class="rdv-card-inner">
                  <div class="rdv-date-block">
                    <span class="rdv-day"><?= date('d',strtotime($r['date_rdv'])) ?></span>
                    <span class="rdv-month"><?= $mois_fr[intval(date('n',strtotime($r['date_rdv'])))] ?></span>
                  </div>
                  <div class="rdv-info">
                    <div class="rdv-service"><?= htmlspecialchars($r['service']??'Intervention') ?></div>
                    <div class="rdv-meta">
                      <span class="rdv-client-name"><?= htmlspecialchars($r['nom_client']) ?></span>
                      <span><svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg><?= substr($r['heure_rdv'],0,5) ?></span>
                      <span><?= htmlspecialchars($r['nom_garage']??'Garage') ?></span>
                      <?php if($modele): ?><span><?= htmlspecialchars($modele) ?> · <span style="color:#1a6bbd"><?= htmlspecialchars($immat) ?></span></span><?php endif; ?>
                    </div>
                  </div>
                  <div class="rdv-right">
                    <span class="<?= sc($r['statut']) ?>"><?= sl($r['statut']) ?></span>
                    <div class="rdv-actions">
                      <?php if($r['statut']==='pending'): ?>
                        <form method="POST" action="mettre_a_jour_statut.php">
                          <?= csrf_field() ?>
                          <input type="hidden" name="rdv_id" value="<?= $r['id'] ?>">
                          <input type="hidden" name="nouveau_statut" value="confirmed">
                          <button type="submit" class="ma ma-confirm">✓ Confirmer</button>
                        </form>
                        <form method="POST" action="mettre_a_jour_statut.php">
                          <?= csrf_field() ?>
                          <input type="hidden" name="rdv_id" value="<?= $r['id'] ?>">
                          <input type="hidden" name="nouveau_statut" value="cancelled">
                          <button type="submit" class="ma ma-cancel">✕ Annuler</button>
                        </form>
                      <?php endif; ?>
                      <?php if($r['statut']!=='cancelled'): ?>
                        <button type="button" class="ma ma-suivi"
                          onclick='prefillSuivi(<?= htmlspecialchars(json_encode($args),ENT_QUOTES) ?>)'>
                          + Suivi
                        </button>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
                <?php if(!empty($r['notes'])): ?>
                  <div class="rdv-notes"><span>Notes client</span><?= htmlspecialchars($r['notes']) ?></div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

      <!-- ══ PANEL : CRÉER SUIVI ══ -->
      <div class="mec-panel" id="panel-suivi">
        <div class="sec-hd">
          <div>
            <div class="sec-hd-title">Créer / mettre à jour un suivi</div>
            <div class="sec-hd-sub">Sélectionnez un RDV pour pré-remplir automatiquement</div>
          </div>
        </div>

        <div class="suivi-layout">
          <!-- Form -->
          <div class="suivi-form-panel">
            <span class="suivi-form-title">Fiche d'intervention</span>

            <form method="POST" action="mettre_a_jour_suivi.php" id="form-suivi">
              <?= csrf_field() ?>
              <input type="hidden" name="rdv_id"         id="f_rdv_id" value="">
              <input type="hidden" name="utilisateur_id" id="f_uid"    value="">

              <!-- Pré-remplissage -->
              <div class="f-field" style="margin-bottom:1.5rem">
                <label>Pré-remplir depuis un RDV</label>
                <select class="prefill-select" id="sel_client" onchange="selectionnerClient(this)">
                  <option value="">Choisir un rendez-vous…</option>
                  <?php foreach($rdvs as $r): if($r['statut']==='cancelled') continue;
                    $im = $r['vehicule_immat']?:$r['vehicule'];
                    $mo = $r['vehicule_modele']?:$r['vehicule'];
                  ?>
                    <option value="<?= intval($r['client_id']) ?>|<?= htmlspecialchars($im,ENT_QUOTES) ?>|<?= htmlspecialchars($mo,ENT_QUOTES) ?>|<?= intval($r['id']) ?>">
                      <?= htmlspecialchars($r['nom_client']) ?> — <?= htmlspecialchars($mo) ?> (<?= date('d/m',strtotime($r['date_rdv'])) ?>)
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="f-row">
                <div class="f-field">
                  <label>Immatriculation</label>
                  <input type="text" name="immatriculation" id="f_immat" placeholder="16600-111-16" required>
                </div>
                <div class="f-field">
                  <label>Modèle</label>
                  <input type="text" name="modele" id="f_modele" placeholder="Renault Clio" required>
                </div>
              </div>

              <div class="f-field">
                <label>Statut de l'intervention</label>
                <input type="text" name="statut" id="f_statut" placeholder="Ex: Diagnostic en cours" required>
              </div>

              <div class="f-field">
                <label>Progression — <span id="prog-val" style="color:#1a6bbd;font-weight:800">0%</span></label>
                <div class="range-wrap">
                  <input type="range" name="progression" id="f_prog" min="0" max="100" value="0"
                    oninput="document.getElementById('prog-val').textContent=this.value+'%'">
                  <span class="range-val" id="prog-display">0%</span>
                </div>
              </div>

              <div class="f-field">
                <label>ETA (heure estimée)</label>
                <input type="text" name="eta" id="f_eta" placeholder="Ex: Aujourd'hui à 17h">
              </div>

              <div class="f-field">
                <label>Note pour le client</label>
                <textarea name="note_mecanicien" id="f_note" rows="3" placeholder="Message visible par le client…"></textarea>
              </div>

              <button type="submit" class="btn-submit-form">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><polyline points="17 21 17 13 7 13 7 21"/><polyline points="7 3 7 8 15 8"/></svg>
                Enregistrer le suivi
              </button>
            </form>
          </div>

          <!-- Info panel -->
          <div>
            <div style="background:#101010;border:1px solid rgba(255,255,255,.07);border-radius:10px;padding:1.5rem">
              <span class="suivi-form-title">Comment ça marche</span>
              <div style="display:flex;flex-direction:column;gap:1rem">
                <?php foreach([
                  ['1','Sélectionnez un RDV','Le formulaire se remplit automatiquement.'],
                  ['2','Mettez à jour le statut','Décrivez l\'avancement de l\'intervention.'],
                  ['3','Ajustez la progression','Le client voit la barre en temps réel.'],
                  ['4','Ajoutez une note','Message personnalisé visible par le client.'],
                ] as [$n,$t,$d]): ?>
                  <div style="display:flex;gap:.85rem;align-items:flex-start">
                    <div style="width:26px;height:26px;border-radius:6px;background:rgba(26,107,189,.15);border:1px solid rgba(26,107,189,.25);display:flex;align-items:center;justify-content:center;flex-shrink:0;font-family:'Barlow Condensed',sans-serif;font-size:.78rem;font-weight:900;color:#1a6bbd"><?= $n ?></div>
                    <div>
                      <div style="font-family:'Barlow Condensed',sans-serif;font-size:.78rem;font-weight:800;text-transform:uppercase;letter-spacing:.04em;color:#fff;margin-bottom:2px"><?= $t ?></div>
                      <div style="font-size:.75rem;color:rgba(255,255,255,.32);line-height:1.4"><?= $d ?></div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ══ PANEL : HISTORIQUE ══ -->
      <div class="mec-panel" id="panel-historique">
        <div class="sec-hd">
          <div>
            <div class="sec-hd-title">Historique des suivis</div>
            <div class="sec-hd-sub"><?= count($suivis) ?> fiche<?= count($suivis)>1?'s':'' ?> créée<?= count($suivis)>1?'s':'' ?></div>
          </div>
        </div>

        <?php if(empty($suivis)): ?>
          <div class="mec-empty">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="rgba(255,255,255,.15)" stroke-width="1.5"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
            <p>Aucune fiche de suivi créée pour le moment.</p>
          </div>
        <?php else: ?>
          <div class="suivi-grid">
            <?php foreach($suivis as $sv):
              $prog = intval($sv['progression']);
              $args = [intval($sv['utilisateur_id']),$sv['immatriculation'],$sv['modele'],$prog,$sv['statut'],$sv['eta']??'',$sv['note_mecanicien']??''];
            ?>
              <div class="suivi-card">
                <div class="suivi-card-head">
                  <div>
                    <div class="suivi-car"><?= htmlspecialchars($sv['modele']) ?></div>
                    <div class="suivi-immat"><?= htmlspecialchars($sv['immatriculation']) ?></div>
                  </div>
                  <span class="ms-<?= $prog>=100?'ok':'wait' ?>"><?= $prog ?>%</span>
                </div>
                <div class="suivi-body">
                  <div class="suivi-prog-label">
                    <span class="suivi-prog-text">Progression</span>
                    <span class="suivi-prog-pct"><?= $prog ?>%</span>
                  </div>
                  <div class="prog-track"><div class="prog-fill <?= $prog>=100?'done':'' ?>" style="width:<?= $prog ?>%"></div></div>
                  <div class="suivi-dl">
                    <div class="suivi-dl-row"><span class="suivi-dl-key">Client</span><span class="suivi-dl-val"><?= htmlspecialchars($sv['nom_client']) ?></span></div>
                    <div class="suivi-dl-row"><span class="suivi-dl-key">Statut</span><span class="suivi-dl-val"><?= htmlspecialchars($sv['statut']) ?></span></div>
                    <div class="suivi-dl-row"><span class="suivi-dl-key">ETA</span><span class="suivi-dl-val"><?= htmlspecialchars($sv['eta']??'—') ?></span></div>
                    <div class="suivi-dl-row"><span class="suivi-dl-key">Réf.</span><span class="suivi-dl-val" style="color:#1a6bbd"><?= htmlspecialchars($sv['reference']) ?></span></div>
                    <?php if(!empty($sv['note_mecanicien'])): ?>
                      <div class="suivi-dl-row"><span class="suivi-dl-key">Note</span><span class="suivi-dl-val"><?= nl2br(htmlspecialchars($sv['note_mecanicien'])) ?></span></div>
                    <?php endif; ?>
                  </div>
                </div>
                <div class="suivi-card-foot">
                  <button type="button" class="ma ma-suivi"
                    onclick='modifierSuivi(<?= htmlspecialchars(json_encode($args),ENT_QUOTES) ?>)'>
                    Modifier
                  </button>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>

    </div><!-- /mec-content -->
  </main>

</div>

<script>
const PANELS = {
  overview:   { sub:'Atelier',        title:'Vue d\'ensemble' },
  rdv:        { sub:'Planning',       title:'Mes rendez-vous' },
  suivi:      { sub:'Intervention',   title:'Créer un suivi'  },
  historique: { sub:'Fiches',         title:'Historique suivis'},
};

function showPanel(id, btn) {
  document.querySelectorAll('.mec-panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.mec-link').forEach(b => b.classList.remove('active'));
  document.getElementById('panel-' + id).classList.add('active');
  if (btn) btn.classList.add('active');
  const p = PANELS[id] || {};
  document.getElementById('tb-sub').textContent   = p.sub   || '';
  document.getElementById('tb-title').textContent = p.title || '';
  history.replaceState(null,'','#'+id);
}

function prefillSuivi(args) {
  const [uid, immat, modele, rdv_id] = args;
  document.getElementById('f_uid').value    = uid;
  document.getElementById('f_immat').value  = immat;
  document.getElementById('f_modele').value = modele;
  document.getElementById('f_rdv_id').value = rdv_id;
  showPanel('suivi', document.querySelectorAll('.mec-link')[2]);
}

function modifierSuivi(args) {
  const [uid, immat, modele, prog, statut, eta, note] = args;
  document.getElementById('f_uid').value    = uid;
  document.getElementById('f_immat').value  = immat;
  document.getElementById('f_modele').value = modele;
  document.getElementById('f_prog').value   = prog;
  document.getElementById('prog-val').textContent     = prog + '%';
  document.getElementById('prog-display').textContent = prog + '%';
  document.getElementById('f_statut').value = statut;
  document.getElementById('f_eta').value    = eta;
  document.getElementById('f_note').value   = note;
  showPanel('suivi', document.querySelectorAll('.mec-link')[2]);
}

function selectionnerClient(sel) {
  const parts = sel.value.split('|');
  if (parts.length < 4) return;
  document.getElementById('f_uid').value    = parts[0];
  document.getElementById('f_immat').value  = parts[1];
  document.getElementById('f_modele').value = parts[2];
  document.getElementById('f_rdv_id').value = parts[3];
}

// Restore hash
(function(){
  const h = location.hash.replace('#','');
  const map = { overview:0, rdv:1, suivi:2, historique:3 };
  if (h && map[h] !== undefined) {
    showPanel(h, document.querySelectorAll('.mec-link')[map[h]]);
  }
})();
</script>
</body>
</html>
