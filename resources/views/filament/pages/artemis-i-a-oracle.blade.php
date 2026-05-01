<x-filament::page>
    <div style="max-width: 900px; line-height: 1.6;">

        <p style="color: #9ca3af; margin-bottom: 8px;">
            LLM-assisted Semantic Extraction and Ontology Tagging Pipeline
        </p>

        <p style="margin-bottom: 28px;">
            <strong>ArtemisIA Oracle</strong> is an AI-assisted pipeline designed to transform
            unstructured cultural heritage documentation into structured, ontology-aligned knowledge.
        </p>

        <div style="border: 1px solid #374151; border-radius: 12px; padding: 24px; margin-bottom: 32px;">
    <h3 style="font-size: 18px; font-weight: 600; margin-bottom: 8px;">Demo workspace</h3>

    <p style="color: #9ca3af; margin-bottom: 20px;">
        Simulate the main stages of the planned LLM-assisted extraction pipeline.
    </p>

    <div style="display: flex; gap: 12px; flex-wrap: wrap;">
        <x-filament::button>
            Upload document
        </x-filament::button>

        <x-filament::button color="gray">
            Run extraction
        </x-filament::button>

        <x-filament::button color="gray">
            Preview TTL
        </x-filament::button>

        <x-filament::button color="gray">
            Download .ttl
        </x-filament::button>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 32px;">
    <div>
        <h4 style="font-weight: 600; margin-bottom: 8px;">Workflow</h4>
        <p style="color: #9ca3af;">
            The workflow follows the transformation from document input to semantic output:
            textual documentation is processed, relevant information is extracted, ontology
            suggestions are generated, and validated results are exported as RDF/Turtle for
            GraphDB ingestion.
        </p>
    </div>

    <div>
        <h4 style="font-weight: 600; margin-bottom: 8px;">Core capabilities</h4>
        <p style="color: #9ca3af;">
            The pipeline is designed to identify heritage entities, places, actors,
            institutions, scientific analyses, methods, devices, and materials, while proposing
            suitable ontology classes and properties with confidence-based ranking.
        </p>
    </div>

    <div>
        <h4 style="font-weight: 600; margin-bottom: 8px;">Expected output</h4>
        <p style="color: #9ca3af;">
            The expected output is a structured, ontology-aligned representation of the source
            document, including traceable semantic annotations and RDF/Turtle files ready to be
            reviewed and integrated into the ARTEMIS Knowledge Base.
        </p>
    </div>
</div>

    </div>
</x-filament::page>