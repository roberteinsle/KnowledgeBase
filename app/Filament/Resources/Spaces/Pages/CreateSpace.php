<?php

namespace App\Filament\Resources\Spaces\Pages;

use App\Filament\Resources\Spaces\SpaceResource;
use App\Modules\Spaces\Services\SpaceService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSpace extends CreateRecord
{
    protected static string $resource = SpaceResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $permissions = array_values($data['permissions'] ?? []);
        unset($data['permissions']);

        return app(SpaceService::class)->create($data, auth()->user(), $permissions);
    }
}
