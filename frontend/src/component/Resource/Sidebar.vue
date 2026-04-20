<template>
  <div>
    <section
      v-if="hasIdentifiers"
      :class="sectionClass"
    >
      <h3 class="text-lg font-bold mb-lg">
        <i class="fas fa-fingerprint mr-sm"></i>
        Identifiers
      </h3>

      <div class="mb-md" v-if="resource.identifier">
        <strong class="block mb-xs">Resource URI</strong>
        <b-link
          v-if="utils.validUrl(resource.identifier)"
          :href="resource.identifier"
          target="_blank"
          class="text-blue hover:underline break-word"
          :useDefaultStyle="true"
        >
          {{ resource.identifier }}
        </b-link>
        <span v-else class="break-word">{{ resource.identifier }}</span>
      </div>

      <div class="mb-md" v-if="resource.originalId">
        <strong class="block mb-xs">Original ID</strong>
        <span class="break-word">{{ resource.originalId }}</span>
      </div>

      <div
        class="mb-md"
        v-if="otherIdentifiers.length"
      >
        <strong class="block mb-xs">Other ID</strong>
        <span
          v-for="(otherId, index) in otherIdentifiers"
          :key="`${otherId}-${index}`"
          class="break-word block"
        >
          {{ otherId }}
        </span>
      </div>

      <div class="mb-md" v-if="resource.landingPage">
        <strong class="block mb-xs">Landing page</strong>
        <b-link
          v-if="utils.validUrl(resource.landingPage)"
          :href="resource.landingPage"
          target="_blank"
          class="text-blue hover:underline break-word"
          :useDefaultStyle="true"
        >
          {{ resource.landingPage }}
        </b-link>
        <span v-else class="break-word">{{ resource.landingPage }}</span>
      </div>
    </section>

    <section
      v-if="collection?.hits?.length && collection.total"
      :class="sectionClass"
    >
      <h3 class="text-lg font-bold mb-lg">
        <i class="fas fa-copy mr-sm"></i>
        Resource has {{ collection.total }} {{ collection.total === 1 ? 'record' : 'records' }}
      </h3>

      <resource-filtered-items
        :items="getTitleSorted(collection.hits)"
        slotType="resource"
        icon="fas mr-sm fa-database"
      />

      <b-link
        v-if="collection.total"
        :to="utils.paramsToString('/search', { isPartOf: resource.identifier, isPartOfLabel: resource.title ? resource.title.text : '' })"
        class="mb-sm block"
        :useDefaultStyle="true"
      >
        <i class="fas fa-search mr-sm mt-md"></i>
        Show all records
      </b-link>
    </section>

    <section
      v-if="resource.isAboutResource && resource.isAboutResource.length"
      :class="sectionClass"
    >
      <h3 class="text-lg font-bold mb-lg">
        <i class="fas fa-copy mr-sm" />
        Resource is about
      </h3>

      <resource-filtered-items :items="resource.isAboutResource">
        <template v-slot="{ item }">
          <b-link :href="item.id" target="_blank" >
            {{ item.title.text }}
          </b-link>
        </template>
      </resource-filtered-items>
    </section>

    <section
      v-if="resource.partOf?.length"
      :class="sectionClass"
    >
      <h3 class="text-lg font-bold mb-lg">
        <i class="fas fa-database mr-sm"></i>
        Resource is part of
      </h3>

      <div
        v-for="(part, key) in getPartOfSorted(resource.partOf)"
        :key="`resource-part-of-${key}`"
        class="mb-md"
      >
        <b-link
          v-if="part.id"
          :to="`/resource/${encodeURIComponent(part.id)}`"
          class="text-blue hover:underline break-word block"
        >
          {{ getResourceTitle(part) }}
        </b-link>
        <span v-else class="break-word block">{{ getResourceTitle(part) }}</span>
        <p v-if="part.resourceType" class="text-md mt-xs">
          {{ utils.sentenceCase(String(part.resourceType).replace(/[-_]/g, ' ')) }}
        </p>
        <template v-if="getPartExternalLink(part)">
          <strong class="block mt-sm mb-xs">External link</strong>
          <a
            :href="getPartExternalLink(part)"
            target="_blank"
            class="text-blue hover:underline break-word text-md"
          >
            {{ getPartExternalLink(part) }}
          </a>
        </template>
      </div>
    </section>

    <section
      v-if="resource.relatedHeritageEntities?.length"
      :class="sectionClass"
    >
      <h3 class="text-lg font-bold mb-lg">
        <i class="fas fa-landmark mr-sm"></i>
        Related Heritage Entities
      </h3>

      <div
        v-for="(entity, key) in getRelatedHeritageEntitiesSorted(resource.relatedHeritageEntities)"
        :key="`resource-related-entity-${key}`"
        class="mb-md"
      >
        <b-link
          :to="`/heritage-entities/${encodeURIComponent(entity.id)}`"
          class="text-blue hover:underline break-word block"
        >
          {{ getRelatedEntityLabel(entity) }}
        </b-link>
        <p v-if="entity.entityType" class="text-md mt-xs">
          {{ entity.entityType }}
        </p>
        <template v-if="getRelatedEntityExternalLink(entity)">
          <strong class="block mt-sm mb-xs">External link</strong>
          <a
            :href="getRelatedEntityExternalLink(entity)"
            target="_blank"
            class="text-blue hover:underline break-word text-md"
          >
            {{ getRelatedEntityExternalLink(entity) }}
          </a>
        </template>
      </div>
    </section>

    <section :class="sectionClass">
      <resource-links :resourceId="resource.id" />
    </section>

    <section :class="sectionClass">
      <h3 class="text-lg font-bold mb-sm">
        <i class="fas fa-bullseye mr-sm"></i>
        Thematically similar
      </h3>
      <p class="mb-md">
        Thematically similar resources based on terms in common of:
      </p>
      <p class="mb-lg">
        <select class="border-base p-sm"
          v-model="resourceParams.thematical"
          v-on:change="initResource(resource.id, true)">
          <option v-for="(val, key) in thematicals"
            :key="key"
            :value="key">
            {{ val }}
          </option>
        </select>
      </p>

      <div v-if="resource.similar?.length">
        <div
          v-for="(similar, key) in getTitleSorted(resource.similar)" :key="key"
          class="mb-sm"
        >
          <b-link
            :to="'/resource/' + encodeURIComponent(similar.id)"
          >
            <div class="flex items-center mb-md">
              <div class="shrink-0">
                <help-tooltip
                  :title="getResourceTypeName(similar)"
                  class="mr-sm"
                  top="0"
                  left="2rem"
                >
                  <!-- style in img is for safari bug -->
                  <img
                    :src="getResourceIcon(similar)"
                    style="width:20px;height:20px"
                    alt="icon"
                    width="20"
                    height="20">
                </help-tooltip>
              </div>

              <span class="leading-sm">{{ similar.title.text || 'No title' }}</span>
            </div>
          </b-link>
        </div>
      </div>
      <p v-else>
        No similar resources found.
      </p>
    </section>
  </div>
