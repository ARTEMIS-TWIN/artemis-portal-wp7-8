<x-filament-panels::page>
    <form wire:submit="importEntities" class="space-y-6">
        {{ $this->form }}

        <div class="flex items-center gap-3">
            <x-filament::button type="submit">
                Import from GraphDB
            </x-filament::button>

            <x-filament::button type="button" color="gray" wire:click="syncHeritageEntities">
                Sync from OpenSearch
            </x-filament::button>
        </div>
    </form>

    <div class="mt-8">
        {{ $this->table }}
    </div>
</x-filament-panels::page>
