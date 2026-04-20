<template>
  <div v-if="hasSpatialContext">
    <h3 class="text-lg font-bold mb-md">
      <i class="fas fa-map-marker-alt mr-sm" />
      Spatial Context
    </h3>

    <div :class="itemClass" v-if="locationEntries.length">
      <strong :class="bClass">Location</strong>
      <div
        v-for="(entry, index) in locationEntries"
        :key="`location-entry-${index}`"
        class="spatial-context-entry"
      >
        <div class="spatial-context-links">
          <template
            v-for="(token, tokenIndex) in entry.tokens"
            :key="`location-token-${index}-${tokenIndex}`"
          >
            <b-link
              :to="utils.paramsToString('/search', token.searchParams)"
              :class="spatialLinkClasses"
            >
              {{ token.label }}
            </b-link>
            <span
              v-if="tokenIndex < entry.tokens.length - 1"
              class="spatial-context-comma"
            >
              ,
            </span>
          </template>
        </div>
      </div>
    </div>

    <div :class="itemClass" v-if="placeLabels.length">
      <strong :class="bClass">Place</strong>
      <div class="spatial-context-links">
        <b-link
          v-for="(item, index) in placeLabels"
          :key="`place-${index}`"
          :to="utils.paramsToString('/search', item.searchParams)"
          :class="spatialLinkClasses"
        >
          {{ item.label }}
        </b-link>
      </div>
    </div>

    <div :class="itemClass" v-if="countryLabels.length">
      <strong :class="bClass">Country</strong>
      <div class="spatial-context-links">
        <b-link
          v-for="(item, index) in countryLabels"
          :key="`country-${index}`"
          :to="utils.paramsToString('/search', item.searchParams)"
          :class="spatialLinkClasses"
        >
          {{ item.label }}
        </b-link>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { $computed } from 'vue/macros';
import { resourceModule } from '@/store/modules';
import utils from '@/utils/utils';
import BLink from '@/component/Base/Link.vue';

defineProps<{
  itemClass: string,
  bClass: string,
}>();

const resource = $computed(() => resourceModule.getResource);
const spatialLinkClasses = 'spatial-context-link break-word';

const locationEntries = $computed(() => {
  const seen = new Set<string>();
  const entries: Array<{ tokens: Array<{ label: string, searchParams: Record<string, string> }> }> = [];

  for (const spatial of resource?.spatial || []) {
    const locationValue = getLocationValue(spatial);
    if (!locationValue) {
      continue;
    }

    const tokens = locationValue
      .split(',')
      .map((token: string) => token.trim())
      .filter(Boolean)
      .map((token: string) => ({
        label: token,
        searchParams: { q: token, fields: 'location' },
      }));

    if (!tokens.length) {
      continue;
    }

    const key = tokens.map((token: any) => token.label).join('|');
    if (!seen.has(key)) {
      seen.add(key);
      entries.push({ tokens });
    }
  }

  return entries;
});

const placeLabels = $computed(() => {
  const seen = new Set<string>();
  const labels: Array<{ label: string, searchParams: Record<string, string> }> = [];

  for (const spatial of resource?.spatial || []) {
    const placeName = String(spatial?.placeName || '').trim();
    const fallbackLocation = getLocationValue(spatial);
    const placeValue = placeName || fallbackLocation;

    if (placeValue && !seen.has(placeValue)) {
      seen.add(placeValue);
      labels.push({
        label: placeValue,
        searchParams: { q: placeValue, fields: 'location' },
      });
    }
  }

  return labels;
});

const countryLabels = $computed(() => {
  const seen = new Set<string>();
  const labels: Array<{ label: string, searchParams: Record<string, string> }> = [];

  for (const country of resource?.country || []) {
    const name = String(country?.name || '').trim();
    if (name && !seen.has(name)) {
      seen.add(name);
      labels.push({
        label: name,
        searchParams: { country: name },
      });
    }
  }

  return labels;
});

const hasSpatialContext = $computed(() => {
  return locationEntries.length > 0
    || placeLabels.length > 0
    || countryLabels.length > 0;
});

const getLocationValue = (spatial: any): string => {
  const location = String(spatial?.location || '').trim();
  const placeName = String(spatial?.placeName || '').trim();

  return location || (placeName.includes(',') ? placeName : '');
};
</script>
