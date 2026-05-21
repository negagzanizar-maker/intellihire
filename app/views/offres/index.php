<?php require ROOT . '/app/views/layouts/header.php'; ?>

<div class="page-header">
  <div>
    <span class="page-eyebrow">Catalogue</span>
    <h1>Offres <em>d'emploi</em>.</h1>
    <p><?= count($offres) ?> offre<?= count($offres) !== 1 ? 's' : '' ?> répertoriée<?= count($offres) !== 1 ? 's' : '' ?> à ce jour.</p>
  </div>
  <div class="ph-actions">
    <?php if (Auth::hasRole(['RECRUTEUR'])): ?>
      <a href="<?= BASE_URL ?>/index.php?url=offres/create" class="btn btn-primary">Nouvelle offre</a>
    <?php endif; ?>
  </div>
</div>

<form method="GET" action="<?= BASE_URL ?>/index.php" class="filter-bar">
  <input type="hidden" name="url" value="offres">
  <input type="text" name="search" class="form-control"
         placeholder="Rechercher un poste…" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">

  <?php if (Auth::hasRole(['RECRUTEUR'])): ?>
  <select name="statut" class="form-control">
    <option value="">Tous statuts</option>
    <?php foreach (['Publiee','Brouillon','Cloturee','Archivee'] as $s): ?>
      <option value="<?= $s ?>" <?= ($_GET['statut'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
    <?php endforeach; ?>
  </select>
  <?php endif; ?>

  <select name="type_contrat" class="form-control">
    <option value="">Tous contrats</option>
    <?php foreach (['CDI','CDD','Stage','Freelance'] as $t): ?>
      <option value="<?= $t ?>" <?= ($_GET['type_contrat'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="btn btn-outline">Filtrer</button>
  <a href="<?= BASE_URL ?>/index.php?url=offres" class="btn btn-ghost">Réinitialiser</a>
</form>

<?php if (empty($offres)): ?>
  <div class="empty-state" style="padding:5rem 1rem">
    <p>Aucune offre ne correspond à votre recherche.</p>
    <?php if (Auth::hasRole(['RECRUTEUR'])): ?>
      <a href="<?= BASE_URL ?>/index.php?url=offres/create" class="btn btn-primary" style="margin-top:1.25rem">Créer une offre</a>
    <?php endif; ?>
  </div>
<?php else: ?>

<div class="table-wrap">
  <?php $is_candidat = Auth::role() === 'CANDIDAT'; ?>
  <table class="table">
    <thead>
      <tr>
        <th style="width:80px">Réf.</th>
        <th>Poste</th>
        <th style="width:120px">Contrat</th>
        <th>Localisation</th>
        <?php if ($is_candidat): ?>
        <th style="width:170px">Compatibilité</th>
        <?php else: ?>
        <th style="width:140px">Recruteur</th>
        <th style="width:100px">Cand.</th>
        <?php endif; ?>
        <?php if (Auth::hasRole(['RECRUTEUR'])): ?>
        <th style="width:120px">Statut</th>
        <?php endif; ?>
        <th style="width:160px"></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($offres as $offre):
          $score = isset($offre['match_score']) ? (float)$offre['match_score'] : null;
      ?>
      <tr data-href="<?= BASE_URL ?>/index.php?url=offres/detail/<?= $offre['id_offre'] ?>">
        <td class="num">#<?= str_pad((string)$offre['id_offre'], 4, '0', STR_PAD_LEFT) ?></td>
        <td>
          <span style="color:var(--text-primary);font-weight:500"><?= htmlspecialchars($offre['titre']) ?></span>
        </td>
        <td><span class="badge badge-<?= strtolower($offre['type_contrat']) ?>"><?= $offre['type_contrat'] ?></span></td>
        <td><?= htmlspecialchars($offre['localisation'] ?: '—') ?></td>

        <?php if ($is_candidat): ?>
        <td>
          <?php if ($score !== null && (int)$offre['skills_total'] > 0): ?>
          <div class="score-wrap">
            <div class="score-track"><div class="score-fill" style="width:<?= $score ?>%"></div></div>
            <span class="score-pct"><?= round($score) ?>%</span>
          </div>
          <?php else: ?>
            <span style="font-size:.8rem;color:var(--text-muted);font-style:italic">—</span>
          <?php endif; ?>
        </td>
        <?php else: ?>
        <td><?= htmlspecialchars($offre['prenom'] . ' ' . $offre['nom']) ?></td>
        <td class="num"><?= $offre['nb_candidatures'] ?></td>
        <?php endif; ?>

        <?php if (Auth::hasRole(['RECRUTEUR'])): ?>
        <td><span class="badge badge-<?= strtolower($offre['statut']) ?>"><?= $offre['statut'] ?></span></td>
        <?php endif; ?>

        <?php if ($is_candidat && $offre['statut'] === 'Publiee'): ?>
        <td style="text-align:right">
          <?php if (!empty($offre['already_applied'])): ?>
            <span style="font-size:.78rem;color:var(--text-muted);font-style:italic">Déjà postulé</span>
          <?php else: ?>
            <a href="<?= BASE_URL ?>/index.php?url=candidatures/postuler/<?= $offre['id_offre'] ?>" class="btn btn-primary btn-sm">Postuler</a>
          <?php endif; ?>
        </td>
        <?php else: ?>
        <td></td>
        <?php endif; ?>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<?php endif; ?>

<?php require ROOT . '/app/views/layouts/footer.php'; ?>
