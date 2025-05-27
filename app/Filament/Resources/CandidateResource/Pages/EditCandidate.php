<?php

namespace App\Filament\Resources\CandidateResource\Pages;

use App\Filament\Resources\CandidateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCandidate extends EditRecord
{
    protected static string $resource = CandidateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $voter = \App\Models\Voter::find($data['voter_id']);
        $data['department_id'] = $voter?->course?->department_id;
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $voter = \App\Models\Voter::find($data['voter_id']);
        $data['department_id'] = $voter?->course?->department_id;
        return $data;
    }
}
