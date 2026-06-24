<template>
  <div class="page-main services-page">
    <div class="content-grid" :class="{ 'content-grid--services-full': showCategoryOverview }">
      <aside v-if="!showCategoryOverview">
        <div class="filter-panel services-filter-panel">
          <h2 class="filter-heading">Filters</h2>

          <div v-if="activeCategories.length" class="services-category-recall mb-lg">
            <div class="services-category-recall__header">
              <p class="services-category-recall__title">Category context</p>
              <button type="button" class="services-category-recall__clear" @click="setCategoryFilters([], searchMode)">
                Clear categories
              </button>
            </div>
            <div class="services-category-recall__list">
              <button
                v-for="category in activeCategories"
                :key="`recall-filter-${category}`"
                type="button"
                class="services-category-recall__chip"
                @click="toggleCategoryFilter(category)"
                :title="`Remove ${category}`"
              >
                <img
                  v-if="categoryImage(category)"
                  :src="categoryImage(category)"
                  :alt="`${category} thumbnail`"
                  class="services-category-recall__thumb"
                >
                <span class="services-category-recall__name">{{ category }}</span>
                <i class="fas fa-times services-category-recall__x"></i>
              </button>
            </div>
          </div>

          <div class="services-filters mt-lg">
            <list-accordion
              v-if="categoryBuckets.length"
              :title="facetTitle('Categories', activeCategories.length)"
              :initShow="false"
            >
              <div class="relative">
                <div
                  v-for="bucket in categoryBuckets"
                  :key="`category-bucket-${bucket.value}`"
                  class="filter-item text-md"
                  :class="{ 'is-active': activeCategories.includes(bucket.value) }"
                  @click="toggleCategoryFilter(bucket.value)"
                >
                  <span class="flex-grow break-word pr-lg">{{ bucket.value }}</span>
                  <span v-if="activeCategories.includes(bucket.value)" class="filter-count services-filter-remove">
                    <i class="fas fa-times"></i>
                  </span>
                  <span class="filter-count">{{ bucket.count }}</span>
                </div>
              </div>
            </list-accordion>

            <list-accordion
              v-if="providerBuckets.length"
              :title="facetTitle('Providers', selectedProviders.length)"
              :initShow="false"
            >
              <div class="relative">
                <div
                  v-for="bucket in providerBuckets"
                  :key="`provider-${bucket.value}`"
                  class="filter-item text-md"
                  :class="{ 'is-active': selectedProviders.includes(bucket.value) }"
                  @click="toggleSelectedProvider(bucket.value)"
                >
                  <span class="flex-grow break-word pr-lg">{{ bucket.value }}</span>
                  <span v-if="selectedProviders.includes(bucket.value)" class="filter-count services-filter-remove">
                    <i class="fas fa-times"></i>
                  </span>
                  <span class="filter-count">{{ bucket.count }}</span>
                </div>
              </div>
            </list-accordion>

            <list-accordion
              v-if="functionalityBuckets.length"
              :title="facetTitle('Functionalities', selectedFunctionalities.length)"
              :initShow="false"
            >
              <div class="relative">
                <div
                  v-for="bucket in functionalityBuckets"
                  :key="`functionality-${bucket.value}`"
                  class="filter-item text-md"
                  :class="{ 'is-active': selectedFunctionalities.includes(bucket.value) }"
                  @click="toggleSelectedFunctionality(bucket.value)"
                >
                  <span class="flex-grow break-word pr-lg">{{ bucket.value }}</span>
                  <span v-if="selectedFunctionalities.includes(bucket.value)" class="filter-count services-filter-remove">
                    <i class="fas fa-times"></i>
                  </span>
                  <span class="filter-count">{{ bucket.count }}</span>
                </div>
              </div>
            </list-accordion>

            <list-accordion
              v-if="formatBuckets.length"
              :title="facetTitle('Formats', selectedFormats.length)"
              :initShow="false"
            >
              <div class="relative">
                <div
                  v-for="bucket in formatBuckets"
                  :key="`format-${bucket.value}`"
                  class="filter-item text-md"
                  :class="{ 'is-active': selectedFormats.includes(bucket.value) }"
                  @click="toggleSelectedFormat(bucket.value)"
                >
                  <span class="flex-grow break-word pr-lg">{{ bucket.value }}</span>
                  <span v-if="selectedFormats.includes(bucket.value)" class="filter-count services-filter-remove">
                    <i class="fas fa-times"></i>
                  </span>
                  <span class="filter-count">{{ bucket.count }}</span>
                </div>
              </div>
            </list-accordion>

            <list-accordion
              v-if="languageBuckets.length"
              :title="facetTitle('Languages', selectedLanguages.length)"
              :initShow="false"
            >
              <div class="relative">
                <div
                  v-for="bucket in languageBuckets"
                  :key="`language-${bucket.value}`"
                  class="filter-item text-md"
                  :class="{ 'is-active': selectedLanguages.includes(bucket.value) }"
                  @click="toggleSelectedLanguage(bucket.value)"
                >
                  <span class="flex-grow break-word pr-lg">{{ bucket.value }}</span>
                  <span v-if="selectedLanguages.includes(bucket.value)" class="filter-count services-filter-remove">
                    <i class="fas fa-times"></i>
                  </span>
                  <span class="filter-count">{{ bucket.count }}</span>
                </div>
              </div>
            </list-accordion>

            <list-accordion
              v-if="intendedForBuckets.length"
              :title="facetTitle('Intended For', selectedIntendedFor.length)"
              :initShow="false"
            >
              <div class="relative">
                <div
                  v-for="bucket in intendedForBuckets"
                  :key="`intended-${bucket.value}`"
                  class="filter-item text-md"
                  :class="{ 'is-active': selectedIntendedFor.includes(bucket.value) }"
                  @click="toggleSelectedIntendedFor(bucket.value)"
                >
                  <span class="flex-grow break-word pr-lg">{{ bucket.value }}</span>
                  <span v-if="selectedIntendedFor.includes(bucket.value)" class="filter-count services-filter-remove">
                    <i class="fas fa-times"></i>
                  </span>
                  <span class="filter-count">{{ bucket.count }}</span>
                </div>
              </div>
            </list-accordion>
          </div>

          <button
            v-if="hasActiveFilters"
            type="button"
            class="mb-lg inline-flex items-center rounded-full border-base border-gray px-base py-sm text-md transition-colors duration-200 hover:bg-lightGray mt-lg"
            @click="clearFilters"
          >
            Clear filters
          </button>
        </div>
      </aside>

      <section class="result-panel">
        <div class="result-layout flex flex-col">
          <div class="result-toolbar result-toolbar--aligned flex flex-col gap-4">
            <h2 class="catalogue-heading text-2xl">Services</h2>
            <div class="services-mode-toggle">
              <button
                type="button"
                class="services-mode-toggle__button"
                :class="{ 'services-mode-toggle__button--active': isByCategoryMode }"
                @click="setSearchMode('category')"
              >
                By category
              </button>
              <button
                type="button"
                class="services-mode-toggle__button"
                :class="{ 'services-mode-toggle__button--active': isAllCategoriesMode }"
                @click="setSearchMode('all')"
              >
                All Services
              </button>
            </div>

            <form class="w-full filter-search" @submit.prevent="utils.blurMobile()">
              <div class="flex items-center w-full min-w-0">
                <div class="filter-search__controls relative flex flex-col w-full min-w-0 filter-search__controls--stacked">
                  <div class="filter-search__input-wrap flex w-full min-w-0">
                    <input
                      ref="serviceInput"
                      v-model="filter"
                      class="filter-search__input flex-1 min-w-0 outline-none border-gray border-base border-r-0 placeholder-darkGray bg-white py-sm px-md rounded-l-2xl text-md"
                      type="text"
                      :placeholder="isByCategoryMode ? 'Search categories or services...' : 'Search all services...'"
                    >
                    <button
                      class="filter-search__submit py-xs px-base text-sm rounded-r-2xl transition-all duration-300 focus:outline-none bg-blue border-blue border-base hover:bg-blue-80"
                    >
                      <i class="fas fa-search text-white"></i>
                    </button>
                  </div>
                </div>
              </div>
            </form>
            <p class="text-sm text-midGray">
              Showing {{ paginationStart }}-{{ paginationEnd }} of {{ activeServicesSorted.length }} services
            </p>
            <div class="result-toolbar__row flex flex-wrap items-center justify-between gap-4">
              <div v-if="!showCategoryOverview" class="services-sort-inline">
                <label class="services-sidebar__label mb-none">Sort</label>
                <select v-model="sortBy" class="services-sort-select border-base border-gray p-sm">
                  <option value="relevance">Relevance</option>
                  <option value="title">Title A-Z</option>
                  <option value="provider">Provider A-Z</option>
                </select>
              </div>
              <div class="flex" v-if="totalPages > 1">
                <span
                  v-for="(token, key) in paginationTokens"
                  :key="`top-page-${key}`"
                  class="leading-1 mx-none my-none py-md px-base text-sm border-base transition-all duration-300 services-page-token"
                  :class="pageClasses(token)"
                  @click.prevent="changePage(token)"
                >{{ token }}</span>
              </div>
            </div>
          </div>

          <div v-if="loading" class="mt-2x">
            <div class="services-skeleton-grid">
              <div v-for="n in 6" :key="`services-skeleton-${n}`" class="services-skeleton-card"></div>
            </div>
          </div>

          <div v-else class="result-list-wrap">
            <div v-if="showCategoryOverview">
              <h2 v-if="!visibleCategories.length" class="mt-3x text-lg">
                No categories matching "{{ filter.trim() }}"
              </h2>

              <div v-else class="services-category-grid" :class="{ 'services-category-grid--wide': isByCategoryMode }">
                <button
                  v-for="category in visibleCategories"
                  :key="category.title"
                  type="button"
                  class="services-category-card"
                  @click="openCategory(category.title)"
                >
                  <img
                    v-if="categoryImage(category.title)"
                    :src="categoryImage(category.title)"
                    :alt="`${category.title} category`"
                    class="services-category-image"
                  >
                  <div class="services-category-body" :class="{ 'services-category-body-no-image': !categoryImage(category.title) }">
                    <h2 class="text-lg mb-sm">{{ category.title }}</h2>
                    <p class="text-sm text-midGray">{{ category.count }} services</p>
                  </div>
                </button>
              </div>
            </div>

            <div v-else>
              <h3 v-if="!activeServicesSorted.length" class="text-lg">
                No services matching the current search and filters.
              </h3>

              <div v-else class="space-y-2x">
                <article
                  v-for="item in pagedServices"
                  :key="serviceRouteId(item)"
                  class="services-item-card p-base transition-all duration-300 mb-base"
                >
                  <div class="services-list-card">
                    <div class="services-list-card__media">
                      <img
                        v-if="serviceCardImage(item)"
                        :src="serviceCardImage(item)"
                        :alt="`${item.topic || item.title || 'Service'} image`"
                        class="services-list-card__image"
                      >
                      <div v-else class="services-list-card__image-fallback">
                        <i class="fas fa-cubes"></i>
                      </div>
                    </div>

                    <div class="services-list-card__body text-mmd">
                      <b-link :to="serviceDetailLink(item)" class="hover:underline">
                        <h3 class="text-blue text-lg font-bold mb-base app-section-title">
                          {{ item.title || 'Untitled service' }}
                        </h3>
                      </b-link>

                      <p
                        v-if="item.description"
                        class="mb-sm text-mmd text-midGray2 services-description"
                        v-html="utils.autolinkText(utils.escHtml(trimServiceDescription(item.description)))"
                      ></p>

                      <div class="mb-sm text-mmd text-midGray2 services-meta-row">
                        <p v-if="item.topic">
                          <i class="fas fa-tags mr-xs"></i>
                          <b>Topic</b>: {{ item.topic }}
                        </p>
                        <p v-if="serviceProviderName(item)">
                          <i class="fas fa-building mr-xs"></i>
                          <b>Provider</b>: {{ serviceProviderName(item) }}
                        </p>
                        <p v-if="serviceLanguagesDisplay(item)">
                          <i class="fas fa-language mr-xs"></i>
                          <b>Languages</b>: {{ serviceLanguagesDisplay(item) }}
                        </p>
                      </div>

                      <div class="services-card-actions mt-md">
                        <b-link :to="serviceDetailLink(item)" class="services-action services-action--primary services-action--sleek">
                          View details
                        </b-link>
                        <b-link
                          v-if="utils.validUrl(item.url)"
                          :href="item.url"
                          target="_blank"
                          class="services-action services-action--ghost services-action--sleek"
                        >
                          <i class="fas fa-external-link-alt mr-xs"></i>
                          Go to service
                        </b-link>
                      </div>
                    </div>
                  </div>
                </article>
              </div>
            </div>
          </div>

          <div class="flex" v-if="totalPages > 1 && !showCategoryOverview">
            <span
              v-for="(token, key) in paginationTokens"
              :key="`bottom-page-${key}`"
              class="leading-1 mx-none my-none py-md px-base text-sm border-base transition-all duration-300 services-page-token"
              :class="pageClasses(token)"
              @click.prevent="changePage(token)"
            >{{ token }}</span>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { $ref, $computed } from 'vue/macros';
