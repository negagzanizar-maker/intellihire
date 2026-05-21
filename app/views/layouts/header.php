<?php
/**
 * views/layouts/header.php — Layout principal IntelliHire
 */
$role       = Auth::role();
$user       = Auth::user() ?? [];
$prenom     = htmlspecialchars($user['prenom'] ?? '');
$initiales  = strtoupper(substr($user['prenom'] ?? 'U', 0, 1) . substr($user['nom'] ?? '', 0, 1));
$currentUrl = $_GET['url'] ?? '';

$notif_count = 0;
$notif_items = [];
if ($role) {
    $notifSvc    = new NotificationService();
    $notif_count = $notifSvc->countUnread(Auth::id());
    $notif_items = $notifSvc->getByUser(Auth::id(), 8);
}

$is_active = fn(string $prefix): string => str_starts_with($currentUrl, $prefix) ? 'active' : '';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="theme-color" content="#FBFAF7">
  <title>IntelliHire</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= filemtime(ROOT . '/public/assets/css/style.css') ?>">
</head>
<body>

<?php if ($role): ?>
<header class="topnav">
  <div class="topnav-inner">
    <a class="brand" href="<?= BASE_URL ?>/index.php?url=dashboard">
      <span class="brand-mark">IH</span>
      <span class="brand-name">IntelliHire</span>
    </a>

    <nav class="topnav-links">
      <?php if (Auth::hasRole(['RECRUTEUR'])): ?>
      <a href="<?= BASE_URL ?>/index.php?url=dashboard"    class="<?= $is_active('dashboard') ?>">Dashboard</a>
      <a href="<?= BASE_URL ?>/index.php?url=offres"       class="<?= $is_active('offres') ?>">Offres</a>
      <a href="<?= BASE_URL ?>/index.php?url=candidatures" class="<?= $is_active('candidatures') ?>">Candidats</a>
      <a href="<?= BASE_URL ?>/index.php?url=entretiens"   class="<?= $is_active('entretiens') ?>">Entretiens</a>
      <?php else: ?>
      <a href="<?= BASE_URL ?>/index.php?url=offres"       class="<?= $is_active('offres') ?>">Offres</a>
      <a href="<?= BASE_URL ?>/index.php?url=candidatures" class="<?= $is_active('candidatures') ?>">Mes candidatures</a>
      <?php endif; ?>
    </nav>

    <div class="topnav-actions">
      <div
        id="notification-vue-app"
        class="notification-vue-app"
        data-vue-island
        v-cloak>
        <button class="topnav-btn" id="notif-btn" type="button" aria-label="Notifications" @click.stop="toggle">
          <span class="notif-icon" aria-hidden="true"></span>
          <span v-if="unread > 0" class="notif-badge" id="notif-badge-count" v-text="unread"></span>
        </button>

        <div class="notif-dropdown" id="notif-dropdown" :class="{ hidden: !open }" @click.stop>
          <div class="notif-header">
            <h4>Notifications</h4>
            <div style="display:flex;align-items:center;gap:.75rem">
              <a href="#" id="notif-mark-read" @click.prevent="markRead">Tout lire</a>
              <button id="notif-close" type="button" aria-label="Fermer" @click="open = false">×</button>
            </div>
          </div>
          <div id="notif-list">
            <div v-if="!items.length" class="notif-empty">Aucune notification</div>
            <template v-else>
              <component
                v-for="item in items"
                :is="item.lien ? 'a' : 'div'"
                :key="item.id_notification || item.created_at + item.message"
                :href="notificationHref(item)"
                class="notif-item"
                :class="{ unread: Number(item.lu) === 0, 'notif-clickable': item.lien }">
                <div v-if="Number(item.lu) === 0" class="notif-dot-indicator"></div>
                <div style="flex:1">
                  <div class="notif-text" v-text="item.message"></div>
                  <div class="notif-time" v-text="formatNotifTime(item.created_at)"></div>
                </div>
              </component>
            </template>
          </div>
        </div>
      </div>

      <div class="user-chip">
        <span class="user-chip-name"><?= $prenom ?></span>
        <form method="POST" action="<?= BASE_URL ?>/index.php?url=auth/logout" style="margin:0">
          <?= Csrf::input() ?>
          <button type="submit" class="user-chip-logout" title="Déconnexion" style="border:0;background:transparent;cursor:pointer;font-family:inherit">Sortir</button>
        </form>
        <span class="user-chip-mark"><?= $initiales ?></span>
      </div>
    </div>
  </div>
</header>
<script>
window.IntelliHireVue = window.IntelliHireVue || {};
window.IntelliHireVue.notifications = {
  baseUrl: <?= json_encode(BASE_URL) ?>,
  csrfToken: <?= json_encode(Csrf::token()) ?>,
  unread: <?= (int)$notif_count ?>,
  items: <?= json_encode($notif_items, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>
};
</script>
<?php endif; ?>

<main class="main-content <?= !$role ? 'auth-layout' : '' ?>">
<div id="toast-container" class="toast-container"></div>

