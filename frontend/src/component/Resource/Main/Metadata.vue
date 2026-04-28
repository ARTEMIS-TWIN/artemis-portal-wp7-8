<template>
  <div>
    <h3 class="text-lg font-bold mb-md">
      <i class="fas fa-tags mr-sm"></i>
      Metadata
    </h3>

    <div v-if="resource.language && utils.getLanguage(resource.language)" :class="itemClass">
        <b :class="bClass">Language:</b>
        <i class="fas fa-globe mr-xs"></i>
        {{ utils.getLanguage(resource.language) }}
    </div>

    <resource-filtered-items
      :items="resource.audience"
      :class="itemClass"
      title="Audience"
      slotType="plain"
    />

    <resource-filtered-items
      :items="resource.ariadneSubject"
      :class="itemClass"
      title="Resource Type"
    >
      <template v-slot="{ item }">
        <b-link :to="utils.paramsToString('/search/', { ariadneSubject: item.prefLabel })">
          <span class="relative">
            <img
              :src="getResourceIcon(item)"
              style="position:absolute;top:-3px;left:0"
              class="mr-sm"
              alt="icon"
              width="20"
              height="20"
            />
            <span class="ml-2x">
              {{ utils.sentenceCase(item.prefLabel) }}
            </span>
          </span>
        </b-link>
      </template>
    </resource-filtered-items>

    <resource-filtered-items
      :items="resource.publisher"
      :class="itemClass"
      slotType="organisation"
      filter="name"
      title="Publisher"
      query="publisher"
    />

    <div v-if="nativeSubjectChips.length" :class="itemClass">
      <b class="mr-xs block mb-sm">
        Original Subject:
      </b>
      <authority-list
        :items="nativeSubjectChips"
        authority-label="Original Subject"
        filter-key="nativeSubject"
        base-path="/search"
        :show-authority-label="false"
      />
    </div>

    <div v-if="derivedSubjects.length" :class="itemClass">
      <b class="mr-xs block mb-sm">
        Getty AAT Subject:
        <help-tooltip
          title="Read more about AAT"
          top="0"
          left="1.25rem"
        >
          <a
            href="https://www.getty.edu/research/tools/vocabularies/aat/about.html"
            target="_blank"
          >
            <i class="fas fa-question-circle ml-xs"></i>
          </a>
        </help-tooltip>
      </b>
      <authority-list
        :items="derivedSubjects"
        authority-label="Getty AAT"
        filter-key="derivedSubject"
        base-path="/search"
        :show-authority-label="false"
      />
    </div>

    <resource-filtered-items
      :items="resource.keyword"
      :class="itemClass"
      title="Keyword"
      query="keyword"
      icon="fas fa-tag mr-sm mb-xs"
      slotType="plain"
    />

    <div v-if="resource.extent && resource.extent.length" :class="itemClass">
      <b :class="bClass">Extent:</b>
      <span>{{ resource.extent.join(', ') }}</span>
    </div>

    <div v-if="resource.resourceType" :class="itemClass">
      <b :class="bClass">Category:</b>
      <i class="fas mr-sm" :class="/collection/i.test(resource.resourceType) ? 'fa-copy' : 'fa-database'"></i>
      <b-link :to="utils.paramsToString('/search', { resourceType: resource.resourceType })">
        {{ utils.sentenceCase(resource.resourceType) }}
      </b-link>
      <b-link
        v-if="resource.has_type"
        :href="resource.has_type.uri"
        target="_blank"
      >
        ({{ utils.sentenceCase(resource.has_type.label) }})
      </b-link>
    </div>

    <resource-filtered-items
      :items="resource.dataType"
      :class="itemClass"
      title="Data Type"
      filter="label"
      query="dataType"
      icon="fas fa-server mr-sm mb-xs"
      slotType="prop"
    />

    <div v-if="resource.wasCreated" :class="itemClass">
      <b :class="bClass">Created:</b>
      <span>{{ utils.formatDate(resource.wasCreated) }}</span>
    </div>

    <div v-if="resource.issued" :class="itemClass">
      <b :class="bClass">Issued:</b>
      <span>{{ utils.formatDate(resource.issued) }}</span>
    </div>

    <div v-if="resource.modified" :class="itemClass">
      <b :class="bClass">Last updated:</b>
      <span>{{ utils.formatDate(resource.modified) }}</span>
    </div>

    <resource-filtered-items
      :items="resource.hasMetadataRecord"
      :class="itemClass"
      :divider="true"
      filter="xmlDoc,conformsTo"
      title="Metadata record"
    >
      <template v-slot="{ item, last }">
        <div v-if="utils.validUrl(item.xmlDoc)" class="mt-sm" :class="{ 'border-b-base border-gray pb-md mb-md': !last }">
          <b-link
            :href="item.xmlDoc"
            target="_blank"
            class="break-word"
            :useDefaultStyle="true"
          >
            {{ item.xmlDoc }}
          </b-link>
        </div>

        <div v-if="item.conformsTo && item.conformsTo.length && item.conformsTo[0]">
          <div v-if="item.conformsTo[0].description" class="mt-sm whitespace-pre-line">{{ utils.cleanText(item.conformsTo[0].description, true) }}</div>
          <div v-if="item.conformsTo[0].characterSet" class="mt-sm">
            (Character set: {{ item.conformsTo[0].characterSet }})
          </div>
        </div>
      </template>
    </resource-filtered-items>
  </div>
</template>

<script setup lang="ts">
import { $computed } from 'vue/macros';
import { resourceModule } from "@/store/modules";
import utils from '@/utils/utils';
import BLink from '@/component/Base/Link.vue';
import HelpTooltip from '@/component/Help/Tooltip.vue';
import AuthorityList from '@/component/HeritageEntity/AuthorityList.vue';
import ResourceFilteredItems from '../FilteredItems.vue';

defineProps<{
  itemClass: string,
  bClass: string,
}>();

const resource = $computed(() => resourceModule.getResource);
const nativeSubjectChips = $computed(() => {
  const items = utils.getSorted(resource?.nativeSubject, 'prefLabel') || [];
  return items
    .filter((item: any) => Boolean(item?.prefLabel))
    .map((item: any) => ({
      label: String(item.prefLabel).trim(),
      uri: item?.id || null,
    }));
});
const derivedSubjects = $computed(() => {
  const items = utils.getSorted(resource?.derivedSubject, 'prefLabel') || [];
  return items
    .filter((item: any) => Boolean(item?.prefLabel))
    .map((item: any) => ({
      label: String(item.prefLabel).trim(),
      uri: item?.id || null,
    }));
});

const getResourceIcon = (item: any): string => {
  return resourceModule.getIconByTypeName(item.prefLabel);
}
</script>
