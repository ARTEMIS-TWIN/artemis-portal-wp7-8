<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class ArtemisIAOracle extends Page
{
    protected string $view = 'filament.pages.artemis-i-a-oracle';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-sparkles';
    protected static ?string $navigationLabel = 'ArtemisIA Oracle';
    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'ArtemisIA Oracle';
}