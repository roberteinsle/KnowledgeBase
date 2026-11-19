<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Modules\Workspace\Services\UserService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $groups = array_map('intval', $data['groups'] ?? []);
        unset($data['groups']);

        return app(UserService::class)->create($data, $groups);
    }
}
