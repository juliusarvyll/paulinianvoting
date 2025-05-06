<?php

namespace App\Filament\Resources\VoterResource\Pages;

use App\Filament\Resources\VoterResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVoter extends EditRecord
{
    protected static string $resource = VoterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
