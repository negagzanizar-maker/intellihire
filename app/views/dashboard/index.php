<?php require ROOT . '/app/views/layouts/header.php'; ?>

<div class="page-header">
  <div>
    <?php
      $mois_fr = ['Janvier','Février','Mars','Avril','Mai','Juin','Juillet','Août','Septembre','Octobre','Novembre','Décembre'];
      $eyebrow = 'N° ' . str_pad((string)((int)date('m')), 2, '0', STR_PAD_LEFT)
               . ' — ' . $mois_fr[(int)date('n') - 1] . ' ' . date('Y');
    ?>
    <span class="page-eyebrow"><?= $eyebrow ?></span>
    <h1>Bonjour, <em><?= $prenom ?></em>.</h1>
    <p>Voici un aperçu calme de l'activité de recrutement aujourd'hui.</p>
  </div>
  <?php if (Auth::hasRole(['RECRUTEUR'])): ?>
  <div class="ph-actions">
    <a href="<?= BASE_URL ?>/index.php?url=offres/create" class="btn btn-primary">Nouvelle offre</a>
  </div>
  <?php endif; ?>
</div>

<?php if (!empty($ai_insights)): ?>
<section class="section ai-dashboard-section">
  <header class="section-h">
    <h2 class="section-t">Insights IA<small>Signaux prioritaires du pipeline</small></h2>
  </header>

  <div class="ai-insight-grid">
    <?php foreach ($ai_insights as $insight): ?>
      <article class="ai-insight-card ai-insight-<?= htmlspecialchars($insight['type'] ?? 'signal') ?>">
        <div class="ai-insight-metric"><?= htmlspecialchars((string)($insight['metric'] ?? '')) ?></div>
        <div>
          <h3><?= htmlspecialchars($insight['title'] ?? 'Insight') ?></h3>
          <p><?= htmlspecialchars($insight['body'] ?? '') ?></p>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; ?>

<!-- ── Vue d'ensemble ──────────────────────────────────────────── -->
<section class="section">
  <header class="section-h">
    <h2 class="section-t">Vue d'ensemble<small>Les chiffres clés du moment</small></h2>
  </header>

  <div class="metrics">
    <a class="metric" href="<?= BASE_URL ?>/index.php?url=candidatures">
      <div class="metric-num is-accent" data-target="<?= $stats_candidatures['total'] ?>">0</div>
      <div class="metric-lbl">Candidatures</div>
    </a>
    <a class="metric" href="<?= BASE_URL ?>/index.php?url=candidatures&statut=Recue">
      <div class="metric-num" data-target="<?= $stats_candidatures['recues'] ?>">0</div>
      <div class="metric-lbl">À traiter</div>
    </a>
    <a class="metric" href="<?= BASE_URL ?>/index.php?url=entretiens">
      <div class="metric-num" data-target="<?= $stats_entretiens['total'] ?>">0</div>
      <div class="metric-lbl">Entretiens</div>
    </a>
    <a class="metric" href="<?= BASE_URL ?>/index.php?url=candidatures&statut=Acceptee">
      <?php $taux = $stats_candidatures['total'] > 0 ? round($stats_candidatures['acceptees'] / $stats_candidatures['total'] * 100) : 0; ?>
      <div class="metric-num" data-target="<?= $stats_candidatures['acceptees'] ?>">0</div>
      <div class="metric-lbl">Acceptés</div>
      <div class="metric-meta"><?= $taux ?>% du total</div>
    </a>
    <a class="metric" href="<?= BASE_URL ?>/index.php?url=offres&statut=Publiee">
      <div class="metric-num" data-target="<?= $offres_publiees ?>">0</div>
      <div class="metric-lbl">Offres actives</div>
    </a>
    <div class="metric">
      <div class="metric-num" data-target="<?= $total_users ?>">0</div>
      <div class="metric-lbl">Utilisateurs</div>
    </div>
  </div>
</section>

