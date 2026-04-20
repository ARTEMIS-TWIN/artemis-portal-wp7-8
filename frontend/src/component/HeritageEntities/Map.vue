<template>
  <div class="relative border-base border-gray ariadne-map rounded-2xl overflow-hidden bg-white">
    <div class="px-base py-sm border-b-base border-gray text-md text-midGray">
      Heritage entity locations
    </div>

    <div class="relative" style="height: 300px;">
      <div
        v-if="!mapHasHits"
        class="absolute top-0 left-0 h-full w-full bg-lightGray-20 z-19 text-darkGray text-xl flex items-center justify-center"
      >
        {{ isLoading ? 'Loading..' : 'No locations found' }}
      </div>

      <div :id="mapId" style="height: 300px;" />
    </div>

    <div v-if="mapHasHits" class="bg-white border-t-base border-gray">
      <div class="max-w-screen-xl mx-auto block md:flex md:justify-between items-center text-midGray text-md">
        <div class="map-legend__title px-base pt-md md:pt-none">
          Legend
        </div>
        <ul class="map-legend__list py-md px-base flex">
          <li class="map-legend__item flex items-center h-2x">
            <img
              :src="markerTypes.point.marker"
              width="15"
              class="mr-sm"
              alt=""
              aria-hidden="true"
            >
            <span>Heritage entity</span>
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { nextTick, onMounted, onUnmounted, watch } from 'vue';
import { $computed } from 'vue/macros';
import * as L from 'leaflet';
import router from '@/router';
import { generalModule, heritageEntitySearchModule } from '@/store/modules';
import utils from '@/utils/utils';

const mapId = 'heritage-entities-map-' + utils.getUniqueId();
const markerTypes = utils.getMarkerTypes(generalModule);

let mapObj: any = null;
let markersLayer: L.LayerGroup | null = null;
let mapHasHits = $ref(false);

const result = $computed(() => heritageEntitySearchModule.getResult);
const isLoading = $computed(() => generalModule.getIsLoading);

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
  mapObj.setView([20, 0], 2);
};

const resolveLatLon = (hit: any): { lat: number, lon: number } | null => {
  const location = hit?.data?.location || {};
  const geo = location?.geopoint || {};

  const lat = Number(geo.lat ?? location.lat);
  const lon = Number(geo.lon ?? location.lon);

  if (!Number.isFinite(lat) || !Number.isFinite(lon)) {
    return null;
  }

  if (lat < -90 || lat > 90 || lon < -180 || lon > 180) {
    return null;
  }

  return { lat, lon };
};

const setMap = () => {
  if (!mapObj) {
    return;
  }

  if (markersLayer) {
    mapObj.removeLayer(markersLayer);
  }

  markersLayer = L.layerGroup();
  const points: L.LatLng[] = [];
  const hits = Array.isArray(result?.hits) ? result.hits : [];

  hits.forEach((hit: any) => {
    const latLon = resolveLatLon(hit);

    if (!latLon) {
      return;
    }

    const latLng = L.latLng(latLon.lat, latLon.lon);
    const marker = L.marker(latLng, {
      icon: L.icon({
        iconUrl: markerTypes.point.marker,
        shadowUrl: markerTypes.shadow.marker,
        iconSize: [25, 41],
        iconAnchor: [12, 41],
        popupAnchor: [1, -34],
        shadowSize: [41, 41],
      }),
    });

    marker.bindTooltip(hit?.data?.label || 'Heritage entity', { direction: 'top', offset: [0, -35] });
    marker.on('click', () => {
      router.push(`/heritage-entities/${encodeURIComponent(hit.id)}`);
    });

    markersLayer!.addLayer(marker);
    points.push(latLng);
  });

  mapHasHits = points.length > 0;

  if (!mapHasHits) {
    mapObj.setView([20, 0], 2);
    nextTick(() => mapObj.invalidateSize());
    return;
  }

  markersLayer.addTo(mapObj);

  if (points.length === 1) {
    mapObj.setView(points[0], 11);
  } else {
    mapObj.fitBounds(L.latLngBounds(points), { padding: [25, 25] });
  }

  nextTick(() => mapObj.invalidateSize());
};

onMounted(async () => {
  await nextTick();
  setupMap();
  setMap();
});

watch(() => result?.hits, async () => {
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
</script>
