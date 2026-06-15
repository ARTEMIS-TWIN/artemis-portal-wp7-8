<?php

namespace App\Services\Artemisia;

use App\Services\HeritageEntitySearchService;
use App\Services\PortalSearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use RuntimeException;

class ArtemisiaChatService
{
    private const SESSION_CACHE_PREFIX = 'artemisia:session:';

    public function __construct(
        private readonly MistralClient $mistral,
        private readonly PortalSearchService $portalSearch,
        private readonly HeritageEntitySearchService $heritageSearch,
    ) {
    }

    /**
     * @param  list<array{id:string,type:string,title?:string}>  $selectedRecords
     * @param  list<array{role:string,text:string}>  $history
     * @return array<string, mixed>
     */
    public function chat(
        string $message,
        array $selectedRecords = [],
        ?string $sessionId = null,
        ?string $scopeOverride = null,
        array $history = [],
    ): array {
        $sessionId = $sessionId && trim($sessionId) !== '' ? trim($sessionId) : (string) Str::uuid();
        $selectedRecords = $this->normalizeSelectedRecords($selectedRecords);
        $hasSelectedRecords = !empty($selectedRecords);

        $nlu = $this->runNlu($message);
        $scopeUsed = self::resolveScopeDecision(
            hasSelectedRecords: $hasSelectedRecords,
            explicitGlobal: (bool) ($nlu['explicit_global_scope'] ?? false),
            explicitSelectedOnly: (bool) ($nlu['explicit_selected_scope'] ?? false),
            scopeOverride: $scopeOverride,
        );

        $groundingRecords = $scopeUsed === 'selected_only'
            ? $this->loadSelectedContext($selectedRecords)
            : $this->loadGlobalContext($message);

        $memory = $this->loadSessionMemory($sessionId);
        $recentClientHistory = $this->normalizeHistory($history);
        $assistantText = $this->generateGroundedAnswer(
            userMessage: $message,
            scopeUsed: $scopeUsed,
            records: $groundingRecords,
            memory: array_slice(array_merge($memory, $recentClientHistory), -10),
        );

        $updatedMemory = $this->rememberTurn($sessionId, $memory, $message, $assistantText);

        return [
            'sessionId' => $sessionId,
            'scopeUsed' => $scopeUsed,
            'intent' => $nlu['intent'] ?? 'unknown',
            'response' => $assistantText,
            'grounding' => [
                'recordsUsed' => count($groundingRecords),
                'selectedRecordsCount' => count($selectedRecords),
            ],
            'historySize' => count($updatedMemory),
        ];
    }

    public static function resolveScopeDecision(
        bool $hasSelectedRecords,
        bool $explicitGlobal,
        bool $explicitSelectedOnly,
        ?string $scopeOverride = null,
    ): string {
        $override = trim((string) $scopeOverride);

        if ($override === 'global') {
            return 'global';
        }

        if ($override === 'selected_only') {
            return $hasSelectedRecords ? 'selected_only' : 'global';
        }

        if (!$hasSelectedRecords) {
            return 'global';
        }

        if ($explicitSelectedOnly) {
            return 'selected_only';
        }

        if ($explicitGlobal) {
            return 'global';
        }

        return 'selected_only';
    }

