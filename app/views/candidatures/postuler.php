<?php require ROOT . '/app/views/layouts/header.php'; ?>

<div class="page-header page-header--form">
  <div>
    <h1>Postuler à une offre</h1>
    <p><?= htmlspecialchars($offre['titre']) ?> &nbsp;·&nbsp; <?= htmlspecialchars($offre['type_contrat']) ?></p>
  </div>
  <a href="<?= BASE_URL ?>/index.php?url=offres/detail/<?= $offre['id_offre'] ?>" class="btn btn-ghost">Retour</a>
</div>

<?php if (!empty($error)): ?>
  <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="application-form-shell">

  <!-- Résumé offre -->
  <div class="card application-form-card application-summary-card">
    <div style="display:flex;justify-content:space-between;align-items:flex-start">
      <div>
        <div style="font-size:1rem;font-weight:700;color:var(--text-primary)"><?= htmlspecialchars($offre['titre']) ?></div>
        <div style="font-size:.78rem;color:var(--text-muted);margin-top:.25rem">
          <?= htmlspecialchars($offre['localisation'] ?: 'Localisation non précisée') ?> &nbsp;·&nbsp; publié le <?= date('d/m/Y', strtotime($offre['created_at'])) ?>
        </div>
      </div>
      <span class="badge badge-<?= strtolower($offre['type_contrat']) ?>"><?= $offre['type_contrat'] ?></span>
    </div>
  </div>

  <form class="application-form" method="POST" action="<?= BASE_URL ?>/index.php?url=candidatures/store" enctype="multipart/form-data">
    <?= Csrf::input() ?>
    <input type="hidden" name="id_offre" value="<?= $offre['id_offre'] ?>">

    <!-- Profil -->
    <div class="card application-form-card">
      <div class="card-title">Votre profil</div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">CV (PDF, DOC, DOCX) *</label>
          <input type="file" name="cv" class="form-control" accept=".pdf,.doc,.docx"
                 <?= empty($profil) ? 'required' : '' ?>>
          <?php if (!empty($profil['cv_path'])): ?>
            <small style="color:var(--em-400);font-size:.72rem;font-family:var(--font-mono)">
              CV actuel : <?= htmlspecialchars(basename($profil['cv_path'])) ?>
            </small>
          <?php endif; ?>
        </div>
        <div class="form-group">
          <label class="form-label">Années d'expérience *</label>
          <input type="number" name="experience" class="form-control" min="0" max="50"
                 value="<?= htmlspecialchars($profil['experience'] ?? '0') ?>" required>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Mes compétences</label>
        <p style="font-size:.75rem;color:var(--text-muted);margin-bottom:.6rem">
          Sélectionnez vos compétences pour calculer votre score de matching avec cette offre.
        </p>
        <div class="comp-grid">
          <?php
          $myComps = [];
          if (!empty($profil)) {
              $compModel = new Competence();
              $myComps   = array_column($compModel->getByProfil($profil['id_profil']), 'id_competence');
          }
          foreach ($competences as $comp):
          ?>
          <label class="comp-label">
            <input type="checkbox" name="competences[]" value="<?= $comp['id_competence'] ?>"
              <?= in_array($comp['id_competence'], $myComps) ? 'checked' : '' ?>>
            <?= htmlspecialchars($comp['nom']) ?>
          </label>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <!-- Lettre de motivation -->
    <div class="card application-form-card">
      <div class="card-title">Lettre de motivation</div>
      <div class="form-group">
        <textarea name="lettre_motiv" class="form-control" rows="10"
                  placeholder="Décrivez votre motivation, vos atouts, pourquoi vous êtes le candidat idéal…"><?= htmlspecialchars($_POST['lettre_motiv'] ?? '') ?></textarea>
      </div>
    </div>

    <div class="form-actions">
      <button type="submit" class="btn btn-primary">Soumettre ma candidature</button>
      <a href="<?= BASE_URL ?>/index.php?url=offres/detail/<?= $offre['id_offre'] ?>" class="btn btn-ghost">Annuler</a>
    </div>
  </form>
</div>

<?php require ROOT . '/app/views/layouts/footer.php'; ?>
