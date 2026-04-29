<template>
  <div>
    <div
      v-if="loading && !service"
      class="py-3x px-base mx-auto max-w-screen-xl"
    >
      <h1 class="text-lg text-center text-midGray">
        Loading service..
      </h1>
    </div>

    <div v-else-if="error" class="py-3x px-base mx-auto max-w-screen-xl">
      <p class="text-red text-mmd text-center">{{ error }}</p>
    </div>

    <div v-else-if="service" class="text-mmd detail-page">
      <div class="service-hero-wrap px-base mx-auto max-w-screen-xl">
        <section
          class="service-hero"
          :class="{ 'service-hero--no-image': !serviceBannerImage }"
          :style="serviceBannerStyle"
        >
          <div class="service-hero__overlay"></div>
          <div class="service-hero__content">
            <p class="service-hero__kicker">Service</p>
            <h1 class="service-hero__title">{{ service.title || 'Untitled service' }}</h1>
            <div class="service-hero__meta">
              <p v-if="service.topic"><strong>Topic</strong>: {{ service.topic }}</p>
            </div>
          </div>
        </section>
      </div>

      <article class="pt-sm pb-xl px-base mx-auto max-w-screen-xl lg:flex resource-shell detail-page__shell">

        <div class="pt-xl w-full lg:w-2/3 lg:pr-2x px-base detail-page__main">
          <div v-if="service.description" class="mt-2x pt-xs mb-3x lg:mb-2x detail-page__description">
            <h3 class="text-lg font-bold mb-md">
              <i class="fas fa-info-circle mr-sm" />
              Description
            </h3>
            <span class="whitespace-pre-line break-word">{{ service.description }}</span>
          </div>

          <section :class="sectionClass">
            <h3 class="text-lg font-bold mb-md">
              <i class="fas fa-tags mr-sm" />
              Metadata
            </h3>

            <div v-if="service.topic" :class="itemClass">
              <strong :class="bClass">Topic</strong>{{ service.topic }}
            </div>

            <div v-if="providerEntries.length" :class="itemClass">
              <strong :class="bClass">Providers</strong>
              <ul class="service-list mt-sm">
                <li v-for="entry in providerEntries" :key="`provider-path-${entry.key}`">{{ entry.path }}</li>
              </ul>
            </div>
          </section>

          <section v-if="providerEntries.length" :class="sectionClass">
            <h3 class="text-lg font-bold mb-md">
              <i class="fas fa-sitemap mr-sm" />
              Provider Details
            </h3>

            <div
              v-for="entry in providerEntries"
              :key="`provider-detail-${entry.key}`"
              :class="itemClass"
            >
              <strong class="block mb-xs">{{ entry.name || 'Provider' }}</strong>
              <p class="text-sm text-midGray mb-sm">{{ entry.path }}</p>

              <p v-if="entry.uri" class="mb-xs">
                <strong class="mr-sm">URI</strong>
                <a
                  v-if="utils.validUrl(entry.uri)"
                  :href="entry.uri"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="text-blue hover:underline break-word"
                >
                  {{ entry.uri }}
                </a>
                <span v-else class="break-word">{{ entry.uri }}</span>
              </p>

              <p v-if="entry.homepage" class="mb-xs">
                <strong class="mr-sm">Homepage</strong>
                <a
                  v-if="utils.validUrl(entry.homepage)"
                  :href="entry.homepage"
                  target="_blank"
                  rel="noopener noreferrer"
                  class="text-blue hover:underline break-word"
                >
                  {{ entry.homepage }}
                </a>
                <span v-else class="break-word">{{ entry.homepage }}</span>
              </p>

              <p v-if="entry.email" class="mb-xs">
                <strong class="mr-sm">Email</strong>
                <a :href="`mailto:${entry.email}`" class="text-blue hover:underline break-word">
                  {{ entry.email }}
                </a>
              </p>
            </div>
          </section>

          <section v-if="hasCapabilities" :class="sectionClass">
            <h3 class="text-lg font-bold mb-md">
              <i class="fas fa-cogs mr-sm" />
              Capabilities
            </h3>

            <div v-if="service.functionalities?.length" :class="itemClass">
              <strong :class="bClass">Functionalities</strong>
              <ul class="service-list mt-sm">
                <li v-for="(item, idx) in service.functionalities" :key="`func-${idx}`">{{ item }}</li>
              </ul>
            </div>

            <div v-if="service.usedForActivities?.length" :class="itemClass">
              <strong :class="bClass">Used for activities</strong>
              <ul class="service-list mt-sm">
                <li v-for="(item, idx) in service.usedForActivities" :key="`used-${idx}`">{{ item }}</li>
              </ul>
            </div>

            <div v-if="service.intendedFor?.length" :class="itemClass">
              <strong :class="bClass">Intended for</strong>
              <ul class="service-list mt-sm">
                <li v-for="(item, idx) in service.intendedFor" :key="`intended-${idx}`">{{ item }}</li>
              </ul>
            </div>

            <div v-if="service.technicalSupport?.length" :class="itemClass">
              <strong :class="bClass">Technical support</strong>
              <ul class="service-list mt-sm">
                <li v-for="(item, idx) in service.technicalSupport" :key="`support-${idx}`">{{ item }}</li>
              </ul>
            </div>

            <div v-if="service.languages?.length" :class="itemClass">
              <strong :class="bClass">Languages</strong>
              <ul class="service-list mt-sm">
                <li v-for="(item, idx) in service.languages" :key="`lang-${idx}`">{{ item }}</li>
              </ul>
            </div>

            <div v-if="service.composedOf?.length" :class="itemClass">
              <strong :class="bClass">Composed of</strong>
              <ul class="service-list mt-sm">
                <li v-for="(item, idx) in service.composedOf" :key="`composed-${idx}`">{{ item }}</li>
              </ul>
            </div>
          </section>

          <section v-if="hasMediaAndFormats" :class="sectionClass">
            <h3 class="text-lg font-bold mb-md">
              <i class="fas fa-photo-video mr-sm" />
              Media and Formats
            </h3>

            <div v-if="service.consumedMedia?.length" :class="itemClass">
              <strong :class="bClass">Consumed media</strong>
              <ul class="service-list mt-sm">
                <li v-for="(item, idx) in service.consumedMedia" :key="`consumed-media-${idx}`">{{ item }}</li>
              </ul>
            </div>

            <div v-if="service.producedMedia?.length" :class="itemClass">
              <strong :class="bClass">Produced media</strong>
              <ul class="service-list mt-sm">
                <li v-for="(item, idx) in service.producedMedia" :key="`produced-media-${idx}`">{{ item }}</li>
              </ul>
            </div>

            <div v-if="service.consumedFormats?.length" :class="itemClass">
              <strong :class="bClass">Consumed formats</strong>
              <ul class="service-list mt-sm">
                <li v-for="(item, idx) in service.consumedFormats" :key="`consumed-format-${idx}`">{{ item }}</li>
              </ul>
            </div>

            <div v-if="service.producedFormats?.length" :class="itemClass">
              <strong :class="bClass">Produced formats</strong>
              <ul class="service-list mt-sm">
                <li v-for="(item, idx) in service.producedFormats" :key="`produced-format-${idx}`">{{ item }}</li>
              </ul>
            </div>
          </section>

          <section v-if="service.documents?.length" :class="sectionClass">
            <h3 class="text-lg font-bold mb-md">
              <i class="fas fa-file-alt mr-sm" />
              Documentation
            </h3>

            <div
              v-for="(doc, idx) in service.documents"
              :key="`doc-${idx}`"
              :class="itemClass"
            >
              <strong class="block mb-xs">{{ doc.label || `Document ${idx + 1}` }}</strong>
              <a
                v-if="utils.validUrl(doc.uri)"
                :href="doc.uri"
                target="_blank"
                rel="noopener noreferrer"
                class="text-blue hover:underline break-word"
              >
                {{ doc.uri }}
              </a>
              <span v-else class="break-word">{{ doc.uri }}</span>
            </div>
          </section>
        </div>

        <div class="w-full lg:w-1/3 pt-xl lg:pl-2x lg:border-l-base border-gray px-base pb-xl detail-page__aside">
          <section v-if="hasIdentifiers" :class="sidebarSectionClass">
            <h3 class="text-lg font-bold mb-lg">
              <i class="fas fa-fingerprint mr-sm"></i>
              Identifiers
            </h3>

            <div class="mb-md" v-if="service.uri">
              <strong class="block mb-xs">Service URI</strong>
              <a
                v-if="utils.validUrl(service.uri)"
                :href="service.uri"
                target="_blank"
                rel="noopener noreferrer"
                class="text-blue hover:underline break-word"
              >
                {{ service.uri }}
              </a>
              <span v-else class="break-word">{{ service.uri }}</span>
            </div>

            <div class="mb-md" v-if="service.id !== undefined && service.id !== null">
              <strong class="block mb-xs">Record ID</strong>
              <span class="break-word">{{ service.id }}</span>
            </div>

          </section>

          <section v-if="hasLinks" :class="sidebarSectionClass">
            <h3 class="text-lg font-bold mb-lg">
              <i class="fas fa-link mr-sm"></i>
              Service Links
            </h3>

            <div class="mb-md" v-if="utils.validUrl(service.url)">
              <strong class="block mb-xs">Service URL</strong>
              <a :href="service.url" target="_blank" rel="noopener noreferrer" class="text-blue hover:underline break-word">
                {{ service.url }}
              </a>
            </div>

            <div v-if="providerLinkEntries.length" class="mb-md">
              <strong class="block mb-xs">Provider links</strong>

              <div
                v-for="entry in providerLinkEntries"
                :key="`provider-link-${entry.key}`"
                class="mb-sm pb-sm border-b-base border-gray last:border-b-0 last:pb-none last:mb-none"
              >
                <p class="text-sm text-midGray mb-xs">{{ entry.path }}</p>

                <div v-if="utils.validUrl(entry.uri)" class="mb-xs">
                  <a :href="entry.uri" target="_blank" rel="noopener noreferrer" class="text-blue hover:underline break-word">
                    {{ entry.uri }}
                  </a>
                </div>

                <div v-if="utils.validUrl(entry.homepage)" class="mb-xs">
                  <a :href="entry.homepage" target="_blank" rel="noopener noreferrer" class="text-blue hover:underline break-word">
                    {{ entry.homepage }}
                  </a>
                </div>

                <div v-if="entry.email">
                  <a :href="`mailto:${entry.email}`" class="text-blue hover:underline break-word">
                    {{ entry.email }}
                  </a>
                </div>
              </div>
            </div>
          </section>
        </div>
      </article>
    </div>

    <div v-else class="py-3x px-base mx-auto max-w-screen-xl">
      <p class="text-mmd text-center">Service not found.</p>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, watch } from 'vue';
