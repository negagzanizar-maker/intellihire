<?php require ROOT . '/app/views/layouts/header.php'; ?>

<div class="page-header">
  <div>
    <span class="page-eyebrow"><?= Auth::role() === 'CANDIDAT' ? 'Mon espace' : 'Recrutement' ?></span>
    <?php if (Auth::role() === 'CANDIDAT'): ?>
      <h1>Mes <em>candidatures</em>.</h1>
    <?php else: ?>
      <h1>Suivi des <em>candidatures</em>.</h1>
    <?php endif; ?>
    <p><?= $total_count ?> candidature<?= $total_count !== 1 ? 's' : '' ?> au total.</p>
  </div>
</div>

<div id="candidature-vue-app" class="vue-island" data-vue-island v-cloak>
  <form id="ajax-filter-form" class="filter-bar" @submit.prevent="fetchRows(1)">
    <input type="text" name="search" class="form-control"
           placeholder="Nom, email, offre…" v-model.trim="filters.search" @input="debouncedFetch">

    <select name="statut" class="form-control" v-model="filters.statut" @change="fetchRows(1)">
      <option value="">Tous les statuts</option>
      <option v-for="status in statuses" :key="status.value" :value="status.value" v-text="status.label"></option>
    </select>

    <?php if (Auth::hasRole(['RECRUTEUR'])): ?>
    <select name="id_offre" class="form-control" v-model="filters.id_offre" @change="fetchRows(1)">
      <option value="">Toutes les offres</option>
      <option v-for="offre in offres" :key="offre.id_offre" :value="String(offre.id_offre)" v-text="offre.titre"></option>
    </select>
    <?php endif; ?>

    <button type="submit" class="btn btn-primary" :disabled="loading">Filtrer</button>
    <button type="button" class="btn btn-ghost" @click="resetFilters" :disabled="loading">Réinitialiser</button>
    <div class="ajax-loader" id="ajax-loader" :class="{ active: loading }"><div class="spinner"></div>Chargement…</div>
  </form>

  <div class="vue-result-meta">
    <span v-text="resultLabel"></span>
    <span v-if="error" class="vue-error" v-text="error"></span>
  </div>

  <div class="table-wrap">
    <table class="table">
      <thead>
        <tr>
          <th>Candidat</th>
          <th>Offre</th>
          <th style="width:160px">Score</th>
          <th style="width:120px">Statut</th>
          <th style="width:110px">Date</th>
        </tr>
      </thead>
      <tbody id="ajax-table-body">
        <tr v-if="!rows.length">
          <td colspan="5" style="text-align:center;padding:2.5rem;color:var(--text-muted)">
            Aucune candidature trouvée.
          </td>
        </tr>
        <template v-else>
          <tr
            v-for="row in rows"
            :key="row.id_candidature"
            :data-href="detailUrl(row)"
            tabindex="0"
            @click="goTo(row, $event)"
            @keydown.enter.prevent="goTo(row, $event)"
            @keydown.space.prevent="goTo(row, $event)">
            <td>
              <strong style="color:var(--text-primary)" v-text="candidateName(row)"></strong>
              <br><span style="font-size:.72rem;color:var(--text-muted);font-family:var(--font-mono)" v-text="row.email"></span>
            </td>
            <td style="color:var(--text-secondary)" v-text="row.titre_offre"></td>
            <td>
              <div class="score-wrap">
                <div class="score-track">
                  <div class="score-fill" :style="{ width: score(row) + '%' }"></div>
                </div>
                <span class="score-pct" v-text="score(row) + '%'"></span>
              </div>
            </td>
            <td><span class="badge" :class="badgeClass(row.statut)" v-text="formatStatus(row.statut)"></span></td>
            <td style="font-family:var(--font-mono);font-size:.72rem;color:var(--text-muted)" v-text="formatDate(row.created_at)"></td>
          </tr>
        </template>
      </tbody>
    </table>
  </div>

  <div v-if="pages > 1" class="pagination" id="ajax-pagination">
    <a class="page-btn" :class="{ disabled: page <= 1 }" @click.prevent="fetchRows(page - 1)">‹</a>
    <a
      v-for="item in paginationItems"
      :key="item.key"
      class="page-btn"
      :class="{ active: item.value === page, disabled: item.type === 'ellipsis' }"
      @click.prevent="item.type === 'page' && fetchRows(item.value)"
      v-text="item.label"></a>
    <a class="page-btn" :class="{ disabled: page >= pages }" @click.prevent="fetchRows(page + 1)">›</a>
  </div>
</div>

<script>
window.IntelliHireVue = window.IntelliHireVue || {};
window.IntelliHireVue.candidatures = {
  baseUrl: <?= json_encode(BASE_URL) ?>,
  initialRows: <?= json_encode($candidatures, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>,
  total: <?= (int)$total_count ?>,
  pages: <?= (int)$total_pages ?>,
  page: <?= (int)$page ?>,
  filters: {
    search: <?= json_encode($_GET['search'] ?? '') ?>,
    statut: <?= json_encode($_GET['statut'] ?? '') ?>,
    id_offre: <?= json_encode((string)($_GET['id_offre'] ?? '')) ?>
  },
  offres: <?= json_encode($offres ?? [], JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>
};
</script>

<?php require ROOT . '/app/views/layouts/footer.php'; ?>

