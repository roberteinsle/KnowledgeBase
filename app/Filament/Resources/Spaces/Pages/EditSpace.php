<?php

namespace App\Filament\Resources\Spaces\Pages;

use App\Filament\Resources\Spaces\SpaceResource;
use App\Modules\Spaces\Models\Space;
use App\Modules\Spaces\Services\SpaceService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditSpace extends EditRecord
{
    protected static string $resource = SpaceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->using(fn (Space $record) => app(SpaceService::class)->delete($record)),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $record = $this->getRecord();
        assert($record instanceof Space);
        $data['permissions'] = $record->permissions()->get()
            ->map(fn ($p) => ['subject_type' => $p->subject_type, 'subject_id' => $p->subject_id, 'role' => $p->role->value])
            ->all();

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        assert($record instanceof Space);
        $permissions = array_values($data['permissions'] ?? []);
        unset($data['permissions']);

        return app(SpaceService::class)->update($record, $data, $permissions);
    }
}
