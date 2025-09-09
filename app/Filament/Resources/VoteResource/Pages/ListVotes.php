<?php

namespace App\Filament\Resources\VoteResource\Pages;

use App\Filament\Resources\VoteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVotes extends ListRecords
{
    protected static string $resource = VoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('exportVotesPdf')
                ->label('Export Votes PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->action(function () {
                    // Get all positions with their candidates and votes, grouped by level
                    $positions = \App\Models\Position::with(['candidates.voter', 'candidates.votes.voter'])
                        ->get()
                        ->groupBy('level');

                    // Move 'representative' from department to university level
                    if (isset($positions['department'])) {
                        $representatives = $positions['department']->filter(function ($position) {
                            return strtolower($position->name) === 'representative';
                        });
                        // Remove from department
                        $positions['department'] = $positions['department']->reject(function ($position) {
                            return strtolower($position->name) === 'representative';
                        });
                        // Add to university
                        if (!isset($positions['university'])) {
                            $positions['university'] = collect();
                        }
                        $positions['university'] = $positions['university']->concat($representatives);
                    }

                    // Get all departments for name lookup
                    $departments = \App\Models\Department::all()->keyBy('id');

                    // Prepare data: for each level, list positions, and for each position, list candidates and their vote counts, including department breakdown
                    $data = $positions->map(function ($positions, $level) use ($departments) {
                        return [
                            'level' => $level,
                            'positions' => $positions->map(function ($position) use ($departments) {
                                $candidates = $position->candidates->map(function ($candidate) use ($departments) {
                                    // Group votes by department
                                    $departmentVotes = [];
                                    foreach ($candidate->votes as $vote) {
                                        $deptId = $vote->voter->department_id;
                                        if (!$deptId) continue;
                                        if (!isset($departmentVotes[$deptId])) {
                                            $departmentVotes[$deptId] = [
                                                'votes' => 0,
                                                'departmentName' => $departments[$deptId]->department_name ?? 'Unknown',
                                            ];
                                        }
                                        $departmentVotes[$deptId]['votes']++;
                                    }
                                    // Sort by department name
                                    uasort($departmentVotes, function($a, $b) {
                                        return strcmp($a['departmentName'], $b['departmentName']);
                                    });
                                    return [
                                        'candidate' => $candidate,
                                        'votes_count' => $candidate->votes->count(),
                                        'voter' => $candidate->voter,
                                        'department_votes' => $departmentVotes,
                                    ];
                                })->sortByDesc('votes_count');
                                return [
                                    'position' => $position,
                                    'candidates' => $candidates,
                                ];
                            }),
                        ];
                    });

                    $pdf = app('dompdf.wrapper');
                    $pdf->loadView('filament.resources.vote-resource.pages.votes-per-candidate-pdf', [
                        'levels' => $data,
                        'departments' => $departments,
                    ]);
                    return response()->streamDownload(function () use ($pdf) {
                        echo $pdf->stream();
                    }, 'votes-per-candidate.pdf');
                }),
            Actions\CreateAction::make(),
        ];
    }
}