import { $computed, $ref } from 'vue/macros';
import { useRoute, useRouter, onBeforeRouteLeave } from 'vue-router';
import { generalModule } from '@/store/modules';
import utils from '@/utils/utils';

type ServiceItem = {
  id?: string | number;
  uri?: string;
  title?: string;
  topic?: string;
  description?: string;
  url?: string;
  provider?: any;
  providers?: any;
  functionalities?: string[];
  usedForActivities?: string[];
  intendedFor?: string[];
  technicalSupport?: string[];
  languages?: string[];
  composedOf?: string[];
  consumedMedia?: string[];
  producedMedia?: string[];
  consumedFormats?: string[];
  producedFormats?: string[];
  documents?: Array<{ uri?: string; label?: string }>;
  sourcePath?: string;
  importSource?: string;
  img?: string;
};

type ProviderEntry = {
  key: string;
  name: string;
  path: string;
  uri: string;
  homepage: string;
  email: string;
};

const route = useRoute();
const router = useRouter();
let service: ServiceItem | null = $ref(null);
let loading = $ref(true);
let error = $ref('');
let categoryImageMap: Record<string, string> = $ref({});
let categoryImageChecked: Record<string, boolean> = $ref({});

const services = $computed(() => generalModule.getServices || []);
const assets = $computed(() => generalModule.getAssetsDir);
const sectionClass = 'py-md mb-lg';
const sidebarSectionClass = 'py-base pb-sm mb-md';
const itemClass = 'border-b-base border-gray mb-md pb-md last:border-b-0 last:pb-none last:mb-none';
const bClass = 'mr-sm';

