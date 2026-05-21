<?php require ROOT . '/app/views/layouts/header.php'; ?>

<div style="display:flex;align-items:center;justify-content:center;min-height:60vh">
  <div style="text-align:center;max-width:400px">
    <div style="font-size:5rem;margin-bottom:1rem;animation:float 3s ease-in-out infinite"></div>
    <div style="font-size:4rem;font-weight:800;color:var(--em-500);font-family:var(--font-mono);line-height:1">404</div>
    <div style="font-size:1.1rem;font-weight:700;color:var(--text-primary);margin-top:.5rem">Page introuvable</div>
    <p style="font-size:.84rem;color:var(--text-muted);margin-top:.75rem;line-height:1.6">
      La page que vous recherchez n'existe pas ou a été déplacée.
    </p>
    <div style="margin-top:1.5rem;display:flex;gap:.75rem;justify-content:center">
      <a href="<?= BASE_URL ?>/index.php?url=dashboard" class="btn btn-primary">Dashboard</a>
      <a href="<?= BASE_URL ?>/index.php?url=offres" class="btn btn-ghost">Offres d'emploi</a>
    </div>
  </div>
</div>

<?php require ROOT . '/app/views/layouts/footer.php'; ?>
