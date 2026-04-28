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
        :key="`heritage-location-entry-${index}`"
        class="spatial-context-entry"
      >
        <div class="spatial-context-links">
          <template
            v-for="(token, tokenIndex) in entry.tokens"
            :key="`heritage-location-token-${index}-${tokenIndex}`"
          >
            <b-link
              :to="utils.paramsToString('/heritage-entities', token.searchParams)"
              class="spatial-context-link break-word"
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
          :key="`heritage-place-${index}`"
          :to="utils.paramsToString('/heritage-entities', item.searchParams)"
          class="spatial-context-link break-word"
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
          :key="`heritage-country-${index}`"
          :to="utils.paramsToString('/heritage-entities', item.searchParams)"
          class="spatial-context-link break-word"
        >
          {{ item.label }}
        </b-link>
      </div>
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

const locationEntries = $computed(() => {
  const seen = new Set<string>();
  const entries: Array<{ tokens: Array<{ label: string, searchParams: Record<string, string> }> }> = [];

  for (const locationValue of getLocationCandidates(props.entity)) {
    const tokens = locationValue
      .split(',')
      .map((token: string) => token.trim())
      .filter(Boolean)
      .map((token: string) => ({
        label: token,
        searchParams: { q: token },
      }));

    if (!tokens.length) {
      continue;
    }

    const key = tokens.map((token: any) => token.label).join('|').toLowerCase();
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
  const placeCandidates = getPlaceCandidates(props.entity);

  for (const place of placeCandidates) {
    const normalized = place.toLowerCase();
    if (!seen.has(normalized)) {
      seen.add(normalized);
      labels.push({
        label: place,
        searchParams: { q: place },
      });
    }
  }

  return labels;
});

const countryLabels = $computed(() => {
  const seen = new Set<string>();
  const labels: Array<{ label: string, searchParams: Record<string, string> }> = [];

  for (const country of getCountryCandidates(props.entity)) {
    const normalized = country.toLowerCase();
    if (!seen.has(normalized)) {
      seen.add(normalized);
      labels.push({
        label: country,
        searchParams: { country },
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

const uniqueNonEmpty = (values: Array<any>): string[] => {
  const seen = new Set<string>();
  const results: string[] = [];

  for (const value of values) {
    const text = String(value || '').trim();
    if (!text) {
      continue;
    }

    const normalized = text.toLowerCase();
    if (!seen.has(normalized)) {
      seen.add(normalized);
      results.push(text);
    }
  }

  return results;
};

const getLocationCandidates = (entity: any): string[] => {
  const locationLabel = String(entity?.location?.label || '').trim();
  const placeLabel = String(entity?.placeLabel || '').trim();

  return uniqueNonEmpty([
    locationLabel,
    placeLabel.includes(',') ? placeLabel : '',
  ]);
};

const getPlaceCandidates = (entity: any): string[] => {
  const placeValue = String(entity?.placeLabel || '').trim();
  if (placeValue) {
    return [placeValue];
  }

  return getLocationCandidates(entity);
};

const getCountryCandidates = (entity: any): string[] => {
  return uniqueNonEmpty([
    entity?.countryLabel,
    entity?.location?.countryLabel,
  ]);
};
</script>
