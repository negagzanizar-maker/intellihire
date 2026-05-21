<?php require ROOT . '/app/views/layouts/header.php'; ?>

<div class="page-header">
  <div>
    <span class="page-eyebrow">Recrutement</span>
    <h1><em>Entretiens</em> planifiés.</h1>
    <p><?= count($entretiens) ?> rencontre<?= count($entretiens) !== 1 ? 's' : '' ?> à venir ou récente<?= count($entretiens) !== 1 ? 's' : '' ?>.</p>
  </div>
</div>

<!-- Filtres -->
<form method="GET" action="<?= BASE_URL ?>/index.php" class="filter-bar">
  <input type="hidden" name="url" value="entretiens">
  <select name="decision" class="form-control">
    <option value="">Toutes les décisions</option>
    <?php foreach (['En_attente','Valide','Refuse'] as $d): ?>
      <option value="<?= $d ?>" <?= ($_GET['decision'] ?? '') === $d ? 'selected' : '' ?>><?= str_replace('_',' ',$d) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="type_entretien" class="form-control">
    <option value="">Tous les types</option>
    <?php foreach (['Presentiel','Visio','Telephonique'] as $t): ?>
      <option value="<?= $t ?>" <?= ($_GET['type_entretien'] ?? '') === $t ? 'selected' : '' ?>><?= $t ?></option>
    <?php endforeach; ?>
  </select>
  <button type="submit" class="btn btn-primary">Filtrer</button>
  <a href="<?= BASE_URL ?>/index.php?url=entretiens" class="btn btn-ghost">Reset</a>
</form>

<?php if (empty($entretiens)): ?>
  <div class="empty-state" style="padding:4rem">
    <div class="empty-icon"></div>
    <p>Aucun entretien planifié pour le moment.</p>
  </div>
<?php else: ?>
<div class="table-wrap">
  <table class="table">
    <thead>
      <tr>
        <th>Candidat</th>
        <th>Poste</th>
        <th>Date & Heure</th>
        <th>Type</th>
        <th>Lieu / Lien</th>
        <th>Décision</th>
        <th></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($entretiens as $e): ?>
      <tr data-href="<?= BASE_URL ?>/index.php?url=candidatures/detail/<?= $e['id_candidature'] ?>">
        <td>
          <strong style="color:var(--text-primary)"><?= htmlspecialchars($e['prenom'] . ' ' . $e['nom']) ?></strong>
          <br><span style="font-size:.72rem;color:var(--text-muted);font-family:var(--font-mono)"><?= htmlspecialchars($e['email']) ?></span>
        </td>
        <td style="color:var(--text-secondary)"><?= htmlspecialchars($e['titre_offre']) ?></td>
        <td>
          <div style="font-weight:500;color:var(--text-primary);font-family:var(--font-mono);font-size:.8rem"><?= date('d/m/Y', strtotime($e['date_entretien'])) ?></div>
          <div style="font-size:.72rem;color:var(--text-muted);font-family:var(--font-mono)"><?= date('H:i', strtotime($e['date_entretien'])) ?></div>
        </td>
        <td>
          <span class="badge <?= $e['type_entretien'] === 'Visio' ? 'badge-candidat' : ($e['type_entretien'] === 'Telephonique' ? 'badge-manager' : 'badge-recruteur') ?>">
            <?= $e['type_entretien'] ?>
          </span>
        </td>
        <td style="font-size:.75rem;color:var(--text-muted);font-family:var(--font-mono);max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
          <?= htmlspecialchars($e['lieu_ou_lien']) ?>
        </td>
        <td>
          <span class="badge badge-<?= strtolower($e['decision']) ?>"><?= str_replace('_',' ',$e['decision']) ?></span>
        </td>
        <td>
          <?php if ($e['decision'] === 'En_attente'): ?>
            <button onclick="openDecision(<?= $e['id_entretien'] ?>, '<?= $e['decision'] ?>')"
                    class="btn btn-ghost btn-sm">Décider</button>
          <?php else: ?>
            <span style="font-size:.72rem;color:var(--text-muted)">Clôturé</span>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
<?php endif; ?>

<!-- Modal Décision -->
<div class="modal-backdrop hidden" id="modal-decision">
  <div class="modal-box">
    <div class="modal-title">Enregistrer la décision</div>
    <form method="POST" id="form-decision">
      <?= Csrf::input() ?>
      <div class="form-group">
        <label class="form-label">Décision *</label>
        <select name="decision" class="form-control" id="select-decision">
          <option value="Valide">Validé — candidat retenu</option>
          <option value="Refuse">Refusé — candidat non retenu</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Compte-rendu</label>
        <textarea name="compte_rendu" class="form-control" rows="4"
                  placeholder="Résumez le déroulement de l'entretien…"></textarea>
      </div>
      <div class="form-actions">
        <button type="submit" class="btn btn-primary">Enregistrer</button>
        <button type="button" class="btn btn-ghost" onclick="closeDecision()">Annuler</button>
      </div>
    </form>
  </div>
</div>

<?php require ROOT . '/app/views/layouts/footer.php'; ?>
