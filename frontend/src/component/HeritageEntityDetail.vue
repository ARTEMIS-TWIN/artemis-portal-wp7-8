<template>
  <div>
    <div
      v-if="loading && !entity"
      class="py-3x px-base mx-auto max-w-screen-xl"
    >
      <h1 class="text-lg text-center text-midGray">
        Loading heritage entity..
      </h1>
    </div>

    <div v-else-if="error" class="py-3x px-base mx-auto max-w-screen-xl">
      <p class="text-red text-mmd text-center">{{ error }}</p>
    </div>

    <div v-else-if="entity" class="text-mmd detail-page">
      <div class="detail-page__map">
        <heritage-entity-map v-if="hasMapData" :entity="entity" />
        <div v-else class="pb-md"></div>
      </div>

      <article class="py-xl px-base mx-auto max-w-screen-xl lg:flex resource-shell detail-page__shell">
        <div class="pt-xl w-full lg:w-2/3 lg:pr-2x px-base detail-page__main">
          <div class="mt-xs detail-page__title-block">
            <div class="flex justify-between items-start gap-base detail-page__title-row">
              <div>
                <p class="text-sm uppercase tracking-wide text-midGray mb-sm detail-page__kicker">Heritage Entity</p>
                <h1 class="text-2xl">{{ entity.label || 'Untitled entity' }}</h1>
                <p v-if="entity.entityType" class="mt-sm">
                  <strong class="mr-sm">Entity type</strong>{{ entity.entityType }}
                </p>
              </div>
            </div>
          </div>

          <div v-if="entity.description" class="mt-2x pt-xs mb-3x lg:mb-2x detail-page__description">
            <h3 class="text-lg font-bold mb-md">
              <i class="fas fa-info-circle mr-sm" />
              Description
            </h3>
            <span class="whitespace-pre-line break-word">{{ entity.description }}</span>
          </div>

          <heritage-entity-main class="mt-lg" :entity="entity" />
        </div>

        <div class="w-full lg:w-1/3 pt-xl lg:pl-2x lg:border-l-base border-gray px-base pb-xl detail-page__aside">
          <heritage-entity-sidebar :entity="entity" :entityId="currentEntityId" />
        </div>
      </article>
    </div>

    <div v-else class="py-3x px-base mx-auto max-w-screen-xl">
      <p class="text-mmd text-center">Heritage entity not found.</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import axios from 'axios';
import { watch } from 'vue';
import { $ref, $computed } from 'vue/macros';
import { useRoute, useRouter, onBeforeRouteLeave } from 'vue-router';
import { generalModule } from '@/store/modules';
import HeritageEntityMap from '@/component/HeritageEntity/Map.vue';
import HeritageEntityMain from '@/component/HeritageEntity/Main.vue';
import HeritageEntitySidebar from '@/component/HeritageEntity/Sidebar.vue';

const route = useRoute();
const router = useRouter();

let entity: any = $ref(null);
let error = $ref('');
let loading = $ref(false);

const hasMapData = $computed(() => {
  return entity?.location?.lat !== undefined
    && entity?.location?.lat !== null
    && entity?.location?.lon !== undefined
    && entity?.location?.lon !== null;
});
const currentEntityId = $computed(() => String(route.params.id || ''));

const loadEntity = async (id: string) => {
  loading = true;
  error = '';
  entity = null;

  try {
    const res = await axios.get(process.env.apiUrl + '/heritage-entities/' + encodeURIComponent(id));
    entity = res?.data?.data || null;

    if (!entity) {
      router.replace('/404');
      return;
    }

    generalModule.setMeta({
      title: entity?.label || 'Heritage Entity',
      description: entity?.description || 'Heritage entity detail',
    });
  } catch (ex: any) {
    if (ex?.response?.status === 404) {
      router.replace('/404');
      return;
    }

    error = 'Unable to load heritage entity.';
  } finally {
    loading = false;
  }
};

const unwatch = watch(() => route.params.id, (id: any) => {
  if (typeof id === 'string' && id.trim()) {
    loadEntity(id);
  }
}, { immediate: true });

onBeforeRouteLeave(unwatch);
</script>
