<?php

namespace App\Filament\Resources\PortfolioItems\Pages;

use App\Filament\Resources\PortfolioItems\PortfolioItemResource;
use Filament\Resources\Pages\ListRecords;

class ListPortfolioItems extends ListRecords
{
    protected static string $resource = PortfolioItemResource::class;
}
