<?php require ROOT . '/app/views/layouts/header.php'; ?>

<div class="page-header">
  <div>
    <span class="page-eyebrow">Candidature</span>
    <h1><?= htmlspecialchars($candidature['prenom']) ?> <em><?= htmlspecialchars($candidature['nom']) ?></em>.</h1>
    <p>Pour le poste de <strong style="color:var(--text-primary);font-weight:500"><?= htmlspecialchars($candidature['titre_offre']) ?></strong>.</p>
  </div>
  <div class="ph-actions">
    <span class="badge badge-<?= strtolower($candidature['statut']) ?>" style="font-size:.8rem;padding:.4rem .9rem">
      <?= str_replace('_',' ',$candidature['statut']) ?>
    </span>
    <a href="<?= BASE_URL ?>/index.php?url=candidatures" class="btn btn-ghost">Retour</a>
  </div>
</div>

<?php if (!empty($cv_parse_result)): ?>
<div class="card ai-summary-card" style="margin-bottom:1rem">
  <div class="card-title">CV parser IA</div>
  <p class="ai-summary-kicker"><?= htmlspecialchars($cv_parse_result['summary'] ?? '') ?></p>
  <?php if (!empty($cv_parse_result['skills'])): ?>
    <div class="comp-chips" style="margin-top:1rem">
      <?php foreach ($cv_parse_result['skills'] as $skill): ?>
        <span class="comp-chip" style="background:rgba(148,0,211,.14);border-color:rgba(148,0,211,.34);color:var(--em-300)">
          <?= htmlspecialchars($skill) ?>
        </span>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
  <?php if (!empty($cv_parse_result['experience'])): ?>
    <p style="margin-top:1rem;color:var(--text-muted);font-size:.82rem">
      Expérience estimée depuis le CV : <strong style="color:var(--text-primary)"><?= (int)$cv_parse_result['experience'] ?> an<?= (int)$cv_parse_result['experience'] > 1 ? 's' : '' ?></strong>
    </p>
  <?php endif; ?>
</div>
<?php endif; ?>