const currentServiceId = $computed(() => String(route.params.id || '').trim());

const normalizeText = (value: unknown): string => String(value || '').trim();

const providerEntries = $computed(() => {
  const entries: ProviderEntry[] = [];
  const seen = new Set<string>();

  const pushValue = (rawValue: unknown, ancestry: string[] = []): void => {
    if (!rawValue) {
      return;
    }

    if (Array.isArray(rawValue)) {
      rawValue.forEach((entry) => pushValue(entry, ancestry));
      return;
    }

    if (typeof rawValue === 'string') {
      const name = normalizeText(rawValue);
      if (name) {
        const path = [...ancestry, name].join(' > ');
        const key = `${path}|${name}`.toLowerCase();
        if (!seen.has(key)) {
          seen.add(key);
          entries.push({
            key,
            name,
            path,
            uri: '',
            homepage: '',
            email: '',
          });
        }
      }
      return;
    }

    if (typeof rawValue !== 'object') {
      return;
    }

    const value = rawValue as Record<string, any>;
    const name = normalizeText(
      value.name
      || value.label
      || value.title
      || value.organizationName
      || value.providerName
      || value.organization,
    );
    const uri = normalizeText(
      value.uri
      || value.id
      || value.identifier
      || value.providerUri
      || value.resource,
    );
    const homepage = normalizeText(
      value.homepage
      || value.url
      || value.website
      || value.homePage
      || value.providerHomepage,
    );
    const email = normalizeText(
      value.email
      || value.mail
      || value.contactEmail
      || value.eMail
      || value.providerEmail,
    );

    const pathSegments = name ? [...ancestry, name] : ancestry;
    const path = pathSegments.length > 0 ? pathSegments.join(' > ') : '';

    if (name || uri || homepage || email) {
      const key = `${path}|${uri}|${homepage}|${email}`.toLowerCase();

      if (!seen.has(key)) {
        seen.add(key);
        entries.push({
          key,
          name: name || 'Provider',
          path: path || name || 'Provider',
          uri,
          homepage,
          email,
        });
      }
    }

    const nextAncestry = name ? [...ancestry, name] : ancestry;
    pushValue(value.provider, nextAncestry);
    pushValue(value.providers, nextAncestry);
    pushValue(value.children, nextAncestry);
    pushValue(value.members, nextAncestry);
    pushValue(value.organizations, nextAncestry);
    pushValue(value.nestedProviders, nextAncestry);
  };

  pushValue(service?.provider);
  pushValue((service as any)?.providers);
  pushValue((service as any)?.provider_name);
  pushValue((service as any)?.providerName);

  return entries;
});

