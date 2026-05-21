<?php require ROOT . '/app/views/layouts/header.php'; ?>

<div class="auth-root">
  <div class="auth-panel">
    <a class="brand" href="<?= BASE_URL ?>/index.php?url=auth/login" style="margin-bottom:3rem">
      <span class="brand-mark">IH</span>
      <span class="brand-name">IntelliHire</span>
    </a>

    <span class="page-eyebrow">Identification</span>
    <h1 class="auth-h">Bon <em>retour</em>.</h1>
    <p class="auth-sub">Reprenez là où vous vous étiez arrêté — votre espace de recrutement vous attend.</p>

    <?php if (!empty($error)): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (isset($_GET['registered'])): ?>
      <div class="alert alert-success">Compte créé. Connectez-vous maintenant.</div>
    <?php endif; ?>

    <form method="POST" action="<?= BASE_URL ?>/index.php?url=auth/login">
      <?= Csrf::input() ?>
      <div class="form-group">
        <label class="form-label">Adresse email</label>
        <input type="email" name="email" class="form-control" required
               placeholder="vous@exemple.com" autocomplete="email">
      </div>
      <div class="form-group">
        <label class="form-label">Mot de passe</label>
        <input type="password" name="mot_de_passe" class="form-control" required
               placeholder="••••••••" autocomplete="current-password">
      </div>
      <button type="submit" class="btn btn-primary btn-full" style="margin-top:1.25rem">
        Se connecter
      </button>
    </form>

    <p class="auth-link-row">
      Pas encore de compte ?
      <a href="<?= BASE_URL ?>/index.php?url=auth/register">Créer un compte</a>
    </p>
  </div>

  <aside class="auth-visual">
    <div class="auth-editorial">
      <span class="auth-editorial-num">Une nouvelle façon de recruter</span>
      <h2 class="auth-editorial-h">Recruter, sans <em>fracas</em>.</h2>
      <p class="auth-editorial-p">
        Une plateforme calme et précise pour suivre vos candidats,
        de la première lettre au premier jour.
      </p>

      <ul class="auth-editorial-list">
        <li><span>1</span> Matching transparent entre profil et poste</li>
        <li><span>2</span> Suivi clair des candidatures en cours</li>
        <li><span>3</span> Planification simple des entretiens</li>
        <li><span>4</span> Tableaux de bord lisibles et apaisés</li>
      </ul>
    </div>
  </aside>
</div>

<?php require ROOT . '/app/views/layouts/footer.php'; ?>

