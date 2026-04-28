<template>
  <div class="artemisia-shell" :class="{ 'artemisia-shell--page': isPageMode }">
    <template v-if="isPageMode">
      <aside class="artemisia-shell__sidebar">
        <div class="artemisia-shell__title-wrap">
          <i class="fas fa-robot"></i>
          <h2 class="artemisia-shell__title">ArtemisIA</h2>
        </div>
        <p class="artemisia-shell__subtitle">
          Your AI assistant for discovery. Pick a starter prompt or write your own question.
        </p>

        <div class="artemisia-starters artemisia-starters--sidebar">
          <p class="artemisia-starters__title">Starter prompts</p>
          <div v-if="availablePromptCategories.length > 1" class="artemisia-starters__categories">
            <button
              v-for="category in availablePromptCategories"
              :key="category.key"
              type="button"
              class="artemisia-starters__category"
              :class="{ 'artemisia-starters__category--active': selectedPromptCategory === category.key }"
              @click="selectedPromptCategory = category.key"
            >
              {{ category.label }}
            </button>
          </div>
          <div class="artemisia-starters__list artemisia-starters__list--sidebar">
            <button
              v-for="prompt in starterPrompts"
              :key="prompt"
              type="button"
              class="artemisia-starters__prompt"
              @click="applyStarterPrompt(prompt)"
            >
              {{ prompt }}
            </button>
          </div>
        </div>

        <div v-if="artemisIAModule.selectedCount" class="artemisia-context artemisia-context--scrollable">
          <p class="artemisia-context__title">
            <i class="fas fa-check-circle"></i>
            {{ artemisIAModule.selectedCount }} selected record{{ artemisIAModule.selectedCount > 1 ? 's' : '' }}
          </p>
          <div class="artemisia-context__list">
            <div
              v-for="item in selectedPreview"
              :key="`${item.type}-${item.id}`"
              class="artemisia-context__chip"
            >
              <a
                class="artemisia-context__chip-link"
                :href="getSelectedRecordHref(item)"
                target="_blank"
                rel="noopener noreferrer"
              >
                {{ item.title }}
              </a>
              <button
                type="button"
                class="artemisia-context__chip-remove"
                title="Deselect record"
                @click="removeSelected(item)"
              >
                <i class="fas fa-times" aria-hidden="true"></i>
              </button>
            </div>
          </div>
          <div class="artemisia-context__actions">
            <button type="button" class="artemisia-shell__ghost" @click="artemisIAModule.useSelectionContext()">
              Use as context
            </button>
            <button type="button" class="artemisia-shell__ghost" @click="artemisIAModule.clearSelections()">
              Clear selection
            </button>
          </div>
        </div>
      </aside>

      <section class="artemisia-shell__chat">
        <div class="artemisia-shell__chat-header">
          <button type="button" class="artemisia-shell__ghost" @click="artemisIAModule.resetConversation()">
            Clear chat
          </button>
        </div>

        <div ref="messagesRef" class="artemisia-shell__messages">
          <div
            v-for="message in artemisIAModule.messages"
            :key="message.id"
            class="artemisia-msg"
            :class="message.role === 'user' ? 'artemisia-msg--user' : 'artemisia-msg--assistant'"
          >
            {{ message.text }}
          </div>

          <div v-if="artemisIAModule.typing" class="artemisia-msg artemisia-msg--assistant">
            ArtemisIA is typing...
          </div>
        </div>

        <div class="artemisia-shell__composer">
          <textarea
            ref="textInputRef"
            :value="artemisIAModule.draft"
            class="artemisia-shell__input"
            rows="2"
            placeholder="Ask ArtemisIA to refine your search..."
            @input="artemisIAModule.setDraft(($event.target as HTMLTextAreaElement).value)"
            @keydown.enter.exact.prevent="submit()"
          />
          <button type="button" class="artemisia-shell__send" @click="submit()">
            <i class="fas fa-paper-plane"></i>
            Send
          </button>
        </div>
      </section>
    </template>

    <template v-else>
      <div class="artemisia-shell__header">
        <div class="artemisia-shell__title-wrap">
          <i class="fas fa-robot"></i>
          <h2 class="artemisia-shell__title">ArtemisIA</h2>
        </div>
        <div class="artemisia-shell__header-actions">
          <button
            type="button"
            class="artemisia-shell__ghost artemisia-shell__ghost--icon"
            title="Open full chat page"
            @click="openFullscreen()"
          >
            <i class="fas fa-expand" aria-hidden="true"></i>
          </button>
          <button type="button" class="artemisia-shell__ghost" @click="artemisIAModule.resetConversation()">
            Clear chat
          </button>
        </div>
      </div>

      <div v-if="artemisIAModule.selectedCount" class="artemisia-context artemisia-context--scrollable">
        <p class="artemisia-context__title">
          <i class="fas fa-check-circle"></i>
          {{ artemisIAModule.selectedCount }} selected record{{ artemisIAModule.selectedCount > 1 ? 's' : '' }}
        </p>
        <div class="artemisia-context__list">
          <div
            v-for="item in selectedPreview"
            :key="`${item.type}-${item.id}`"
            class="artemisia-context__chip"
          >
            <a
              class="artemisia-context__chip-link"
              :href="getSelectedRecordHref(item)"
              target="_blank"
              rel="noopener noreferrer"
            >
              {{ item.title }}
            </a>
            <button
              type="button"
              class="artemisia-context__chip-remove"
              title="Deselect record"
              @click="removeSelected(item)"
            >
              <i class="fas fa-times" aria-hidden="true"></i>
            </button>
          </div>
        </div>
        <div class="artemisia-context__actions">
          <button type="button" class="artemisia-shell__ghost" @click="artemisIAModule.useSelectionContext()">
            Use as context
          </button>
          <button type="button" class="artemisia-shell__ghost" @click="artemisIAModule.clearSelections()">
            Clear selection
          </button>
        </div>
      </div>

      <div ref="messagesRef" class="artemisia-shell__messages">
        <div
          v-for="message in artemisIAModule.messages"
          :key="message.id"
          class="artemisia-msg"
          :class="message.role === 'user' ? 'artemisia-msg--user' : 'artemisia-msg--assistant'"
        >
          {{ message.text }}
        </div>

        <div v-if="artemisIAModule.typing" class="artemisia-msg artemisia-msg--assistant">
          ArtemisIA is typing...
        </div>
      </div>

      <div class="artemisia-shell__composer">
        <textarea
          ref="textInputRef"
          :value="artemisIAModule.draft"
          class="artemisia-shell__input"
          rows="2"
          placeholder="Ask ArtemisIA to refine your search..."
          @input="artemisIAModule.setDraft(($event.target as HTMLTextAreaElement).value)"
          @keydown.enter.exact.prevent="submit()"
        />
        <button type="button" class="artemisia-shell__send" @click="submit()">
          <i class="fas fa-paper-plane"></i>
          Send
        </button>
      </div>
    </template>
  </div>
