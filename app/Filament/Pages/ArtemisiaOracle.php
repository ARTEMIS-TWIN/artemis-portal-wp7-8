<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
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

    public function updatedUploadedDocument(): void
    {
        if ($this->uploadedDocument) {
            $this->uploadedFileName = $this->uploadedDocument->getClientOriginalName();
            $this->activeStep = 'upload';
        }
    }

    public function runExtraction(): void
    {
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

        return [
            'data' => json_decode(file_get_contents($jsonPath), true),
            'ttlPreview' => file_exists($ttlPath)
                ? mb_substr(file_get_contents($ttlPath), 0, 2500)
                : 'TTL file not found at: ' . $ttlPath,
        ];
    }
}