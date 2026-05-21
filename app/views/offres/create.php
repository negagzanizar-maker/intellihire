<?php require ROOT . '/app/views/layouts/header.php'; ?>

<div class="page-header page-header--form">
  <div>
    <h1>Nouvelle offre d'emploi</h1>
    <p>Créez et publiez une offre pour attirer les meilleurs talents.</p>
  </div>
  <a href="<?= BASE_URL ?>/index.php?url=offres" class="btn btn-ghost">Retour</a>
</div>

<?php if (!empty($error)): ?>
  <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="offer-form-shell">
<form class="offer-form" method="POST" action="<?= BASE_URL ?>/index.php?url=offres/store">
  <?= Csrf::input() ?>
  <div class="card offer-form-card">
    <div class="card-title">Informations générales</div>
    <div class="form-group">
      <label class="form-label">Titre du poste *</label>
      <input type="text" name="titre" class="form-control" required
             placeholder="Ex : Développeur Full-Stack PHP/React" value="<?= htmlspecialchars($_POST['titre'] ?? '') ?>">
    </div>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Type de contrat *</label>
        <select name="type_contrat" class="form-control">
          <?php foreach (['CDI','CDD','Stage','Freelance'] as $t): ?>
            <option value="<?= $t ?>" <?= ($_POST['type_contrat'] ?? 'CDI') === $t ? 'selected' : '' ?>><?= $t ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Localisation</label>
        <input type="text" name="localisation" class="form-control"
               placeholder="Ex : Casablanca, Maroc" value="<?= htmlspecialchars($_POST['localisation'] ?? '') ?>">
      </div>
    </div>
    <div class="form-row form-row--single">
      <div class="form-group">
        <label class="form-label">Statut de publication</label>
        <select name="statut" class="form-control">
          <option value="Brouillon" <?= ($_POST['statut'] ?? '') === 'Brouillon' ? 'selected' : '' ?>>Brouillon</option>
          <option value="Publiee"   <?= ($_POST['statut'] ?? '') === 'Publiee'   ? 'selected' : '' ?>>Publier immédiatement</option>
        </select>
      </div>
    </div>
    <div class="form-group">
      <label class="form-label">Description du poste *</label>
      <textarea name="description" class="form-control" rows="8" required
                placeholder="Décrivez le poste, les responsabilités, le profil recherché…"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
    </div>
  </div>

  <div class="card offer-form-card">
    <div class="card-title">Compétences requises</div>
    <p style="font-size:.78rem;color:var(--text-muted);margin-bottom:.85rem">Sélectionnez les compétences attendues pour ce poste. Elles serviront au calcul du score de matching.</p>
    <div class="comp-grid">
      <?php foreach ($competences as $comp): ?>
      <label class="comp-label">
        <input type="checkbox" name="competences[]" value="<?= $comp['id_competence'] ?>">
        <?= htmlspecialchars($comp['nom']) ?>
      </label>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="form-actions">
    <button type="submit" class="btn btn-primary">Créer l'offre</button>
    <a href="<?= BASE_URL ?>/index.php?url=offres" class="btn btn-ghost">Annuler</a>
  </div>
</form>
</div>

<?php require ROOT . '/app/views/layouts/footer.php'; ?>
