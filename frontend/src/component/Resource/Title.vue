<template>
  <div class="detail-page__title-block">
    <div class="flex justify-between items-start gap-base detail-page__title-row">
      <div>
        <p class="text-sm uppercase tracking-wide text-midGray mb-sm detail-page__kicker">Data Resource</p>
        <h1 class="text-2xl">{{ mainTitle }}</h1>
        <p v-if="resourceType" class="mt-sm">
          <strong class="mr-sm">Resource type</strong>{{ resourceType }}
        </p>
      </div>

      <!-- cts icon -->
      <div class="ml-base shrink-0">
        <help-tooltip
          v-if="isCtsCertified"
          title="CoreTrustSeal Certified"
          top=".3rem"
          right="3.5rem"
        >
          <a
            href="https://www.coretrustseal.org/"
            target="_blank"
            class="w-3x h-3x"
          >
            <img
              :src="`${ generalModule.getAssetsDir }/CTS-logo.png`"
              alt="CoreTrustSeal Certified"
              class="w-full"
            >
          </a>
        </help-tooltip>
      </div>
    </div>

    <!-- other lang titles -->
    <multi-lang-info
      :nativeInfoText="nativeTitle"
      nativeInfoIconHelpText="Title in native language for this resource"
      nonNativeInfoTitleActive="Hide titles in other languages"
      nonNativeInfoTitleInactive="Show titles in other languages"
      :nonNativeInfoList="nonNativeTitles"
    />
  </div>
</template>

<script setup lang="ts">
import { $computed } from 'vue/macros';
import { generalModule, resourceModule } from "@/store/modules";
import HelpTooltip from '@/component/Help/Tooltip.vue';
import MultiLangInfo from './MultiLangInfo.vue';
import utils from '@/utils/utils';

const resource = $computed(() => resourceModule.getResource);
const mainTitle = $computed(() => resourceModule.getMainTitle(resource));
const isCtsCertified = $computed(() => resourceModule.getIsCtsCertified(resource));
const nativeTitle = $computed(() => resourceModule.getNativeTitle(resource));
const nonNativeTitles = $computed(() => resourceModule.getNonNativeTitles(resource));
const resourceType = $computed(() => {
  const typeName = String(resource?.resourceType ?? '').trim();
  return typeName ? utils.sentenceCase(typeName.replace(/[-_]/g, ' ')) : '';
});
</script>
