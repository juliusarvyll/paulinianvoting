<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Widgets\CandidatesByPositionWidget;

class Dashboard extends BaseDashboard
{
    public function getColumns(): int | array
    {
        // Force a single column layout at all breakpoints
        return [
            'sm' => 1,
            'md' => 1,
            'lg' => 1,
            'xl' => 1,
            '2xl' => 1,
        ];
    }

    public function getWidgets(): array
    {
        // Explicitly render only our widget on the dashboard
        return [
            CandidatesByPositionWidget::class,
        ];
    }
}
