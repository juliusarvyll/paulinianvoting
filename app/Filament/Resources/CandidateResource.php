<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CandidateResource\Pages;
use App\Models\Candidate;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CandidateResource extends Resource
{
    protected static ?string $model = Candidate::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'Election Management';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()->can('view_any_candidate');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('election_id')
                    ->relationship('election', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('voter_id')
                    ->relationship(
                        'voter',
                        'last_name',
                        fn ($query) => $query->select(['id', 'last_name', 'first_name', 'middle_name'])
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record) =>
                        "{$record->last_name}, {$record->first_name} {$record->middle_name}"
                    )
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('position_id')
                    ->relationship(
                        'position',
                        'name',
                        fn ($query, $get) => $query->when(
                            $get('election_id'),
                            fn ($q) => $q->where('election_id', $get('election_id'))
                        )
                    )
                    ->getOptionLabelFromRecordUsing(fn ($record) =>
                        "{$record->name} ({$record->level})"
                    )
                    ->required()
                    ->searchable()
                    ->preload()
                    ->reactive()
                    ->disabled(fn ($get) => ! $get('election_id')),
                Forms\Components\Select::make('course_id')
                    ->relationship('course', 'course_name')
                    ->searchable()
                    ->preload()
                    ->visible(fn ($get) =>
                        $get('position_id') &&
                        \App\Models\Position::find($get('position_id'))?->level === 'course'
                    ),
                Forms\Components\Select::make('department_id')
                    ->relationship('department', 'department_name')
                    ->searchable()
                    ->preload()
                    ->reactive()
                    ->afterStateHydrated(function ($set, $get, $record) {
                        if ($record && $record->voter && $record->voter->course && $record->voter->course->department_id && !$get('department_id')) {
                            $set('department_id', $record->voter->course->department_id);
                        }
                    })
                    ->afterStateUpdated(function ($set, $get) {
                        $position = \App\Models\Position::find($get('position_id'));
                        if ($position && $position->level === 'department') {
                            $voter = \App\Models\Voter::find($get('voter_id'));
                            if ($voter && $voter->course && $voter->course->department_id) {
                                $set('department_id', $voter->course->department_id);
                            }
                        }
                    })
                    ->visible(fn ($get) =>
                        $get('position_id') &&
                        \App\Models\Position::find($get('position_id'))?->level === 'department'
                    )
                    ->disabled(fn ($get) =>
                        $get('position_id') &&
                        \App\Models\Position::find($get('position_id'))?->level === 'department'
                    ),
                Forms\Components\TextInput::make('slogan')
                    ->maxLength(255),
                Forms\Components\FileUpload::make('photo_path')
                    ->image()
                    ->directory('candidates'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('election.name')
                    ->label('Election')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('voter.last_name')
                    ->label('Voter Name')
                    ->formatStateUsing(fn ($record) =>
                        "{$record->voter->last_name}, {$record->voter->first_name} {$record->voter->middle_name}"
                    )
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('position.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('course.course_name')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('department.department_name')
                      ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('slogan')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\ImageColumn::make('photo_path')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('votes_count')
                    ->counts('votes')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('election')
                    ->relationship('election', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('position')
                    ->relationship('position', 'name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('course')
                    ->relationship('course', 'course_name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('department')
                    ->relationship('department', 'department_name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCandidates::route('/'),
            'create' => Pages\CreateCandidate::route('/create'),
            'edit' => Pages\EditCandidate::route('/{record}/edit'),
        ];
    }
}
