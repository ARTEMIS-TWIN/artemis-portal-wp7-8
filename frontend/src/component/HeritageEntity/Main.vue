<template>
  <div>
    <section :class="sectionClass">
      <h3 class="text-lg font-bold mb-md">
        <i class="fas fa-tags mr-sm" />
        Metadata
      </h3>

      <div :class="itemClass" v-if="entity.entityType">
        <strong :class="bClass">Entity type</strong>{{ entity.entityType }}
      </div>

      <div :class="itemClass" v-if="entity.classification?.length">
        <strong :class="bClass">Classification</strong>
        <authority-list :items="entity.classification" authority-label="Getty AAT" />
      </div>

      <div :class="itemClass" v-if="materialItems.length">
        <strong :class="bClass">Materials</strong>
        <authority-list :items="materialItems" authority-label="Getty AAT" />
      </div>

      <div :class="itemClass" v-if="entity.periods?.length">
        <strong :class="bClass">Periods</strong>
        <authority-list :items="periodItems" authority-label="PeriodO" />
      </div>

      <div :class="itemClass" v-if="entity.ownerLabel">
        <strong :class="bClass">Current owner</strong>{{ entity.ownerLabel }}
      </div>

      <div :class="itemClass" v-if="chronology">
        <strong :class="bClass">Chronology</strong>{{ chronology }}
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
        <figcaption class="mt-sm text-md">
          {{ primaryImage.label || entity.label || 'Visual representation' }}
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

    <section v-if="entity.location?.label || entity.countryLabel" :class="sectionClass">
      <h3 class="text-lg font-bold mb-md">
        <i class="fas fa-map-marker-alt mr-sm" />
        Spatial Context
      </h3>

      <div :class="itemClass" v-if="entity.location?.label">
        <strong :class="bClass">Location</strong>{{ entity.location.label }}
      </div>

      <div :class="itemClass" v-if="entity.placeLabel">
        <strong :class="bClass">Place</strong>{{ entity.placeLabel }}
      </div>

      <div :class="itemClass" v-if="entity.countryLabel">
        <strong :class="bClass">Country</strong>{{ entity.countryLabel }}
      </div>
    </section>
  </div>
</template>

<script setup lang="ts">
import { $computed } from 'vue/macros';
import AuthorityList from './AuthorityList.vue';

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

const periodItems = $computed(() => {
  return (props.entity?.periods || []).map((item: any) => ({
    label: item.label,
    uri: item.uri,
  }));
});

const primaryImage = $computed(() => {
  return props.entity?.visualRepresentations?.[0] || null;
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
