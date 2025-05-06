<?php

namespace App\Filament\Resources\VoterResource\Pages;

use App\Filament\Resources\VoterResource;
use App\Models\Course;
use App\Models\Voter;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;

class ListVoters extends ListRecords
{
    protected static string $resource = VoterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('import')
                ->label('Import CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->form([
                    Forms\Components\FileUpload::make('csv')
                        ->label('CSV File')
                        ->disk('local')
                        ->directory('csv-imports')
                        ->acceptedFileTypes(['text/csv', 'application/csv'])
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $csvPath = Storage::disk('local')->path($data['csv']);
                    $csv = Reader::createFromPath($csvPath, 'r');
                    $csv->setHeaderOffset(0);

                    $records = $csv->getRecords();
                    $importCount = 0;

                    foreach ($records as $record) {
                        // Find course based on abbreviation
                        $course = Course::where('course_abbreviation', trim($record['Course']))
                            ->orWhere('course_name', 'LIKE', '%' . trim($record['Course']) . '%')
                            ->first();

                        if (!$course) {
                            continue; // Skip if course not found
                        }

                        // Create or update voter record
                        Voter::updateOrCreate(
                            ['code' => $record['Code']],
                            [
                                'first_name' => trim($record['First Name']),
                                'middle_name' => trim($record['Middle Name'] ?? ''),
                                'last_name' => trim($record['Last Name']),
                                'sex' => $record['Sex'] === 'M' ? 'Male' : 'Female',
                                'course_id' => $course->id,
                                'year_level' => $record['Year'],
                                'has_voted' => false,
                            ]
                        );
                        $importCount++;
                    }

                    // Clean up the uploaded file
                    Storage::disk('local')->delete($data['csv']);

                    // Show a notification with the results
                    Notification::make()
                        ->title('Import Completed')
                        ->body("Successfully imported {$importCount} voters.")
                        ->success()
                        ->send();
                }),
            Actions\CreateAction::make(),
        ];
    }
}
