<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AppointmentResource\Pages;
use App\Models\Appointment;
use App\Models\Client;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AppointmentResource extends Resource
{
    protected static ?string $model = Appointment::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Записи';

    protected static ?string $modelLabel = 'Запись';

    protected static ?string $pluralModelLabel = 'Записи';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Запись')
                    ->schema([
                        Forms\Components\Select::make('master_id')
                            ->label('Мастер')
                            ->options(fn () => User::query()->where('role', 'master')->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->preload()
                            ->getOptionLabelUsing(fn ($value) => optional(User::query()->find($value))->name)
                            ->required()
                            ->live(),
                        Forms\Components\Select::make('client_id')
                            ->label('Клиент')
                            ->options(fn (Get $get) => Client::query()
                                ->when($get('master_id'), fn ($q) => $q->where('user_id', $get('master_id')))
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->getOptionLabelUsing(fn ($value) => optional(Client::query()->find($value))->name)
                            ->required(),
                        Forms\Components\Select::make('service_id')
                            ->label('Услуга')
                            ->relationship('service', 'name')
                            ->searchable()
                            ->required(),
                        Forms\Components\DateTimePicker::make('starts_at')
                            ->label('Дата и время')
                            ->required()
                            ->minutesStep(5),
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'scheduled' => 'Запланирована',
                                'completed' => 'Завершена',
                                'canceled' => 'Отменена',
                            ])
                            ->required(),
                        Forms\Components\Select::make('source')
                            ->label('Источник')
                            ->options([
                                'manual' => 'Вручную',
                                'web' => 'Сайт',
                                'telegram' => 'Telegram',
                            ]),
                        Forms\Components\Textarea::make('private_notes')
                            ->label('Заметки мастера')
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
                Tables\Columns\TextColumn::make('client.name')
                    ->label('Клиент')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('service.name')
                    ->label('Услуга')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('starts_at')
                    ->label('Дата/время')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('source')
                    ->label('Источник')
                    ->sortable(),
                Tables\Columns\TextColumn::make('private_notes')
                    ->label('Заметки')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('master_id')
                    ->label('Мастер')
                    ->options(fn () => User::query()->where('role', 'master')->pluck('name', 'id')->all()),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'scheduled' => 'Запланирована',
                        'completed' => 'Завершена',
                        'canceled' => 'Отменена',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppointments::route('/'),
            'create' => Pages\CreateAppointment::route('/create'),
            'edit' => Pages\EditAppointment::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }
}
