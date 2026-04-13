<template>
  <div class="flex items-center">
    <h3 class="font-bold mr-md text-mmd">Per page</h3>
    <b-select
      :value="perPage"
      :options="options"
      :maxWidth="45"
      color="blue"
      @input="setPerPage"
    />
  </div>
</template>

<script setup lang="ts">
import { $computed } from 'vue/macros';
import { heritageEntitySearchModule } from '@/store/modules';
import BSelect from '@/component/Base/Select.vue';

const options = $computed(() => heritageEntitySearchModule.getPerPageOptions);
const perPage = $computed(() => heritageEntitySearchModule.getPerPage);

const setPerPage = (value: string) => {
  heritageEntitySearchModule.setSearch({
    size: value === '20' ? '' : value,
    page: 0,
  });
};
</script>
