<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MasterScheduleExceptionResource\Pages;
use App\Models\MasterScheduleException;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MasterScheduleExceptionResource extends Resource
{
    protected static ?string $model = MasterScheduleException::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationLabel = 'Исключения расписания';

    protected static ?string $modelLabel = 'Исключение';

    protected static ?string $pluralModelLabel = 'Исключения';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Исключение')
                    ->schema([
                        Forms\Components\Select::make('master_id')
                            ->label('Мастер')
                            ->options(fn () => User::query()->where('role', 'master')->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->required(),
                        Forms\Components\DatePicker::make('date')
                            ->label('Дата')
                            ->required(),
                        Forms\Components\Select::make('type')
                            ->label('Тип')
                            ->options([
                                'day_off' => 'Выходной',
                                'break' => 'Перерыв',
                            ])
                            ->required(),
                        Forms\Components\TimePicker::make('start_time')
                            ->label('Время с')
                            ->withoutSeconds(),
                        Forms\Components\TimePicker::make('end_time')
                            ->label('Время до')
                            ->withoutSeconds(),
                        Forms\Components\Textarea::make('description')
                            ->label('Описание')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('master.name')
                    ->label('Мастер')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('date')
                    ->label('Дата')
                    ->date(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Тип')
                    ->badge(),
                Tables\Columns\TextColumn::make('start_time')
                    ->label('С')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('end_time')
                    ->label('До')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Редактировать'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Удалить выбранные'),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMasterScheduleExceptions::route('/'),
            'create' => Pages\CreateMasterScheduleException::route('/create'),
            'edit' => Pages\EditMasterScheduleException::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }
}