    /**
     * @param  list<array{id:string,type:string,title?:string}>  $selectedRecords
     * @return list<array<string,mixed>>
     */
    private function loadSelectedContext(array $selectedRecords): array
    {
        $records = [];
        $request = Request::create('/api/getRecord', 'GET');

        foreach ($selectedRecords as $selected) {
            $id = $selected['id'];
            $type = $selected['type'];

            if ($type === 'heritage-entity') {
                $entity = $this->heritageSearch->getEntity($id);
                if ($entity !== null) {
                    $records[] = $this->normalizeHeritageEntity($entity['id'] ?? $id, $entity['data'] ?? []);
                }
                continue;
            }

            $record = $this->portalSearch->getRecord($id, $request);
            if ($record !== null) {
                $records[] = $this->normalizeDataResource($id, $record);
            }
        }

        return array_slice($records, 0, 20);
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function loadGlobalContext(string $message): array
    {
        $records = [];
        $request = Request::create('/api/search', 'GET', [
            'q' => $message,
            'size' => 6,
            'page' => 1,
        ]);

        $dataResources = $this->portalSearch->search($request);
        foreach (Arr::get($dataResources, 'hits', []) as $hit) {
            $records[] = $this->normalizeDataResource((string) ($hit['id'] ?? ''), Arr::get($hit, 'data', []));
        }

        $entityRequest = Request::create('/api/heritage-entities/search', 'GET', [
            'q' => $message,
            'size' => 6,
            'page' => 1,
        ]);

        $heritageEntities = $this->heritageSearch->search($entityRequest);
        foreach (Arr::get($heritageEntities, 'hits', []) as $hit) {
            $records[] = $this->normalizeHeritageEntity((string) ($hit['id'] ?? ''), Arr::get($hit, 'data', []));
        }

        return array_slice($records, 0, 16);
    }

    /**
     * @param  list<array<string,mixed>>  $memory
     * @param  list<array<string,mixed>>  $records
     */
    private function generateGroundedAnswer(string $userMessage, string $scopeUsed, array $records, array $memory): string
    {
        $messages = [
            [
                'role' => 'system',
                'content' => <<<PROMPT
You are a cultural heritage assistant embedded in a platform that contains structured data about cultural heritage.

The platform includes:
1) "Heritage Entities": specific cultural assets (e.g., monuments, artifacts, sites).
2) "Data Resources": datasets or collections that group or describe heritage entities.

Each record contains structured data, metadata, and links to related records.

---

CONTEXT SOURCES

You will receive context in two possible forms:

- SELECTED RECORDS: explicitly chosen by the user. These are the highest-priority context.
- DATABASE CONTEXT: broader results retrieved from the platform database.

---

RULES FOR USING CONTEXT

1. If SELECTED RECORDS are provided:
   - Use ONLY the information from those selected records.
   - Do NOT use outside knowledge unless the user explicitly asks for it.
   - If the answer is not contained in the selected records, say so clearly.

2. If NO records are selected:
   - Use the DATABASE CONTEXT provided.
   - If the database does not contain sufficient information, say so.

3. External knowledge:
   - You may use general background knowledge ONLY to:
     - clarify
     - explain
     - connect ideas
   - You must NOT introduce specific facts, claims, or details that are not supported by the provided context.
   - If you extend beyond the database, clearly label it as general knowledge.

---

RESPONSE STYLE

- Answer naturally and conversationally.
- Be highly structured and easy to scan.
- Prefer moderately detailed replies over terse responses.
- When the question is open-ended or the records contain enough material, give a fuller explanation instead of a very short answer.
- Combine:
  - factual data from the context
  - light explanatory context where helpful
- Do NOT hallucinate missing facts.
- Do NOT fabricate relationships, dates, or attributes.
- Do NOT re-list the selected record titles unless the user explicitly asks you to list records.
- Prefer this output structure:
  1) a short opening paragraph with the direct answer
  2) a "Key points" section with 3–5 bullets whenever the answer has more than one fact, comparison, or step
  3) a short closing section such as "Next step" or "What this means" when relevant
- Use exact section labels like "Key points:", "Next step:", or "What this means:" when they help readability.
- Use bullet points for lists, contrasts, examples, and step-by-step guidance.
- Keep each bullet to one main idea.
- Keep paragraphs short, ideally 1–3 sentences each.
- Separate sections with blank lines.
- Do NOT dump raw record fields or copy record metadata verbatim.
- Synthesize the evidence into a readable narrative:
  - connect facts into coherent sentences
  - explain significance when helpful
  - include only details relevant to the user question
