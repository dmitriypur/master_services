<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\Service;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
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

                Forms\Components\Section::make('Подписка')
                    ->schema([
                        Forms\Components\Select::make('subscription_status')
                            ->label('Статус подписки')
                            ->options([
                                'none' => 'Нет',
                                'trial' => 'Триал',
                                'active' => 'Активна',
                                'paused' => 'Остановлена',
                            ]),
                        Forms\Components\DateTimePicker::make('trial_ends_at')
                            ->label('Триал до')
                            ->seconds(false),
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
                        Forms\Components\Repeater::make('services_cascade')
                            ->label('Добавить услуги')
                            ->columnSpanFull()
                            ->schema([
                                Forms\Components\Grid::make(3)
                                    ->schema([
                                        // 1. Категория (Корневой уровень)
                                        Forms\Components\Select::make('category_id')
                                            ->label('Категория')
                                            ->options(fn () => Service::where('parent_id', -1)->pluck('name', 'id'))
                                            ->live()
                                            ->afterStateUpdated(function (Set $set) {
                                                $set('subcategory_id', null);
                                                $set('service_id', null);
                                            })
                                            ->required(),

                                        // 2. Подкатегория (Второй уровень)
                                        Forms\Components\Select::make('subcategory_id')
                                            ->label('Подкатегория')
                                            ->options(function (Get $get) {
                                                $categoryId = $get('category_id');
                                                if (! $categoryId) {
                                                    return [];
                                                }

                                                return Service::where('parent_id', $categoryId)->pluck('name', 'id');
                                            })
                                            ->live()
                                            ->afterStateUpdated(function (Set $set) {
                                                $set('service_id', null);
                                            })
                                            ->hidden(fn (Get $get) => empty(Service::where('parent_id', $get('category_id'))->count())),

                                        // 3. Услуга (Третий уровень - конечная)
                                        Forms\Components\Select::make('service_id')
                                            ->label('Услуга')
                                            ->options(function (Get $get) {
                                                $subcategoryId = $get('subcategory_id');
                                                // Если подкатегория выбрана - показываем её детей
                                                if ($subcategoryId) {
                                                    return Service::where('parent_id', $subcategoryId)->pluck('name', 'id');
                                                }

                                                // Если подкатегории нет, но выбрана категория (и у неё нет подкатегорий, а сразу услуги)
                                                // Такое возможно, если структура неглубокая.
                                                // Но в нашем сидере везде 3 уровня.
                                                // На всякий случай оставим логику:
                                                $categoryId = $get('category_id');
                                                if ($categoryId) {
                                                    // Если нет подкатегорий, показываем услуги категории
                                                    // Но мы выше скрываем subcategory_id только если count() == 0.
                                                    // Значит здесь просто возвращаем пустой массив, пока не выбрана подкатегория.
                                                    return [];
                                                }

                                                return [];
                                            })
                                            ->required()
                                            ->hidden(fn (Get $get) => ! $get('subcategory_id')),
                                    ]),
                            ])
                            // Важно: Repeater работает с JSON массивом. Нам нужно мапить это на отношение many-to-many.
                            // Filament позволяет работать с отношениями через Repeater, но тут сложнее из-за промежуточных селектов.
                            // Поэтому мы будем использовать loadStateFromRelationships и saveRelationships (кастомная логика в Page или Model).
                            // Или проще: использовать отношение services() и кастомный ->saveRelationshipsUsing()
                            ->relationship('services') // Это не сработает напрямую с каскадом.
                            // Оставим Repeater без relationship, и будем сохранять вручную.
                            ->dehydrated(false) // Не отправлять в модель User напрямую
                            ->afterStateHydrated(function (Forms\Components\Repeater $component, ?User $record) {
                                // Загрузка данных при открытии формы
                                if (! $record) {
                                    return;
                                }

                                $services = $record->services()->with('parent.parent')->get();
                                $data = [];

                                foreach ($services as $service) {
                                    // Пытаемся восстановить иерархию снизу вверх
                                    // service -> parent (subcategory) -> parent (category)

                                    $subcategory = $service->parent;
                                    $category = $subcategory?->parent;

                                    // Если структура нарушена или это корневая услуга (чего быть не должно по логике)
                                    if (! $subcategory || ! $category) {
                                        continue;
                                    }

                                    $data[] = [
                                        'category_id' => $category->id,
                                        'subcategory_id' => $subcategory->id,
                                        'service_id' => $service->id,
                                    ];
                                }

                                $component->state($data);
                            })
                            ->saveRelationshipsUsing(function (User $record, $state) {
                                // Сохранение данных
                                // $state - массив из репитера
                                // Нам нужны только service_id

                                $serviceIds = collect($state)
                                    ->pluck('service_id')
                                    ->filter()
                                    ->unique()
                                    ->toArray();

                                $record->services()->sync($serviceIds);
                            }),
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
                Tables\Columns\TextColumn::make('subscription_status')
                    ->label('Подписка')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('trial_ends_at')
                    ->label('Триал до')
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('masterSettings.address')
                    ->label('Адрес')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('schedule')
                    ->label('График')
                    ->state(fn (User $user) => ($user->masterSettings?->work_time_from ? ($user->masterSettings->work_time_from.'–'.$user->masterSettings->work_time_to) : null))
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
