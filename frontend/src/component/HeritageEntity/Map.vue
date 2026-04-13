<template>
  <div class="ariadne-map ariadne-map--resource">
    <div v-show="hasMapData">
      <div :id="mapId" :style="{ height: getMapSize() }"></div>

      <div class="bg-lightGray-60">
        <div class="max-w-screen-xl mx-auto block md:flex md:justify-between items-center text-center text-midGray text-sm">
          <div class="py-md px-base flex items-center w-full">
            <img
              :src="markerTypes.point.current"
              width="15"
              class="inline mr-sm"
              alt="current marker"
            >

            Current heritage entity

            <div class="ml-auto relative">
              <i class="fas fa-expand text-lg transition-color duration-300 hover:text-green px-sm"
                @mouseover="activeMapSizePopup = true"
                @mouseleave="activeMapSizePopup = false"
              />
              <div class="absolute bottom-0 right-0 bg-black-80 px-base py-base text-mmd text-white text-left whitespace-no-wrap transition-all duration-300"
                :class="activeMapSizePopup ? 'z-30 opacity-100' : 'z-neg10 opacity-0'"
                @mouseover="activeMapSizePopup = true"
                @mouseleave="activeMapSizePopup = false">
                <b class="text-base">Map size:</b>
                <ul class="pt-sm pl-lg list-disc">
                  <li class="mb-xs">
                    <a href="#" class="hover:underline" :class="{ underline: mapSize === 'small' }" @click.prevent="setMapSize('small')">
                      Small
                    </a>
                  </li>
                  <li class="mb-xs">
                    <a href="#" class="hover:underline" :class="{ underline: mapSize === 'medium' }" @click.prevent="setMapSize('medium')">
                      Medium
                    </a>
                  </li>
                  <li>
                    <a href="#" class="hover:underline" :class="{ underline: mapSize === 'large' }" @click.prevent="setMapSize('large')">
                      Full screen
                    </a>
                  </li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div v-if="!hasMapData" class="pb-md"></div>
  </div>
</template>

<script setup lang="ts">
import { nextTick, onMounted, onUnmounted, watch } from 'vue';
import { $ref } from 'vue/macros';
import * as L from 'leaflet';
import { generalModule } from '@/store/modules';
import utils from '@/utils/utils';

const props = defineProps<{
  entity: any,
}>();

const mapId = 'heritage-map-' + utils.getUniqueId();
const markerTypes = utils.getMarkerTypes(generalModule);

let mapObj: any = null;
let marker: any = null;
let hasMapData: boolean = $ref(false);
let activeMapSizePopup: boolean = $ref(false);
let mapSize: string = $ref('small');

onMounted(() => {
  nextTick(() => {
    setupMap();
    setMap();
  });
});

watch(() => props.entity, async () => {
  await nextTick();
  if (!mapObj) {
    setupMap();
  }
  setMap();
}, { deep: true });

onUnmounted(() => {
  if (mapObj) {
    mapObj.remove();
    mapObj = null;
  }
});

const setupMap = () => {
  if (mapObj || !document.getElementById(mapId)) {
    return;
  }

  mapObj = L.map(mapId, {
    zoomControl: false,
    scrollWheelZoom: false,
  });

  const allTileLayers = utils.getTileLayers(L, false, false);
  allTileLayers.OSM.addTo(mapObj);
  L.control.layers(allTileLayers, undefined, { position: 'bottomright' }).addTo(mapObj);
  mapObj.addControl(L.control.zoom({ position: 'bottomright' }));
};

const setMap = () => {
  const lat = props.entity?.location?.lat;
  const lon = props.entity?.location?.lon;

  hasMapData = lat !== undefined && lat !== null && lon !== undefined && lon !== null;

  if (!mapObj || !hasMapData) {
    return;
  }

  if (marker) {
    marker.remove();
  }

  const latLng = L.latLng(lat, lon);
  marker = L.marker(latLng, {
    icon: L.icon({
      iconUrl: markerTypes.point.current,
      shadowUrl: markerTypes.shadow.marker,
      iconSize: [25, 41],
      iconAnchor: [12, 41],
      popupAnchor: [1, -34],
      shadowSize: [41, 41],
    }),
  });

  marker.bindTooltip(props.entity?.label || 'Heritage entity', { direction: 'top', offset: [0, -35] });
  marker.addTo(mapObj);
  mapObj.setView(latLng, 11);

  nextTick(() => {
    mapObj.invalidateSize();
  });
};

const setMapSize = (size: string) => {
  mapSize = size;
  nextTick(() => mapObj?.invalidateSize());
};

const getMapSize = (): string => {
  if (mapSize === 'small') {
    return '300px';
  }
  if (mapSize === 'medium') {
    return 'max(50vh, 400px)';
  }
  return 'calc(100vh - 10rem)';
};
</script>
