<template>
  <div class="page-main">
    <div class="content-grid">
      <aside>
        <template v-if="window.innerWidth < 1000">
          <filter-toggleable class="mb-lg">
            <heritage-entity-filter-list />
          </filter-toggleable>
        </template>
        <template v-else>
          <heritage-entity-filter-list />
        </template>
      </aside>

      <section class="result-panel">
        <div class="result-layout flex flex-col">
          <div class="result-toolbar result-toolbar--aligned flex flex-col gap-4">
            <h2 class="catalogue-heading text-2xl">Heritage Entities</h2>
            <heritage-entity-info />
            <div class="result-toolbar__row flex flex-wrap items-center justify-between gap-4">
              <div class="result-toolbar__controls flex flex-wrap items-center">
                <heritage-entity-sort-order />
                <div class="result-toolbar__per-page">
                  <heritage-entity-per-page />
                </div>
              </div>
              <heritage-entity-paginator />
            </div>
          </div>

          <div class="result-list-wrap">
            <heritage-entity-list />
          </div>

          <heritage-entity-paginator :scrollTop="true" />
        </div>
      </section>
    </div>
  </div>
</template>

<script setup lang="ts">
import { watch } from 'vue';
import { $computed } from 'vue/macros';
import { useRoute, onBeforeRouteLeave } from 'vue-router';
import { generalModule, heritageEntitySearchModule } from '@/store/modules';
import FilterToggleable from '@/component/Filter/Toggleable.vue';
import HeritageEntityFilterList from '@/component/HeritageEntities/FilterList.vue';
import HeritageEntityInfo from '@/component/HeritageEntities/Info.vue';
import HeritageEntityList from '@/component/HeritageEntities/List.vue';
import HeritageEntityPaginator from '@/component/HeritageEntities/Paginator.vue';
import HeritageEntityPerPage from '@/component/HeritageEntities/PerPage.vue';
import HeritageEntitySortOrder from '@/component/HeritageEntities/SortOrder.vue';

let first = true;

const route = useRoute();
const window = $computed(() => generalModule.getWindow);
const params = $computed(() => heritageEntitySearchModule.getParams);

const setMeta = () => {
  let title = 'Heritage Entities';

  if (params.q) {
    title = `Heritage entities: ${params.q}`;
  }
  if (parseInt(params.page) > 1) {
    title += ` (page ${params.page})`;
  }

  generalModule.setMeta({
    title,
    description: 'Search and explore cultural heritage entities.',
  });
};

const unwatch = watch(route, async () => {
  if (first) {
    heritageEntitySearchModule.actionResetResultState();
    first = false;
  }

  await heritageEntitySearchModule.setSearch({ fromRoute: true });
  heritageEntitySearchModule.setAggregationSearch(route.query);
  setMeta();
}, { immediate: true });

onBeforeRouteLeave(unwatch);
</script>
