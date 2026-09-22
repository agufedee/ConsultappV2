<?php

namespace App\Filament\Resources\Pacientes;

use App\Filament\Resources\Pacientes\Pages\CreatePaciente;
use App\Filament\Resources\Pacientes\Pages\EditPaciente;
use App\Filament\Resources\Pacientes\Pages\ListPacientes;
use App\Filament\Resources\Pacientes\Pages\ViewPaciente;
use App\Filament\Resources\Pacientes\RelationManagers\ConsultasRelationManager;
use App\Models\Paciente;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PacienteResource extends Resource
{
    protected static ?string $model = Paciente::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $modelLabel = 'Paciente';

    protected static ?string $pluralModelLabel = 'Pacientes';

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function getGloballySearchableAttributes(): array
    {
        return ['nombre', 'apellido', 'dni'];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextInput::make('nombre')
                            ->required()
                            ->maxLength(100),
                        TextInput::make('apellido')
                            ->required()
                            ->maxLength(100),
                        TextInput::make('dni')
                            ->nullable()
                            ->unique()
                            ->maxLength(20),
                        DatePicker::make('fecha_nacimiento'),
                        Select::make('sexo')
                            ->options([
                                'masculino' => 'Masculino',
                                'femenino' => 'Femenino',
                                'otro' => 'Otro',
                            ]),
                        TextInput::make('telefono')
                            ->maxLength(50),
                        TextInput::make('email')
                            ->email()
                            ->maxLength(100),
                        Textarea::make('antecedentes'),
                        DatePicker::make('fecha_alta')
                            ->default(now()),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos personales')
                    ->schema([
                        TextEntry::make('nombre'),
                        TextEntry::make('apellido'),
                        TextEntry::make('dni'),
                        TextEntry::make('sexo'),
                        TextEntry::make('fecha_nacimiento')
                            ->date('d/m/Y'),
                        TextEntry::make('edad')
                            ->placeholder('—'),
                        TextEntry::make('telefono'),
                        TextEntry::make('email'),
                        TextEntry::make('antecedentes')
                            ->columnSpanFull(),
                        TextEntry::make('fecha_alta')
                            ->date('d/m/Y'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nombre')
            ->columns([
                TextColumn::make('nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('apellido')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('dni')
                    ->searchable(),
                TextColumn::make('sexo')
                    ->toggleable(),
                TextColumn::make('fecha_alta')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('apellido')
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ConsultasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPacientes::route('/'),
            'create' => CreatePaciente::route('/create'),
            'view' => ViewPaciente::route('/{record}'),
            'edit' => EditPaciente::route('/{record}/edit'),
        ];
    }
}
