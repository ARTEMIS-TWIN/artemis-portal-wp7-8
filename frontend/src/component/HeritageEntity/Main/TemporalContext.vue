<template>
  <div v-if="periodItems.length || chronology">
    <h3 class="text-lg font-bold mb-md">
      <i class="fas fa-hourglass-half mr-sm" />
      Temporal Context
    </h3>

    <div :class="itemClass" v-if="periodItems.length">
      <strong :class="bClass">Dating</strong>
      <div
        v-for="(item, index) in periodItems"
        :key="`heritage-temporal-period-${index}`"
        class="block"
        :class="{ 'mb-sm': index < periodItems.length - 1 }"
      >
        <b-link
          :to="utils.paramsToString('/heritage-entities', { period: item.label })"
          class="spatial-context-link break-word"
        >
          {{ item.label }}
        </b-link>
      </div>
    </div>

    <div :class="itemClass" v-if="chronology">
      <strong :class="bClass">Chronology</strong>{{ chronology }}
    </div>
  </div>
</template>

<script setup lang="ts">
import { $computed } from 'vue/macros';
import utils from '@/utils/utils';
import BLink from '@/component/Base/Link.vue';

const props = defineProps<{
  entity: any,
  itemClass: string,
  bClass: string,
}>();

const periodItems = $computed(() => {
  const seen = new Set<string>();
  const items: Array<{ label: string }> = [];

  for (const period of props.entity?.periods || []) {
    const label = String(period?.label || '').trim();
    if (!label || seen.has(label.toLowerCase())) {
      continue;
    }

    seen.add(label.toLowerCase());
    items.push({ label });
  }

  return items;
});

const chronology = $computed(() => {
  const from = props.entity?.minPeriodFrom;
  const until = props.entity?.maxPeriodUntil;

  if (from === undefined || from === null || until === undefined || until === null) {
    return '';
  }

  return `${from} to ${until}`;
});
</script>
