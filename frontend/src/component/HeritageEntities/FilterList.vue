<template>
  <div class="filter-panel">
    <h2 class="filter-heading">Filters</h2>

    <heritage-entity-search-input class="mb-lg" />

    <button
      type="button"
      class="mb-lg inline-flex items-center rounded-full border-base border-gray px-base py-sm text-md transition-colors duration-200 hover:bg-lightGray"
      @click="clearFilters"
    >
      Clear filters
    </button>

    <heritage-entities-map class="mb-lg" />
    <heritage-entities-time-line />

    <heritage-entity-aggregation
      v-for="(item, id) in sortedAggs"
      :key="id"
      :id="id"
      :item="item"
    />
  </div>
</template>

<script setup lang="ts">
import { $computed } from 'vue/macros';
import { heritageEntityAggregationModule, heritageEntitySearchModule } from '@/store/modules';
import HeritageEntityAggregation from './Aggregation.vue';
import HeritageEntitiesMap from './Map.vue';
import HeritageEntitiesTimeLine from './TimeLine.vue';
import HeritageEntitySearchInput from './SearchInput.vue';

const sortedAggs = $computed(() => heritageEntityAggregationModule.getSorted);

const clearFilters = () => {
  heritageEntitySearchModule.setSearch({
    q: '',
    entityType: '',
    classification: '',
    material: '',
    period: '',
    country: '',
    place: '',
    owner: '',
    visualRepresentation: '',
    relatedDataResource: '',
    sort: '',
    order: '',
    page: 0,
    size: '',
  });
};
</script>
