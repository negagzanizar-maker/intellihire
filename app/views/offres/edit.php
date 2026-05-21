<?php require ROOT . '/app/views/layouts/header.php'; ?>

<div class="page-header page-header--form">
  <div>
    <h1>Modifier l'offre</h1>
    <p><?= htmlspecialchars($offre['titre']) ?></p>
  </div>
  <a href="<?= BASE_URL ?>/index.php?url=offres/detail/<?= $offre['id_offre'] ?>" class="btn btn-ghost">Retour</a>
</div>

<?php if (!empty($error)): ?>
  <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="offer-form-shell">
<form class="offer-form" method="POST" action="<?= BASE_URL ?>/index.php?url=offres/update/<?= $offre['id_offre'] ?>">
  <?= Csrf::input() ?>
  <div class="card offer-form-card">
    <div class="card-title">Informations générales</div>
    <div class="form-group">
      <label class="form-label">Titre du poste *</label>
      <input type="text" name="titre" class="form-control" required
             value="<?= htmlspecialchars($_POST['titre'] ?? $offre['titre']) ?>">
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Type de contrat *</label>
        <select name="type_contrat" class="form-control">
          <?php foreach (['CDI','CDD','Stage','Freelance'] as $t): ?>
            <option value="<?= $t ?>" <?= ($_POST['type_contrat'] ?? $offre['type_contrat']) === $t ? 'selected' : '' ?>><?= $t ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Localisation</label>
        <input type="text" name="localisation" class="form-control"
               value="<?= htmlspecialchars($_POST['localisation'] ?? $offre['localisation']) ?>">
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Statut</label>
      <select name="statut" class="form-control">
        <?php foreach (['Brouillon','Publiee','Cloturee','Archivee'] as $s): ?>
          <option value="<?= $s ?>" <?= ($_POST['statut'] ?? $offre['statut']) === $s ? 'selected' : '' ?>><?= $s ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label class="form-label">Description *</label>
      <textarea name="description" class="form-control" rows="8" required><?= htmlspecialchars($_POST['description'] ?? $offre['description']) ?></textarea>
    </div>
  </div>

  <div class="card offer-form-card">
    <div class="card-title">Compétences requises</div>
    <div class="comp-grid">
      <?php foreach ($competences as $comp): ?>
      <label class="comp-label">
        <input type="checkbox" name="competences[]" value="<?= $comp['id_competence'] ?>"
          <?= in_array($comp['id_competence'], $selected ?? []) ? 'checked' : '' ?>>
        <?= htmlspecialchars($comp['nom']) ?>
      </label>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
    <a href="<?= BASE_URL ?>/index.php?url=offres/detail/<?= $offre['id_offre'] ?>" class="btn btn-ghost">Annuler</a>
  </div>
</form>
</div>

<?php require ROOT . '/app/views/layouts/footer.php'; ?>
