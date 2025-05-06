<?php

namespace App\Filament\Resources;

use App\Filament\Imports\VoterImporter;
use App\Filament\Resources\VoterResource\Pages;
use App\Models\Voter;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VoterResource extends Resource
{
    protected static ?string $model = Voter::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Voter Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('code')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\TextInput::make('last_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('first_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('middle_name')
                    ->maxLength(255),
                Forms\Components\Select::make('sex')
                    ->required()
                    ->options([
                        'Male' => 'Male',
                        'Female' => 'Female',
                    ]),
                Forms\Components\Select::make('course_id')
                    ->relationship('course', 'course_name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('year_level')
                    ->required()
                    ->options([
                        1 => '1st Year',
                        2 => '2nd Year',
                        3 => '3rd Year',
                        4 => '4th Year',
                        5 => '5th Year',
                    ]),
                Forms\Components\Toggle::make('has_voted')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('first_name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('middle_name')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('sex')
                    ->searchable(),
                Tables\Columns\TextColumn::make('course.course_name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('year_level')
                    ->sortable(),
                Tables\Columns\IconColumn::make('has_voted')
                    ->boolean(),
                Tables\Columns\TextColumn::make('votes_count')
                    ->counts('votes')
                    ->label('Votes Cast'),
                Tables\Columns\TextColumn::make('candidate_count')
                    ->counts('candidate')
                    ->label('Running As Candidate'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('course')
                    ->relationship('course', 'course_name')
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('year_level')
                    ->options([
                        1 => '1st Year',
                        2 => '2nd Year',
                        3 => '3rd Year',
                        4 => '4th Year',
                        5 => '5th Year',
                    ]),
                Tables\Filters\SelectFilter::make('sex')
                    ->options([
                        'Male' => 'Male',
                        'Female' => 'Female',
                    ]),
                Tables\Filters\TernaryFilter::make('has_voted'),
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
            'index' => Pages\ListVoters::route('/'),
            'create' => Pages\CreateVoter::route('/create'),
            'edit' => Pages\EditVoter::route('/{record}/edit'),
        ];
    }
}
