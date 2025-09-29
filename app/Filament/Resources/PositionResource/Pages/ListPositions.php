<?php

namespace App\Filament\Resources\PositionResource\Pages;

use App\Filament\Resources\PositionResource;
use App\Models\Election;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;

class ListPositions extends ListRecords
{
    protected static string $resource = PositionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        $tabs = [
            'All' => Tab::make(),
        ];

        foreach (Election::orderByDesc('start_at')->get() as $election) {
            $tabs[$election->name] = Tab::make()
                ->modifyQueryUsing(fn ($query) => $query->where('election_id', $election->id));
        }

        return $tabs;
    }
}
