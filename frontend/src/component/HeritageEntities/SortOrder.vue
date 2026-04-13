<template>
  <div class="flex items-center">
    <h3 class="font-bold mr-md text-mmd">Order</h3>
    <b-select
      :value="order"
      :options="options"
      :minWidth="185"
      color="blue"
      @input="setOrder"
    />
  </div>
</template>

<script setup lang="ts">
import { $computed } from 'vue/macros';
import { heritageEntitySearchModule } from '@/store/modules';
import BSelect from '@/component/Base/Select.vue';

const options = $computed(() => heritageEntitySearchModule.getSortOptions);
const order = $computed(() => heritageEntitySearchModule.getDefaultSort().sort + '-' + heritageEntitySearchModule.getDefaultSort().order);

const setOrder = (value: string) => {
  const parts = value.split('-');

  heritageEntitySearchModule.setSearch({
    sort: parts[0],
    order: parts[1],
  });
};
</script>
