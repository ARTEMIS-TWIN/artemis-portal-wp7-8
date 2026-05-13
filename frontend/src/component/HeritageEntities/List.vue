<template>
  <div>
    <p v-if="result.error" class="mt-xl text-red text-mmd">
      {{ result.error }}
    </p>
    <p v-else-if="utils.objectIsEmpty(result) && isLoading" class="mt-xl text-mmd">
      Searching..
    </p>
    <p v-else-if="!result.hits || !result.hits.length" class="mt-xl text-mmd">
      No heritage entities found
    </p>

    <div v-else>
      <div
        v-for="(res, key) in result.hits"
        :key="key"
        class="relative"
      >
        <label class="artemisia-select" :class="{ 'artemisia-select--active': isSelected(res.id) }">
          <input
            type="checkbox"
            :checked="isSelected(res.id)"
            @click.stop
            @change="toggleSelection(res)"
          >
          <span class="artemisia-select__label">
            <i class="fas fa-robot mr-xs"></i>
            Ask Artemisia
          </span>
        </label>

        <div class="absolute left-md top-base flex flex-col result-list__icon-column">
          <b-link
            :to="`/heritage-entities/${res.id}`"
            class="leading-0 result-list__type-link"
          >
            <span class="result-list__type-tile">
              <i class="fas fa-landmark result-list__type-fallback" aria-hidden="true"></i>
            </span>
          </b-link>
        </div>

        <b-link
          :to="`/heritage-entities/${res.id}`"
          class="block p-base transition-all duration-300 group result-card mb-base"
          :class="{ 'result-card--selected': isSelected(res.id) }"
        >
          <div class="ml-4x pl-sm result-list__body">
            <h3 class="text-blue text-lg font-bold mb-base group-hover:underline app-section-title">
              {{ res.data.label || 'Untitled entity' }}
            </h3>

            <div v-if="res.data.description" class="mb-base text-mmd text-midGray2">
              <p>{{ trimDescription(res.data.description) }}</p>
            </div>

            <div class="text-mmd">
              <p v-if="res.data.entityType"><b>Entity Type</b>: {{ res.data.entityType }}</p>
              <p v-if="res.data.classificationLabels?.length"><b>Classification</b>: {{ res.data.classificationLabels.join(', ') }}</p>
              <p v-if="res.data.materials?.length"><b>Material</b>: {{ res.data.materials.join(', ') }}</p>
              <p v-if="res.data.periodLabels?.length"><b>Period</b>: {{ res.data.periodLabels.join(', ') }}</p>
              <p v-if="res.data.ownerLabel"><b>Owner</b>: {{ res.data.ownerLabel }}</p>
              <p v-if="res.data.placeLabel || res.data.countryLabel">
                <b>Location</b>: {{ [res.data.placeLabel, res.data.countryLabel].filter(Boolean).join(', ') }}
              </p>
              <p v-if="res.data.relatedDataResources?.length">
                <b>Related Data Resources</b>: {{ res.data.relatedDataResources.length }}
              </p>
            </div>
          </div>
        </b-link>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { $computed } from 'vue/macros';
import { generalModule, heritageEntitySearchModule, artemisIAModule } from '@/store/modules';
import utils from '@/utils/utils';
import BLink from '@/component/Base/Link.vue';

const result = $computed(() => heritageEntitySearchModule.getResult);
const isLoading = $computed(() => generalModule.getIsLoading);

const trimDescription = (value: string): string => {
  return utils.trimString(utils.cleanText(value, false), 320);
};

const isSelected = (id: string): boolean => {
  return artemisIAModule.isHeritageEntitySelected(String(id));
};

const toggleSelection = (res: any) => {
  artemisIAModule.toggleHeritageEntity({
    id: String(res?.id || ''),
    title: String(res?.data?.label || 'Untitled entity'),
    type: 'heritage-entity',
    subtitle: String(res?.data?.entityType || ''),
  });
};
</script>
