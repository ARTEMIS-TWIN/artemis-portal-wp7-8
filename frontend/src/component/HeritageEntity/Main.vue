<template>
  <div>
    <section :class="sectionClass">
      <h3 class="text-lg font-bold mb-md">
        <i class="fas fa-tags mr-sm" />
        Metadata
      </h3>

      <div :class="itemClass" v-if="entity.entityType">
        <strong :class="bClass">Entity type</strong>
        <b-link
          :to="getFilterUrl('entityType', entity.entityType)"
          class="spatial-context-link break-word"
        >
          {{ entity.entityType }}
        </b-link>
      </div>

      <div :class="itemClass" v-if="entity.classification?.length">
        <strong :class="bClass">Classification</strong>
        <authority-list :items="entity.classification" authority-label="Getty AAT" filter-key="classification" />
      </div>

      <div :class="itemClass" v-if="materialItems.length">
        <strong :class="bClass">Materials</strong>
        <authority-list :items="materialItems" authority-label="Getty AAT" filter-key="material" />
      </div>

      <div :class="itemClass" v-if="entity.ownerLabel">
        <strong :class="bClass">Current owner</strong>
        <b-link
          :to="getFilterUrl('owner', entity.ownerLabel)"
          class="spatial-context-link break-word"
        >
          {{ entity.ownerLabel }}
        </b-link>
      </div>
    </section>

    <section v-if="primaryImage" :class="sectionClass">
      <h3 class="text-lg font-bold mb-md">
        <i class="fas fa-image mr-sm" />
        Visual Representation
      </h3>

      <figure class="heritage-entity__figure">
        <a :href="primaryImage.uri" target="_blank" rel="noopener noreferrer">
          <img
            :src="primaryImage.uri"
            :alt="primaryImage.label || (entity.label || 'Heritage entity visual representation')"
            class="heritage-entity__image"
          >
        </a>
        <figcaption v-if="showPrimaryImageCaption" class="mt-sm text-md">
          {{ primaryImageCaption }}
        </figcaption>
      </figure>
    </section>

    <section v-if="entity.encounterEvent" :class="sectionClass">
      <h3 class="text-lg font-bold mb-md">
        <i class="fas fa-project-diagram mr-sm" />
        Archaeological Context
      </h3>

      <div :class="itemClass" v-if="entity.encounterEvent.label">
        <strong :class="bClass">Encounter event</strong>{{ entity.encounterEvent.label }}
      </div>

      <div :class="itemClass" v-if="entity.encounterEvent.actorLabel">
        <strong :class="bClass">Carried out by</strong>{{ entity.encounterEvent.actorLabel }}
      </div>

      <div :class="itemClass" v-if="entity.encounterEvent.timeSpanLabel">
        <strong :class="bClass">Time span</strong>{{ entity.encounterEvent.timeSpanLabel }}
      </div>
    </section>

    <section
      v-if="entity.location?.label || entity.placeLabel || entity.countryLabel || entity.location?.countryLabel"
      :class="sectionClass"
    >
      <heritage-entity-main-spatial-context
        :entity="entity"
        :itemClass="itemClass"
        :bClass="bClass"
      />
    </section>

    <section
      v-if="entity.location?.lat !== undefined || entity.location?.geopoint"
      :class="sectionClass"
    >
      <heritage-entity-main-coordinates
        :entity="entity"
        :itemClass="itemClass"
        :bClass="bClass"
      />
    </section>

    <section
      v-if="entity.periods?.length || (entity.minPeriodFrom !== undefined && entity.maxPeriodUntil !== undefined)"
      :class="sectionClass"
    >
      <heritage-entity-main-temporal-context
        :entity="entity"
        :itemClass="itemClass"
        :bClass="bClass"
      />
    </section>
  </div>
</template>

<script setup lang="ts">
import { $computed } from 'vue/macros';
import AuthorityList from './AuthorityList.vue';
import HeritageEntityMainSpatialContext from './Main/SpatialContext.vue';
import HeritageEntityMainCoordinates from './Main/Coordinates.vue';
import HeritageEntityMainTemporalContext from './Main/TemporalContext.vue';
import BLink from '@/component/Base/Link.vue';
import utils from '@/utils/utils';

const props = defineProps<{
  entity: any,
}>();

const sectionClass = 'py-md mb-lg';
const itemClass = 'border-b-base border-gray mb-md pb-md last:border-b-0 last:pb-none last:mb-none';
const bClass = 'mr-sm';

const materialItems = $computed(() => {
  if (Array.isArray(props.entity?.materialDetails) && props.entity.materialDetails.length) {
    return props.entity.materialDetails;
  }

  return (props.entity?.materials || []).map((label: string) => ({ label }));
});

const primaryImage = $computed(() => {
  return props.entity?.visualRepresentations?.[0] || null;
});

const primaryImageCaption = $computed(() => {
  return String(props.entity?.visualRepresentations?.[0]?.label || props.entity?.label || '').trim();
});

const showPrimaryImageCaption = $computed(() => {
  return primaryImageCaption !== '' && !utils.validUrl(primaryImageCaption);
});

const getFilterUrl = (key: string, value: string): string => {
  return utils.paramsToString('/heritage-entities', {
    [key]: value,
  });
};
</script>
