<template>
  <div v-if="coordinatePoints.length">
    <h3 class="text-lg font-bold mb-md">
      <i class="fas fa-crosshairs mr-sm" />
      Coordinates
    </h3>

    <div :class="itemClass">
      <div
        v-for="(point, index) in coordinatePoints"
        :key="`resource-coord-${index}`"
        class="block"
        :class="{ 'mb-xs': index < coordinatePoints.length - 1 }"
      >
        <strong v-if="showCoordinateLabels" class="block text-md mb-xs">{{ point.label }}</strong>
        {{ point.lat }}, {{ point.lon }}
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { $computed } from 'vue/macros';
import { resourceModule } from '@/store/modules';

defineProps<{
  itemClass: string,
  bClass: string,
}>();

const resource = $computed(() => resourceModule.getResource);
const showCoordinateLabels = $computed(() => coordinatePoints.length > 1);

const coordinatePoints = $computed(() => {
  const seen = new Set<string>();
  const points: Array<{ lat: string, lon: string, label: string }> = [];

  for (const [index, spatial] of (resource?.spatial || []).entries()) {
    const source = spatial?.geopoint || spatial?.centroid || null;
    const lat = Number(source?.lat);
    const lon = Number(source?.lon);

    if (Number.isNaN(lat) || Number.isNaN(lon)) {
      continue;
    }

    const normalizedLat = formatCoordinate(lat);
    const normalizedLon = formatCoordinate(lon);
    const key = `${normalizedLat},${normalizedLon}`;

    if (!seen.has(key)) {
      seen.add(key);
      points.push({
        lat: normalizedLat,
        lon: normalizedLon,
        label: getCoordinateLabel(spatial, index + 1),
      });
    }
  }

  return points;
});

const formatCoordinate = (value: number): string => {
  return Number(value.toFixed(6)).toString();
};

const getCoordinateLabel = (spatial: any, fallbackIndex: number): string => {
  const placeName = String(spatial?.placeName || '').trim();
  if (placeName) {
    return placeName;
  }

  const location = String(spatial?.location || '').trim();
  if (location) {
    return location;
  }

  if (spatial?.geopoint) {
    return `Point ${fallbackIndex}`;
  }

  if (spatial?.centroid) {
    return `Centroid ${fallbackIndex}`;
  }

  return `Coordinate ${fallbackIndex}`;
};
</script>
