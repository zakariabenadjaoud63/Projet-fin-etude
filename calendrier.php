<?php
session_start();
require_once 'connexion_bd.php';
require_once 'includes/security.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
if (($_SESSION['role'] ?? 'client') !== 'client') {
  header('Location: ' . ($_SESSION['role'] === 'mecano' ? 'espace_mecano.php' : 'espace_admin.php'));
  exit();
}

$stmt_v = $pdo->prepare("SELECT id_v, marque, modele, immatriculation FROM vehicules WHERE id_proprietaire = :id");
$stmt_v->execute([':id' => $_SESSION['user_id']]);
$mes_voitures = $stmt_v->fetchAll();

$mois  = isset($_GET['mois'])  ? intval($_GET['mois'])  : intval(date('m'));
$annee = isset($_GET['annee']) ? intval($_GET['annee']) : intval(date('Y'));
if ($mois < 1)  { $mois = 12; $annee--; }
if ($mois > 12) { $mois = 1;  $annee++; }

$premier_jour   = mktime(0, 0, 0, $mois, 1, $annee);
$nombre_jours   = date('t', $premier_jour);
$jour_sem_debut = date('N', $premier_jour);
$mois_fr = [1=>"Janvier",2=>"Février",3=>"Mars",4=>"Avril",5=>"Mai",6=>"Juin",
            7=>"Juillet",8=>"Août",9=>"Septembre",10=>"Octobre",11=>"Novembre",12=>"Décembre"];

$mp = $mois - 1; $ap = $annee; if ($mp < 1) { $mp = 12; $ap--; }
$ms = $mois + 1; $as = $annee; if ($ms > 12) { $ms = 1; $as++; }

// Tous les créneaux pris pour le mois (date → garage_id → [heures])
$debut_mois = sprintf('%04d-%02d-01', $annee, $mois);
$fin_mois   = sprintf('%04d-%02d-%02d', $annee, $mois, date('t', mktime(0,0,0,$mois,1,$annee)));