</template>

<script setup lang="ts">
import { watch, nextTick, ref } from 'vue';
import { $computed } from 'vue/macros';
import { artemisIAModule } from '@/store/modules';
import { useRouter } from 'vue-router';

type PromptCategoryKey = 'general' | 'spatial' | 'temporal';

const props = defineProps<{
  mode?: 'page' | 'floating',
}>();

const messagesRef = ref<HTMLElement | null>(null);
const textInputRef = ref<HTMLTextAreaElement | null>(null);
const selectedPromptCategory = ref<PromptCategoryKey>('general');
const isPageMode = $computed(() => props.mode === 'page');
const router = useRouter();

const selectedPreview = $computed(() => {
  return artemisIAModule.allSelected;
});

const availablePromptCategories = $computed(() => {
  if (!artemisIAModule.selectedCount) {
    return [{ key: 'general' as PromptCategoryKey, label: 'General' }];
  }

  return [
    { key: 'general' as PromptCategoryKey, label: 'General' },
    { key: 'spatial' as PromptCategoryKey, label: 'Spatial' },
    { key: 'temporal' as PromptCategoryKey, label: 'Temporal' },
  ];
});

const starterPromptsByCategory = $computed(() => {
  if (artemisIAModule.selectedCount) {
    const dataCount = artemisIAModule.selectedDataResources.length;
    const heritageCount = artemisIAModule.selectedHeritageEntities.length;

    return {
      general: [
        'Summarize common traits across my selected records.',
        'Highlight key differences across my selected records.',
        'Suggest 3 precise filters to narrow results from this selection.',
        'Create a query to find records similar to this selection.',
        `Build a step-by-step strategy using my current selection (${dataCount} data resources, ${heritageCount} heritage entities).`,
        'Which metadata fields should I inspect first for these selected records?',
      ],
      spatial: [
        'Suggest spatial filters to cluster these selected records by region.',
        'Suggest filters for location, place, and country based on my selection.',
        'Propose a map-oriented query strategy from this selected set.',
        'Which location-based facets should I prioritize to refine this selection?',
      ],
      temporal: [
        'Suggest temporal filters to compare the selected records by period.',
        'Propose a chronology-focused query path from my selected records.',
        'How should I filter by date range first, then by type?',
        'Suggest a temporal drill-down strategy using periods and chronology.',
      ],
    };
  }

  return {
    general: [
      'Help me find relevant data resources for my topic.',
      'Suggest search filters to get more precise results.',
      'Explain how to compare data resources and heritage entities.',
    ],
  };
});

const starterPrompts = $computed(() => {
  const category = selectedPromptCategory.value;
  const prompts = starterPromptsByCategory[category];

  return prompts || starterPromptsByCategory.general || [];
});

const applyStarterPrompt = (prompt: string) => {
  artemisIAModule.setDraft(prompt);
  textInputRef.value?.focus();
};

const submit = () => {
  artemisIAModule.submitMessage();
  textInputRef.value?.focus();
};

const getSelectedRecordHref = (item: { id: string, type: string }) => {
  const basePath = String(process.env.ARIADNE_PUBLIC_PATH || '/');
  const normalizedBase = basePath.endsWith('/') ? basePath : `${basePath}/`;
  const routePath = item.type === 'heritage-entity'
    ? `heritage-entities/${item.id}`
    : `resource/${item.id}`;

  return `${normalizedBase}${routePath}`;
};

const removeSelected = (item: any) => {
  if (item.type === 'heritage-entity') {
    artemisIAModule.toggleHeritageEntity(item);
    return;
  }

  artemisIAModule.toggleDataResource(item);
};

const openFullscreen = () => {
  router.push('/artemisia');
};

watch(() => artemisIAModule.messages.length, async () => {
  await nextTick();
  if (messagesRef.value) {
    messagesRef.value.scrollTop = messagesRef.value.scrollHeight;
  }
});

watch(() => artemisIAModule.panelOpen, async (isOpen) => {
  if (!isOpen || props.mode === 'page') {
    return;
  }

  await nextTick();
  textInputRef.value?.focus();
});

watch(() => artemisIAModule.selectedCount, () => {
  if (!artemisIAModule.selectedCount && selectedPromptCategory.value !== 'general') {
    selectedPromptCategory.value = 'general';
  }
});
</script>
