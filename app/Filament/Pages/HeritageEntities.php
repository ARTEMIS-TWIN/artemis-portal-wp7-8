<?php

namespace App\Filament\Pages;

use App\Models\ImportedHeritageEntity;
use App\Services\GraphDbHeritageEntityImportService;
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

class HeritageEntities extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithTable;

    protected static string | BackedEnum | null $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationLabel = 'Heritage Entities';

    protected static ?int $navigationSort = 2;

    protected ?string $heading = 'Heritage Entities';

    protected string $view = 'filament.pages.heritage-entities';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'graph_uri' => '',
            'entity_uris' => '',
        ]);

        if (app()->runningUnitTests()) {
            return;
        }

        try {
            $this->syncProjection(notify: false);
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Heritage entities could not be synchronized')
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
                Section::make('GraphDB Import')
                    ->description('Import one or more heritage entities from an ARTEMIS GraphDB named graph. Leave entity URIs empty to import all tangible heritage entities discovered in the graph.')
                    ->schema([
                        TextInput::make('graph_uri')
                            ->label('Named graph URI')
                            ->required()
                            ->placeholder('https://artemis-twin.eu/digitaltwins/stonehenge')
                            ->url(),
                        Textarea::make('entity_uris')
                            ->label('Entity URIs')
                            ->rows(8)
                            ->placeholder("https://artemis-twin.eu/entity/Stonehenge\nhttps://artemis-twin.eu/entity/AnotherEntity"),
                    ]),
            ]);
    }

    public function importEntities(): void
    {
        $state = $this->form->getState();
        $graphUri = trim((string) ($state['graph_uri'] ?? ''));
        $entityUris = $this->parseLines((string) ($state['entity_uris'] ?? ''));

        if ($graphUri === '') {
            Notification::make()
                ->title('No graph URI provided')
                ->body('Provide a named graph URI before importing heritage entities.')
                ->danger()
                ->send();

            return;
        }

        $importer = app(GraphDbHeritageEntityImportService::class);
        $successes = 0;
        $failures = 0;
        $importedByOverrides = [];

        try {
            $results = $importer->importGraph($graphUri, $entityUris);

            if ($results === []) {
                Notification::make()
                    ->title('No heritage entities discovered')
                    ->body('No importable entities were found in that named graph. Check the graph URI, class filters, or provide explicit entity URIs.')
                    ->warning()
                    ->send();

                return;
            }

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
                ->title('Heritage entity import failed')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }

        if ($successes > 0) {
            $this->syncProjection($importedByOverrides, notify: false);
            $this->form->fill([
                'graph_uri' => $graphUri,
                'entity_uris' => '',
            ]);
            $this->resetTable();
        }

        if ($successes > 0 || $failures > 0) {
            Notification::make()
                ->title('Heritage import completed')
                ->body("Imported {$successes} heritage entit".($successes === 1 ? 'y' : 'ies').", failed {$failures}.")
                ->color($failures > 0 ? 'warning' : 'success')
                ->send();
        }
    }

    public function syncHeritageEntities(): void
    {
        try {
            $count = $this->syncProjection(notify: false);

            $this->resetTable();

            Notification::make()
                ->title('Heritage entities synchronized')
                ->body("The heritage index currently exposes {$count} linked entit".($count === 1 ? 'y' : 'ies').'.')
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
            ->query(ImportedHeritageEntity::query()->with('user'))
            ->defaultSort('imported_at', 'desc')
            ->headerActions([
                Action::make('sync')
                    ->label('Sync from OpenSearch')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->action(fn () => $this->syncHeritageEntities()),
            ])
            ->columns([
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'linked' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('label')
                    ->searchable()
                    ->wrap()
                    ->limit(80),
                TextColumn::make('entity_type')
                    ->label('Type')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('country_label')
                    ->label('Country')
                    ->toggleable(),
                TextColumn::make('place_label')
                    ->label('Place')
                    ->toggleable(),
                TextColumn::make('record_id')
                    ->label('Record ID')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('entity_uri')
                    ->label('Entity URI')
                    ->url(fn (ImportedHeritageEntity $record): ?string => $record->entity_uri)
                    ->openUrlInNewTab()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('graph_uri')
                    ->label('Source graph')
                    ->url(fn (ImportedHeritageEntity $record): ?string => $record->graph_uri)
                    ->openUrlInNewTab()
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('user.name')
                    ->label('Admin')
                    ->toggleable(),
                TextColumn::make('imported_at')
                    ->label('Imported')
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
                    ->modalHeading('Remove linked heritage entity')
                    ->modalDescription('This removes the selected heritage entity from the local portal index.')
                    ->action(function (ImportedHeritageEntity $record): void {
                        $this->removeLinks(collect([$record]));
                    }),
            ])
            ->toolbarActions([
                BulkAction::make('removeLinks')
                    ->label('Remove Link')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Remove selected heritage entities')
                    ->modalDescription('This removes the selected heritage entities from the local portal index.')
                    ->action(function (Collection $records): void {
                        $this->removeLinks($records);
                    }),
            ])
            ->recordUrl(null)
            ->emptyStateHeading('No heritage entities yet')
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
        $count = app(GraphDbHeritageEntityImportService::class)->syncProjection($importedByOverrides);

        if ($notify) {
            Notification::make()
                ->title('Heritage entities synchronized')
                ->body("The heritage index currently exposes {$count} linked entit".($count === 1 ? 'y' : 'ies').'.')
                ->success()
                ->send();
        }

        return $count;
    }

    protected function removeLinks(Collection $records): void
    {
        $importer = app(GraphDbHeritageEntityImportService::class);
        $removed = 0;

        foreach ($records as $record) {
            if (! $record instanceof ImportedHeritageEntity) {
                continue;
            }

            $importer->remove($record->record_id);
            $removed++;
        }

        $remaining = $this->syncProjection(notify: false);
        $this->resetTable();

        Notification::make()
            ->title('Heritage entities updated')
            ->body("Removed {$removed} heritage entit".($removed === 1 ? 'y' : 'ies').". {$remaining} linked entit".($remaining === 1 ? 'y remains' : 'ies remain')." in the heritage index.")
            ->success()
            ->send();
    }
}
