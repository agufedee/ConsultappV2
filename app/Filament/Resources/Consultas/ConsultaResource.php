<?php

namespace App\Filament\Resources\Consultas;

use App\Filament\Resources\Consultas\Pages\CreateConsulta;
use App\Filament\Resources\Consultas\Pages\EditConsulta;
use App\Filament\Resources\Consultas\Pages\ListConsultas;
use App\Filament\Resources\Consultas\Pages\ViewConsulta;
use App\Models\Consulta;
use App\Models\Paciente;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ConsultaResource extends Resource
{
    protected static ?string $model = Consulta::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = 'Consulta';

    protected static ?string $pluralModelLabel = 'Consultas';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $recordTitleAttribute = 'motivo';

    public static function motivoOptions(): array
    {
        return [
            'primera_consulta' => 'Primera consulta',
            'control' => 'Control',
            'derivacion' => 'Derivación',
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return [];
    }

    public static function form(Schema $schema): Schema
    {
        $calculateImc = function (Set $set, Get $get): void {
            $peso = (float) $get('peso');
            $altura = (float) $get('altura');

            if ($altura <= 0) {
                $set('imc', null);

                return;
            }

            $set('imc', round($peso / ($altura / 100) ** 2, 2));
        };

        return $schema
            ->components([
                Section::make()
                    ->columns(2)
                    ->schema([
                        Select::make('paciente_id')
                            ->relationship('paciente', modifyQueryUsing: fn (Builder $query): Builder => $query->orderBy('nombre')->orderBy('apellido'))
                            ->getOptionLabelFromRecordUsing(fn (Paciente $record): string => $record->nombre_completo)
                            ->required()
                            ->preload()
                            ->searchable(['nombre', 'apellido'])
                            ->hidden(fn (HasSchemas $livewire): bool => $livewire instanceof RelationManager),
                        DatePicker::make('fecha')
                            ->required(),
                        Select::make('motivo')
                            ->options(static::motivoOptions())
                            ->required(),
                        TextInput::make('peso')
                            ->required()
                            ->numeric()
                            ->minValue(20)
                            ->maxValue(300)
                            ->step(0.1)
                            ->suffix('kg')
                            ->live(onBlur: true)
                            ->afterStateUpdated($calculateImc),
                        TextInput::make('altura')
                            ->required()
                            ->numeric()
                            ->minValue(100)
                            ->maxValue(250)
                            ->step(0.5)
                            ->suffix('cm')
                            ->live(onBlur: true)
                            ->afterStateUpdated($calculateImc),
                        TextInput::make('imc')
                            ->readOnly()
                            ->numeric()
                            ->nullable()
                            ->suffix('kg/m²'),
                        TextInput::make('circunferencia_cintura')
                            ->numeric()
                            ->suffix('cm'),
                        TextInput::make('circunferencia_cadera')
                            ->numeric()
                            ->suffix('cm'),
                        TextInput::make('porcentaje_grasa')
                            ->numeric()
                            ->suffix('%'),
                        Repeater::make('pliegues_cutaneos')
                            ->defaultItems(0)
                            ->schema([
                                TextInput::make('pliegue')
                                    ->required(),
                                TextInput::make('mm')
                                    ->required()
                                    ->numeric(),
                            ])
                            ->columns(2)
                            ->mutateDehydratedStateUsing(static function (Repeater $component, ?array $state): ?array {
                                $dehydrated = $component->dehydrateItems($state);

                                return empty($dehydrated) ? null : $dehydrated;
                            }),
                        Textarea::make('observaciones')
                            ->columnSpanFull(),
                        DatePicker::make('proximo_control'),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos de la consulta')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('fecha')
                            ->date('d/m/Y'),
                        TextEntry::make('motivo')
                            ->formatStateUsing(fn (string $state): string => static::motivoOptions()[$state] ?? $state),
                        TextEntry::make('peso')
                            ->suffix(' kg'),
                        TextEntry::make('altura')
                            ->suffix(' cm'),
                        TextEntry::make('imc')
                            ->suffix(' kg/m²')
                            ->placeholder('—'),
                        TextEntry::make('circunferencia_cintura')
                            ->placeholder('—'),
                        TextEntry::make('circunferencia_cadera')
                            ->placeholder('—'),
                        TextEntry::make('porcentaje_grasa')
                            ->placeholder('—'),
                        TextEntry::make('pliegues_cutaneos')
                            ->placeholder('—'),
                        TextEntry::make('observaciones')
                            ->columnSpanFull()
                            ->placeholder('—'),
                        TextEntry::make('proximo_control')
                            ->date('d/m/Y')
                            ->placeholder('—'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('motivo')
            ->columns([
                TextColumn::make('fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('motivo')
                    ->formatStateUsing(fn (string $state): string => static::motivoOptions()[$state] ?? $state),
                TextColumn::make('peso'),
                TextColumn::make('altura'),
                TextColumn::make('imc'),
            ])
            ->defaultSort('fecha', 'desc')
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConsultas::route('/'),
            'create' => CreateConsulta::route('/create'),
            'view' => ViewConsulta::route('/{record}'),
            'edit' => EditConsulta::route('/{record}/edit'),
        ];
    }
}
