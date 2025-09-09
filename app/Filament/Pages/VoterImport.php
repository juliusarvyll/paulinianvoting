<?php

namespace App\Filament\Pages;

use App\Services\VoterImportService;
use Filament\Forms;
use Filament\Pages\Page;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;

class VoterImport extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static string $view = 'filament.pages.voter-import';
    protected static ?string $navigationGroup = 'Utilities';
    protected static ?string $navigationLabel = 'Import Voters';
    protected static ?int $navigationSort = 3;

    public $jsonFile = null;

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\FileUpload::make('jsonFile')
                ->label('JSON File')
                ->acceptedFileTypes(['application/json'])
                ->helperText('Upload a JSON file containing voter information. The file should follow the required structure.')
                ->required(),
        ];
    }

    public function import()
    {
        try {
            $path = Storage::disk('local')->path($this->jsonFile);

            $importer = new VoterImportService();
            $importer->import($path);

            Storage::disk('local')->delete($this->jsonFile);
            $this->jsonFile = null;

            Notification::make()
                ->title('Import successful')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Import failed')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->can('page_VoterImport') ?? false;
    }
}
