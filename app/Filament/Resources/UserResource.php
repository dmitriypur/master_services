<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Пользователи';

    protected static ?string $modelLabel = 'Пользователь';

    protected static ?string $pluralModelLabel = 'Пользователи';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Основные')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Имя')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('phone')
                            ->label('Телефон')
                            ->maxLength(32),
                        Forms\Components\TextInput::make('password')
                            ->label('Пароль')
                            ->password()
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context) => $context === 'create'),
                        Forms\Components\Select::make('role')
                            ->label('Роль')
                            ->options([
                                'master' => 'Мастер',
                                'superadmin' => 'Суперадмин',
                            ])
                            ->required()
                            ->default('master')
                            ->live(),
                        Forms\Components\Select::make('city_id')
                            ->label('Город')
                            ->relationship('city', 'name')
                            ->searchable(),
                    ]),

                Forms\Components\Section::make('Настройки мастера')
                    ->relationship('masterSettings')
                    ->hidden(fn (Get $get) => $get('role') !== 'master')
                    ->schema([
                        Forms\Components\TextInput::make('address')
                            ->label('Адрес')
                            ->maxLength(255),
                        Forms\Components\CheckboxList::make('work_days')
                            ->label('Рабочие дни')
                            ->options([
                                'mon' => 'Пн',
                                'tue' => 'Вт',
                                'wed' => 'Ср',
                                'thu' => 'Чт',
                                'fri' => 'Пт',
                                'sat' => 'Сб',
                                'sun' => 'Вс',
                            ]),
                        Forms\Components\TimePicker::make('work_time_from')
                            ->label('Время с')
                            ->withoutSeconds(),
                        Forms\Components\TimePicker::make('work_time_to')
                            ->label('Время до')
                            ->withoutSeconds(),
                        Forms\Components\Select::make('slot_duration_min')
                            ->label('Длительность слота, мин')
                            ->options([
                                15 => '15',
                                30 => '30',
                                60 => '60',
                            ]),
                        Forms\Components\TextInput::make('lat')
                            ->label('Широта')
                            ->numeric()
                            ->step('0.0000001'),
                        Forms\Components\TextInput::make('lon')
                            ->label('Долгота')
                            ->numeric()
                            ->step('0.0000001'),
                    ]),

                Forms\Components\Section::make('Услуги мастера')
                    ->hidden(fn (Get $get) => $get('role') !== 'master')
                    ->schema([
                        Forms\Components\Select::make('services')
                            ->label('Услуги')
                            ->relationship('services', 'name')
                            ->multiple()
                            ->preload()
                            ->searchable()
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('role', ['master', 'superadmin']))
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Имя')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('role')
                    ->label('Роль')
                    ->badge(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Телефон')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('city.name')
                    ->label('Город')
                    ->sortable(),
                Tables\Columns\TextColumn::make('masterSettings.address')
                    ->label('Адрес')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('schedule')
                    ->label('График')
                    ->state(fn (User $user) => ($user->masterSettings?->work_time_from ? ($user->masterSettings->work_time_from . '–' . $user->masterSettings->work_time_to) : null))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Создано')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Обновлено')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->label('Роль')
                    ->options([
                        'master' => 'Мастер',
                        'superadmin' => 'Суперадмин',
                    ]),
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

    public static function canViewAny(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
