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
        <a :href="externalResourceHref(resource.uri)" target="_blank" class="text-blue hover:underline break-word text-md">
          {{ resource.uri }}
        </a>
      </div>
    </section>

    <section v-if="entity.sameAs?.length" :class="sectionClass">
      <h3 class="text-lg font-bold mb-lg">
        <i class="fas fa-link mr-sm"></i>
        External Links
      </h3>

      <div
        v-for="(link, key) in entity.sameAs"
        :key="key"
        class="mb-md"
      >
        <a :href="link" target="_blank" class="text-blue hover:underline break-word">
          {{ link }}
        </a>
      </div>
    </section>

    <section v-if="authoritySummary.length" :class="sectionClass">
      <h3 class="text-lg font-bold mb-lg">
        <i class="fas fa-project-diagram mr-sm"></i>
        Authorities
      </h3>

      <div
        v-for="(item, key) in authoritySummary"
        :key="key"
        class="mb-md"
      >
        <strong class="block mb-xs">{{ item.label }}</strong>
        <a :href="item.uri" target="_blank" class="text-blue hover:underline break-word">
          {{ item.uri }}
        </a>
      </div>
    </section>

    <section v-if="hasCoordinates" :class="sectionClass">
      <h3 class="text-lg font-bold mb-lg">
        <i class="fas fa-crosshairs mr-sm"></i>
        Coordinates
      </h3>

      <p>
        {{ entity.location.lat }}, {{ entity.location.lon }}
      </p>
    </section>
  </div>
</template>

<script setup lang="ts">
import { $computed } from 'vue/macros';
import BLink from '@/component/Base/Link.vue';

const props = defineProps<{
  entity: any,
}>();

const sectionClass = 'py-base pb-sm mb-md';

const authoritySummary = $computed(() => {
  const items: Array<{ label: string, uri: string }> = [];
  const seen = new Set<string>();

  const pushItem = (label: string, uri?: string) => {
    if (!uri || seen.has(uri)) {
      return;
    }

    seen.add(uri);
    items.push({ label, uri });
  };

  (props.entity?.classification || []).forEach((item: any) => {
    pushItem(`Getty AAT classification${item.label ? `: ${item.label}` : ''}`, item?.uri);
  });

  (props.entity?.materialDetails || []).forEach((item: any) => {
    pushItem(`Getty AAT material${item.label ? `: ${item.label}` : ''}`, item?.uri);
  });

  (props.entity?.periods || []).forEach((item: any) => {
    pushItem(`PeriodO authority${item.label ? `: ${item.label}` : ''}`, item?.uri);
  });

  return items;
});

const hasCoordinates = $computed(() => {
  return props.entity?.location?.lat !== undefined
    && props.entity?.location?.lat !== null
    && props.entity?.location?.lon !== undefined
    && props.entity?.location?.lon !== null;
});

const externalResourceHref = (uri?: string): string => {
  if (!uri) {
    return '';
  }

  return uri.endsWith('.html') ? uri : `${uri}.html`;
};
</script>
