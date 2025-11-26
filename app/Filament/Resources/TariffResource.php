<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TariffResource\Pages;
use App\Models\Tariff;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TariffResource extends Resource
{
    protected static ?string $model = Tariff::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    protected static ?string $navigationLabel = 'Тарифы';

    protected static ?string $modelLabel = 'Тариф';

    protected static ?string $pluralModelLabel = 'Тарифы';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Название')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('code')
                    ->label('Код')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\TextInput::make('price_month')
                    ->label('Цена/мес')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('max_clients')
                    ->label('Макс. клиентов')
                    ->numeric()
                    ->nullable(),
                Forms\Components\TextInput::make('included_sms')
                    ->label('Включено SMS')
                    ->numeric()
                    ->default(0),
                Forms\Components\Toggle::make('auto_sms_enabled')
                    ->label('Авто-SMS')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Название')
                    ->searchable(),
                Tables\Columns\TextColumn::make('code')
                    ->label('Код')
                    ->searchable(),
                Tables\Columns\TextColumn::make('price_month')
                    ->label('Цена/мес')
                    ->money('RUB', locale: 'ru')
                    ->sortable(),
                Tables\Columns\TextColumn::make('max_clients')
                    ->label('Макс. клиентов')
                    ->sortable(),
                Tables\Columns\IconColumn::make('auto_sms_enabled')
                    ->label('Авто-SMS')
                    ->boolean(),
                Tables\Columns\TextColumn::make('included_sms')
                    ->label('Включено SMS')
                    ->sortable(),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTariffs::route('/'),
            'create' => Pages\CreateTariff::route('/create'),
            'edit' => Pages\EditTariff::route('/{record}/edit'),
        ];
    }
}