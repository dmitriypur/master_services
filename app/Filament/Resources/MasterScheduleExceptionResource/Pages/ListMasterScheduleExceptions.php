<?php

namespace App\Filament\Resources\MasterScheduleExceptionResource\Pages;

use App\Filament\Resources\MasterScheduleExceptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMasterScheduleExceptions extends ListRecords
{
    protected static string $resource = MasterScheduleExceptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Создать'),
        ];
    }
}