const providerLinkEntries = $computed(() => {
  return providerEntries.filter((entry) => {
    return utils.validUrl(entry.uri) || utils.validUrl(entry.homepage) || normalizeText(entry.email) !== '';
  });
});

const imageExists = (url: string): Promise<boolean> => {
  return new Promise((resolve) => {
    const img = new Image();
    img.onload = () => resolve(true);
    img.onerror = () => resolve(false);
    img.src = url;
  });
};

const categoryFilenameCandidates = (category: string): string[] => {
  const value = category.trim();
  const underscored = value.replace(/\s+/g, '_');
  const underscoredWithSlash = value.replace(/[\/\s]+/g, '_');
  const alnumUnderscore = value.replace(/[^A-Za-z0-9]+/g, '_').replace(/^_+|_+$/g, '');
  const normalizedLower = alnumUnderscore.toLowerCase();

  const variantForms = new Set<string>([
    normalizedLower,
    normalizedLower
      .replace(/visualisation/g, 'visualization')
      .replace(/geospatial/g, 'geospacial')
      .replace(/enrichment/g, 'enrichement'),
    normalizedLower
      .replace(/visualization/g, 'visualisation')
      .replace(/geospacial/g, 'geospatial')
      .replace(/enrichement/g, 'enrichment'),
  ]);

  const bases = Array.from(new Set([
    value,
    underscored,
    underscoredWithSlash,
    alnumUnderscore,
    underscored.toLowerCase(),
    underscoredWithSlash.toLowerCase(),
    alnumUnderscore.toLowerCase(),
    ...Array.from(variantForms),
    ...Array.from(variantForms).map((variant) => variant.replace(/_/g, ' ')),
  ])).filter((base) => base.length > 0);

  const exts = ['png', 'jpg', 'jpeg', 'webp', 'svg', 'gif'];
  const paths: string[] = [];

  bases.forEach((base) => {
    exts.forEach((ext) => {
      paths.push(`${assets}/services/${base}.${ext}`);
    });
  });

  return paths;
};

const resolveCategoryImage = async (category: string): Promise<void> => {
  if (!category || categoryImageChecked[category]) {
    return;
  }

  categoryImageChecked = {
    ...categoryImageChecked,
    [category]: true,
  };

  const candidates = categoryFilenameCandidates(category);

  for (const candidate of candidates) {
    // eslint-disable-next-line no-await-in-loop
    const exists = await imageExists(candidate);
    if (exists) {
      categoryImageMap = {
        ...categoryImageMap,
        [category]: candidate,
      };
      return;
    }
  }
};

const serviceBannerImage = $computed(() => {
  if (utils.validUrl(service?.img)) {
    return String(service?.img);
  }

  const topic = normalizeText(service?.topic);
  return topic ? (categoryImageMap[topic] || '') : '';
});