import { generalModule } from "@/store/modules";
import utils from '@/utils/utils';
import BLink from '@/component/Base/Link.vue';
import ListAccordion from '@/component/List/Accordion.vue';

type ServiceItem = {
  id?: string | number;
  uri?: string;
  title?: string;
  topic?: string;
  description?: string;
  url?: string;
  img?: string;
  provider?: any;
  providers?: any;
  functionalities?: string[];
  usedForActivities?: string[];
  intendedFor?: string[];
  technicalSupport?: string[];
  languages?: string[];
  composedOf?: string[];
  consumedMedia?: string[];
  producedMedia?: string[];
  consumedFormats?: string[];
  producedFormats?: string[];
  documents?: Array<{ uri?: string; label?: string }>;
  sourcePath?: string;
  importSource?: string;
};

type CategorySummary = {
  title: string;
  count: number;
  items: ServiceItem[];
};
type OptionBucket = {
  value: string;
  count: number;
};

let filter: string = $ref('');
let serviceInput: any = $ref(null);
let loading: boolean = $ref(true);
let sortBy: string = $ref('relevance');
let categoryImageMap: Record<string, string> = $ref({});
let categoryImageChecked: Record<string, boolean> = $ref({});

let selectedProviders: string[] = $ref([]);
let selectedFunctionalities: string[] = $ref([]);
let selectedFormats: string[] = $ref([]);
let selectedLanguages: string[] = $ref([]);
let selectedIntendedFor: string[] = $ref([]);
let currentPage: number = $ref(1);

