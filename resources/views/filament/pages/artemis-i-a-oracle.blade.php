<x-filament::page>
    @php
        $demo = $this->getDemoCase();
        $data = $demo['data'];
        $ttlPreview = $demo['ttlPreview'];
    @endphp

    <div x-data="{ uploadedFileName: null }" style="max-width: 1000px; line-height: 1.6;">

        <p style="color: #9ca3af; margin-bottom: 8px;">
            LLM-assisted Semantic Extraction and Ontology Tagging Pipeline
        </p>

        <p style="margin-bottom: 28px;">
            <strong>Artemisia Oracle</strong> is an AI-assisted pipeline designed to transform
            unstructured cultural heritage documentation into structured, ontology-aligned knowledge.
        </p>

        <div style="margin-bottom: 24px;">
            <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">

                <label
                    for="file-upload"
                    style="
                        display: inline-block;
                        width: fit-content;
                        padding: 10px 16px;
                        border-radius: 8px;
                        background: {{ $activeStep === 'upload' ? '#f59e0b' : '#374151' }};
                        color: white;
                        cursor: pointer;
                        font-size: 14px;
                        font-weight: 600;
                    "
                >
                    Upload document
                </label>

                <input
                    id="file-upload"
                    type="file"
                    accept=".pdf,.txt,.doc,.docx"
                    style="display: none;"
                    wire:model="uploadedDocument"
                    x-on:change="uploadedFileName = $event.target.files[0]?.name"
                >

                <x-filament::button
                    :color="$activeStep === 'alignment' ? 'primary' : 'gray'"
                    wire:click="runExtraction"
                >
                    Run extraction
                </x-filament::button>

                <x-filament::button
                    :color="$activeStep === 'ttl' ? 'primary' : 'gray'"
                    wire:click="previewTtl"
                >
                    Preview TTL
                </x-filament::button>

                <x-filament::button color="gray">
                    Download TTL
                </x-filament::button>

            </div>
        </div>

        @if ($activeStep === 'upload')
            <div style="border-top: 1px solid #374151; padding-top: 20px; margin-bottom: 32px;">
                <p style="color: #9ca3af; margin-bottom: 4px;">
                    Uploaded document
                </p>

                <template x-if="uploadedFileName">
                    <strong>
                        Document uploaded: <span x-text="uploadedFileName"></span>
                    </strong>
                </template>

                <template x-if="!uploadedFileName">
                    <p style="color: #9ca3af;">
                        No document uploaded yet.
                    </p>
                </template>
            </div>
        @endif

        @if ($activeStep === 'alignment')
            <div style="margin-bottom: 32px;">
                <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 12px;">
                    Proposed ontology alignment
                </h3>

                <p style="color: #9ca3af; margin-bottom: 16px;">
                    The extracted entities are matched against domain ontologies.
                    Each proposed class is associated with a confidence score
                    and requires expert validation before TTL generation.
                </p>

                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 1px solid #374151;">
                            <th style="text-align: left; padding: 10px;">Extracted entity</th>
                            <th style="text-align: left; padding: 10px;">Suggested class</th>
                            <th style="text-align: left; padding: 10px;">Confidence</th>
                            <th style="text-align: left; padding: 10px;">Human validation</th>
                        </tr>
                    </thead>

                    <tbody>
                        @php
                            $entities = $extractedEntities !== [] ? $extractedEntities : ($data['entities'] ?? []);
                        @endphp

                        @forelse ($entities as $entity)
                            <tr style="border-bottom: 1px solid #1f2937;">
                                <td style="padding: 10px;">{{ $entity['label'] }}</td>
                                <td style="padding: 10px; color: #9ca3af;">{{ $entity['suggested_class'] }}</td>
                                <td style="padding: 10px;">{{ round($entity['confidence'] * 100) }}%</td>

                                <td style="padding: 10px;">
                                    @if ($entity['status'] === 'accepted')
                                        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                                            <span style="color: #22c55e;">Accepted</span>
                                            <button style="font-size: 12px; color: #9ca3af; text-decoration: underline;">Change</button>
                                            <button style="font-size: 12px; color: #ef4444; text-decoration: underline;">Reject</button>
                                        </div>
                                    @else
                                        <div style="display: flex; gap: 8px; align-items: center; flex-wrap: wrap;">
                                            <span style="color: #f59e0b;">Needs review</span>
                                            <button style="font-size: 12px; color: #22c55e; text-decoration: underline;">Accept</button>
                                            <button style="font-size: 12px; color: #9ca3af; text-decoration: underline;">Change class</button>
                                            <button style="font-size: 12px; color: #ef4444; text-decoration: underline;">Reject</button>
                                        </div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr style="border-bottom: 1px solid #1f2937;">
                                <td colspan="4" style="padding: 10px; color: #9ca3af;">
                                    No extracted entities are available yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif

        @if ($activeStep === 'ttl')
            <div style="margin-bottom: 32px;">
                <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 12px;">
                    TTL preview
                </h3>

                <p style="color: #9ca3af; margin-bottom: 16px;">
                    The validated semantic annotations are transformed into RDF/Turtle statements ready
                    for review and GraphDB ingestion.
                </p>

                <pre style="background: #020617; color: #d1d5db; padding: 16px; border-radius: 10px; overflow-x: auto; font-size: 13px; max-height: 360px;">{{ $ttlPreview }}</pre>
            </div>
        @endif

        <div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 32px;">
            <div>
                <h4 style="font-weight: 600; margin-bottom: 8px;">Workflow</h4>
                <p style="color: #9ca3af;">
                    The workflow follows the transformation from document input
                    to semantic output: textual documentation is processed,
                    relevant information is extracted, ontology suggestions are
                    generated, and validated results are exported as RDF/Turtle
                    for GraphDB ingestion.
                </p>
            </div>

            <div>
                <h4 style="font-weight: 600; margin-bottom: 8px;">Core capabilities</h4>
                <p style="color: #9ca3af;">
                    The pipeline is designed to identify heritage entities,
                    places, actors, institutions, scientific analyses,
                    methods, devices, and materials, while proposing suitable
                    ontology classes and properties with confidence-based ranking.
                </p>
            </div>

            <div>
                <h4 style="font-weight: 600; margin-bottom: 8px;">Expected output</h4>
                <p style="color: #9ca3af;">
                    The expected output is a structured, ontology-aligned
                    representation of the source document, including traceable
                    semantic annotations and RDF/Turtle files ready to be
                    reviewed and integrated into the ARTEMIS Knowledge Base.
                </p>
            </div>
        </div>

    </div>
</x-filament::page>
