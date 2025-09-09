<x-filament::page>
    <form wire:submit.prevent="import" class="space-y-6">
        {{ $this->form }}

        <div class="mt-4">
            <x-filament::button type="submit" wire:loading.attr="disabled">
                Import Voters
            </x-filament::button>
        </div>
    </form>

    <div class="mt-8">
        <h3 class="text-lg font-medium">JSON Format Example:</h3>
        <pre class="mt-2 p-4 bg-gray-100 rounded-lg overflow-x-auto">
{
    "departments": {
        "DEPT_CODE": {
            "name": "Department Name",
            "courses": {
                "COURSE_CODE": "Course Name"
            }
        }
    },
    "voters": [
        {
            "first_name": "First Name",
            "last_name": "Last Name",
            "email": "email@example.com",
            "student_number": "20230001",
            "department": "DEPT_CODE",
            "course": "COURSE_CODE",
            "year_level": 1,
            "votes": {
                "position_name": "candidate_student_number"
            }
        }
    ]
}</pre>
    </div>
</x-filament::page>
