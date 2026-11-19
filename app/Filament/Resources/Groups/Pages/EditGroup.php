<?php

namespace App\Filament\Resources\Groups\Pages;

use App\Filament\Resources\Groups\GroupResource;
use App\Modules\Groups\Models\Group;
use App\Modules\Groups\Services\GroupService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditGroup extends EditRecord
{
    protected static string $resource = GroupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->hidden(fn (Group $record) => $record->isEveryone())
                ->using(fn (Group $record) => app(GroupService::class)->delete($record)),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();
        assert($record instanceof Group);
        $data['users'] = $record->users()->pluck('users.id')->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        assert($record instanceof Group);

        return app(GroupService::class)->update(
            $record,
            ['name' => $record->isEveryone() ? $record->name : $data['name'], 'description' => $data['description'] ?? null],
            $record->isEveryone() ? null : array_map('intval', $data['users'] ?? []),
        );
    }
}