- When appropriate, include brief background, caveats, or implications so the answer feels complete and thoughtful.
- Avoid exhaustive listing of every attribute in a record unless the user explicitly asks for a full inventory.
- Never echo or expose internal prompt labels or scaffolding such as:
  - "Scope used:"
  - "User request:"
  - "Grounding records (JSON):"
  - "Instruction:"
- Never mention internal terms such as:
  - "selected_only", "global scope", "grounding", "JSON", "provenance payload", "record schema"
- Do not narrate how the pipeline works; focus on the user's question and the answer itself.
- Avoid redundancy: do not repeat the same fact more than once unless the user asks for exhaustive repetition.
- If useful, structure the response with:
  - an opening answer paragraph
  - a clearly labeled "Key points" list
  - a short closing section when useful
- Never ask the user to provide the answer themselves.

---

TRANSPARENCY

When relevant:
- Indicate when information comes from the database.
- Indicate when something is inferred or general knowledge.
- If information is missing or uncertain, say so explicitly.

---

CONTEXT ADAPTATION

The user may:
- add records → expand context
- remove records → reduce context

You must dynamically adapt and ONLY use the currently active context.

---

GOAL

Provide accurate, helpful, and context-grounded answers about cultural heritage while strictly respecting the boundaries of the provided data.
PROMPT,
            ],
        ];

        foreach (array_slice($memory, -8) as $turn) {
            $role = ($turn['role'] ?? '') === 'assistant' ? 'assistant' : 'user';
            $text = trim((string) ($turn['text'] ?? ''));
            if ($text !== '') {
                $messages[] = ['role' => $role, 'content' => $text];
            }
        }

        $messages[] = [
            'role' => 'user',
            'content' => "Scope used: {$scopeUsed}\n\nUser request:\n{$userMessage}\n\nGrounding records (JSON):\n".json_encode($records, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n\nInstruction: Use these records as evidence, but respond with a concise, narrative explanation rather than a field-by-field dump.",
        ];

        try {
            $response = $this->mistral->chat($messages, $this->mistral->largeModel(), 0.35);
            $text = $this->sanitizeAssistantText($this->mistral->extractText($response));

            if ($text !== '') {
                return $text;
            }

            throw new RuntimeException('The LLM returned an empty response.');
        } catch (\Throwable) {
            if ($this->forceLlmResponses()) {
                throw new RuntimeException($this->llmUnavailableMessage());
            }
        }

        if (empty($records)) {
            return $this->buildNoContextFallback($userMessage, $scopeUsed);
        }

        return $this->buildDeterministicFallback($userMessage, $scopeUsed, $records);
    }

    /**
     * @return array{intent:string,explicit_global_scope:bool,explicit_selected_scope:bool}
     */
    private function runNlu(string $userMessage): array
    {
        $prompt = <<<PROMPT
You are an intent and scope classifier.

Your task is to analyze the user message and extract:
1) The user’s intent (as a short, normalized label)
2) Whether the user explicitly requests a specific scope

---

DEFINITIONS

Intent:
- A concise label (2–4 words max)
- Use lowercase with underscores
- Examples: "ask_information", "compare_items", "request_summary", "explore_topic", "clarify_detail"

Scope types:

1) explicit_selected_scope = true
   - The user explicitly refers to selected items or context
   - Examples:
     - "these items"
     - "the selected records"
     - "compare these"
     - "based on what I selected"

2) explicit_global_scope = true
   - The user explicitly asks for broader/general knowledge beyond selected items
   - Examples:
     - "in general"
     - "more broadly"
     - "outside the database"
     - "not just these items"

IMPORTANT:
- Both flags can be false
- Both flags should NEVER be true at the same time
- Only mark a flag as true if the intent is explicit (not implied)

---

OUTPUT RULES

- Return ONLY valid JSON
- Do NOT include explanations or extra text
- Do NOT include code blocks
- Use exactly this schema:

{
  "intent": "string",
  "explicit_global_scope": boolean,
  "explicit_selected_scope": boolean
}