const route = useRoute();
const router = useRouter();
const perPage = 10;

const assets: string = $computed(() => generalModule.getAssetsDir);
const explicitCategoryImages: Record<string, string> = {
  '3d visualisation': `${assets}/services/3D_Visualization.png`,
  '3d visualization': `${assets}/services/3D_Visualization.png`,
  'ar/vr visualisation': `${assets}/services/AR_VR_Visualization.png`,
  'ar/vr visualization': `${assets}/services/AR_VR_Visualization.png`,
  'geospatial visualisation': `${assets}/services/Geospacial_Visualization.png`,
  'geospatial visualization': `${assets}/services/Geospacial_Visualization.png`,
  'semantic enrichment': `${assets}/services/Semantic_Enrichement.png`,
  'semantic enrichement': `${assets}/services/Semantic_Enrichement.png`,
};
const services: Array<ServiceItem> = $computed(() => generalModule.getServices || []);
const normalizedFilter = $computed(() => filter.toLowerCase().trim());
const hasSearchQuery = $computed(() => normalizedFilter !== '');
const searchMode = $computed(() => {
  return normalizeText(route.query.mode).toLowerCase() === 'all' ? 'all' : 'category';
});
const isByCategoryMode = $computed(() => searchMode === 'category');
const isAllCategoriesMode = $computed(() => searchMode === 'all');

