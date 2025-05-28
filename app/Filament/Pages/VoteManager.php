<?php

namespace App\Filament\Pages;

use App\Models\Candidate;
use App\Models\Voter;
use App\Models\Vote;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class VoteManager extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';
    protected static string $view = 'filament.pages.vote-manager';
    protected static ?string $navigationGroup = 'Utilities';
    protected static ?string $navigationLabel = 'Vote Manager';
    protected static ?int $navigationSort = 2;

    public $action = 'add';
    public $candidate_ids = [];
    public $number_of_votes = 1;
    public $source_candidate_id = null;
    public $target_candidate_id = null;
    public $vote_source = 'random';

    protected function getFormSchema(): array
    {
        $candidates = Candidate::with(['voter', 'position'])->get();

        return [
            Forms\Components\Select::make('action')
                ->label('Action')
                ->options([
                    'add' => 'Add Votes',
                    'remove' => 'Remove Votes',
                    'reassign' => 'Reassign Votes',
                ])
                ->default('add')
                ->reactive()
                ->required(),

            // Vote source selector for add action
            Forms\Components\Select::make('vote_source')
                ->label('Vote Source')
                ->options([
                    'random' => 'Random Non-voters',
                    'other_positions' => 'Voters from Other Positions',
                ])
                ->default('random')
                ->visible(fn (callable $get) => $get('action') === 'add')
                ->reactive()
                ->required(fn (callable $get) => $get('action') === 'add'),

            // Source candidate for reassigning votes
            Forms\Components\Select::make('source_candidate_id')
                ->label('Source Candidate (Move votes FROM)')
                ->options($candidates->mapWithKeys(fn($c) => [
                    $c->id => "{$c->voter->last_name}, {$c->voter->first_name} ({$c->position->name}) - Current Votes: {$c->votes()->count()}"
                ]))
                ->searchable()
                ->visible(fn (callable $get) => $get('action') === 'reassign')
                ->required(fn (callable $get) => $get('action') === 'reassign'),

            // Target candidate for reassigning votes
            Forms\Components\Select::make('target_candidate_id')
                ->label('Target Candidate (Move votes TO)')
                ->options(function (callable $get) use ($candidates) {
                    if ($get('source_candidate_id')) {
                        $sourceCandidate = $candidates->firstWhere('id', $get('source_candidate_id'));
                        return $candidates
                            ->where('position_id', $sourceCandidate->position_id)
                            ->where('id', '!=', $get('source_candidate_id'))
                            ->mapWithKeys(fn($c) => [
                                $c->id => "{$c->voter->last_name}, {$c->voter->first_name} - Current Votes: {$c->votes()->count()}"
                            ]);
                    }
                    return [];
                })
                ->searchable()
                ->visible(fn (callable $get) => $get('action') === 'reassign')
                ->required(fn (callable $get) => $get('action') === 'reassign'),

            // Multiple candidates for add/remove
            Forms\Components\Select::make('candidate_ids')
                ->label('Candidates')
                ->options($candidates->mapWithKeys(fn($c) => [
                    $c->id => "{$c->voter->last_name}, {$c->voter->first_name} ({$c->position->name}) - Current Votes: {$c->votes()->count()}"
                ]))
                ->multiple()
                ->searchable()
                ->visible(fn (callable $get) => $get('action') !== 'reassign')
                ->required(fn (callable $get) => $get('action') !== 'reassign'),

            Forms\Components\TextInput::make('number_of_votes')
                ->label('Number of Votes')
                ->helperText(function ($get) {
                    if ($get('action') === 'add') {
                        return $get('vote_source') === 'random'
                            ? 'Number of random votes to add per candidate'
                            : 'Number of votes to add from other position voters';
                    } elseif ($get('action') === 'remove') {
                        return 'Number of votes to remove per candidate';
                    } else {
                        return 'Number of votes to reassign from source to target candidate';
                    }
                })
                ->numeric()
                ->minValue(1)
                ->default(1)
                ->required(),
        ];
    }

    public function submit()
    {
        if ($this->action === 'add') {
            $this->addVotes();
        } elseif ($this->action === 'remove') {
            $this->removeVotes();
        } else {
            $this->reassignVotes();
        }
    }

    protected function addVotes()
    {
        $addedVotes = [];
        foreach ($this->candidate_ids as $candidateId) {
            $candidate = Candidate::find($candidateId);
            if (!$candidate) continue;

            if ($this->vote_source === 'random') {
                $voters = Voter::inRandomOrder()
                    ->whereDoesntHave('votes', function ($q) use ($candidate) {
                        $q->where('candidate_id', $candidate->id);
                    })
                    ->limit($this->number_of_votes)
                    ->get();
            } else {
                // Get voters who have voted in other positions but not this one
                $voters = Voter::whereHas('votes')
                    ->whereDoesntHave('votes', function ($q) use ($candidate) {
                        $q->where('position_id', $candidate->position_id);
                    })
                    ->inRandomOrder()
                    ->limit($this->number_of_votes)
                    ->get();
            }

            $votesAdded = 0;
            foreach ($voters as $voter) {
                Vote::create([
                    'voter_id' => $voter->id,
                    'candidate_id' => $candidate->id,
                    'position_id' => $candidate->position_id,
                ]);
                $voter->has_voted = true;
                $voter->save();
                $votesAdded++;
            }

            $addedVotes[] = "{$candidate->voter->last_name}, {$candidate->voter->first_name} ({$votesAdded} votes)";
        }

        $sourceType = $this->vote_source === 'random' ? 'random non-voters' : 'voters from other positions';
        Notification::make()
            ->title('Votes added')
            ->body("Added votes from {$sourceType} to: " . implode('; ', $addedVotes))
            ->success()
            ->send();
    }

    protected function removeVotes()
    {
        $removedVotes = [];
        foreach ($this->candidate_ids as $candidateId) {
            $candidate = Candidate::find($candidateId);
            if (!$candidate) continue;

            $votesToRemove = $candidate->votes()
                ->latest()
                ->limit($this->number_of_votes)
                ->get();

            foreach ($votesToRemove as $vote) {
                $voter = $vote->voter;
                $vote->delete();

                // Check if the voter has any remaining votes
                if ($voter->votes()->count() === 0) {
                    $voter->has_voted = false;
                    $voter->save();
                }
            }

            $removedVotes[] = "{$candidate->voter->last_name}, {$candidate->voter->first_name} ({$votesToRemove->count()} votes)";
        }

        Notification::make()
            ->title('Votes removed')
            ->body('Removed votes from: ' . implode('; ', $removedVotes))
            ->success()
            ->send();
    }

    protected function reassignVotes()
    {
        $sourceCandidate = Candidate::find($this->source_candidate_id);
        $targetCandidate = Candidate::find($this->target_candidate_id);

        if (!$sourceCandidate || !$targetCandidate) {
            Notification::make()
                ->title('Error')
                ->body('Source or target candidate not found.')
                ->danger()
                ->send();
            return;
        }

        if ($sourceCandidate->position_id !== $targetCandidate->position_id) {
            Notification::make()
                ->title('Error')
                ->body('Cannot reassign votes between different positions.')
                ->danger()
                ->send();
            return;
        }

        $votesToReassign = $sourceCandidate->votes()
            ->latest()
            ->limit($this->number_of_votes)
            ->get();

        $reassignedCount = 0;
        foreach ($votesToReassign as $vote) {
            // Check if voter hasn't already voted for target candidate
            if (!Vote::where('voter_id', $vote->voter_id)
                    ->where('candidate_id', $targetCandidate->id)
                    ->exists()) {
                $vote->update(['candidate_id' => $targetCandidate->id]);
                $reassignedCount++;
            }
        }

        if ($reassignedCount > 0) {
            Notification::make()
                ->title('Votes reassigned')
                ->body("Reassigned {$reassignedCount} votes from {$sourceCandidate->voter->name} to {$targetCandidate->voter->name}")
                ->success()
                ->send();
        } else {
            Notification::make()
                ->title('No votes reassigned')
                ->body('No eligible votes found to reassign.')
                ->warning()
                ->send();
        }
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('page_VoteManager') ?? false;
    }
}
