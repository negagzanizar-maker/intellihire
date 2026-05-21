<?php
require ROOT . '/app/views/layouts/header.php';

$candidateName = trim(($candidature['prenom'] ?? '') . ' ' . ($candidature['nom'] ?? ''));
$candidateInitials = strtoupper(substr($candidature['prenom'] ?? 'C', 0, 1) . substr($candidature['nom'] ?? '', 0, 1));
$candidateDetailUrl = BASE_URL . '/index.php?url=candidatures/detail/' . $candidature['id_candidature'];
$scoreMatching = isset($candidature['score_matching']) ? round((float)$candidature['score_matching']) : 0;
?>

<div class="page-header page-header--compact interview-header">
  <div>
    <span class="page-eyebrow">Entretien</span>
    <h1>Planifier un entretien</h1>
    <p class="interview-header-meta">
      <span>Pour <strong><?= htmlspecialchars($candidateName) ?></strong></span>
      <span>Poste <strong><?= htmlspecialchars($candidature['titre_offre']) ?></strong></span>
    </p>
  </div>
  <a href="<?= $candidateDetailUrl ?>" class="btn btn-outline">Retour</a>
</div>

<?php if ($existant): ?>
  <div class="alert alert-warning interview-alert">Un entretien est d&eacute;j&agrave; planifi&eacute; pour cette candidature.</div>
<?php endif; ?>

<form class="interview-create" method="POST" action="<?= BASE_URL ?>/index.php?url=entretiens/store">
  <?= Csrf::input() ?>
  <input type="hidden" name="id_candidature" value="<?= $candidature['id_candidature'] ?>">

  <div class="interview-layout">
    <section class="card interview-card">
      <div class="interview-card-head">
        <div>
          <div class="card-title">D&eacute;tails de l'entretien</div>
          <p class="interview-card-copy">Choisissez le cr&eacute;neau, le format et le lieu ou lien de rendez-vous.</p>
        </div>
      </div>

      <div class="form-row interview-form-row">
        <div class="form-group">
          <label class="form-label">Date et heure *</label>
          <input type="datetime-local" name="date_entretien" class="form-control" required
                 min="<?= date('Y-m-d\TH:i') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Type d'entretien *</label>
          <select name="type_entretien" class="form-control" id="type-select">
            <option value="Presentiel">Pr&eacute;sentiel</option>
            <option value="Visio">Visio (Zoom / Teams...)</option>
            <option value="Telephonique">T&eacute;l&eacute;phonique</option>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label" id="lieu-label">Lieu *</label>
        <input type="text" name="lieu_ou_lien" class="form-control" id="lieu-input" required
               placeholder="Ex : Bureau RH, 2e &eacute;tage">
      </div>

      <div class="interview-actions">
        <button type="submit" class="btn btn-primary" <?= $existant ? 'disabled' : '' ?>>
          Planifier l'entretien
        </button>
        <a href="<?= $candidateDetailUrl ?>" class="btn btn-ghost">Annuler</a>
      </div>
    </section>

    <aside class="card interview-side" aria-label="Recapitulatif candidat">
      <div class="card-title">R&eacute;capitulatif candidat</div>
      <div class="interview-candidate-head">
        <div class="interview-avatar"><?= htmlspecialchars($candidateInitials) ?></div>
        <div>
          <strong><?= htmlspecialchars($candidateName) ?></strong>
          <span><?= htmlspecialchars($candidature['titre_offre']) ?></span>
        </div>
      </div>

      <dl class="interview-meta-list">
        <div>
          <dt>Email</dt>
          <dd><a href="mailto:<?= htmlspecialchars($candidature['email']) ?>"><?= htmlspecialchars($candidature['email']) ?></a></dd>
        </div>
        <div>
          <dt>Poste</dt>
          <dd><?= htmlspecialchars($candidature['titre_offre']) ?></dd>
        </div>
        <div class="interview-score">
          <dt>Score matching</dt>
          <dd><strong><?= $scoreMatching ?>%</strong></dd>
        </div>
      </dl>

      <a href="<?= $candidateDetailUrl ?>" class="btn btn-outline btn-full">Voir la candidature</a>
    </aside>
  </div>
</form>

<?php require ROOT . '/app/views/layouts/footer.php'; ?>
