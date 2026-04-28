<template>
  <div v-if="temporalItems.length">
    <h3 class="text-lg font-bold mb-md">
      <i class="fas fa-hourglass-half mr-sm" />
      Temporal Context
    </h3>

    <div :class="itemClass">
      <strong :class="bClass">Dating</strong>

      <div
        v-for="(item, index) in temporalItems"
        :key="`temporal-item-${index}`"
        class="block"
        :class="{ 'mb-sm': index < temporalItems.length - 1 }"
      >
        <span v-if="getPeriodo(item)">
          <span class="mr-xs" @mouseenter="toggleTooltip($event, true)" @mouseleave="toggleTooltip($event, false)">
            <i class="fas fa-question-circle text-blue hover:text-darkGray transition-color duration-300 mr-xs"/>
            <div class="fixed z-20 hidden">
              <div class="bg-blue text-white p-sm pb-xs relative">
                <div class="absolute" style="width:20px;height:30px;left:-10px;top:0"></div>
                <div class="mb-xs">
                  <b>Period:</b>&nbsp;
                  <b-link :to="utils.paramsToString('/search/', { culturalPeriods: getPeriodo(item).key, culturalLabels: getPeriodo(item).key + ':' + getPeriodo(item).filterLabel + (getPeriodo(item).region ? (' (' + utils.getCountryCode(getPeriodo(item).region) + ')') : '') })" className="text-white hover:underline">
                    <i class="fas fa-search"></i>
                    {{ getPeriodo(item).filterLabel }} ({{ getPeriodo(item).region ? utils.getCountryCode(getPeriodo(item).region) : '' }})
                  </b-link>
                </div>
                <div class="mb-xs" v-if="getPeriodo(item).region">
                  <b>Region:</b>&nbsp;
                  <b-link :to="utils.paramsToString('/search/', { temporalRegion: getPeriodo(item).region })" className="text-white hover:underline">
                    <i class="fas fa-search"></i>
                    {{ getPeriodo(item).region }}
                  </b-link>
                </div>
                <div v-if="item.from && item.until" class="mb-xs">
                  <b>Timespan:</b>&nbsp;{{ ': ' + item.from + ' to ' +  item.until }}
                </div>
                <div v-for="(label, key) in getPeriodo(item).extraLabels" :key="`temporal-extra-${index}-${key}`" class="mb-xs">
                  <span v-if="label">
                    <b>{{ utils.sentenceCase(utils.splitCase(key)) }}:</b>&nbsp;{{ label }}<br/>
                  </span>
                </div>
              </div>
            </div>
          </span>
        </span>

        <b-link
          v-if="item.periodName"
          :to="utils.paramsToString('/search', { temporal: item.periodName })"
          class="spatial-context-link break-word"
        >
          {{ utils.sentenceCase(item.periodName, noFormat(resource.publisher)) }}
        </b-link>
        <span v-if="item.from && item.until">
          {{ ': ' + item.from + ' to ' +  item.until }}
        </span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { $computed } from 'vue/macros';
import { generalModule, resourceModule } from '@/store/modules';
import utils from '@/utils/utils';
import BLink from '@/component/Base/Link.vue';

defineProps<{
  itemClass: string,
  bClass: string,
}>();

const resource = $computed(() => resourceModule.getResource);
const temporalItems = $computed(() => Array.isArray(resource?.temporal) ? resource.temporal : []);

const getPeriodo = (item: any) => {
  if (resource.periodo) {
    const uri = utils.last(item?.uri?.split('/') || [])?.toLowerCase();
    return resource.periodo.find((p: any) => p?.key?.toLowerCase() === uri);
  }
  return null;
}

const toggleTooltip = (e: any, show: boolean) => {
  const rect = e.target.getBoundingClientRect();
  e.target.firstElementChild.nextElementSibling.style.cssText = `width:300px; top: ${rect.top - 5}px; left: ${rect.left + 20}px; display:${show ? 'block' : 'none'};`;
};

const noFormat = (publisher: any) => generalModule.getNoFormat(publisher);
</script>
