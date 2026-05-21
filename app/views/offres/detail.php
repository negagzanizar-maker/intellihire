<?php require ROOT . '/app/views/layouts/header.php'; ?>

<div class="page-header">
  <div>
    <span class="page-eyebrow">Offre d'emploi</span>
    <h1><?= htmlspecialchars($offre['titre']) ?></h1>
    <p><?= htmlspecialchars($offre['localisation'] ?: 'Localisation non précisée') ?> · proposée par <?= htmlspecialchars($offre['prenom'] . ' ' . $offre['nom']) ?>.</p>
  </div>
  <div class="ph-actions">
    <span class="badge badge-<?= strtolower($offre['type_contrat']) ?>"><?= $offre['type_contrat'] ?></span>
    <span class="badge badge-<?= strtolower($offre['statut']) ?>"><?= $offre['statut'] ?></span>
    <a href="<?= BASE_URL ?>/index.php?url=offres" class="btn btn-ghost">Retour</a>
  </div>
</div>

<div class="detail-grid">
  <div class="detail-main">

    <!-- Description -->
    <div class="card">
      <div class="card-title">Description du poste</div>
      <div style="color:var(--text-secondary);line-height:1.8;font-size:.88rem;white-space:pre-line">
        <?= htmlspecialchars($offre['description']) ?>
      </div>
    </div>

    <!-- Compétences requises -->
    <?php if (!empty($competences)): ?>
    <div class="card">
      <div class="card-title">Compétences requises</div>
      <div class="comp-chips">
        <?php foreach ($competences as $comp): ?>
          <span class="comp-chip"><?= htmlspecialchars($comp['nom']) ?></span>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <div class="detail-side">
    <!-- Infos -->
    <div class="card">
      <div class="card-title">Informations</div>
      <div style="display:flex;flex-direction:column;gap:.75rem;font-size:.82rem">
        <div>
          <div style="color:var(--text-muted);font-size:.68rem;font-family:var(--font-mono);text-transform:uppercase;margin-bottom:.15rem">Contrat</div>
          <div class="badge badge-<?= strtolower($offre['type_contrat']) ?>"><?= $offre['type_contrat'] ?></div>
        </div>
        <div>
          <div style="color:var(--text-muted);font-size:.68rem;font-family:var(--font-mono);text-transform:uppercase;margin-bottom:.15rem">Localisation</div>
          <div style="color:var(--text-primary)"><?= htmlspecialchars($offre['localisation'] ?: 'Non précisée') ?></div>
        </div>
        <div>
          <div style="color:var(--text-muted);font-size:.68rem;font-family:var(--font-mono);text-transform:uppercase;margin-bottom:.15rem">Publié le</div>
          <div style="color:var(--text-secondary);font-family:var(--font-mono)"><?= date('d/m/Y', strtotime($offre['created_at'])) ?></div>
        </div>
        <div>
          <div style="color:var(--text-muted);font-size:.68rem;font-family:var(--font-mono);text-transform:uppercase;margin-bottom:.15rem">Recruteur</div>
          <div style="color:var(--text-primary)"><?= htmlspecialchars($offre['prenom'] . ' ' . $offre['nom']) ?></div>
          <div style="color:var(--text-muted);font-size:.72rem;font-family:var(--font-mono)"><?= htmlspecialchars($offre['email'] ?? '') ?></div>
        </div>
      </div>
    </div>

    <!-- Actions candidat -->
    <?php if ($offre['statut'] === 'Publiee' && Auth::role() === 'CANDIDAT'): ?>
    <div class="card" style="border-color:rgba(148,0,211,.34)">
      <div class="card-title">Postuler</div>
      <p style="font-size:.8rem;color:var(--text-muted);margin-bottom:1rem">Envoyez votre candidature pour ce poste maintenant.</p>
      <a href="<?= BASE_URL ?>/index.php?url=candidatures/postuler/<?= $offre['id_offre'] ?>" class="btn btn-primary btn-full">Postuler maintenant</a>
    </div>
    <?php endif; ?>

    <!-- Actions recruteur -->
    <?php if (Auth::hasRole(['RECRUTEUR'])): ?>
    <div class="card">
      <div class="card-title">Actions recruteur</div>
      <div style="display:flex;flex-direction:column;gap:.5rem">
        <a href="<?= BASE_URL ?>/index.php?url=candidatures?id_offre=<?= $offre['id_offre'] ?>" class="btn btn-outline btn-full">Voir les candidatures</a>
        <a href="<?= BASE_URL ?>/index.php?url=offres/edit/<?= $offre['id_offre'] ?>" class="btn btn-ghost btn-full">Modifier l'offre</a>
        <form method="POST" action="<?= BASE_URL ?>/index.php?url=offres/delete/<?= $offre['id_offre'] ?>">
          <?= Csrf::input() ?>
          <button type="submit"
                  class="btn btn-danger btn-full"
                  data-confirm="Supprimer définitivement cette offre ?">Supprimer</button>
        </form>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>

<?php require ROOT . '/app/views/layouts/footer.php'; ?>
