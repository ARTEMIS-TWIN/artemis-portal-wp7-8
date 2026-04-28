<template>
  <div v-if="coordinatePoints.length">
    <h3 class="text-lg font-bold mb-md">
      <i class="fas fa-crosshairs mr-sm" />
      Coordinates
    </h3>

    <div :class="itemClass">
      <div
        v-for="(point, index) in coordinatePoints"
        :key="`heritage-coord-${index}`"
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

const props = defineProps<{
  entity: any,
  itemClass: string,
  bClass: string,
}>();

const coordinatePoints = $computed(() => {
  const seen = new Set<string>();
  const points: Array<{ lat: string, lon: string, label: string }> = [];

  const location = props.entity?.location || {};
  const candidates = [
    location?.geopoint || null,
    (location?.lat !== undefined && location?.lon !== undefined) ? { lat: location.lat, lon: location.lon } : null,
  ];

  for (const candidate of candidates) {
    const lat = Number(candidate?.lat);
    const lon = Number(candidate?.lon);

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
        label: getCoordinateLabel(props.entity, points.length + 1),
      });
    }
  }

  return points;
});

const showCoordinateLabels = $computed(() => coordinatePoints.length > 1);

const formatCoordinate = (value: number): string => {
  return Number(value.toFixed(6)).toString();
};

const getCoordinateLabel = (entity: any, fallbackIndex: number): string => {
  const placeLabel = String(entity?.placeLabel || '').trim();
  if (placeLabel) {
    return placeLabel;
  }

  const locationLabel = String(entity?.location?.label || '').trim();
  if (locationLabel) {
    return locationLabel;
  }

  return `Coordinate ${fallbackIndex}`;
};
</script>
