<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Filament\Resources\ClientResource\RelationManagers;
use App\Models\Client;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;
use Filament\Forms\Get;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Клиенты';

    protected static ?string $modelLabel = 'Клиент';

    protected static ?string $pluralModelLabel = 'Клиенты';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Клиент')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Мастер')
                            ->options(fn () => User::query()->where('role', 'master')->pluck('name', 'id')->all())
                            ->searchable()
                            ->preload()
                            ->getOptionLabelUsing(fn ($value) => optional(User::query()->find($value))->name)
                            ->required(),
                        Forms\Components\TextInput::make('name')
                            ->label('Имя')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('Телефон')
                            ->tel()
                            ->maxLength(50),
                        Forms\Components\TextInput::make('whatsapp_phone')
                            ->label('WhatsApp телефон')
                            ->tel()
                            ->maxLength(50),
                        Forms\Components\TextInput::make('telegram_id')
                            ->label('Telegram ID')
                            ->numeric(),
                        Forms\Components\CheckboxList::make('preferred_channels')
                            ->label('Предпочтительные каналы')
                            ->options([
                                'telegram' => 'Telegram',
                                'whatsapp' => 'WhatsApp',
                            ])
                            ->columnSpanFull(),
                    ])
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
                Tables\Columns\TextColumn::make('name')
                    ->label('Имя')
                    ->searchable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('Телефон')
                    ->searchable(),
                Tables\Columns\TagsColumn::make('preferred_channels')
                    ->label('Каналы')
                    ->separator(','),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Мастер')
                    ->options(fn () => User::query()->where('role', 'master')->pluck('name', 'id')->all()),
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
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }
}
