<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VoteResource\Pages;
use App\Models\Vote;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VoteResource extends Resource
{
    protected static ?string $model = Vote::class;

    protected static ?string $navigationIcon = 'heroicon-o-check-circle';

    protected static ?string $navigationGroup = 'Election Management';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('voter_id')
                    ->relationship('voter', 'name')
                    ->required()
                    ->searchable()
                    ->preload(),
                Forms\Components\Select::make('position_id')
                    ->relationship('position', 'name')
                    ->required()
                    ->searchable()
                    ->preload()
                    ->reactive(),
                Forms\Components\Select::make('candidate_id')
                    ->relationship('candidate', 'voter.name', fn ($query, $get) =>
                        $query->when($get('position_id'), fn ($query, $positionId) =>
                            $query->where('position_id', $positionId)
                        )
                    )
                    ->required()
                    ->searchable()
                    ->preload(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('voter.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('position.name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('candidate.voter.name')
                    ->label('Candidate')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('position')
                    ->relationship('position', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
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
            'index' => Pages\ListVotes::route('/'),
            'create' => Pages\CreateVote::route('/create'),
        ];
    }
}
