@php
    /** @var ?int $electionId */
    /** @var ?string $electionName */
    /** @var \Illuminate\Support\Collection|array<\App\Models\Position> $positions */
    use Illuminate\Support\Facades\Storage;
    use Illuminate\Support\Str;
@endphp

<div class="space-y-6 w-full -mx-6 sm:-mx-8">
    <div class="flex items-center justify-between">
        <h3 class="text-base font-semibold">
            {{ static::$heading ?? 'Candidates by Position' }}
        </h3>
        @if($electionName)
            <span class="text-sm text-gray-500">Election: {{ $electionName }}</span>
        @endif
    </div>

    @forelse($positions as $position)
        <div class="rounded-xl border border-gray-200 dark:border-gray-700 overflow-hidden w-full">
            <div class="px-4 py-3 bg-gray-50 dark:bg-gray-800/50 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <div class="font-medium">{{ $position->name }}</div>
                </div>
            </div>

            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @forelse($position->candidates as $candidate)
                    @php
                        $voter = $candidate->voter;
                        $name = trim(($voter->last_name ?? '') . ', ' . ($voter->first_name ?? '') . ' ' . ($voter->middle_name ?? ''));
                        $photoPath = $candidate->photo_path ?? null;
                        $photoUrl = $photoPath ? (Str::startsWith($photoPath, ['http://', 'https://']) ? $photoPath : Storage::url($photoPath)) : null;
                    @endphp
                    <div class="px-4 py-3 flex items-center justify-between">
                        <div class="flex items-center gap-3 min-w-0">
                            @if($photoUrl)
                                <img src="{{ $photoUrl }}" alt="{{ $name }}" class="h-10 w-10 rounded-full object-cover ring-1 ring-gray-200 dark:ring-gray-700" />
                            @else
                                <div class="h-10 w-10 rounded-full bg-gray-200 dark:bg-gray-700 ring-1 ring-gray-200 dark:ring-gray-700 flex items-center justify-center text-xs text-gray-600 dark:text-gray-300">
                                    {{ strtoupper(Str::of(($voter->first_name ?? '') . ' ' . ($voter->last_name ?? ''))->substr(0, 2)) }}
                                </div>
                            @endif
                            <div class="min-w-0">
                                <div class="font-medium truncate">{{ $name }}</div>
                                @if($candidate->department?->department_name)
                                    <div class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $candidate->department->department_name }}</div>
                                @endif
                            </div>
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-300 whitespace-nowrap">
                            <span class="inline-flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                                {{ number_format($candidate->votes_count ?? 0) }} votes
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="px-4 py-6 text-sm text-gray-500">
                        No candidates.
                    </div>
                @endforelse
            </div>
        </div>
    @empty
        <div class="text-sm text-gray-500">
            No positions found for the active election.
        </div>
    @endforelse
</div>
