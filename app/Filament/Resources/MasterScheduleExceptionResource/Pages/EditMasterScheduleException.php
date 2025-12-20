<?php

namespace App\Filament\Resources\MasterScheduleExceptionResource\Pages;

use App\Filament\Resources\MasterScheduleExceptionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMasterScheduleException extends EditRecord
{
    protected static string $resource = MasterScheduleExceptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
