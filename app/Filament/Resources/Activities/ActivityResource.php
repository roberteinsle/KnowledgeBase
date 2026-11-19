<?php

namespace App\Filament\Resources\Activities;

use App\Core\Activity\Activity;
use App\Core\Support\Dates;
use App\Filament\Concerns\AdminOnly;
use App\Filament\Resources\Activities\Pages\ListActivities;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ActivityResource extends Resource
{
    use AdminOnly;

    protected static ?string $model = Activity::class;

    protected static ?string $slug = 'protokoll';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = 90;

    public static function getModelLabel(): string
    {
        return __('Activity');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Activity log');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('System');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')->label(__('Time'))->sortable()
                    ->formatStateUsing(fn ($state) => Dates::display($state)),
                TextColumn::make('user.name')->label(__('User'))->placeholder('—')->searchable(),
                TextColumn::make('action')->label(__('Action'))->badge()->searchable(),
                TextColumn::make('subject_type')->label(__('Object'))
                    ->formatStateUsing(fn ($state, Activity $record) => $state.' #'.$record->subject_id),
                TextColumn::make('properties')->label(__('Details'))->limit(80)
                    ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_UNESCAPED_UNICODE) : (string) $state),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('subject_type')->label(__('Object'))
                    ->options(fn () => Activity::query()->whereNotNull('subject_type')->distinct()->pluck('subject_type', 'subject_type')),
            ]);
    }

    public static function getPages(): array
    {
        return ['index' => ListActivities::route('/')];
    }
}