$stmt_slots = $pdo->prepare("
    SELECT date_rdv, garage_id, TIME_FORMAT(heure_rdv,'%H:%i') as heure
    FROM rendez_vous
    WHERE date_rdv BETWEEN :debut AND :fin
      AND statut <> 'cancelled'
    ORDER BY date_rdv, heure_rdv
");
$stmt_slots->execute([':debut' => $debut_mois, ':fin' => $fin_mois]);

$HEURES_DISPO = ['08:00','09:00','10:00','11:00','13:00','14:00','15:00','16:00'];
$slots_pris   = [];   // [date][garage_id] = [heures prises]
$dates_pleines = [];  // dates où TOUS les créneaux sont pris pour TOUS les garages

foreach ($stmt_slots->fetchAll() as $row) {
    $slots_pris[$row['date_rdv']][$row['garage_id']][] = $row['heure'];
}

// Charger les garages pour savoir combien il y en a
$garages = $pdo->query("SELECT id, nom FROM garages ORDER BY id")->fetchAll();
$nb_garages = count($garages);

// Date complète = tous les créneaux pris dans au moins 1 garage → on laisse la logique côté JS
// Date saturée côté calendrier = TOUS les créneaux pris pour TOUS les garages
foreach ($slots_pris as $date => $par_garage) {
    $all_full = true;
    foreach ($garages as $g) {
        $pris = $par_garage[$g['id']] ?? [];
        if (count(array_diff($HEURES_DISPO, $pris)) > 0) { $all_full = false; break; }
    }
    if ($all_full) $dates_pleines[] = $date;
}

// Ancienne logique de saturation (5 RDV) pour compatibilité
$stmt_sat = $pdo->prepare("SELECT date_rdv FROM rendez_vous WHERE statut <> 'cancelled' GROUP BY date_rdv HAVING COUNT(*) >= 8");
$stmt_sat->execute();
$dates_saturees = array_unique(array_merge(
    array_column($stmt_sat->fetchAll(), 'date_rdv'),
    $dates_pleines
));

$success = isset($_GET['success']);
$activePage = 'rdv';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MécaSpeed — Rendez-vous</title>
</head>
<body>
<?php require __DIR__ . '/includes/public-nav.php'; ?>

<main id="contenu-principal">

  <!-- HERO -->
  <section class="page-hero" aria-labelledby="titre-rdv">
    <div class="container page-hero-inner">
      <span class="eyebrow">Réservation atelier</span>
      <h1 id="titre-rdv">Prendre <em>rendez-vous</em></h1>
      <p class="hero-lead">Choisissez une date disponible sur le calendrier puis complétez les informations de l'intervention.</p>

      <?php if ($success): ?>
        <div class="alert alert-success" role="status">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
          Rendez-vous enregistré avec succès.
        </div>
      <?php endif; ?>
      <?php if (isset($_GET['erreur']) && $_GET['erreur'] === 'champs_vides'): ?>
        <div class="alert alert-error" role="alert">Veuillez remplir tous les champs obligatoires.</div>
      <?php endif; ?>
      <?php if (isset($_GET['erreur']) && $_GET['erreur'] === 'creneau_pris'): ?>
        <div class="alert alert-error" role="alert">Ce créneau est déjà réservé. Choisissez une autre heure.</div>
      <?php endif; ?>
    </div>
  </section>

  <!-- CALENDRIER + FORMULAIRE -->
  <section class="ms-section" aria-label="Prise de rendez-vous">
    <div class="container">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:2.5rem;align-items:start">

        <!-- CALENDRIER -->
        <div>
          <div class="section-head">
            <span class="eyebrow">Disponibilités</span>
            <h2 style="font-size:1.3rem">Choisir une date</h2>
          </div>

          <div class="cal-panel">
            <div class="cal-header">
              <span class="cal-title"><?= $mois_fr[$mois] . ' ' . $annee ?></span>
              <div class="cal-nav-btns">
                <a href="calendrier.php?mois=<?= $mp ?>&annee=<?= $ap ?>" class="cal-nav-btn" aria-label="Mois précédent">‹</a>
                <a href="calendrier.php?mois=<?= $ms ?>&annee=<?= $as ?>" class="cal-nav-btn" aria-label="Mois suivant">›</a>
              </div>
            </div>
            <div class="cal-body">
              <div class="cal-grid" role="grid" aria-label="Calendrier du mois">
                <?php foreach (['L','M','M','J','V','S','D'] as $j): ?>
                  <div class="cal-weekday" role="columnheader"><?= $j ?></div>
                <?php endforeach; ?>

                <?php for ($i = 1; $i < $jour_sem_debut; $i++): ?>
                  <div class="cal-day empty" role="gridcell"></div>
                <?php endfor; ?>

                <?php
                $today = date('Y-m-d');
                for ($j = 1; $j <= $nombre_jours; $j++):
                  $date = sprintf('%04d-%02d-%02d', $annee, $mois, $j);
                  $is_today = $date === $today;
                  $disabled = $date < $today || in_array($date, $dates_saturees);
                ?>
                  <button
                    type="button"
                    class="cal-day <?= $disabled ? 'unavailable' : 'available' ?> <?= $is_today ? 'today-marker' : '' ?>"
                    <?= $disabled ? 'disabled' : "onclick=\"pickDate(this,'$date')\"" ?>
                    data-date="<?= $date ?>"
                    role="gridcell"
                    aria-label="<?= $j ?> <?= $mois_fr[$mois] ?> <?= $annee ?><?= $disabled ? ', indisponible' : '' ?>"
                  ><?= $j ?></button>
                <?php endfor; ?>
              </div>

              <div class="cal-legend">
                <span class="cal-legend-item"><span class="cal-legend-dot" style="background:var(--blue)"></span>Disponible</span>
                <span class="cal-legend-item"><span class="cal-legend-dot" style="background:rgba(255,255,255,.15)"></span>Indisponible</span>
              </div>
            </div>
          </div>
        </div>

        <!-- FORMULAIRE -->
        <div>
          <div class="section-head">
            <span class="eyebrow">Détails</span>
            <h2 style="font-size:1.3rem">Informations du rendez-vous</h2>
          </div>

          <?php if (empty($mes_voitures)): ?>
            <div class="alert alert-warning" role="alert">
              <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
              Vous n'avez aucun véhicule enregistré. <a href="vehicule.php" style="color:var(--blue);text-decoration:underline">Ajouter un véhicule</a>
            </div>
          <?php endif; ?>

          <div style="background:var(--surface-2);border:1px solid var(--border);border-radius:12px;padding:1.75rem">

            <!-- Date sélectionnée -->
            <div id="date-badge" style="
              background:var(--blue-pale);border:1px solid var(--blue-border);
              border-radius:6px;padding:0.75rem 1rem;margin-bottom:1.25rem;
              font-family:'Barlow Condensed',sans-serif;font-size:0.82rem;font-weight:700;
              letter-spacing:0.06em;text-transform:uppercase;color:var(--text-muted)">
              Sélectionnez une date sur le calendrier
            </div>

            <form action="traitement_rendezvous.php" method="POST">
              <?= csrf_field() ?>
              <input type="hidden" name="date_rdv"      id="input_date"     required>
              <input type="hidden" name="vehicule"       id="input_vehicule">
              <input type="hidden" name="id_vl"          id="input_id_vl">

              <fieldset>
                <legend>Véhicule et service</legend>
                <div class="field">
                  <label for="sel_vehicule">Votre véhicule</label>
                  <select id="sel_vehicule" required <?= empty($mes_voitures) ? 'disabled' : '' ?>>
                    <option value="">Choisir un véhicule</option>
                    <?php foreach ($mes_voitures as $v): ?>
                      <option value="<?= $v['id_v'] ?>"
                        data-text="<?= htmlspecialchars(strtoupper($v['marque']) . ' ' . $v['modele'] . ' (' . $v['immatriculation'] . ')') ?>">
                        <?= htmlspecialchars(strtoupper($v['marque']) . ' ' . $v['modele'] . ' (' . $v['immatriculation'] . ')') ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="field">
                  <label for="service">Type de service</label>
                  <select name="service" id="service" required>
                    <option value="">Choisir le service</option>
                    <option value="Vidange">Vidange et filtres</option>
                    <option value="Freinage">Révision freinage</option>
                    <option value="Diagnostic">Diagnostic électronique</option>
                    <option value="Climatisation">Climatisation</option>
                    <option value="Distribution">Courroie de distribution</option>
                    <option value="Pneus">Remplacement pneus</option>
                    <option value="Carrosserie">Carrosserie</option>
                    <option value="Autre">Autre intervention</option>
                  </select>
                </div>
              </fieldset>

              <fieldset>
                <legend>Garage et horaire</legend>
                <div class="field">
                  <label for="sel_garage">Garage</label>
                  <select name="garage_id" id="sel_garage" required>
                    <option value="">Choisir un garage</option>
                    <?php foreach ($garages as $g): ?>
                      <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['nom']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="field">
                  <label for="sel_mecano">Mécanicien</label>
                  <select name="mecanicien_id" id="sel_mecano">
                    <option value="">Choisir un garage d'abord</option>
                  </select>
                </div>
                <div class="field">
                  <label for="heure_rdv" style="display:flex;align-items:center;justify-content:space-between">
                    Heure
                    <span id="slots-badge" style="display:none;font-size:.65rem;font-weight:700;letter-spacing:.07em;text-transform:uppercase;padding:.12rem .5rem;border-radius:100px;background:rgba(74,222,128,.1);border:1px solid rgba(74,222,128,.25)"></span>
                  </label>
                  <select name="heure_rdv" id="heure_rdv" required>
                    <option value="08:00">08:00</option>
                    <option value="09:00">09:00</option>
                    <option value="10:00">10:00</option>
                    <option value="11:00">11:00</option>
                    <option value="13:00">13:00</option>
                    <option value="14:00">14:00</option>
                    <option value="15:00">15:00</option>
                    <option value="16:00">16:00</option>
                  </select>
                </div>
              </fieldset>

              <div class="field">
                <label for="notes">Notes / symptômes</label>
                <textarea name="notes" id="notes" rows="3" placeholder="Ex : bruit au freinage, voyant allumé..."></textarea>
              </div>

              <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                Confirmer le rendez-vous
              </button>
            </form>
          </div>
        </div>

      </div>
    </div>
  </section>

</main>

<?php require __DIR__ . '/includes/page-footer.php'; ?>

<script>
// Créneaux pris par date et garage (injectés depuis PHP)
const SLOTS_PRIS = <?= json_encode($slots_pris) ?>;
const HEURES_ALL = ['08:00','09:00','10:00','11:00','13:00','14:00','15:00','16:00'];

let selectedDate   = '';
let selectedGarage = '';

function updateHeures() {
  const sel = document.getElementById('heure_rdv');
  const pris = (SLOTS_PRIS[selectedDate] && selectedGarage)
    ? (SLOTS_PRIS[selectedDate][selectedGarage] || [])
    : [];

  // Sauvegarder la valeur actuelle
  const valActuelle = sel.value;
  sel.innerHTML = '';

  let premierDispo = null;
  HEURES_ALL.forEach(h => {
    const taken = pris.includes(h);
    const opt   = document.createElement('option');
    opt.value    = h;
    opt.disabled = taken;
    opt.textContent = taken ? h + ' — Indisponible' : h;
    if (taken) opt.style.color = 'rgba(248,113,113,.6)';
    sel.appendChild(opt);
    if (!taken && !premierDispo) premierDispo = h;
  });

  // Remettre la valeur ou le premier dispo
  if (!pris.includes(valActuelle)) {
    sel.value = valActuelle;
  } else if (premierDispo) {
    sel.value = premierDispo;
  }

  // Afficher un badge si des créneaux sont pris
  const nbPris   = pris.length;
  const nbRestants = HEURES_ALL.length - nbPris;
  const badgeEl  = document.getElementById('slots-badge');
  if (badgeEl && selectedDate && selectedGarage) {
    if (nbPris > 0) {
      badgeEl.textContent = nbRestants + ' créneau' + (nbRestants > 1 ? 'x' : '') + ' disponible' + (nbRestants > 1 ? 's' : '');
      badgeEl.style.display = 'inline-block';
      badgeEl.style.color   = nbRestants <= 2 ? '#f87171' : '#4ade80';
    } else {
      badgeEl.style.display = 'none';
    }
  }
}

function pickDate(el, date) {
  document.querySelectorAll('.cal-day.selected').forEach(d => d.classList.remove('selected'));
  el.classList.add('selected');
  selectedDate = date;
  document.getElementById('input_date').value = date;

  const [y, m, j] = date.split('-');
  const mois = ['','Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
  document.getElementById('date-badge').innerHTML =
    '<span style="color:var(--blue);margin-right:.4rem">✓</span> Date sélectionnée : <strong style="color:#fff">' + j + ' ' + mois[parseInt(m)] + ' ' + y + '</strong>';
  document.getElementById('date-badge').style.color = 'var(--text-sub)';

  updateHeures();
}

document.getElementById('sel_vehicule').addEventListener('change', function() {
  document.getElementById('input_id_vl').value    = this.value;
  document.getElementById('input_vehicule').value = this.options[this.selectedIndex].dataset.text || '';
});

document.getElementById('sel_garage').addEventListener('change', function() {
  selectedGarage = this.value;
  updateHeures();

  const sel = document.getElementById('sel_mecano');
  if (!this.value) { sel.innerHTML = '<option value="">Choisir un garage d\'abord</option>'; return; }
  sel.innerHTML = '<option value="">Chargement…</option>';
  fetch('get_mecano.php?garage_id=' + this.value)
    .then(r => r.json())
    .then(data => {
      sel.innerHTML = '<option value="">Aucune préférence</option>';
      (data.mecanos || []).forEach(m => {
        const o = document.createElement('option');
        o.value = m.id; o.textContent = m.nom; sel.appendChild(o);
      });
    })
    .catch(() => { sel.innerHTML = '<option value="">Erreur de chargement</option>'; });
});
</script>
</body>
</html>
