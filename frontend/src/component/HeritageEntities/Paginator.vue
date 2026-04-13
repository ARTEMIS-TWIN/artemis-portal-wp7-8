<template>
  <div class="flex" v-if="active">
    <span
      v-for="(page, key) in getPaging()"
      :key="key"
      class="leading-1 mx-none my-none py-md px-base text-sm border-base transition-all duration-300"
      :class="pageClasses(page)"
      :title="'page ' + (page.val < 2 ? 1 : page.val)"
      @click.prevent="changePage(page.val, page.hover)"
      v-html="page.label"
    ></span>
  </div>
</template>

<script setup lang="ts">
import { $computed } from 'vue/macros';
import VueScrollTo from 'vue-scrollto';
import { heritageEntitySearchModule } from '@/store/modules';

const props = defineProps<{
  scrollTop?: boolean,
}>();

const params = $computed(() => heritageEntitySearchModule.getParams);
const result = $computed(() => heritageEntitySearchModule.getResult);
const perPage = $computed(() => parseInt(heritageEntitySearchModule.getPerPage));
const currentPage = $computed(() => parseInt(params.page) || 1);
const active = $computed(() => {
  const total = result?.total?.value || 0;
  return total > perPage;
});

const pageClasses = (page: any): string => {
  const classes = page.active ? 'bg-blue border-blue text-white' : 'border-gray text-blue';
  return classes + (page.hover ? ' hover:bg-gray-30 cursor-pointer' : '');
};

const changePage = (page: any, can: boolean) => {
  if (!can) {
    return;
  }

  heritageEntitySearchModule.setSearch({ page: page < 2 ? 0 : page });

  if (props.scrollTop) {
    VueScrollTo.scrollTo('body');
  }
};

const getPaging = (): Array<any> => {
  const total = parseInt(result.total.value || 0);
  const pages = [];
  let start = currentPage - 2;
  const max = Math.ceil(total / perPage);
  let amount = 5;

  if (amount > max) {
    amount = max;
  }

  if (start < 1) {
    start = 1;
  }

  if (start + amount > max) {
    start = max - amount + 1;
  }

  const activePage = currentPage < 2 ? 1 : currentPage;

  for (let i = start; i < start + amount; i++) {
    if (i > 0) {
      pages.push({
        active: i === activePage,
        hover: i !== activePage,
        label: i,
        val: i,
      });
    }
  }

  pages.unshift({ label: '<i class="fas fa-angle-double-left"></i>', val: currentPage - 1, hover: currentPage > 1 });

  if (currentPage > 1) {
    pages.unshift({ label: '<i class="fas fa-arrow-circle-left"></i>', val: 1, hover: currentPage > 1 });
  }

  if (currentPage < max) {
    pages.push({ label: '<i class="fas fa-angle-double-right"></i>', val: currentPage + 1, hover: currentPage < max });
  }

  return pages;
};
</script>
