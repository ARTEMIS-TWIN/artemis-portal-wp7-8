<template>
  <div>
    <div v-if="result?.total" class="text-md">
      Time: {{ result.time }}s.
      Total: {{ new Intl.NumberFormat('en', { style: 'decimal' }).format(result.total.value) }}<span v-if="result.total.relation !== 'eq'">+</span>.
      <span v-if="result.total.value">
        Page: {{ currentPage }} / {{ lastPage }}
      </span>
    </div>

    <button
      v-if="selectedCount"
      type="button"
      class="artemisia-suggestion mt-md"
      @click="useArtemisIA()"
    >
      <i class="fas fa-robot mr-sm"></i>
      Ask ArtemisIA with {{ selectedCount }} selected
    </button>

    <div v-if="activeFilters.length">
      <div
        v-for="(filter, key) in activeFilters"
        :key="key"
        class="inline-block bg-lightGray mr-md mt-md py-xs px-sm cursor-pointer hover:bg-red-80 group transition-bg duration-300"
        @click="removeFilter(filter)"
      >
        <span class="group-hover:text-white transition-text duration-300 text-mmd align-middle">
          {{ getFilterTitle(filter) }}: {{ filter.val }}
        </span>
        <i class="fas fa-times text-md text-red group-hover:text-white align-middle ml-sm transition-text duration-300"></i>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { $computed } from 'vue/macros';
import { heritageEntityAggregationModule, heritageEntitySearchModule, artemisIAModule } from '@/store/modules';
import { heritageFilter } from '@/store/modules/HeritageEntityAggregation';

const params = $computed(() => heritageEntitySearchModule.getParams);
const result = $computed(() => heritageEntitySearchModule.getResult);
const perPage = $computed(() => parseInt(heritageEntitySearchModule.getPerPage));
const activeFilters = $computed(() => heritageEntityAggregationModule.activeFilters);
const selectedCount = $computed(() => artemisIAModule.selectedCount);

const currentPage = $computed(() => {
  const page = parseInt(params.page);
  return page && page > 1 ? page : 1;
});

const lastPage = $computed(() => Math.max(1, Math.ceil((result.total?.value || 0) / perPage)));

const getFilterTitle = (filter: heritageFilter): string => {
  if (filter.key === 'q') {
    return 'Search';
  }

  return heritageEntityAggregationModule.getTitle(filter.key);
};

const removeFilter = (filter: heritageFilter) => {
  heritageEntityAggregationModule.setActive({
    key: filter.key,
    value: filter.val,
    add: false,
  });
};

const useArtemisIA = () => {
  artemisIAModule.useSelectionContext();
};
</script>
