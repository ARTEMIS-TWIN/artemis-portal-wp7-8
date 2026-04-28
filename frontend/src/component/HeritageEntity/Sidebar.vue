<template>
  <div>
    <section :class="sectionClass">
      <h3 class="text-lg font-bold mb-lg">
        <i class="fas fa-fingerprint mr-sm"></i>
        Identifiers
      </h3>

      <div class="mb-md" v-if="entity.uri">
        <strong class="block mb-xs">Entity URI</strong>
        <a :href="entity.uri" target="_blank" class="text-blue hover:underline break-word">
          {{ entity.uri }}
        </a>
      </div>

      <div class="mb-md" v-if="entity.sourceGraph">
        <strong class="block mb-xs">Source graph</strong>
        <a :href="entity.sourceGraph" target="_blank" class="text-blue hover:underline break-word">
          {{ entity.sourceGraph }}
        </a>
      </div>

      <div
        v-for="(identifier, key) in entity.identifiers || []"
        :key="`identifier-${key}`"
        class="mb-md"
      >
        <strong class="block mb-xs">{{ identifier.type || 'Identifier' }}</strong>
        <span class="break-word">{{ identifier.value }}</span>
      </div>
    </section>

    <section v-if="entity.relatedDataResources?.length" :class="sectionClass">
      <h3 class="text-lg font-bold mb-lg">
        <i class="fas fa-database mr-sm"></i>
        Related Data Resources
      </h3>

      <div
        v-for="(resource, key) in entity.relatedDataResources"
        :key="`resource-${key}`"
        class="mb-md"
      >
        <b-link :to="`/resource/${resource.id}`" class="text-blue hover:underline break-word block">
          {{ resource.title || resource.uri }}
        </b-link>
        <p v-if="resource.resourceType" class="text-md mt-xs">{{ resource.resourceType }}</p>
        <strong class="block mt-sm mb-xs">Available on ARIADNE</strong>
        <a :href="externalResourceHref(resource.uri)" target="_blank" class="text-blue hover:underline break-word text-md">
          {{ resource.uri }}
        </a>
      </div>
    </section>

    <section v-if="hasEntityLinks" :class="sectionClass">
      <h3 class="text-lg font-bold mb-lg">
        <i class="fas fa-link mr-sm"></i>
        Entity Links
      </h3>

      <div class="md:flex justify-between items-center md:pb-lg mb-md">
        <ul class="mb-base md:mb-none md:flex">
          <li v-if="entityJsonLink" class="border-gray border-b-base md:border-b-0 last:border-b-0">
            <b-link
              :href="entityJsonLink"
              target="_blank"
              class="block pb-md md:pb-none md:pr-md leading-1 hover:text-black group transition-all duration-300"
            >
              <i class="fas fa-cloud-download-alt mr-sm text-blue group-hover:text-black transition-all duration-300"></i>
              <span class="group-hover:underline">Json</span>
            </b-link>
          </li>

          <li v-if="utils.validUrl(entity.uri)" class="border-gray border-b-base md:border-b-0 last:border-b-0">
            <b-link
              :href="entity.uri"
              target="_blank"
              class="block py-md md:py-none md:px-md leading-1 hover:text-black group transition-all duration-300"
            >
              <i class="fas fa-share-alt mr-sm text-blue group-hover:text-black transition-all duration-300"></i>
              <span class="group-hover:underline">Rdf</span>
            </b-link>
          </li>

          <template v-if="utils.validUrl(citationLink)">
            <li
              class="relative border-gray border-b-base md:border-b-0 leading-1 last:border-b-0 transition-all duration-300"
              :class="{ 'text-green': isCiting }"
              @click="toggleCiting"
            >
              <span
                class="block py-md md:py-none md:px-md cursor-pointer hover:text-black group transition-all duration-300"
              >
                <i class="fas fa-link mr-sm text-blue group-hover:text-black transition-all duration-300"></i>
                <span class="group-hover:underline">Cite</span>
              </span>
            </li>
          </template>
        </ul>
      </div>

      <div v-show="isCiting">
        <input
          v-on:focus="toggleCiting(true)"
          :id="citeRefId"
          type="text"
          :value="citationLink"
          style="width:100%"
          class="w-full border-base py-sm px-md block border-yellow outline-none"
        >
      </div>

      <b-link
        v-if="externalEntityLink"
        class="inline-block border-base border-blue p-sm hover:text-black hover:border-black group mt-md mb-sm transition-all duration-300"
        :useDefaultStyle="true"
        target="_blank"
        :href="externalEntityLink"
      >
        <i class="fas fa-external-link-alt mr-sm text-blue group-hover:text-black transition-all duration-300"></i>External Link
      </b-link>

      <div
        v-if="remainingExternalLinks.length"
        class="mt-md"
      >
        <strong class="block mb-xs">Additional external links</strong>
        <div
          v-for="(link, key) in remainingExternalLinks"
          :key="`entity-external-${key}`"
          class="mb-xs"
        >
          <a :href="link" target="_blank" class="text-blue hover:underline break-word">
            {{ link }}
          </a>
        </div>
      </div>
    </section>

    <section v-if="authorityGroups.length" :class="sectionClass">
      <h3 class="text-lg font-bold mb-lg">
        <i class="fas fa-project-diagram mr-sm"></i>
        Authorities
      </h3>

      <div
        v-for="(group, key) in authorityGroups"
        :key="`authority-group-${key}`"
        class="mb-md"
      >
        <authority-list
          :items="group.items"
          :authority-label="group.authorityLabel"
          :show-authority-label="true"
          mode="external"
        />
      </div>
    </section>

  </div>