const serviceBannerStyle = $computed(() => {
  return serviceBannerImage
    ? { backgroundImage: `url(${serviceBannerImage})` }
    : {};
});

const hasCapabilities = $computed(() => {
  return Boolean(
    service?.functionalities?.length
    || service?.usedForActivities?.length
    || service?.intendedFor?.length
    || service?.technicalSupport?.length
    || service?.languages?.length
    || service?.composedOf?.length,
  );
});

const hasMediaAndFormats = $computed(() => {
  return Boolean(
    service?.consumedMedia?.length
    || service?.producedMedia?.length
    || service?.consumedFormats?.length
    || service?.producedFormats?.length,
  );
});

const hasIdentifiers = $computed(() => {
  return Boolean(
    service?.uri
    || service?.id !== undefined,
  );
});

const hasLinks = $computed(() => {
  return Boolean(
    utils.validUrl(service?.url)
    || providerLinkEntries.length,
  );
});

const buildServiceKey = (item: ServiceItem): string => {
  if (item.id !== undefined && item.id !== null && String(item.id).trim() !== '') {
    return String(item.id).trim();
  }

  if (String(item.uri || '').trim() !== '') {
    return String(item.uri).trim();
  }

  if (String(item.url || '').trim() !== '') {
    return String(item.url).trim();
  }

  return String(item.title || '').trim();
};

const findService = (id: string): ServiceItem | null => {
  if (!id) {
    return null;
  }

  const exact = services.find((item: ServiceItem) => buildServiceKey(item) === id);
  if (exact) {
    return exact;
  }

  let decoded = id;
  try {
    decoded = decodeURIComponent(id);
  } catch (_) {}

  const decodedMatch = services.find((item: ServiceItem) => buildServiceKey(item) === decoded);
  if (decodedMatch) {
    return decodedMatch;
  }

  return null;
};

const loadService = (id: string): void => {
  loading = true;
  error = '';
  service = null;

  generalModule.callAfterLoadedServices(() => {
    service = findService(id);

    if (!service) {
      error = 'Service not found.';
      loading = false;
      return;
    }

    generalModule.setMeta({
      title: service.title || 'Service',
      description: service.description || `Service detail for ${service.title || 'service'}`,
    });

    if (service?.topic) {
      resolveCategoryImage(service.topic);
    }

    loading = false;
  });
};

onMounted(() => {
  loadService(currentServiceId);
});

const unwatch = watch(() => route.params.id, (id: any) => {
  const value = String(id || '').trim();
  if (!value) {
    router.replace('/services');
    return;
  }

  loadService(value);
});

onBeforeRouteLeave(unwatch);
</script>

<style scoped>
.service-hero-wrap {
  margin-top: 0;
  margin-bottom: 0.45rem;
}

.service-hero {
  position: relative;
  width: 100%;
  height: clamp(210px, 28vw, 320px);
  border-radius: 0.8rem;
  overflow: hidden;
  background-size: cover;
  background-position: center center;
  border: 1px solid rgba(154, 169, 189, 0.35);
}

.service-hero--no-image {
  background: linear-gradient(130deg, #123259 0%, #274c75 40%, #4b7cb3 100%);
}

.service-hero__overlay {
  position: absolute;
  inset: 0;
  background: linear-gradient(120deg, rgba(15, 46, 82, 0.68) 0%, rgba(15, 46, 82, 0.38) 60%, rgba(255, 255, 255, 0.08) 100%);
}

.service-hero__content {
  position: absolute;
  inset: auto auto 0 0;
  z-index: 2;
  width: 100%;
  color: #ffffff;
  padding: 1.05rem 1.15rem;
}

.service-hero__kicker {
  margin: 0 0 0.35rem 0;
  font-size: 0.78rem;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  opacity: 0.95;
}

.service-hero__title {
  margin: 0;
  font-size: clamp(1.55rem, 2.3vw, 2.3rem);
  font-weight: 800;
  line-height: 1.08;
}

.service-hero__meta {
  margin-top: 0.65rem;
  display: flex;
  flex-wrap: wrap;
  gap: 0.5rem 1rem;
  font-size: 0.93rem;
}

.service-hero__meta p {
  margin: 0;
}

.service-list {
  list-style: disc;
  margin: 0;
  padding-left: 1.25rem;
}

.service-list li + li {
  margin-top: 0.3rem;
}
</style>
