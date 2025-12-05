<?php

namespace App\Filament\Resources\ServiceResource\Pages;

use App\Filament\Resources\ServiceResource;
use Filament\Forms\Components\TextInput;
use SolutionForest\FilamentTree\Resources\Pages\TreePage as BasePage;

class ServiceTree extends BasePage
{
    protected static string $resource = ServiceResource::class;

    protected static int $maxDepth = 3;

    protected function getActions(): array
    {
        return [
            $this->getCreateAction(),
        ];
    }

    protected function getFormSchema(): array
    {
        return [
            TextInput::make('name')
                ->label('Название')
                ->required()
                ->maxLength(255),
        ];
    }

    protected function hasDeleteAction(): bool
    {
        return true;
    }

    protected function hasEditAction(): bool
    {
        return true;
    }

    protected function hasViewAction(): bool
    {
        return false;
    }
}
