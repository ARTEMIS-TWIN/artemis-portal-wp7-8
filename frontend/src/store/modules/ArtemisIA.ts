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

  submitMessage(text?: string) {
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

    const reply = this.buildReply(content);
    setTimeout(() => {
      this.messages.push({
        id: this.nextMessageId++,
        role: 'assistant',
        text: reply,
        ts: Date.now(),
      });
      this.typing = false;
    }, 380);
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

  private buildReply(question: string): string {
    const total = this.selectedCount;
    const hints = [
      'Try combining one keyword with one filter first, then narrow gradually.',
      'You can compare selected records by type, place, period, and publisher/owner.',
      'If results are broad, apply one temporal and one spatial filter together.',
    ];

    if (total > 0) {
      return `I can use your ${ total } selected record${ total > 1 ? 's' : '' } as context. ${ hints[0] } ${ hints[1] }`;
    }

    if (/compare|difference|similar/i.test(question)) {
      return `Select two or more records and I will help structure a comparison. ${ hints[1] }`;
    }

    return hints[Math.floor(Math.random() * hints.length)];
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
}
