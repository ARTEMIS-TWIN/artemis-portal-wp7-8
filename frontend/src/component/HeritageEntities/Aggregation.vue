<template>
  <div v-if="item?.buckets?.length">
    <list-accordion
      :title="title"
      :description="description"
      :initShow="isActiveAny"
      :autoShow="isActiveAny"
      :hover="true"
    >
      <div>
        <div
          v-for="bucket in item.buckets"
          :key="bucket.key"
          class="filter-item text-md"
          :class="{ 'is-active': isActive(bucket.key) }"
          @click="toggle(bucket.key)"
        >
          <span class="flex-grow break-word pr-lg">
            {{ bucket.key }}
          </span>

          <span class="filter-count">
            {{ bucket.doc_count || 0 }}
          </span>
        </div>
      </div>
    </list-accordion>
  </div>
</template>

<script setup lang="ts">
import { $computed } from 'vue/macros';
import { heritageEntityAggregationModule } from '@/store/modules';
import ListAccordion from '@/component/List/Accordion.vue';

const props = defineProps<{
  id: string,
  item: any,
}>();

const title = $computed(() => heritageEntityAggregationModule.getTitle(props.id));
const description = $computed(() => heritageEntityAggregationModule.getDescription(props.id));
const isActiveAny = $computed(() => props.item?.buckets?.some((bucket: any) => heritageEntityAggregationModule.getIsActive(props.id, bucket.key)));

const isActive = (key: string): boolean => heritageEntityAggregationModule.getIsActive(props.id, key);

const toggle = (key: string) => {
  heritageEntityAggregationModule.setActive({
    key: props.id,
    value: key,
    add: !isActive(key),
  });
};
</script>
