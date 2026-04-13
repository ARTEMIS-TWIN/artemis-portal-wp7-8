<template>
  <div class="flex flex-wrap gap-sm">
    <a
      v-for="(item, key) in items"
      :key="key"
      :href="item.uri || undefined"
      :target="item.uri ? '_blank' : undefined"
      class="heritage-authority-chip"
      :class="{ 'heritage-authority-chip--linked': !!item.uri }"
    >
      <span class="heritage-authority-chip__label">{{ item.label }}</span>
      <span class="heritage-authority-chip__meta">{{ authorityLabel }}</span>
      <i v-if="item.uri" class="fas fa-external-link-alt heritage-authority-chip__icon" aria-hidden="true"></i>
    </a>
  </div>
</template>

<script setup lang="ts">
defineProps<{
  items: Array<{ label: string, uri?: string | null }>,
  authorityLabel: string,
}>();
</script>