const normalizeText = (value: unknown): string => String(value || '').trim();

const listValues = (value: unknown): string[] => {
  if (!Array.isArray(value)) {
    return [];
  }

  return value
    .map((entry) => normalizeText(entry))
    .filter((entry) => entry !== '');
};

const makeBuckets = (items: ServiceItem[], extractor: (service: ServiceItem) => string[]): OptionBucket[] => {
  const counts = new Map<string, number>();

  items.forEach((service) => {
    const uniqueValues = Array.from(new Set(extractor(service).map((value) => normalizeText(value)).filter(Boolean)));
    uniqueValues.forEach((value) => counts.set(value, (counts.get(value) || 0) + 1));
  });

  return Array.from(counts.entries())
    .map(([value, count]) => ({ value, count }))
    .sort((a, b) => (b.count - a.count) || a.value.localeCompare(b.value));
};

const facetTitle = (title: string, selectedCount: number): string => {
  return selectedCount > 0 ? `${title} (${selectedCount})` : title;
};

const providerNamesFrom = (value: unknown, acc: Set<string>): void => {
  if (!value) {
    return;
  }

  if (Array.isArray(value)) {
    value.forEach((entry) => providerNamesFrom(entry, acc));
    return;
  }

  if (typeof value !== 'object') {
    return;
  }

  const obj = value as Record<string, any>;
  [obj.name, obj.label, obj.title, obj.organizationName].forEach((entry) => {
    const name = normalizeText(entry);
    if (name) {
      acc.add(name);
    }
  });

  providerNamesFrom(obj.provider, acc);
  providerNamesFrom(obj.providers, acc);
  providerNamesFrom(obj.children, acc);
  providerNamesFrom(obj.members, acc);
  providerNamesFrom(obj.organizations, acc);
};

const providerNamesForService = (service: ServiceItem): string[] => {
  const names = new Set<string>();
  providerNamesFrom(service.provider, names);
  providerNamesFrom((service as any).providers, names);
  return Array.from(names);
};

const groupedServices = $computed(() => {
  const grouped: Record<string, ServiceItem[]> = {};

  services.forEach((service: ServiceItem) => {
    const category = normalizeText(service.topic) || 'Uncategorized';

    if (!grouped[category]) {
      grouped[category] = [];
    }

    grouped[category].push(service);
  });

  return grouped;
});

const allCategories = $computed(() => {
  return Object.entries(groupedServices)
    .map(([title, items]) => ({
      title,
      count: items.length,
      items,
    }))
    .sort((a, b) => a.title.localeCompare(b.title));
});

const serviceMatchesFilter = (service: ServiceItem, filterValue: string): boolean => {
  if (filterValue === '') {
    return true;
  }

  const title = normalizeText(service.title).toLowerCase();
  const description = normalizeText(service.description).toLowerCase();
  const topic = normalizeText(service.topic).toLowerCase();
  const url = normalizeText(service.url).toLowerCase();
  const providers = providerNamesForService(service).join(' ').toLowerCase();

  return title.includes(filterValue)
    || description.includes(filterValue)
    || topic.includes(filterValue)
    || url.includes(filterValue)
    || providers.includes(filterValue);
};

const visibleCategories = $computed(() => {
  if (!hasSearchQuery) {
    return allCategories;
  }

  return allCategories.filter((category) => {
    if (category.title.toLowerCase().includes(normalizedFilter)) {
      return true;
    }

    return category.items.some((service) => serviceMatchesFilter(service, normalizedFilter));
  });
});

const activeCategories = $computed(() => {
  const selected = normalizeText(route.query.category);
  if (!selected) {
    return [];
  }

  const selectedSet = new Set(selected.split('|').map((value) => normalizeText(value)).filter(Boolean));
  return allCategories
    .map((category) => category.title)
    .filter((title) => selectedSet.has(title));
});

const categoryBuckets = $computed(() => {
  return allCategories.map((category) => ({
    value: category.title,
    count: category.count,
  }));
});

const servicesInScope = $computed(() => {
  if (activeCategories.length > 0) {
    const combined: ServiceItem[] = [];
    activeCategories.forEach((category) => {
      combined.push(...(groupedServices[category] || []));
    });
    return combined;
  }

  if (isAllCategoriesMode) {
    return services;
  }

  if (hasSearchQuery) {
    return services;
  }

  return [];
});

const showCategoryOverview = $computed(() => {
  return isByCategoryMode
    && activeCategories.length === 0
    && !hasSearchQuery;
});

const activeServices = $computed(() => {
  return servicesInScope.filter((service) => serviceMatchesFilter(service, normalizedFilter));
});

const providerBuckets = $computed(() => makeBuckets(activeServices, (service) => providerNamesForService(service)));
const functionalityBuckets = $computed(() => makeBuckets(activeServices, (service) => listValues(service.functionalities)));
const formatBuckets = $computed(() => makeBuckets(activeServices, (service) => [
  ...listValues(service.consumedFormats),
  ...listValues(service.producedFormats),
]));
const languageBuckets = $computed(() => makeBuckets(activeServices, (service) => listValues(service.languages)));
const intendedForBuckets = $computed(() => makeBuckets(activeServices, (service) => listValues(service.intendedFor)));

