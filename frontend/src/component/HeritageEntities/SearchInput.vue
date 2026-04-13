<template>
  <form class="w-full filter-search" @submit.prevent="submit">
    <div class="flex items-center w-full min-w-0">
      <div class="filter-search__controls relative flex flex-col w-full min-w-0">
        <div class="filter-search__input-wrap flex w-full min-w-0">
          <input
            v-model="newSearch"
            class="filter-search__input flex-1 min-w-0 outline-none border-gray border-base border-r-0 placeholder-darkGray bg-white py-sm px-md rounded-l-2xl text-md"
            type="text"
            placeholder="Search heritage entities..."
          >
          <button
            class="filter-search__submit py-xs px-base text-sm rounded-r-2xl transition-all duration-300 focus:outline-none bg-blue border-blue border-base hover:bg-blue-80"
          >
            <i class="fas fa-search text-white"></i>
          </button>
        </div>
      </div>
    </div>
  </form>
</template>

<script setup lang="ts">
import { watch } from 'vue';
import { useRoute, onBeforeRouteLeave } from 'vue-router';
import { heritageEntitySearchModule } from '@/store/modules';

const route = useRoute();
let newSearch = $ref('');

const submit = () => {
  heritageEntitySearchModule.setSearch({
    q: newSearch.trim(),
    page: 0,
  });
};

const unwatch = watch(route, () => {
  newSearch = heritageEntitySearchModule.getParams.q || '';
}, { immediate: true });

onBeforeRouteLeave(unwatch);
</script>
