<?php

namespace App\Filament\Widgets;

use App\Models\Election;
use App\Models\Position;
use Filament\Widgets\Widget;

class CandidatesByPositionWidget extends Widget
{
    protected static string $view = 'filament.widgets.candidates-by-position-widget';

    protected static ?string $heading = 'Candidates by Position (Active Election)';

    protected int | string | array $columnSpan = 'full';

    protected static bool $isCard = false;

    public function getColumnSpan(): int|string|array
    {
        return 'full';
    }

    protected function getViewData(): array
    {
        $electionId = Election::query()->where('is_active', true)->value('id');
        $electionName = Election::query()->where('is_active', true)->value('name');

        $positions = Position::query()
            ->when($electionId, fn ($q) => $q->where('election_id', $electionId))
            ->with([
                'candidates' => function ($q) use ($electionId) {
                    $q->with([
                        'voter:id,last_name,first_name,middle_name',
                        'department:id,department_name',
                    ])
                        ->withCount(['votes as votes_count' => function ($vq) use ($electionId) {
                            if ($electionId) {
                                $vq->where('election_id', $electionId);
                            }
                        }])
                        ->orderByDesc('votes_count');
                },
            ])
            ->orderBy('level')
            ->orderBy('name')
            ->get(['id', 'name', 'level']);

        return [
            'electionId' => $electionId,
            'electionName' => $electionName,
            'positions' => $positions,
        ];
    }
}
