<?php require ROOT . '/app/views/layouts/header.php'; ?>

<div class="auth-root">
  <div class="auth-panel auth-panel--wide">
    <a class="brand" href="<?= BASE_URL ?>/index.php?url=auth/login" style="margin-bottom:2.5rem">
      <span class="brand-mark">IH</span>
      <span class="brand-name">IntelliHire</span>
    </a>

    <span class="page-eyebrow">Inscription</span>
    <h1 class="auth-h">Rejoignez <em>IntelliHire</em>.</h1>
    <p class="auth-sub">Dites-nous ce que vous cherchez — nous adaptons votre espace en conséquence.</p>

    <?php if (!empty($error)): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/index.php?url=auth/register" id="register-form">
      <?= Csrf::input() ?>

      <!-- Role chooser -->
      <div class="role-picker" role="group" aria-label="Type de compte">
        <label class="role-option">
          <input type="radio" name="role_choice" value="CANDIDAT" required>
          <div class="role-card">
            <div class="role-card-label">Emploi</div>
            <div class="role-card-title">Je cherche un emploi</div>
            <div class="role-card-desc">Accédez aux offres CDI et CDD, postulez et suivez vos candidatures.</div>
          </div>
        </label>
        <label class="role-option">
          <input type="radio" name="role_choice" value="CANDIDAT">
          <div class="role-card">
            <div class="role-card-label">Stage</div>
            <div class="role-card-title">Je cherche un stage</div>
            <div class="role-card-desc">Trouvez votre stage, construisez votre profil et montrez vos compétences.</div>
          </div>
        </label>
        <label class="role-option">
          <input type="radio" name="role_choice" value="RECRUTEUR">
          <div class="role-card">
            <div class="role-card-label">Recrutement</div>
            <div class="role-card-title">Je publie des offres</div>
            <div class="role-card-desc">Gérez vos offres, suivez les candidatures et planifiez les entretiens.</div>
          </div>
        </label>
      </div>

      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Nom</label>
          <input type="text" name="nom" class="form-control" required placeholder="Benali">
        </div>
        <div class="form-group">
          <label class="form-label">Prénom</label>
          <input type="text" name="prenom" class="form-control" required placeholder="Yassine">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Adresse email</label>
        <input type="email" name="email" class="form-control" required placeholder="vous@exemple.com">
      </div>
      <div class="form-group">
        <label class="form-label">
          Mot de passe
          <small style="color:var(--text-muted);font-weight:400;letter-spacing:normal">(min. 6 caractères)</small>
        </label>
        <input type="password" name="mot_de_passe" class="form-control" required
               placeholder="••••••••" minlength="6">
      </div>
      <button type="submit" class="btn btn-primary btn-full" style="margin-top:1.25rem">
        Créer mon compte
      </button>
    </form>

    <p class="auth-link-row">
      Déjà un compte ? <a href="<?= BASE_URL ?>/index.php?url=auth/login">Se connecter</a>
    </p>
  </div>

  <aside class="auth-visual" id="register-visual">
    <div class="auth-editorial" id="visual-candidat">
      <span class="auth-editorial-num">Pour les candidats</span>
      <h2 class="auth-editorial-h">Postuler n'a jamais été <em>aussi simple</em>.</h2>
      <p class="auth-editorial-p">
        Renseignez votre profil une fois et laissez la plateforme
        s'occuper du reste — matching, suivi, notifications.
      </p>
      <ul class="auth-editorial-list">
        <li><span>1</span> Mise en relation avec les offres pertinentes</li>
        <li><span>2</span> Suivi en temps réel de vos candidatures</li>
        <li><span>3</span> Notifications discrètes pour vos entretiens</li>
      </ul>
    </div>
    <div class="auth-editorial hidden" id="visual-recruteur">
      <span class="auth-editorial-num">Pour les recruteurs</span>
      <h2 class="auth-editorial-h">Recruter, sans <em>fracas</em>.</h2>
      <p class="auth-editorial-p">
        Une plateforme calme et précise pour piloter vos recrutements
        de la première candidature au premier jour en poste.
      </p>
      <ul class="auth-editorial-list">
        <li><span>1</span> Publiez vos offres en quelques clics</li>
        <li><span>2</span> Matching automatique avec les profils candidats</li>
        <li><span>3</span> Tableaux de bord lisibles et apaisés</li>
      </ul>
    </div>
  </aside>
</div>

<script>
(function () {
  const radios   = document.querySelectorAll('input[name="role_choice"]');
  const vCand    = document.getElementById('visual-candidat');
  const vRecr    = document.getElementById('visual-recruteur');

  radios.forEach(r => r.addEventListener('change', () => {
    const isRecr = r.value === 'RECRUTEUR' && r.checked;
    vCand.classList.toggle('hidden', isRecr);
    vRecr.classList.toggle('hidden', !isRecr);
  }));
})();
</script>

<?php require ROOT . '/app/views/layouts/footer.php'; ?>
