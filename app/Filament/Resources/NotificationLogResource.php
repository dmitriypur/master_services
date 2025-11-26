<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NotificationLogResource\Pages;
use App\Filament\Resources\NotificationLogResource\RelationManagers;
use App\Models\NotificationLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class NotificationLogResource extends Resource
{
    protected static ?string $model = NotificationLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Логи уведомлений';

    protected static ?string $modelLabel = 'Лог уведомления';

    protected static ?string $pluralModelLabel = 'Логи уведомлений';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Лог уведомлений')
                    ->schema([
                        Forms\Components\Select::make('appointment_id')
                            ->label('Запись')
                            ->relationship('appointment', 'id')
                            ->searchable(),
                        Forms\Components\Select::make('channel')
                            ->label('Канал')
                            ->options([
                                'telegram' => 'Telegram',
                                'whatsapp' => 'WhatsApp',
                            ])
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->label('Статус')
                            ->options([
                                'sent' => 'Отправлено',
                                'failed' => 'Ошибка',
                                'prepared' => 'Подготовлено',
                            ])
                            ->required(),
                        Forms\Components\DateTimePicker::make('sent_at')
                            ->label('Отправлено в'),
                        Forms\Components\Textarea::make('error_message')
                            ->label('Ошибка')
                            ->columnSpanFull(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('appointment.id')
                    ->label('Запись')
                    ->sortable(),
                Tables\Columns\TextColumn::make('channel')
                    ->label('Канал')
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Статус')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('sent_at')
                    ->label('Отправлено')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('channel')
                    ->label('Канал')
                    ->options([
                        'telegram' => 'Telegram',
                        'whatsapp' => 'WhatsApp',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Статус')
                    ->options([
                        'sent' => 'Отправлено',
                        'failed' => 'Ошибка',
                        'prepared' => 'Подготовлено',
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
            'index' => Pages\ListNotificationLogs::route('/'),
            'create' => Pages\CreateNotificationLog::route('/create'),
            'edit' => Pages\EditNotificationLog::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }
}
