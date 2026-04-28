<template>
  <div class="flex flex-wrap gap-sm">
    <b-link
      v-for="(item, key) in items"
      :key="key"
      class="heritage-authority-chip"
      :class="{ 'heritage-authority-chip--linked': !!item.label }"
      :to="isFilterMode ? getFilterUrl(item.label) : undefined"
      :href="isExternalMode ? item.uri || undefined : undefined"
      :target="isExternalMode && item.uri ? '_blank' : undefined"
    >
      <span class="heritage-authority-chip__label">{{ item.label }}</span>
      <span v-if="showAuthorityLabel" class="heritage-authority-chip__meta">{{ authorityLabel }}</span>
      <i :class="authorityIconClass" class="heritage-authority-chip__icon" aria-hidden="true"></i>
    </b-link>
  </div>
</template>

<script setup lang="ts">
import { $computed } from 'vue/macros';
import BLink from '@/component/Base/Link.vue';
import utils from '@/utils/utils';

const props = defineProps<{
  items: Array<{ label: string, uri?: string | null }>,
  authorityLabel: string,
  filterKey?: string,
  basePath?: string,
  showAuthorityLabel?: boolean,
  mode?: 'filter' | 'external',
}>();

const isFilterMode = $computed(() => (props.mode || 'filter') === 'filter');
const isExternalMode = $computed(() => (props.mode || 'filter') === 'external');
const showAuthorityLabel = $computed(() => props.showAuthorityLabel !== false);
const authorityIconClass = $computed(() => isFilterMode ? 'fas fa-search' : 'fas fa-external-link-alt');

const getFilterUrl = (value: string): string => {
  const basePath = props.basePath || '/heritage-entities';
  if (!props.filterKey) {
    return basePath;
  }

  return utils.paramsToString(basePath, {
    [props.filterKey]: value,
  });
};
</script>
