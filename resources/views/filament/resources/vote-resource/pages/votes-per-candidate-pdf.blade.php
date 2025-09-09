<html>
<head>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        h1 { margin-bottom: 20px; }
        h2 { margin-top: 30px; margin-bottom: 10px; }
        h3 { margin-top: 20px; margin-bottom: 5px; }
        .dept-header { display: flex; align-items: center; margin-top: 30px; margin-bottom: 10px; }
        .dept-logo { height: 40px; margin-right: 12px; }
        .stats-table { width: 100%; margin-bottom: 30px; }
        .stats-table td { padding: 8px 16px; font-size: 15px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border-top: 1px solid #333; border-bottom: 1px solid #333; border-left: none; border-right: none; padding: 6px 8px; text-align: left; }
        th { background: #f0f0f0; }
        .dept-table { margin-top: 5px; margin-bottom: 10px; font-size: 11px; }
        .dept-table th, .dept-table td { border-top: 1px solid #bbb; border-bottom: 1px solid #bbb; border-left: none; border-right: none; padding: 3px 6px; }
    </style>
</head>
<body>
    {{-- University-wide header --}}
    @if(isset($levels['university']))
        <div style="width: 100%; display: flex; align-items: center; justify-content: center; margin-bottom: 10px; border-bottom: 2px solid #333; padding-bottom: 8px;">
            <img src="{{ public_path('assets/images/PSG logo.png') }}" alt="PSG Logo" style="height: 60px;">
        </div>
    @endif

    {{-- University-wide positions --}}
    @if(isset($levels['university']))
        <h2>University-wide Positions</h2>
        @foreach ($levels['university']['positions'] as $positionGroup)
            @php
                $isRepresentative = stripos($positionGroup['position']->name, 'representative') !== false;
            @endphp
            <h3>{{ $positionGroup['position']->name }}</h3>
            <table>
                <thead>
                    <tr>
                        <th>Image</th>
                        <th>Candidate Name</th>
                        <th>Total Votes</th>
                        @if(!$isRepresentative)
                            <th>Votes by Department</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach ($positionGroup['candidates'] as $item)
                        <tr>
                            <td>
                                @if($item['candidate']->photo_path)
                                    <img src="{{ public_path('storage/' . $item['candidate']->photo_path) }}" alt="Candidate Image" style="height:40px;">
                                @else
                                    <span>No image</span>
                                @endif
                            </td>
                            <td>{{ $item['voter']->name }}</td>
                            <td>{{ $item['votes_count'] }}</td>
                            @if(!$isRepresentative)
                                <td>
                                    @if (!empty($item['department_votes']))
                                        <table class="dept-table">
                                            <thead>
                                                <tr>
                                                    <th>Department</th>
                                                    <th>Votes</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($item['department_votes'] as $dept)
                                                    <tr>
                                                        <td>{{ $dept['departmentName'] }}</td>
                                                        <td>{{ $dept['votes'] }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    @else
                                        <span>No votes by department</span>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endforeach
    @endif

    {{-- Department-wide positions, grouped by department (all candidates per department, not per position) --}}
    @if(isset($levels['department']))
        @php
            // Gather all department names from all candidates in all positions
            $allDepartmentGroups = collect($levels['department']['positions'])
                ->flatMap(function($positionGroup) {
                    return collect($positionGroup['candidates'])->map(function($item) use ($positionGroup) {
                        $deptName = $item['candidate']->department->department_name ?? 'Unknown Department';
                        return [
                            'department_name' => $deptName,
                            'position' => $positionGroup['position'],
                            'candidate' => $item['candidate'],
                            'voter' => $item['voter'],
                            'votes_count' => $item['votes_count'],
                        ];
                    });
                })
                ->groupBy('department_name');
        @endphp
        @foreach ($allDepartmentGroups as $departmentName => $items)
            <div class="dept-header" style="display: flex; flex-direction: column; align-items: center; margin-bottom: 10px;">
                @php
                    $department = null;
                    if (isset($departments)) {
                        $department = collect($departments)->first(function($dept) use ($departmentName) {
                            return $dept->department_name === $departmentName;
                        });
                    }
                    $logoPath = $department ? $department->getRawOriginal('logo_path') : null;
                    $fullLogoPath = public_path('storage/' . ($logoPath ?? ''));
                @endphp
                @if ($logoPath)
                    <img src="{{ $fullLogoPath }}" alt="Logo" class="dept-logo" style="height: 100px; margin-bottom: 5px;">
                @endif
                <span style="font-weight: bold; font-size: 15px; text-align: center;">{{ $departmentName }}</span>
            </div>
            @php
                // Group by position name under this department
                $positions = collect($items)->groupBy(function($item) { return $item['position']->name; });
            @endphp
            @foreach ($positions as $positionName => $candidates)
                <h3>{{ $positionName }}</h3>
                <table>
                    <thead>
                        <tr>
                            <th>Image</th>
                            <th>Candidate Name</th>
                            <th>Total Votes</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($candidates->sortByDesc('votes_count') as $item)
                            <tr>
                                <td>
                                    @if($item['candidate']->photo_path)
                                        <img src="{{ public_path('storage/' . $item['candidate']->photo_path) }}" alt="Candidate Image" style="height:100px;">
                                    @else
                                        <span>No image</span>
                                    @endif
                                </td>
                                <td>{{ $item['voter']->name }}</td>
                                <td>{{ $item['votes_count'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endforeach
        @endforeach
    @endif

    {{-- You can add similar sections for course-wide and year-level if needed --}}

    <pre style="font-size:10px; color:#c00;">DEPARTMENTS TYPE: {{ gettype($departments) }}
    @php
        if (is_object($departments) && method_exists($departments, 'first')) {
            echo 'First department class: ' . get_class($departments->first()) . "\n";
        }
    @endphp
    DEPARTMENTS CONTENT:
    {{ print_r($departments, true) }}
    </pre>
    <pre style="font-size:10px; color:#c00;">DEPARTMENT TYPE: {{ gettype($department) }}
    @php
        if (is_object($department)) {
            echo 'Department class: ' . get_class($department) . "\n";
        }
    @endphp
    DEPARTMENT CONTENT:
    {{ print_r($department, true) }}
    </pre>

</body>
</html>
