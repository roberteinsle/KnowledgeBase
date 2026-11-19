<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Modules\Workspace\Services\UserService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->using(fn (User $record) => app(UserService::class)->delete($record)),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();
        assert($record instanceof User);
        $data['groups'] = $record->groups()->pluck('groups.id')->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        assert($record instanceof User);
        $groups = array_map('intval', $data['groups'] ?? []);
        unset($data['groups']);

        return app(UserService::class)->update($record, $data, $groups);
    }
}