</template>

<script setup lang="ts">
import { onUnmounted } from 'vue';
import { $computed } from 'vue/macros';
import { resourceModule } from "@/store/modules";
import utils from '@/utils/utils';
import BLink from '@/component/Base/Link.vue';
import HelpTooltip from '@/component/Help/Tooltip.vue';
import ResourceLinks from './Links.vue';
import ResourceFilteredItems from './FilteredItems.vue';

defineProps<{
  initResource: Function,
}>();

const sectionClass: string = 'py-base pb-sm mb-md';
const resource = $computed(() => resourceModule.getResource);
const thematicals = $computed(() => resourceModule.getThematicals);
const resourceParams = $computed(() => resourceModule.getResourceParams);
const otherIdentifiers = $computed(() => {
  const seen = new Set<string>();
  const normalizedLandingPage = normalizeIdentifier(resource?.landingPage);
  const ids = Array.isArray(resource?.otherId) ? resource.otherId : [];

  return ids
    .map((item: any) => String(item ?? '').trim())
    .filter((item: string) => item !== '')
    .filter((item: string) => {
      const normalized = normalizeIdentifier(item);

      if (!normalized || normalized === normalizedLandingPage || seen.has(normalized)) {
        return false;
      }

      seen.add(normalized);
      return true;
    });
});

const hasIdentifiers = $computed(() => {
  return Boolean(
    resource?.identifier
    || resource?.originalId
    || otherIdentifiers.length
    || resource?.landingPage
  );
});

// Todo: support multiple collections later
const collection = $computed(() => Array.isArray(resource.collection) ? resource.collection[0] : resource.collection);

// reset thematical selection
onUnmounted(() => resourceModule.setResourceParamsThematical(''));

const getTitleSorted = (items: any) => {
  return utils.getSorted(items.map((s: any) => ({ ...s, text: getResourceTitle(s) })), 'text');
}

const getPartOfSorted = (items: any) => {
  return utils.getSorted(items.map((item: any) => ({ ...item, text: getResourceTitle(item) })), 'text');
}

const getResourceTitle = (item: any): string => {
  return String(item?.title?.text ?? item?.title ?? item?.identifier ?? 'Untitled resource');
}

const getPartExternalLink = (item: any): string => {
  const landingPage = String(item?.landingPage ?? '').trim();
  if (utils.validUrl(landingPage)) {
    return landingPage;
  }

  const identifier = String(item?.identifier ?? '').trim();
  if (!identifier) {
    return '';
  }

  if (utils.validUrl(identifier)) {
    return identifier.endsWith('.html') ? identifier : `${identifier}.html`;
  }

  return '';
}

const getRelatedHeritageEntitiesSorted = (items: any) => {
  return utils.getSorted(items.map((item: any) => ({ ...item, text: getRelatedEntityLabel(item) })), 'text');
}

const getRelatedEntityLabel = (item: any): string => {
  return String(item?.label ?? item?.uri ?? item?.id ?? 'Unnamed heritage entity');
}

const getRelatedEntityExternalLink = (item: any): string => {
  const uri = String(item?.uri ?? '').trim();
  if (utils.validUrl(uri)) {
    return uri;
  }

  const sourceGraph = String(item?.sourceGraph ?? '').trim();
  if (utils.validUrl(sourceGraph)) {
    return sourceGraph;
  }

  return '';
}

const getResourceTypeName = (item: any): string => {
  return item.type?.[0]?.prefLabel ?? '';
}

const getResourceIcon = (item: any): string => {
  return resourceModule.getIconByTypeName(item.type?.[0]?.prefLabel);
}

const normalizeIdentifier = (value: any): string => {
  const text = String(value ?? '').trim();
  if (!text) {
    return '';
  }

  if (!utils.validUrl(text)) {
    return text.replace(/\/+$/, '').toLowerCase();
  }

  try {
    const parsed = new URL(text);
    const normalizedPath = parsed.pathname.replace(/\/+$/, '');
    const normalizedSearch = parsed.search || '';
    return `${parsed.protocol}//${parsed.host}${normalizedPath}${normalizedSearch}`.toLowerCase();
  } catch (error) {
    return text.replace(/\/+$/, '').toLowerCase();
  }
}
</script>
