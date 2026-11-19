<?php

namespace App\Filament\Resources\Groups\Pages;

use App\Filament\Resources\Groups\GroupResource;
use App\Modules\Groups\Services\GroupService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateGroup extends CreateRecord
{
    protected static string $resource = GroupResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $users = array_map('intval', $data['users'] ?? []);

        return app(GroupService::class)->create(
            ['name' => $data['name'], 'description' => $data['description'] ?? null],
            $users,
        );
    }
}