const hasActiveFilters = $computed(() => {
  return selectedProviders.length > 0
    || selectedFunctionalities.length > 0
    || selectedFormats.length > 0
    || selectedLanguages.length > 0
    || selectedIntendedFor.length > 0;
});

const activeServicesFiltered = $computed(() => {
  return activeServices.filter((service) => {
    if (selectedProviders.length > 0) {
      const values = new Set(providerNamesForService(service));
      if (!selectedProviders.every((provider) => values.has(provider))) {
        return false;
      }
    }

    if (selectedFunctionalities.length > 0) {
      const values = new Set(listValues(service.functionalities));
      if (!selectedFunctionalities.every((value) => values.has(value))) {
        return false;
      }
    }

    if (selectedFormats.length > 0) {
      const values = new Set([
        ...listValues(service.consumedFormats),
        ...listValues(service.producedFormats),
      ]);
      if (!selectedFormats.every((format) => values.has(format))) {
        return false;
      }
    }

    if (selectedLanguages.length > 0) {
      const values = new Set(listValues(service.languages));
      if (!selectedLanguages.every((language) => values.has(language))) {
        return false;
      }
    }

    if (selectedIntendedFor.length > 0) {
      const values = new Set(listValues(service.intendedFor));
      if (!selectedIntendedFor.every((item) => values.has(item))) {
        return false;
      }
    }

    return true;
  });
});

const activeServicesSorted = $computed(() => {
  const filtered = activeServicesFiltered;

  if (sortBy === 'title') {
    return filtered.slice().sort((a, b) => normalizeText(a.title).localeCompare(normalizeText(b.title)));
  }

  if (sortBy === 'provider') {
    return filtered.slice().sort((a, b) => serviceProviderName(a).localeCompare(serviceProviderName(b)));
  }

  return filtered;
});

const totalPages = $computed(() => {
  return Math.max(1, Math.ceil(activeServicesSorted.length / perPage));
});

const pagedServices = $computed(() => {
  const start = (currentPage - 1) * perPage;
  return activeServicesSorted.slice(start, start + perPage);
});

const paginationStart = $computed(() => {
  if (activeServicesSorted.length === 0) {
    return 0;
  }

  return (currentPage - 1) * perPage + 1;
});

const paginationEnd = $computed(() => {
  if (activeServicesSorted.length === 0) {
    return 0;
  }

  return Math.min(currentPage * perPage, activeServicesSorted.length);
});

const paginationTokens = $computed(() => {
  const total = totalPages;
  const page = currentPage;

  if (total <= 7) {
    return Array.from({ length: total }, (_, index) => index + 1);
  }

  const tokens: Array<number | string> = [1];
  const start = Math.max(2, page - 1);
  const end = Math.min(total - 1, page + 1);

  if (start > 2) {
    tokens.push('...');
  }

  for (let i = start; i <= end; i += 1) {
    tokens.push(i);
  }

  if (end < total - 1) {
    tokens.push('...');
  }

  tokens.push(total);
  return tokens;
});

const openCategory = (category: string) => {
  setCategoryFilters([category], 'category');
};

const setSearchMode = (mode: 'category' | 'all') => {
  clearFilters();
  currentPage = 1;
  filter = '';
  const query: Record<string, any> = {
    ...route.query,
    mode,
  };

  if (mode === 'all') {
    delete query.category;
  }
  if (mode === 'category') {
    delete query.category;
  }

  router.replace({
    path: '/services',
    query,
  });
};

const setCategoryFilters = (categories: string[], mode?: 'category' | 'all') => {
  currentPage = 1;
  const query: Record<string, any> = {
    ...route.query,
    mode: mode || searchMode,
  };

  const cleaned = Array.from(new Set(categories.map((value) => normalizeText(value)).filter(Boolean)));
  if (cleaned.length > 0) {
    query.category = cleaned.join('|');
  } else {
    delete query.category;
  }

  router.replace({
    path: '/services',
    query,
  });
};

const toggleCategoryFilter = (category: string) => {
  const next = activeCategories.includes(category)
    ? activeCategories.filter((value) => value !== category)
    : [...activeCategories, category];
  setCategoryFilters(next);
};

const clearFilters = () => {
  selectedProviders = [];
  selectedFunctionalities = [];
  selectedFormats = [];
  selectedLanguages = [];
  selectedIntendedFor = [];
};

const toggleInSelection = (selection: string[], value: string, checked: boolean): string[] => {
  if (checked) {
    return Array.from(new Set([...selection, value]));
  }

  return selection.filter((item) => item !== value);
};

const toggleSelectedLanguage = (value: string) => {
  selectedLanguages = toggleInSelection(selectedLanguages, value, !selectedLanguages.includes(value));
};

const toggleSelectedIntendedFor = (value: string) => {
  selectedIntendedFor = toggleInSelection(selectedIntendedFor, value, !selectedIntendedFor.includes(value));
};

const toggleSelectedProvider = (value: string) => {
  selectedProviders = toggleInSelection(selectedProviders, value, !selectedProviders.includes(value));
};

const toggleSelectedFunctionality = (value: string) => {
  selectedFunctionalities = toggleInSelection(selectedFunctionalities, value, !selectedFunctionalities.includes(value));
};

const toggleSelectedFormat = (value: string) => {
  selectedFormats = toggleInSelection(selectedFormats, value, !selectedFormats.includes(value));
};

