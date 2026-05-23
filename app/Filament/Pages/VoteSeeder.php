<?php

namespace App\Filament\Pages;

use App\Models\Candidate;
use App\Models\Election;
use App\Models\Position;
use App\Models\Voter;
use App\Models\VoterElectionParticipation;
use App\Models\Vote;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;

class VoteSeeder extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cube-transparent';
    protected static string $view = 'filament.pages.vote-seeder';
    protected static ?string $navigationGroup = 'Utilities';

    public $election_id = null;
    public $seed_mode = 'selected_winners';
    public $balance_department_turnout = true;
    public $allow_turnout_overflow = false;
    public $turnout_mode = 'percent';
    public $turnout_value = 83;
    public $minimum_votes = 0;
    public $position_configs = [];
    public $boost_position_configs = [];

    public function mount(): void
    {
        $this->election_id = Election::active()->value('id') ?? Election::query()->value('id');
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Select::make('election_id')
                ->label('Election')
                ->options($this->getElectionOptions())
                ->default($this->election_id)
                ->reactive()
                ->required()
                ->afterStateUpdated(function (callable $set) {
                    $set('position_configs', []);
                    $set('boost_position_configs', []);
                }),
            Forms\Components\Select::make('seed_mode')
                ->label('Seed Mode')
                ->options([
                    'selected_winners' => 'Choose winners per position',
                    'boost_existing' => 'Boost current standings',
                    'random_high_votes' => 'Random high votes',
                ])
                ->default('selected_winners')
                ->reactive()
                ->required(),
            Forms\Components\Toggle::make('balance_department_turnout')
                ->label('Balance Department Turnout')
                ->default(true)
                ->visible(fn (callable $get) => $get('seed_mode') !== 'random_high_votes')
                ->helperText('Try to distribute seeded turnout across departments proportionally to their voter population.'),
            Forms\Components\Toggle::make('allow_turnout_overflow')
                ->label('Allow Turnout Overflow')
                ->default(false)
                ->visible(fn (callable $get) => !in_array($get('seed_mode'), ['boost_existing', 'random_high_votes'], true))
                ->helperText('If off, the seeder will not exceed the turnout target just to satisfy minimum votes.'),
            Forms\Components\Select::make('turnout_mode')
                ->label('Turnout Type')
                ->options([
                    'percent' => 'Percentage of total voters',
                    'count' => 'Total voter count',
                ])
                ->default('percent')
                ->required()
                ->reactive(),
            Forms\Components\TextInput::make('turnout_value')
                ->label('Target Turnout')
                ->numeric()
                ->minValue(1)
                ->default(83)
                ->required()
                ->helperText('This is the total turnout to reach for the selected election, not an increment.'),
            Forms\Components\TextInput::make('minimum_votes')
                ->label('Minimum Votes Per Candidate')
                ->numeric()
                ->minValue(0)
                ->default(0)
                ->required()
                ->visible(fn (callable $get) => $get('seed_mode') !== 'random_high_votes')
                ->helperText('Before normal seeding continues, each eligible candidate will be raised to at least this many real votes when enough valid voters are available.'),
            Forms\Components\Repeater::make('position_configs')
                ->label('Position Winners')
                ->defaultItems(0)
                ->reorderable(false)
                ->collapsible()
                ->visible(fn (callable $get) => $get('seed_mode') === 'selected_winners')
                ->schema([
                    Forms\Components\Select::make('position_id')
                        ->label('Position')
                        ->options(fn () => $this->getPositionOptions($this->election_id))
                        ->searchable()
                        ->reactive()
                        ->required(),
                    Forms\Components\Select::make('winner_candidate_ids')
                        ->label('Candidates to Win')
                        ->options(function (callable $get) {
                            return $this->getCandidateOptions($get('position_id'));
                        })
                        ->multiple()
                        ->searchable()
                        ->required()
                        ->helperText('Select up to the number of winners allowed for this position. Only eligible voters will be assigned to these candidates.'),
                ])
                ->helperText('Add one row per position you want to seed.'),
            Forms\Components\Repeater::make('boost_position_configs')
                ->label('Boost Position Turnout Ranges')
                ->defaultItems(0)
                ->reorderable(false)
                ->collapsible()
                ->visible(fn (callable $get) => $get('seed_mode') === 'boost_existing')
                ->schema([
                    Forms\Components\Select::make('position_id')
                        ->label('Position')
                        ->options(fn () => $this->getPositionOptions($this->election_id))
                        ->searchable()
                        ->required(),
                    Forms\Components\TextInput::make('min_turnout_percent')
                        ->label('Min %')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->default(70)
                        ->required(),
                    Forms\Components\TextInput::make('max_turnout_percent')
                        ->label('Max %')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->default(83)
                        ->required(),
                ])
                ->helperText('Seed each selected position to a turnout range using the existing election turnout pool only. Default range is 70% to 83%.'),
        ];
    }

    public function submit()
    {
        $election = Election::find($this->election_id);

        if (!$election) {
            $this->notifyError('Election not found.');

            return;
        }

        if ($this->seed_mode === 'selected_winners' && blank($this->position_configs)) {
            $this->notifyError('Add at least one position configuration.');

            return;
        }

        $totalVoters = Voter::count();
        $targetTurnout = $this->seed_mode === 'boost_existing'
            ? (int) floor($totalVoters * 0.83)
            : $this->resolveTurnoutCount($totalVoters);

        if ($targetTurnout < 1) {
            $this->notifyError('Target turnout must be at least 1 voter.');

            return;
        }

        $existingParticipantIds = VoterElectionParticipation::where('election_id', $election->id)
            ->pluck('voter_id')
            ->all();

        $existingTurnout = count($existingParticipantIds);

        if ($existingTurnout > $targetTurnout) {
            $this->notifyError("Current turnout is already {$existingTurnout}, which is above the target turnout of {$targetTurnout}.");

            return;
        }

        $existingParticipants = Voter::with('course')
            ->whereIn('id', $existingParticipantIds)
            ->get()
            ->keyBy('id');

        $departmentTurnoutPlan = ($this->balance_department_turnout && $this->seed_mode !== 'random_high_votes')
            ? $this->buildDepartmentTurnoutPlan($targetTurnout, $existingParticipants)
            : null;

        $newParticipantIds = ($this->balance_department_turnout && $this->seed_mode !== 'random_high_votes')
            ? $this->selectNewParticipantIdsByDepartment(
                $existingParticipantIds,
                $targetTurnout - $existingTurnout,
                $departmentTurnoutPlan
            )
            : Voter::query()
                ->whereNotIn('id', $existingParticipantIds)
                ->inRandomOrder()
                ->limit($targetTurnout - $existingTurnout)
                ->pluck('id')
                ->all();

        $participantIds = array_values(array_unique([
            ...$existingParticipantIds,
            ...$newParticipantIds,
        ]));

        $participants = $existingParticipants->merge(
            Voter::with('course')
                ->whereIn('id', $newParticipantIds)
                ->get()
                ->keyBy('id')
        );

        $positionPayloads = match ($this->seed_mode) {
            'boost_existing' => $this->buildBoostPayloads($election->id, $participants),
            'random_high_votes' => $this->buildRandomHighVotePayloads($election->id),
            default => $this->buildPositionPayloads($election->id),
        };

        if ($positionPayloads === null) {
            return;
        }

        $positionIds = collect($positionPayloads)->pluck('position.id')->all();
        $existingVoteKeys = Vote::query()
            ->where('election_id', $election->id)
            ->whereIn('voter_id', $participantIds)
            ->whereIn('position_id', $positionIds)
            ->get(['voter_id', 'position_id'])
            ->mapWithKeys(fn (Vote $vote) => [$vote->voter_id . ':' . $vote->position_id => true])
            ->all();

        $totalVotesAdded = 0;
        $positionSummaries = [];

        DB::transaction(function () use (
            $election,
            $newParticipantIds,
            $participants,
            &$participantIds,
            &$departmentTurnoutPlan,
            &$positionPayloads,
            &$existingVoteKeys,
            &$totalVotesAdded,
            &$positionSummaries
        ) {
            foreach ($newParticipantIds as $voterId) {
                VoterElectionParticipation::firstOrCreate(
                    [
                        'voter_id' => $voterId,
                        'election_id' => $election->id,
                    ],
                    [
                        'participated_at' => now(),
                    ]
                );
            }

            foreach ($positionPayloads as &$payload) {
                $seededForPosition = 0;
                $skippedForPosition = 0;
                $positionVoteLimit = $this->resolvePositionVoteLimit($payload, $participants);
                $minimumSeededForPosition = $this->applyMinimumVotesForPayload(
                    $payload,
                    $participants,
                    $participantIds,
                    $departmentTurnoutPlan,
                    $existingVoteKeys,
                    $election->id,
                    $positionVoteLimit
                );

                $seededForPosition += $minimumSeededForPosition['seeded'];
                $skippedForPosition += $minimumSeededForPosition['skipped'];

                foreach ($participants as $participant) {
                    if (($payload['assigned_votes'] ?? 0) >= $positionVoteLimit) {
                        break;
                    }

                    $voteKey = $participant->id . ':' . $payload['position']->id;

                    if (isset($existingVoteKeys[$voteKey])) {
                        continue;
                    }

                    $eligibleWinnerCandidates = $payload['candidates']->filter(
                        fn (Candidate $candidate) => $this->candidateCanReceiveVoteFromVoter($candidate, $payload['position'], $participant)
                    );

                    if ($eligibleWinnerCandidates->isEmpty()) {
                        $skippedForPosition++;

                        continue;
                    }

                    $candidate = $this->pickCandidateForPayload($payload, $eligibleWinnerCandidates);

                    Vote::create([
                        'voter_id' => $participant->id,
                        'candidate_id' => $candidate->id,
                        'position_id' => $payload['position']->id,
                        'election_id' => $election->id,
                    ]);

                    $payload['tallies'][$candidate->id] = ($payload['tallies'][$candidate->id] ?? 0) + 1;
                    $payload['assigned_votes'] = ($payload['assigned_votes'] ?? 0) + 1;
                    $existingVoteKeys[$voteKey] = true;
                    $seededForPosition++;
                    $totalVotesAdded++;
                }

                $totalVotesAdded += $minimumSeededForPosition['seeded'];

                $positionSummary = "{$payload['position']->name}: {$seededForPosition} votes seeded";
                if ($minimumSeededForPosition['seeded'] > 0) {
                    $positionSummary .= ", {$minimumSeededForPosition['seeded']} used for minimums";
                }
                if ($minimumSeededForPosition['unmet'] > 0) {
                    $positionSummary .= ", {$minimumSeededForPosition['unmet']} minimum votes could not be assigned";
                }
                if ($skippedForPosition > 0) {
                    $positionSummary .= ", {$skippedForPosition} voters had no eligible selected winner";
                }

                $positionSummaries[] = $positionSummary;
            }
        });

        $finalTurnout = count($participantIds);
        $turnoutMessage = $finalTurnout === $targetTurnout
            ? "Election turnout is now {$finalTurnout} voters"
            : "Election turnout is now {$finalTurnout} voters, versus a target of {$targetTurnout}";

        Notification::make()
            ->title('Votes seeded')
            ->body(
                "{$turnoutMessage} using {$this->getSeedModeLabel()}. Added {$totalVotesAdded} votes. " . implode('; ', $positionSummaries)
            )
            ->success()
            ->send();
    }

    private function buildBoostPayloads(int $electionId, EloquentCollection $participants): ?array
    {
        $boostConfigs = blank($this->boost_position_configs)
            ? Position::query()
                ->where('election_id', $electionId)
                ->orderBy('name')
                ->get()
                ->map(fn (Position $position) => [
                    'position_id' => $position->id,
                    'min_turnout_percent' => 70,
                    'max_turnout_percent' => 83,
                ])
                ->all()
            : $this->boost_position_configs;

        $positionIds = collect($boostConfigs)
            ->pluck('position_id')
            ->filter()
            ->values();

        if ($positionIds->unique()->count() !== $positionIds->count()) {
            $this->notifyError('Each boost position can only be configured once.');

            return null;
        }

        $positions = Position::with([
            'candidates' => fn ($query) => $query
                ->where('election_id', $electionId)
                ->with(['voter.course']),
        ])
            ->where('election_id', $electionId)
            ->whereIn('id', $positionIds)
            ->get();

        if ($positions->isEmpty()) {
            $this->notifyError('No positions found for the selected election.');

            return null;
        }

        $payloads = [];

        foreach ($boostConfigs as $config) {
            $position = $positions->firstWhere('id', $config['position_id'] ?? null);

            if (!$position) {
                $this->notifyError('One of the selected boost positions does not belong to the selected election.');

                return null;
            }

            $candidates = $position->candidates->values();

            if ($candidates->isEmpty()) {
                continue;
            }

            $tallies = $this->getCurrentTallies($candidates, $electionId);
            $assignedVotes = array_sum($tallies);
            $targetVotes = $this->resolveBoostTargetVotes($config, $position, $participants, $assignedVotes);

            if ($targetVotes === null) {
                return null;
            }

            $payloads[] = [
                'position' => $position,
                'candidates' => $candidates,
                'tallies' => $tallies,
                'mode' => 'weighted',
                'assigned_votes' => $assignedVotes,
                'target_votes' => $targetVotes,
                'preferred_candidate_ids' => $this->resolvePreferredCandidateIds($position, $candidates, $tallies),
            ];
        }

        if (empty($payloads)) {
            $this->notifyError('No positions with candidates were found for the selected election.');

            return null;
        }

        return $payloads;
    }

    private function buildRandomHighVotePayloads(int $electionId): ?array
    {
        $positions = Position::with([
            'candidates' => fn ($query) => $query
                ->where('election_id', $electionId)
                ->with(['voter.course']),
        ])
            ->where('election_id', $electionId)
            ->orderBy('name')
            ->get();

        if ($positions->isEmpty()) {
            $this->notifyError('No positions found for the selected election.');

            return null;
        }

        $payloads = [];

        foreach ($positions as $position) {
            $candidates = $position->candidates->values();

            if ($candidates->isEmpty()) {
                continue;
            }

            $payloads[] = [
                'position' => $position,
                'candidates' => $candidates,
                'tallies' => $this->getCurrentTallies($candidates, $electionId),
                'mode' => 'random_high',
                'assigned_votes' => $this->getPositionAssignedVoteCount($position->id, $electionId),
            ];
        }

        if (empty($payloads)) {
            $this->notifyError('No positions with candidates were found for the selected election.');

            return null;
        }

        return $payloads;
    }

    private function buildDepartmentTurnoutPlan(int $targetTurnout, EloquentCollection $existingParticipants): array
    {
        $departmentTotals = Voter::query()
            ->join('courses', 'voters.course_id', '=', 'courses.id')
            ->select('courses.department_id', DB::raw('count(*) as total'))
            ->groupBy('courses.department_id')
            ->pluck('total', 'courses.department_id')
            ->map(fn ($count) => (int) $count)
            ->all();

        $currentCounts = $this->tallyParticipantsByDepartment($existingParticipants);
        $targets = $this->allocateDepartmentTargets($departmentTotals, $targetTurnout);

        return [
            'totals' => $departmentTotals,
            'targets' => $targets,
            'current_counts' => $currentCounts,
        ];
    }

    private function selectNewParticipantIdsByDepartment(
        array $existingParticipantIds,
        int $needed,
        ?array &$departmentTurnoutPlan
    ): array {
        if ($needed <= 0 || $departmentTurnoutPlan === null) {
            return [];
        }

        $currentCounts = $departmentTurnoutPlan['current_counts'];
        $targets = $departmentTurnoutPlan['targets'];
        $totals = $departmentTurnoutPlan['totals'];
        $desiredAdds = [];
        $allocated = 0;

        foreach ($targets as $departmentId => $target) {
            $current = $currentCounts[$departmentId] ?? 0;
            $capacity = max(0, ($totals[$departmentId] ?? 0) - $current);
            $add = min($capacity, max(0, $target - $current));
            $desiredAdds[$departmentId] = $add;
            $allocated += $add;
        }

        $remaining = max(0, $needed - $allocated);

        if ($remaining > 0) {
            $weights = [];
            $caps = [];

            foreach ($totals as $departmentId => $total) {
                $alreadyAllocated = $desiredAdds[$departmentId] ?? 0;
                $current = $currentCounts[$departmentId] ?? 0;
                $capacity = max(0, $total - $current - $alreadyAllocated);

                if ($capacity > 0) {
                    $weights[$departmentId] = $capacity;
                    $caps[$departmentId] = $capacity;
                }
            }

            foreach ($this->distributeByWeights($remaining, $weights, $caps) as $departmentId => $extra) {
                $desiredAdds[$departmentId] = ($desiredAdds[$departmentId] ?? 0) + $extra;
            }
        }

        $newIds = [];
        foreach ($desiredAdds as $departmentId => $count) {
            if ($count < 1) {
                continue;
            }

            $selectedIds = Voter::query()
                ->join('courses', 'voters.course_id', '=', 'courses.id')
                ->where('courses.department_id', $departmentId)
                ->whereNotIn('voters.id', [...$existingParticipantIds, ...$newIds])
                ->inRandomOrder()
                ->limit($count)
                ->pluck('voters.id')
                ->all();

            $newIds = [...$newIds, ...$selectedIds];
            $departmentTurnoutPlan['current_counts'][$departmentId] = ($departmentTurnoutPlan['current_counts'][$departmentId] ?? 0) + count($selectedIds);
        }

        return $newIds;
    }

    private function buildPositionPayloads(int $electionId): ?array
    {
        $positionIds = collect($this->position_configs)
            ->pluck('position_id')
            ->filter()
            ->values();

        if ($positionIds->unique()->count() !== $positionIds->count()) {
            $this->notifyError('Each position can only be configured once.');

            return null;
        }

        $positions = Position::with([
            'candidates' => fn ($query) => $query
                ->where('election_id', $electionId)
                ->with(['voter.course']),
        ])
            ->where('election_id', $electionId)
            ->whereIn('id', $positionIds)
            ->get()
            ->keyBy('id');

        $payloads = [];

        foreach ($this->position_configs as $config) {
            $position = $positions->get($config['position_id'] ?? null);

            if (!$position) {
                $this->notifyError('One of the selected positions does not belong to the selected election.');

                return null;
            }

            $winnerIds = collect($config['winner_candidate_ids'] ?? [])
                ->filter()
                ->values();

            if ($winnerIds->isEmpty()) {
                $this->notifyError("Select at least one winning candidate for {$position->name}.");

                return null;
            }

            if ($winnerIds->count() > max(1, (int) $position->max_winners)) {
                $this->notifyError("{$position->name} only allows {$position->max_winners} winner(s).");

                return null;
            }

            $candidates = $position->candidates
                ->whereIn('id', $winnerIds)
                ->values();

            if ($candidates->count() !== $winnerIds->count()) {
                $this->notifyError("One or more selected candidates for {$position->name} are invalid.");

                return null;
            }

            $payloads[] = [
                'position' => $position,
                'candidates' => $candidates,
                'tallies' => $this->getCurrentTallies($candidates, $electionId),
                'mode' => 'balanced',
                'assigned_votes' => $this->getPositionAssignedVoteCount($position->id, $electionId),
            ];
        }

        return $payloads;
    }

    private function getCurrentTallies(EloquentCollection $candidates, int $electionId): array
    {
        return Vote::query()
            ->selectRaw('candidate_id, count(*) as total_votes')
            ->where('election_id', $electionId)
            ->whereIn('candidate_id', $candidates->pluck('id'))
            ->groupBy('candidate_id')
            ->pluck('total_votes', 'candidate_id')
            ->map(fn ($count) => (int) $count)
            ->all();
    }

    private function getPositionAssignedVoteCount(int $positionId, int $electionId): int
    {
        return Vote::query()
            ->where('election_id', $electionId)
            ->where('position_id', $positionId)
            ->count();
    }

    private function resolveBoostTargetVotes(
        array $config,
        Position $position,
        EloquentCollection $participants,
        int $currentAssignedVotes
    ): ?int {
        $minPercent = max(0, min(100, (int) ($config['min_turnout_percent'] ?? 0)));
        $maxPercent = max(0, min(100, (int) ($config['max_turnout_percent'] ?? 0)));

        if ($minPercent > $maxPercent) {
            $this->notifyError("{$position->name} has an invalid turnout range.");

            return null;
        }

        $eligibleParticipantCount = $participants
            ->filter(function (Voter $participant) use ($position) {
                return $position->candidates->contains(
                    fn (Candidate $candidate) => $this->candidateCanReceiveVoteFromVoter($candidate, $position, $participant)
                );
            })
            ->count();

        $maxAllowedVotes = $this->getPositionMaxAllowedVotes($position, $eligibleParticipantCount);
        $minVotes = (int) ceil(($participants->count() * $minPercent) / 100);
        $maxVotes = (int) floor(($participants->count() * $maxPercent) / 100);
        $minVotes = min($maxAllowedVotes, $minVotes);
        $maxVotes = min($maxAllowedVotes, $maxVotes);

        if ($maxVotes < $minVotes) {
            $this->notifyError("{$position->name} cannot reach the requested turnout range with the current election turnout.");

            return null;
        }

        if ($currentAssignedVotes > $maxVotes) {
            $this->notifyError("{$position->name} already has {$currentAssignedVotes} votes, above the configured max turnout range.");

            return null;
        }

        return random_int(max($currentAssignedVotes, $minVotes), $maxVotes);
    }

    private function applyMinimumVotesForPayload(
        array &$payload,
        EloquentCollection &$participants,
        array &$participantIds,
        ?array &$departmentTurnoutPlan,
        array &$existingVoteKeys,
        int $electionId,
        int $positionVoteLimit
    ): array {
        $minimumVotes = max(0, (int) $this->minimum_votes);

        if (in_array(($payload['mode'] ?? null), ['weighted', 'random_high'], true) && $payload['candidates']->count() > 1) {
            $minimumVotes = max(1, $minimumVotes);
        }

        if ($minimumVotes === 0) {
            return ['seeded' => 0, 'skipped' => 0, 'unmet' => 0];
        }

        $seeded = 0;
        $skipped = 0;
        $unmet = 0;

        foreach ($payload['candidates'] as $candidate) {
            while (($payload['tallies'][$candidate->id] ?? 0) < $minimumVotes) {
                if (($payload['assigned_votes'] ?? 0) >= $positionVoteLimit) {
                    $unmet += $minimumVotes - ($payload['tallies'][$candidate->id] ?? 0);
                    $skipped++;
                    break;
                }

                $participant = $this->findEligibleParticipantForCandidate(
                    $candidate,
                    $payload['position'],
                    $participants,
                    $participantIds,
                    $departmentTurnoutPlan,
                    $existingVoteKeys,
                    $electionId
                );

                if (!$participant) {
                    $unmet += $minimumVotes - ($payload['tallies'][$candidate->id] ?? 0);
                    $skipped++;
                    break;
                }

                Vote::create([
                    'voter_id' => $participant->id,
                    'candidate_id' => $candidate->id,
                    'position_id' => $payload['position']->id,
                    'election_id' => $electionId,
                ]);

                $payload['tallies'][$candidate->id] = ($payload['tallies'][$candidate->id] ?? 0) + 1;
                $payload['assigned_votes'] = ($payload['assigned_votes'] ?? 0) + 1;
                $existingVoteKeys[$participant->id . ':' . $payload['position']->id] = true;
                $seeded++;
            }
        }

        return [
            'seeded' => $seeded,
            'skipped' => $skipped,
            'unmet' => $unmet,
        ];
    }

    private function resolvePositionVoteLimit(array $payload, EloquentCollection $participants): int
    {
        if (($payload['mode'] ?? null) === 'weighted' && isset($payload['target_votes'])) {
            return (int) $payload['target_votes'];
        }

        $eligibleCount = $participants
            ->filter(function (Voter $participant) use ($payload) {
                return $payload['candidates']->contains(
                    fn (Candidate $candidate) => $this->candidateCanReceiveVoteFromVoter($candidate, $payload['position'], $participant)
                );
            })
            ->count();

        if ($payload['candidates']->count() === 1 && $eligibleCount > 1) {
            return max(0, $eligibleCount - 1);
        }

        return $eligibleCount;
    }

    private function getPositionMaxAllowedVotes(Position $position, int $eligibleCount): int
    {
        if ($position->candidates->count() === 1 && $eligibleCount > 1) {
            return max(0, $eligibleCount - 1);
        }

        return $eligibleCount;
    }

    private function tallyParticipantsByDepartment(EloquentCollection $participants): array
    {
        $counts = [];

        foreach ($participants as $participant) {
            $departmentId = $participant->course?->department_id;
            if ($departmentId === null) {
                continue;
            }

            $counts[$departmentId] = ($counts[$departmentId] ?? 0) + 1;
        }

        return $counts;
    }

    private function allocateDepartmentTargets(array $departmentTotals, int $targetTurnout): array
    {
        $totalVoters = array_sum($departmentTotals);

        if ($totalVoters === 0 || $targetTurnout <= 0) {
            return array_fill_keys(array_keys($departmentTotals), 0);
        }

        $base = [];
        $remainders = [];
        $allocated = 0;

        foreach ($departmentTotals as $departmentId => $departmentTotal) {
            $exact = ($departmentTotal / $totalVoters) * $targetTurnout;
            $floor = min($departmentTotal, (int) floor($exact));
            $base[$departmentId] = $floor;
            $remainders[$departmentId] = $exact - $floor;
            $allocated += $floor;
        }

        $remaining = max(0, $targetTurnout - $allocated);
        arsort($remainders);

        foreach (array_keys($remainders) as $departmentId) {
            if ($remaining === 0) {
                break;
            }

            if (($base[$departmentId] ?? 0) >= ($departmentTotals[$departmentId] ?? 0)) {
                continue;
            }

            $base[$departmentId]++;
            $remaining--;
        }

        return $base;
    }

    private function distributeByWeights(int $total, array $weights, array $caps = []): array
    {
        $distribution = array_fill_keys(array_keys($weights), 0);

        if ($total <= 0 || empty($weights) || array_sum($weights) <= 0) {
            return $distribution;
        }

        $weightSum = array_sum($weights);
        $remainders = [];
        $allocated = 0;

        foreach ($weights as $key => $weight) {
            $exact = ($weight / $weightSum) * $total;
            $floor = (int) floor($exact);
            $cap = $caps[$key] ?? PHP_INT_MAX;
            $distribution[$key] = min($cap, $floor);
            $remainders[$key] = $exact - $floor;
            $allocated += $distribution[$key];
        }

        $remaining = max(0, $total - $allocated);
        arsort($remainders);

        while ($remaining > 0) {
            $progress = false;

            foreach (array_keys($remainders) as $key) {
                $cap = $caps[$key] ?? PHP_INT_MAX;
                if ($distribution[$key] >= $cap) {
                    continue;
                }

                $distribution[$key]++;
                $remaining--;
                $progress = true;

                if ($remaining === 0) {
                    break;
                }
            }

            if (!$progress) {
                break;
            }
        }

        return $distribution;
    }

    private function findEligibleParticipantForCandidate(
        Candidate $candidate,
        Position $position,
        EloquentCollection &$participants,
        array &$participantIds,
        ?array &$departmentTurnoutPlan,
        array &$existingVoteKeys,
        int $electionId
    ): ?Voter {
        $participant = $participants
            ->shuffle()
            ->first(function (Voter $participant) use ($candidate, $position, $existingVoteKeys) {
                $voteKey = $participant->id . ':' . $position->id;

                if (isset($existingVoteKeys[$voteKey])) {
                    return false;
                }

                return $this->candidateCanReceiveVoteFromVoter($candidate, $position, $participant);
            });

        if ($participant) {
            return $participant;
        }

        if (($position->id && $this->seed_mode === 'boost_existing') || !$this->allow_turnout_overflow) {
            return null;
        }

        $extraParticipants = Voter::with('course')
            ->whereNotIn('id', $participantIds)
            ->inRandomOrder()
            ->get()
            ->filter(fn (Voter $voter) => $this->candidateCanReceiveVoteFromVoter($candidate, $position, $voter))
            ->values();

        if ($extraParticipants->isEmpty()) {
            return null;
        }

        $extraParticipant = $this->balance_department_turnout && $departmentTurnoutPlan !== null
            ? $this->pickParticipantByDepartmentNeed($extraParticipants, $departmentTurnoutPlan)
            : $extraParticipants->first();

        if (!$extraParticipant) {
            return null;
        }

        VoterElectionParticipation::firstOrCreate(
            [
                'voter_id' => $extraParticipant->id,
                'election_id' => $electionId,
            ],
            [
                'participated_at' => now(),
            ]
        );

        $participants->put($extraParticipant->id, $extraParticipant);
        $participantIds[] = $extraParticipant->id;
        $this->incrementDepartmentTurnoutPlan($departmentTurnoutPlan, $extraParticipant);

        return $extraParticipant;
    }

    private function pickCandidateForPayload(array $payload, EloquentCollection $eligibleCandidates): Candidate
    {
        if (($payload['mode'] ?? 'balanced') === 'weighted') {
            return $this->pickWeightedCandidate(
                $eligibleCandidates,
                $payload['tallies'],
                $payload['preferred_candidate_ids'] ?? []
            );
        }

        if (($payload['mode'] ?? 'balanced') === 'random_high') {
            return $this->pickRandomHighVoteCandidate($eligibleCandidates, $payload['tallies']);
        }

        return $eligibleCandidates
            ->sortBy(fn (Candidate $candidate) => $payload['tallies'][$candidate->id] ?? 0)
            ->first();
    }

    private function pickWeightedCandidate(
        EloquentCollection $eligibleCandidates,
        array $tallies,
        array $preferredCandidateIds = []
    ): Candidate
    {
        $pool = !empty($preferredCandidateIds)
            ? $eligibleCandidates->whereIn('id', $preferredCandidateIds)->values()
            : $eligibleCandidates->values();

        if ($pool->isEmpty()) {
            $pool = $eligibleCandidates->values();
        }

        $weightedCandidates = $pool
            ->map(function (Candidate $candidate) use ($tallies) {
                return [
                    'candidate' => $candidate,
                    'weight' => max(1, $tallies[$candidate->id] ?? 0),
                ];
            })
            ->values();

        $totalWeight = $weightedCandidates->sum('weight');
        $roll = random_int(1, $totalWeight);
        $runningWeight = 0;

        foreach ($weightedCandidates as $item) {
            $runningWeight += $item['weight'];

            if ($roll <= $runningWeight) {
                return $item['candidate'];
            }
        }

        return $weightedCandidates->last()['candidate'];
    }

    private function resolvePreferredCandidateIds(
        Position $position,
        EloquentCollection $candidates,
        array $tallies
    ): array {
        $winnerCount = max(1, (int) $position->max_winners);

        return $candidates
            ->sortByDesc(fn (Candidate $candidate) => $tallies[$candidate->id] ?? 0)
            ->take($winnerCount)
            ->pluck('id')
            ->all();
    }

    private function pickRandomHighVoteCandidate(EloquentCollection $eligibleCandidates, array $tallies): Candidate
    {
        $ordered = $eligibleCandidates
            ->sortBy(fn (Candidate $candidate) => $tallies[$candidate->id] ?? 0)
            ->values();

        $lowestCount = $tallies[$ordered->first()->id] ?? 0;
        $lowestGroup = $ordered
            ->takeWhile(fn (Candidate $candidate) => ($tallies[$candidate->id] ?? 0) === $lowestCount)
            ->values();

        return $lowestGroup->random();
    }

    private function pickParticipantByDepartmentNeed(EloquentCollection $participants, array $departmentTurnoutPlan): ?Voter
    {
        return $participants
            ->sortByDesc(function (Voter $participant) use ($departmentTurnoutPlan) {
                $departmentId = $participant->course?->department_id;
                $target = $departmentTurnoutPlan['targets'][$departmentId] ?? 0;
                $current = $departmentTurnoutPlan['current_counts'][$departmentId] ?? 0;

                return $target - $current;
            })
            ->first();
    }

    private function incrementDepartmentTurnoutPlan(?array &$departmentTurnoutPlan, Voter $participant): void
    {
        if ($departmentTurnoutPlan === null) {
            return;
        }

        $departmentId = $participant->course?->department_id;
        if ($departmentId === null) {
            return;
        }

        $departmentTurnoutPlan['current_counts'][$departmentId] = ($departmentTurnoutPlan['current_counts'][$departmentId] ?? 0) + 1;
    }

    private function candidateCanReceiveVoteFromVoter(Candidate $candidate, Position $position, Voter $voter): bool
    {
        if ($candidate->position_id !== $position->id || $candidate->election_id !== $position->election_id) {
            return false;
        }

        $voterDepartmentId = $voter->course?->department_id ?? $voter->department_id;
        $candidateDepartmentId = $candidate->department_id
            ?? $candidate->voter?->course?->department_id
            ?? $candidate->voter?->department_id;
        $candidateCourseId = $candidate->course_id ?? $candidate->voter?->course_id;
        $candidateYearLevel = $candidate->voter?->year_level;

        return match ($position->level) {
            'university' => true,
            'department' => $candidateDepartmentId !== null && $candidateDepartmentId === $voterDepartmentId,
            'course' => $candidateCourseId !== null && $candidateCourseId === $voter->course_id,
            'year_level' => $candidateYearLevel !== null && $candidateYearLevel === $voter->year_level,
            'department_year_level' => $candidateDepartmentId !== null
                && $candidateDepartmentId === $voterDepartmentId
                && $candidateYearLevel === $voter->year_level,
            'department_course_level' => $candidateDepartmentId !== null
                && $candidateDepartmentId === $voterDepartmentId
                && $candidateCourseId === $voter->course_id
                && $candidateYearLevel === $voter->year_level,
            default => false,
        };
    }

    private function resolveTurnoutCount(int $totalVoters): int
    {
        $value = (int) $this->turnout_value;

        if ($this->turnout_mode === 'percent') {
            $value = (int) floor(($totalVoters * max(0, min(100, $value))) / 100);
        }

        return min($totalVoters, $value);
    }

    private function getElectionOptions(): array
    {
        return Election::query()
            ->orderByDesc('is_active')
            ->orderByDesc('start_at')
            ->get()
            ->mapWithKeys(fn (Election $election) => [
                $election->id => $election->name . ($election->is_active ? ' (Active)' : ''),
            ])
            ->all();
    }

    private function getPositionOptions(?int $electionId): array
    {
        if (!$electionId) {
            return [];
        }

        return Position::query()
            ->where('election_id', $electionId)
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Position $position) => [
                $position->id => "{$position->name} ({$position->level}, max winners: {$position->max_winners})",
            ])
            ->all();
    }

    private function getCandidateOptions($positionId): array
    {
        if (!$positionId) {
            return [];
        }

        return Candidate::query()
            ->with(['voter.course.department'])
            ->where('position_id', $positionId)
            ->where('election_id', $this->election_id)
            ->get()
            ->mapWithKeys(function (Candidate $candidate) {
                $department = $candidate->department?->department_name
                    ?? $candidate->voter?->course?->department?->department_name;
                $course = $candidate->course?->course_abbreviation ?? $candidate->voter?->course?->course_abbreviation;
                $yearLevel = $candidate->voter?->year_level;
                $scope = collect([$department, $course, $yearLevel ? "Year {$yearLevel}" : null])
                    ->filter()
                    ->implode(' / ');

                $label = "{$candidate->voter->last_name}, {$candidate->voter->first_name}";
                if ($scope !== '') {
                    $label .= " ({$scope})";
                }

                return [$candidate->id => $label];
            })
            ->all();
    }

    private function notifyError(string $message): void
    {
        Notification::make()
            ->title('Vote seeding failed')
            ->body($message)
            ->danger()
            ->send();
    }

    private function getSeedModeLabel(): string
    {
        return match ($this->seed_mode) {
            'boost_existing' => 'boost current standings',
            'random_high_votes' => 'random high votes',
            default => 'selected winners',
        };
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('page_VoteSeeder') ?? false;
    }
}
