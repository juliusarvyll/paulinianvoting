<?php

namespace App\Filament\Pages;

use App\Models\Candidate;
use App\Models\Voter;
use App\Models\Vote;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class VoteSeeder extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cube-transparent';
    protected static string $view = 'filament.pages.vote-seeder';
    protected static ?string $navigationGroup = 'Utilities';

    public $candidate_ids = [];
    public $number_of_votes = 1;

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Select::make('candidate_ids')
                ->label('Candidates')
                ->options(Candidate::all()->mapWithKeys(fn($c) => [
                    $c->id => "{$c->voter->last_name}, {$c->voter->first_name} ({$c->position->name})"
                ]))
                ->multiple()
                ->searchable()
                ->required(),
            Forms\Components\TextInput::make('number_of_votes')
                ->label('Number of Votes to Add (per candidate)')
                ->numeric()
                ->minValue(1)
                ->default(1)
                ->required(),
        ];
    }

    public function submit()
    {
        $addedVotes = [];
        foreach ($this->candidate_ids as $candidateId) {
            $candidate = Candidate::find($candidateId);
            if (!$candidate) continue;

            $voters = Voter::inRandomOrder()
                ->whereDoesntHave('votes', function ($q) use ($candidate) {
                    $q->where('candidate_id', $candidate->id);
                })
                ->limit($this->number_of_votes)
                ->get();

            foreach ($voters as $voter) {
                Vote::create([
                    'voter_id' => $voter->id,
                    'candidate_id' => $candidate->id,
                    'position_id' => $candidate->position_id,
                ]);
                $voter->has_voted = true;
                $voter->save();
            }

            $addedVotes[] = "{$candidate->voter->last_name}, {$candidate->voter->first_name} ({$voters->count()} votes)";
        }

        Notification::make()
            ->title('Random votes added')
            ->body('Added votes to: ' . implode('; ', $addedVotes))
            ->success()
            ->send();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('page_VoteSeeder') ?? false;
    }
}