const setPage = (page: number) => {
  currentPage = Math.max(1, Math.min(page, totalPages));
};

const changePage = (token: number | string) => {
  if (typeof token !== 'number') {
    return;
  }

  setPage(token);
};

const pageClasses = (token: number | string): string => {
  if (token === '...') {
    return 'border-gray text-midGray';
  }

  return token === currentPage ? 'bg-blue border-blue text-white' : 'border-gray text-blue hover:bg-gray-30 cursor-pointer';
};

const imageExists = (url: string): Promise<boolean> => {
  return new Promise((resolve) => {
    const img = new Image();
    img.onload = () => resolve(true);
    img.onerror = () => resolve(false);
    img.src = url;
  });
};

const categoryFilenameCandidates = (category: string): string[] => {
  const value = category.trim();
  const underscored = value.replace(/\s+/g, '_');
  const underscoredWithSlash = value.replace(/[\/\s]+/g, '_');
  const alnumUnderscore = value.replace(/[^A-Za-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
  const normalizedLower = alnumUnderscore.toLowerCase();

  const variantForms = new Set<string>([
    normalizedLower,
    normalizedLower
      .replace(/visualisation/g, 'visualization')
      .replace(/geospatial/g, 'geospacial')
      .replace(/enrichment/g, 'enrichement'),
    normalizedLower
      .replace(/visualization/g, 'visualisation')
      .replace(/geospacial/g, 'geospatial')
      .replace(/enrichement/g, 'enrichment'),
  ]);

  const bases = Array.from(new Set([
    value,
    underscored,
    underscoredWithSlash,
    alnumUnderscore,
    underscored.toLowerCase(),
    underscoredWithSlash.toLowerCase(),
    alnumUnderscore.toLowerCase(),
    ...Array.from(variantForms),
    ...Array.from(variantForms).map((variant) => variant.replace(/_/g, ' ')),
  ])).filter((base) => base.length > 0);

  const exts = ['png', 'jpg', 'jpeg', 'webp', 'svg', 'gif'];
  const paths: string[] = [];

  bases.forEach((base) => {
    exts.forEach((ext) => {
      paths.push(`${assets}/services/${base}.${ext}`);
    });
  });

  return paths;
};

const resolveCategoryImage = async (category: string): Promise<void> => {
  if (categoryImageChecked[category]) {
    return;
  }

  categoryImageChecked = {
    ...categoryImageChecked,
    [category]: true,
  };

  const explicitImage = explicitCategoryImages[normalizeText(category).toLowerCase()];
  if (explicitImage) {
    categoryImageMap = {
      ...categoryImageMap,
      [category]: explicitImage,
    };
    return;
  }

  const candidates = categoryFilenameCandidates(category);

  for (const candidate of candidates) {
    // eslint-disable-next-line no-await-in-loop
    const exists = await imageExists(candidate);
    if (exists) {
      categoryImageMap = {
        ...categoryImageMap,
        [category]: candidate,
      };
      return;
    }
  }
};

const refreshCategoryImages = async (): Promise<void> => {
  const categories = allCategories.map((category) => category.title);
  for (const category of categories) {
    // eslint-disable-next-line no-await-in-loop
    await resolveCategoryImage(category);
  }
};

const categoryImage = (category: string): string => {
  return categoryImageMap[category] || '';
};

const serviceRouteId = (item: ServiceItem): string => {
  if (item.id !== undefined && item.id !== null && normalizeText(item.id) !== '') {
    return normalizeText(item.id);
  }

  if (normalizeText(item.uri) !== '') {
    return normalizeText(item.uri);
  }

  if (normalizeText(item.url) !== '') {
    return normalizeText(item.url);
  }

  return normalizeText(item.title) || 'service';
};

const serviceDetailLink = (item: ServiceItem): string => {
  return utils.paramsToString(`/services/${encodeURIComponent(serviceRouteId(item))}`, {
    category: activeCategories.length > 0 ? activeCategories.join('|') : String(item.topic || ''),
  });
};

const serviceProviderName = (item: ServiceItem): string => {
  return providerNamesForService(item)[0] || '';
};

const serviceLanguagesDisplay = (item: ServiceItem): string => {
  const languages = listValues(item.languages);
  return languages.join(', ');
};

const serviceCardImage = (item: ServiceItem): string => {
  if (utils.validUrl(item.img)) {
    return item.img as string;
  }

  const byTopic = categoryImage(item.topic || '');
  if (byTopic) {
    return byTopic;
  }

  return '';
};

const trimServiceDescription = (description?: string): string => {
  const cleaned = utils.cleanText(String(description || ''), true);
  if (!cleaned) {
    return '';
  }

  return utils.trimString(cleaned, 420);
};

onMounted(() => {
  generalModule.callAfterLoadedServices(() => {
    if (!utils.isMobile() && serviceInput) {
      serviceInput.focus();
    }
    loading = false;
    refreshCategoryImages();
  });
});

watch(
  () => allCategories.map((category) => category.title).join('|'),
  () => {
    refreshCategoryImages();
  },
);

watch(
  () => activeCategories.join('|'),
  () => {
    clearFilters();
  },
);

watch(
  () => [
    searchMode,
    activeCategories.join('|'),
    normalizedFilter,
    sortBy,
    selectedProviders.join('|'),
    selectedFunctionalities.join('|'),
    selectedFormats.join('|'),
    selectedLanguages.join('|'),
    selectedIntendedFor.join('|'),
  ].join('||'),
  () => {
    currentPage = 1;
  },
);

watch(
  () => activeServicesSorted.length,
  () => {
    if (currentPage > totalPages) {
      currentPage = totalPages;
    }
  },
);
</script>

<style scoped>
.services-page {
  position: relative;
  min-height: 100%;
  margin-bottom: 0;
  padding-bottom: 0;
}

.content-grid--services-full {
  grid-template-columns: minmax(0, 1fr);
}

.services-mode-toggle {
  display: inline-flex;
  align-items: center;
  gap: 0.15rem;
  border: 1px solid #d1dae5;
  border-radius: 9999px;
  background: #f8fafc;
  padding: 0.12rem;
  width: fit-content;
  max-width: 100%;
}

.services-mode-toggle__button {
  border: 0;
  background: transparent;
  color: #2f455d;
  font-size: 0.86rem;
  font-weight: 700;
  border-radius: 9999px;
  padding: 0.32rem 0.62rem;
  transition: background-color 140ms ease, color 140ms ease;
  line-height: 1.1;
}

.services-mode-toggle__button--active {
  background: #234a78;
  color: #fff;
}

.services-sort-select {
  border-radius: 0.45rem;
  background: #ffffff;
}

.services-sort-inline {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.services-skeleton-grid {
  display: grid;
  grid-template-columns: repeat(1, minmax(0, 1fr));
  gap: 1rem;
}

.services-skeleton-card {
  height: 124px;
  border-radius: 0.7rem;
  background: linear-gradient(110deg, rgba(255, 255, 255, 0.28) 8%, rgba(255, 255, 255, 0.6) 18%, rgba(255, 255, 255, 0.28) 33%);
  background-size: 200% 100%;
  animation: servicesShimmer 1.2s linear infinite;
}

@keyframes servicesShimmer {
  to {
    background-position-x: -200%;
  }
}

.services-category-grid {
  display: grid;
  grid-template-columns: repeat(1, minmax(0, 1fr));
  gap: 1rem;
}

@media (min-width: 768px) {
  .services-category-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (min-width: 1120px) {
  .services-category-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (min-width: 1280px) {
  .services-category-grid--wide {
    grid-template-columns: repeat(4, minmax(0, 1fr));
  }
}

.services-category-card {
  overflow: hidden;
  border-radius: 0.6rem;
  border: 1px solid #d2d6dc;
  background: #ffffff;
  text-align: left;
  box-shadow: 0 8px 18px rgba(17, 24, 39, 0.1);
  transition: transform 170ms ease, box-shadow 170ms ease, filter 170ms ease;
}

.services-category-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 16px 30px rgba(17, 24, 39, 0.22);
  filter: brightness(0.95);
}

.services-category-image {
  width: 100%;
  height: 150px;
  object-fit: cover;
  display: block;
}

.services-category-body {
  padding: 0.9rem 1rem;
}

.services-category-body-no-image {
  min-height: 150px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}

.services-detail-layout {
  display: grid;
  grid-template-columns: 1fr;
  gap: 1.4rem;
}

@media (min-width: 1024px) {
  .services-detail-layout {
    grid-template-columns: 360px minmax(0, 1fr);
    align-items: start;
  }
}

.services-sidebar {
  border: 1px solid #d2d6dc;
  border-radius: 0.55rem;
  padding: 1rem;
  background: rgba(255, 255, 255, 0.92);
  backdrop-filter: blur(6px);
  align-self: start;
}

@media (min-width: 1024px) {
  .services-sidebar {
    position: sticky;
    top: 1.15rem;
  }
}

.services-sidebar__label {
  font-size: 0.82rem;
  font-weight: 600;
  letter-spacing: 0.02em;
  color: #4b5563;
  margin-bottom: 0.35rem;
}

.services-filters {
  border-top: 1px solid #e2e8f0;
  padding-top: 0.85rem;
}

.services-filters :deep(.filter-accordion) {
  border-color: rgba(35, 74, 120, 0.2);
  box-shadow: 0 3px 10px rgba(35, 74, 120, 0.08);
}

.services-filters :deep(.filter-accordion__header) {
  background: linear-gradient(180deg, #f5f8fd 0%, #eef4fb 100%);
}

.services-filter-remove {
  background: rgba(35, 74, 120, 0.14);
  color: #1f4b79;
  min-width: 1.35rem;
  justify-content: center;
  display: inline-flex;
  align-items: center;
  padding: 0.2rem 0.35rem;
}

.services-filter-list {
  max-height: 150px;
  overflow: auto;
  padding-right: 0.2rem;
}

.services-filter-check {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  font-size: 0.88rem;
  margin-bottom: 0.45rem;
  color: #334155;
}

.services-filter-check input {
  margin: 0;
}

.services-list-card {
  display: grid;
  grid-template-columns: 112px minmax(0, 1fr);
  gap: 0.9rem;
  align-items: stretch;
}

.services-list-card__body {
  min-width: 0;
}

.services-list-card__media {
  width: 112px;
  height: 112px;
  flex-shrink: 0;
}

.services-list-card__image,
.services-list-card__image-fallback {
  width: 112px;
  height: 112px;
  border-radius: 0.5rem;
}

.services-list-card__image {
  object-fit: cover;
  display: block;
}

.services-list-card__image-fallback {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background: linear-gradient(160deg, #e9f1fb 0%, #d6e6f8 100%);
  color: #315a87;
  font-size: 1.45rem;
}

@media (max-width: 639px) {
  .services-list-card {
    grid-template-columns: 1fr;
  }

  .services-list-card__media,
  .services-list-card__image,
  .services-list-card__image-fallback {
    width: 100%;
    height: 180px;
  }
}

.services-item-card {
  border-radius: 0.7rem;
  border: 1px solid rgba(205, 215, 228, 0.95);
  background: rgba(255, 255, 255, 0.92);
  box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
}

.services-item-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 16px 30px rgba(15, 23, 42, 0.12);
}

.services-item-card p {
  line-height: 1.45;
}

.services-category-recall {
  border: 1px solid rgba(35, 74, 120, 0.18);
  background: linear-gradient(180deg, #f8fbff 0%, #f1f6fd 100%);
  border-radius: 0.6rem;
  padding: 0.75rem;
}

.services-category-recall__header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  margin-bottom: 0.6rem;
}

.services-category-recall__title {
  margin: 0;
  color: #1f3f63;
  font-size: 0.88rem;
  font-weight: 700;
  letter-spacing: 0.03em;
  text-transform: uppercase;
}

.services-category-recall__clear {
  border: 0;
  background: transparent;
  color: #1f4b79;
  font-size: 0.84rem;
  font-weight: 700;
}

.services-category-recall__list {
  display: flex;
  flex-wrap: wrap;
  gap: 0.55rem;
}

.services-category-recall__chip {
  display: inline-flex;
  align-items: center;
  gap: 0.45rem;
  border: 1px solid rgba(35, 74, 120, 0.16);
  border-radius: 9999px;
  background: #fff;
  padding: 0.22rem 0.52rem 0.22rem 0.28rem;
  max-width: 100%;
}

.services-category-recall__thumb {
  width: 1.95rem;
  height: 1.95rem;
  border-radius: 9999px;
  object-fit: cover;
  flex-shrink: 0;
}

.services-category-recall__name {
  font-size: 0.86rem;
  color: #1f3f63;
  font-weight: 600;
  line-height: 1.2;
}

.services-category-recall__x {
  color: #5e6f82;
  font-size: 0.73rem;
  flex-shrink: 0;
}

.services-meta-row {
  display: flex;
  flex-wrap: wrap;
  gap: 0.45rem 1rem;
}

.services-meta-row p {
  margin: 0;
  display: inline-flex;
  align-items: center;
}

.services-card-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 0.55rem;
}

.services-action {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 0.5rem;
  padding: 0.45rem 0.75rem;
  font-size: 0.9rem;
  border: 1px solid transparent;
}

.services-action--primary {
  background: #234a78;
  border-color: #234a78;
  color: #ffffff;
}

.services-action--primary:hover {
  background: #1d3e66;
  color: #ffffff;
}

.services-action--ghost {
  border-color: #a9bfd6;
  background: #f8fbff;
  color: #1f4b79;
}

.services-action--ghost:hover {
  background: #e8f2fb;
}

.services-action--sleek {
  border-radius: 9999px;
  padding: 0.5rem 0.95rem;
  font-weight: 700;
  letter-spacing: 0.01em;
  box-shadow: 0 8px 16px rgba(17, 24, 39, 0.08);
  transition: transform 160ms ease, box-shadow 160ms ease, background-color 160ms ease;
}

.services-action--sleek:hover {
  transform: translateY(-1px);
  box-shadow: 0 12px 22px rgba(17, 24, 39, 0.14);
}

.services-description {
  white-space: pre-line;
  display: -webkit-box;
  -webkit-line-clamp: 4;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.services-hero {
  position: relative;
  height: clamp(190px, 24vw, 300px);
  border-radius: 0.8rem;
  overflow: hidden;
  background-size: cover;
  background-position: center center;
  border: 1px solid rgba(154, 169, 189, 0.35);
}

.services-hero--no-image {
  background: linear-gradient(130deg, #123259 0%, #274c75 40%, #4b7cb3 100%);
}

.services-hero__overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(120deg, rgba(15, 46, 82, 0.62) 0%, rgba(15, 46, 82, 0.35) 55%, rgba(255, 255, 255, 0.08) 100%);
}

.services-hero__content {
  position: absolute;
  inset: auto auto 0 0;
  z-index: 2;
  padding: 1.05rem 1.15rem;
  color: #ffffff;
  max-width: min(620px, 100%);
}

.services-hero__kicker {
  margin: 0 0 0.3rem 0;
  font-size: 0.78rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  opacity: 0.95;
}

.services-hero__title {
  margin: 0;
  font-size: clamp(1.9rem, 3.4vw, 3.2rem);
  font-weight: 800;
  line-height: 1.06;
}

.services-hero__count {
  margin: 0.5rem 0 0 0;
  font-size: 1rem;
  opacity: 0.95;
}

.services-hero__back {
  margin-top: 0.8rem;
  border: 1px solid rgba(255, 255, 255, 0.55);
  background: rgba(255, 255, 255, 0.12);
  color: #ffffff;
  border-radius: 9999px;
  padding: 0.45rem 0.8rem;
  font-size: 0.85rem;
  transition: background-color 150ms ease;
}

.services-hero__back:hover {
  background: rgba(255, 255, 255, 0.2);
}

@media (min-width: 900px) {
  .services-skeleton-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}
</style>
