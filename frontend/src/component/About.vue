<template>
	<div class="pt-3x px-base max-w-screen-xl mx-auto text-mmd">
    <h1 class="text-2xl mb-md">About ARTEMIS</h1>
    <p class="mb-md">ARTEMIS is a three-year research project involving a consortium of 22 European partners (and two affiliates) led by the National Research Council of Italy (CNR).</p>
    <p class="mb-3x">At the heart of ARTEMIS is the Heritage Digital Twin. This concept consists of the physical heritage object (such as a small artefact or a large monument) along with all the digital information associated with it and the system which connects all the information together. The digital data may include many different types, from the history and related documents, physical measurements and test results, 3D and VR models, environmental data and records which all provide a context for the original object.</p>

    <h2 class="text-lg mb-md">ARTEMIS Objectives</h2>
    <p class="mb-md">A further development of this concept that ARTEMIS will address is the Reactive Heritage Digital Twin (RHDT) which will enable heritage professionals to forecast the consequences of real-life events via sensors and other information collected automatically from other systems, or resulting from computer modelled experiments. Most importantly, the digital twin technology will allow researchers and conservators to conduct virtual experiments and simulations, testing various conservation methods and their potential impact without risking damage to the actual heritage assets. This will revolutionise conservation practises as it will lead to better, non-invasive interventions.</p>
    <p class="mb-3x">Over the next three years, ARTEMIS will:</p>
      <ul class="list-disc pl-lg space-y-sm mb-3x">
        <li>develop the cloud-based digital infrastructure that will enable the digital data from different sources to be connected together,</li>
        <li>create and integrate the necessary tools and services for gathering data, performing experiments, etc.</li>
        <li>develop 3D and VR technologies that also benefit the public,</li>
        <li>run tests and real life pilot studies,</li>
        <li>provide training to end users using ARTEMIS,</li>
        <li>establish a research infrastructure (RI) that is well integrated with other existing cultural heritage RIs and initiatives (ARIADNE, 4CH, DARIAH, DIGILAB.be and HSDS).</li>
      </ul>

    <h2 class="text-lg mb-md">For more information</h2>
    <p class="mb-base">
      About ARTEMIS please see our project website:
      <b-link
        target="_blank"
        href="https://www.artemis-twin.eu/"
        class="break-word"
        :useDefaultStyle="true"
      >https://www.artemis-twin.eu/</b-link>.
    </p>
  </div>
</template>

<script setup lang="ts">
import BLink from '@/component/Base/Link.vue';
</script>