---

MESSAGE TO ANALYZE:
{$userMessage}

PROMPT;

        try {
            $response = $this->mistral->chat([
                ['role' => 'system', 'content' => 'You are a strict JSON extractor. Output only JSON.'],
                ['role' => 'user', 'content' => $prompt],
            ], $this->mistral->mediumModel(), 0.0);

            $decoded = $this->decodeJsonObject($this->mistral->extractText($response));

            if (!empty($decoded)) {
                return [
                    'intent' => (string) ($decoded['intent'] ?? 'unknown'),
                    'explicit_global_scope' => (bool) ($decoded['explicit_global_scope'] ?? false),
                    'explicit_selected_scope' => (bool) ($decoded['explicit_selected_scope'] ?? false),
                ];
            }
        } catch (\Throwable) {
            // Fall back to heuristic scope parsing below.
        }

        $lower = Str::lower($userMessage);

        $globalHints = ['search all', 'all records', 'ignore selected', 'outside selected', 'whole database', 'global search'];
        $selectedHints = ['selected records', 'selected items', 'only these records', 'just selected', 'only selected'];

        return [
            'intent' => 'search_assistance',
            'explicit_global_scope' => collect($globalHints)->contains(fn (string $hint): bool => str_contains($lower, $hint)),
            'explicit_selected_scope' => collect($selectedHints)->contains(fn (string $hint): bool => str_contains($lower, $hint)),
        ];
    }

    /**
     * @param  list<array<string,mixed>>  $history
     * @return list<array{role:string,text:string}>
     */
    private function normalizeHistory(array $history): array
    {
        $normalized = [];
        $boilerplateAssistantMessages = [
            'Hello, I am Artemisia. I can help you refine your searches and compare selected records.',
            'Conversation reset. Select records or type a question and I will guide your search.',
        ];

        foreach ($history as $item) {
            $role = ($item['role'] ?? '') === 'assistant' ? 'assistant' : 'user';
            $text = trim((string) ($item['text'] ?? ''));

            if ($role === 'assistant' && in_array($text, $boilerplateAssistantMessages, true)) {
                continue;
            }

            if ($text !== '') {
                $normalized[] = ['role' => $role, 'text' => $text];
            }
        }

        return array_slice($normalized, -10);
    }

    /**
     * @param  list<array<string,mixed>>  $memory
     * @return list<array{role:string,text:string}>
     */
    private function rememberTurn(string $sessionId, array $memory, string $userMessage, string $assistantMessage): array
    {
        $memory[] = ['role' => 'user', 'text' => $userMessage];
        $memory[] = ['role' => 'assistant', 'text' => $assistantMessage];
        $memory = array_slice($memory, -20);

        Cache::put(self::SESSION_CACHE_PREFIX.$sessionId, $memory, now()->addHours(6));

        return $memory;
    }

    /**
     * @return list<array{role:string,text:string}>
     */
    private function loadSessionMemory(string $sessionId): array
    {
        $memory = Cache::get(self::SESSION_CACHE_PREFIX.$sessionId, []);

        return is_array($memory) ? $this->normalizeHistory($memory) : [];
    }

    /**
     * @param  list<array{id:string,type:string,title?:string}>  $selectedRecords
     * @return list<array{id:string,type:string,title?:string}>
     */
    private function normalizeSelectedRecords(array $selectedRecords): array
    {
        $normalized = [];

        foreach ($selectedRecords as $record) {
            $id = trim((string) ($record['id'] ?? ''));
            $type = trim((string) ($record['type'] ?? ''));

            if ($id === '' || !in_array($type, ['data-resource', 'heritage-entity'], true)) {
                continue;
            }

            $normalized[] = [
                'id' => $id,
                'type' => $type,
                'title' => trim((string) ($record['title'] ?? '')),
            ];
        }

        return array_values($normalized);
    }

    /**
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    private function normalizeDataResource(string $id, array $data): array
    {
        $title = trim((string) Arr::get($data, 'title.text', Arr::get($data, 'title', '')));
        $description = trim((string) Arr::get($data, 'description.text', Arr::get($data, 'description', '')));

        return [
            'kind' => 'data-resource',
            'id' => $id,
            'title' => $title !== '' ? $title : 'Untitled resource',
            'description' => Str::limit(strip_tags($description), 320, '...'),
            'resourceType' => Arr::get($data, 'resourceType'),
            'publisher' => Arr::get($data, 'publisher.0.name'),
            'country' => Arr::get($data, 'country.0.name'),
            'place' => Arr::get($data, 'spatial.0.placeName'),
            'period' => Arr::get($data, 'temporal.0.periodName'),
            'url' => '/resource/'.$id,
        ];
    }

    /**
     * @param  array<string,mixed>  $data
     * @return array<string,mixed>
     */
    private function normalizeHeritageEntity(string $id, array $data): array
    {
        $description = trim((string) ($data['description'] ?? ''));

        return [
            'kind' => 'heritage-entity',
            'id' => $id,
            'title' => trim((string) ($data['label'] ?? '')) ?: 'Untitled entity',
            'description' => Str::limit(strip_tags($description), 320, '...'),
            'entityType' => $data['entityType'] ?? null,
            'classification' => $data['classificationLabels'] ?? [],
            'periods' => $data['periodLabels'] ?? [],
            'place' => $data['placeLabel'] ?? null,
            'country' => $data['countryLabel'] ?? null,
            'url' => '/heritage-entities/'.$id,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function decodeJsonObject(string $text): array
    {
        $trimmed = trim($text);

        if ($trimmed === '') {
            return [];
        }

        if (preg_match('/```json\s*(\{.*\})\s*```/is', $trimmed, $matches) === 1) {
            $trimmed = trim((string) ($matches[1] ?? $trimmed));
        }

        $decoded = json_decode($trimmed, true);
        if (is_array($decoded)) {
            return $decoded;
        }

        $firstBrace = strpos($trimmed, '{');
        $lastBrace = strrpos($trimmed, '}');

        if ($firstBrace === false || $lastBrace === false || $lastBrace <= $firstBrace) {
            return [];
        }

        $candidate = substr($trimmed, $firstBrace, ($lastBrace - $firstBrace) + 1);
        $decoded = json_decode($candidate, true);

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param  list<array<string,mixed>>  $records
     */
    private function buildDeterministicFallback(string $userMessage, string $scopeUsed, array $records): string
    {
        $intent = $this->inferFallbackIntent($userMessage);
        $resourceCount = 0;
        $entityCount = 0;
        $periodHints = [];
        $placeHints = [];
        $typeHints = [];

        foreach ($records as $record) {
            if (($record['kind'] ?? null) === 'heritage-entity') {
                $entityCount++;
            } else {
                $resourceCount++;
            }

            $period = trim((string) ($record['period'] ?? (($record['periods'][0] ?? '') ?: '')));
            if ($period !== '') {
                $periodHints[] = $period;
            }

            $place = trim((string) ($record['place'] ?? ''));
            if ($place !== '') {
                $placeHints[] = $place;
            }

            $type = trim((string) ($record['resourceType'] ?? ($record['entityType'] ?? '')));
            if ($type !== '') {
                $typeHints[] = $type;
            }
        }

        $periodHints = array_values(array_unique($periodHints));
        $placeHints = array_values(array_unique($placeHints));
        $typeHints = array_values(array_unique($typeHints));

        $scopeText = $scopeUsed === 'selected_only'
            ? 'I based this answer only on your selected records.'
            : 'I based this answer on matching records from the portal.';

        $recordCount = count($records);
        $recordSummary = sprintf(
            'I found %d relevant %s (%d data resources and %d heritage entities).',
            $recordCount,
            $recordCount === 1 ? 'record' : 'records',
            $resourceCount,
            $entityCount,
        );

        $intentResponse = match ($intent) {
            'compare' => $this->compareFallbackText($typeHints, $placeHints, $periodHints),
            'spatial' => $this->spatialFallbackText($placeHints),
            'temporal' => $this->temporalFallbackText($periodHints),
            'count' => "You’re looking for quantity and coverage, so the key number is {$recordCount} matching records in this scope.",
            'details' => $this->detailsFallbackText($typeHints),
            default => $this->generalFallbackText($typeHints),
        };

        $refinementHints = [];
        if (!empty($typeHints)) {
            $refinementHints[] = 'type';
        }
        if (!empty($placeHints)) {
            $refinementHints[] = 'place';
        }
        if (!empty($periodHints)) {
            $refinementHints[] = 'period';
        }

        $refinementText = empty($refinementHints)
            ? 'Would you like me to narrow this to a specific type, place, or period?'
            : 'If you want, I can narrow this next by '.implode(', ', array_slice($refinementHints, 0, 3)).'.';

        return trim($scopeText."\n\n".$recordSummary."\n\n".$intentResponse."\n\n".$refinementText);
    }

    private function buildNoContextFallback(string $userMessage, string $scopeUsed): string
    {
        $intent = $this->inferFallbackIntent($userMessage);
        $scopeText = $scopeUsed === 'selected_only'
            ? 'I checked only your selected records and could not find enough matching detail for that request.'
            : 'I could not find enough matching records for that request in the current search scope.';

        $nextStep = match ($intent) {
            'spatial' => 'Try adding a place/country name and one subject filter.',
            'temporal' => 'Try adding a period term or a date range together with a keyword.',
            'compare' => 'Try selecting at least two records, then ask me to compare them by type/place/period.',
            default => 'Try adding one clear topic keyword and one filter (type, place, or period).',
        };

        return $scopeText."\n\nNext step:\n- ".$nextStep."\n\nIf you want, I can suggest a specific query to try.";
    }

    private function inferFallbackIntent(string $userMessage): string
    {
        $message = Str::lower($userMessage);

        if (str_contains($message, 'compare') || str_contains($message, 'difference') || str_contains($message, 'similar')) {
            return 'compare';
        }

        if (str_contains($message, 'where') || str_contains($message, 'location') || str_contains($message, 'place') || str_contains($message, 'country') || str_contains($message, 'map')) {
            return 'spatial';
        }

        if (str_contains($message, 'when') || str_contains($message, 'period') || str_contains($message, 'chronology') || str_contains($message, 'date') || str_contains($message, 'temporal')) {
            return 'temporal';
        }

        if (str_contains($message, 'how many') || str_contains($message, 'count') || str_contains($message, 'number of')) {
            return 'count';
        }

        if (str_contains($message, 'detail') || str_contains($message, 'tell me about') || str_contains($message, 'what is')) {
            return 'details';
        }

        return 'general';
    }

    /**
     * @param  list<string>  $typeHints
     * @param  list<string>  $placeHints
     * @param  list<string>  $periodHints
     */
    private function compareFallbackText(array $typeHints, array $placeHints, array $periodHints): string
    {
        $points = [];

        if (!empty($typeHints)) {
            $points[] = 'Types in scope: '.implode(', ', array_slice($typeHints, 0, 3)).'.';
        }

        if (!empty($placeHints)) {
            $points[] = 'Places in scope: '.implode(', ', array_slice($placeHints, 0, 3)).'.';
        }

        if (!empty($periodHints)) {
            $points[] = 'Periods in scope: '.implode(', ', array_slice($periodHints, 0, 3)).'.';
        }

        if (empty($points)) {
            $points[] = 'I can compare by type, place, and period once those fields are available.';
        }

        return "Key points:\n- ".implode("\n- ", $points);
    }

    /**
     * @param  list<string>  $placeHints
     */
    private function spatialFallbackText(array $placeHints): string
    {
        if (empty($placeHints)) {
            return 'I can help with spatial analysis, but the current records do not expose strong place values yet.';
        }

        return "Key points:\n- Spatial values available include ".implode(', ', array_slice($placeHints, 0, 4)).'.';
    }

    /**
     * @param  list<string>  $periodHints
     */
    private function temporalFallbackText(array $periodHints): string
    {
        if (empty($periodHints)) {
            return 'I can help with temporal analysis, but the current records do not expose clear period values yet.';
        }

        return "Key points:\n- Temporal values available include ".implode(', ', array_slice($periodHints, 0, 4)).'.';
    }

    /**
     * @param  list<string>  $typeHints
     */
    private function detailsFallbackText(array $typeHints): string
    {
        $typePart = empty($typeHints) ? '' : 'I can expand details by type: '.implode(', ', array_slice($typeHints, 0, 3)).'. ';

        return trim($typePart.'I can further detail materials, period, place, and relationships based on your next question.');
    }

    /**
     * @param  list<string>  $typeHints
     */
    private function generalFallbackText(array $typeHints): string
    {
        $parts = ['Based on your question, here is what is most relevant in the current data scope.'];

        if (!empty($typeHints)) {
            $parts[] = 'I also see useful type groupings such as '.implode(', ', array_slice($typeHints, 0, 3)).'.';
        }

        return implode(' ', $parts);
    }

    private function forceLlmResponses(): bool
    {
        return (bool) config('services.artemisia.force_llm', true);
    }

    private function llmUnavailableMessage(): string
    {
        $provider = (string) config('services.artemisia.llm_provider', 'mistral');

        if ($provider === 'ollama') {
            return 'Artemisia could not generate an LLM response. Check that Ollama is running, the model is installed, and OLLAMA_BASE_URL is reachable.';
        }

        return 'Artemisia could not generate an LLM response. Check MISTRAL_API_KEY and network access to Mistral.';
    }

    private function sanitizeAssistantText(string $text): string
    {
        $clean = trim($text);

        if ($clean === '') {
            return '';
        }

        $leakMarkers = [
            'Scope used:',
            'User request:',
            'Grounding records (JSON):',
            'Instruction:',
        ];

        foreach ($leakMarkers as $marker) {
            if (str_contains($clean, $marker)) {
                return '';
            }
        }

        $clean = $this->removeInternalProvenanceSentences($clean);
        $clean = $this->deduplicateSentences($clean);

        return trim($clean);
    }

    private function removeInternalProvenanceSentences(string $text): string
    {
        $sentences = preg_split('/(?<=[\.\!\?])\s+/u', $text) ?: [];
        $filtered = [];
        $blockedPatterns = [
            '/\bselected_only\b/i',
            '/\bglobal scope\b/i',
            '/\bscope used\b/i',
            '/\bgrounding\b/i',
            '/\bgrounding records?\b/i',
            '/\bjson\b/i',
            '/\bprovenance\b/i',
            '/\buser request\b/i',
            '/\binstruction\b/i',
        ];

        foreach ($sentences as $sentence) {
            $trimmed = trim($sentence);
            if ($trimmed === '') {
                continue;
            }

            $isBlocked = false;
            foreach ($blockedPatterns as $pattern) {
                if (preg_match($pattern, $trimmed) === 1) {
                    $isBlocked = true;
                    break;
                }
            }

            if (!$isBlocked) {
                $filtered[] = $trimmed;
            }
        }

        return implode(' ', $filtered);
    }

    private function deduplicateSentences(string $text): string
    {
        $sentences = preg_split('/(?<=[\.\!\?])\s+/u', $text) ?: [];
        $seen = [];
        $deduped = [];

        foreach ($sentences as $sentence) {
            $trimmed = trim($sentence);
            if ($trimmed === '') {
                continue;
            }

            $key = mb_strtolower(preg_replace('/\s+/u', ' ', $trimmed) ?? $trimmed);
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $deduped[] = $trimmed;
        }

        return implode(' ', $deduped);
    }
}
