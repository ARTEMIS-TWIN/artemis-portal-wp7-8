import axios from 'axios';

type ArtemisIARecord = {
  id: string,
  title: string,
  type: 'data-resource' | 'heritage-entity',
  subtitle?: string,
};

type ArtemisIAMessage = {
  id: number,
  role: 'assistant' | 'user',
  text: string,
  ts: number,
};

export class ArtemisIAModule {
  panelOpen: boolean = false;
  draft: string = '';
  sessionId: string = this.createSessionId();
  lastScopeUsed: 'selected_only' | 'global' | '' = '';
  messages: ArtemisIAMessage[] = [
    {
      id: 1,
      role: 'assistant',
      text: 'Hello, I am ArtemisIA. I can help you refine your searches and compare selected records.',
      ts: Date.now(),
    },
  ];
  selectedDataResources: ArtemisIARecord[] = [];
  selectedHeritageEntities: ArtemisIARecord[] = [];
  nextMessageId: number = 2;
  typing: boolean = false;

  openPanel() {
    this.panelOpen = true;
  }

  closePanel() {
    this.panelOpen = false;
  }

  togglePanel() {
    this.panelOpen = !this.panelOpen;
  }

  setDraft(value: string) {
    this.draft = value;
  }

  clearDraft() {
    this.draft = '';
  }

  clearSelections() {
    this.selectedDataResources = [];
    this.selectedHeritageEntities = [];
  }

  resetConversation() {
    this.sessionId = this.createSessionId();
    this.lastScopeUsed = '';
    this.messages = [
      {
        id: this.nextMessageId++,
        role: 'assistant',
        text: 'Conversation reset. Select records or type a question and I will guide your search.',
        ts: Date.now(),
      },
    ];
  }

  toggleDataResource(record: ArtemisIARecord) {
    this.toggleRecord(record, this.selectedDataResources);
  }

  toggleHeritageEntity(record: ArtemisIARecord) {
    this.toggleRecord(record, this.selectedHeritageEntities);
  }

  isDataResourceSelected(id: string): boolean {
    return this.selectedDataResources.some((item) => item.id === id);
  }

  isHeritageEntitySelected(id: string): boolean {
    return this.selectedHeritageEntities.some((item) => item.id === id);
  }

  useSelectionContext() {
    this.openPanel();

    if (!this.selectedCount) {
      this.draft = 'Help me improve my search strategy.';
      return;
    }

    this.draft = `Compare my ${ this.selectedCount } selected records and suggest the best next filters.`;
  }

  async submitMessage(text?: string) {
    const content = String(text ?? this.draft).trim();
    if (!content) {
      return;
    }

    this.messages.push({
      id: this.nextMessageId++,
      role: 'user',
      text: content,
      ts: Date.now(),
    });

    this.draft = '';
    this.typing = true;

    try {
      const payload = {
        message: content,
        sessionId: this.sessionId,
        selectedRecords: this.allSelected.map((record) => ({
          id: record.id,
          type: record.type,
          title: record.title,
        })),
        history: this.messages.slice(-10).map((message) => ({
          role: message.role,
          text: message.text,
        })),
      };

      const response = await axios.post(process.env.apiUrl + '/artemisia/chat', payload);
      const data = response?.data || {};
      const reply = String(data.response || '').trim();

      if (data.sessionId) {
        this.sessionId = String(data.sessionId);
      }

      if (data.scopeUsed === 'selected_only' || data.scopeUsed === 'global') {
        this.lastScopeUsed = data.scopeUsed;
      } else {
        this.lastScopeUsed = '';
      }

      this.messages.push({
        id: this.nextMessageId++,
        role: 'assistant',
        text: reply || 'I could not generate a response right now. Please try again.',
        ts: Date.now(),
      });
    } catch (error: any) {
      const backendMessage = String(error?.response?.data?.message || '').trim();
      this.messages.push({
        id: this.nextMessageId++,
        role: 'assistant',
        text: backendMessage || 'ArtemisIA is temporarily unavailable. Please check configuration and try again.',
        ts: Date.now(),
      });
    } finally {
      this.typing = false;
    }
  }

  private toggleRecord(record: ArtemisIARecord, target: ArtemisIARecord[]) {
    const id = String(record?.id || '').trim();
    if (!id) {
      return;
    }

    const index = target.findIndex((item) => item.id === id);

    if (index >= 0) {
      target.splice(index, 1);
      return;
    }

    target.push({
      id,
      title: String(record?.title || 'Untitled'),
      type: record.type,
      subtitle: record.subtitle ? String(record.subtitle) : '',
    });
  }

  get selectedCount(): number {
    return this.selectedDataResources.length + this.selectedHeritageEntities.length;
  }

  get selectionPrompt(): string {
    const dataCount = this.selectedDataResources.length;
    const heritageCount = this.selectedHeritageEntities.length;

    if (!this.selectedCount) {
      return 'Help me improve my search query.';
    }

    return `Use my selected records context (${ dataCount } data resources, ${ heritageCount } heritage entities) and suggest better filters and next queries.`;
  }

  get allSelected(): ArtemisIARecord[] {
    return [...this.selectedDataResources, ...this.selectedHeritageEntities];
  }

  private createSessionId(): string {
    return `artemisia-${ Date.now() }-${ Math.random().toString(36).slice(2, 10) }`;
  }
}
