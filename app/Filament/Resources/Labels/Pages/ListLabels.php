<?php

namespace App\Filament\Resources\Labels\Pages;

use App\Filament\Resources\Labels\LabelResource;
use Filament\Resources\Pages\ListRecords;

class ListLabels extends ListRecords
{
    protected static string $resource = LabelResource::class;
}
