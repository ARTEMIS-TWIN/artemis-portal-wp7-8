<template>
  <div v-if="showFloating" class="artemisia-floating">
    <transition name="fade">
      <div
        v-if="artemisIAModule.panelOpen"
        class="artemisia-floating__panel app-panel"
        @wheel.stop
        @touchmove.stop
      >
        <ArtemisIAAssistant mode="floating" />
      </div>
    </transition>

    <button type="button" class="artemisia-floating__launcher" @click="artemisIAModule.togglePanel()">
      <i class="fas fa-robot"></i>
      <span class="artemisia-floating__label">Artemisia</span>
      <span v-if="artemisIAModule.selectedCount" class="artemisia-floating__badge">
        {{ artemisIAModule.selectedCount }}
      </span>
    </button>
  </div>
</template>

<script setup lang="ts">
import { $computed } from 'vue/macros';
import { useRoute } from 'vue-router';
import { artemisIAModule } from '@/store/modules';
import ArtemisIAAssistant from './Assistant.vue';

const route = useRoute();

const showFloating = $computed(() => route.path !== '/artemisia');
</script>
