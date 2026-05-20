<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Livewire\WithFileUploads;

class ArtemisiaOracle extends Page
{
    use WithFileUploads;

    protected string $view = 'filament.pages.artemis-i-a-oracle';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationLabel = 'Artemisia Oracle';
    protected static ?int $navigationSort = 1;
    protected static ?string $title = 'Artemisia Oracle';

    public string $activeStep = 'upload';
    public $uploadedDocument = null;
    public ?string $uploadedFileName = null;
    public array $extractedEntities = [];

    public function updatedUploadedDocument(): void
    {
        if ($this->uploadedDocument) {
            $this->uploadedFileName = $this->uploadedDocument->getClientOriginalName();
            $this->activeStep = 'upload';
        }
    }

    public function runExtraction(): void
    {
        if ($this->uploadedDocument !== null) {
            $this->validate([
                'uploadedDocument' => ['file', 'mimes:pdf,txt,doc,docx', 'max:20480'],
            ]);
        }

        $demo = $this->getDemoCase();
        $demoEntities = $demo['data']['entities'] ?? [];
        $this->extractedEntities = is_array($demoEntities) ? $demoEntities : [];

        if ($this->extractedEntities === [] && $this->uploadedFileName !== null) {
            $this->extractedEntities = [[
                'label' => $this->uploadedFileName,
                'suggested_class' => 'crm:E22_Human-Made_Object',
                'confidence' => 0.6,
                'status' => 'needs_review',
            ]];
        }

        if ($this->extractedEntities === []) {
            Notification::make()
                ->title('No extracted entities available')
                ->body('Upload a document and/or provide demo extraction JSON before running extraction.')
                ->warning()
                ->send();
        }

        $this->activeStep = 'alignment';
    }

    public function previewTtl(): void
    {
        $this->activeStep = 'ttl';
    }

    public function getDemoCase(): array
    {
        $jsonPath = storage_path('app/artemisia-demo/leo_x_demo.json');
        $ttlPath = storage_path('app/artemisia-demo/leo_x_demo.ttl');

        $jsonContent = file_exists($jsonPath)
            ? file_get_contents($jsonPath)
            : null;

        $jsonData = is_string($jsonContent)
            ? json_decode($jsonContent, true)
            : null;

        $data = [
            'entities' => [],
        ];

        if (is_array($jsonData)) {
            $data = array_replace_recursive($data, $jsonData);
        } else {
            $data['notice'] = 'Demo JSON file is not available.';
            $data['path'] = $jsonPath;
        }

        return [
            'data' => $data,
            'ttlPreview' => file_exists($ttlPath)
                ? mb_substr(file_get_contents($ttlPath), 0, 2500)
                : 'TTL file not found at: ' . $ttlPath,
        ];
    }
}
