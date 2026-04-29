<?php

namespace App\Filament\Pages;

use App\Models\ImportedService;
use App\Services\ServicesTtlImportService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Throwable;

class LinkServices extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationLabel = 'Link Services';

    protected static ?int $navigationSort = 3;

    protected ?string $heading = 'Link Services';

    protected string $view = 'filament.pages.link-services';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'source_path' => '',
            'service_uris' => '',
        ]);

        if (app()->runningUnitTests()) {
            return;
        }

        try {
            $this->syncProjection(notify: false);
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Linked services could not be synchronized')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Section::make('Services TTL Import')
                    ->description('Import service records from either a GraphDB named graph URI or TTL data files (.ttl/.zip/folder). Leave service URIs empty to import all discovered service records.')
                    ->schema([
                        TextInput::make('source_path')
                            ->label('Source (graph URI or path)')
                            ->placeholder('https://artemis-twin.eu/digitaltwins/stonehenge or services_ttl.zip'),
                        Textarea::make('service_uris')
                            ->label('Service URIs')
                            ->rows(8)
                            ->placeholder("https://example.org/service/1\nhttps://example.org/service/2"),
                    ]),
            ]);
    }

    public function linkServices(): void
    {
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        $state = $this->form->getState();
        $sourcePath = trim((string) ($state['source_path'] ?? ''));
        $serviceUris = $this->parseLines((string) ($state['service_uris'] ?? ''));

        $importer = app(ServicesTtlImportService::class);
        $importedByOverrides = [];
        $successes = 0;
        $failures = 0;

        try {
            $results = $importer->import($sourcePath, $serviceUris);

            foreach ($results as $result) {
                $recordId = $result['id'] ?? null;

                if (filled($recordId)) {
                    $importedByOverrides[$recordId] = auth()->id();
                }

                $successes++;
            }
        } catch (Throwable $exception) {
            $failures++;

            Notification::make()
                ->title('Service import failed')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }

        if ($successes > 0) {
            $this->syncProjection($importedByOverrides, notify: false);
            $this->resetTable();
        }

        $this->form->fill([
            'source_path' => $sourcePath,
            'service_uris' => '',
        ]);

        if ($successes > 0 || $failures > 0) {
            Notification::make()
                ->title('Service linking completed')
                ->body("Imported {$successes} service".($successes === 1 ? '' : 's').", failed {$failures}.")
                ->color($failures > 0 ? 'warning' : 'success')
                ->send();
        }
    }

    public function syncLinkedServices(): void
    {
        try {
            $count = $this->syncProjection(notify: false);

            $this->resetTable();

            Notification::make()
                ->title('Linked services synchronized')
                ->body("The services index currently exposes {$count} linked service".($count === 1 ? '' : 's').'.')
                ->success()
                ->send();
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Synchronization failed')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(ImportedService::query()->with('user'))
            ->defaultSort('imported_at', 'desc')
            ->headerActions([
                Action::make('sync')
                    ->label('Sync from OpenSearch')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->action(fn () => $this->syncLinkedServices()),
            ])
            ->columns([
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'linked' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('title')
                    ->searchable()
                    ->wrap()
                    ->limit(80),
                TextColumn::make('service_type')
                    ->label('Type')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('provider_name')
                    ->label('Provider')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('record_id')
                    ->label('Record ID')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('service_uri')
                    ->label('Service URI')
                    ->url(fn (ImportedService $record): ?string => $record->service_uri)
                    ->openUrlInNewTab()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('url')
                    ->label('External URL')
                    ->url(fn (ImportedService $record): ?string => $record->url)
                    ->openUrlInNewTab()
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('source_path')
                    ->label('Source path')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user.name')
                    ->label('Admin')
                    ->toggleable(),
                TextColumn::make('imported_at')
                    ->label('Linked')
                    ->since()
                    ->sortable(),
                TextColumn::make('error_message')
                    ->label('Error')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Action::make('removeLink')
                    ->label('Remove Link')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Remove linked service')
                    ->modalDescription('This removes the selected service from the local portal index.')
                    ->action(function (ImportedService $record): void {
                        $this->removeLinks(collect([$record]));
                    }),
            ])
            ->toolbarActions([
                BulkAction::make('removeLinks')
                    ->label('Remove Link')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Remove selected linked services')
                    ->modalDescription('This removes the selected services from the local portal index.')
                    ->action(function (Collection $records): void {
                        $this->removeLinks($records);
                    }),
            ])
            ->recordUrl(null)
            ->emptyStateHeading('No linked services yet')
            ->paginated([10, 25, 50]);
    }

    /**
     * @return list<string>
     */
    protected function parseLines(string $value): array
    {
        return array_values(array_unique(array_filter(array_map(
            static fn (string $item): string => trim($item),
            preg_split('/[\r\n,;]+/', $value) ?: [],
        ))));
    }

    /**
     * @param  array<string, int|null>  $importedByOverrides
     */
    protected function syncProjection(array $importedByOverrides = [], bool $notify = true): int
    {
        $count = app(ServicesTtlImportService::class)->syncProjection($importedByOverrides);

        if ($notify) {
            Notification::make()
                ->title('Linked services synchronized')
                ->body("The services index currently exposes {$count} linked service".($count === 1 ? '' : 's').'.')
                ->success()
                ->send();
        }

        return $count;
    }

    protected function removeLinks(Collection $records): void
    {
        $importer = app(ServicesTtlImportService::class);
        $removed = 0;

        foreach ($records as $record) {
            if (! $record instanceof ImportedService) {
                continue;
            }

            $importer->remove($record->record_id);
            $removed++;
        }

        $remaining = $this->syncProjection(notify: false);
        $this->resetTable();

        Notification::make()
            ->title('Linked services updated')
            ->body("Removed {$removed} service".($removed === 1 ? '' : 's').". {$remaining} linked service".($remaining === 1 ? ' remains' : 's remain')." in the portal.")
            ->success()
            ->send();
    }
}