</template>

<script setup lang="ts">
import { nextTick } from 'vue';
import { $computed, $ref } from 'vue/macros';
import BLink from '@/component/Base/Link.vue';
import AuthorityList from './AuthorityList.vue';
import utils from '@/utils/utils';

const props = defineProps<{
  entity: any,
  entityId: string,
}>();

const sectionClass = 'py-base pb-sm mb-md';
const apiUrl: string | undefined = $computed(() => process.env.apiUrl);
const citeRefId: string = 'heritage-cite-ref-' + utils.getUniqueId();
let isCiting: boolean = $ref(false);

const authorityGroups = $computed(() => {
  const groupMap = new Map<string, Array<{ label: string, uri?: string | null }>>();
  const seen = new Set<string>();

  const pushItems = (authorityLabel: string, items: any[]) => {
    if (!groupMap.has(authorityLabel)) {
      groupMap.set(authorityLabel, []);
    }

    const groupItems = groupMap.get(authorityLabel) as Array<{ label: string, uri?: string | null }>;

    for (const item of items || []) {
      const label = String(item?.label || '').trim();
      const uri = String(item?.uri || '').trim();

      if (!label || !uri) {
        continue;
      }

      const key = `${authorityLabel}|${uri}`;
      if (seen.has(key)) {
        continue;
      }

      seen.add(key);
      groupItems.push({ label, uri });
    }
  };

  pushItems('Getty AAT', props.entity?.classification || []);
  pushItems('Getty AAT', props.entity?.materialDetails || []);
  pushItems('PeriodO', props.entity?.periods || []);

  return Array.from(groupMap.entries())
    .map(([authorityLabel, items]) => ({ authorityLabel, items }))
    .filter((group) => group.items.length > 0);
});

const hasEntityLinks = $computed(() => {
  return Boolean(entityJsonLink || utils.validUrl(props.entity?.uri) || externalEntityLink || (props.entity?.sameAs || []).length);
});

const externalEntityLink = $computed(() => {
  const links = (props.entity?.sameAs || []).filter((link: string) => utils.validUrl(link));
  if (links.length) {
    return links[0];
  }

  if (utils.validUrl(props.entity?.uri)) {
    return props.entity.uri;
  }

  if (utils.validUrl(props.entity?.sourceGraph)) {
    return props.entity.sourceGraph;
  }

  return '';
});

const entityJsonLink = $computed(() => {
  const id = String(props.entityId || '').trim();
  if (!id || !apiUrl) {
    return '';
  }

  return `${apiUrl}/heritage-entities/${encodeURIComponent(id)}`;
});

const citationLink = $computed(() => {
  if (utils.validUrl(externalEntityLink)) {
    return externalEntityLink;
  }

  if (utils.validUrl(props.entity?.uri)) {
    return props.entity.uri;
  }

  return '';
});

const remainingExternalLinks = $computed(() => {
  const primary = String(externalEntityLink || '').trim();

  return (props.entity?.sameAs || [])
    .filter((link: string) => utils.validUrl(link))
    .filter((link: string) => link.trim() !== primary);
});

const toggleCiting = (val?: boolean) => {
  isCiting = typeof val === 'boolean' ? val : !isCiting;
  if (isCiting) {
    nextTick(() => {
      const citeDiv = document.getElementById(citeRefId) as any;
      citeDiv?.focus?.();
      if (typeof citeDiv?.select === 'function' && !utils.isMobile()) {
        citeDiv.select();
      }
    });
  }
};

const externalResourceHref = (uri?: string): string => {
  if (!uri) {
    return '';
  }

  return uri.endsWith('.html') ? uri : `${uri}.html`;
};
</script>
