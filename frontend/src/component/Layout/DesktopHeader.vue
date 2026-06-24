<template>
  <header class="portal-header hidden md:block">
    <div class="portal-header__content">
      <b-link to="/" class="portal-logo" aria-label="Visit ARTEMIS">
        <img
          :src="logoSrc"
          alt="ARTEMIS logo"
          width="240"
          height="68"
          loading="eager"
          fetchpriority="high"
        />
        <span class="portal-logo__tagline">Data Infrastructure</span>
      </b-link>
      <nav class="portal-nav">
        <div
          class="portal-nav__group"
          :class="{ active: isSearchGroupActive }"
        >
          <span class="portal-nav__group-trigger">Search for...</span>
          <div class="portal-nav__group-menu">
            <b-link
              v-for="(item, index) in searchLinks"
              :key="item.path"
              :to="item.path"
              class="portal-nav__search-link"
              :class="[getSearchButtonClass(index), { active: item.path && isActive(item.path) }]"
            >
              {{ item.name }}
            </b-link>
          </div>
        </div>
        <b-link
          v-for="item in primaryLinks"
          :key="item.path || item.href"
          :to="item.path"
          :href="item.href"
          :class="{ active: item.path && isActive(item.path) }"
        >
          <i
            v-if="item.icon"
            :class="item.icon"
            class="admin-icon"
          ></i>
          <span v-else>{{ item.name }}</span>
        </b-link>
      </nav>
    </div>
  </header>
</template>

<script setup lang="ts">
import { watch, onMounted } from 'vue';
import { $ref, $computed } from 'vue/macros';
import { useRoute } from 'vue-router'
import { generalModule } from "@/store/modules";
import BLink from '@/component/Base/Link.vue';

const route = useRoute();
let path: string = $ref('');
const assets: string = $computed(() => generalModule.getAssetsDir);
const logoSrc: string = $computed(() => `${assets}/artemis-logo.png`);
const searchPaths = ['/search', '/heritage-entities', '/services'];

const searchLinks = $computed(() => {
  return generalModule.getMainNavigation.filter((item: any) => item.path && searchPaths.includes(item.path));
});

const primaryLinks = $computed(() => {
  return generalModule.getMainNavigation.filter((item: any) => !item.path || !searchPaths.includes(item.path));
});

const isActive = (itemPath?: string): boolean => {
  if (!itemPath) {
    return false;
  }

  return path.includes(itemPath) ||
    (itemPath.includes('search') && path.includes('resource'));
}

const isSearchGroupActive = $computed(() => {
  return searchLinks.some((item: any) => item.path && isActive(item.path));
});

const getSearchButtonClass = (index: number): string => {
  const classes = [
    'portal-nav__search-link--one',
    'portal-nav__search-link--two',
    'portal-nav__search-link--three',
  ];

  return classes[index] || classes[classes.length - 1];
};

const updateMenuPath = (): void => {
  path = route.fullPath;
}

onMounted(updateMenuPath);
watch(route, updateMenuPath);
</script>