<div class="detail-grid">
  <div class="detail-main">

    <!-- Score matching -->
    <div class="card">
      <div class="card-title">Score de matching</div>
      <div style="display:flex;align-items:center;gap:2rem">
        <div style="position:relative;width:120px;height:120px;flex-shrink:0">
          <svg viewBox="0 0 36 36" style="width:120px;height:120px;transform:rotate(-90deg)">
            <circle cx="18" cy="18" r="16" fill="none" stroke="var(--bg-elevated)" stroke-width="1.8"/>
            <circle cx="18" cy="18" r="16" fill="none" stroke="var(--em-600)" stroke-width="1.8"
                    stroke-dasharray="<?= round($candidature['score_matching'] * 100.5 / 100, 1) ?> 100.5"
                    stroke-linecap="round"/>
          </svg>
          <div style="position:absolute;inset:0;display:flex;align-items:center;justify-content:center;font-family:var(--font-serif);font-size:1.95rem;font-weight:400;color:var(--text-primary);letter-spacing:-.025em;line-height:1;font-variant-numeric:tabular-nums">
            <?= round($candidature['score_matching']) ?>
          </div>
        </div>
        <div style="flex:1">
          <div style="font-family:var(--font-serif);font-size:1.4rem;font-weight:400;color:var(--text-primary);margin-bottom:.4rem;letter-spacing:-.012em">
            <?php
            $s = (float)$candidature['score_matching'];
            echo $s >= 75 ? '<em>Excellent</em> profil' : ($s >= 50 ? '<em>Bon</em> profil' : ($s >= 25 ? 'Profil <em>partiel</em>' : 'Peu de <em>correspondance</em>'));
            ?>
          </div>
          <p style="font-size:.88rem;color:var(--text-secondary);line-height:1.55">
            <?= count($matching['matched']) ?> compétence<?= count($matching['matched']) > 1 ? 's' : '' ?> sur <?= $matching['total'] ?> ·
            <?= $matching['years'] ?> an<?= $matching['years'] > 1 ? 's' : '' ?> d'expérience
          </p>
        </div>
      </div>

      <?php if ($matching['total'] > 0): ?>
      <div style="margin-top:1.75rem;padding-top:1.5rem;border-top:1px solid var(--border-subtle);display:grid;grid-template-columns:1fr 1fr;gap:2rem">
        <div>
          <div style="font-size:.85rem;color:var(--text-secondary);margin-bottom:.75rem">
            Possédées <span style="color:var(--text-muted)">(<?= count($matching['matched']) ?>)</span>
          </div>
          <div class="comp-chips">
            <?php if (empty($matching['matched'])): ?>
              <span style="font-size:.85rem;color:var(--text-muted);font-style:italic">aucune</span>
            <?php else: foreach ($matching['matched'] as $nom): ?>
              <span class="comp-chip" style="background:rgba(52,211,153,.10);border-color:rgba(52,211,153,.24);color:var(--green)"><?= htmlspecialchars($nom) ?></span>
            <?php endforeach; endif; ?>
          </div>
        </div>
        <div>
          <div style="font-size:.85rem;color:var(--text-secondary);margin-bottom:.75rem">
            Manquantes <span style="color:var(--text-muted)">(<?= count($matching['missing']) ?>)</span>
          </div>
          <div class="comp-chips">
            <?php if (empty($matching['missing'])): ?>
              <span style="font-size:.85rem;color:var(--text-muted);font-style:italic">aucune — profil complet</span>
            <?php else: foreach ($matching['missing'] as $nom): ?>
              <span class="comp-chip" style="background:rgba(251,113,133,.10);border-color:rgba(251,113,133,.24);color:var(--red)"><?= htmlspecialchars($nom) ?></span>
            <?php endforeach; endif; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <?php if (Auth::hasRole(['RECRUTEUR'])): ?>
    <!-- Analyse IA locale -->
    <div class="card ai-summary-card">
      <div class="ai-summary-head">
        <div>
          <div class="card-title">Analyse IA</div>
          <p class="ai-summary-kicker">Synthese locale du profil candidat.</p>
        </div>
        <button
          type="button"
          class="btn btn-outline"
          id="ai-summary-btn"
          data-ai-url="<?= BASE_URL ?>/index.php?url=ai/candidate-summary/<?= (int)$candidature['id_candidature'] ?>">
          Analyser avec IA
        </button>
      </div>

      <div class="ai-summary-status" id="ai-summary-status" aria-live="polite"></div>
      <div class="ai-summary-result hidden" id="ai-summary-result"></div>
    </div>
    <?php endif; ?>

    <!-- Lettre de motivation -->
    <?php if (!empty($candidature['lettre_motiv'])): ?>
    <div class="card">
      <div class="card-title">Lettre de motivation</div>
      <div style="color:var(--text-secondary);line-height:1.8;font-size:.86rem;white-space:pre-line">
        <?= htmlspecialchars($candidature['lettre_motiv']) ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Historique -->
    <div class="card">
      <div class="card-title">Historique des statuts</div>
      <?php if (empty($historique)): ?>
        <p style="font-size:.8rem;color:var(--text-muted)">Aucun changement de statut enregistré.</p>
      <?php else: ?>
      <div class="timeline">
        <?php foreach ($historique as $h): ?>
        <div class="timeline-item">
          <div class="timeline-date"><?= date('d/m/Y H:i', strtotime($h['created_at'])) ?></div>
          <div class="timeline-content">
            <span class="badge badge-<?= strtolower($h['ancien_statut']) ?>"><?= str_replace('_',' ',$h['ancien_statut']) ?></span>
            &nbsp;→&nbsp;
            <span class="badge badge-<?= strtolower($h['nouveau_statut']) ?>"><?= str_replace('_',' ',$h['nouveau_statut']) ?></span>
            &nbsp; par <strong><?= htmlspecialchars($h['prenom'] . ' ' . $h['nom']) ?></strong>
          </div>
          <?php if (!empty($h['note'])): ?>
          <div class="timeline-note">"<?= htmlspecialchars($h['note']) ?>"</div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Entretien existant -->
    <?php if ($entretien): ?>
    <div class="card" style="border-color:rgba(251,191,36,.28)">
      <div class="card-title">Entretien planifié</div>
      <div style="display:flex;gap:1.5rem;flex-wrap:wrap;font-size:.84rem">
        <div><span style="color:var(--text-muted)">Date : </span><strong style="color:var(--text-primary)"><?= date('d/m/Y à H:i', strtotime($entretien['date_entretien'])) ?></strong></div>
        <div><span style="color:var(--text-muted)">Type : </span><span class="badge badge-candidat"><?= $entretien['type_entretien'] ?></span></div>
        <div><span style="color:var(--text-muted)">Lieu/Lien : </span><span style="color:var(--em-400);font-family:var(--font-mono)"><?= htmlspecialchars($entretien['lieu_ou_lien']) ?></span></div>
        <div><span style="color:var(--text-muted)">Décision : </span><span class="badge badge-<?= strtolower($entretien['decision']) ?>"><?= str_replace('_',' ',$entretien['decision']) ?></span></div>
      </div>
    </div>
    <?php endif; ?>

  </div>

  <div class="detail-side">

    <!-- Infos candidat -->
    <div class="card">
      <div class="card-title">Candidat</div>
      <div style="display:flex;flex-direction:column;gap:1.25rem">

        <div style="display:flex;align-items:center;gap:.85rem">
          <div style="width:42px;height:42px;background:var(--em-500);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:.78rem;font-weight:500;color:var(--text-primary);flex-shrink:0;letter-spacing:.02em">
            <?= strtoupper(substr($candidature['prenom'],0,1).substr($candidature['nom'],0,1)) ?>
          </div>
          <div style="min-width:0;flex:1">
            <div style="font-size:.95rem;font-weight:500;color:var(--text-primary);overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($candidature['prenom'] . ' ' . $candidature['nom']) ?></div>
            <div style="font-size:.78rem;color:var(--text-muted);font-family:var(--font-mono);overflow:hidden;text-overflow:ellipsis"><?= htmlspecialchars($candidature['email']) ?></div>
          </div>
        </div>

        <dl style="display:flex;flex-direction:column;gap:.85rem;margin:0;padding-top:1.25rem;border-top:1px solid var(--border-subtle)">
          <div style="display:flex;justify-content:space-between;align-items:baseline;gap:1rem">
            <dt style="font-size:.85rem;color:var(--text-muted)">Expérience</dt>
            <dd style="font-size:.9rem;color:var(--text-primary);margin:0"><?= (int)$candidature['experience'] ?> an<?= (int)$candidature['experience'] > 1 ? 's' : '' ?></dd>
          </div>
          <div style="display:flex;justify-content:space-between;align-items:baseline;gap:1rem">
            <dt style="font-size:.85rem;color:var(--text-muted)">Soumise le</dt>
            <dd style="font-size:.85rem;color:var(--text-secondary);margin:0;font-family:var(--font-mono)"><?= date('d/m/Y', strtotime($candidature['created_at'])) ?></dd>
          </div>
        </dl>

        <?php if (!empty($candidature['cv_path'])): ?>
        <a href="<?= BASE_URL ?>/<?= htmlspecialchars($candidature['cv_path']) ?>" target="_blank" class="btn btn-outline btn-sm btn-full" style="margin-top:.25rem">Télécharger le CV</a>
        <?php endif; ?>

      </div>
    </div>

    <!-- Actions recruteur -->
    <?php if (Auth::hasRole(['RECRUTEUR'])): ?>
    <div class="card">
      <div class="card-title">Changer le statut</div>
      <form method="POST" action="<?= BASE_URL ?>/index.php?url=candidatures/statut/<?= $candidature['id_candidature'] ?>">
        <?= Csrf::input() ?>
        <div class="form-group">
          <label class="form-label">Nouveau statut</label>
          <select name="statut" class="form-control">
            <?php foreach (['Recue','En_cours','Entretien','Acceptee','Refusee'] as $s): ?>
              <option value="<?= $s ?>" <?= $candidature['statut'] === $s ? 'selected' : '' ?>><?= str_replace('_',' ',$s) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Note interne (optionnel)</label>
          <textarea name="note" class="form-control" rows="3" placeholder="Commentaire sur ce changement de statut…"></textarea>
        </div>
        <button type="submit" class="btn btn-primary btn-full">Mettre à jour</button>
      </form>
    </div>

    <?php if (!$entretien): ?>
    <div class="card">
      <div class="card-title">Planifier un entretien</div>
      <p style="font-size:.78rem;color:var(--text-muted);margin-bottom:.75rem">Invitez ce candidat à un entretien.</p>
      <a href="<?= BASE_URL ?>/index.php?url=entretiens/create/<?= $candidature['id_candidature'] ?>" class="btn btn-outline btn-full">Planifier</a>
    </div>
    <?php endif; ?>
    <?php endif; ?>

  </div>
</div>

<?php require ROOT . '/app/views/layouts/footer.php'; ?>
