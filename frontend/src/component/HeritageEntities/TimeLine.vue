<template>
  <div class="rounded-2xl overflow-hidden mb-lg border-base border-gray bg-white">
    <div class="bg-white items-center p-sm flex justify-between border-b-base border-gray">
      <span class="text-md">When</span>
      <i class="fas fa-chart-line text-blue"></i>
    </div>

    <div class="relative p-sm">
      <canvas v-if="hasChartData" :id="timelineId" />
      <p v-else class="text-sm text-midGray py-sm px-xs">No timeline data available for the current filters.</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, onUnmounted, watch } from 'vue';
import { $computed, $ref } from 'vue/macros';
import Chart from 'chart.js';
import { heritageEntitySearchModule } from '@/store/modules';
import utils from '@/utils/utils';

const timelineId = 'heritage-timeline-' + utils.getUniqueId();
let chart: any = $ref(null);
let ctx: any = $ref(null);
let hasChartData = $ref(false);

const aggs = $computed(() => heritageEntitySearchModule.getAggsResult?.aggs || {});
const buckets = $computed(() => {
  return aggs?.range_buckets?.buckets
    || aggs?.range_buckets?.range_agg?.buckets
    || {};
});

const getData = (bucketMap: Record<string, any>) => {
  const labels: string[] = [];
  const values: number[] = [];

  Object.keys(bucketMap)
    .sort((a, b) => {
      const aStart = parseInt((a.split(':')[0] || '0'), 10);
      const bStart = parseInt((b.split(':')[0] || '0'), 10);
      return aStart - bStart;
    })
    .forEach((key) => {
      labels.push(key.replace(':', ' - '));
      values.push(parseInt(bucketMap[key]?.doc_count || 0, 10));
    });

  return { labels, values };
};

const renderTimeline = () => {
  const data = getData(buckets || {});

  if (!data.labels.length || !data.values.some((value) => value > 0)) {
    if (chart) {
      chart.destroy();
      chart = null;
    }
    hasChartData = false;
    return;
  }

  if (!ctx) {
    const canvas = document.getElementById(timelineId) as any;
    if (!canvas) {
      return;
    }
    ctx = canvas.getContext('2d');
  }

  if (!ctx) {
    return;
  }

  const gradient = ctx.createLinearGradient(0, 0, 0, 240);
  gradient.addColorStop(0, '#BB3921');
  gradient.addColorStop(0.5, '#D5A03A');
  gradient.addColorStop(1, '#75A99D');

  const chartData = {
    labels: data.labels,
    datasets: [{
      data: data.values,
      backgroundColor: gradient,
      borderColor: '#315a87',
      borderWidth: 1.5,
      fill: true,
      pointRadius: 1.5,
      lineTension: 0.2,
    }],
  };

  if (chart) {
    chart.data = chartData;
    chart.update();
  } else {
    chart = new Chart(ctx, {
      type: 'line',
      data: chartData,
      options: {
        responsive: true,
        maintainAspectRatio: true,
        aspectRatio: 1.65,
        legend: { display: false },
        scales: {
          xAxes: [{
            ticks: { maxTicksLimit: 10 },
          }],
          yAxes: [{
            ticks: { beginAtZero: true, precision: 0 },
          }],
        },
      },
    });
  }

  hasChartData = true;
};

onMounted(() => {
  renderTimeline();
});

watch(() => JSON.stringify(buckets || {}), () => {
  renderTimeline();
});

onUnmounted(() => {
  if (chart) {
    chart.destroy();
    chart = null;
  }
});
</script>
