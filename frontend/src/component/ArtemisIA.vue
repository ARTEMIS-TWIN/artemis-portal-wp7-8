<template>
  <div class="py-xl px-base mx-auto max-w-screen-xl">
    <section class="app-panel">
      <div class="mb-lg">
        <h1 class="text-2xl app-section-title">ArtemisIA</h1>
        <p class="text-mmd mt-sm">
          Your AI assistant for search support. Select records on result pages or ask directly here.
        </p>
      </div>

      <ArtemisIAAssistant mode="page" />
    </section>
  </div>
</template>

<script setup lang="ts">
import ArtemisIAAssistant from './ArtemisIA/Assistant.vue';
</script>