<!-- ── Répartition ─────────────────────────────────────────────── -->
<section class="section">
  <header class="section-h">
    <h2 class="section-t">Répartition<small>Statuts et offres en cours</small></h2>
  </header>

  <div class="dash-grid">
    <div class="card">
      <div class="card-title">Statuts des candidatures</div>
      <div style="height:240px"><canvas id="chartDonut"></canvas></div>
    </div>

    <div class="card">
      <div class="card-title">Candidatures par offre</div>
      <div style="height:240px"><canvas id="chartBar"></canvas></div>
    </div>
  </div>
</section>

<!-- ── Évolution ───────────────────────────────────────────────── -->
<section class="section">
  <header class="section-h">
    <h2 class="section-t">Évolution<small>Douze derniers mois</small></h2>
  </header>

  <div class="card">
    <div style="height:260px"><canvas id="chartLine"></canvas></div>
  </div>
</section>

<!-- ── Prochains entretiens ────────────────────────────────────── -->
<?php if (!empty($entretiens_upcoming)): ?>
<section class="section">
  <header class="section-h">
    <h2 class="section-t">Prochains entretiens<small>Les rendez-vous à venir</small></h2>
    <a class="section-action" href="<?= BASE_URL ?>/index.php?url=entretiens">Tout voir</a>
  </header>

  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th style="width:120px">Date</th>
          <th>Candidat</th>
          <th>Poste</th>
          <th style="width:120px">Type</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($entretiens_upcoming as $e): ?>
        <tr data-href="<?= BASE_URL ?>/index.php?url=candidatures/detail/<?= $e['id_candidature'] ?>">
          <td class="num"><?= date('d/m H:i', strtotime($e['date_entretien'])) ?></td>
          <td><strong style="color:var(--text-primary)"><?= htmlspecialchars($e['prenom'] . ' ' . $e['nom']) ?></strong></td>
          <td><?= htmlspecialchars($e['titre_offre']) ?></td>
          <td><span class="badge badge-<?= strtolower($e['type_entretien']) === 'visio' ? 'candidat' : 'recruteur' ?>"><?= $e['type_entretien'] ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</section>
<?php endif; ?>

<!-- ── Dernières candidatures ──────────────────────────────────── -->
<section class="section">
  <header class="section-h">
    <h2 class="section-t">Dernières candidatures<small>Activité récente</small></h2>
    <a class="section-action" href="<?= BASE_URL ?>/index.php?url=candidatures">Tout voir</a>
  </header>

  <?php if (empty($dernieres_candidatures)): ?>
    <div class="empty-state"><p>Aucune candidature pour le moment.</p></div>
  <?php else: ?>
  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th style="width:90px">Réf.</th>
          <th>Candidat</th>
          <th>Offre</th>
          <th style="width:160px">Score</th>
          <th style="width:120px">Statut</th>
          <th style="width:110px">Date</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($dernieres_candidatures as $c): ?>
        <tr data-href="<?= BASE_URL ?>/index.php?url=candidatures/detail/<?= $c['id_candidature'] ?>">
          <td class="num">#<?= str_pad((string)$c['id_candidature'], 4, '0', STR_PAD_LEFT) ?></td>
          <td><strong style="color:var(--text-primary)"><?= htmlspecialchars($c['prenom'] . ' ' . $c['nom']) ?></strong></td>
          <td><?= htmlspecialchars($c['titre_offre']) ?></td>
          <td>
            <div class="score-wrap">
              <div class="score-track"><div class="score-fill" style="width:<?= $c['score_matching'] ?>%"></div></div>
              <span class="score-pct"><?= $c['score_matching'] ?>%</span>
            </div>
          </td>
          <td><span class="badge badge-<?= strtolower($c['statut']) ?>"><?= str_replace('_',' ',$c['statut']) ?></span></td>
          <td class="num"><?= date('d/m/Y', strtotime($c['created_at'])) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</section>

<!-- Données pour les graphiques -->
<script>
const chartDonut  = <?= json_encode($chart_donut) ?>;
const chartBar    = <?= json_encode($chart_bar) ?>;
const chartLine   = <?= json_encode($chart_line) ?>;
</script>

<?php require ROOT . '/app/views/layouts/footer.php'; ?>
